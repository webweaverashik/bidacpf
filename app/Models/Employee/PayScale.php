<?php
namespace App\Models\Employee;

use App\Models\BaseModel;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayScale extends BaseModel
{
    use SoftDeletes, LogsModelActivity;

    // Activity-log config — structural changes are audit-worthy admin actions.
    protected ?string $auditLogName   = 'pay_scale';
    protected ?string $auditLabel     = 'Pay Scale';
    protected array $auditAttributes  = ['name', 'effective_year', 'effective_from', 'effective_to', 'is_active', 'total_grades'];

    protected $fillable = ['name', 'total_grades', 'effective_year', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'is_active'      => 'boolean',
        ];
    }

    public function steps()
    {
        return $this->hasMany(PayScaleStep::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getSalary(int $grade, int $step): ?int
    {
        return $this->steps()
            ->where('grade', $grade)
            ->where('step', $step)
            ->value('basic_salary');
    }
}
