<?php $edit = isset($item) ?>
<div class="top-campaign" style="padding-right: 10px; padding-left: 10px;">
    <h5>Components<small><i>(Other Items that constitutes to make this item)</i></small>:</h5>
    <div class="table-responsive table-responsive-data2">
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
                            <input type="number" style="text-align: right" name="quantities[]" class="form-control">
                            <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" readonly>
                        </div>
                    </td>
                    <td><input type="number" style="text-align: right" name="rates[]" class="form-control"></td>
                    <td><input type="number" style="text-align: right" name="amounts[]" class="form-control" disabled=""></td>
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
                            <input type="number" style="text-align: right" name="quantities[]" class="form-control">
                            <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" readonly>
                        </div>
                    </td>
                    <td style="vertical-align: bottom;"><input type="number" style="text-align: right" name="rates[]" class="form-control"></td>
                    <td style="vertical-align: bottom;"><input type="number" style="text-align: right" name="amounts[]" class="form-control" disabled=""></td>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!$edit) { ?>
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
                                <input type="number" style="text-align: right" name="quantities[]" class="form-control">
                                <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" readonly>
                            </div>
                        </td>
                        <td><input type="number" style="text-align: right" name="rates[]" class="form-control"></td>
                        <td><input type="number" style="text-align: right" name="amounts[]" class="form-control" disabled=""></td>
                    </tr>
                    <?php
                } else {
                    $grand_total_amount = $total_amount = 0;
                    foreach ($item_components as $component) { ?>
                        <tr>
                            <td>
                                <button type="button" class="close remove_row" aria-label="Close"><span aria-hidden="true" style="color: red">&times;</span></button>
                            </td>
                            <td colspan="2">
                                <select name="item_ids[]" class="form-control-sm form-control selectXis">
                                    <?php foreach ($items_options as $index => $option) { ?>
                                        <option <?= $component->component_id == $index ? 'selected' : '' ?> value="<?= $index ?>"><?= $option ?></option>
                                    <?php } ?>
                                </select>
                                <input type="hidden" name="item_types[]" value="<?= $component->type ?>">
                                <input type="hidden" name="component_descriptions[]" <?= $component->descriptions != '' ? 'value="' . $component->descriptions . '"' : '' ?>>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="number" style="text-align: right" name="quantities[]" value="<?= $component->quantity ?>" class="form-control">
                                    <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" value="<?= $component->componentItem()->unit()->symbol ?>" readonly>
                                </div>
                            </td>
                            <td><input type="number" style="text-align: right" name="rates[]" value="<?= $component->rate ?>" class="form-control"></td>
                            <td><input type="number" style="text-align: right" name="amounts[]" value="<?= $component->quantity * $component->rate ?>" class="form-control" disabled=""></td>
                        </tr>
                <?php }
                } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;">TOTAL</td>
                    <td class="total_amount" style="text-align: right"><?= $edit ? round($total_amount, 2) : '' ?></td>
                </tr>
                <tr class="proc-costs-header" <?= ($edit && !empty($cost_components)) ? '' : 'style="display: none"' ?>>
                    <td colspan="6">
                        <h5>Additional Costs:</h5>
                    </td>
                </tr>
                <?php
                $total_cost_amount = 0;
                if ($edit && !empty($cost_components)) {
                    foreach ($cost_components as $component) {
                        $total_cost_amount += $amount = $component->quantity * $component->rate; ?>
                        <tr class="new_cost_row">
                            <td style="vertical-align: bottom;"><button type="button" class="close costs_row remove_row" aria-label="Close"><span aria-hidden="true" style="color: red">&times;</span></button></td>
                            <td style="vertical-align: bottom;">
                                <select name="item_ids[]" class="form-control-sm form-control selectXis">
                                    <?php foreach ($services_options as $index => $option) { ?>
                                        <option <?= $component->component_id == $index ? 'selected' : '' ?> value="<?= $index ?>"><?= $option ?></option>
                                    <?php } ?>
                                </select>
                                <input type="hidden" name="item_types[]" value="<?= $component->type ?>">
                            </td>
                            <td style="vertical-align: bottom;">
                                <textarea name="component_descriptions[]" class="form-control" rows="1" placeholder="Descriptions.." style="min-height: 30px;"><?= $component->descriptions ?></textarea>
                            </td>
                            <td style="vertical-align: bottom;">
                                <div class="input-group">
                                    <input type="number" style="text-align: right" name="quantities[]" class="form-control" value="<?= $component->quantity ?>">
                                    <input name="unit_ids[]" type="text" style="max-width: 41%;" class="form-control input-group-addon unit_display" value="<?= $component->componentItem()->unit()->symbol ?>" readonly>
                                </div>
                            </td>
                            <td style="vertical-align: bottom;"><input type="number" style="text-align: right" name="rates[]" class="form-control" value="<?= $component->rate ?>"></td>
                            <td style="vertical-align: bottom;"><input type="number" style="text-align: right" name="amounts[]" class="form-control" value="<?= round($amount, 2) ?>" disabled=""></td>
                        </tr>
                <?php }
                    $grand_total_amount += $total_cost_amount;
                } ?>
                <tr class="proc-costs-total" <?= ($edit && !empty($cost_components)) ? '' : 'style="display: none"' ?>>
                    <td colspan="5" style="text-align: right;">TOTAL</td>
                    <td class="total_costs_amount" style="text-align: right"><?= $edit ? round($total_cost_amount, 2) : '' ?></td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right;"><strong>GRAND TOTAL</strong></td>
                    <td><strong><span class="grand_total_amount pull-right"><?= $edit ? round($grand_total_amount, 2) : '' ?></span></strong></td>
                </tr>
            </tfoot>
        </table>
        <div class="row col-lg-12">
            <button type="button" class="btn btn-link add_item_row" style="padding-left: 0"><strong><i class="fa fa-plus"></i> Item</strong></button>
            <button type="button" class="btn btn-link add_costs_row" style="padding-left: 0"><strong><i class="fa fa-plus"></i> Cost</strong></button>
        </div>
    </div>
</div>