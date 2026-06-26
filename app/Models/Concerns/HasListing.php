<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasListing
{
    /**
     * Apply the request's search/sort/direction parameters to the given query
     * and return the paginated results keyed by $key alongside those resolved
     * parameters, ready to hand straight to an Inertia view.
     *
     * Requires the model to use the Searchable and Sortable concerns.
     *
     * @return array{search: string, sort_by: string, direction: string}&array<string, mixed>
     */
    protected function paginatedListing(Builder $query, Request $request, string $key, string $default_sort = 'last_name', int $per_page = 15): array
    {
        $params = [
            'search' => $request->string('search')->trim()->toString(),
            'sort_by' => $request->string('sort_by', $default_sort)->toString(),
            'direction' => $request->input('direction') === 'desc' ? 'desc' : 'asc',
        ];

        return [
            $key => $query
                ->when($params['search'], fn (Builder $query) => $query->withSearch($params['search']))
                ->withSort($params['sort_by'], $params['direction'])
                ->paginate($per_page)
                ->withQueryString(),
            ...$params,
        ];
    }
}
