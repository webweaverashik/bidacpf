<?php
namespace App\Enums;

enum EmployeeStatus: string {
    case ACTIVE   = 'active';
    case RETIRED  = 'retired';
    case RESIGNED = 'resigned';
    case DECEASED = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE   => 'Active',
            self::RETIRED  => 'Retired',
            self::RESIGNED => 'Resigned',
            self::DECEASED => 'Deceased',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [
                $case->value => $case->label(),
            ])
            ->toArray();
    }
}
