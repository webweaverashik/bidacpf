<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Application base model.
 *
 * Every domain model (except the Authenticatable ones — User, Employee)
 * extends this class so they share a single set of conventions:
 *  - Mass assignment is open ($guarded = []) since input is validated
 *    upstream by Form Requests.
 *  - The HasFactory trait is included here once so all child models
 *    (CpfAdvance, BankInterestBatch, etc.) can use ModelName::factory()
 *    without each declaring the trait individually.
 */
abstract class BaseModel extends Model
{
    use HasFactory;

    /**
     * All attributes are mass assignable.
     *
     * Validation happens in Form Requests, so we intentionally leave the
     * model open rather than maintaining a duplicate $fillable list.
     */
    protected $guarded = [];

    /**
     * Convenience scope: newest records first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->latest();
    }
}