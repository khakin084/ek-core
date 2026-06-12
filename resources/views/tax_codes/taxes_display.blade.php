@if (isset($aggregation_arr['tax']))
	@if ($table_id == 'commerce-fom-table')
		@foreach ($aggregation_arr['tax'] as $row)
			<tr class="taxes_temp_row">
				<td colspan="6" style="text-align: right">{{ strtoupper($row['symbol']) }}</td>
				<td style="text-align: right">{{ number_format($row['amount'], 2) }}</td>
			</tr>
		@endforeach
		<tr class="taxes_temp_row">
			<td colspan="6" style="text-align: right">TOTAL</td>
			<td class="total_tax_amount" style="text-align: right">{{ number_format($aggregation_arr['total_tax'], 2) }}</td>
		</tr>
	@else
		@foreach ($aggregation_arr['tax'] as $row)
			<div class="row col-md-12 taxes_temp_row" style="margin: 0">
				<div style="width: 60%; text-align: left; font-size: 15px;">
					{!! strtoupper($row['symbol']) !!}
				</div>
				<div style="width: 40%; text-align: right;">
					<span>{!! number_format($row['amount'], 2) !!}</span>&nbsp;{!! baseCurrency()->symbol !!}
				</div>
			</div>
		@endforeach
		<div class="row col-md-12 taxes_temp_row" style="margin: 0">
			<div style="width: 60%; text-align: left; font-size: 15px;">
				<strong>TOTAL</strong>
			</div>
			<div style="width: 40%; text-align: right;">
				<strong><span class="total_tax_amount">{!! number_format($aggregation_arr['total_tax'], 2) !!}</span>&nbsp;{!! baseCurrency()->symbol !!}</strong>
			</div>
		</div>
	@endif
@endif
