<form action="javascript:void(0)">
    <table class="table table-borderless table-hover table-data2 acess-control-table" type="acess-control" id="acess-control-table" style="table-layout: fixed; width: 100%;">
        <thead>
            <tr style="background-color: #2b2b2b">
                <th style="width: 1%; color: white;"></th>
                <th style="width: 41%; color: white;">MODULE</th>
                <th style="width: 14.5%; color: white;">NONE</th>
                <th style="width: 14.5%; color: white;">READ</th>
                <th style="width: 14.5%; color: white;">READ/WRITE</th>
                <th style="width: 14.5%; color: white;">FULL CONTROLL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $permission_types = ["NONE", "READ", "WRITE", "FULLCTRL"];
            foreach ($modules as $module) { ?>
                <tr>
                    <td></td>
                    <td><strong><?= $table_items[$module->name]["name"] ?></strong></td>
                    <?php foreach ($permission_types as $type) { ?>
                        <td>
                            <?php if (
                                (isset($table_items[$module->name]["sub_modules"]) && !in_array($type, ["WRITE", "FULLCTRL"])) ||
                                (!isset($table_items[$module->name]["sub_modules"]))
                            ) { ?>
                                <label class="switch switch-3d switch-primary mr-3">
                                    <input type="checkbox" name="perimssions[]" value="<?= $table_items[$module->name]["id"] . '_' . $type ?>" id="module_<?= $table_items[$module->name]["id"] . '_' . $type ?>" parent_id="" column="<?= $type ?>" class="switch-input module_<?= $table_items[$module->name]["id"] ?> access_check" <?= $table_items[$module->name][$type] ?>>
                                    <span class="switch-label"></span>
                                    <span class="switch-handle"></span>
                                </label>
                            <?php } ?>
                        </td>
                    <?php } ?>
                </tr>
                <?php if (isset($table_items[$module->name]["sub_modules"])) {
                    foreach ($table_items[$module->name]["sub_modules_arr"] as $sub_module) { ?>
                        <tr>
                            <td></td>
                            <td style="padding-left: 2rem;"><i><?= $table_items[$module->name]["sub_modules"][$sub_module->name]["name"] ?></i></td>
                            <?php foreach ($permission_types as $type) { ?>
                                <td>
                                    <label class="switch switch-3d switch-primary mr-3">
                                        <input type="checkbox" name="perimssions[]" value="<?= $table_items[$module->name]["sub_modules"][$sub_module->name]["id"] . '_' . $type ?>" id="module_<?= $table_items[$module->name]["sub_modules"][$sub_module->name]["id"] . '_' . $type ?>" parent_id="<?= $table_items[$module->name]["id"] ?>" column="<?= $type ?>" class="switch-input child_of_<?= $table_items[$module->name]["id"] ?> access_check" <?= $table_items[$module->name]["sub_modules"][$sub_module->name][$type] ?>>
                                        <span class="switch-label"></span>
                                        <span class="switch-handle"></span>
                                    </label>
                                </td>
                            <?php } ?>
                        </tr>
                <?php }
                } ?>
            <?php } ?>
        </tbody>
    </table>
</form>