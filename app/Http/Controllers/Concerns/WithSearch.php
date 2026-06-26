<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait WithSearch
{
    /**
     * Resolve the trimmed search term from the request.
     */
    protected function searchTerm(Request $request): string
    {
        return $request->string('search')->trim()->toString();
    }

    /**
     * Resolve the search, sort, and direction parameters from the request.
     *
     * @return array{search: string, sort_by: string, direction: string}
     */
    protected function searchParameters(Request $request, string $default_sort = 'last_name'): array
    {
        return [
            'search' => $this->searchTerm($request),
            'sort_by' => $request->string('sort_by', $default_sort)->toString(),
            'direction' => $request->input('direction') === 'desc' ? 'desc' : 'asc',
        ];
    }
}
