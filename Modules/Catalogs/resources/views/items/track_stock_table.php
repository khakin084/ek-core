<?php $edit = isset($item->id) ?>
<div class="top-campaign" style="padding-right: 10px;">
    <div class="row">
        <div class="form-group col-md-4 col-sm-12" style="padding-right: 3px;">
            <label for="optimal_stock" class="control-label mb-1">Optimal Stock</label>
            <input type="text" name="optimal_stock" placeholder="Enter Optimal Stock" <?= $edit && !empty($inventory_detail) ? 'value="' . $inventory_detail->optimal_stock . '"' : '' ?> class="form-control">
        </div>
        <div class="form-group col-md-4 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
            <label for="low_stock" class="control-label mb-1">Low Stock</label>
            <input type="text" name="low_stock" placeholder="Enter Low Stock" <?= $edit && !empty($inventory_detail) ? 'value="' . $inventory_detail->low_stock . '"' : '' ?> class="form-control">
        </div>
        <div class="form-group col-md-4 col-sm-12" style="padding-left: 3px;">
            <label for="entity_id" class="control-label mb-1">Primary Supplier:</span></label>
            <select name="entity_id" class="form-control-sm form-control selectXs">
                <?php
                foreach ($entities_options as $index => $option) { ?>
                    <option <?= $edit && isset($inventory_detail->id) && $inventory_detail->vendor_id == $index ? 'selected' : '' ?> value="<?= $index ?>"><?= $option ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
</div>