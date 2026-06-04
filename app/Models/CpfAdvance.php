<?php
namespace App\Models;

use App\Enums\AdvanceStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class CpfAdvance extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['advance_no', 'employee_id', 'application_date', 'approval_date', 'approved_amount', 'interest_rate', 'installment_count', 'outstanding_amount', 'status', 'remarks', 'created_by', 'approved_by'];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'approval_date'    => 'date',
            'status'           => AdvanceStatus::class,
        ];
    }

    /**
     * CPF member.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Recovery entries.
     */
    public function recoveries()
    {
        return $this->hasMany(CpfAdvanceRecovery::class);
    }

    /**
     * Supporting documents.
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Creator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Approver.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Pending advances.
     */
    public function scopePending($query)
    {
        return $query->where('status', AdvanceStatus::PENDING);
    }

    /**
     * Approved advances.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', AdvanceStatus::APPROVED);
    }

    /**
     * Completed advances.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', AdvanceStatus::COMPLETED);
    }

    /**
     * Total recovered amount.
     */
    public function totalRecovered(): int
    {
        return (int) $this->recoveries()->sum('amount');
    }

    /**
     * Remaining balance.
     */
    public function remainingAmount(): int
    {
        return (int) $this->outstanding_amount;
    }

    /**
     * Fully recovered?
     */
    public function isCompleted(): bool
    {
        return $this->outstanding_amount <= 0;
    }

    /**
     * First attachment.
     */
    public function firstAttachment()
    {
        return $this->attachments()->first();
    }
}
