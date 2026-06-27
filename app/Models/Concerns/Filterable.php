<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    /**
     * Define which fields are filterable.
     *
     * Keys are the URL-facing filter parameter names.
     * Values are direct columns ('blood_type') or dot-notation relation columns ('roles.name').
     * Each filter accepts one or more values; values within a single filter match with OR,
     * while different filters are combined with AND.
     *
     * @return array<string, string>
     */
    abstract protected function filterableFields(): array;

    /**
     * Resolve the active filters from the request, keyed by filter parameter name.
     *
     * @return array<string, list<string>>
     */
    public function resolveFilters(Request $request): array
    {
        $filters = [];

        foreach (array_keys($this->filterableFields()) as $key) {
            $values = array_values(array_filter(
                array_map('strval', (array) $request->input($key, [])),
                fn (string $value): bool => $value !== '',
            ));

            $filters[$key] = $values;
        }

        return $filters;
    }

    /**
     * @param  array<string, list<string>>  $filters
     */
    public function scopeWithFilters(Builder $query, array $filters): void
    {
        foreach ($this->filterableFields() as $key => $column) {
            $values = $filters[$key] ?? [];

            if ($values === []) {
                continue;
            }

            if (str_contains($column, '.')) {
                [$relation, $related_column] = explode('.', $column, 2);
                $query->whereHas($relation, fn (Builder $query) => $query->whereIn($related_column, $values));
            } else {
                $query->whereIn($column, $values);
            }
        }
    }
}
