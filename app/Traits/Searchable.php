<?php

namespace App\Traits;


trait Searchable
{
    public function scopeSearchAny($query, $search) : void
    {
        $query->when(strlen($search) > 2, function ($query) {
            $query->whereAny($this->search_fields, 'like', '%'.request('search').'%');
        });
    }
}