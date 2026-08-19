<div id="ApprovalStngContainer">
	<div class="row">
		<div class="text-left col-md-6">
			<h2 class="title-3 m-b-30">
				<i class="{{ $tab['icon'] }}"></i>&nbsp;&nbsp;{{ $tab['label'] }}
			</h2>
		</div>

		@if (userCan('approvals.settings', 'read_write'))
			<div class="text-right col-md-6">
				<a href="{{ route('approvals.settings.create') }}" type="button" title="Add New User" class="btn btn-primary btn-sm">
					New <i class="fa fa-plus"></i></a>
			</div>
		@endif
	</div>
	<div class="table-responsive table--no-card m-b-30">
		<table class="table table-borderless table-data2" type="approval-flows" id="approvalFlowTable" style="table-layout: fixed; width: 100%;">
			<thead>
				<tr style="background-color: #2b2b2b">
					<th style="width: 40%; color: white;">NAME</th>
					<th style="width: 27%; color: white;">MODULE(S)</th>
					<th style="width: 10%; color: white; text-align: left">STATUS</th>
					<th style="width: 8%; color: white; text-align: right">&nbsp;</th>
				</tr>
			</thead>
		</table>
	</div>
</div>
