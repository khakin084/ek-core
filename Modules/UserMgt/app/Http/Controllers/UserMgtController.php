<?php

namespace Modules\UserMgt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * User Mgt landing.
 *
 * user_mgt is a CONTAINER (Users, Roles, Access Controls). It cannot be gated with
 * perm:user_mgt — CheckPermission throws on containers by design, because a container level
 * is menu visibility, not a record action. Visibility is enforced here instead, from the
 * already-composed menu.
 *
 * Follows the BFF index standard:
 *   1. Tenant comes from the token — nothing here queries by a passed tenant.
 *   2. No local domain models — user/role data lives in auth and is fetched by the child
 *      screens over their gateways, not here.
 *   3. Renders the children the user can actually see, as sub-tiles, reusing home.tile.
 *   4. Returns one view-model.
 */
class UserMgtController extends Controller
{
    public function index(): View
    {
        // Visibility gate for a TABBED container. Tabs are inline panes, not links, so this
        // does NOT use authMenu() — that drops a container whose children lack standalone
        // routes, which would wrongly 403 a valid tabbed section. authCan() reads the
        // effective level map: container >= Read means at least one child is visible (the
        // auto-bump invariant) or it was granted directly.
        abort_unless(userCan('user_mgt', 'read'), 403, 'You do not have access to User Management.');

        return view('usermgt::index', [
            'title' => 'User Mgt',
        ]);
    }
}
