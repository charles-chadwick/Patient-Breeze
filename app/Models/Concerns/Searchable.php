<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Define which fields are searchable.
     *
     * Entries can be direct columns ('email', 'blood_type') or
     * dot-notation relation columns ('user.first_name', 'user.email').
     * Relation columns are grouped into a single whereHas per relation.
     *
     * @return array<int, string>
     */
    abstract protected function searchableFields(): array;

    public function scopeWithSearch(Builder $query, string $search): void
    {
        $direct_fields = [];
        $relation_fields = [];

        foreach ($this->searchableFields() as $field) {
            if (str_contains($field, '.')) {
                [$relation, $column] = explode('.', $field, 2);
                $relation_fields[$relation][] = $column;
            } else {
                $direct_fields[] = $field;
            }
        }

        $query->where(function (Builder $query) use ($search, $direct_fields, $relation_fields): void {
            foreach ($direct_fields as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }

            foreach ($relation_fields as $relation => $columns) {
                $query->orWhereHas($relation, function (Builder $query) use ($columns, $search): void {
                    $query->where(function (Builder $query) use ($columns, $search): void {
                        foreach ($columns as $column) {
                            $query->orWhere($column, 'like', "%{$search}%");
                        }
                    });
                });
            }
        });
    }
}
