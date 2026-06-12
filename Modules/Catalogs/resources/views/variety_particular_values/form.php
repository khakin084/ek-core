<?php $edit = isset($variety_particular_value); ?>
<form name="variety-partcular-reg-form" action="<?= base_url(route_to('variety-particulars-store-value')) ?>" method="POST" class="form-horizontal variety-partcular-form" autocomplete="off">
    <div class="modal-header">
        <h5 class="modal-title">Add Particular Value</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div class="col-lg-12">
            <div class="card-body card-block">
                <div class="row form-group" style="margin-bottom: 0.2rem;">
                    <div class="col col-md-3">
                        <label for="name" class=" form-control-label">Name:</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <div class="input-group select-group">
                            <input name="value" type="text" class="form-control" aria-required="true" value="<?= $edit ? $variety_particular_value->value : '' ?>">
                            <input type="hidden" name="id" value="<?= $edit ? $variety_particular_value->id : '' ?>">
                            <input type="hidden" name="item_variety_particular_id" value="<?= $edit ? $variety_particular_value->item_variety_particular_id : $variety_particular->id ?>">
                            <select name="unit_id" class="form-control-sm form-control input-group-addon" style="max-width:30%;">
                                <?php
                                foreach ($units_options as $index => $option) { ?>
                                    <option <?= $edit && $variety_particular_value->unit_id == $index ? 'selected' : '' ?> value="<?= $index ?>"><?= $option ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row form-group" style="margin-bottom: 0.2rem;">
                    <div class="col col-md-3">
                        <label for="descriptions" class=" form-control-label">Descriptions:</label>
                    </div>
                    <div class="col col-md-9">
                        <textarea name="descriptions" rows="3" placeholder="Any descriptions..?" class="form-control"><?= $edit ? $variety_particular_value->descriptions : '' ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary btn-sm submit_variety_particular_value">
                <i class="fa fa-save"></i> Submit
            </button>
        </div>
    </div>
</form>