{{--
    One home-page tile, rendered from a composed authMenu() node.

    $tile = ['key','label','icon','href','active','level','children']

    - Leaf  -> links to its own screen ($tile['href']).
    - Container -> has no href of its own (it "expands"), but this grid is flat, so it
      links to a section landing page that shows its children as their own tile grid.
      section-index resolves by key: route('section', ['key' => 'accounts']).

    Icons come from config/navigation.php. The original hardcoded grid used Font Awesome
    classes like "far fa-handshake fa-2x"; MenuComposer passes the class string through, so
    keep the fa-2x sizing here rather than in the config.
--}}

@php
	$isContainer = ! empty($tile['children']);
	$href = $isContainer
		? ($tile['href']
			?? (\Illuminate\Support\Facades\Route::has('section') ? route('section', ['key' => $tile['key']]) : '#'))
		: ($tile['href'] ?? '#');
@endphp

<li>
	<a href="{{ $href }}"
	   class="custom-menu-link link menu-box @if (! empty($tile['active'])) is-active @endif"
	   @if ($isContainer) data-section="{{ $tile['key'] }}" @endif>
		<span class="pa3"><i class="{{ $tile['icon'] }} fa-2x"></i></span>
		<span class="pv2 custom-menu-link-lower-span br--bottom items-center">{{ $tile['label'] }}</span>
	</a>
</li>