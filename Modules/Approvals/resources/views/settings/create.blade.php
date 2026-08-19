@extends('layouts.master')
@section('body-contents')
	<style>
		/* Small additions only where the theme has no equivalent yet — everything
																									else below reuses main.css / theme.css classes as-is. */
		body {
			background: #e5e5e5;
			padding: 30px 15px;
		}

		.flow-title-input {
			border: none;
			background: transparent;
			font-weight: 700;
			font-size: 20px;
			color: #333;
			padding: 2px 6px;
			border-radius: 4px;
			width: auto;
		}

		.flow-title-input:hover,
		.flow-title-input:focus {
			background: #f2f2f2;
			outline: none;
		}

		.badge-version {
			font-size: 11px;
			font-weight: 600;
		}

		.escalation-box {
			background: #fff8e6;
			border: 1px solid #f0dfa8;
			border-radius: 6px;
			padding: 10px 14px;
			margin-top: 14px;
			font-size: 13.5px;
			color: #8a6416;
		}

		.approver-row {
			display: flex;
			align-items: center;
			gap: 12px;
			border: 1px solid #eee;
			border-radius: 6px;
			padding: 8px 10px;
			margin-bottom: 6px;
			background: #fff;
		}

		.approver-row.inactive {
			opacity: .5;
		}

		.approver-role {
			font-size: 11.5px;
			color: #999;
		}

		.cap-input {
			width: 130px;
			display: inline-block;
		}

		.add-dashed {
			border: 1.5px dashed #c7c7c7;
			border-radius: 20px;
			color: #888;
			background: #fff;
			font-size: 12.5px;
			padding: 5px 12px;
			cursor: pointer;
		}

		.add-dashed:hover {
			border-color: #51797d;
			color: #51797d;
		}

		.section-label {
			font-size: 12.5px;
			font-weight: 600;
			color: #555;
			text-transform: uppercase;
			margin-bottom: 8px;
		}

		.hint-text {
			font-weight: 400;
			text-transform: none;
			color: #999;
		}

		.stage-item {
			border: 1px solid #eee;
			border-radius: 8px;
			padding: 14px 16px 6px;
			margin-bottom: 16px;
			background: #fcfdfd;
		}

		.stage-badge {
			font-size: 11px;
			font-weight: 700;
			vertical-align: middle;
		}

		.js-remove-stage {
			font-size: 12.5px;
		}

		.invalid-feedback.d-block {
			font-size: 12px;
		}
	</style>
	@php
		$url = route('approvals.index');
		$edit = isset($approvalFlow);

		// $approvalFlow arrives as a plain array from the downstream service —
		// see the payload contract: id, name, active, targets[], levels[] (each
		// with approvers[]). No Eloquent access anywhere below.

		$levels = $edit ? $approvalFlow['levels'] ?? [] : [];
		if (empty($levels)) {
		    $levels = [[]]; // always render one blank stage
		}

		$selectedTypeIds = array_column($approvalFlow['targets'] ?? [], 'approval_type_id');

		// Small local helper for approver avatar initials — "John Mwakalinga — Line Manager" -> "JM"
		$initialsOf = function ($label) {
		    $namePart = explode('—', (string) $label)[0];
		    $parts = array_filter(explode(' ', trim($namePart)));
		    $parts = array_slice($parts, 0, 2);
		    return strtoupper(implode('', array_map(fn($w) => $w[0], $parts)));
		};
	@endphp
	<!-- BREADCRUMB -->
	<section>
		<div class="row">
			<div class="col-lg-12" style="padding-right: 0; padding-left: 0;">
				<div class="au-breadcrumb-content">
					<div class="au-breadcrumb-left">
						<nav class="breadcrumb" style="margin-bottom: .5rem">
							<a class="breadcrumb-item" href="{{ route('home-page') }}"><i class="fa fa-home"></i>&nbsp;Home</a>
							<a class="breadcrumb-item" href="{{ $url }}">&nbsp;Approvals</a>
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
				<form name="approval-flow-form" id="approvalFlowForm" action="{{ route('approvals.settings.store') }}" method="POST" enctype="multipart/form-data">
					@csrf
					@if ($edit)
						<input type="hidden" name="id" value="{{ $approvalFlow['id'] }}">
					@endif
					<div class="card-header">
						<div class="row">
							<h4 class="form-title" id="mainFormLabel"><span>{{ $title }}</span></h4>
							<a href="{{ $url }}" class="close" aria-label="Close">
								<span aria-hidden="true" style="color: red">&times;</span>
							</a>
						</div>
					</div>
					<div class="card-body" style="padding: 0.25rem;">
						<div class="col-lg-10">
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

							<div class="col-lg-12 d-flex justify-content-between align-items-start">
								<div class="col-lg-4">
									<div class="form-group" style="margin: 0;">
										<label for="flow_name" class="control-label mb-1">Name</label>
										<input type="text" id="flow_name" name="name" placeholder="i.e Purchases Approval" class="form-control" value="{{ old('name', $approvalFlow['name'] ?? '') }}" required>
									</div>
								</div>
								<div class="d-flex align-items-center">
									<div class="row">
										<label class="switch switch-3d switch-primary mr-3">
											<input type="checkbox" name="active" value="1" class="switch-input" id="flow_active" {{ old('active', $approvalFlow['active'] ?? true) ? 'checked' : '' }}>
											<span class="switch-label"></span>
											<span class="switch-handle"></span>
										</label>
										&nbsp;
										<span>Active</span>
									</div>
								</div>
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<label class="section-label">
										Applies to <span class="hint-text">— which request types use this flow</span>
									</label>

									<select class="my-custom-multiple-select" name="approval_type_ids[]" multiple="multiple" data-placeholder="Select Scope" data-placeholder-more="Select Another Scope">
										@foreach ($approvalTypes as $type)
											{{-- value is the UUID, NOT a positional integer --}}
											<option value="{{ $type['id'] }}" @selected(in_array($type['id'], $selectedTypeIds))>
												{{ $type['name'] }}@if (!empty($type['service']))
													· {{ $type['service'] }}
												@endif
											</option>
										@endforeach
									</select>

									@if (empty($approvalTypes))
										<small class="text-muted d-block mt-1">
											No approval types are available for your licensed modules yet.
										</small>
									@endif
								</div>
							</div>

							<hr class="my-4">

							<div id="stages-container">
								@foreach ($levels as $i => $level)
									@php
										$level = $level ?: [];
										$quorum = old("levels.$i.quorum", $level['quorum'] ?? 1);
										$maxAmount = old("levels.$i.max_amount", $level['max_amount'] ?? null);
										$noUpperLimit = old("levels.$i.no_upper_limit", is_null($maxAmount));
										$mode = old("levels.$i.mode", $level['mode'] ?? 'sequential');
										$escalationTargetId = old("levels.$i.escalation_level_id", $level['escalation_level_id'] ?? null);
										$approvers = $level['approvers'] ?? [];
									@endphp
									<div class="stage-item" data-stage-index="{{ $i }}">
										<div class="top-campaign" style="padding-left: 10px; padding-right: 10px">
											<div class="d-flex justify-content-between align-items-center mb-1">
												<label for="level_name_{{ $i }}" class="control-label mb-0">
													Role <span class="stage-badge badge badge-light">Stage {{ $i + 1 }}</span>
												</label>
												<a href="#" class="text-danger js-remove-stage" style="{{ count($levels) > 1 ? '' : 'display:none;' }}">
													<i class="fas fa-trash-alt"></i>&nbsp;Remove stage
												</a>
											</div>

											@if (!empty($level['id']))
												<input type="hidden" name="levels[{{ $i }}][id]" value="{{ $level['id'] }}" class="js-level-id">
											@endif

											<input type="text" id="level_name_{{ $i }}" name="levels[{{ $i }}][name]" placeholder="i.e Production Manager" class="form-control js-level-name" value="{{ old("levels.$i.name", $level['name'] ?? '') }}">

											<div class="row mb-2 mt-2">
												<div class="col-md-3 custom-control custom-switch">
													<div class="row">
														<label class="switch switch-3d switch-primary mr-3">
															<input type="checkbox" name="levels[{{ $i }}][active]" value="1" class="switch-input js-stage-active" id="stage_active_{{ $i }}" {{ old("levels.$i.active", $level['active'] ?? true) ? 'checked' : '' }}>
															<span class="switch-label"></span>
															<span class="switch-handle"></span>
														</label>
														&nbsp;
														<span>Stage active</span>
													</div>
												</div>
												<div class="col-md-6 custom-control custom-switch js-required-wrap">
													<div class="row">
														<label class="switch switch-3d switch-primary mr-3">
															<input type="checkbox" name="levels[{{ $i }}][is_mandatory]" value="1" class="switch-input js-required" id="stage_required_{{ $i }}" {{ old("levels.$i.is_mandatory", $level['is_mandatory'] ?? true) ? 'checked' : '' }}>
															<span class="switch-label"></span>
															<span class="switch-handle"></span>
														</label>
														&nbsp;
														<span>Required to proceed</span>
														<span class="hint-text">&nbsp;— blocks the next level until this one clears quorum</span>
													</div>
												</div>
											</div>

											<div class="col-lg-12">
												<h8 class="panel-title">
													<a data-toggle="collapse" href="#stageCollapse_{{ $i }}" aria-expanded="false" aria-controls="stageCollapse_{{ $i }}">
														Advanced Settings
													</a>
												</h8>
												<div id="stageCollapse_{{ $i }}" class="panel-collapse collapse" role="tabpanel">
													<div class="panel-body">
														<div class="form-row align-items-center mt-2">
															<div class="col-auto text-muted" style="font-size:13.5px;">Applies when amount is between</div>
															<div class="col-auto">
																<input type="text" name="levels[{{ $i }}][min_amount]" class="form-control form-control-sm js-amount" value="{{ old("levels.$i.min_amount", $level['min_amount'] ?? 0) }}" style="width:110px;">
															</div>
															<div class="col-auto text-muted" style="font-size:13.5px;">and</div>
															<div class="col-auto">
																<input type="text" name="levels[{{ $i }}][max_amount]" class="form-control form-control-sm js-amount js-max-amount" value="{{ $noUpperLimit ? '' : $maxAmount }}" style="width:110px;" {{ $noUpperLimit ? 'disabled' : '' }}>
															</div>
															<div class="col-auto text-muted" style="font-size:13.5px;">TZS</div>
															<div class="col-auto">
																<div class="custom-control custom-checkbox">
																	<input type="checkbox" name="levels[{{ $i }}][no_upper_limit]" value="1" class="custom-control-input js-no-limit" id="nolimit_{{ $i }}" {{ $noUpperLimit ? 'checked' : '' }}>
																	<label class="custom-control-label text-muted" for="nolimit_{{ $i }}" style="font-size:13px;">no upper limit</label>
																</div>
															</div>
														</div>

														<div class="form-row align-items-center mt-2">
															<div class="col-auto text-muted" style="font-size:13.5px;">Needs</div>
															<div class="col-auto">
																<input type="number" min="1" name="levels[{{ $i }}][quorum]" class="form-control form-control-sm js-quorum" value="{{ $quorum }}" style="width:56px;">
															</div>
															<div class="col-auto text-muted" style="font-size:13.5px;">approval(s), collected</div>
															<div class="col-auto">
																<select name="levels[{{ $i }}][mode]" class="form-control form-control-sm">
																	<option value="sequential" @selected($mode === 'sequential')>in sequence</option>
																	<option value="parallel" @selected($mode === 'parallel')>at the same time</option>
																</select>
															</div>
														</div>

														<div class="escalation-box">
															<i class="fas fa-clock mr-1"></i>
															If not actioned within
															<input type="number" min="0" name="levels[{{ $i }}][sla_hours]" class="form-control form-control-sm d-inline-block" value="{{ old("levels.$i.sla_hours", $level['sla_hours'] ?? null) }}" style="width:56px;">
															hours, escalate to
															<select name="levels[{{ $i }}][escalation_level_id]" class="form-control form-control-sm d-inline-block js-escalation-select" style="width:200px;">
																<option value="" @selected(empty($escalationTargetId))>None</option>
																@foreach ($levels as $j => $otherLevel)
																	@continue($j <= $i || empty($otherLevel['id']))
																	<option value="{{ $otherLevel['id'] }}" @selected($escalationTargetId == $otherLevel['id'])>
																		{{ $otherLevel['name'] ?: 'Stage ' . ($j + 1) }}
																	</option>
																@endforeach
															</select>
														</div>
													</div>
												</div>
											</div>

											<div class="mt-3">
												<label class="section-label mb-2">
													Approvers <span class="hint-text">— needs <span class="js-quorum-copy">{{ $quorum }}</span> of the active people below</span>
												</label>

												<div class="approver-list">
													@forelse ($approvers as $j => $approver)
														@php $label = $approver['user_label'] ?? ''; @endphp
														<div class="approver-row" data-approver-index="{{ $j }}">
															<div class="avatar-x w-32 circle bg-flat-color-1 text-white">{{ $initialsOf($label) ?: '?' }}</div>
															<div class="flex-grow-1">
																@if (!empty($approver['id']))
																	<input type="hidden" name="levels[{{ $i }}][approvers][{{ $j }}][id]" value="{{ $approver['id'] }}">
																@endif
																<select name="levels[{{ $i }}][approvers][{{ $j }}][user_id]" class="js-approver-select form-control form-control-sm" style="width:100%;">
																	<option value="">&nbsp;</option>
																	@foreach ($users as $u)
																		<option value="{{ $u['value'] }}" @selected($u['value'] == ($approver['user_id'] ?? null))>{{ $u['label'] }}</option>
																	@endforeach
																</select>
															</div>
															<div class="text-muted" style="font-size:12.5px;">Can approve up to</div>
															<input type="text" name="levels[{{ $i }}][approvers][{{ $j }}][amount_cap]" class="form-control form-control-sm cap-input js-amount" value="{{ $approver['amount_cap'] ?? '' }}">
															<div class="custom-control custom-switch">
																<input type="checkbox" name="levels[{{ $i }}][approvers][{{ $j }}][active]" value="1" class="custom-control-input" id="ap_{{ $i }}_{{ $j }}" {{ $approver['active'] ?? true ? 'checked' : '' }}>
																<label class="custom-control-label" for="ap_{{ $i }}_{{ $j }}"></label>
															</div>
															<a href="#" class="text-secondary js-remove-approver"><i class="fas fa-trash-alt"></i></a>
														</div>
													@empty
														{{-- no approvers yet — added with the button below --}}
													@endforelse
												</div>

												<span class="add-dashed js-add-approver"><i class="fas fa-plus mr-1"></i>Add approver</span>
											</div>
										</div>
									</div>
								@endforeach
							</div>

							<div class="row col-md-12">
								<button type="button" class="btn btn-link js-add-stage" id="add_stage_row" style="padding-left: 0">
									<strong><i class="fa fa-plus"></i> Add Stage</strong>
								</button>
							</div>
						</div><!-- /.col-lg-12 -->
					</div><!-- /.card-body -->
					<div class="col-lg-12">
						<div class="form-actions form-group">
							<button type="submit" class="btn btn-primary btn-sm float-right" id="submitApprovalFlow">
								<i class="fa fa-save"></i>&nbsp;&nbsp;Submit
							</button>
							<a href="{{ $url }}" type="button" class="btn btn-cancel btn-sm"><i class="fa fa-times"></i>&nbsp;&nbsp;Cancel</a>
						</div>
					</div>
				</form>

			</div>
		</div>
	</div>

	{{-- ===== blank templates used by jQuery when adding a stage / approver ===== --}}
	{{-- Anything cloned from these is always brand-new: no [id] field, so the
	     backend's coalesced sync creates a fresh row rather than updating one. --}}

	<template id="stage-template">
		<div class="stage-item" data-stage-index="__STAGE__">
			<div class="top-campaign" style="padding-left: 10px; padding-right: 10px">
				<div class="d-flex justify-content-between align-items-center mb-1">
					<label for="level_name___STAGE__" class="control-label mb-0">
						Role <span class="stage-badge badge badge-light">Stage __STAGE__</span>
					</label>
					<a href="#" class="text-danger js-remove-stage">
						<i class="fas fa-trash-alt"></i>&nbsp;Remove stage
					</a>
				</div>
				<input type="text" id="level_name___STAGE__" name="levels[__STAGE__][name]" placeholder="i.e Production Manager" class="form-control js-level-name">

				<div class="row mb-2 mt-2">
					<div class="col-md-3 custom-control custom-switch">
						<div class="row">
							<label class="switch switch-3d switch-primary mr-3">
								<input type="checkbox" name="levels[__STAGE__][active]" value="1" class="switch-input js-stage-active" id="stage_active___STAGE__" checked>
								<span class="switch-label"></span>
								<span class="switch-handle"></span>
							</label>
							&nbsp;
							<span>Stage active</span>
						</div>
					</div>
					<div class="col-md-6 custom-control custom-switch js-required-wrap">
						<div class="row">
							<label class="switch switch-3d switch-primary mr-3">
								<input type="checkbox" name="levels[__STAGE__][is_mandatory]" value="1" class="switch-input js-required" id="stage_required___STAGE__" checked>
								<span class="switch-label"></span>
								<span class="switch-handle"></span>
							</label>
							&nbsp;
							<span>Required to proceed</span>
							<span class="hint-text">&nbsp;— blocks the next level until this one clears quorum</span>
						</div>
					</div>
				</div>

				<div class="col-lg-12">
					<h8 class="panel-title">
						<a data-toggle="collapse" href="#stageCollapse___STAGE__" aria-expanded="true" aria-controls="stageCollapse___STAGE__">
							Advanced Settings
						</a>
					</h8>
					<div id="stageCollapse___STAGE__" class="panel-collapse collapse" role="tabpanel">
						<div class="panel-body">
							<div class="form-row align-items-center mt-2">
								<div class="col-auto text-muted" style="font-size:13.5px;">Applies when amount is between</div>
								<div class="col-auto">
									<input type="text" name="levels[__STAGE__][min_amount]" class="form-control form-control-sm js-amount" value="0" style="width:110px;">
								</div>
								<div class="col-auto text-muted" style="font-size:13.5px;">and</div>
								<div class="col-auto">
									<input type="text" name="levels[__STAGE__][max_amount]" class="form-control form-control-sm js-amount js-max-amount" value="" style="width:110px;">
								</div>
								<div class="col-auto text-muted" style="font-size:13.5px;">TZS</div>
								<div class="col-auto">
									<div class="custom-control custom-checkbox">
										<input type="checkbox" name="levels[__STAGE__][no_upper_limit]" value="1" class="custom-control-input js-no-limit" id="nolimit___STAGE__">
										<label class="custom-control-label text-muted" for="nolimit___STAGE__" style="font-size:13px;">no upper limit</label>
									</div>
								</div>
							</div>

							<div class="form-row align-items-center mt-2">
								<div class="col-auto text-muted" style="font-size:13.5px;">Needs</div>
								<div class="col-auto">
									<input type="number" min="1" name="levels[__STAGE__][quorum]" class="form-control form-control-sm js-quorum" value="1" style="width:56px;">
								</div>
								<div class="col-auto text-muted" style="font-size:13.5px;">approval(s), collected</div>
								<div class="col-auto">
									<select name="levels[__STAGE__][mode]" class="form-control form-control-sm">
										<option value="sequential" selected>in sequence</option>
										<option value="parallel">at the same time</option>
									</select>
								</div>
							</div>

							<div class="escalation-box">
								<i class="fas fa-clock mr-1"></i>
								If not actioned within
								<input type="number" min="0" name="levels[__STAGE__][sla_hours]" class="form-control form-control-sm d-inline-block" value="" style="width:56px;">
								hours, escalate to
								<select name="levels[__STAGE__][escalation_level_id]" class="form-control form-control-sm d-inline-block js-escalation-select" style="width:200px;">
									<option value="" selected>None</option>
									{{-- options rebuilt by JS (rebuildEscalationOptions) once this stage is inserted --}}
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class="mt-3">
					<label class="section-label mb-2">
						Approvers <span class="hint-text">— needs <span class="js-quorum-copy">1</span> of the active people below</span>
					</label>

					<div class="approver-list"></div>

					<span class="add-dashed js-add-approver"><i class="fas fa-plus mr-1"></i>Add approver</span>
				</div>
			</div>
		</div>
	</template>

	<template id="approver-template">
		<div class="approver-row" data-approver-index="__APPROVER__">
			<div class="avatar-x w-32 circle bg-flat-color-1 text-white">?</div>
			<div class="flex-grow-1">
				<select name="levels[__STAGE__][approvers][__APPROVER__][user_id]" class="js-approver-select form-control form-control-sm" style="width:100%;">
					<option value="">&nbsp;</option>
					@foreach ($users as $u)
						<option value="{{ $u['value'] }}">{{ $u['label'] }}</option>
					@endforeach
				</select>
			</div>
			<div class="text-muted" style="font-size:12.5px;">Can approve up to</div>
			<input type="text" name="levels[__STAGE__][approvers][__APPROVER__][amount_cap]" class="form-control form-control-sm cap-input js-amount" value="">
			<div class="custom-control custom-switch">
				<input type="checkbox" name="levels[__STAGE__][approvers][__APPROVER__][active]" value="1" class="custom-control-input" id="ap___STAGE_____APPROVER__" checked>
				<label class="custom-control-label" for="ap___STAGE_____APPROVER__"></label>
			</div>
			<a href="#" class="text-secondary js-remove-approver"><i class="fas fa-trash-alt"></i></a>
		</div>
	</template>
@endsection
@section('scripts')
	<script>
		$(document).ready(function() {
			divDismissible();

			var $form = $('form[name="approval-flow-form"]');
			var $submitBtn = $('#submitApprovalFlow');
			var $stagesContainer = $('#stages-container');

			// ---------- helpers ----------

			function initSelect2($scope) {
				$scope.find('.js-approver-select').each(function() {
					if (!$(this).hasClass('select2-hidden-accessible')) {
						$(this).select2({
							width: '100%',
							dropdownParent: $(this).closest('.approver-row')
						});
					}
				});
			}

			function stripAmount(val) {
				if (!val) return '';
				return val.toString().replace(/[^\d.]/g, '');
			}

			function formatAmount($input) {
				var raw = stripAmount($input.val());
				if (raw === '') {
					$input.val('');
					return;
				}
				$input.val(Number(raw).toLocaleString('en-US'));
			}

			// dot-notation validation key -> bracket-notation input name
			// e.g. "levels.0.approvers.1.amount_cap" -> "levels[0][approvers][1][amount_cap]"
			function dotToBracketName(key) {
				var parts = key.split('.');
				var name = parts[0];
				for (var i = 1; i < parts.length; i++) {
					name += '[' + parts[i] + ']';
				}
				return name;
			}

			function reindexApprovers($stage) {
				var stageIdx = $stage.attr('data-stage-index');
				$stage.find('.approver-row').each(function(j) {
					var $row = $(this);
					$row.attr('data-approver-index', j);

					$row.find('[name]').each(function() {
						var newName = $(this).attr('name')
							.replace(/levels\[\d+\]\[approvers\]\[\d+\]/, 'levels[' + stageIdx + '][approvers][' + j + ']');
						$(this).attr('name', newName);
					});
					$row.find('[id]').each(function() {
						var base = $(this).attr('id').replace(/_\d+_\d+$/, '');
						$(this).attr('id', base + '_' + stageIdx + '_' + j);
					});
					$row.find('label[for]').each(function() {
						var base = $(this).attr('for').replace(/_\d+_\d+$/, '');
						$(this).attr('for', base + '_' + stageIdx + '_' + j);
					});
				});
			}

			function reindexStages() {
				var $stages = $stagesContainer.find('.stage-item');

				$stages.each(function(i) {
					var $stage = $(this);
					$stage.attr('data-stage-index', i);
					$stage.find('.stage-badge').first().text('Stage ' + (i + 1));
					$stage.find('.js-remove-stage').toggle($stages.length > 1);

					$stage.find('[name]').each(function() {
						var newName = $(this).attr('name').replace(/^levels\[\d+\]/, 'levels[' + i + ']');
						$(this).attr('name', newName);
					});
					$stage.find('[id]').each(function() {
						var base = $(this).attr('id').replace(/_\d+$/, '');
						$(this).attr('id', base + '_' + i);
					});
					$stage.find('label[for]').each(function() {
						var base = $(this).attr('for').replace(/_\d+$/, '');
						$(this).attr('for', base + '_' + i);
					});
					$stage.find('[href^="#stageCollapse"]').attr('href', '#stageCollapse_' + i);
					$stage.find('[aria-controls^="stageCollapse"]').attr('aria-controls', 'stageCollapse_' + i);

					reindexApprovers($stage);
				});

				rebuildEscalationOptions();
			}

			// Escalation targets are only ever subsequent, ALREADY-SAVED levels in this
			// same flow — a stage added in this same edit has no id yet, so it simply
			// can't be picked as a target until it exists (saved, then reopened).
			// Rebuilt any time stages are added/removed/reordered/renamed so the list
			// and labels never go stale.
			function rebuildEscalationOptions() {
				var $stages = $stagesContainer.find('.stage-item');

				var stageInfo = $stages.map(function() {
					var $s = $(this);
					var $idField = $s.find('.js-level-id');
					return {
						id: $idField.length ? $idField.val() : null,
						name: ($s.find('.js-level-name').val() || '').trim(),
						index: parseInt($s.attr('data-stage-index'), 10)
					};
				}).get();

				$stages.each(function() {
					var $stage = $(this);
					var myIndex = parseInt($stage.attr('data-stage-index'), 10);
					var $select = $stage.find('.js-escalation-select');
					var currentVal = $select.val();

					$select.empty().append('<option value="">None</option>');

					stageInfo.forEach(function(s) {
						if (s.index <= myIndex || !s.id) return; // subsequent AND already saved
						var label = s.name || ('Stage ' + (s.index + 1));
						$('<option></option>').attr('value', s.id).text(label).appendTo($select);
					});

					// keep the previous selection only if that target still qualifies
					if (currentVal && $select.find('option[value="' + currentVal + '"]').length) {
						$select.val(currentVal);
					} else {
						$select.val('');
					}
				});
			}

			// ---------- events ----------

			// no-upper-limit: disable max_amount so it's simply absent from the payload
			$(document).on('change', '.js-no-limit', function() {
				var $max = $(this).closest('.form-row').find('.js-max-amount');
				$max.prop('disabled', this.checked);
				if (this.checked) $max.val('');
			});

			// A stage can't be inactive and required to proceed at the same time —
			// keep the two toggles in sync as the user clicks, no locking needed.
			// Only the state at submit time matters.
			$(document).on('change', '.js-stage-active', function() {
				if (!this.checked) {
					$(this).closest('.stage-item').find('.js-required').prop('checked', false);
				}
			});

			$(document).on('change', '.js-required', function() {
				if (this.checked) {
					$(this).closest('.stage-item').find('.js-stage-active').prop('checked', true);
				}
			});

			$(document).on('blur', '.js-amount', function() {
				formatAmount($(this));
			});

			$(document).on('input', '.js-quorum', function() {
				$(this).closest('.top-campaign').find('.js-quorum-copy').text($(this).val() || '0');
			});

			$(document).on('input', '.js-level-name', function() {
				rebuildEscalationOptions();
			});

			$(document).on('click', '.js-add-approver', function(e) {
				e.preventDefault();
				var $stage = $(this).closest('.stage-item');
				var $list = $stage.find('.approver-list');
				var stageIdx = $stage.attr('data-stage-index');
				var approverIdx = $list.find('.approver-row').length;

				var html = $('#approver-template').html()
					.split('__STAGE__').join(stageIdx)
					.split('__APPROVER__').join(approverIdx);

				var $row = $(html);
				$list.append($row);
				initSelect2($row);
			});

			$(document).on('click', '.js-remove-approver', function(e) {
				e.preventDefault();
				var $stage = $(this).closest('.stage-item');
				$(this).closest('.approver-row').remove();
				reindexApprovers($stage);
			});

			$(document).on('click', '.js-add-stage', function(e) {
				e.preventDefault();
				var newIndex = $stagesContainer.find('.stage-item').length;

				var html = $('#stage-template').html().split('__STAGE__').join(newIndex);
				var $stage = $(html);

				$stagesContainer.append($stage);
				initSelect2($stage);
				reindexStages();
			});

			$(document).on('click', '.js-remove-stage', function(e) {
				e.preventDefault();
				if ($stagesContainer.find('.stage-item').length <= 1) return;

				var $stage = $(this).closest('.stage-item');

				Swal.fire({
					title: 'Remove this stage?',
					text: 'Existing approvals recorded on it are unaffected, but the stage itself will be gone once you submit.',
					icon: 'warning',
					showCancelButton: true,
					reverseButtons: true,
					confirmButtonText: 'Remove stage',
					cancelButtonText: 'Cancel',
					confirmButtonColor: '#c4453b'
				}).then(function(result) {
					if (!result.isConfirmed) return;
					$stage.remove();
					reindexStages();
				});
			});

			$form.on('submit', function(e) {
				e.preventDefault();

				$form.find('.js-amount:not(:disabled)').each(function() {
					$(this).val(stripAmount($(this).val()));
				});

				$form.find('.is-invalid').removeClass('is-invalid');
				$form.find('.invalid-feedback').remove();

				$submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>&nbsp;&nbsp;Saving...');

				var formData = new FormData(this);

				$.ajax({
					url: $form.attr('action'),
					method: 'POST', // @method('PUT') spoofs this to a real PUT server-side when editing
					data: formData,
					processData: false,
					contentType: false,
					headers: {
						'X-Requested-With': 'XMLHttpRequest'
					}
				}).done(function(res) {
					window.location.href = (res && res.redirect) ? res.redirect : "{{ $url }}";
				}).fail(function(xhr) {
					if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
						$.each(xhr.responseJSON.errors, function(key, messages) {
							var name = dotToBracketName(key);
							var $field = $form.find('[name="' + name + '"]');
							$field.addClass('is-invalid');
							$field.closest('.form-group, .form-row, .col-auto, .cap-input')
								.first()
								.append('<div class="invalid-feedback d-block">' + messages[0] + '</div>');
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Submission Failed',
							text: 'Something went wrong while saving. Please try again.',
						});
					}
				}).always(function() {
					$submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i>&nbsp;&nbsp;Submit');
				});
			});

			// select2 needs explicit init on every approver select already in the DOM
			// (server-rendered rows from edit mode included) — newly cloned rows are
			// initialized individually at the point they're added, above.
			initSelect2($form);
			reindexStages();
		});
	</script>
@endsection
