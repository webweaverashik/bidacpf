<?php
namespace App\Models\Employee;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayScale extends BaseModel
{
    use SoftDeletes;

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
