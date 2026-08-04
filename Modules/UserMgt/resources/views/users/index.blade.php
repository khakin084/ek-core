<div id="usersContainer">
	<div class="row">
		<div class="text-left col-md-6">
			<h2 class="title-3 m-b-30">
				<i class="{{ $tab['icon'] }}"></i>&nbsp;&nbsp;{{ $tab['label'] }}
			</h2>
		</div>

		@if (userCan('user_mgt.users', 'read_write'))
			<div class="text-right col-md-6">
				<a href="{{ route('users.create') }}" type="button" title="Add New User" class="btn btn-primary btn-sm">
					New <i class="fa fa-plus"></i></a>
			</div>
		@endif
	</div>
	<div class="table-responsive table--no-card m-b-30">
		<table class="table table-borderless table-data3" width="100%" type="staffs" id="usersTable" style="table-layout: fixed">
			<thead>
				<tr style="color: white">
					<td style="width: 20%">USERNAME</td>
					<td style="width: 20%">FULL NAME</td>
					<td style="width: 16%">PHONE</td>
					<td style="width: 16%">EMAIL</td>
					<td style="width: 20%">ROLES</td>
					<td style="width: 8%"></td>
				</tr>
			</thead>
		</table>
	</div>
</div>
