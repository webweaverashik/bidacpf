<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected $guarded = [];

    public function scopeLatestFirst($query)
    {
        return $query->latest();
    }
}
