<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class FiscalYear extends Model
{
    /** @use HasFactory<\Database\Factories\FiscalYearFactory> */
    use HasFactory, SoftDeletes, LogsActivity;
}
