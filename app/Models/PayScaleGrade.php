<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayScaleGrade extends Model
{
    protected $guarded = [];

    public function payScale()
    {
        return $this->belongsTo(PayScale::class);
    }
}
