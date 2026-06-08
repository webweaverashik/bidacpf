<?php
namespace App\Models\Employee;

use App\Enums\EmployeeStatus;
use App\Models\BaseModel;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfLedger;
use App\Models\Cpf\CpfOpeningBalance;
use App\Models\Interest\BankInterestDistribution;
use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a BIDA CPF member (officer or employee).
 *
 * Employees are NOT system users — they do not log in and have no
 * password, roles, or authentication tokens. All authentication is
 * handled by App\Models\Auth\User (the admin/officer accounts).
 *
 * Extends BaseModel (which carries HasFactory and $guarded = []).
 */
class Employee extends BaseModel
{
    use SoftDeletes, LogsActivity, HasCreatedBy;

    protected $fillable = [
        'cpf_account_no',
        'name',
        'designation',
        'email',
        'mobile_number',
        'photo',
        'joining_date',
        'retirement_date',
        'pay_scale_step_id',
        'status',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'joining_date'    => 'date',
            'retirement_date' => 'date',
            'is_active'       => 'boolean',
            'status'          => EmployeeStatus::class,
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
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * CPF opening balance snapshot (onboarding record).
     */
    public function openingBalance()
    {
        return $this->hasOne(CpfOpeningBalance::class);
    }

    /**
     * Current pay scale step (grade + step + basic salary).
     */
    public function payScaleStep()
    {
        return $this->belongsTo(PayScaleStep::class);
    }

    /**
     * Full pay scale step history (promotions, increments, revisions).
     */
    public function salaryHistories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    /**
     * CPF advances (loans) taken by this employee.
     */
    public function advances()
    {
        return $this->hasMany(CpfAdvance::class);
    }

    /**
     * Running CPF ledger entries for this employee.
     */
    public function ledgers()
    {
        return $this->hasMany(CpfLedger::class);
    }

    /**
     * Bank interest distributions allocated to this employee.
     */
    public function interestDistributions()
    {
        return $this->hasMany(BankInterestDistribution::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Only active employees (excluded from contribution batches if inactive).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Search by name, CPF account number, email, or mobile.
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('cpf_account_no', 'like', "%{$keyword}%")
                ->orWhere('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('mobile_number', 'like', "%{$keyword}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Display label used in dropdowns: "John Doe (PRA/K/1234/25)".
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->cpf_account_no})";
    }

    /**
     * Current grade (derived from the assigned pay scale step).
     */
    public function getGradeAttribute(): ?int
    {
        return $this->payScaleStep?->grade;
    }

    /**
     * Current step number within the grade.
     */
    public function getCurrentStepAttribute(): ?int
    {
        return $this->payScaleStep?->step;
    }

    /**
     * Current basic salary (whole BDT integer).
     */
    public function getCurrentBasicSalaryAttribute(): ?int
    {
        return $this->payScaleStep?->basic_salary;
    }

    /**
     * Photo URL — falls back to a placeholder if no photo is uploaded.
     */
    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? asset($this->photo) // ✅ NEW — direct public path
            : asset('img/male-placeholder.png');
    }

    /*
    |--------------------------------------------------------------------------
    | Balance
    |--------------------------------------------------------------------------
    */

    /**
     * Latest running CPF balance for this employee.
     *
     * Reads the balance column from the most recent ledger row rather than
     * summing all credits/debits so it stays O(1) regardless of history.
     */
    public function currentBalance(): int
    {
        return (int) $this->ledgers()
            ->latest('transaction_date')
            ->latest('id')
            ->value('balance');
    }
}
