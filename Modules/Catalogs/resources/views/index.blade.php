@extends('layouts.master')
@section('body-contents')
	<!-- BREADCRUMB-->
	<section>
		<div class="row">
			<div class="col-lg-12" style="padding-right: 0; padding-left: 0;">
				<div class="au-breadcrumb-content">
					<div class="au-breadcrumb-left">
						<nav class="breadcrumb" style="margin-bottom: .5rem">
							<a class="breadcrumb-item" href="{{ route('home-page') }}"><i class="fa fa-home"></i>&nbsp;Home</a>
							<span class="breadcrumb-item active">Item Master</span>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- END BREADCRUMB-->
	<div class="row">
		<div class="col-lg-12" style="padding-left: 0; padding-right: 0">
			<div class="card">
				<div class="card-body">
					<h3>Item Master</h3>
					<div class="col-lg-12">
						<div class="div-dismissible" style="height: 75px; padding-bottom: 20px; margin-left: auto; margin-right: auto;">
							<div class="sufee-alert alert with-close alert-warning alert-dismissible fade show" style="padding-right: 19rem">
								<span class="badge badge-pill badge-warning">Hint</span>
								Before adding any Item or Unit search it first to make sure it is not there and avoid duplicates.
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						</div>
						<div class="row" id="item_master_container">
							<div class="col-md-2 mb-3" style="padding-left: 0">
								<ul class="nav nav-pills flex-column" id="item-master-nav-pills" role="tablist">
									<li class="nav-item">
										<a class="nav-link active" id="items-tab" data-toggle="pill" href="#items" role="tab" aria-controls="items" aria-selected="true">Items</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="varieties-tab" data-toggle="pill" href="#varieties" role="tab" aria-controls="varieties" aria-selected="false">Varieties</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="item-groups-tab" data-toggle="pill" href="#item-groups" role="tab" aria-controls="item-groups" aria-selected="true">Item Groups</a>
									</li>
								</ul>
							</div>
							<!-- /.col-md-4 -->
							<div class="col-md-10" style="padding: 0; margin: 0;">
								<div class="tab-content" id="myTabContent">
									<div class="tab-pane fade show active" style="min-height: 650px; overflow-y: auto;" id="items" role="tabpanel" aria-labelledby="items-tab">
										<div class="row">
											<div class="text-left col-md-6">
												<h2 class="title-3 m-b-30">
													<i class="fa fa-wrench"></i>&nbsp;&nbsp;Items
												</h2>
											</div>
										</div>
										{{ view('catalogs::items.index',['item_groups'=>$item_groups,'variety_options'=>$variety_options]) }}
									</div>
									<div class="tab-pane fade" style="min-height: 650px; overflow-y: auto;" id="varieties" role="tabpanel" aria-labelledby="varieties-tab">
										<div class="row">
											<div class="text-left col-md-6">
												<h2 class="title-3 m-b-30">
													<i class="fa fa-cogs"></i>&nbsp;&nbsp;Varieties
												</h2>
											</div>
										</div>
										{{ view('catalogs::varieties.index',['item_groups'=>$item_groups]) }}
									</div>
									<div class="tab-pane fade" style="min-height: 650px; overflow-y: auto;" id="item-groups" role="tabpanel" aria-labelledby="item-groups-tab">
										<div class="row">
											<div class="text-left col-md-6">
												<h2 class="title-3 m-b-30">
													<i class="fa fa-cubes"></i>&nbsp;&nbsp;Item Groups
												</h2>
											</div>
										</div>
										{{ view('catalogs::item_groups.index') }}
									</div>
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
		window.addEventListener("DOMContentLoaded", function() {
			divDismissible();

			// Cache DOM elements
			const $itemsContainer = $("#items_container");
			const $varietiesContainer = $("#varieties_container");
			const $itemMasterNav = $('#item-master-nav-pills');

			// Initialize tab persistence
			initTabPersistence();

			// Initialize Items tab
			initItemsTab();

			// Initialize Item Groups tab (lazy load)
			$('#item-groups-tab').on('shown.bs.tab', initItemGroupsTab);

			// Initialize Varieties tab (lazy load)
			$('#varieties-tab').on('shown.bs.tab', initVarietiesTab);

			function initTabPersistence() {
				$('a[data-toggle="pill"]').on("show.bs.tab", function(e) {
					localStorage.setItem("itemMasterActiveTab", $(e.target).attr("href"));
				});

				const itemMasterActiveTab = localStorage.getItem("itemMasterActiveTab");
				if (itemMasterActiveTab) {
					$itemMasterNav.find(`a[href="${itemMasterActiveTab}"]`).tab("show");
				}
			}

			function initItemsTab() {
				const $itemGroupSelect = $itemsContainer.find('select[name="item_group_select"]');
				const $itemVarietySelect = $itemsContainer.find('select[name="item_variety_select"]');

				// Initialize Select2
				initSelect2([$itemGroupSelect, $itemVarietySelect]);

				// Initialize DataTable
				const itemsTable = initItemsDataTable($itemGroupSelect, $itemVarietySelect);

				// Bind change event
				$itemsContainer.find("select").on('change', function() {
					itemsTable.DataTable().draw();
				});
			}

			function initItemsDataTable($itemGroupSelect, $itemVarietySelect) {
				const table = $("#items-table").dataTable({
					colReorder: true,
					processing: true,
					serverSide: true,
					ajax: {
						url: "{!! route('catalogs-list') !!}",
						type: "POST",
						data: function(e) {
							e.item_group_id = $itemGroupSelect.val();
							e.item_variety_id = $itemVarietySelect.val();
						},
					},
					columns: [{
							data: "name",
							orderable: true
						},
						{
							data: "group_name",
							orderable: true
						},
						{
							data: "action",
							orderable: false,
							searchable: false
						}
					],
					language: {
						zeroRecords: "<div class='alert alert-info'>No matching data found</div>",
						emptyTable: "<div class='alert alert-info'>No data found</div>"
					},
					drawCallback: function() {
						$(this).find("tr td:last-child").attr("nowrap", "nowrap");
					}
				});

				return table;
			}

			function initItemGroupsTab() {
				const $table = $("#item-groups-table");

				// Check if already initialized
				if ($table.attr("initialized") === "true") return;

				// Initialize DataTable
				const table = $table.dataTable({
					colReorder: true,
					serverSide: true,
					processing: true,
					ajax: {
						url: '{!! route("item-groups-list") !!}',
					},
					columns: [{
							data: "name",
							orderable: true
						},
						{
							data: "code",
							orderable: true
						},
						{
							data: "descriptions"
						},
						{
							data: "action",
							orderable: false,
							searchable: false
						}
					],
					language: {
						zeroRecords: "<div class='alert alert-info'>No matching data found</div>",
						emptyTable: "<div class='alert alert-info'>No data found</div>"
					},
					drawCallback: function() {
						$(this).find("tr td:last-child").attr("nowrap", "nowrap");
						initCustomScripts();
					}
				});

				// Mark as initialized
				$table.attr("initialized", "true");
			}

			function initVarietiesTab() {
				// Check if already initialized
				if ($.fn.DataTable.isDataTable("#varieties-table")) return;

				const $itemGroupSelect = $varietiesContainer.find('select[name="item_group_select"]');

				// Initialize Select2
				initSelect2([$itemGroupSelect]);

				// Initialize DataTable
				const varTable = initVarietiesDataTable($itemGroupSelect);

				// Bind change event
				$itemGroupSelect.on('change', function() {
					varTable.DataTable().draw();
				});
			}

			function initVarietiesDataTable($itemGroupSelect) {
				const table = $("#varieties-table").dataTable({
					colReorder: true,
					processing: true,
					serverSide: true,
					ajax: {
						url: '{!! route("varieties-list") !!}',
						data: function(e) {
							e.item_group_id = $itemGroupSelect.val() || null;
						},
					},
					columns: [{
							data: "name",
							orderable: true
						},
						{
							data: "descriptions",
							orderable: false
						},
						{
							data: "action",
							orderable: false
						}
					],
					language: {
						zeroRecords: "<div class='alert alert-info'>No matching data found</div>",
						emptyTable: "<div class='alert alert-info'>No data found</div>"
					},
					drawCallback: function() {
						$(this).find("tr td:last-child").attr("nowrap", "nowrap");
					}
				});

				return table;
			}

			function initSelect2(elements) {
				elements.forEach($el => {
					if ($el.length) {
						$el.select2({
							width: "18.5vw"
						});
					}
				});
			}
		});
	</script>
@endsection
