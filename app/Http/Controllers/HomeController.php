<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * BFF index standard — pure shell form (rule a).
 *
 * The old index carried $company_id and $journal_id. Both are gone:
 *
 *   - $company was a stand-in for the tenant. Tenancy now handles that end to end; the
 *     tenant is on every token and every downstream query scopes by it. There is nothing
 *     for the home page to resolve.
 *
 *   - $journal_id flagged whether the tenant had recorded its opening balance. That is
 *     accounts-service setup state, not a home-page concern. Whatever needs it — a setup
 *     banner, a guard before posting — asks accounts-service at that point, over the API.
 *
 * With no first-paint data to compose, this is a shell: render, and let the frontend
 * hydrate widgets via the API. A shell index outperforms a composing one, so only compose
 * when the very first paint genuinely needs the data.
 *
 * THE FOUR RULES (unchanged; here only 1, 2, 4 apply — there is no remote call to make)
 *   1. Tenant comes from the token — authTenantId(), never a parameter.
 *   2. Pass LEVELS to the view; Blade renders buttons by level, it does not re-check.
 *   4. Return one view-model array, not loose compact() variables.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('home.index', [
            'tenant' => authTenant(),          // {id, name, slug} for the header
            'user' => authUser(),
            'menu' => authMenu(),            // already filtered by auth — render directly

            // Levels drive which action buttons the dashboard shows. Widgets fetch their
            // own data over the API; the shell only needs to know what to render.
            'access' => [
                'invoices' => moduleLevel('invoices.sales_invoices'),
                'vouchers' => moduleLevel('accounts.vouchers'),
                'expenses' => moduleLevel('expenses'),
            ],
        ]);
    }
}