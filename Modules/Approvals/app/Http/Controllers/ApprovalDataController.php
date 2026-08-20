<?php

namespace Modules\Approvals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DropdownCacheService;
use Illuminate\Http\Request;
use Modules\Approvals\Services\ApprovalService;

class ApprovalDataController extends Controller
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

    public function dataTable(Request $request)
    {
        $dt = dtParams($request, ApprovalService::FILTERS);

        $result = $this->approvalService->getApprovalsDataTable($dt);

        if (!userCan('approvals.data', 'read')) {
            return dtEmpty($dt, 'You do not have access to approval requests.');
        }

        return dtRelay($dt, $result);
    }


}
