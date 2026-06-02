<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PayScale extends Model
{
    /** @use HasFactory<\Database\Factories\PayScaleFactory> */
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'is_active'      => 'boolean',
        ];
    }

    public function grades()
    {
        return $this->hasMany(PayScaleGrade::class);
    }
}
