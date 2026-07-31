<?php

namespace App\Services\Navigation;

use Illuminate\Support\Facades\Route;

/**
 * Turns the auth service's visible menu tree into a render-ready navigation structure.
 *
 * IN : auth's tree — [ ['key','name','level','type','children'], ... ] — already filtered to
 *      what this user may see, in the right order, but with no routes or icons.
 * OUT: the same tree enriched with href + icon + active, and pruned of anything ek-core
 *      cannot route yet.
 *
 * The two services stay decoupled: auth never learns ek-core's URLs, ek-core never
 * re-runs permission logic. The module key is the only thing they share.
 */
class MenuComposer
{
    private array $nav;

    public function __construct()
    {
        $this->nav = config('navigation.items', []);
    }

    /**
     * @param  array  $tree  the session menu tree from auth
     * @return array         render-ready items
     */
    public function compose(array $tree): array
    {
        $items = [];

        foreach ($tree as $node) {
            if ($item = $this->composeNode($node)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function composeNode(array $node): ?array
    {
        $key       = $node['key'];
        $config    = $this->nav[$key] ?? null;
        $container = ($node['type'] ?? 'leaf') === 'container';

        if ($container) {
            $children = $this->compose($node['children'] ?? []);

            // A container is only a heading for its children — drop it if nothing under it
            // survived (all children unrouted, or the section is empty).
            if (empty($children)) {
                return null;
            }

            // A container MAY carry its own landing route (e.g. user_mgt -> usermgt.index).
            // When it does, the tile links there and highlights on it; when it doesn't, it
            // stays a pure heading (href null) and the flat-grid tile falls back to the
            // generic section route.
            $routeName = $config['route'] ?? null;
            $hasRoute  = $routeName !== null && Route::has($routeName);

            return [
                'key'      => $key,
                'label'    => $node['name'],
                'icon'     => $config['icon'] ?? config('navigation.default_icon'),
                'href'     => $hasRoute ? route($routeName) : null,
                'active'   => ($hasRoute && Route::currentRouteNamed($routeName, $routeName . '.*'))
                              || $this->anyActive($children),
                'level'    => $node['level'] ?? 0,
                'children' => $children,
            ];
        }

        // Leaf: needs a real, registered route to be a link.
        $routeName = $config['route'] ?? null;

        if ($routeName === null || ! Route::has($routeName)) {
            // Visible to the user, but ek-core has no screen for it yet. Skip, don't crash —
            // and log, so a half-wired module surfaces in the ops log rather than as a dead
            // tile.
            $this->logUnrouted($key, $routeName);

            return null;
        }

        return [
            'key'      => $key,
            'label'    => $node['name'],
            'icon'     => $config['icon'] ?? config('navigation.default_icon'),
            'href'     => route($routeName),
            'active'   => Route::currentRouteNamed($routeName, $routeName . '.*'),
            'level'    => $node['level'] ?? 0,
            'children' => [],
        ];
    }

    private function anyActive(array $children): bool
    {
        foreach ($children as $child) {
            if (($child['active'] ?? false) || $this->anyActive($child['children'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    private function logUnrouted(string $key, ?string $route): void
    {
        errorLogger(date('H') . '.info.log', json_encode([
            'message' => 'Menu item is visible to the user but ek-core cannot route it.',
            'module'  => $key,
            'route'   => $route,
        ]));
    }
}