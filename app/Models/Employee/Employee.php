<?php
namespace App\Models\Employee;

use App\Enums\EmployeeStatus;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfLedger;
use App\Models\Cpf\CpfOpeningBalance;
use App\Models\Interest\BankInterestDistribution;
use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable
{
    use SoftDeletes, Notifiable, HasRoles, LogsActivity, HasCreatedBy;

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
            'password'        => 'hashed',
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
    public function openingBalance()
    {
        return $this->hasOne(CpfOpeningBalance::class);
    }

    public function payScaleStep()
    {
        return $this->belongsTo(PayScaleStep::class);
    }

    public function salaryHistories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    public function advances()
    {
        return $this->hasMany(CpfAdvance::class);
    }

    public function ledgers()
    {
        return $this->hasMany(CpfLedger::class);
    }

    public function interestDistributions()
    {
        return $this->hasMany(BankInterestDistribution::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

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
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->cpf_account_no})";
    }

    public function getGradeAttribute(): ?int
    {
        return $this->payScaleStep?->grade;
    }

    public function getCurrentStepAttribute(): ?int
    {
        return $this->payScaleStep?->step;
    }

    public function getCurrentBasicSalaryAttribute(): ?int
    {
        return $this->payScaleStep?->basic_salary;
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('img/male-placeholder.png');
    }

    /*
    |--------------------------------------------------------------------------
    | Balance
    |--------------------------------------------------------------------------
    */
    public function currentBalance(): int
    {
        return (int) $this->ledgers()
            ->latest('transaction_date')
            ->latest('id')
            ->value('balance');
    }
}
