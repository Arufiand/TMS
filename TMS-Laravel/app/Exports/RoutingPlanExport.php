<?php
//
//namespace App\Exports;
//
//use Maatwebsite\Excel\Concerns\FromArray;
//
//class RoutingPlanExport implements FromArray
//{
//    protected array $salesReps;
//    protected array $dates;
//
//    public function __construct(array $salesReps, array $dates)
//    {
//        $this->salesReps = $salesReps;
//        $this->dates = $dates;
//    }
//
//    public function array(): array
//    {
//        // Generate the header row for the Excel file
//        $header = ['Sales Rep', 'Date', 'Stores Visited'];
//
//        // Build the rows for each sales rep and date
//        $rows = [];
//        foreach ($this->salesReps as $rep => $days) {
//            foreach ($this->dates as $date) {
//                // Check if the sales rep has stores to visit on this date
//                $stores = $days[$date] ?? [];
//                if (!empty($stores)) {
//                    $rows[] = [$rep, $date, implode(', ', $stores)];
//                }
//            }
//        }
//
//        return array_merge([$header], $rows);
//    }
//}


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
        $header = array_merge(['Sales'], $this->dates);

        // Build the rows for each sales rep
        $rows = [];
        foreach ($this->salesReps as $rep => $days) {
            $row = [$rep]; // Start the row with the sales rep name

            // Add the stores visited for each date
            foreach ($this->dates as $date) {
                $storesVisited = isset($days[$date]) ? implode(', ', $days[$date]) : '';
                $row[] = $storesVisited;
            }

            $rows[] = $row;
        }

        return array_merge([$header], $rows);
    }
}
