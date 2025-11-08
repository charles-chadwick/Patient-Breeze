<?php

namespace App\Traits;


trait Searchable
{
    public function scopeSearch($query, $search) : void
    {
        $query->when(strlen($search) > 2, function ($query) {
            $query->whereAny($this->search_fields, 'like', '%'.request('search').'%');
        });
    }
}