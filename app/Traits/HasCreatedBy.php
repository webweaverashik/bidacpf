<?php
namespace App\Traits;

use App\Models\User;

trait HasCreatedBy
{
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
