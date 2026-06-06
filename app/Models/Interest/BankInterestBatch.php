<?php
namespace App\Models\Interest;

use App\Enums\BatchStatus;
use App\Models\Auth\User;
use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BankInterestBatch extends BaseModel
{
    use HasCreatedBy, LogsActivity;

    protected $fillable = [
        'distribution_date',
        'fiscal_year',
        'total_interest_amount',
        'total_eligible_balance',
        'status',
        'remarks',
        'created_by',
        'submitted_by',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'distribution_date' => 'date',
            'submitted_at'      => 'datetime',
            'status'            => BatchStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'distribution_date',
                'fiscal_year',
                'total_interest_amount',
                'total_eligible_balance',
                'status',
                'submitted_by',
                'submitted_at',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('bank_interest_batch');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function distributions()
    {
        return $this->hasMany(BankInterestDistribution::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeDraft($query)
    {
        return $query->where('status', BatchStatus::DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', BatchStatus::SUBMITTED);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */
    public function canBeSubmitted(): bool
    {
        return $this->status === BatchStatus::DRAFT;
    }

    public function canBeReversed(): bool
    {
        return $this->status === BatchStatus::SUBMITTED;
    }

    public function totalDistributed(): float
    {
        return (float) $this->distributions()->sum('interest_amount');
    }

    public function distributionCount(): int
    {
        return $this->distributions()->count();
    }
}
