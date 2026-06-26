<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Sortable
{
    /**
     * Define which fields are sortable.
     *
     * Keys are the URL-facing sort parameter values.
     * Values are direct columns ('blood_type') or dot-notation relation columns ('user.last_name').
     * Only BelongsTo relations are supported for relation sorting.
     *
     * @return array<string, string>
     */
    abstract protected function sortableFields(): array;

    public function scopeWithSort(Builder $query, string $sort_by, string $direction = 'asc'): void
    {
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';
        $fields = $this->sortableFields();
        $model_table = $this->getTable();

        if (! array_key_exists($sort_by, $fields)) {
            $query->orderBy("{$model_table}.id");

            return;
        }

        $column = $fields[$sort_by];

        if (str_contains($column, '.')) {
            [$relation_name, $col] = explode('.', $column, 2);
            $relation = $this->$relation_name();

            if ($relation instanceof BelongsTo) {
                $related = $relation->getRelated();
                $foreign_key = $relation->getForeignKeyName();
                $owner_key = $relation->getOwnerKeyName();
                $related_table = $related->getTable();

                $query
                    ->leftJoin($related_table, "{$model_table}.{$foreign_key}", '=', "{$related_table}.{$owner_key}")
                    ->orderBy("{$related_table}.{$col}", $direction)
                    ->select("{$model_table}.*");
            }
        } else {
            $query->orderBy($column, $direction);
        }

        $query->orderBy("{$model_table}.id");
    }
}
