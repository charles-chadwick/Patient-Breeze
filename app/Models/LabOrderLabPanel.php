<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabOrderLabPanel extends Pivot
{
    use SoftDeletes;

    protected $table = 'lab_order_lab_panel';

    public $incrementing = true;
}
