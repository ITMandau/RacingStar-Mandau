<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllResultsWithRegionExport implements WithMultipleSheets
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            // Sheet 1: pakai class AllResultsExport (kode lo tetap utuh)
            new AllResultsExport($this->filters),

            // Sheet 2: summary star per region -> serpo
            new StarRegionSerpoExport($this->filters),
        ];
    }
}
