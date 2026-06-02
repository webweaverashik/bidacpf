<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, SoftDeletes, LogsActivity, Notifiable;

    protected $fillable = [
        'name',
        'designation',
        'email',
        'mobile_number',
        'is_active',
        'photo_url',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Options
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
    | Role Helper Methods
    |--------------------------------------------------------------------------
    */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function isCpfOfficer(): bool
    {
        return $this->hasRole('CPF Officer');
    }

    public function isAccountsOfficer(): bool
    {
        return $this->hasRole('Accounts Officer');
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
        return $this->hasMany(LoginActivity::class, 'user_id')
            ->where('user_type', 'user');
    }

    public function latestLoginActivity()
    {
        return $this->hasOne(LoginActivity::class)
            ->where('user_type', 'user')
            ->latestOfMany()
            ->select('login_activities.*');
    }
}
