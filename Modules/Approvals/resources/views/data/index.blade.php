<div id="approvalsContainer">
	<div class="row">
		<div class="text-left col-md-6">
			<h2 class="title-3 m-b-30">
				<i class="{{ $tab['icon'] }}"></i>&nbsp;&nbsp;{{ $tab['label'] }}
			</h2>
		</div>
	</div>
	<div class="row" style="padding-right: 0; margin-bottom: 1rem;">
		<div class="col-lg-11" id="head-div">
			<div class="row form-group" style="margin-bottom: 0.2rem;">

				<!-- Filter (removed invalid 'for' attribute since there is no input) -->
				<div class="col-12 col-md-1" style="padding-left: 1.3rem; padding-right: 0.3rem;">
					<label class="control-label mb-1">&nbsp;</label>
					<span class="form-control" style="text-align: center;"><i class="fa fa-filter"></i> filter</span>
				</div>

				<!-- Requester -->
				<div class="col-12 col-md-2" style="padding-left: 0.3rem; padding-right: 0.3rem;">
					<label for="requester_select" class="control-label mb-1">Requester</label>
					<select id="requester_select" name="requester" class="form-control-sm form-control selectXs">
						<option value="">&nbsp;</option>
						@foreach ($users as $u)
							<option value="{{ $u['value'] }}">{{ strtoupper($u['label']) }}</option>
						@endforeach
					</select>
				</div>

				<!-- Type -->
				<div class="col-12 col-md-2" style="padding-left: 0.3rem; padding-right: 0.3rem;">
					<label for="type_select" class="control-label mb-1">Type</label>
					<select id="type_select" name="request_type" class="form-control-sm form-control selectXs">
						<option value="">&nbsp;</option>
						@foreach ($approvalTypes as $type)
							{{-- value is the UUID, NOT a positional integer --}}
							<option value="{{ $type['id'] }}">{{ strtoupper($type['name']) }}</option>
						@endforeach
					</select>
				</div>

				<!-- Status -->
				<div class="col-12 col-md-2" style="padding-left: 0.3rem; padding-right: 0.3rem;">
					<label for="status_select" class="control-label mb-1">Status</label>
					<select id="status_select" name="request_status" class="form-control-sm form-control selectXs">
						<option value="">&nbsp;</option>
					</select>
				</div>

				<!-- From -->
				<div class="col-12 col-md-2" style="padding-left: 0.3rem; padding-right: 0.3rem;">
					<label for="date_from" class="control-label mb-1">From</label>
					<input id="date_from" name="date_from" type="text" class="form-control datepicker" value="">
				</div>

				<!-- To -->
				<div class="col-12 col-md-2" style="padding-left: 0.3rem; padding-right: 0.3rem;">
					<label for="date_to" class="control-label mb-1">To</label>
					<input id="date_to" name="date_to" type="text" class="form-control datepicker" value="">
				</div>

			</div>
		</div>
	</div>
	<div class="row" style="margin: 10px;">
		<div class="col-md-12 text-right">
			<button class="btn btn-sm btn-primary js-search-filters">
				<i class="fa fa-search"></i> Apply Filters
			</button>
			<button class="btn btn-sm btn-secondary js-reset-filters">
				<i class="fa fa-undo"></i> Reset
			</button>
		</div>
	</div>
	<div class="table-responsive table--no-card m-b-30">
		<table class="table table-borderless table-data2" type="approvals" id="approvalsTable" style="table-layout: fixed; width: 100%;">
			<thead>
				<tr style="background-color: #2b2b2b">
					<th style="width: 19%; color: white;">REQUEST DATE</th>
					<th style="width: 19%; color: white;">REQUESTED FOR</th>
					<th style="width: 19%; color: white;">REQUEST TYPE</th>
					<th style="width: 9%; color: white;">REFERENCE</th>
					<th style="width: 10%; color: white;">VALUE</th>
					<th style="width: 13%; color: white;">CREATED BY</th>
					<th style="width: 7%; color: white; text-align: left">STATUS</th>
					<th style="width: 4%; color: white; text-align: right">&nbsp;</th>
				</tr>
			</thead>
		</table>
	</div>
</div>
