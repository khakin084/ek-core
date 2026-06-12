@extends('layouts.master')
@section('body-contents')
	@php $edit = isset($itemVariety); @endphp
	<style>
		.select2-selection--multiple {
			height: 118px !important;
		}
	</style>
	<!-- BREADCRUMB -->
	<section>
		<div class="row">
			<div class="col-lg-12" style="padding-right: 0; padding-left: 0;">
				<div class="au-breadcrumb-content">
					<div class="au-breadcrumb-left">
						<nav class="breadcrumb" style="margin-bottom: .5rem">
							<a class="breadcrumb-item" href="{{ route('home-page') }}"><i class="fa fa-home"></i>&nbsp;Home</a>
							<a class="breadcrumb-item" href="{{ route('catalogs') }}">&nbsp;Item Master</a>
							<span class="breadcrumb-item active">Variety Form</span>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- END BREADCRUMB -->

	<div class="row">
		<div class="col-lg-12">
			<form class="form-horizontal" id="varieties-form" method="POST" enctype="multipart/form-data">
				<div class="card">
					<div class="card-header">
						<div class="row">
							<h4 class="form-title" id="formLabel">Variety Form</h4>
							<input type="hidden" name="item_id" value="{{ $edit ? $itemVariety['id'] : '' }}">
							<a href="{{ route('catalogs') }}" class="close" aria-label="Close">
								<span aria-hidden="true" style="color: red">&times;</span>
							</a>
						</div>
					</div>

					<div class="card-body" style="padding: 0.25rem;">
						<div class="col-lg-12">
							{{-- Flash messages --}}
							@include('partials._flash')

							<div class="row">
								<div class="col-lg-4" style="margin-top: 1rem; margin-bottom: 1rem">
									<div class="componentWraper" style="padding: 10px;">
										<p class="componentTitle" style="padding: 0 4px;">Variety Details</p>
										<div class="card-body card-block" style="padding: 0.25rem;">
											<div class="row form-group" style="margin-bottom: 0.2rem;">
												<div class="col col-md-3">
													<label for="name" class="form-control-label">Name:</label>
												</div>
												<div class="col-12 col-md-9">
													<input type="text" name="name" placeholder="Enter Variety Name" class="form-control" value="{{ $edit ? $itemVariety['name'] : '' }}" required>
													<input type="hidden" name="id" value="{{ $edit ? $itemVariety['id'] : '' }}">
												</div>
											</div>

											<div class="row form-group" style="margin-bottom: 0.2rem;">
												<div class="col col-md-3">
													<label for="item_group_id" class="form-control-label"><span>Group:</span></label>
												</div>
												<div class="col-12 col-md-9">
													<select name="item_group_id" id="item_group_id" class="form-control-sm form-control selectXs" required>
														<option value="">&nbsp;</option>
														@foreach ($itemGroups as $index => $option)
															<option value="{{ $option['value'] }}" @if ($edit && $itemVariety['item_group_id'] == $option['value']) selected @endif>
																{{ $option['label'] }}
															</option>
														@endforeach
													</select>
												</div>
											</div>

											<div class="row form-group" style="margin-bottom: 0.2rem;">
												<div class="col col-md-3" style="padding-right: 0;">
													<label for="item_uom" class="form-control-label"><span>Base Unit:</span></label>
												</div>
												<div class="col-12 col-md-9">
													<select name="item_uom" id="item_uom" class="form-control-sm form-control selectXs" required>
														@foreach ($unitsOptions as $index => $option)
															<option value="{{ $option['value'] }}" @if ($edit && $itemVariety['units'][0]['id'] == $option['value']) selected @endif>
																{{ $option['label'] }}
															</option>
														@endforeach
													</select>
													<small class="form-text text-muted">The smallest unit for this item</small>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-lg-4" style="margin-top: 1rem; margin-bottom: 1rem" id="particular_container">
									<div class="form-group">
										<label for="variety_particulars" class="control-label mb-1">Varies In</label>
										<select name="variety_particulars[]" class="my-custom-multiple-select" multiple="" aria-hidden="true" required>
											<option value="">&nbsp;</option>
											@foreach ($vpsOptions as $index => $option)
												<option value="{{ $option['value'] }}" @if ($edit && in_array($option['value'], $itemVariety['variety_particulars'])) selected @endif>
													{{ $option['label'] }}
												</option>
											@endforeach
										</select>
									</div>
								</div>

								<div class="col-lg-4" style="margin-top: 1rem; margin-bottom: 1rem">
									<div class="form-group col-md-12 col-sm-12">
										<label for="notes" class="control-label mb-1">Notes</label>
										<textarea name="notes" id="notes" rows="5" class="form-control">{{ $edit ? $itemVariety['descriptions'] : '' }}</textarea>
									</div>
								</div>

								<div class="col-lg-12">
									<div class="form-actions form-group">
										<button type="button" class="btn btn-primary btn-sm submit_variety float-right" id="submit_variety">
											<i class="fa fa-save"></i>&nbsp;&nbsp;Submit
										</button>
										<a href="{{ route('item-master-index') }}" type="button" class="btn btn-cancel btn-sm">
											<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
@endsection
@section('scripts')
	<script>
		$(function() {
			'use strict';

			const $form = $('#varieties-form');
			const $submitBtn = $('#submit_variety');
			const baseUrl = window.base_url || ''; // ensure base_url is defined globally

			// Cache form fields
			const $name = $form.find('input[name="name"]');
			const $itemGroup = $form.find('select[name="item_group_id"]');
			const $itemUom = $form.find('select[name="item_uom"]');
			const $varietyParticulars = $form.find('select[name="variety_particulars[]"]');
			const $notes = $form.find('textarea[name="notes"]');
			const $id = $form.find('input[name="id"]');

			// Select2 initialization (URLs could be moved to data attributes or constants)
			const urls = {
				particulars: "{!! route('variety-particulars-create') !!}",
				groups: "{!! route('item-group-create') !!}",
				units: "{!! route('unit-create') !!}"
			};

			select2Create($varietyParticulars, 'Particular', urls.particulars, 'OTF-BTN', false);
			select2Create($itemGroup, 'Item Group', urls.groups, 'OTF-BTN', true);
			select2Create($itemUom, 'Item Unit', urls.units, 'OTF-BTN', true);

			// Prevent double submission
			let isSubmitting = false;

			// Client-side validation
			function validateForm() {
				const errors = [];
				if (!$name.val().trim()) {
					errors.push('Variety name is required.');
				}
				if (!$itemGroup.val()) {
					errors.push('Item group is required.');
				}
				if (!$itemUom.val()) {
					errors.push('Base unit is required.');
				}
				if (!$varietyParticulars.val()) {
					errors.push('Variety Particular is required.');
				}
				return errors;
			}

			$submitBtn.on('click', function(e) {
				e.preventDefault();

				if (isSubmitting) return;
				isSubmitting = true;

				const validationErrors = validateForm();
				if (validationErrors.length > 0) {
					console.log(validationErrors.join('\n'));
					Toast.fire({
						icon: "error",
						title: "Fill all the required fields"
					});
					isSubmitting = false;
					return;
				}

				const formData = {
					id: $id.val(),
					name: $name.val().trim(),
					item_group_id: $itemGroup.val(),
					item_uom: $itemUom.val(),
					notes: $notes.val(),
					variety_particulars: $varietyParticulars.val() || []
				};

				$.ajax({
					url: "{!! route('variety-store') !!}",
					method: 'POST',
					dataType: 'json',
					data: formData,
					beforeSend: function() {
						$submitBtn.prop('disabled', true).text('Submitting, please wait...');
						startSpinner(); // assuming this function exists
					},
					success: function(res) {
						toastResponse(res); // show toast message
						// Redirect after a short delay to allow toast to be seen
						setTimeout(function() {
							window.location.replace("{!! route('catalogs') !!}");
						}, 500);
					},
					error: function(xhr) {
						// Handle validation errors from server if any
						if (xhr.responseJSON && xhr.responseJSON.errors) {
							alert('Validation error:\n' + Object.values(xhr.responseJSON.errors).join('\n'));
						} else {
							alert('An error occurred. Please try again.');
						}
					},
					complete: function() {
						$submitBtn.prop('disabled', false).text('<i class="fa fa-save"></i> Submit');
						stopSpinner();
						isSubmitting = false;
					}
				});
			});
		});
	</script>
@endsection
