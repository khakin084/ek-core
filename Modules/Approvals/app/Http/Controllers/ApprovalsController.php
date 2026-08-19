<?php

namespace Modules\Approvals\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ApprovalsController extends Controller
{

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
}
