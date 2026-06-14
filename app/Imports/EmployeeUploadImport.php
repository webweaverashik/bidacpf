<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Thin reader for the employee bulk-upload file.
 *
 * Does NOT persist anything — it simply captures every data row keyed by the
 * header (snake_cased by WithHeadingRow). The EmployeeUploadService then
 * normalises, validates and (on confirm) imports those rows so the preview
 * and the commit run through exactly the same pipeline.
 */
class EmployeeUploadImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** @var Collection Rows keyed by header. */
    public Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $collection): void
    {
        $this->rows = $collection;
    }

    /** Header is on row 1. */
    public function headingRow(): int
    {
        return 1;
    }
}
