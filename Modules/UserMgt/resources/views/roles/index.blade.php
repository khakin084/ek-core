<div id="rolesContainer">
	<div class="row">
		<div class="text-left col-md-6">
			<h2 class="title-3 m-b-30">
				<i class="{{ $tab['icon'] }}"></i>&nbsp;&nbsp;{{ $tab['label'] }}
			</h2>
		</div>

		@if (userCan('user_mgt.roles', 'read_write'))
			<div class="text-right col-md-6">
				<button type="button" class="btn btn-sm btn-primary" id="addRole" title="Add New Role" onclick="create('{{ route('roles.create') }}', 'NRML-BTN', 'rolesContainer', null, {{ json_encode($auditData['role']) }})">
					New <i class="fa fa-plus"></i>
				</button>
			</div>
		@endif
	</div>
	<div class="table-responsive table--no-card m-b-30">
		<table class="table table-borderless table-data3" width="100%" type="roles" id="rolesTable" style="table-layout: fixed">
			<thead>
				<tr style="color: white">
					<td>NAME</td>
					<td style="width: 10%">PERM COUNT</td>
					<td style="width: 10%"></td>
				</tr>
			</thead>
		</table>
	</div>
</div>
