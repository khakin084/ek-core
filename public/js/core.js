var spinner;
function startSpinner() {
    if (startSpinner.called) {
        stopSpinner();
    }
    let opts = {
        lines: 20,
        length: 22,
        width: 8,
        radius: 20,
        scale: 1.0,
        corners: 0,
        color: "#808080",
        fadeColor: "transparent",
        animation: "spinner-line-shrink",
        rotate: 34,
        direction: 1,
        speed: 0.9,
        zIndex: 2e9,
        className: "spinner",
        top: "50%",
        left: "50%",
        shadow: "0 0 1px transparent",
        position: "absolute",
    };
    spinner = new Spin.Spinner(opts).spin($("#spinner_div")[0]);
    startSpinner.called = true;
}

function stopSpinner() {
    spinner.stop();
}

var dates = {
    convert: function (d) {
        // Converts the date in d to a date-object. The input can be:
        //   a date object: returned without modification
        //  an array      : Interpreted as [year,month,day]. NOTE: month is 0-11.
        //   a number     : Interpreted as number of milliseconds
        //                  since 1 Jan 1970 (a timestamp)
        //   a string     : Any format supported by the javascript engine, like
        //                  "YYYY/MM/DD", "MM/DD/YYYY", "Jan 31 2009" etc.
        //  an object     : Interpreted as an object with year, month and date
        //                  attributes.  **NOTE** month is 0-11.
        return d.constructor === Date
            ? d
            : d.constructor === Array
              ? new Date(d[0], d[1], d[2])
              : d.constructor === Number
                ? new Date(d)
                : d.constructor === String
                  ? new Date(d)
                  : typeof d === "object"
                    ? new Date(d.year, d.month, d.date)
                    : NaN;
    },
    compare: function (a, b) {
        // Compare two dates (could be of any type supported by the convert
        // function above) and returns:
        //  -1 : if a < b
        //   0 : if a = b
        //   1 : if a > b
        // NaN : if a or b is an illegal date
        // NOTE: The code inside isFinite does an assignment (=).
        return isFinite((a = this.convert(a).valueOf())) &&
            isFinite((b = this.convert(b).valueOf()))
            ? (a > b) - (a < b)
            : NaN;
    },
    inRange: function (d, start, end) {
        // Checks if date in d is between dates in start and end.
        // Returns a boolean or NaN:
        //    true  : if d is between start and end (inclusive)
        //    false : if d is before start or after end
        //    NaN   : if one or more of the dates is illegal.
        // NOTE: The code inside isFinite does an assignment (=).
        return isFinite((d = this.convert(d).valueOf())) &&
            isFinite((start = this.convert(start).valueOf())) &&
            isFinite((end = this.convert(end).valueOf()))
            ? start <= d && d <= end
            : NaN;
    },
};

const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
        confirmButton: "btn btn-success",
        cancelButton: "btn btn-danger",
    },
    buttonsStyling: false,
});

const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 6000,
});

function toast(attribute, message, title) {
    toastr.options.closeButton = true;
    toastr.options.preventDuplicates = attribute == "info" ? false : true;
    toastr.options.progressBar = true;
    toastr.options.positionClass = "toast-top-center";
    switch (attribute) {
        case "warning":
            toastr.warning(message);
            break;
        case "info":
            toastr.info(message, title, { timeOut: 5000 });
            break;
        case "success":
            toastr.success(message, title);
            break;
        case "error":
            toastr.error(message, title);
            break;
        case "ov_global":
            toastr.success(message, title, { timeOut: 5000 });
            break;
    }
}

function toastResponse(data) {
    if (data.state === "Done") {
        Toast.fire({ icon: "success", title: data.msg });
    } else if (data.state === "Fail") {
        Toast.fire({ icon: "info", title: data.msg });
    } else if (data.state === "Error") {
        Toast.fire({ icon: "error", title: data.msg });
    } else {
        var num = 0;
        jQuery.each(data, function (k, v) {
            if (num === 0) Toast.fire({ icon: "error", title: v });
            num++;
        });
    }
    stopSpinner();
}

function initCustomScripts() {
    $(".number_format").priceFormat();

    $(".datepicker").datepicker({ format: "yyyy-mm-dd" });

    $(".datetime_picker").datetimepicker({
        todayBtn: 1,
    });

    $(".dt-buttons").addClass("pull-right");

    $(".rangepicker").daterangepicker();

    $(".dataTables_wrapper").removeClass("form-inline");
}

function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}

function getToday() {
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, "0");
    var mm = String(today.getMonth() + 1).padStart(2, "0"); //January is 0!
    var yyyy = today.getFullYear();
    today = yyyy + "-" + mm + "-" + dd;
    return today;
}

function formatDate(date) {
    var monthNos = [
        "01",
        "02",
        "03",
        "04",
        "05",
        "06",
        "07",
        "08",
        "09",
        "10",
        "11",
        "12",
    ];

    var day = date.getDate();
    var monthIndex = date.getMonth();
    var year = date.getFullYear();

    return year + "-" + monthNos[monthIndex] + "-" + pad(day, 2);
}

function hasOneDayPassed() {
    var date = new Date().toLocaleDateString();
    if (localStorage.getItem("date") == date) {
        return false;
    } else {
        localStorage.setItem("date", date);
        return true;
    }
}

function DaysOfMonth(nYear, nMonth) {
    switch (nMonth) {
        case 0: // January
            return 31;
            break;
        case 1: // February
            if (nYear % 4 == 0) {
                return 29;
            } else {
                return 28;
            }
            break;
        case 2: // March
            return 31;
            break;
        case 3: // April
            return 30;
            break;
        case 4: // May
            return 31;
            break;
        case 5: // June
            return 30;
            break;
        case 6: // July
            return 31;
            break;
        case 7: // August
            return 31;
            break;
        case 8: // September
            return 30;
            break;
        case 9: // October
            return 31;
            break;
        case 10: // November
            return 30;
            break;
        case 11: // December
            return 31;
            break;
    }
}

function SkipDate(dDate, skipDays) {
    var nYear = dDate.getFullYear();
    var nMonth = dDate.getMonth();
    var nDate = dDate.getDate();
    var remainDays = skipDays;
    var dRunDate = dDate;

    while (remainDays > 0) {
        remainDays_month = DaysOfMonth(nYear, nMonth) - nDate;
        if (remainDays > remainDays_month) {
            remainDays = remainDays - remainDays_month - 1;
            nDate = 1;
            if (nMonth < 11) {
                nMonth = nMonth + 1;
            } else {
                nMonth = 0;
                nYear = nYear + 1;
            }
        } else {
            nDate = nDate + remainDays;
            remainDays = 0;
        }
        dRunDate = Date(nYear, nMonth, nDate);
    }
    return new Date(nYear, nMonth, nDate);
}

function select2Create(
    selector,
    title,
    url,
    button_type,
    can_clear,
    root_div,
    set_width,
) {
    let div = typeof root_div !== "undefined" ? root_div : null;
    let width = typeof set_width !== "undefined" ? set_width : "100%";
    let allowClear = typeof can_clear !== "undefined" ? can_clear : false;
    selector
        .select2({
            width: width,
            allowClear: allowClear,
            placeholder: "Select " + title,
            templateSelection: function (tag, container) {
                // here we are finding option element of tag and
                // if it has property 'locked' we will add class 'locked-tag'
                // to be able to style element in select
                var $option = $(
                    '.contributions-multiple-select option[value="' +
                        tag.id +
                        '"]',
                );
                if ($option.attr("locked")) {
                    $(container).addClass("locked-tag");
                    tag.locked = true;
                }
                return tag.text;
            },
        })
        .on("select2:open", function () {
            let a = $(this).data("select2");
            if (!$(".select2-link").length) {
                a.$results
                    .parents(".select2-results")
                    .append(
                        "<hr>" +
                            '<div class="col-md-12 select2-link select2-close" style="padding-left: 5px;">' +
                            '<button type="button" class="btn btn-primary btn-sm" style="padding: .05rem 1rem; width: 100%; color: white; margin: 5px;">' +
                            '<i class="fa fa-plus"></i> Create ' +
                            title +
                            "</button>" +
                            "</div>",
                    )
                    .on("click", function (b) {
                        b.preventDefault();
                        create(url, button_type, div, selector);
                    });
            }
        })
        .on("select2:unselecting", function (e) {
            // before removing tag we check option element of tag and
            // if it has property 'locked' we will create error to prevent all select2 functionality
            if ($(e.params.args.data.element).attr("locked")) {
                e.preventDefault();
            }
        });
}

function select2custom(selector, title, can_clear, set_width) {
    let width = typeof set_width !== "undefined" ? set_width : "100%";
    let allowClear = typeof can_clear !== "undefined" ? can_clear : false;
    selector
        .select2({
            tags: true,
            width: width,
            allowClear: allowClear,
            placeholder: "Select " + title,
            templateSelection: function (tag, container) {
                // here we are finding option element of tag and
                // if it has property 'locked' we will add class 'locked-tag'
                // to be able to style element in select
                var $option = $(
                    '.contributions-multiple-select option[value="' +
                        tag.id +
                        '"]',
                );
                if ($option.attr("locked")) {
                    $(container).addClass("locked-tag");
                    tag.locked = true;
                }
                return tag.text;
            },
        })
        .on("change", function (e) {
            if ($(this).val() && $(this).val().length) {
                $(this)
                    .next(".select2-container")
                    .find(
                        "li.select2-search--inline input.select2-search__field",
                    )
                    .attr("placeholder", "Select Another " + title);
            }
        })
        .on("select2:unselecting", function (e) {
            // before removing tag we check option element of tag and
            // if it has property 'locked' we will create error to prevent all select2 functionality
            if ($(e.params.args.data.element).attr("locked")) {
                e.preventDefault();
            }
        });
}

initCustomScripts();

function divDismissible() {
    window.setTimeout(function () {
        $(".div-dismissible")
            .fadeTo(1000, 0)
            .slideUp(1000, function () {
                $(this).remove();
            });
    }, 5000);
}

function initTabPersistence($nav, storageKey) {
    if (!$nav || !$nav.length) return;

    $nav.off("show.bs.tab.persist").on(
        "show.bs.tab.persist",
        'a[data-toggle="pill"]',
        function (e) {
            try {
                localStorage.setItem(storageKey, $(e.target).attr("href"));
            } catch (err) {
                /* storage disabled or full — ignore */
            }
        },
    );

    let saved = null;
    try {
        saved = localStorage.getItem(storageKey);
    } catch (err) {}

    if (saved) {
        const $tab = $nav.find(`a[href="${saved}"]`);
        if ($tab.length) $tab.tab("show");
    }
}

function calculateRowAmount(form, amount_decimal_places) {
    var decimal_places =
        typeof amount_decimal_places !== "undefined"
            ? amount_decimal_places
            : "";
    form.on(
        "change keyup",
        'input[name="rate"],input[name="quantity"],select[name="tax_id"]',
        function () {
            var rate = form.find('input[name="rate"]').val();
            rate = rate != "" ? (!isNaN(rate) ? parseFloat(rate) : 0) : 0;
            var quantity = form.find('input[name="quantity"]').val();
            quantity =
                quantity != ""
                    ? !isNaN(quantity)
                        ? parseFloat(quantity)
                        : 0
                    : 0;
            var amount = rate * quantity;
            // form.find('input[name="amount"]').val(amount.toFixed(decimal_places)).trigger("change");
            form.find('input[name="amount"]').val(amount).trigger("change");
        },
    );
}

function calculateTotalAmount(table, amount_decimal_places) {
    var decimal_places =
        typeof amount_decimal_places !== "undefined"
            ? amount_decimal_places
            : "";
    var total_amount = 0;
    table.find('tbody input[name="amount"]').each(function () {
        var amount = $(this).val();
        amount = amount != "" ? parseFloat($(this).val()) : 0;
        total_amount += amount;
    });
    table.find(".total_amount").html(total_amount.toFixed(decimal_places));

    var total_costs_amount = 0;
    table.find('tfoot input[name="amount"]').each(function () {
        var amount = $(this).val();
        amount = amount != "" ? parseFloat($(this).val()) : 0;
        total_costs_amount += amount;
    });
    table
        .find(".total_costs_amount")
        .html(total_costs_amount.toFixed(decimal_places));
}

function calculateGrandTotalAmount(container, amount_decimal_places) {
    var decimal_places =
        typeof amount_decimal_places !== "undefined"
            ? amount_decimal_places
            : "";
    let table = container.find("table");
    calculateTotalAmount(table, 2);
    var total_amount = !isNaN(table.find(".total_amount").val())
        ? parseFloat(table.find(".total_amount").unmask())
        : 0;
    var total_costs_amount = !isNaN(table.find(".total_costs_amount").val())
        ? parseFloat(table.find(".total_costs_amount").unmask())
        : 0;
    var row_tax_ids = new Array(),
        row_amounts = new Array(),
        i = 0;
    let grand_total = total_amount + total_costs_amount;

    var factor = grand_total / total_amount;
    container.find('input[name="factor"]').val(factor);

    table.find('tbody select[name="tax_id"]').each(function () {
        var row_tax_id = $(this).val();
        if (row_tax_id != "") {
            var row_amount = $(this)
                .closest("tr")
                .find('input[name="amount"]')
                .val();
            row_tax_ids[i] = row_tax_id;
            row_amounts[i] = row_amount != "" ? parseFloat(row_amount) : 0;
            i++;
        }
    });

    table.find('tfoot select[name="tax_id"]').each(function () {
        var row_tax_id = $(this).val();
        if (row_tax_id != "") {
            var row_amount = $(this)
                .closest("tr")
                .find('input[name="amount"]')
                .val();
            row_tax_ids[i] = row_tax_id;
            row_amounts[i] = row_amount != "" ? parseFloat(row_amount) : 0;
            i++;
        }
    });

    $.ajax({
        url: base_url + "/accounts/tax-codes/aggregate-tax-n-display",
        type: "POST",
        data: {
            table_id: table.attr("id"),
            row_tax_ids: row_tax_ids,
            row_amounts: row_amounts,
        },
        dataType: "json",
        beforeSend: function (e) {
            if (row_tax_ids.length == 0) {
                container
                    .find(".grand_total_amount")
                    .html(grand_total.toFixed(decimal_places))
                    .priceFormat();
            }
        },
        success: function (res) {
            table.find("tfoot .taxes_temp_row").remove();
            if (res.taxes_rows != "") {
                let grand_total_row = table.find("tfoot .grand-total");
                let tax_rows = $("<div></div>")
                    .append(jQuery(res.taxes_rows))
                    .find(".taxes_temp_row");
                tax_rows.insertBefore(grand_total_row);
                grand_total += !isNaN(table.find(".total_tax_amount").val())
                    ? parseFloat(table.find(".total_tax_amount").unmask())
                    : 0;
                container
                    .find(".grand_total_amount")
                    .html(grand_total.toFixed(decimal_places))
                    .priceFormat();
            }
        },
    });
}

function newCostRowCount(tfoot) {
    var count = 0;
    tfoot.find(".new_cost_row").each(function () {
        count++;
    });
    return count;
}

function initializeRemoveButton(button) {
    button.click(function () {
        let form = button.closest("form");
        let row = $(this).closest("tr");
        let next_row = row.next("tr");
        if (next_row.hasClass("temp_row")) {
            next_row.find("#extra").slideToggle("slow", function () {
                next_row.remove();
            });
            row.remove();
        } else {
            if (button.hasClass("costs_row")) {
                tfoot = row.closest("tfoot");
                if (newCostRowCount(tfoot) == 1) {
                    tfoot
                        .find(".proc-costs-header")
                        .attr("style", "display: none");
                    tfoot
                        .find(".proc-costs-total")
                        .attr("style", "display: none");
                }
                row.remove();
            } else {
                row.remove();
            }
        }
        calculateGrandTotalAmount(form, 2);
    });
}

function sendForApproval(object_type, object_id) {
    $("#send_for_approval_" + object_type + "_" + object_id)
        .off("click")
        .on("click", function () {
            $.ajax({
                url: base_url + "/approvals/store",
                type: "POST",
                data: {
                    object_id: object_id,
                    ret_route: "invoices-index",
                    module_id: $(this).attr("module_id"),
                    object_class: $(this).attr("object_class"),
                },
                dataType: "json",
                beforeSend: function (e) {},
                success: function (res) {
                    $(
                        "#approval_btn_container_" +
                            object_type +
                            "_" +
                            object_id,
                    ).html(res.button_view);
                    toastResponse(res);
                },
            });
        });
}

function retrieveComponentRowUnitNLastPrice(container, price_type) {
    container.on("change", 'select[name="item_ids[]"]', function () {
        var warehouse_id = container
            .closest("form")
            .find("#warehouse_id")
            .val();
        warehouse_id = typeof warehouse_id !== "undefined" ? warehouse_id : 0;
        var item_id = container.find('select[name="item_ids[]"]').val();
        var qty_before_input = container.find('input[name="qty_befores[]"]');
        var quantity_input = container.find('input[name="quantities[]"]');
        var rate_input = container.find('input[name="rates[]"]');
        var unit_display = container.find(".unit_display");
        $.ajax({
            url:
                base_url +
                "/item-master/" +
                item_id +
                "/" +
                warehouse_id +
                "/" +
                price_type +
                "/unit-and-last-price",
            type: "GET",
            dataType: "json",
            beforeSend: function (e) {
                unit_display.html("");
                rate_input.val("");
                qty_before_input.val("");
                quantity_input.val("");
                if (item_id == "") {
                    e.abort();
                }
            },
            success: function (res) {
                rate_input.val(res.last_price);
                qty_before_input.val(res.last_qty);
                unit_display.val(res.unit);
                initCustomScripts();
            },
        });
    });
}

function calculateComponentRowAmount(row, amount_decimal_places) {
    var decimal_places =
        typeof amount_decimal_places !== "undefined"
            ? amount_decimal_places
            : "";
    row.delegate(
        ' input[name="rates[]"],  input[name="quantities[]"]',
        "change keyup",
        function () {
            var rate = row.find('input[name="rates[]"]').val();
            rate = rate != "" ? (!isNaN(rate) ? parseFloat(rate) : 0) : 0;
            var quantity = row.find('input[name="quantities[]"]').val();
            quantity =
                quantity != ""
                    ? !isNaN(quantity)
                        ? parseFloat(quantity)
                        : 0
                    : 0;
            var amount = rate * quantity;
            row.find('input[name="amounts[]"]')
                .val(amount.toFixed(decimal_places))
                .trigger("change");
        },
    );
}

function calculateComponentsTotalAmount(table, amount_decimal_places) {
    var decimal_places =
        typeof amount_decimal_places !== "undefined"
            ? amount_decimal_places
            : "";
    var total_amount = 0;
    table.find('tbody input[name="amounts[]"]').each(function () {
        var amount = $(this).val();
        amount = amount != "" ? parseFloat($(this).val()) : 0;
        total_amount += amount;
    });
    table.find(".total_amount").html(total_amount.toFixed(decimal_places));
    var total_costs_amount = 0;
    table.find('tfoot input[name="amounts[]"]').each(function () {
        var amount = $(this).val();
        amount = amount != "" ? parseFloat($(this).val()) : 0;
        total_costs_amount += amount;
    });
    table
        .find(".total_costs_amount")
        .html(total_costs_amount.toFixed(decimal_places));
}

function calculateComponentsGrandTotalAmount(container, amount_decimal_places) {
    var decimal_places =
        typeof amount_decimal_places !== "undefined"
            ? amount_decimal_places
            : "";
    let table = container.find("table");
    calculateComponentsTotalAmount(table, 2);
    var total_amount = !isNaN(table.find(".total_amount").val())
        ? parseFloat(table.find(".total_amount").unmask())
        : 0;
    var total_costs_amount = !isNaN(table.find(".total_costs_amount").val())
        ? parseFloat(table.find(".total_costs_amount").unmask())
        : 0;
    let grand_total = total_amount + total_costs_amount;
    container
        .find(".grand_total_amount")
        .html(grand_total.toFixed(decimal_places))
        .priceFormat();
}

function initializeComponentRowRemoveButton(button) {
    button.click(function () {
        let form = button.closest("form");
        let row = $(this).closest("tr");
        if (button.hasClass("costs_row")) {
            tfoot = row.closest("tfoot");
            if (newCostRowCount(tfoot) == 1) {
                tfoot.find(".proc-costs-header").attr("style", "display: none");
                tfoot.find(".proc-costs-total").attr("style", "display: none");
            }
            row.remove();
        } else {
            row.remove();
        }
        calculateComponentsGrandTotalAmount(form, 2);
    });
}

function getAttachments(container) {
    $.ajax({
        url: "http://localhost/ek-4.3.6/public/api/attachments/object-attachments",
        type: "POST",
        dataType: "json",
        beforeSend: function () {
            container
                .find(".attachment_container")
                .html(
                    '<div class="d-flex justify-content-center">\n' +
                        '  <div class="spinner-border" role="status">\n' +
                        '    <span class="sr-only">Loading...</span>\n' +
                        "  </div>\n" +
                        "</div>",
                );
        },
        data: {
            object_id: container.find('input[name="object_id"]').val(),
            object_type: container.find('input[name="object_type"]').val(),
        },
        success: function (response) {
            container.find(".attachment_container").html(response);
        },
    });
}

function resetTempRow(row) {
    let next_row = row.next("tr");
    if (next_row.hasClass("temp_row")) {
        next_row.find("#extra").slideToggle("slow", function () {
            next_row.remove();
        });
    }
}

function getFxRates(selector) {
    let currency_id = selector.val();
    $.ajax({
        url: base_url + "/accounts/get-fx-rate/" + currency_id,
        type: "GET",
        beforeSend: function (e) {
            startSpinner();
            if (currency_id == "") {
                e.abort();
                selector
                    .closest(".row")
                    .find('input[name="fx_rate"]')
                    .val("")
                    .trigger("input");
                stopSpinner();
            }
        },
        success: function (data) {
            selector.closest(".row").find('input[name="fx_rate"]').val(data);
            stopSpinner();
        },
        complete: function () {
            stopSpinner();
        },
    });
}

function getAccountingVoucherTableChildTable(table, tbody) {
    tbody.off("click").on("click", "td.details-control", function () {
        let tr = $(this).closest("tr");
        let row = table.api().row(tr);

        if (row.child.isShown()) {
            $("div.slider", row.child()).slideUp(function () {
                row.child.hide();
                tr.removeClass("details").removeClass("shown");
            });
        } else {
            tr.addClass("details");
            let row_data_arr = row.data();
            let row_object_id = row_data_arr["id"];
            $.ajax({
                url:
                    base_url +
                    "/accounts/vouchers/" +
                    row_object_id +
                    "/load-object-details",
                type: "GET",
                dataType: "json",
                beforeSend: function (e) {
                    if (row_object_id == "") e.abort();
                },
                success: function (data) {
                    row.child(data.object_childtable, "no-padding").show();
                    tr.addClass("shown");
                    $("div.slider", row.child()).slideDown();

                    $("#nav-attachments-tab-" + row_object_id).on(
                        "shown.bs.tab",
                        function () {
                            getAttachments(
                                $("#attachments_div_vouchers_" + row_object_id),
                            );
                            uploadAttachments(
                                $("#attachments_div_vouchers_" + row_object_id),
                            );
                        },
                    );
                },
            });
        }
    });
}

function validateTypedQuantity(container, price_type) {
    if (price_type == "RETAIL") {
        container.on(
            "change keyup",
            'select[name="item_id"], input[name="quantity"]',
            function (e) {
                var qty_before_input = container.find(
                    'input[name="qty_before"]',
                );
                var quantity_input = container.find('input[name="quantity"]');
                if (e.handled !== true) {
                    var available_qty = parseFloat(qty_before_input.val());
                    var typed_qty = parseFloat(quantity_input.val());
                    if (available_qty < typed_qty) {
                        toast(
                            "error",
                            "Only " + available_qty + " is available",
                        );
                        quantity_input.val(available_qty);
                    }
                    e.handled = true;
                }
            },
        );
    }
}

function retrieveUnitNLastPrice(container, price_type) {
    container.on("change", 'select[name="item_id"]', function () {
        var warehouse_id = container
            .closest("form")
            .find("#warehouse_id")
            .val();
        warehouse_id = warehouse_id != "" ? warehouse_id : 0;
        var item_id = container.find('select[name="item_id"]').val();
        var qty_before_input = container.find('input[name="qty_before"]');
        var quantity_input = container.find('input[name="quantity"]');
        var qty_after_input = container.find('input[name="qty_after"]');
        var rate_input = container.find('input[name="rate"]');
        var unit_display = container.find(".unit_display");
        $.ajax({
            url:
                base_url +
                "/item-master/" +
                item_id +
                "/" +
                warehouse_id +
                "/" +
                price_type +
                "/unit-and-last-price",
            type: "GET",
            dataType: "json",
            beforeSend: function (e) {
                unit_display.html("");
                rate_input.val("");
                qty_before_input.val("");
                quantity_input.val("");
                qty_after_input.val("");
                if (item_id == "") {
                    e.abort();
                }
            },
            success: function (res) {
                qty_before_input.val(res.last_qty);
                rate_input.val(res.last_price);
                unit_display.val(res.unit);
                initCustomScripts();
            },
            complete: function () {
                validateTypedQuantity(container, price_type);
            },
        });
    });
}

function uploadAttachments(container) {
    container
        .find(".add_attachments")
        .off("click")
        .on("click", function () {
            var form_data = new FormData();
            var attachments = new Array();
            var object_id = container.find('input[name="object_id"]').val();
            var object_type = container.find('input[name="object_type"]').val();
            var input = container.find('input[name="attachments"]')[0];
            for (var i = 0; i < input.files.length; ++i) {
                attachments[i] = input.files.item(i);
                form_data.append("attachments[]", attachments[i]);
            }
            form_data.append("object_id", object_id);
            form_data.append("object_type", object_type);

            $.ajax({
                url: "http://localhost/ek-4.3.6/public/api/attachments/upload",
                method: "POST",
                dataType: "json",
                data: form_data,
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function () {
                    container
                        .find(".attachment_container")
                        .html(
                            '<div style="height: 100px;" class="d-flex justify-content-center">\n' +
                                '  <div class="spinner-border" role="status">\n' +
                                '    <span class="sr-only">Loading...</span>\n' +
                                "  </div>\n" +
                                "</div>",
                        );
                },
                success: function (response) {
                    window.setTimeout(function () {
                        $(".alert_to_dismiss")
                            .fadeTo(1000, 0)
                            .slideUp(1000, function () {
                                $(this).remove();
                            });
                    }, 5000);
                    var attachment_container = container.find(
                        ".attachment_container",
                    );
                    container.find("form")[0].reset();
                    attachment_container.html(response);
                },
            });
        });
}

function drawPsDatatable(table) {
    let type = table.attr("type");
    let entity_select = table
        .closest(".row")
        .find('select[name="entity_select"]');
    let psDatatable = table.dataTable({
        order: [[3, "desc"]],
        colReorder: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: base_url + "/commerce/list",
            type: "POST",
            data: function (d) {
                d.type = type;
                d.entity_id = entity_select.val();
            },
        },
        columns: [
            { data: "checkBox", orderable: false },
            {
                class: "details-control",
                data: null,
                orderable: false,
                defaultContent: "",
            },
            { data: "commerceDate", name: "date", orderable: true },
            { data: "commerceNo", name: "id", orderable: true },
            {
                data: "stakeholder_name",
                name: "stakeholders.name",
                orderable: true,
            },
            {
                data: "warehouse_name",
                name: "warehouses.name",
                orderable: true,
            },
            { data: "reference", orderable: true },
            { data: "status", orderable: false },
            { data: "amount", orderable: false },
            { data: "action", orderable: false },
        ],
        language: {
            zeroRecords:
                "<div class='alert alert-info'>No matching data found</div>",
            emptyTable: "<div class='alert alert-info'>No data found</div>",
        },
    });

    psDatatable.find("tr").each(function () {
        $(this).find("td:last-child").attr("nowrap", "nowrap");
    });

    psDatatable.find("tbody").each(function () {
        var tbody = $(this);
        tbody.off("click").on("click", "td.details-control", function () {
            var tr = $(this).closest("tr");
            var row = psDatatable.api().row(tr);

            if (row.child.isShown()) {
                $("div.slider", row.child()).slideUp(function () {
                    row.child.hide();
                    tr.removeClass("details").removeClass("shown");
                });
            } else {
                tr.addClass("details");
                let row_data_arr = row.data();
                let row_object_id = row_data_arr["typeId"];
                let object_id = row_object_id.split("_")[1];
                let object_type = row_object_id.split("_")[0];
                $.ajax({
                    url: base_url + "/commerce/object-details/" + row_object_id,
                    type: "GET",
                    dataType: "json",
                    beforeSend: function (e) {
                        startSpinner();
                        if (row_object_id == "") {
                            e.abort();
                            stopSpinner();
                        }
                    },
                    success: function (res) {
                        row.child(res.object_childtable, "no-padding").show();
                        tr.addClass("shown");
                        getAttachments(
                            $(
                                "#attachments_div_" +
                                    object_type +
                                    "_" +
                                    object_id,
                            ),
                        );
                        uploadAttachments(
                            $(
                                "#attachments_div_" +
                                    object_type +
                                    "_" +
                                    object_id,
                            ),
                        );
                        $("div.slider", row.child()).slideDown();
                        stopSpinner();
                    },
                    error: function (xhr, textStatus, error) {
                        onAjaxError(xhr, textStatus, error);
                    },
                    complete: function () {
                        stopSpinner();
                    },
                });
            }
        });
    });

    entity_select.change(function (event) {
        psDatatable.DataTable().draw();
    });
}

function predecessorDetails(row) {
    row.find('select[name="predecessor"]').change(function () {
        let predecessor = $(this).val();
        $.ajax({
            url: base_url + "/accounts/predecessor-details/" + predecessor,
            type: "GET",
            dataType: "json",
            beforeSend: function (e) {
                if (predecessor == "") {
                    e.abort();
                } else {
                    row.find('input[name="rate"]').val(null);
                    row.find('select[name="tax_account"]').val(null);
                }
            },
            success: function (response) {
                row.find('input[name="rate"]').val(response.effective_rate);
                row.find('select[name="tax_account"]').val(
                    response.tax_account,
                );
            },
        });
    });
}

function generateAccountStatement(container, account_id) {
    var statement_container = container.find("#statement_container");
    var currency_id = container.find('select[name="currency_id"]').val();
    var date_range = container.find('input[name="date_range"]').val();
    var sub_account_ids = container
        .find('select[name="sub_account_ids[]"]')
        .val();
    $.ajax({
        url: base_url + "/accounts/generate-statement",
        type: "POST",
        dataType: "json",
        data: {
            account_id: account_id,
            currency_id: currency_id,
            date_range: date_range,
            sub_account_ids: sub_account_ids,
        },
        beforeSend: function (e) {
            startSpinner();
        },
        success: function (data) {
            container
                .closest("#content-wrapper")
                .find("#currency_display")
                .val(data.symbol);
            statement_container.html(data.statement_table);
            stopSpinner();
        },
        error: function (xhr, textStatus, error) {
            onAjaxError(xhr, textStatus, error);
        },
        complete: function () {
            stopSpinner();
        },
    });
}

function generateStockReports(button, report_type) {
    var root_div = button.closest(".root-div");
    var date_range = root_div.find('input[name="date_range"]').val();
    var type = root_div.find('select[name="type"]').val();
    var item_id = root_div.find('select[name="item_id"]').val();
    var user_id = root_div.find('select[name="user_id"]').val();
    var item_variety_id = root_div.find('select[name="item_variety_id"]').val();
    var item_group_id = root_div.find('select[name="item_group_id"]').val();
    var order_by = root_div.find('select[name="order_by"]').val();
    $.ajax({
        url:
            base_url +
            "/warehouses/profile/reports/" +
            report_type +
            "/generate/" +
            root_div.find('input[name="warehouse_id"]').val(),
        type: "POST",
        data: {
            date_range: date_range,
            type: type,
            item_id: item_id,
            user_id: user_id,
            item_variety_id: item_variety_id,
            item_group_id: item_group_id,
            order_by: order_by,
        },
        beforeSend: function (e) {
            startSpinner();
            if (report_type == "stock-movement" && item_id == 0) {
                e.abort();
                stopSpinner();
                Toast.fire({
                    icon: "error",
                    title: "Please Select Item To Track Movement",
                });
            }
        },
        success: function (res) {
            root_div.find("#root_div_container").html(res);
            stopSpinner();
        },
        error: function (xhr, textStatus, error) {
            onAjaxError(xhr, textStatus, error);
        },
        complete: function () {
            stopSpinner();
        },
    });
}

function accountGroupNAccountFormControl() {
    $(".openModal").on("click", function () {
        let modal = $(this).closest(".chart-container").find(".modal");
        modal.find(".modal-title").html($(this).attr("modal-head"));
        modal.find(".modal-body").load($(this).attr("href"));
    });

    $(".delete_account").each(function () {
        var button = $(this);
        button.off("click").on("click", function () {
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
                    $.ajax({
                        url:
                            base_url +
                            "/accounts/delete/" +
                            button.attr("account_id"),
                        type: "GET",
                        dataType: "json",
                        beforeSend: function (e) {
                            if (result.isConfirmed) {
                            } else if (
                                /* TODO: Read more about handling dismissals below */
                                result.dismiss === Swal.DismissReason.cancel
                            ) {
                                e.abort();
                                swalWithBootstrapButtons.fire(
                                    "Cancelled",
                                    "Your account is safe :)",
                                    "error",
                                );
                            }
                        },
                        success: function (res) {
                            window.location.replace(
                                base_url + "/accounts/index",
                            );
                            toastResponse(res);
                        },
                    });
                });
        });
    });

    $(".delete_account_group")
        .off("click")
        .on("click", function () {
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
                    $.ajax({
                        url:
                            base_url +
                            "/accounts/account-groups/" +
                            $(this).attr("account_group_id") +
                            "/delete",
                        type: "GET",
                        dataType: "json",
                        beforeSend: function (e) {
                            /* Read more about handling dismissals below */
                            if (result.dismiss === Swal.DismissReason.cancel) {
                                e.abort();
                                swalWithBootstrapButtons.fire(
                                    "Cancelled",
                                    "Your account is safe :)",
                                    "error",
                                );
                            }
                        },
                        success: function (data) {
                            window.location.replace(
                                base_url + "/accounts/index",
                            );
                            Toast.fire({
                                icon: data.flag == 1 ? "success" : "info",
                                title: data.message,
                            });
                        },
                    });
                });
        });
}

$('a[data-toggle="tab"]').on("show.bs.tab", function (e) {
    localStorage.setItem("activeTab", $(e.target).attr("href"));
});

$("#purchase_table").each(function () {
    var table = $(this);
    drawPsDatatable(table);
});

$("#sale_table").each(function () {
    var table = $(this);
    if (table.attr("initialized") != "true") {
        drawPsDatatable(table);
        table.attr("initialized", "true");
    }
});

//TODO when start dealing with reports check out this
$("#reports-tab").on("shown.bs.tab", function () {
    var form_container = $(".inputs_form");
    var report_type_field = form_container.find('select[name="report_type"]');
    var from_o_to_field = form_container.find('select[name="from_o_to"]');
    var from_o_to_form_group = from_o_to_field.closest(".form-group");
    var report_container = form_container
        .closest(".col-lg-12")
        .find(".report_container");
    report_container.html("");
    report_type_field.change(function () {
        switch (report_type_field.val()) {
            case "balance":
            case "profit_o_loss":
                from_o_to_form_group.hide();
                break;
            case "purchases":
            case "sales":
                from_o_to_form_group.show();
                break;
        }
    });

    $("#view_report").each(function () {
        var button = $(this);
        button.off("click").on("click", function () {
            var report_type = report_type_field.val();
            var start_date = form_container
                .find('input[name="start_date"]')
                .val();
            var end_date = form_container.find('input[name="end_date"]').val();
            var from_o_to = from_o_to_field.val();

            if ((report_type != "", start_date != "", end_date != "")) {
                $.post(
                    base_url + "reports",
                    {
                        report_type: report_type,
                        start_date: start_date,
                        end_date: end_date,
                        from_o_to: from_o_to,
                    },
                    function (data) {
                        report_container.html(data.table_view);
                    },
                    "json",
                );
            } else {
                toast("warning", "Please fill all the neccesary details!");
            }
        });
    });
});

$("#tb_container").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        divDismissible();

        $("#print_tb").click(function () {
            var root_div = $(this).closest(".root-div");
            var date_range = root_div.find('input[name="date_range"]').val();
            $.ajax({
                url: base_url + "/accounts/trial-balance/generate",
                type: "POST",
                data: {
                    date_range: date_range,
                },
                beforeSend: function (e) {
                    startSpinner();
                    if (date_range == "") {
                        e.abort();
                        stopSpinner();
                    }
                },
                success: function (response) {
                    root_div.find("#root_div_container").html(response);
                    stopSpinner();
                },
                error: function (xhr, textStatus, error) {
                    onAjaxError(xhr, textStatus, error);
                },
                complete: function () {
                    stopSpinner();
                },
            });
        });
    });
});

$("#gj_container").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        divDismissible();

        $("#print_journal").click(function () {
            var root_div = $(this).closest(".root-div");
            var date_range = root_div.find('input[name="date_range"]').val();
            $.ajax({
                url: base_url + "/accounts/general-journal/generate",
                type: "POST",
                data: {
                    date_range: date_range,
                },
                beforeSend: function (e) {
                    startSpinner();
                    if (date_range == "") {
                        e.abort();
                        stopSpinner();
                    }
                },
                success: function (response) {
                    root_div.find("#root_div_container").html(response);
                    stopSpinner();
                },
                error: function (xhr, textStatus, error) {
                    onAjaxError(xhr, textStatus, error);
                },
                complete: function () {
                    stopSpinner();
                },
            });
        });
    });
});

$("#gl_container").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        divDismissible();

        var root_div = $(".root-div");
        root_div.on(
            "change keyup",
            'select[name="account_group_id"]',
            function () {
                var account_group_id = root_div
                    .find('select[name="account_group_id"]')
                    .val();
                $.ajax({
                    url:
                        base_url +
                        "/accounts/get-group-accounts/" +
                        account_group_id,
                    type: "GET",
                    beforeSend: function (e) {
                        startSpinner();
                        if (account_group_id == "") {
                            e.abort();
                            stopSpinner();
                        }
                    },
                    success: function (response) {
                        if (response.length == 1)
                            root_div
                                .find('select[name="account_id"]')
                                .attr("disabled");
                        root_div
                            .find('select[name="account_id"]')
                            .removeAttr("disabled")
                            .html(response)
                            .change();
                        stopSpinner();
                    },
                    error: function (xhr, textStatus, error) {
                        onAjaxError(xhr, textStatus, error);
                    },
                    complete: function () {
                        stopSpinner();
                    },
                });
            },
        );

        $("#print_ledger").click(function () {
            var root_div = $(this).closest(".root-div");
            var account_group_id = root_div
                .find('select[name="account_group_id"]')
                .val();
            var account_id = root_div.find('select[name="account_id"]').val();
            var date_range = root_div.find('input[name="date_range"]').val();
            var currency_id = root_div.find('select[name="currency_id"]').val();
            $.ajax({
                url: base_url + "/accounts/general-ledger/generate",
                type: "POST",
                beforeSend: function (e) {
                    startSpinner();
                    if (currency_id == "") {
                        e.abort();
                    }
                },
                data: {
                    account_group_id: account_group_id,
                    account_id: account_id,
                    currency_id: currency_id,
                    date_range: date_range,
                },
                success: function (response) {
                    root_div.find("#root_div_container").html(response);
                    stopSpinner();
                },
                complete: function () {
                    stopSpinner();
                },
            });
        });
    });
});

$("#approvals_container").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        $('a[data-toggle="pill"]').on("show.bs.tab", function (e) {
            localStorage.setItem(
                "approvalsActiveTab",
                $(e.target).attr("href"),
            );
        });

        let approvalsActiveTab = localStorage.getItem("approvalsActiveTab");
        if (approvalsActiveTab) {
            $('#approvals-nav-pills a[href="' + approvalsActiveTab + '"]').tab(
                "show",
            );
        }

        function draw_approvals_table(table_id, approval_flow_id) {
            $(table_id).dataTable({
                order: [[0, "desc"]],
                colReorder: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: base_url + "/approvals/flows/list/" + approval_flow_id,
                    type: "GET",
                },
                columns: [
                    {
                        data: "approval_date",
                        name: "approvals.date",
                        orderable: true,
                    },
                    { data: "request_number", orderable: false },
                    { data: "vendor", orderable: false },
                    { data: "amount", orderable: false },
                    { data: "requester", orderable: false },
                    { data: "status", orderable: false },
                    { data: "action", orderable: false },
                ],
                language: {
                    zeroRecords:
                        "<div class='alert alert-info'>No matching data found</div>",
                    emptyTable:
                        "<div class='alert alert-info'>No data found</div>",
                },
            });
        }

        function draw_settings_table() {
            let approvalSettingsTable = $("#approval-flow-table").dataTable({
                order: [[0, "desc"]],
                colReorder: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: base_url + "/approvals/settings/list",
                    type: "GET",
                },
                columns: [
                    { data: "name", orderable: true },
                    { data: "modules", orderable: false },
                    { data: "status", orderable: false },
                    { data: "user_id", orderable: false },
                    { data: "action", orderable: false },
                ],
                language: {
                    zeroRecords:
                        "<div class='alert alert-info'>No matching data found</div>",
                    emptyTable:
                        "<div class='alert alert-info'>No data found</div>",
                },
            });
        }

        divDismissible();

        let j = 0;
        $(".approvals-flows-tab").each(function () {
            let dom_id = "#" + $(this).attr("id");
            let approval_flow_id = $(this).attr("approval_flow_id");
            let table_id = "#approval_flow_" + approval_flow_id + "_table";

            if (j === 0) {
                draw_approvals_table(table_id, approval_flow_id);
            } else {
                $(dom_id).on("shown.bs.tab", function () {
                    if (!$.fn.DataTable.isDataTable(table_id)) {
                        draw_approvals_table(table_id, approval_flow_id);
                    }
                });
            }
            j++;
        });

        if (j === 0) {
            draw_settings_table();
        }
        $("#approvals-settings-tab").on("shown.bs.tab", function () {
            if (!$.fn.DataTable.isDataTable("#approval-flow-table")) {
                draw_settings_table();
            }
        });
    });
});

$("#hrs_staff_profile").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        function get_access_controls() {
            $.ajax({
                url:
                    base_url +
                    "/user-permissions/" +
                    $("#hrs_staff_profile").attr("user_id"),
                type: "GET",
                beforeSend: function (e) {
                    startSpinner();
                },
                success: function (res) {
                    $("#access_control_container").html(res);

                    $("#access_control_container")
                        .find("tbody tr")
                        .each(function () {
                            let row = $(this);
                            row.off("off").on(
                                "click",
                                ".access_check",
                                function () {
                                    let access_check = $(this);
                                    let tbody = access_check.closest("tbody");
                                    row.find(".access_check")
                                        .not(this)
                                        .prop("checked", false);

                                    if (
                                        access_check.is(":checked") &&
                                        access_check.attr("column") != "NONE" &&
                                        access_check.attr("parent_id") != ""
                                    ) {
                                        let parent_module = tbody.find(
                                            "#module_" +
                                                $(this).attr("parent_id") +
                                                "_READ",
                                        );

                                        parent_module.prop("checked", true);
                                        parent_module
                                            .closest("tr")
                                            .find(".access_check")
                                            .not(parent_module)
                                            .prop("checked", false);
                                    }

                                    if (
                                        access_check.is(":checked") &&
                                        access_check.attr("column") == "NONE" &&
                                        access_check.attr("parent_id") == ""
                                    ) {
                                        let id = access_check
                                            .attr("id")
                                            .split("_", 2)[1];
                                        tbody
                                            .find(".child_of_" + id)
                                            .each(function () {
                                                let child_module = $(this);
                                                if (
                                                    child_module.attr(
                                                        "column",
                                                    ) != "NONE"
                                                ) {
                                                    child_module.prop(
                                                        "checked",
                                                        false,
                                                    );
                                                } else {
                                                    if (
                                                        !child_module.is(
                                                            ":disabled",
                                                        )
                                                    ) {
                                                        child_module.prop(
                                                            "checked",
                                                            true,
                                                        );
                                                    }
                                                }
                                            });
                                    }

                                    let form = $(
                                        "#access_control_container",
                                    ).find("form");
                                    let data = form
                                        .find(":input[value!='']")
                                        .serialize();
                                    $.ajax({
                                        url:
                                            base_url +
                                            "/user-permissions/store/" +
                                            $("#hrs_staff_profile").attr(
                                                "user_id",
                                            ),
                                        type: "POST",
                                        data: data,
                                        beforeSend: function (e) {
                                            startSpinner();
                                        },
                                        success: function (res) {
                                            toastResponse(res);
                                            get_access_controls();
                                            stopSpinner();
                                        },
                                        error: function (
                                            xhr,
                                            textStatus,
                                            error,
                                        ) {
                                            onAjaxError(xhr, textStatus, error);
                                        },
                                        complete: function () {
                                            stopSpinner();
                                        },
                                    });
                                },
                            );
                        });
                    stopSpinner();
                },
                error: function (xhr, textStatus, error) {
                    onAjaxError(xhr, textStatus, error);
                },
                complete: function () {
                    stopSpinner();
                },
            });
        }

        let staff_id = $(".staff_contracts_table").attr("staff_id");
        let contractsDataTable = $(".staff_contracts_table").dataTable({
            colReorder: true,
            processing: true,
            serverSide: true,
            ajax: {
                url:
                    base_url +
                    "/human-resources/registration/contracts/list/" +
                    staff_id,
                type: "GET",
            },
            columns: [
                { data: "contract_number", name: "id", orderable: true },
                { data: "start_date", orderable: true },
                { data: "end_date", orderable: true },
                { data: "status", orderable: false, className: "centerClass" },
            ],
            language: {
                zeroRecords:
                    "<div class='alert alert-info'>No matching data found</div>",
                emptyTable: "<div class='alert alert-info'>No data found</div>",
            },
        });

        $("#pills-salary-adjustments-tab").on("shown.bs.tab", function () {
            if (!$.fn.DataTable.isDataTable("#staff_adjustments_table")) {
                let adjustmentDataTable = $(
                    "#staff_adjustments_table",
                ).dataTable({
                    colReorder: true,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url:
                            base_url +
                            "/human-resources/registration/staff/staff-adjustments/" +
                            staff_id,
                        type: "GET",
                    },
                    columns: [
                        {
                            data: "name",
                            name: "adjustment_name",
                            orderable: true,
                        },
                        {
                            data: "type",
                            name: "adjustment_type",
                            orderable: true,
                        },
                        {
                            data: "corresponding_to",
                            orderable: false,
                            className: "rightClass",
                        },
                        {
                            data: "amount",
                            orderable: false,
                            className: "rightClass",
                        },
                        {
                            data: "basis",
                            name: "adjustment_basis",
                            orderable: true,
                            className: "centerClass",
                        },
                        {
                            data: "status",
                            orderable: false,
                            className: "centerClass",
                        },
                    ],
                    language: {
                        zeroRecords:
                            "<div class='alert alert-info'>No matching data found</div>",
                        emptyTable:
                            "<div class='alert alert-info'>No data found</div>",
                    },
                });
            }
        });

        $("#pills-access-control-tab").on("shown.bs.tab", function () {
            get_access_controls();
        });
    });
});

$("#hrs_reg_container").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        $('a[data-toggle="pill"]').on("show.bs.tab", function (e) {
            localStorage.setItem("hrsregActiveTab", $(e.target).attr("href"));
        });

        divDismissible();

        var hrsregActiveTab = localStorage.getItem("hrsregActiveTab");
        if (hrsregActiveTab) {
            $('#hrs-reg-nav-pills a[href="' + hrsregActiveTab + '"]').tab(
                "show",
            );
        }

        regTable = $("#staffs_table").dataTable({
            order: [[0, "asc"]],
            colReorder: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: base_url + "/human-resources/registration/list",
                type: "GET",
            },
            columns: [
                { data: "name", orderable: true },
                { data: "phone", orderable: false },
                { data: "email", orderable: false },
                { data: "address", orderable: false },
                { data: "action", orderable: false },
            ],
            language: {
                zeroRecords:
                    "<div class='alert alert-info'>No matching data found</div>",
                emptyTable: "<div class='alert alert-info'>No data found</div>",
            },
        });

        regTable.find("tr").each(function () {
            $(this).find("td:first-child").attr("nowrap", "nowrap");
        });

        regTable.fnAdjustColumnSizing(false);

        $("#contracts-tab").on("shown.bs.tab", function () {
            if (!$.fn.DataTable.isDataTable("#contracts_table")) {
                contrctTable = $("#contracts_table").dataTable({
                    order: [[1, "desc"]],
                    colReorder: true,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url:
                            base_url +
                            "/human-resources/registration/contracts/list/0",
                        type: "GET",
                    },
                    columns: [
                        { data: "name", orderable: true },
                        {
                            data: "contract_number",
                            name: "employee_contracts.id",
                            orderable: true,
                        },
                        { data: "start_date", orderable: true },
                        { data: "end_date", orderable: true },
                        { data: "status", orderable: true },
                        { data: "action", orderable: false },
                    ],
                    language: {
                        zeroRecords:
                            "<div class='alert alert-info'>No matching data found</div>",
                        emptyTable:
                            "<div class='alert alert-info'>No data found</div>",
                    },
                });

                contrctTable.find("tr").each(function () {
                    $(this).find("td:first-child").attr("nowrap", "nowrap");
                });

                contrctTable.fnAdjustColumnSizing(false);
            }
        });
    });
});

$("#hrs-contract-form-one").each(function () {
    var container = $(this);
    window.addEventListener("DOMContentLoaded", function () {
        var load_user_details = function (container) {
            $.ajax({
                url:
                    base_url +
                    "/human-resources/registration/staff/user-details/" +
                    container.find('select[name="staff_id"]').val(),
                type: "GET",
                dataType: "json",
                success: function (response) {
                    container.find("#user-details-container").html(response);
                },
            });
        };
        console.log(container.find('select[name="staff_id"]'));
        if (container.find('select[name="staff_id"]').val() != "") {
            load_user_details(container);
        }
        container.find('select[name="staff_id"]').change(function () {
            load_user_details(container);
        });
    });
});

$("#hrs-contract-form-two").each(function () {
    var container = $(this);
    window.addEventListener("DOMContentLoaded", function () {
        select2custom($(".allowances-multiple-select"), "Allowance", false);
        select2custom(
            $(".contributions-multiple-select"),
            "Contribution",
            false,
        );
        select2custom($(".deductions-multiple-select"), "Deduction", false);
    });
});

$("#hrs-contract-form-three").each(function () {
    let container = $(this);
    window.addEventListener("DOMContentLoaded", function () {
        let emp_payment_mode_id = container
            .find('input[name="emp_payment_mode_id"]')
            .val();
        let emp_term_id = container
            .find('input[name="employment_term_id"]')
            .val();
        let load_bank_details = function (container) {
            $.ajax({
                url:
                    base_url +
                    "/human-resources/registration/staff/bank-details/" +
                    emp_term_id +
                    "/" +
                    emp_payment_mode_id,
                type: "GET",
                dataType: "json",
                success: function (response) {
                    container.find("#bank-details-container").html(response);
                },
            });
        };

        if (container.find('select[name="payment_mode"]').val() == "BANK") {
            load_bank_details(container);
        }
        container.find('select[name="payment_mode"]').change(function () {
            var payment_mode = $(this).val();
            if (payment_mode == "BANK") load_bank_details(container);
            else container.find("#bank-details-container").html("");
        });
    });
});

$("#hrs_prl_container").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        $('a[data-toggle="pill"]').on("show.bs.tab", function (e) {
            localStorage.setItem("hrsprlActiveTab", $(e.target).attr("href"));
        });

        var hrsprlActiveTab = localStorage.getItem("hrsprlActiveTab");
        if (hrsprlActiveTab) {
            $('#hrs-prl-nav-pills a[href="' + hrsprlActiveTab + '"]').tab(
                "show",
            );
        }
    });
});

$("#hrs_stng_container").each(function () {
    window.addEventListener("DOMContentLoaded", function () {
        $('a[data-toggle="pill"]').on("show.bs.tab", function (e) {
            localStorage.setItem("hrsstngActiveTab", $(e.target).attr("href"));
        });

        var hrsstngActiveTab = localStorage.getItem("hrsstngActiveTab");
        if (hrsstngActiveTab) {
            $('#hrs-stng-nav-pills a[href="' + hrsstngActiveTab + '"]').tab(
                "show",
            );
        }

        var initialize_delete_button = function (button, table) {
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
                        $.post(
                            base_url + "human_resources/delete_setting_item",
                            {
                                id: button.attr("item_id"),
                                table_type: button.attr("table_type"),
                            },
                            function (response) {
                                Toast.fire({
                                    icon: "success",
                                    title: response,
                                });
                                table.DataTable().draw("page");
                            },
                        );
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        swalWithBootstrapButtons.fire({
                            icon: "error",
                            text: "Cancelled",
                        });
                    }
                });
        };

        var draw_hrs_stng_datable = function (table_type) {
            let columns;
            switch (table_type) {
                case "departments":
                case "branches":
                case "job_titles":
                    columns = [
                        { data: "name", orderable: true },
                        { data: "descriptions", orderable: false },
                        { data: "status", orderable: false },
                        { data: "action", orderable: false },
                    ];
                    break;
                case "adjustments":
                    columns = [
                        {
                            data: "name",
                            orderable: true,
                            name: "adjustments.adjustment_name",
                        },
                        { data: "type", orderable: true },
                        { data: "corresponding_to", orderable: false },
                        { data: "basis", orderable: true },
                        { data: "status", orderable: false },
                        { data: "action", orderable: false },
                    ];
                    break;
            }
            let hrsStngDataTable = $("#" + table_type + "_table").dataTable({
                colReorder: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url:
                        base_url +
                        "/human-resources/settings/list/" +
                        table_type.replace(/_/g, ""),
                    type: "GET",
                },
                columns: columns,
                language: {
                    zeroRecords:
                        "<div class='alert alert-info'>No matching data found</div>",
                    emptyTable:
                        "<div class='alert alert-info'>No data found</div>",
                },
            });
        };

        draw_hrs_stng_datable("departments");

        $("#branches-tab").on("shown.bs.tab", function () {
            if (!$.fn.DataTable.isDataTable("#branches_table")) {
                draw_hrs_stng_datable("branches");
            }
        });

        $("#job_titles-tab").on("shown.bs.tab", function () {
            if (!$.fn.DataTable.isDataTable("#job_titles_table")) {
                draw_hrs_stng_datable("job_titles");
            }
        });

        $("#adjustments-tab").on("shown.bs.tab", function () {
            if (!$.fn.DataTable.isDataTable("#adjustments_table")) {
                draw_hrs_stng_datable("adjustments");
            }
        });

        $("#tax-tables-tab").on("shown.bs.tab", function () {
            var displayTaxTable = function (tax_table_div) {
                $.post(
                    base_url + "/human-resources/settings/tax-tables",
                    {
                        data_sent: true,
                    },
                    function (data) {
                        tax_table_div.html(data);
                        tax_table_div
                            .find(".delete_tax_rate")
                            .off("click")
                            .on("click", function () {
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
                                            $.post(
                                                base_url +
                                                    "human_resources/delete_tax_rate",
                                                {
                                                    id: button.attr(
                                                        "account_id",
                                                    ),
                                                },
                                                function (res) {
                                                    toastResponse(res);
                                                },
                                                "json",
                                            );
                                        } else if (
                                            /* Read more about handling dismissals below */
                                            result.dismiss ===
                                            Swal.DismissReason.cancel
                                        ) {
                                            swalWithBootstrapButtons.fire(
                                                "Cancelled",
                                                "Your tax table is safe :)",
                                                "error",
                                            );
                                        }
                                    });
                            });
                    },
                );
            };
            displayTaxTable($("#taxtable_accordion"));
        });
    });
});
