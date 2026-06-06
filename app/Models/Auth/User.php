<?php
namespace App\Models\Auth;

use App\Models\Attachment;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use App\Models\Interest\BankInterestBatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Auth\LoginActivity;

class User extends Authenticatable
{
    use HasFactory, HasRoles, SoftDeletes, LogsActivity, Notifiable;

    protected $fillable = ['name', 'designation', 'email', 'mobile_number', 'is_active', 'photo_url', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
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
            ->logOnly(['name', 'email', 'designation', 'mobile_number', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */
    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function isCpfOfficer(): bool
    {
        return $this->hasRole('CPF Officer');
    }

    public function isAuditor(): bool
    {
        return $this->hasRole('Auditor');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class, 'user_id');
    }

    public function latestLoginActivity()
    {
        return $this->hasOne(LoginActivity::class)
            ->latestOfMany()
            ->select('login_activities.*');
    }

    public function createdAdvances()
    {
        return $this->hasMany(CpfAdvance::class, 'created_by');
    }

    public function approvedAdvances()
    {
        return $this->hasMany(CpfAdvance::class, 'approved_by');
    }

    public function advanceRecoveries()
    {
        return $this->hasMany(CpfAdvanceRecovery::class, 'created_by');
    }

    public function interestBatches()
    {
        return $this->hasMany(BankInterestBatch::class, 'created_by');
    }

    public function uploadedAttachments()
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }
}
