<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class RoutingPlanExport implements FromArray
{
    protected array $salesReps;

    public function __construct(array $salesReps)
    {
        $this->salesReps = $salesReps;
    }

    public function array(): array
    {
        // Generate the header row for the Excel file
        $header = ['Sales Rep', 'Day', 'Stores Visited'];

        // Build the rows for each sales rep and day
        $rows = [];
        foreach ($this->salesReps as $rep => $days) {  // Use $this->salesReps here
            foreach ($days as $day => $stores) {
                $rows[] = [$rep, $day, implode(', ', $stores)];
            }
        }

        return array_merge([$header], $rows);
    }
}

