<?php

namespace Modules\UserMgt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DropdownCacheService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\UserMgt\Services\UserMgtService;

class UserController extends Controller
{
    protected DropdownCacheService $dropdownCacheService;
    protected UserMgtService $userMgtService;

    public function __construct(
        DropdownCacheService $dropdownCacheService,
        UserMgtService $userMgtService
    ) {
        $this->dropdownCacheService = $dropdownCacheService;
        $this->userMgtService = $userMgtService;
    }


    public function create()
    {
        return view('usermgt::users.create', [
            'title' => 'Add User',
        ]);
    }

    public function store(Request $request)
    {
        infoLogger('info.log', json_encode($request->all()));
        $userId = $request->input('id'); // present on edit, null on create
        $isEdit = filled($userId);

        try {
            $validated = $request->validate([
                'full_name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'gender' => ['nullable', Rule::in(['MALE', 'FEMALE'])],
                'password' => [$isEdit ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
                'active' => ['nullable', 'boolean'],
                'attachments' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ], [
                'password.confirmed' => 'Passwords do not match.',
            ]);

            // The file goes over as multipart separately; keep it out of the JSON fields.
            $photo = $request->file('attachments');
            unset($validated['attachments']);

            // Normalize the checkbox to a definite bool for ek-auth.
            $validated['active'] = $request->boolean('active');

            // Don't forward an empty password on edit (would overwrite with a blank hash downstream).
            if ($isEdit && blank($validated['password'] ?? null)) {
                unset($validated['password'], $validated['passconf'], $validated['password_confirmation']);
            }

            $result = $isEdit
                ? $this->userMgtService->updateUser($userId, $validated, $photo)
                : $this->userMgtService->storeUser($validated, $photo);

            if ($result !== null) {
                return $result;
            }

            return apiFail('Record could not be saved', 500, $validated, $userId);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['password', 'passconf', 'password_confirmation']),
            ]));

            return apiFail('An unexpected error occurred.', 500, [], $userId);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $user = $this->userMgtService->getUser($id);
        return view('usermgt::users.show', [
            'tenant' => authTenant(),
            'title' => strtoupper($user['full_name']),
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = $this->userMgtService->getUser($id);
        return view('usermgt::users.create', [
            'title' => 'Edit User',
            'user' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
    }

    public function dataTable(Request $request)
    {

        $dt = dtParams($request);

        $result = $this->userMgtService->getUsersDataTable($dt);

        return response()->json($result ?? [
            'draw' => $dt['draw'],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Failed to load item groups',
        ], $result ? 200 : 502);
    }

    /**
     * Render the roles pane: every tenant role, with the user's current ones pre-checked.
     */
    public function loadRoles(Request $request, string $id)
    {
        $roles = $this->userMgtService->listResource('/api/v1/internal/roles', 'listRoles', ['tenant' => authTenantId()]) ?? [];
        $userRoles = $this->userMgtService->listResource("/api/v1/internal/users/{$id}/roles", 'listUserRoles') ?? [];

        // syncRoles works on NAMES. Accept [{name}], [{label}], or bare ["Accountant"].
        $assignedNames = collect($userRoles)
            ->map(fn($r) => is_array($r) ? ($r['name'] ?? $r['label'] ?? null) : $r)
            ->filter()
            ->values()
            ->all();

        return view('usermgt::users.access_controls._roles', [
            'userId' => $id,
            'roles' => $roles,
            'assignedNames' => $assignedNames,
        ]);
    }

    /**
     * Render the permission matrix: the module registry with each module's current ordinal
     * level preselected. A module absent from the map is level 0 (NONE).
     */
    public function loadPermissions(Request $request, string $id)
    {
        // The catalog IS the module registry — two-level tree { key, label, max_level, children[] }.
        $modules = $this->userMgtService->listResource('/api/v1/internal/permissions/catalog', 'permissionCatalog', ['tenant' => authTenantId()]) ?? [];

        // The user's effective map: { module_key: level_int }. This is payload()['perms'] — the
        // same thing RemotePermissionResolver already consumes, so no new endpoint is needed.
        $assignments = $this->userMgtService->fetchResource("/api/v1/internal/users/{$id}/permissions", 'userPermissions') ?? [];

        return view('usermgt::users.access_controls._permissions', [
            'userId' => $id,
            'modules' => $modules,
            'assignments' => $assignments,
            // Levels come from the enum (via the catalog response), not a hardcoded local config —
            // one source of truth. The catalog ships max_level per module so the blade renders the
            // right number of columns (2 for containers, 4 for leaves).
            'levels' => $this->userMgtService->fetchResource('/api/v1/internal/permissions/levels', 'permissionLevels')
                ?? [
                    ['key' => 'none', 'label' => 'NONE', 'value' => 0],
                    ['key' => 'read', 'label' => 'READ', 'value' => 1],
                    ['key' => 'read_write', 'label' => 'READ/WRITE', 'value' => 2],
                    ['key' => 'full_control', 'label' => 'FULL CONTROL', 'value' => 3],
                ],
        ]);
    }

    /**
     * Persist both panes in one shot. Roles sync + module levels set, coalesced to a SINGLE
     * permission_version bump so downstream services refetch once, not twice.
     */
    public function save(Request $request, string $id)
    {
        $data = $request->validate([
            'roles' => 'sometimes|array',
            'roles.*' => 'string',
            'permissions' => 'sometimes|array',      // { module_key: level_int }
            'permissions.*' => 'integer|between:0,3',
        ]);

        $this->userMgtService->storeResource("/api/v1/internal/users/{$id}/access", [
            'roles' => $data['roles'] ?? [],
            'permissions' => $data['permissions'] ?? [],
        ], 'saveAccess');

        return back()->with('status', 'Access updated.');
    }
}
