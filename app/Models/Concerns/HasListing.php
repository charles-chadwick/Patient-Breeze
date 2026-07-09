<?php

namespace App\Models\Concerns;

use App\Enums\SettingKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasListing
{
    /**
     * Apply the request's search/sort/direction parameters to the given query
     * and return the paginated results keyed by $key alongside those resolved
     * parameters, ready to hand straight to an Inertia view.
     *
     * The page size follows the authenticated user's "Items Per Page"
     * preference, falling back to $default_per_page when no such user is
     * present (e.g. the patient portal).
     *
     * Requires the model to use the Searchable, Sortable, and Filterable concerns.
     *
     * @return array{search: string, sort_by: string, direction: string, filters: array<string, list<string>>}&array<string, mixed>
     */
    protected function paginatedListing(Builder $query, Request $request, string $key, string $default_sort = 'last_name', int $default_per_page = 15): array
    {
        $per_page = $this->resolvePerPage($request, $default_per_page);

        $params = [
            'search' => $request->string('search')->trim()->toString(),
            'sort_by' => $request->string('sort_by', $default_sort)->toString(),
            'direction' => $request->input('direction') === 'desc' ? 'desc' : 'asc',
            'filters' => $this->resolveFilters($request),
        ];

        return [
            $key => $query
                ->when($params['search'], fn (Builder $query) => $query->withSearch($params['search']))
                ->withFilters($params['filters'])
                ->withSort($params['sort_by'], $params['direction'])
                ->paginate($per_page)
                ->withQueryString(),
            ...$params,
        ];
    }

    /**
     * Resolve the listing page size from the authenticated user's
     * "Items Per Page" preference, or the given default when unavailable.
     */
    private function resolvePerPage(Request $request, int $default_per_page): int
    {
        $user = $request->user();

        if ($user instanceof User) {
            return (int) $user->getSetting(SettingKey::ItemsPerPage);
        }

        return $default_per_page;
    }
}
