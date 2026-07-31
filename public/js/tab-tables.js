/**
 * TabTables
 * ---------
 * Shared helper for the common "Bootstrap pill/tab nav + one DataTable per pane"
 * UI structure used across the app (user management, and similar screens).
 *
 * Responsibilities:
 *   - Lazy-initialize each tab's DataTable the first time its pane becomes
 *     visible (avoids the classic zero-width column bug from initializing a
 *     DataTable inside a hidden `display:none` pane).
 *   - Persist the active tab to localStorage and restore it on reload.
 *   - Handle the first-load edge case where the restored/default tab is already
 *     the DOM-active tab (Bootstrap 4 fires no event when you `.tab("show")` an
 *     already-active tab), by initializing that table directly.
 *   - Re-align column widths on tab show as belt-and-suspenders for tables using
 *     scrollX / responsive / fixed widths.
 *
 * Markup contract (must hold on every page that uses this):
 *   - The nav has an id, and its links carry data-toggle="pill" (or "tab") with
 *     href="#paneId".
 *   - Exactly one nav link is marked `.active`, and its matching pane is
 *     `tab-pane fade show active`. The JS reads `a.active` to pick the default
 *     tab, so nav and panes must agree or first-load picks the wrong table.
 *   - storageKey is UNIQUE per screen (e.g. "<module>ActiveTab"). Reusing a key
 *     across pages makes their tab state bleed together.
 *
 * NOTE: This is Bootstrap 4 API (data-toggle, show/shown.bs.tab, .tab("show")).
 *       On a future Bootstrap 5 migration, the changes are isolated to this file.
 *
 * Usage:
 *   TabTables.init({
 *       nav: "#userMgtNav",
 *       storageKey: "userMgtActiveTab",
 *       tables: {
 *           "#users": initUsersTable,
 *           "#roles": initRolesTable,
 *           // panes without a table are simply omitted
 *       }
 *   });
 */
window.TabTables = (function ($) {
    'use strict';

    var NS = ".tabtables";
    var LINK = 'a[data-toggle="pill"], a[data-toggle="tab"]';

    // Guarded localStorage access — throws in Safari private mode (historically),
    // when quota is exceeded, or when storage is disabled by policy.
    var store = {
        get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { /* ignore */ } }
    };

    /**
     * Initialize the tab/table behavior for one nav.
     *
     * @param {Object}  options
     * @param {(string|jQuery)} options.nav            Nav selector or jQuery object.
     * @param {string}          [options.storageKey]   Unique per-screen key. Omit to disable persistence.
     * @param {Object}          [options.tables]       Map of "#paneHref" -> init function.
     * @param {boolean}         [options.adjustColumns=true]  Re-align column widths on tab show.
     * @returns {?{ensure: function, refresh: function}}  Small API, or null if nav not found.
     */
    function init(options) {
        options = options || {};

        var $nav = options.nav instanceof $ ? options.nav : $(options.nav);
        if (!$nav.length) return null;

        var storageKey   = options.storageKey;
        var initializers = options.tables || {};
        var adjust       = options.adjustColumns !== false; // default on

        // Per-nav state stored on the element, so re-init is idempotent and two
        // navs on the same page never share an init-guard.
        var state = $nav.data("tabTablesState");
        if (!state) {
            state = { done: {} };
            $nav.data("tabTablesState", state);
        }

        // Initialize the table for a given pane href exactly once.
        function ensure(href) {
            if (!href || state.done[href]) return;
            var fn = initializers[href];
            if (typeof fn === "function") {
                fn();                     // pane is visible here → correct column widths
                state.done[href] = true;
            }
        }

        // Clear only OUR prior bindings before rebinding, so calling init() again
        // on the same nav doesn't stack duplicate handlers.
        $nav.off(NS);

        // Lazy-init on every real tab switch. `shown.bs.tab` fires AFTER the pane
        // is visible, which is what makes the column widths correct.
        $nav.on("shown.bs.tab" + NS, LINK, function (e) {
            var href = $(e.target).attr("href");
            ensure(href);
            if (adjust && $.fn.dataTable) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }
        });

        // Persist the active tab on switch (fires before the pane is shown, which
        // is fine — we only need the target href).
        if (storageKey) {
            $nav.on("show.bs.tab" + NS, LINK, function (e) {
                store.set(storageKey, $(e.target).attr("href"));
            });
        }

        // --- initial load ---
        var saved   = storageKey ? store.get(storageKey) : null;
        var current = $nav.find("a.active").attr("href");
        var $target = saved ? $nav.find('a[href="' + saved + '"]') : $();

        if ($target.length && saved !== current) {
            // Switching to a different tab → shown.bs.tab fires and inits its table.
            $target.tab("show");
        } else {
            // Already on the right tab (default first tab, or saved === active).
            // No event fires in BS4, so init directly — the pane is already visible.
            ensure(current);
        }

        return {
            // Manually ensure a pane's table is initialized (e.g. programmatic show).
            ensure: ensure,
            // Force a fresh re-init of a pane's table (e.g. after create/delete
            // when you'd rather rebuild than ajax.reload()).
            refresh: function (href) {
                state.done[href] = false;
                ensure(href);
            }
        };
    }

    return { init: init };
})(jQuery);
