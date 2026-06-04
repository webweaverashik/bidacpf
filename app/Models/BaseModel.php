<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Allow mass assignment.
     */
    protected $guarded = [];

    /**
     * Order by latest created record.
     */
    public function scopeLatestFirst($query)
    {
        return $query->latest();
    }
}
