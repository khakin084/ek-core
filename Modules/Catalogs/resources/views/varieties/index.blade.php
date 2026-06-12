<div id="varieties_container">
    <div class="col-md-12" style="padding-left: 0; padding-right: 0">
        <div class="table-data__tool" style="margin-bottom: 0.5rem;">
            <div class="row table-data__tool-left form-group" style="margin: 0.1rem; width: 100%;">
                <div class="col col-md-1" style="padding: 0; margin: 0;">
                    <span class="form-control" style="text-align: center;"><i class="fa fa-filter"></i>filter</span>
                </div>
                <div class="col col-md-4 dropDownSelect2" style="padding: 0; margin: 2px;">
                    <div class="rs-select2--light rs-select2--md">
                        <select class="js-select2" name="item_group_select">
                            <option value="">Item Group</option>
                            @foreach ($item_groups as $index => $option)
                                <option value="{{ $option['value'] }}">{{ strtoupper($option['label']) }}</option>
                            @endforeach
                        </select>
                        <div class="dropDownSelect2"></div>
                    </div>
                </div>
            </div>
            <div class="table-data__tool-right">
                <a type="button" class="btn btn-sm btn-primary" id="add_variety" title="Add New Variety" href="{{ route('variety-create') }}">
                    <i class="fa fa-plus"></i> Variety
                </a>
            </div>
        </div>
    </div>
    <div class="table-responsive table--no-card m-b-30">
        <table class="table table-borderless table-hover table-data2 varieties" type="varieties" id="varieties-table" style="table-layout: fixed; width: 100%;">
            <thead>
                <tr style="background-color: #2b2b2b">
                    <th style="width: 30%; color: white;">NAME</th>
                    <th style="width: 60%; color: white;">DESCTIPTIONS</th>
                    <th style="width: 10%; color: white;"></th>
                </tr>
            </thead>
        </table>
    </div>
</div>