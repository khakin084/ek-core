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
        return view('usermgt::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('usermgt::edit');
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

    public function dataTable(Request $request){

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
}
