<div id="items_container">
    <div class="col-md-12" style="padding-left: 0; padding-right: 0">
        <div class="table-data__tool" style="margin-bottom: 0.5rem;">
            <div class="row table-data__tool-left form-group" style="margin: 0.1rem; width: 100%;">
                <div class="col col-md-1" style="padding: 0; margin: 0;">
                    <span class="form-control" style="text-align: center;"><i class="fa fa-filter"></i>filter</span>
                </div>
                <div class="col col-md-3 dropDownSelect2" style="padding: 0; margin: 2px;">
                    <div class="rs-select2--light rs-select2--md">
                        <select class="js-select2" name="item_group_select">
                            <option value="">Item Group</option>
                            @foreach($item_groups as $index => $option)
                                <option value="{{ $option['value'] }}">{{ $index != '' ? strtoupper($option['label']) : '&nbsp;' }}</option>
                            @endforeach
                        </select>
                        <div class="dropDownSelect2"></div>
                    </div>
                </div>
                <div class="col col-md-3 dropDownSelect2" style="padding: 0; margin: 2px">
                    <div class="rs-select2--light rs-select2--md">
                        <select class="js-select2" name="item_variety_select">
                            <option value="">Item Veriety</option>
                            @foreach($variety_options as $index => $option)
                                <option value="{{ $option['value'] }}">{{ $index != '' ? strtoupper($option['label']) : '&nbsp;' }}</option>
                            @endforeach
                        </select>
                        <div class="dropDownSelect2"></div>
                    </div>
                </div>
            </div>
            <div class="table-data__tool-right">
                <a type="button" class="btn btn-sm btn-primary" id="add_item" title="Add New Item" href="{{ route('item-create') }}">
                    <i class="fa fa-plus"></i> Item
                </a>
            </div>
        </div>
    </div>
    <div class="table-responsive table--no-card m-b-30">
        <table class="table table-borderless table-hover table-data2 items" type="items" id="items-table" style="table-layout: fixed; width: 100%;">
            <thead>
                <tr style="background-color: #2b2b2b">
                    <th style="width: 75%; color: white;">NAME</th>
                    <th style="width: 15%; color: white;">GROUP</th>
                    <th style="width: 10%; color: white;"></th>
                </tr>
            </thead>
        </table>
    </div>
</div>