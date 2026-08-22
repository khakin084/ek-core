@extends('layouts.master')
@section('body-contents')
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
					<h3>{{ $title }}</h3><small>Customers | Vendors | Contractors | Partners | Third Parties </small>

					<div class="col-lg-12" id="stakeholderContainer">
						<div class="div-dismissible" style="height: 75px; padding-bottom: 20px; margin-left: auto; margin-right: auto;">
							<div class="sufee-alert alert with-close alert-warning alert-dismissible fade show" style="padding-right: 19rem">
								<span class="badge badge-pill badge-warning">Hint</span>
								Hints will go here.
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						</div>
						<div class="col-md-12" style="padding-left: 0; padding-right: 0">
							<div class="table-data__tool" style="margin-bottom: 0.5rem;">
								<div class="row table-data__tool-left form-group" style="margin: 0.1rem;">
								</div>
								@if (userCan('stakeholders', 'read_write'))
									<div class="table-data__tool-right">
										<button type="button" class="btn btn-sm btn-primary" id="add_stakeholder" title="Add New Stakeholder" onclick="create('{{ route('stakeholders.create') }}', 'NRML-BTN', 'stakeholderContainer', null, {{ json_encode($auditData) }})">
											<i class="fa fa-plus"></i> New
										</button>
									</div>
								@endif
							</div>
						</div>
						<!-- DATA TABLE-->
						<div class="table-responsive table--no-card m-b-30">
							<table class="table table-borderless table-hover table-data2 stakeholders" type="stakeholders" id="stakeholdersTable" style="table-layout: fixed; width: 100%;">
								<thead>
									<tr style="background-color: #2b2b2b">
										<th style="width: 16%; color: white">PHONE</th>
										<th style="width: 16%; color: white">EMAIL</th>
										<th style="width: 30%; color: white">NAME</th>
										<th style="width: 30%; color: white">ADDRESS</th>
										<th style="width: 8%; color: white"></th>
									</tr>
								</thead>
							</table>
						</div>
						<!-- END DATA TABLE-->
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
	<script>
		$(document).ready(function() {

			const stkAuditData = @json($auditData);

			divDismissible();

			initStakeholdersTable()

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

			function initStakeholdersTable() {
				return $("#stakeholdersTable").DataTable({
					processing: true,
					serverSide: true,
					ajax: {
						url: "{{ route('stakeholders.data') }}",
						type: "POST"
					},
					columns: [{
							data: "phone",
							name: "phone"
						},
						{
							data: "email",
							name: "email"
						},
						{
							data: "name",
							name: "name"
						},
						{
							data: "address",
							name: "address"
						},
						{
							data: "id",
							name: "actions",
							orderable: false,
							searchable: false,
							render: function(id) {
								return `
									<div class="table-data-feature pull-left" style="display: flex; gap: 2px;">
										<button class="btn btn-sm btn-success js-show-stakeholder" data-id="${id}">
											<i class="zmdi zmdi-eye"></i>
										</button>
										<button class="btn btn-sm btn-primary js-edit-stakeholder" data-id="${id}">
											<i class="zmdi zmdi-edit"></i>
										</button>
										<button class="btn btn-sm btn-danger js-delete-stakeholder" data-id="${id}">
											<i class="zmdi zmdi-delete"></i>
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

			$(document).on("click", ".js-show-stakeholder", function() {
				const id = $(this).data("id");
				const stakeholderShowUrl = "{{ route('stakeholders.show', ['__ID__']) }}";
				window.location.href = stakeholderShowUrl.replace("__ID__", id);
			});

			$(document).on("click", ".js-edit-stakeholder", function() {
				const id = $(this).data("id");
				const stakeholderEditUrl = "{{ route('stakeholders.edit', ['__ID__']) }}";
				let url = stakeholderEditUrl.replace("__ID__", id);
				edit(url, 'NRML-BTN', 'stakeholderContainer', null, stkAuditData)
			});

			$(document).on("click", ".js-delete-stakeholder", function() {
				const id = $(this).data("id");
				// confirm + delete, then stakeholdersTable.ajax.reload(null, false);
			});

		});
	</script>
@endsection
