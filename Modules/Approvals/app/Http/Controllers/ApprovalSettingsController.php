<?php

namespace Modules\Approvals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DropdownCacheService;
use App\Services\Http\TokenType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Approvals\Services\ApprovalService;

class ApprovalSettingsController extends Controller
{
    protected DropdownCacheService $dropdownCacheService;
    protected ApprovalService $approvalService;

    public function __construct(
        DropdownCacheService $dropdownCacheService,
        ApprovalService $approvalService
    ) {
        $this->dropdownCacheService = $dropdownCacheService;
        $this->approvalService = $approvalService;
    }


    public function create()
    {
        return view('approvals::settings.create', [
            'users' => $this->dropdownCacheService->get('users'),
            'approvalTypes' => $this->approvalTypes(),
            'selectedTypeIds' => [],
            'title' => 'Add Approval Flow',
        ]);
    }

    public function edit(string $id)
    {
        $approvalFlow = $this->approvalService->fetchResource("/api/v1/internal/flows/{$id}", 'getFlow', [], TokenType::Service) ?? abort(404);

        return view('approvals::settings.create', [
            'approvalFlow' => $approvalFlow,
            'users' => $this->dropdownCacheService->get('users'),
            'approvalTypes' => $this->approvalTypes(),
            'title' => 'Edit ' . ucwords($approvalFlow['name']),
        ]);
    }

    /**
     * The shared vocabulary of approval types, scoped to what this tenant uses. Service token
     * (auth-owned config), tenant passed so the list is filtered to licensed modules.
     */
    private function approvalTypes(): array
    {
        $tenantId = authTenantId();
        $approvalTypes = $this->approvalService->approvalTypes($tenantId);
        return $approvalTypes ?? [];
    }

    public function store(Request $request)
    {
        infoLogger('info.log', json_encode($request->all()));
        // Validation errors auto-return 422 with dot-notation keys (levels.0.name), which the
        // form maps to bracket names (levels[0][name]) via dotToBracketName().

        try {
            $data = $request->validate([
                'id' => 'nullable|uuid',              // present => update
                'name' => 'required|string|max:255',
                'active' => 'nullable|boolean',
                'approval_type_ids' => 'array',
                'approval_type_ids.*' => 'uuid',                       // UUIDs, never positional ints

                'levels' => 'required|array|min:1',
                'levels.*.name' => 'required|string|max:255',
                'levels.*.active' => 'nullable|boolean',
                'levels.*.is_mandatory' => 'nullable|boolean',
                'levels.*.min_amount' => 'nullable|numeric|min:0',
                'levels.*.max_amount' => 'nullable|numeric|min:0',
                'levels.*.no_upper_limit' => 'nullable|boolean',
                'levels.*.quorum' => 'required|integer|min:1',
                'levels.*.mode' => 'required|in:sequential,parallel',
                'levels.*.sla_hours' => 'nullable|integer|min:0',
                'levels.*.approvers' => 'array',
                'levels.*.approvers.*.user_id' => 'required|uuid',  // UUIDs
                'levels.*.approvers.*.amount_cap' => 'nullable|numeric|min:0',
                'levels.*.approvers.*.active' => 'nullable|boolean',
            ]);

            // Tenant is NEVER a form field — stamped from the acting admin's session.
            $payload = $data + ['tenant_id' => authTenantId()];

            $result = $this->approvalService->saveFlow($payload);

            infoLogger('info.log', is_array($result) ? json_encode($result) : $result);

            if ($result !== null) {
                return $result;
            }

            return apiFail('Record could not be saved', 500, $data, $data['id']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
            ]));

            return apiFail('An unexpected error occurred.', 500, [], null);
        }

    }

    public function dataTable(Request $request)
    {
        $dt = dtParams($request);

        $result = $this->approvalService->getApprovalFlowDataTable($dt);

        if (!userCan('approvals.settings', 'read')) {
            return dtEmpty($dt, 'You do not have access to approval requests.');
        }

        return dtRelay($dt, $result);
    }


}
