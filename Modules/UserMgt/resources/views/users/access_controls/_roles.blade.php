<div class="row" id="rolesPane" data-user="{{ $userId }}">
	@forelse ($roles as $role)
		@php
			$rname = $role['name'] ?? ($role['label'] ?? '—');
			$desc = $role['description'] ?? null;
			$checked = in_array($rname, $assignedNames, true);
		@endphp
		<div class="col-md-4">
			<div class="card mb-2 {{ $checked ? 'border-primary' : '' }}">
				<div class="card-body py-2">
					<label class="mb-0 d-flex align-items-center" style="cursor:pointer">
						<input type="checkbox" name="roles[]" value="{{ $rname }}" class="mr-2 role-check" {{ $checked ? 'checked' : '' }}>
						<span>
							<strong>{{ $rname }}</strong>
							@if ($desc)
								<br><small class="text-muted">{{ $desc }}</small>
							@endif
						</span>
					</label>
				</div>
			</div>
		</div>
	@empty
		<div class="col-12">
			<p class="text-muted">No roles available for this tenant.</p>
		</div>
	@endforelse
</div>
