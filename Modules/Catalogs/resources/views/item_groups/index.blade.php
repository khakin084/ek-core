<div id="item_groups_container">
	<div class="col-md-12" style="padding-left: 0; padding-right: 0">
		<div class="table-data__tool" style="margin-bottom: 0.5rem;">
			<div class="row table-data__tool-left form-group" style="margin: 0.1rem;">
			</div>
			<div class="table-data__tool-right">
				<button type="button" class="btn btn-sm btn-primary" id="add_item_group" title="Add New Item Group" 
                onclick="create('{!! route('item-group-create') !!}', 'NRML-BTN', 'item_groups_container', null, {
                module: 'Item Master',
                entity: 'Item Group'
                })">
					<i class="fa fa-plus"></i> Item Group
				</button>
			</div>
		</div>
	</div>
	<div class="table-responsive table--no-card m-b-30">
		<table class="table table-borderless table-hover table-data2 item-groups" type="item-groups" id="item-groups-table" style="table-layout: fixed; width: 100%;">
			<thead>
				<tr style="background-color: #2b2b2b">
					<th style="width: 30%; color: white;">NAME</th>
					<th style="width: 15%; color: white;">CODE</th>
					<th style="width: 30%; color: white;">DESCTIPTIONS</th>
					<th style="width: 10%; color: white;"></th>
				</tr>
			</thead>
		</table>
	</div>
</div>
