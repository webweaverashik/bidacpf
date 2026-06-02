<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class CpfAdvance extends Model
{
    /** @use HasFactory<\Database\Factories\CpfAdvanceFactory> */
    use HasFactory, SoftDeletes, LogsActivity;
}
