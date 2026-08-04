/* ========= CRUD Operations functions (Improved - WITH Audit Support) ========== */

let main_modal = $("#main_modal");

/* ---------- CSRF ---------- */
$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

/* ---------- Public Entry Points ---------- */

function create(
    url,
    mode = null,
    container = null,
    selector = null,
    audit = null,
) {
    openFormModal(url, mode, container, selector, audit);
}

function edit(
    url,
    mode = null,
    container = null,
    selector = null,
    audit = null,
) {
    openFormModal(url, mode, container, selector, audit);
}

function store(
    form,
    mode = null,
    container = null,
    selector = null,
    audit = null,
) {
    submitFormAjax(form, mode, container, selector, audit);
}

function update(
    form,
    mode = null,
    container = null,
    selector = null,
    audit = null,
) {
    submitFormAjax(form, mode, container, selector, audit);
}

/* ---------- Audit Header Builder ---------- */

function buildAuditHeaders(audit = null) {
    if (!audit) return {};

    const headers = {};

    if (audit.module) headers["X-AUDIT-MODULE"] = audit.module;

    if (audit.entity) headers["X-AUDIT-ENTITY"] = audit.entity;

    if (audit.recordId !== undefined && audit.recordId !== null)
        headers["X-AUDIT-RECORD-ID"] = String(audit.recordId);

    return headers;
}

/* ========= Generic Ajax (DELETE etc.) ========= */

function mainAjax(url, method, mode = null, container = null, audit = null) {
    $.ajax({
        url: url,
        type: method,
        dataType: "json",
        headers: buildAuditHeaders(audit),
        beforeSend: function () {
            startSpinner();
        },
        success: function (data) {
            if (typeof mainAjaxSuccess === "function") {
                mainAjaxSuccess(data);
            } else {
                onJsonSuccess(data, mode, container);
            }
        },
        error: onAjaxError,
        complete: function () {
            stopSpinner();
        },
    });
}

/* ========= Destroy ========= */

function destroy(url, mode = null, container = null, audit = null) {
    swalWithBootstrapButtons
        .fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "No, cancel!",
            reverseButtons: true,
        })
        .then((result) => {
            if (result.isConfirmed) {
                mainAjax(url, "DELETE", mode, container, audit);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                swalWithBootstrapButtons.fire({
                    icon: "error",
                    text: "Cancelled",
                });
            }
        });
}

/* ========= Modal Form Load (GET HTML ONLY) ========= */

function openFormModal(
    url,
    mode = null,
    container = null,
    selector = null,
    audit = null,
) {
    $.ajax({
        url,
        type: "GET",
        dataType: "html",
        beforeSend: startSpinner,
        success: function (html) {
            main_modal.find(".modal-content").empty().append(html);
            main_modal.modal("show");
            onModalShown(main_modal);

            // Persist context inside modal
            main_modal.data("mode", mode);
            main_modal.data("container", container);
            main_modal.data("selector", selector);
            main_modal.data("audit", audit);

            // Prevent duplicate submit binding
            main_modal
                .find("form")
                .off("submit")
                .on("submit", function (event) {
                    event.preventDefault();

                    const modalAudit = main_modal.data("audit");

                    submitFormAjax(
                        $(this),
                        main_modal.data("mode"),
                        main_modal.data("container"),
                        main_modal.data("selector"),
                        modalAudit,
                    );
                });
        },
        error: onAjaxError,
        complete: stopSpinner,
    });
}

/* ========= Submit Form (POST/PUT/PATCH) ========= */

function submitFormAjax(
    form,
    mode = null,
    container = null,
    selector = null,
    audit = null,
) {
    const hasFile = form.find('input[type="file"]').length > 0;
    const payload = hasFile ? new FormData(form[0]) : form.serialize();

    $.ajax({
        url: form.attr("action"),
        type: form.attr("method"),
        data: payload,
        dataType: "json",
        headers: buildAuditHeaders(audit),
        processData: !hasFile,
        contentType: hasFile
            ? false
            : "application/x-www-form-urlencoded; charset=UTF-8",
        beforeSend: function () {
            disableSubmitBtn();
            startSpinner();
        },
        success: function (data) {
            onJsonSuccess(data, mode, container, selector);
        },
        error: onAjaxError,
        complete: function () {
            enableSubmitBtn();
            stopSpinner();
        },
    });
}

/* ========= JSON Success Handler ========= */

function onJsonSuccess(data, mode = null, container = null, selector = null) {
    if (!data) {
        Toast.fire({ icon: "error", title: "Empty response from server" });
        return;
    }

    if (data.state === "Done") {
        Toast.fire({ icon: "success", title: data.msg || "Success" });
        main_modal.modal("hide");

        if (mode === "OTF-BTN") {
            if (!selector) return;

            const newOption = new Option(data.newname, data.newval, true, true);
            selector.prepend(newOption).trigger("change");
            return;
        }

        redrawDataTable(container, true);
        return;
    }

    if (data.state === "Fail") {
        Toast.fire({ icon: "info", title: data.msg || "Operation failed" });
        return;
    }

    if (data.state === "Error") {
        Toast.fire({ icon: "error", title: data.msg || "Server error" });
        return;
    }

    // Validation object fallback
    if (typeof data === "object") {
        let shown = false;
        $.each(data, function (k, v) {
            if (!shown) {
                Toast.fire({
                    icon: "error",
                    title: Array.isArray(v) ? v[0] : v,
                });
                shown = true;
            }
        });
        return;
    }

    Toast.fire({ icon: "error", title: "Unexpected response" });
}

/* ========= Ajax Error Handler ========= */

function onAjaxError(xhr, textStatus, error) {
    if (xhr.status === 419) {
        Toast.fire({
            icon: "error",
            title: "Session expired. Refresh the page and try again.",
        });
        return;
    }

    if (xhr.status === 422 && xhr.responseJSON?.errors) {
        const errors = xhr.responseJSON.errors;
        const firstKey = Object.keys(errors)[0];
        const firstMsg = errors[firstKey]?.[0] || "Validation error";
        Toast.fire({ icon: "error", title: firstMsg });
        return;
    }

    if (xhr.status === 401) {
        Toast.fire({ icon: "error", title: "Unauthenticated (401)" });
        return;
    }

    if (xhr.status === 403) {
        Toast.fire({ icon: "error", title: "Unauthorized (403)" });
        return;
    }

    const msg =
        xhr.responseJSON?.message ||
        xhr.responseText ||
        textStatus ||
        "Request failed";

    Toast.fire({ icon: "error", title: String(msg) });
}

/* ========= Modal Supporting ========= */

function onModalShown(modal) {
    modal.find(".selectXs").select2({ width: "100%" });
    modal.find(".datepicker").datepicker({ autoclose: true });

    modal.find("input, textarea").on("focusin", function () {
        $(this).select();
    });

    $(".selectXs, .my-custom-multiple-select").select2("close");

    if (typeof modalScripts === "function") {
        modalScripts();
    }
}

/* ========= DataTable Safe Redraw ========= */

function redrawDataTable(container, reload = false) {
    if (!container) return;

    // Target the DataTable directly instead of any descendant <table>,
    // so responsive child tables don't get picked up.
    const $table = $("#" + container)
        .find("table.dataTable")
        .first();
    if (!$table.length) {
        // No <table> found at all — container may not be rendered yet.
        return;
    }

    if (!$.fn.DataTable.isDataTable($table)) {
        // Table exists but was never initialized (tab not opened yet, or the
        // container HTML was re-rendered and wiped the old instance).
        console.warn(
            `redrawDataTable: #${container} has a table but no DataTable instance`,
        );
        return;
    }

    const dt = $table.DataTable();
    reload ? dt.ajax.reload(null, false) : dt.draw(false);
}

/* ========= Submit Button Controls ========= */

function enableSubmitBtn() {
    const btn = main_modal.find("button[type=submit]");
    btn.prop("disabled", false).text(" Submit ");
}

function disableSubmitBtn() {
    const btn = main_modal.find("button[type=submit]");
    btn.prop("disabled", true).text("Submitting, Please wait...");
}

/* ========= CRUD Operations functions - End ========= */
