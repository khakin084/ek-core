@php
$edit = !empty($item);
if ($edit) {
    $inventory_detail = $item['inventory_detail'] ?? [];
    $unitMap = collect($units_options)->keyBy('id');
}
@endphp

<div class="row componentWraper mb-4" style="padding: 10px;">
    <p class="componentTitle" style="padding: 0 4px;">Details</p>
    <div class="col-lg-8 col-md-9 col-sm-12">
        <div class="row">
            <div class="form-group col-md-4 col-sm-12" style="padding-right: 3px;">
                <label for="name" class="control-label mb-1">Name:</label><br />
                <span><strong>{{ strtoupper($variety['name']) }}</strong></span>
            </div>
            <div class="form-group col-md-4 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                <label for="item_group" class="control-label mb-1">Group:</label><br />
                <span><strong>{{ strtoupper($variety['group']['name']) }}</strong></span>
            </div>
            <div class="form-group col-md-4 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                <label for="barcode" class="control-label mb-1">Barcode:</label>
                <input type="text" name="barcode" placeholder="Enter Barcode" class="form-control" @if($edit && !empty($inventory_detail)) value="{{ $inventory_detail['barcode'] }}" @endif>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-4 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                <label for="uoms" class="control-label mb-1">&nbsp;</label>
                <label class="switch switch-3d switch-primary mr-3">
                    <input type="checkbox" name="is_fixed_asset" class="switch-input fa_o_cnsmbl_check" {{ $edit && $item['type'] == 'FIXED ASSET' ? 'checked' : '' }}>
                    <span class="switch-label"></span>
                    <span class="switch-handle"></span>
                </label>
                &nbsp;
                <span>Fixed Asset</span>
            </div>
            <div class="form-group col-md-4 col-sm-12 cnsmbl-o-sale-form-group" style="padding-left: 3px; padding-right: 3px; {{ $edit ? ($item['type'] == 'FIXED ASSET' ? 'display: none;' : '') : '' }}">
                <label class="switch switch-3d switch-primary mr-3">
                    <input type="checkbox" name="is_consumable" class="switch-input fa_o_cnsmbl_check" {{ $edit && $item['type'] == 'CONSUMABLE' ? 'checked' : '' }}>
                    <span class="switch-label"></span>
                    <span class="switch-handle"></span>
                </label>
                &nbsp;
                <span>Consumable</span>
            </div>
            <div class="form-group col-md-4 col-sm-12 cnsmbl-o-sale-form-group" style="padding-left: 3px; padding-right: 3px; {{ $edit ? ($item['type'] == 'FIXED ASSET' ? 'display: none;' : '') : '' }}">
                <label class="switch switch-3d switch-primary mr-3">
                    <input type="checkbox" name="is_for_sale" class="switch-input fa_o_cnsmbl_check" {{ $edit && !in_array($item['type'], ['CONSUMABLE', 'VARIETY', 'FIXED ASSET']) ? 'checked' : '' }}>
                    <span class="switch-label"></span>
                    <span class="switch-handle"></span>
                </label>
                &nbsp;
                <span>For Sale</span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 col-sm-12">
        <div class="row">
            <div class="form-group col-md-12 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                <label for="uoms" class="control-label mb-1">Alternative Units:</label>
                <select name="uoms[]" class="my-custom-multiple-select" multiple="" aria-hidden="true">
                    @foreach($units_options as $index => $option)
                        @php
                            $option_status = '';
                            $conditon_is_met = $variety['default_unit'][0]['id'] == $option['value'];
                            if ($conditon_is_met) {
                                $option_status = 'selected locked="locked"';
                            } else if ($edit && in_array($option['value'], $item['item_units'])) {
                                $option_status = 'selected';
                            }
                        @endphp
                        <option {{ $option_status }} value="{{ $option['value']}}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Select units other than base unit which this item is available in</small>
            </div>
        </div>
    </div>
</div>

<div class="componentWraper mb-2" style="padding: 10px;">
    <p class="componentTitle" style="padding: 0 4px;">Variety Details</p>
    <div class="row col-md-12">
        <table class="table table-borderless table-data2 variations" type="variations" id="variations-table" style="table-layout: fixed; width: 100%;">
            {!! $thead !!}
            {!! $tbody !!}
        </table>
    </div>
</div>

<div class="col-lg-12">
    <h8 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            Advanced Settings
        </a>
    </h8>
    <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
        <div class="panel-body">
            <div class="row form-group" style="margin-bottom: 0.2rem;">
                <div class="componentWraper mb-4 mt-3" style="padding: 10px;">
                    <p class="componentTitle" style="padding: 0 4px;">Properties</p>
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="form-group col-md-12 col-sm-12" style="padding-left: 3px; padding-right: 3px; margin-bottom: 0">
                            <label class="switch switch-3d switch-primary mr-3">
                                <input type="checkbox" name="is_composite" class="switch-input" {{ $edit && $item['type'] == 'COMPOSITE' ? 'checked' : '' }}>
                                <span class="switch-label"></span>
                                <span class="switch-handle"></span>
                            </label>
                            &nbsp;
                            <span>Composite Item</span>
                        </div>
                        <div class="form-group col-md-12 col-sm-12" id="componets-container" style="display: none; padding-left: 3px; padding-right: 3px; margin-bottom: 0">
                        </div>
                        <div class="form-group col-md-12 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                            <label class="switch switch-3d switch-primary mr-3">
                                <input type="checkbox" name="can_track_stock" class="switch-input" {{ $edit && !empty($inventory_detail) && ($inventory_detail['optimal_stock'] > 0 || $inventory_detail['low_stock'] > 0) ? 'checked' : '' }}>
                                <span class="switch-label"></span>
                                <span class="switch-handle"></span>
                            </label>
                            &nbsp;
                            <span>Track inventory</span>
                        </div>
                        <div class="form-group col-md-12 col-sm-12" id="track-record-ontainer" style="display: none; padding-left: 3px; padding-right: 3px;">
                        </div>
                    </div>
                </div>
                <div class="componentWraper mb-4" style="padding: 10px;">
                    <p class="componentTitle" style="padding: 0 4px;">Unit Coversion</p>
                    <div class="col-lg-6 col-md-11 col-sm-12">
                        <div class="top-campaign" style="padding-right: 10px; padding-left: 10px;">
                            <div class="table-responsive table-responsive-data2">
                                <table class="table table-data2 table-custom" id="unit-conversion-table" style="table-layout: fixed">
                                    <thead>
                                        <tr>
                                            <th style="width: 2%;"></th>
                                            <th style="width: 23.75%; text-align: right">value</th>
                                            <th style="width: 23.75%; text-align: left">unit from</th>
                                            <th style="width: 3%;">&nbsp;</th>
                                            <th style="width: 23.75%; text-align: right">value</th>
                                            <th style="width: 23.75%; text-align: left">
                                                <span>unit to</span>
                                            </th>
                                        </tr>
                                        <tr class="additional_row" style="display: none;">
                                            <td><button type="button" class="close remove_row" aria-label="Close"><span aria-hidden="true" style="color: red">&times;</span></button></td>
                                            <td>
                                                <span class="form-control" style="text-align: right;">1</span>
                                                <input type="hidden" name="value_ones[]" value="1">
                                            </td>
                                            <td>
                                                <span class="form-control" style="text-align: left;">{{ $variety['default_unit'][0]['symbol'] }}</span>
                                                <input type="hidden" name="uom_ones[]" value="{{ $variety['default_unit'][0]['id'] }}">
                                            </td>
                                            <td style="text-align: center;"> = </td>
                                            <td>
                                                <input type="text" name="value_twos[]" style="text-align: right;" placeholder="Enter Value" class="form-control">
                                            </td>
                                            <td>
                                                <select name="uom_twos[]" class="form-control-sm form-control selectXis">
                                                    @foreach($units_options as $index => $option)
                                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody id="unit_conversion_tbody">
                                        @if($edit && !empty($item['unit_conversions'])))
                                            @foreach($item['unit_conversions'] as $unit_conversion)
                                                <tr class="new_row">
                                                    <td><button type="button" class="close remove_row" aria-label="Close"><span aria-hidden="true" style="color: red">&times;</span></button></td>
                                                    <td>
                                                        <span class="form-control" style="text-align: right;">1</span>
                                                        <input type="hidden" name="value_ones[]" value="{{ $unit_conversion['value'] }}">
                                                    </td>
                                                    <td>
                                                        <span class="form-control" style="text-align: left;">{{ $symbol = $unitMap[$unit_conversion['unit_from']]['symbol'] ?? null }}</span>
                                                        <input type="hidden" name="uom_ones[]" value="{{ $unit_conversion['unit_from'] }}">
                                                    </td>
                                                    <td style="text-align: center;"> = </td>
                                                    <td>
                                                        <input type="text" name="value_twos[]" style="text-align: right;" placeholder="Enter Value" value="{{ $unit_conversion['coversion_factor'] }}" class="form-control">
                                                    </td>
                                                    <td>
                                                        <select name="uom_twos[]" class="form-control-sm form-control selectXis">
                                                            @foreach($units_options as $index => $option)
                                                                <option {{ $unit_conversion['unit_to'] == $option['value'] ? 'selected' : '' }} value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                                <div class="row col-lg-12">
                                    <button type="button" title="Add Another Conversion" class="btn btn-link add_new_row" style="padding-left: 0"><strong><i class="fa fa-plus"></i> New</strong></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="componentWraper mb-4" style="padding: 10px;">
                    <p class="componentTitle" style="padding: 0 4px;">Other Details</p>
                    <div class="row">
                        <div class="col-lg-3 col-md-9 col-sm-12">
                            <div class="form-group col-md-12 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                                <label for="alt_items" class="control-label mb-1">Cost Flow Method:</label>
                                <select name="valuation_method" class="form-control-sm form-control selectXs">
                                    <option {{ $edit && !empty($inventory_detail) && $inventory_detail['valuation_method'] == 'MOVING AVERAGE' ? 'selected' : '' }} value="MOVING AVERAGE">MOVING AVERAGE</option>
                                    <option {{ $edit && !empty($inventory_detail) && $inventory_detail['valuation_method'] == 'FIFO' ? 'selected' : '' }} value="FIFO">FIFO</option>
                                    <option {{ $edit && !empty($inventory_detail) && $inventory_detail['valuation_method'] == 'LIFO' ? 'selected' : '' }} value="LIFO">LIFO</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            <div class="form-group col-md-12 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                                <label for="alt_items" class="control-label mb-1">Alternative Item(s):</label>
                                <select name="alt_items[]" class="my-custom-multiple-select" multiple="" aria-hidden="true">
                                    @foreach($items_options as $index => $option)
                                        <option {{ $edit && in_array($index, $item['alternative_items_ids']) ? 'selected' : '' }} value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row cnsmbl-o-sale-form-group">
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            <div class="form-group col-md-12 col-sm-12" style="padding-left: 3px; padding-right: 3px;">
                                <label class="switch switch-3d switch-primary mr-3">
                                    <input type="checkbox" name="create_helping_ledger" class="switch-input" {{ $edit && !empty($inventory_detail['account_id']) ? 'checked' : '' }}>
                                    <span class="switch-label"></span>
                                    <span class="switch-handle"></span>
                                </label>
                                &nbsp;
                                <span>Create A Subsidiary Helping Ledger For This Item</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>