<form name="composite-item-reg-form" action="<?= base_url(route_to('composite-item-store')) ?>" method="POST" class="item-group-form" autocomplete="off">
    <div class="modal-header">
        <h5 class="modal-title" id="item_group_form_label">Add Composite Item</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div class="col-lg-12">
            <div class="card-body card-block">
                <div class="form-group col-md-12 col-sm-12" style="padding-right: 3px; padding-left: 3px;">
                    <label for="product_id" class="control-label mb-1">Item</label>
                    <select id="product_id" name="product_id" class="form-control-sm form-control selectXs">
                        <?php foreach ($items_options as $index => $option) { ?>
                            <option value="<?= $index ?>"><?= $option ?></option>
                        <?php } ?>
                    </select>
                </div>

                <h5>Components<small><i>(Other Items that constitutes to make this item)</i></small>:</h5>
                <div class="table-responsive table-responsive-data2" id="componets-container">
                    <table class="table table-data2" style="table-layout: fixed" id="components-table">
                        <thead>
                            <tr>
                                <th style="width: 3%"></th>
                                <th colspan="2" style="width: 54%">Item</th>
                                <th style="width: 13%">Qty</th>
                                <th style="width: 12%">Rate</th>
                                <th style="width: 18%">Amount</th>
                            </tr>
                            <tr style="display: none" class="additional_items_row">
                                <td>
                                    <button type="button" class="close remove_row" aria-label="Close"><span aria-hidden="true" style="color: red">&times;</span></button>
                                </td>
                                <td colspan="2">
                                    <select name="item_ids[]" class="form-control-sm form-control selectXis">
                                        <?php foreach ($items_options as $index => $option) { ?>
                                            <option value="<?= $index ?>"><?= $option ?></option>
                                        <?php } ?>
                                    </select>
                                    <input type="hidden" name="item_types[]" value="ITEM">
                                    <input type="hidden" name="component_descriptions[]">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" step="any" style="text-align: right" name="quantities[]" class="form-control">
                                        <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" readonly>
                                    </div>
                                </td>
                                <td><input type="number" step="any" style="text-align: right" name="rates[]" class="form-control"></td>
                                <td><input type="number" step="any" style="text-align: right" name="amounts[]" class="form-control" disabled=""></td>
                            </tr>

                            <tr style="display: none" class="additional_costs_row">
                                <td style="vertical-align: bottom;"><button type="button" class="close costs_row remove_row" aria-label="Close"><span aria-hidden="true" style="color: red">&times;</span></button></td>
                                <td style="vertical-align: bottom;">
                                    <select name="item_ids[]" class="form-control-sm form-control selectXis">
                                        <?php foreach ($services_options as $index => $option) { ?>
                                            <option value="<?= $index ?>"><?= $option ?></option>
                                        <?php } ?>
                                    </select>
                                    <input type="hidden" name="item_types[]" value="COST">
                                </td>
                                <td style="vertical-align: bottom;">
                                    <textarea name="component_descriptions[]" class="form-control" rows="1" placeholder="Descriptions.." style="min-height: 30px;"></textarea>
                                </td>
                                <td style="vertical-align: bottom;">
                                    <div class="input-group">
                                        <input type="number" step="any" style="text-align: right" name="quantities[]" class="form-control">
                                        <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" readonly>
                                    </div>
                                </td>
                                <td style="vertical-align: bottom;"><input type="number" step="any" style="text-align: right" name="rates[]" class="form-control"></td>
                                <td style="vertical-align: bottom;"><input type="number" step="any" style="text-align: right" name="amounts[]" class="form-control" disabled=""></td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <button type="button" class="close remove_row" aria-label="Close"><span aria-hidden="true" style="color: red">&times;</span></button>
                                </td>
                                <td colspan="2">
                                    <select name="item_ids[]" class="form-control-sm form-control selectXis">
                                        <?php foreach ($items_options as $index => $option) { ?>
                                            <option value="<?= $index ?>"><?= $option ?></option>
                                        <?php } ?>
                                    </select>
                                    <input type="hidden" name="item_types[]" value="ITEM">
                                    <input type="hidden" name="component_descriptions[]">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" step="any" style="text-align: right" name="quantities[]" class="form-control">
                                        <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" readonly>
                                    </div>
                                </td>
                                <td><input type="number" step="any" style="text-align: right" name="rates[]" class="form-control"></td>
                                <td><input type="number" step="any" style="text-align: right" name="amounts[]" class="form-control" disabled=""></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align: right;">TOTAL</td>
                                <td class="total_amount" style="text-align: right"></td>
                            </tr>
                            <tr class="proc-costs-header" style="display: none">
                                <td colspan="6">
                                    <h5>Additional Costs:</h5>
                                </td>
                            </tr>
                            <?php $total_cost_amount = 0; ?>
                            <tr class="proc-costs-total" style="display: none">
                                <td colspan="5" style="text-align: right;">TOTAL</td>
                                <td class="total_costs_amount" style="text-align: right"></td>
                            </tr>
                            <tr>
                                <td colspan="5" style="text-align: right;"><strong>GRAND TOTAL</strong></td>
                                <td><strong><span class="grand_total_amount pull-right"></span></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="row col-lg-12">
                        <button type="button" class="btn btn-link add_item_row" style="padding-left: 0"><strong><i class="fa fa-plus"></i> Item</strong></button>
                        <button type="button" class="btn btn-link add_costs_row" style="padding-left: 0"><strong><i class="fa fa-plus"></i> Cost</strong></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary btn-sm submit_item_group">
                <i class="fa fa-save"></i> Submit
            </button>
        </div>
    </div>
</form>
<script>
    let form = $('form[name="composite-item-reg-form"]');
    let container = $("#componets-container");
    container.find("tbody tr").each(function() {
        var av_row = $(this);
        av_row.find(".selectXis").select2({
            width: "100%"
        });
        calculateComponentRowAmount(av_row, 2);
        initializeComponentRowRemoveButton(av_row.find(".remove_row"));
        retrieveComponentRowUnitNLastPrice(av_row, "COST");
    });

    form.find("tfoot .new_cost_row").each(function() {
        var av_row = $(this);
        av_row.find(".selectXis").select2({
            width: "100%"
        });
        calculateComponentRowAmount(av_row, 2);
        initializeComponentRowRemoveButton(av_row.find(".remove_row"));
    });

    $(".add_item_row")
        .off("click")
        .on("click", function() {
            var tbody = container.find("tbody");
            var new_row = container.find(".additional_items_row").clone();
            new_row
                .removeAttr("style")
                .removeClass("additional_items_row")
                .addClass("new_row")
                .appendTo(tbody);

            new_row.find(".selectXis").select2({
                width: "100%"
            });
            retrieveComponentRowUnitNLastPrice(new_row, "COST");
            calculateComponentRowAmount(new_row, 2);
            initializeComponentRowRemoveButton(
                new_row.find(".remove_row")
            );
            initCustomScripts();
        });

    $(".add_costs_row")
        .off("click")
        .on("click", function() {
            var tfoot = container.find("tfoot");
            tfoot.find("tr").each(function() {
                $(this).removeClass("removal_flag");
            });
            var headrow = tfoot
                .find(".proc-costs-header")
                .removeAttr("style");
            var totalrow = tfoot
                .find(".proc-costs-total")
                .removeAttr("style");
            var new_row = container.find(".additional_costs_row").clone();
            new_row
                .removeAttr("style")
                .removeClass("additional_costs_row")
                .addClass("new_cost_row")
                .insertAfter(headrow);
            new_row.find(".selectXis").select2({
                width: "100%"
            });
            retrieveComponentRowUnitNLastPrice(new_row, "COST");
            calculateComponentRowAmount(new_row, 2);
            initializeComponentRowRemoveButton(
                new_row.find(".remove_row")
            );
            initCustomScripts();
        });

    calculateComponentsGrandTotalAmount(form, 2);

    form.on(
        "change keyup",
        'select[name="item_ids[]"], input[name="rates[]"],  input[name="quantities[]"]',
        function() {
            form.find("tbody tr").each(function() {
                var row = $(this);
                calculateComponentRowAmount(row, 2);
            });
            calculateComponentsGrandTotalAmount(form, 2);
        }
    );
</script>