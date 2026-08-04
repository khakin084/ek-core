@php
	$key = $module['key'] ?? ($module['id'] ?? null);
	$label = $module['label'] ?? ($module['name'] ?? '—');
	$current = (int) ($assignments[$key] ?? 0);
@endphp
<tr class="{{ $isGroup ? 'perm-group' : 'perm-child' }}">
	<td class="{{ $isGroup ? 'font-weight-bold' : 'font-italic text-muted' }}">{{ $label }}</td>
	@foreach ($levels as $lvl)
		<td class="text-center">
			<label class="perm-switch mb-0">
				<input type="radio" name="permissions[{{ $key }}]" value="{{ $lvl['value'] }}" {{ $current === (int) $lvl['value'] ? 'checked' : '' }}>
				<span class="perm-slider"></span>
			</label>
		</td>
	@endforeach
</tr>
