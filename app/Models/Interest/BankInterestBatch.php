<?php
namespace App\Models\Interest;

use App\Enums\BatchStatus;
use App\Models\Auth\User;
use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankInterestBatch extends BaseModel
{
    use HasCreatedBy, LogsModelActivity;

    protected ?string $auditLogName  = 'bank_interest_batch';
    protected ?string $auditLabel    = 'Bank Interest';
    protected array $auditAttributes = [
        'distribution_date', 'fiscal_year', 'total_interest_amount', 'total_eligible_balance',
        'status', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at',
        'reversed_by', 'reversed_at', 'remarks',
    ];

    protected $fillable = [
        'distribution_date', 'fiscal_year', 'total_interest_amount', 'total_eligible_balance',
        'status', 'remarks', 'created_by',
        'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'reversed_by', 'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'distribution_date' => 'date',
            'submitted_at'      => 'datetime',
            'approved_at'       => 'datetime',
            'reversed_at'       => 'datetime',
            'status'            => BatchStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function distributions(): HasMany
    {
        return $this->hasMany(BankInterestDistribution::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
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
    public function scopeApproved($query)
    {
        return $query->where('status', BatchStatus::APPROVED);
    }
    public function scopeReversed($query)
    {
        return $query->where('status', BatchStatus::REVERSED);
    }

    /*
    |--------------------------------------------------------------------------
    | Aggregates
    |--------------------------------------------------------------------------
    */
    public function totalDistributed(): int
    {
        return (int) $this->distributions()->sum('interest_amount');
    }

    public function distributionCount(): int
    {
        return $this->distributions()->count();
    }

    /**
     * Rounding residual: total interest received minus total actually allocated.
     * Usually a few BDT due to per-member half-up rounding.
     */
    public function roundingResidual(): int
    {
        return (int) $this->total_interest_amount - $this->totalDistributed();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */
    public function getCutOffLabelAttribute(): string
    {
        return $this->distribution_date->format('d M Y');
    }

    public function getReferenceNoAttribute(): string
    {
        return 'CPF-INT-' . $this->distribution_date->format('Ymd');
    }

    /*
    |--------------------------------------------------------------------------
    | State helpers (delegate to the enum)
    |--------------------------------------------------------------------------
    */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }
    public function canBeSubmitted(): bool
    {
        return $this->status->canSubmit();
    }
    public function canBeApproved(): bool
    {
        return $this->status->canApprove();
    }
    public function canBeRejected(): bool
    {
        return $this->status->canReject();
    }
    public function canBeReversed(): bool
    {
        return $this->status->canReverse();
    }
}
