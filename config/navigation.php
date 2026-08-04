<?php

/**
 * Navigation presentation map — ek-core owned.
 *
 * The auth service decides WHICH tiles a user sees (the filtered menu tree in the session).
 * This file decides HOW each one is presented: its route and icon. The module KEY is the
 * contract between the two — auth ships keys, ek-core maps them here.
 *
 * Division of responsibility:
 *   - A key in auth's tree but missing here -> visible but not routable -> MenuComposer
 *     skips it and logs it (a half-deployed module, not a crash).
 *   - A key here but not in auth's tree -> the user can't see it -> never rendered.
 *
 * Containers (Accounts, HR, ...) take no route — they expand to their children. Only leaves
 * navigate.
 *
 * ROUTES ARE A CONVENTION, NOT A PROMISE. MenuComposer guards every one with Route::has(),
 * so a name that doesn't resolve is skipped rather than throwing. Replace these with your
 * actual registered route names as each screen lands.
 *
 * ICONS are placeholders in Font Awesome style — swap the class strings for whatever icon
 * set ek-core uses.
 */

return [

    'default_icon' => 'fa-solid fa-circle-dot',

    'items' => [

        // ---- top-level leaves (navigate directly) ----
        'stakeholders' => ['route' => 'stakeholders.index', 'icon' => 'fa-solid fa-handshake'],
        'fleet'        => ['route' => 'fleet.index',        'icon' => 'fa-solid fa-truck'],
        'to_do_list'   => ['route' => 'todo.index',         'icon' => 'fa-solid fa-list-check'],
        'assets'       => ['route' => 'assets.index',       'icon' => 'fa-solid fa-barcode'],
        'expenses'     => ['route' => 'expenses.index',     'icon' => 'fa-solid fa-coins'],
        'purchases'    => ['route' => 'purchases.index',    'icon' => 'fa-solid fa-basket-shopping'],
        'production'   => ['route' => 'production.index',   'icon' => 'fa-solid fa-industry'],
        'reports'      => ['route' => 'reports.index',      'icon' => 'fa-solid fa-flag-checkered'],
        'projects'     => ['route' => 'projects.index',     'icon' => 'fa-solid fa-diagram-project'],
        'sales'        => ['route' => 'sales.index',        'icon' => 'fa-solid fa-cash-register'],
        'loadings'     => ['route' => 'loadings.index',     'icon' => 'fa-solid fa-dolly'],
        'settings'     => ['route' => 'settings.index',     'icon' => 'fa-solid fa-gear'],

        // ---- containers (expand; no route) ----
        'accounts'        => ['icon' => 'fa-solid fa-book'],
        'invoices'        => ['icon' => 'fa-solid fa-file-invoice'],
        'human_resources' => ['icon' => 'fa-solid fa-users'],
        'item_master' => ['icon' => 'fa-solid fas fa-boxes'],
        'warehouses'      => ['icon' => 'fa-solid fa-warehouse'],
        'user_mgt' => ['route' => 'usermgt.index', 'icon' => 'fa-solid fas fa-users-cog'],
        'approvals'       => ['icon' => 'fa-solid fa-calendar-check'],

        // ---- accounts submodules ----
        'accounts.vouchers'          => ['route' => 'accounts.vouchers.index',          'icon' => 'fa-solid fa-receipt'],
        'accounts.control_accounts'  => ['route' => 'accounts.control-accounts.index',  'icon' => 'fa-solid fa-sitemap'],
        'accounts.tax_codes'         => ['route' => 'accounts.tax-codes.index',         'icon' => 'fa-solid fa-percent'],
        'accounts.chart_of_accounts' => ['route' => 'accounts.chart-of-accounts.index', 'icon' => 'fa-solid fa-list-ol'],
        'accounts.reports'           => ['route' => 'accounts.reports.index',           'icon' => 'fa-solid fa-chart-line'],

        // ---- invoices submodules ----
        'invoices.purchase_bills' => ['route' => 'invoices.purchase-bills.index', 'icon' => 'fa-solid fa-file-import'],
        'invoices.sales_invoices' => ['route' => 'invoices.sales-invoices.index', 'icon' => 'fa-solid fa-file-export'],

        // ---- human resources submodules ----
        'human_resources.registration' => ['route' => 'hr.registration.index', 'icon' => 'fa-solid fa-user-plus'],
        'human_resources.payroll'      => ['route' => 'hr.payroll.index',      'icon' => 'fa-solid fa-money-check-dollar'],
        'human_resources.timesheet'    => ['route' => 'hr.timesheet.index',    'icon' => 'fa-solid fa-clock'],
        'human_resources.settings'     => ['route' => 'hr.settings.index',     'icon' => 'fa-solid fa-sliders'],

        // ---- item master submodules ----
        'item_master.items'       => ['route' => 'catalogs',       'icon' => 'fa-solid fa-box'],
        'item_master.varieties'   => ['route' => 'varieties.index',   'icon' => 'fa-solid fa-seedling'],
        'item_master.item_groups' => ['route' => 'item-groups.index', 'icon' => 'fa-solid fa-layer-group'],

        // ---- warehouses submodules ----
        'warehouses.stock_adjustments' => ['route' => 'warehouses.stock-adjustments.index', 'icon' => 'fa-solid fa-scale-balanced'],
        'warehouses.loading_orders'    => ['route' => 'warehouses.loading-orders.index',    'icon' => 'fa-solid fa-truck-ramp-box'],
        'warehouses.price_list'        => ['route' => 'warehouses.price-list.index',        'icon' => 'fa-solid fa-tags'],
        'warehouses.reports'           => ['route' => 'warehouses.reports.index',           'icon' => 'fa-solid fa-chart-column'],

        // ---- user mgt submodules ----
        'user_mgt.users'       => ['route' => 'users.index',           'icon' => 'fa-solid fa-user'],
        'user_mgt.roles'       => ['route' => 'roles.index',           'icon' => 'fa-solid fa-user-shield'],
        'user_mgt.permissions' => ['route' => 'access-controls.index', 'icon' => 'fa-solid fa-toggle-on'],

        // ---- approvals submodules ----
        'approvals.settings' => ['route' => 'approvals.settings.index', 'icon' => 'fa-solid fa-sliders'],
    ],
];