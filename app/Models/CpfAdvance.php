<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class CpfAdvance extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'status',
    ];

    protected static $logAttributes = ['user_id', 'amount', 'reason', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
