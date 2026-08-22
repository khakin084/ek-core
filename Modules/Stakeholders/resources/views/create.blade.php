@php
	$editing = isset($stakeholder);
	$record = $editing ? $stakeholder : null; // downstream data — array or stdClass depending on the BFF client's decode mode, never an Eloquent model here
$kind = old('kind', data_get($record, 'kind', 'ORGANIZATION'));
$id = old('id', data_get($record, 'id'));
@endphp

<style>
	#main_modal {
		width: 50%;
		left: 25%;
	}
</style>

<form name="stakeholder-reg-form" action="{{ route('stakeholders.store') }}" method="POST" autocomplete="off">
	@csrf

	<div class="modal-header">
		<h5 class="modal-title" id="stakeholderRegFormLabel">{{ $title }}</h5>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>

	<div class="modal-body">
		<div class="col-lg-12">
			<div class="card-body card-block">

				<div class="row form-group" style="margin-bottom: 0.2rem;">
					<div class="col col-md-3">
						<label class="form-control-label">Kind:</label>
					</div>
					<div class="col col-md-9">
						<select name="kind" id="kind" class="form-control @error('kind') is-invalid @enderror">
							<option value="ORGANIZATION" {{ $kind === 'ORGANIZATION' ? 'selected' : '' }}>Organization</option>
							<option value="INDIVIDUAL" {{ $kind === 'INDIVIDUAL' ? 'selected' : '' }}>Individual</option>
						</select>
						@error('kind')
							<span class="text-danger">{{ $message }}</span>
						@enderror
					</div>
				</div>

				<div class="row form-group" style="margin-bottom: 0.2rem;">
					<div class="col col-md-3">
						<label for="name" class="form-control-label">Name:</label>
					</div>
					<div class="col col-md-9">
						<input type="text" name="name" placeholder="Enter Name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', data_get($record, 'name')) }}">
						@error('name')
							<span class="text-danger">{{ $message }}</span>
						@enderror
					</div>
				</div>

				{{-- Organization fields --}}
				<div id="organization_fields">
					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="industry_id" class="form-control-label">Industry:</label>
						</div>
						<div class="col col-md-9">
							<select name="industry_id" id="industry_id" class="form-control">
								<option value="">Select Industry</option>
								@foreach ($industries as $industry)
									<option value="{{ data_get($industry, 'id') }}" {{ old('industry_id', data_get($record, 'business_details.industry_id')) == data_get($industry, 'id') ? 'selected' : '' }}>
										{{ data_get($industry, 'name') }}
									</option>
								@endforeach
							</select>
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="tin" class="form-control-label">TIN:</label>
						</div>
						<div class="col col-md-9">
							<input type="text" name="tin" placeholder="Enter TIN Number" class="form-control" value="{{ old('tin', data_get($record, 'business_details.tin')) }}">
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="vrn" class="form-control-label">VRN:</label>
						</div>
						<div class="col col-md-9">
							<input type="text" name="vrn" placeholder="Enter VRN Number" class="form-control" value="{{ old('vrn', data_get($record, 'business_details.vrn')) }}">
						</div>
					</div>
				</div>

				{{-- Individual fields --}}
				<div id="individual_fields" style="display: none;">
					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="first_name" class="form-control-label">First Name:</label>
						</div>
						<div class="col col-md-9">
							<input type="text" name="first_name" class="form-control" value="{{ old('first_name', data_get($record, 'personal_details.first_name')) }}">
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="middle_name" class="form-control-label">Middle Name:</label>
						</div>
						<div class="col col-md-9">
							<input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', data_get($record, 'personal_details.middle_name')) }}">
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="surname" class="form-control-label">Surname:</label>
						</div>
						<div class="col col-md-9">
							<input type="text" name="surname" class="form-control" value="{{ old('surname', data_get($record, 'personal_details.surname')) }}">
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="gender" class="form-control-label">Gender:</label>
						</div>
						<div class="col col-md-9">
							<select name="gender" class="form-control">
								<option value="MALE" {{ old('gender', data_get($record, 'personal_details.gender')) === 'MALE' ? 'selected' : '' }}>Male</option>
								<option value="FEMALE" {{ old('gender', data_get($record, 'personal_details.gender')) === 'FEMALE' ? 'selected' : '' }}>Female</option>
							</select>
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="date_of_birth" class="form-control-label">Date of Birth:</label>
						</div>
						<div class="col col-md-9">
							<input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', data_get($record, 'personal_details.date_of_birth')) }}">
						</div>
					</div>
				</div>

				{{-- Contacts — flat columns on stakeholders itself, no dynamic rows --}}
				<div class="componentWraper" style="padding: 10px;">
					<p class="componentTitle" style="padding: 0 4px;">Contacts</p>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="phone" class="form-control-label">Phone:</label>
						</div>
						<div class="col col-md-9">
							<input type="text" name="phone" placeholder="e.g. 0768234534" class="form-control" value="{{ old('phone', data_get($record, 'phone')) }}">
							<input type="hidden" name="id" value="{{ $id }}">
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="alt_phone" class="form-control-label">Alt Phone:</label>
						</div>
						<div class="col col-md-9">
							<input type="text" name="alt_phone" class="form-control" value="{{ old('alt_phone', data_get($record, 'alt_phone')) }}">
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="email" class="form-control-label">Email:</label>
						</div>
						<div class="col col-md-9">
							<input type="email" name="email" class="form-control" value="{{ old('email', data_get($record, 'email')) }}">
						</div>
					</div>

					<div class="row form-group" style="margin-bottom: 0.2rem;">
						<div class="col col-md-3">
							<label for="address" class="form-control-label">Address:</label>
						</div>
						<div class="col col-md-9">
							<textarea name="address" rows="2" placeholder="Got their address...?" class="form-control">{{ old('address', data_get($record, 'address')) }}</textarea>
						</div>
					</div>
				</div>

				<div class="row form-group" style="margin-bottom: 0.2rem;">
					<div class="col col-md-3">
						<label for="description" class="form-control-label">Description:</label>
					</div>
					<div class="col col-md-9">
						<textarea name="description" rows="3" placeholder="Got any notes...?" class="form-control">{{ old('description', data_get($record, 'description')) }}</textarea>
					</div>
				</div>

			</div>
		</div>

		<div class="modal-footer">
			<button type="submit" class="btn btn-primary btn-sm submit_stakeholder">
				<i class="fa fa-save"></i> Submit
			</button>
		</div>
	</div>
</form>

<script>
	$(function() {
		let form = $('form[name="stakeholder-reg-form"]');

		form.find('select[name="industry_id"]').select2({
			width: '100%',
			placeholder: 'Select Industry'
		});

        function toggleKindFields() {
            let kind = form.find('select[name="kind"]').val();
            form.find('#organization_fields').toggle(kind === 'ORGANIZATION');
            form.find('#individual_fields').toggle(kind === 'INDIVIDUAL');
        }

		form.find('select[name="kind"]').on('change', toggleKindFields);
		toggleKindFields();
	});
</script>
