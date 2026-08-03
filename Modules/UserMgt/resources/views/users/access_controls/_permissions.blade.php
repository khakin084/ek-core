<style>
	.perm-matrix .perm-switch {
		position: relative;
		display: inline-block;
		width: 46px;
		height: 24px;
	}

	.perm-matrix .perm-switch input {
		position: absolute;
		opacity: 0;
		width: 0;
		height: 0;
	}

	.perm-matrix .perm-slider {
		position: absolute;
		inset: 0;
		cursor: pointer;
		background: #e9ecef;
		border-radius: 24px;
		transition: .15s;
	}

	.perm-matrix .perm-slider::before {
		content: "";
		position: absolute;
		height: 18px;
		width: 18px;
		left: 3px;
		top: 3px;
		background: #fff;
		border-radius: 50%;
		box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
		transition: .15s;
	}

	.perm-matrix input:checked+.perm-slider {
		background: #4b6cf5;
	}

	.perm-matrix input:checked+.perm-slider::before {
		transform: translateX(22px);
	}

	.perm-matrix .perm-child td:first-child {
		padding-left: 2.5rem;
	}
</style>

<div class="perm-matrix" data-user="{{ $userId }}">
	<table class="table table-sm mb-0">
		<thead class="thead-dark">
			<tr>
				<th style="width:40%">MODULE</th>
				@foreach ($levels as $lvl)
					<th class="text-center">{{ $lvl['label'] }}</th>
				@endforeach
			</tr>
		</thead>
		<tbody>
			@forelse ($modules as $group)
				@include('usermgt::users.access_controls._perm-row', ['module' => $group, 'isGroup' => true])
				@foreach ($group['children'] ?? [] as $child)
					@include('usermgt::users.access_controls._perm-row', ['module' => $child, 'isGroup' => false])
				@endforeach
			@empty
				<tr>
					<td colspan="{{ count($levels) + 1 }}" class="text-muted">No modules registered.</td>
				</tr>
			@endforelse
		</tbody>
	</table>
</div>
