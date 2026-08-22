@php
	$edit = isset($role);
@endphp

<form name="role-reg-form" action="{{ route('roles.store') }}" method="POST" class="role-form" autocomplete="off">
	<div class="modal-header">
		<h5 class="modal-title" id="role_form_label">{{ $title }}</h5>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>

	<div class="modal-body">
		<div class="col-lg-12">
			<div class="card-body card-block">
				<div class="form-group" style="margin-bottom: 0.2rem;">
					<div class="col col-md-12">
						<label for="name" class="form-control-label">Role Name:</label>
						<input type="text" name="name" placeholder="i.e Storekeeper" class="form-control" value="{{ $edit ? $role['name'] : '' }}" required>
						<input type="hidden" name="id" value="{{ $edit ? $role['id'] : '' }}">
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal-footer">
		<button type="submit" class="btn btn-primary btn-sm submit_role">
			<i class="fa fa-save"></i> Submit
		</button>
	</div>
</form>
