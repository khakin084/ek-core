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
		$tabs = collect([['key' => 'approvals.data', 'pane' => 'requests', 'label' => 'Requests', 'icon' => 'fas fa-calendar-check', 'view' => 'approvals::data.index'], ['key' => 'approvals.settings', 'pane' => 'settings', 'label' => 'Settings', 'icon' => 'fas fa-cogs', 'view' => 'approvals::settings.index']])
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
								<p>No approvals sections are available to you.</p>
							</div>
						@else
							<div class="row" id="approval_container">
								<div class="col-md-2 mb-3" style="padding-left: 0">
									<ul class="nav nav-pills flex-column" id="approvalNav" role="tablist">
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
									<div class="tab-content" id="approvalTabContent">
										@foreach ($tabs as $tab)
											<div class="tab-pane fade @if ($loop->first) show active @endif" style="min-height: 650px; overflow-y: auto;" id="{{ $tab['pane'] }}" role="tabpanel" aria-labelledby="{{ $tab['pane'] }}-tab">
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

			// DOM REFERENCES
			const $container = $('#approval_container');
			const $requesterSelect = $container.find('select[name="requester"]');
			const $requestTypeSelect = $container.find('select[name="request_type"]');
			const $requestStatusSelect = $container.find('select[name="request_status"]');
			const $dateFromInput = $container.find('input[name="date_from"]');
			const $dateToInput = $container.find('input[name="date_to"]');
			const $nav = $('#approvalNav a[data-toggle="pill"]');

			divDismissible();

			// Hold the instance so filters can reload instead of re-initializing.
			let approvalsTable;
			
			TabTables.init({
				nav: "#approvalNav",
				storageKey: "approvalNavActiveTab",
				tables: {
					"#requests": initApprovalsTable,
					"#settings": initApprovalSettingsTable
				}
			});

			function initApprovalsTable() {
				approvalsTable = $("#approvalsTable").DataTable({
					processing: true,
					serverSide: true,
					ajax: {
						url: "{{ route('approvals.data.data') }}",
						type: "POST",
						data: function(e) {
							e.requester = $requesterSelect.val();
							e.request_type = $requestTypeSelect.val();
							e.request_status = $requestStatusSelect.val();
							e.date_from = $dateFromInput.val();
							e.date_to = $dateToInput.val();
						},
					},
					columns: [{
							data: "request_date",
							name: "request_date"
						},
						{
							data: "requested_for",
							name: "requested_for"
						},
						{
							data: "requested_type",
							name: "requested_type"
						},
						{
							data: "reference",
							name: "reference"
						},
						{
							data: "value",
							name: "value"
						},
						{
							data: "created_by",
							name: "created_by"
						},
						{
							data: "status",
							name: "status"
						},
						{
							data: "id",
							name: "actions",
							orderable: false,
							searchable: false,
							render: function(id) {
								return `
									<div class="table-data-feature pull-left" style="display: flex; gap: 2px;">
										<button class="btn btn-sm btn-success js-show-request" data-id="${id}">
											<i class="zmdi zmdi-eye"></i>
										</button>
									</div>`;
							}
						}
					],
					order: [
						[0, "desc"]
					],
					pageLength: 25,
					responsive: true,
					error: function(xhr, error, thrown) {
						console.error('DataTable Ajax Error:', error, thrown);
					}
				});
				return approvalsTable;
			}
			
			function reloadApprovals() {
				if (approvalsTable) approvalsTable.ajax.reload();
			}

			$container.find('.js-search-filters').on('click', reloadApprovals);

			$container.find('.js-reset-filters').on('click', function() {
				$requesterSelect.val('').trigger('change');
				$requestTypeSelect.val('').trigger('change');
				$requestStatusSelect.val('').trigger('change');
				$dateFromInput.val('');
				$dateToInput.val('');
				reloadApprovals();
			});

			// Tab event: init select2 widgets only when a pane becomes visible.
			// (Separate concern from TabTables — widget init, not table init.)
			$nav.on('shown.bs.tab', function(e) {
				var paneId = $(e.target).attr('href');
				if (paneId && paneId.startsWith('#')) {
					initSelect2(paneId);
				}
			});

			// Page load: init select2 in the currently active tab.
			var $activeTab = $nav.filter('.active');
			if ($activeTab.length) {
				initSelect2($activeTab.attr('href'));
			}

			function initSelect2(container) {
				$(container).find('.selectXs').each(function() {
					var $this = $(this);
					if (!$this.data('select2')) {
						$this.select2({
							width: '100%',
							allowClear: true
						});
					}
				});
			}

			function initApprovalSettingsTable() {
				return $("#approvalFlowTable").DataTable({
					processing: true,
					serverSide: true,
					ajax: {
						url: "{{ route('approvals.settings.data') }}",
						type: "POST"
					},
					columns: [{
							data: "name",
							name: "name"
						},
						{
							data: "types",
							name: "types"
						},
						{
							data: "active",
							name: "active"
						},
						{
							data: "id",
							name: "actions",
							orderable: false,
							searchable: false,
							render: function(id) {
								return `
									<div class="table-data-feature pull-left" style="display: flex; gap: 2px;">
										<button class="btn btn-sm btn-primary js-edit-aflow" data-id="${id}">
											<i class="zmdi zmdi-edit"></i>
										</button>
									</div>`;
							}
						}
					],
					order: [
						[0, "desc"]
					],
					pageLength: 25,
					responsive: true
				});
			}

			$(document).on("click", ".js-edit-aflow", function() {
				const id = $(this).data("id");
				const aflowEditUrl = "{{ route('approvals.settings.edit', ['__ID__']) }}";
				window.location.href = aflowEditUrl.replace("__ID__", id);
			});

			$(document).on("click", ".js-show-request", function() {
				const id = $(this).data("id");
				const requestShowUrl = "{{ route('approvals.data.show', ['__ID__']) }}";
				window.location.href = requestShowUrl.replace("__ID__", id);
			});

		});
	</script>
@endsection
