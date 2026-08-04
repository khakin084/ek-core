<?php

namespace Modules\UserMgt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DropdownCacheService;
use Illuminate\Http\Request;
use Modules\UserMgt\Services\UserMgtService;

class RoleController extends Controller
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('usermgt::roles.create', [
            'title' => 'Create Role'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        infoLogger('info.log', json_encode($request->all()));
        $roleId = $request->input('id'); // present on edit, null on create
        $isEdit = filled($roleId);

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $result = $isEdit
                ? $this->userMgtService->updateResource('/api/usermgt/v1/roles/' . $roleId, $validated, 'updateRole')
                : $this->userMgtService->storeResource('/api/usermgt/v1/roles/store', $validated, 'storeRole');

            if ($result !== null) {
                return $result;
            }

            return apiFail('Record could not be saved', 500, $validated, $roleId);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['password', 'passconf', 'password_confirmation']),
            ]));

            return apiFail('An unexpected error occurred.', 500, [], $roleId);
        }

    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $role = $this->userMgtService->fetchResource('/api/usermgt/v1/roles/get/' . $id, 'getRole');

        return view('usermgt::roles.show', [
            'tenant' => authTenant(),
            'title' => ucwords($role['name']),
            'role' => $role
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $role = $this->userMgtService->fetchResource('/api/usermgt/v1/roles/get/' . $id, 'getRole');
        return view('usermgt::roles.create', [
            'title' => 'Edit Role',
            'role' => $role,
            'audit' => [
                'module' => 'User Management',
                'entity' => 'Role',
                'recordId' => $role['id']
            ]
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

        $result = $this->userMgtService->getRolesDataTable($dt);

        return response()->json($result ?? [
            'draw' => $dt['draw'],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Failed to load item groups',
        ], $result ? 200 : 502);
    }

    //   user  -> PermissionResolver::payload()  (override ?? max(role levels) ?? 0)  — RESOLVED
    //   role  -> role_permissions rows directly ({ module_key: level })              — RAW
    //
    // A role has no overrides and no "max across roles" to resolve; it is itself a source, so we
    // read its grants straight from role_permissions.

    public function loadPermissions(Request $request, string $id)
    {
        // The catalog IS the module registry — two-level tree { key, label, max_level, children[] }.
        // Identical to the user screen; the module tree doesn't change per subject.
        $modules = $this->userMgtService->listResource(
            '/api/usermgt/v1/access/permissions/catalog',
            'permissionCatalog',
            ['tenant' => authTenantId()]
        ) ?? [];

        // The role's RAW grants: { module_key: level_int }. Absent module => level 0 (NONE),
        // which is exactly how the blade already treats a missing key — so the same matrix
        // partial renders unchanged.
        $assignments = $this->userMgtService->fetchResource(
            "/api/usermgt/v1/access/roles/{$id}/permissions",
            'rolePermissions'
        ) ?? [];

        return view('usermgt::roles.access_controls._permissions', [
            // The matrix partial keys everything off one id + a { key: level } map, so it doesn't
            // care whether the subject is a user or a role. We pass the role id and, for the save
            // form, flag the subject type so the blade posts to the role save route.
            'subjectType' => 'role',
            'subjectId' => $id,
            'roleId' => $id,
            'modules' => $modules,
            'assignments' => $assignments,
            'levels' => $this->userMgtService->fetchResource('/api/usermgt/v1/access/permissions/levels', 'permissionLevels')
                ?? [
                    ['key' => 'none', 'label' => 'NONE', 'value' => 0],
                    ['key' => 'read', 'label' => 'READ', 'value' => 1],
                    ['key' => 'read_write', 'label' => 'READ/WRITE', 'value' => 2],
                    ['key' => 'full_control', 'label' => 'FULL CONTROL', 'value' => 3],
                ],
        ]);
    }

    /**
     * Persist role permission changes — the matrix save for a role.
     */
    public function savePermissions(Request $request, string $id)
    {
        $data = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|between:0,3',
        ]);

        $result = $this->userMgtService->storeResource(
            "/api/usermgt/v1/access/roles/{$id}/permissions",
            ['permissions' => $data['permissions']],
            'saveRolePermissions'
        );

        if ($result === null) {
            return response()->json(['message' => 'Role permissions could not be saved.'], 502);
        }
        auditLog('User Mgt', 'App\Models\Role', $id, $data, 'Updated Permissions');
        return response()->json(['message' => 'Role permissions updated.', 'data' => $result]);
    }

}
