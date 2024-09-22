<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class RoutingPlanExport implements FromArray
{
    protected array $salesReps;
    protected array $dates;

    public function __construct(array $salesReps, array $dates)
    {
        $this->salesReps = $salesReps;
        $this->dates = $dates;
    }

    public function array(): array
    {
        // Generate the header row for the Excel file
        $header = ['Sales Rep', 'Date', 'Stores Visited'];

        // Build the rows for each sales rep and date
        $rows = [];
        foreach ($this->salesReps as $rep => $days) {
            foreach ($this->dates as $date) {
                // Check if the sales rep has stores to visit on this date
                $stores = $days[$date] ?? [];
                if (!empty($stores)) {
                    $rows[] = [$rep, $date, implode(', ', $stores)];
                }
            }
        }

        return array_merge([$header], $rows);
    }
}
