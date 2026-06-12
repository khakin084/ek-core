@extends('layouts.master')
@section('body-contents')
	@php
		$edit = isset($item);
		$params_arr = $edit ? ['variety_id' => ':variety_id', 'item_id' => ':item_id'] : ['variety_id' => ':variety_id'];
	@endphp

	<!-- BREADCRUMB -->
	<section>
		<div class="row">
			<div class="col-lg-12" style="padding-right: 0; padding-left: 0;">
				<div class="au-breadcrumb-content">
					<div class="au-breadcrumb-left">
						<nav class="breadcrumb" style="margin-bottom: .5rem">
							<a class="breadcrumb-item" href="{{ route('home-page') }}"><i class="fa fa-home"></i>&nbsp;Home</a>
							<a class="breadcrumb-item" href="{{ route('item-master-index') }}">&nbsp;Item Master</a>
							<span class="breadcrumb-item active">Item Form</span>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- END BREADCRUMB -->

	<div class="row">
		<div class="col-lg-12">
			<form class="form-horizontal" id="items-form" enctype="multipart/form-data" data-variations-url="{{ route('get-item-variations', $params_arr) }}" data-components-url="{{ route('get-item-components', [':item_id']) }}" data-track-records-url="{{ route('get-item-track-records', [':item_id']) }}"
				data-uom-url="{{ route('get-uom', [':selected_val']) }}" data-store-url="{{ route('item-store') }}" enctype="multipart/form-data">
				<div class="card">
					<div class="card-header">
						<div class="row">
							<h4 class="form-title" id="formLabel">Item Form</h4>
							<input type="hidden" name="id" value="{{ $edit ? encodeUrlx($item->id) : encodeUrlx(0) }}">
							<a href="{{ route('item-master-index') }}" class="close" aria-label="Close">
								<span aria-hidden="true" style="color: red">&times;</span>
							</a>
						</div>
					</div>
					<div class="card-body" style="padding: 0.25rem;">
						<div class="col-lg-12">
							<div class="row">
								{{-- Flash messages --}}
								@include('partials._flash')

								@if (!$edit)
									<div class="div-dismissible" style="height: 50px; padding-bottom: 20px; margin-left: auto; margin-right: auto;">
										<div class="sufee-alert alert with-close alert-warning alert-dismissible fade show" style="padding-right: 19rem">
											<span class="badge badge-pill badge-warning">Hint</span>
											To add item: first select variety, then fill all the necessary details.
											<button type="button" class="close" data-dismiss="alert" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
									</div>
								@endif

								<div class="col-lg-12" style="margin-bottom: 1rem">
									<div class="row">
										<div class="col-lg-3"></div>
										<div class="col-lg-6" style="margin-left: 0; margin-right: 0;">
											<div class="card-body card-block" style="padding: 0.25rem;">
												<div class="form-group" style="margin-bottom: 0.2rem;">
													<label for="nf-email" class="form-control-label">Variety</label>
													<select name="item_variety" id="item_variety" class="form-control-sm form-control selectXs">
														<option value="">&nbsp;</option>
														@foreach ($varietyOptions as $index => $option)
															<option {{ $edit && $item['variety']['id'] == $option['value'] ? 'selected' : '' }} value="{{ encodeUrlx($option['value']) }}">{{ $option['label'] }}</option>
														@endforeach
													</select>
													<span class="help-block">Please select a variety to add item</span>
												</div>
											</div>
										</div>
										<div class="col-lg-3"></div>
									</div>

									<div class="col-lg-12" style="margin-top: 1rem; margin-bottom: 1rem" id="form-container">
									</div>

									<div class="col-lg-12" style="margin-top: 1rem; margin-bottom: 1rem">
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-12">
												<textarea name="descriptions" rows="3" placeholder="Any thing to take note...?" class="form-control">{{ $edit ? $item->descriptions : '' }}</textarea>
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="form-actions form-group">
										<button type="button" class="btn btn-primary btn-sm submit_item float-right" id="submit_item"><i class="fa fa-save"></i>&nbsp;&nbsp;Submit</button>
										<a href="{{ route('item-master-index') }}" type="button" class="btn btn-cancel btn-sm"><i class="fa fa-times"></i>&nbsp;&nbsp;Cancel</a>
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
		(function($) {
			'use strict';

			$(document).ready(function() {
				const $form = $('#items-form');
				if (!$form.length) return;

				// --- Helper: Replace placeholders in a URL string ---
				function buildUrl(baseUrl, replacements) {
					let url = baseUrl;
					for (const [key, value] of Object.entries(replacements)) {
						url = url.replace(`:${key}`, value);
					}
					return url;
				}

				// --- Helper: Get UOM for a selected particular ---
				function fetchUom($select, selectedVal) {
					if (!selectedVal) return;
					const $td = $select.closest('td');
					const url = buildUrl($form.data('uom-url'), {
						selected_val: selectedVal
					});
					$.ajax({
						url: url,
						type: 'GET',
						beforeSend: startSpinner,
						success: function(res) {
							$td.find('input[name="unit_id"]').val(res);
						},
						complete: stopSpinner
					});
				}

				// --- Helper: Initialize a component row (items or costs) ---
				function initComponentRow($row, isCost = false) {
					$row.find('.selectXis').select2({
						width: '100%'
					});

					if (!isCost) {
						// For item rows: fetch unit & last price
						if (typeof retrieveComponentRowUnitNLastPrice === 'function') {
							retrieveComponentRowUnitNLastPrice($row, 'COST');
						}
						calculateComponentRowAmount($row, 2);
					}

					// Remove button
					const $removeBtn = $row.find('.remove_row');
					if ($removeBtn.length) {
						$removeBtn.off('click').on('click', function() {
							$(this).closest('tr').remove();
							if (!isCost) calculateComponentsGrandTotalAmount($form, 2);
						});
					}
				}

				// --- Load components when "Is Composite" is checked ---
				function loadItemComponents() {
					const itemId = $form.find('input[name="id"]').val();
					if (!itemId) return;

					const url = buildUrl($form.data('components-url'), {
						item_id: itemId
					});
					$.ajax({
						url: url,
						type: 'GET',
						dataType: 'json',
						beforeSend: startSpinner,
						success: function(res) {
							const $container = $form.find('#componets-container');
							$container.show().html(res.components_table);

							// Existing rows
							$container.find('tbody tr').each(function() {
								initComponentRow($(this), false);
							});
							$container.find('tfoot .new_cost_row').each(function() {
								initComponentRow($(this), true);
							});

							// Add new item row
							$('.add_item_row').off('click').on('click', function() {
								const $tbody = $container.find('tbody');
								const $newRow = $container.find('.additional_items_row').clone().removeAttr('style').removeClass('additional_items_row').addClass('new_row');
								$tbody.append($newRow);
								initComponentRow($newRow, false);
								if (typeof initCustomScripts === 'function') initCustomScripts();
							});

							// Add new cost row
							$('.add_costs_row').off('click').on('click', function() {
								const $tfoot = $container.find('tfoot');
								$tfoot.find('.proc-costs-header, .proc-costs-total').show();
								const $newRow = $container.find('.additional_costs_row').clone().removeAttr('style').removeClass('additional_costs_row').addClass('new_cost_row');
								$tfoot.find('.proc-costs-header').after($newRow);
								initComponentRow($newRow, true);
								if (typeof initCustomScripts === 'function') initCustomScripts();
							});

							calculateComponentsGrandTotalAmount($form, 2);

							// Listen for changes in component rows
							$container.on('change keyup', 'select[name="item_ids[]"], input[name="rates[]"], input[name="quantities[]"]', function() {
								const $row = $(this).closest('tr');
								calculateComponentRowAmount($row, 2);
								calculateComponentsGrandTotalAmount($form, 2);
							});
						},
						complete: stopSpinner
					});
				}

				// --- Load track records when "Can Track Stock" is checked ---
				function loadItemTrackRecords() {
					const itemId = $form.find('input[name="id"]').val();
					if (!itemId) return;

					const url = buildUrl($form.data('track-records-url'), {
						item_id: itemId
					});
					$.ajax({
						url: url,
						type: 'GET',
						dataType: 'json',
						beforeSend: startSpinner,
						success: function(res) {
							const $container = $form.find('#track-record-ontainer');
							$container.show().html(res.track_stock_table);

							if (typeof select2Create === 'function') {
								select2Create(
									$container.find('select[name="entity_id"]'),
									'Supplier',
									'{{ route('stakeholders.create', [0]) }}', // could also use data-attribute
									'OTF-BTN',
									true
								);
							}
						},
						complete: stopSpinner
					});
				}

				// --- Main: load variations based on selected variety ---
				function loadVariations(varietyId) {
					if (!varietyId) return;

					const itemId = $form.find('input[name="id"]').val();
					const url = buildUrl($form.data('variations-url'), {
						variety_id: varietyId,
						item_id: itemId
					});

					$.ajax({
						url: url,
						type: 'GET',
						dataType: 'json',
						beforeSend: startSpinner,
						success: function(data) {
							$form.find('#form-container').html(data.variation_table);
							// Reinitialize dynamic elements inside the new content
							initDynamicContent();
						},
						complete: stopSpinner
					});
				}

				// --- Initialize all dynamic parts after content load ---
				function initDynamicContent() {
					const $container = $form.find('#form-container');

					// Dragtable for variations
					const $variationsTable = $container.find('#variations-table');
					if ($variationsTable.length && $.fn.dragtable) {
						$variationsTable.dragtable({
							dragHandle: '.some-handle',
							persistState: function(table) {
								const $table = $(table.el);
								const $parentForm = $table.closest('form');
								const varietyVal = $parentForm.find('select[name="item_variety"]').val();
								if (!varietyVal) return;

								$.ajax({
									url: '{{ route('rearrange-variations') }}',
									type: 'POST',
									data: $parentForm.serialize(),
									beforeSend: startSpinner,
									success: function(response) {
										$form.find('#form-container').html(response.variation_table);
										initDynamicContent();
									},
									error: onAjaxError,
									complete: stopSpinner
								});
							}
						});
					}

					// Unit conversion table
					const $convTable = $container.find('#unit-conversion-table');
					if ($convTable.length) {
						$convTable.find('tbody tr .remove_row').off('click').on('click', function() {
							$(this).closest('tr').remove();
						});

						$convTable.closest('.componentWraper').find('.add_new_row').off('click').on('click', function() {
							const $tbody = $convTable.find('tbody');
							const $newSpacer = $convTable.find('.additional_spacer').clone().removeAttr('style').removeClass('additional_spacer').addClass('new_spacer');
							const $newRow = $convTable.find('.additional_row').clone().removeAttr('style').removeClass('additional_row').addClass('new_row');
							$tbody.prepend($newSpacer);
							$tbody.prepend($newRow);
							$newRow.find('.selectXis').select2({
								width: '100%'
							});
							$newRow.find('.remove_row').off('click').on('click', function() {
								$(this).closest('tr').remove();
							});
						});
					}

					// Variety particulars rows
					$container.find('#item_item_variety_particulars_tbody tr td').each(function() {
						const $td = $(this);
						const $select = $td.find('select');
						const particularId = $td.find('input[name="item_variety_particular_ids[]"]').val();

						if (typeof select2Create === 'function') {
							select2Create(
								$select,
								'Particular',
								'{{ route('variety-particulars.create-value', [':id']) }}'.replace(':id', particularId),
								'OTF-BTN',
								true,
								null,
								$select.attr('its_width')
							);
						}

						if ($select.val()) {
							fetchUom($select, $select.val());
						}

						$select.off('change').on('change', function() {
							fetchUom($(this), $(this).val());
						});
					});

					// Alternative items select2
					$container.find('select[name="alt_items[]"]').select2({
						width: '100%',
						placeholder: 'Select Alternative'
					});

					$container.find('select[name="uoms[]"]').select2({
						width: '100%',
					});

					// Checkbox toggles
					$container.find('input[name="is_composite"]').off('change').on('change', function() {
						if ($(this).is(':checked')) {
							loadItemComponents();
						} else {
							$form.find('#componets-container').hide().empty();
						}
					});

					$container.find('input[name="can_track_stock"]').off('change').on('change', function() {
						if ($(this).is(':checked')) {
							loadItemTrackRecords();
						} else {
							$form.find('#track-record-ontainer').hide().empty();
						}
					});

					$container.find('input[name="is_fixed_asset"]').off('change').on('change', function() {
						$form.find('select[name="fa_ctrl_account_id"]').val('').trigger('change');
						if ($(this).is(':checked')) {
							$form.find('input[name="create_helping_ledger"]').prop('checked', false);
							$form.find('.cnsmbl-o-sale-form-group').hide();
						} else {
							$form.find('.cnsmbl-o-sale-form-group').show();
						}
					});

					$container.find('.fa_o_cnsmbl_check').off('click').on('click', function() {
						$('.fa_o_cnsmbl_check').not(this).prop('checked', false);
					});

					// Select2 for valuation and control account
					$container.find('select[name="valuation_method"]').select2({
						width: '100%'
					});
					$container.find('select[name="fa_ctrl_account_id"]').select2({
						width: '100%',
						allowClear: true,
						placeholder: 'Select Control Account'
					});

					// Load components/track records if already checked
					if ($container.find('input[name="is_composite"]').is(':checked')) loadItemComponents();
					if ($container.find('input[name="can_track_stock"]').is(':checked')) loadItemTrackRecords();
				}

				// --- Variety change handler ---
				$form.on('change', 'select[name="item_variety"]', function(e) {
					e.preventDefault();
					loadVariations($(this).val());
				});

				// --- Form submission ---
				$form.off('submit').on('submit', function(e) {
					e.preventDefault();
					const data = $form.find(':input[value!=""]').serialize();

					$.ajax({
						url: $form.data('store-url'),
						type: 'POST',
						data: data,
						beforeSend: function() {
							$form.find('#submit_item').prop('disabled', true).text('Submitting, Please wait...');
							startSpinner();
						},
						success: function(res) {
							window.location.replace('{{ route('item-master-index') }}');
							if (typeof toastResponse === 'function') toastResponse(res);
						},
						error: onAjaxError,
						complete: function() {
							stopSpinner();
							$form.find('#submit_item').prop('disabled', false).html('<i class="fa fa-save"></i>&nbsp;&nbsp;Submit');
						}
					});
				});

				// --- Initial load ---
				const initialVariety = $form.find('select[name="item_variety"]').val();
				if (initialVariety) loadVariations(initialVariety);
				initDynamicContent();
			});

		})(jQuery);
	</script>
@endsection
