@extends('layouts.master')
@section('body-contents')
	@php
		$url = route('usermgt.index');

		// The tab set for this container. Each tab is gated on its child LEAF key via
		// authCan() — a permission check, not a route check, because tabs render inline.
		// A child the user cannot read produces no tab AND no rendered pane, so an
		// unauthorised sub-view never executes.
		//
		// This map (key -> pane/label/icon/view) is the one container-specific bit; every
		// tabbed container index has its own. Everything else below is the standard shell.
		$tabs = collect([['key' => 'user_mgt.users', 'pane' => 'access-controls', 'label' => 'Access Controls', 'view' => 'usermgt::users.access_controls.index'], ['key' => 'user_mgt.users', 'pane' => 'authorized-approvals', 'label' => 'Authorized Approvals', 'view' => 'usermgt::users.authorized_approvals.index']])
		    ->filter(fn($tab) => userCan($tab['key'], 'read_write'))
		    ->values();
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

						<div class="row form-wrap" id="user_profile" user_id="{{ $user['id'] }}" style="padding: 0;">
							<div class="col-lg-12" style="margin-bottom: 1.3rem">
								<div class="componentWraper">
									<p class="componentTitle">About Me</p>
									<div class="row col-md-12 col-sm-12" style="padding: 0; margin: 3px;">
										<div class="col-md-1 col-sm-12" style="padding: 0; margin: 3px;">
											<div class="text-center" style="margin-bottom: .5rem">
												<img class="img-fluid" src="{{ asset('images/icon/avatar-01.jpg') }}" alt="User profile picture">
											</div>
											<div>
												@if (userCan('user_mgt.users', 'read_write'))
													<a title="Edit About Me" href="{{ route('users.edit', [$user['id']]) }}" class="btn btn-primary btn-block" style="width: inherit; height: 2rem"><b>Edit</b></a>
												@endif
											</div>
										</div>

										<div class="col-md-1 col-sm-12" style="padding: 0; margin: 3px;">
										</div>

										<div class="col-md-4 col-sm-12" style="padding: 0; margin: 3px;">
											<h3 class="profile-username" style="font-family: 'Open Sans', sans-serif;">{{ $user['full_name'] }}</h3>
											<p class="text-muted">Software Engineer</p>
											<div class="row form-group" style="margin-bottom: 0.2rem;">
												<div class="col col-md-1">
													<strong><i class="fas fa-phone-square"></i></strong>
												</div>
												<div class="col-12 col-md-10">
													<span>{{ $user['phone'] }}</span>
												</div>
											</div>
											<div class="row form-group" style="margin-bottom: 0.2rem;">
												<div class="col col-md-1">
													<strong><i class="fas fa-envelope"></i></strong>
												</div>
												<div class="col-12 col-md-10">
													<span>{{ $user['email'] }}</span>
												</div>
											</div>
										</div>

										<div class="col-md-4 col-sm-12" style="padding: 0; margin: 3px;">
											<div class="row form-group" style="margin-bottom: 0.2rem;">
												<div class="col col-md-1">
													<strong><i class="fas fa-globe-africa"></i></strong>
												</div>
												<div class="col-12 col-md-10">
													<span>{{ $tenant['slug'] }}.ek.co.tz</span>
												</div>
											</div>
											<div class="row form-group" style="margin-bottom: 0.2rem;">
												<div class="col col-md-1">
													<strong><i class="fas fa-map-marker-alt"></i></strong>
												</div>
												<div class="col-12 col-md-10">
													<span>NIL</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							@if (!$tabs->isEmpty())
								<div class="row" id="usermgt_container">
									<div class="col-md-12 mb-3" style="padding-left: 0">
										<ul class="nav nav-tabs" id="userProfileNav" role="tablist">
											@foreach ($tabs as $tab)
												<li class="nav-item">
													<a class="nav-link @if ($loop->first) active @endif" id="{{ $tab['pane'] }}-tab" data-toggle="pill" href="#{{ $tab['pane'] }}" role="tab" aria-controls="{{ $tab['pane'] }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
														{{ $tab['label'] }}
													</a>
												</li>
											@endforeach
										</ul>
									</div>

									<div class="col-md-12" style="padding: 0; margin: 0;">
										<div class="tab-content" id="usermgtTabContent">
											@foreach ($tabs as $tab)
												<div class="tab-pane fade @if ($loop->first) show active @endif" style="min-height: 450px; overflow-y: auto;" id="{{ $tab['pane'] }}" role="tabpanel" aria-labelledby="{{ $tab['pane'] }}-tab">
													{{-- Only authorised panes reach this loop, so the sub-view never
												     renders for a user who cannot read it. --}}
													@include($tab['view'])
												</div>
											@endforeach
										</div>
									</div>
								</div>
							@endif
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

			initTabPersistence($('#userMgtNav'), "userProfileActiveTab");

			$(function() {
				TabTables.init({
					nav: "#userProfileNav",
					storageKey: "userProfileActiveTab",
					tables: {
						"#access-controls": loadAccessControl,
					}
				});
			});

			function loadAccessControl() {
				loadAccessPane('{{ route('users.access.permissions', [$user['id']]) }}', '#accessPermissions');
				loadAccessPane('{{ route('users.access.roles', [$user['id']]) }}', '#accessRoles');
			}

			function loadAccessPane(url, target) {
				$(target).html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin"></i></div>');
				$.get(url)
					.done(html => $(target).html(html))
					.fail(() => $(target).html('<div class="text-danger p-3">Failed to load. Please retry.</div>'));
			}

			function collectRoles($scope) {
				return $scope.find('input.role-check:checked').map((_, el) => el.value).get();
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

			function saveUserAccess() {
				const $perm = $('#accessPermissions');
				const $roles = $('#accessRoles');

				const permissions = collectPermissions($perm);

				// Same complete-map guard as before.
				const rendered = $perm.find('.perm-matrix tr')
					.filter((_, tr) => $(tr).find('input[type="radio"]').length).length;
				if (Object.keys(permissions).length !== rendered) {
					accessNotify('Some rows have no level selected — please review before saving.', 'warning');
					return;
				}

				const $btn = $('#saveUserAccess');
				$.ajax({
					url: "{{ route('users.access.save', [$user['id']]) }}",
					method: 'POST',
					contentType: 'application/json',
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},
					// Both keys present → one request, one pv bump. roles:[] here means
					// "user genuinely has no roles", which is the correct explicit-clear signal.
					data: JSON.stringify({
						roles: collectRoles($roles),
						permissions
					}),
					beforeSend: () => setBtnLoading($btn, true),
					success: (res) => accessNotify((res && res.message) || 'Access updated.', 'success'),
					error: (xhr) => accessNotify(
						(xhr.responseJSON && xhr.responseJSON.message) || 'Save failed. Please retry.', 'danger'),
					complete: () => setBtnLoading($btn, false)
				});
			}

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

			$(document).on('click', '#saveUserAccess', function() {
				saveUserAccess() 
			});
		});
	</script>
@endsection
