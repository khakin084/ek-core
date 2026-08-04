@extends('layouts.master')
@section('body-contents')
	@php
		$url = route('usermgt.index');
	@endphp
	<!-- BREADCRUMB -->
	<section>
		<div class="row">
			<div class="col-lg-12" style="padding-right: 0; padding-left: 0;">
				<div class="au-breadcrumb-content">
					<div class="au-breadcrumb-left">
						<nav class="breadcrumb" style="margin-bottom: .5rem">
							<a class="breadcrumb-item" href="{{ route('home-page') }}"><i class="fa fa-home"></i>&nbsp;Home</a>
							<a class="breadcrumb-item" href="{{ $url }}">&nbsp;User Mgt</a>
							<span class="breadcrumb-item active">{{ $title }}</span>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- END BREADCRUMB -->

	<div class="row">
		<div class="col-lg-12" style="padding-left: 0; padding-right: 0">
			<div class="card">
				<div class="card-header">
					<div class="row">
						<h4 class="form-title" id="mainFormLabel"><span>{{ $title }}</span></h4>
						<a href="{{ $url }}" class="close" aria-label="Close">
							<span aria-hidden="true" style="color: red">&times;</span>
						</a>
					</div>
				</div>
				<div class="card-body" style="padding: 0.25rem;">
					<div class="col-lg-12">
						<div class="div-dismissible" style="height: 75px; padding-bottom: 20px; margin-left: auto; margin-right: auto;">
							<div class="sufee-alert alert with-close alert-warning alert-dismissible fade show" style="padding-right: 19rem">
								<span class="badge badge-pill badge-warning">Hint</span>
								Hints will go here.
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						</div>

						{{-- Flash messages --}}
						@include('partials._flash')

						<div class="col-lg-12" style="margin-top: 1rem;">
							<h4>Permissions</h4>
							<div class="p-3 border rounded bg-white" id="rolePermissions">
								<!-- loaded content goes here -->


							</div>
							<div class="col-lg-12">
								<div class="form-actions form-group" style="margin-top: 2rem;">
									<button type="submit" class="btn btn-primary btn-sm float-right" id="saveRolePermissions"><i class="fa fa-save"></i>&nbsp;&nbsp;Submit</button>
									<a href="{{ $url }}" type="button" class="btn btn-cancel btn-sm"><i class="fa fa-times"></i>&nbsp;&nbsp;Cancel</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
@section('scripts')
	<script>
		$(document).ready(function() {
			divDismissible();

			loadAccessPane('{{ route('roles.access.permissions', [$role['id']]) }}', '#rolePermissions');

			function loadAccessPane(url, target) {
				$(target).html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin"></i></div>');
				$.get(url)
					.done(html => $(target).html(html))
					.fail(() => $(target).html('<div class="text-danger p-3">Failed to load. Please retry.</div>'));
			}

			/**
			 * Read the matrix into a complete { module_key: level_int } map.
			 * Because every row has a NONE radio checked by default, this always returns
			 * a level for EVERY module — that's what makes the save a true mass-assign
			 * rather than a patch of only the rows the admin touched.
			 */
			function collectPermissions($scope) {
				const map = {};
				$scope.find('input[type="radio"]:checked').each(function() {
					const name = $(this).attr('name'); // e.g. permissions[ACCOUNTS]
					const key = name.substring(name.indexOf('[') + 1, name.indexOf(']'));
					map[key] = parseInt(this.value, 10);
				});
				return map;
			}

			/**
			 * Shared saver. opts: { url, scopeSelector, buttonSelector, onSuccess }
			 */
			function savePermissions(opts) {
				const $scope = $(opts.scopeSelector);
				const $btn = $(opts.buttonSelector);
				const $matrix = $scope.find('.perm-matrix');

				const permissions = collectPermissions($scope);

				// Sanity guard: collected keys must equal rendered rows. A mismatch means a
				// row lost its checked radio, which would silently save that module as NONE.
				const renderedRows = $matrix.find('tr').filter((_, tr) => $(tr).find('input[type="radio"]').length).length;
				if (Object.keys(permissions).length !== renderedRows) {
					accessNotify('Some rows have no level selected — please review before saving.', 'warning');
					return;
				}

				$.ajax({
					url: opts.url,
					method: 'POST',
					contentType: 'application/json',
					data: JSON.stringify({
						permissions
					}),
					beforeSend: () => setBtnLoading($btn, true),
					success: function(res) {
						accessNotify((res && res.message) || 'Permissions saved.', 'success');
						$matrix.data('dirty', false);
						if (typeof opts.onSuccess === 'function') opts.onSuccess(res);
					},
					error: function(xhr) {
						if (xhr.status === 422) {
							const errs = (xhr.responseJSON && xhr.responseJSON.errors) || {};
							const first = Object.values(errs)[0];
							accessNotify((first && first[0]) || 'Validation failed.', 'danger');
						} else {
							accessNotify((xhr.responseJSON && xhr.responseJSON.message) || 'Save failed. Please retry.', 'danger');
						}
					},
					complete: () => setBtnLoading($btn, false)
				});
			}

			function saveRolePermissions() {
				savePermissions({
					url: "{{ route('roles.access.permissions.save', [$role['id']]) }}",
					scopeSelector: '#rolePermissions',
					buttonSelector: '#saveRolePermissions'
				});
			}

			/* -------- Small shared helpers -------- */

			function setBtnLoading($btn, loading) {
				if (loading) {
					$btn.prop('disabled', true)
						.data('html', $btn.html())
						.html('<i class="fa fa-spinner fa-spin"></i>&nbsp;Saving...');
				} else {
					$btn.prop('disabled', false).html($btn.data('html') || 'Save');
				}
			}

			// Swap for your toastr/sufee-alert if you have one wired.
			function accessNotify(message, type) {
				if (window.toastr) {
					toastr[type === 'danger' ? 'error' : type](message);
					return;
				}
				alert(message);
			}

			// Mark the matrix dirty when a level changes (drives the unsaved-changes guard).
			$(document).on('change', '.perm-matrix input[type="radio"]', function() {
				$(this).closest('.perm-matrix').data('dirty', true);
			});

			$(document).on('click', '#saveRolePermissions', function() {
				saveRolePermissions() 
			});

		});
	</script>
@endsection
