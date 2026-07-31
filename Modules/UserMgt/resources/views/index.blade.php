@extends('layouts.master')
@section('body-contents')

	@php
		// The tab set for this container. Each tab is gated on its child LEAF key via
		// authCan() — a permission check, not a route check, because tabs render inline.
		// A child the user cannot read produces no tab AND no rendered pane, so an
		// unauthorised sub-view never executes.
		//
		// This map (key -> pane/label/icon/view) is the one container-specific bit; every
		// tabbed container index has its own. Everything else below is the standard shell.
		$tabs = collect([
		    ['key' => 'user_mgt.users', 'pane' => 'users', 'label' => 'Users', 'icon' => 'fa fa-users', 'view' => 'usermgt::users.index'],
		    ['key' => 'user_mgt.roles', 'pane' => 'roles', 'label' => 'Roles', 'icon' => 'fa fa-cogs', 'view' => 'usermgt::roles.index'],
		    ['key' => 'user_mgt.permissions', 'pane' => 'access-controls', 'label' => 'Access Controls', 'icon' => 'fa fa-cubes', 'view' => 'usermgt::access_controls.index'],
		])
		    ->filter(fn($tab) => userCan($tab['key'], 'read'))
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
				<div class="card-body">
					<h3>{{ $title }}</h3>

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

						@if ($tabs->isEmpty())
							{{-- Container visible but no child readable (container granted Read directly
							     with no child access). Defensive — normally the auto-bump invariant means
							     a visible container has at least one visible child. --}}
							<div class="text-center" style="padding: 3rem 1rem; color: #868e96;">
								<i class="fa fa-folder-open fa-2x"></i>
								<p>No User Management sections are available to you.</p>
							</div>
						@else
							<div class="row" id="usermgt_container">
								<div class="col-md-2 mb-3" style="padding-left: 0">
									<ul class="nav nav-pills flex-column" id="userMgtNav" role="tablist">
										@foreach ($tabs as $tab)
											<li class="nav-item">
												<a class="nav-link @if ($loop->first) active @endif" id="{{ $tab['pane'] }}-tab" data-toggle="pill" href="#{{ $tab['pane'] }}" role="tab" aria-controls="{{ $tab['pane'] }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
													{{ $tab['label'] }}
												</a>
											</li>
										@endforeach
									</ul>
								</div>

								<div class="col-md-10" style="padding: 0; margin: 0;">
									<div class="tab-content" id="usermgtTabContent">
										@foreach ($tabs as $tab)
											<div class="tab-pane fade @if ($loop->first) show active @endif" style="min-height: 650px; overflow-y: auto;" id="{{ $tab['pane'] }}" role="tabpanel" aria-labelledby="{{ $tab['pane'] }}-tab">
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
@endsection

@section('scripts')
	<script>
		$(document).ready(function() {
			divDismissible();

			initTabPersistence($('#userMgtNav'), "usermgtActiveTab");

			$(function() {
				TabTables.init({
					nav: "#userMgtNav",
					storageKey: "userMgtActiveTab",
					tables: {
						"#users": initUsersTable,
						"#roles": initRolesTable,
					}
				});
			});

			function initUsersTable() {
				return $("#usersTable").DataTable({
					processing: true,
					serverSide: true,
					ajax: {
						url: "{{ route('users.data') }}", // your DataTables endpoint
						type: "POST"
					},
					columns: [{
							data: "username",
							name: "username"
						},
						{
							data: "full_name",
							name: "full_name"
						},
						{
							data: "phone",
							name: "phone"
						},
						{
							data: "email",
							name: "email"
						},
						{
							data: "roles",
							name: "roles",
							orderable: false,
							searchable: false
						},
						{
							data: "id",
							name: "actions",
							orderable: false,
							searchable: false,
							render: function(id) {
								return `
									<div class="table-data-feature pull-left">
										<button class="btn btn-sm btn-primary js-show-user" data-id="${id}"><i class="zmdi zmdi-eye"></i></button>
										<button class="btn btn-sm btn-primary js-edit-user" data-id="${id}"><i class="zmdi zmdi-edit"></i></button>
										<button class="btn btn-sm btn-danger js-delete-user" data-id="${id}"><i class="zmdi zmdi-delete"></i></button>
									</div>`;
							}
						}
					],
					order: [
						[0, "asc"]
					],
					pageLength: 25,
					responsive: true
				});
			}

			$(document).on("click", ".js-edit-user", function() {
				const id = $(this).data("id");
				// open edit modal / navigate
			});

			$(document).on("click", ".js-delete-user", function() {
				const id = $(this).data("id");
				// confirm + delete, then usersTable.ajax.reload(null, false);
			});

			function initRolesTable() {
				return $("#rolesTable").DataTable({
					ajax: {
						url: "{{ route('roles.data') }}",
						dataSrc: "data" // expects { "data": [...] }
					},
					columns: [{
							data: "name",
							name: "name"
						},
						{
							data: "guard_name",
							name: "guard_name"
						},
						{
							data: "permissions_count",
							name: "permissions_count",
							orderable: false,
							searchable: false
						},
						{
							data: "created_at",
							name: "created_at"
						},
						{
							data: "id",
							name: "actions",
							orderable: false,
							searchable: false,
							render: function(id) {
								return `
                        <button class="btn btn-sm btn-primary js-edit-role" data-id="${id}">Edit</button>
                        <button class="btn btn-sm btn-danger js-delete-role" data-id="${id}">Delete</button>`;
							}
						}
					],
					order: [
						[0, "asc"]
					],
					pageLength: 25,
					responsive: true
				});
			}

			$(document).on("click", ".js-edit-role", function() {
				const id = $(this).data("id");
				// open edit modal / navigate
			});
			$(document).on("click", ".js-delete-role", function() {
				const id = $(this).data("id");
				// confirm + delete, then $("#rolesTable").DataTable().ajax.reload(null, false);
			});

		});
	</script>
@endsection
