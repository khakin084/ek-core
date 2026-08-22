<?php

namespace Modules\Approvals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DropdownCacheService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Approvals\Services\ApprovalService;

class ApprovalsController extends Controller
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
    
    public function index(): View
    {
        // Visibility gate for a TABBED container. Tabs are inline panes, not links, so this
        // does NOT use authMenu() — that drops a container whose children lack standalone
        // routes, which would wrongly 403 a valid tabbed section. authCan() reads the
        // effective level map: container >= Read means at least one child is visible (the
        // auto-bump invariant) or it was granted directly.
        abort_unless(userCan('approvals', 'read'), 403, 'You do not have access to User Management.');

        return view('approvals::index', [
            'title' => 'Approvals',
            'users' => $this->dropdownCacheService->get('users'),
            'approvalTypes' => $this->approvalTypes(),
            'auditData' => [
                'data' => [
                    'module' => 'Approvals',
                    'entity' => 'Approval',
                ],
                'settings' => [
                    'module' => 'Approvals',
                    'entity' => 'User',
                ],
            ]
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
}
