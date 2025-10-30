<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Adds a query scope to filter results based on a search term when the term's length exceeds two characters.
     *
     * @param  Builder  $query  The query builder instance.
     * @param  string|null  $search  The search term used for filtering.
     *
     * @return void
     */
    public function scopeSearchAny(Builder $query, string|null $search) : void
    {
        $query->when(strlen($search) > 2, function ($query) use ($search) {
            $query->whereAny(self::SEARCH_FIELDS, 'like', '%'.$search.'%');
        });
    }
}