@extends('layouts.master')
@section('body-contents')
	@php
		$url = route('stakeholders.index');

		// The tab set for this container. Each tab is gated on its child LEAF key via
		// authCan() — a permission check, not a route check, because tabs render inline.
		// A child the user cannot read produces no tab AND no rendered pane, so an
		// unauthorised sub-view never executes.
		//
		// This map (key -> pane/label/icon/view) is the one container-specific bit; every
		// tabbed container index has its own. Everything else below is the standard shell.
		$tabs = collect([
		    ['key' => 'stakeholders', 'pane' => 'invoices', 'label' => 'Invoice', 'view' => 'stakeholders::invoices.index'],
		    ['key' => 'stakeholders', 'pane' => 'payments', 'label' => 'Payments', 'view' => 'stakeholders::payments.index'],
		    ['key' => 'stakeholders', 'pane' => 'orders', 'label' => 'Order History', 'view' => 'stakeholders::orders.index'],
		    ['key' => 'stakeholders', 'pane' => 'reports', 'label' => 'Reports', 'view' => 'stakeholders::reports.index'],
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
							<a class="breadcrumb-item" href="{{ $url }}">&nbsp;Stakeholders</a>
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

						<div class="row col-lg-12" style="margin: 0; padding: 0;">
							<div class="row col-lg-2" style="margin: 0; padding-left: 0; padding-right: 0">
								<div class="col-md-12">
									<div class="mt-3">

										@if ($stakeholder['kind'] == 'ORGANIZATION')
											<div class="mb-2">
												<div class="font-weight-bold">TIN</div>
												<div>{{ $stakeholder['business_details']['tin'] }}</div>
											</div>
											<div class="mb-2">
												<div class="font-weight-bold">VRN</div>
												<div>{{ $stakeholder['business_details']['vrn'] }}</div>
											</div>
										@else
											<div class="mb-2">
												<div class="font-weight-bold">Full Name</div>
												<div>{{ $stakeholder['personal_details']['first_name'].' '.$stakeholder['personal_details']['middle_name'].' '.$stakeholder['personal_details']['surname'] }}</div>
											</div>
										@endif
										<div class="mb-2">
											<div class="font-weight-bold">Address</div>
											<div>{!! nl2br(e($stakeholder['address'] ?? '')) !!}</div>
										</div>
										<div class="mb-2">
											<div class="font-weight-bold">Phone</div>
											<div>{{ $stakeholder['phone'] ?? '' }}</div>
										</div>
										@if (!empty($stakeholder['alt_phone']))
											<div class="mb-2">
												<div class="font-weight-bold">Alt Phone</div>
												<div>{{ $stakeholder['alt_phone'] }}</div>
											</div>
										@endif
										<div class="mb-2">
											<div class="font-weight-bold">Email</div>
											<div><a href="mailto:{{ $stakeholder['email'] ?? '' }}">{{ $stakeholder['email'] ?? '' }}</a></div>
										</div>
									</div>
								</div>
							</div>

							<div class="row col-lg-10" style="margin: 0; padding-left: 0; padding-right: 0">
								<div class="row col-lg-12" style="margin: 0; padding-left: 0; padding-right: 0">
									<div class="col-sm-12 col-lg-4">
										<div class="overview-item overview-item--c2">
											<div class="overview__inner">
												<div class="overview-box clearfix">
													<div class="icon">
														<i class="zmdi zmdi-check-square"></i>
													</div>
													<div class="text">
														<h3 style="color: #FFF;">{{ 'TZS ' . number_format(0) }}</h3>
														<span style="color: #FFF;">Paid</span>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-sm-12 col-lg-4">
										<div class="overview-item overview-item--c1">
											<div class="overview__inner">
												<div class="overview-box clearfix">
													<div class="icon">
														<i class="zmdi zmdi-calendar-note"></i>
													</div>
													<div class="text">
														<h3 style="color: #FFF;">{{ 'TZS ' . number_format(0) }}</h3>
														<span style="color: #FFF;">Open Invoices</span>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-sm-12 col-lg-4">
										<div class="overview-item overview-item--c3">
											<div class="overview__inner">
												<div class="overview-box clearfix">
													<div class="icon">
														<i class="zmdi zmdi-money"></i>
													</div>
													<div class="text">
														<h3 style="color: #FFF;">{{ 'TZS ' . number_format(0) }}</h3>
														<span style="color: #FFF;">Overdue Invoices</span>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="row col-lg-12" style="margin: 0; padding-left: 0; padding-right: 0">
									@if (!$tabs->isEmpty())
										<div class="col-md-12 mb-3" style="padding-left: 0">
											<ul class="nav nav-tabs" id="stkhldrProfileNav" role="tablist">
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
											<div class="tab-content" id="stkhldrTabContent">
												@foreach ($tabs as $tab)
													<div class="tab-pane fade @if ($loop->first) show active @endif" style="min-height: 450px; overflow-y: auto;" id="{{ $tab['pane'] }}" role="tabpanel" aria-labelledby="{{ $tab['pane'] }}-tab">
														{{-- Only authorised panes reach this loop, so the sub-view never
                                                            renders for a user who cannot read it. --}}
														@include($tab['view'])
													</div>
												@endforeach
											</div>
										</div>
									@endif
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

			initTabPersistence($('#stkhldrProfileNav'), "stkhldrProfileActiveTab");

		});
	</script>
@endsection
