/*
 * Reusable multi-select initializer. Drop the class on any <select multiple> and configure
 * via data- attributes — no JS edits per use:
 *
 *   <select class="my-custom-multiple-select"
 *           name="approval_type_ids[]" multiple
 *           data-placeholder="Select Scope"
 *           data-placeholder-more="Select Another Scope">
 *
 * data-placeholder        first prompt (default "Select…")
 * data-placeholder-more   prompt shown once at least one item is chosen (default: same)
 * data-allow-clear        "true" to show the clear-all x (default true)
 *
 * Safe in tabs/modals: initialised only when the element is actually visible, and RE-init on
 * tab/modal show — avoids the "_positionDropdown / isConnected undefined" crash that happens
 * when select2 is attached while hidden.
 */
(function ($) {
    'use strict';

    function initIn(scope) {
        $(scope).find('.my-custom-multiple-select').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) return; // already initialised

            const first = $el.data('placeholder') || 'Select…';
            const more  = $el.data('placeholder-more') || first;

            // Anchor the dropdown to the nearest stable ancestor so it can't detach.
            const $parent =
                $el.closest('.modal-body').length ? $el.closest('.modal-body') :
                $el.closest('.tab-pane').length   ? $el.closest('.tab-pane')   :
                $el.closest('.card-body').length  ? $el.closest('.card-body')  :
                $el.parent();

            $el.select2({
                placeholder: first,
                allowClear: $el.data('allow-clear') !== false,
                width: '100%',
                dropdownParent: $parent
            });

            // Swap the inline search placeholder once something is selected — via select2's own
            // container handle, not a fragile .next() DOM walk.
            const applyPlaceholder = () => {
                const chosen = ($el.val() || []).length > 0;
                $el.data('select2').$container
                    .find('input.select2-search__field')
                    .attr('placeholder', chosen ? more : first);
            };

            $el.on('change', applyPlaceholder);
            applyPlaceholder(); // set correct prompt on load (edit mode pre-selected)
        });
    }

    $(function () {
        initIn(document);
        $(document).on('shown.bs.tab',   (e) => initIn($(e.target).attr('href') || document));
        $(document).on('shown.bs.modal', (e) => initIn(e.target));
        $(document).on('draw.dt',        (e) => initIn(e.target));
    });

    // For AJAX-loaded panes: window.initMultiSelect(container) after injecting HTML.
    window.initMultiSelect = initIn;
})(jQuery);
