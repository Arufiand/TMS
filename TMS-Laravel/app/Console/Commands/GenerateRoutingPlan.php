<?php
//
//namespace App\Console\Commands;
//
//use Carbon\Carbon;
//use Illuminate\Console\Command;
//use League\Csv\Exception;
//use League\Csv\Reader;
//use League\Csv\UnavailableStream;
//use Maatwebsite\Excel\Facades\Excel;
//use App\Exports\RoutingPlanExport;
//
//class GenerateRoutingPlan extends Command
//{
//    protected $signature = 'generate:routing-plan';
//    protected $description = 'Generate the most effective routing plan based on proximity and store visit frequency';
//
//    // HQ coordinates
//    private float $hqLatitude = -7.9826;
//    private float $hqLongitude = 112.6308;
//
//    /**
//     * @throws UnavailableStream
//     * @throws Exception
//     */
//    public function handle(): void
//    {
//        // Step 1: Load stores from CSV
//        $csv = Reader::createFromPath(storage_path('app/stores.csv'), 'r');
//        $csv->setHeaderOffset(0);
//        $stores = iterator_to_array($csv->getRecords());
//
//        // Step 2: Organize stores by frequency (weekly, biweekly, monthly)
//        $weeklyStores = [];
//        $biweeklyStores = [];
//        $monthlyStores = [];
//        foreach ($stores as $store) {
//            switch ($store['FINAL CYCLE']) {
//                case 'Weekly':
//                    $weeklyStores[] = $store;
//                    break;
//                case 'Biweekly':
//                    $biweeklyStores[] = $store;
//                    break;
//                case 'Monthly':
//                    $monthlyStores[] = $store;
//                    break;
//            }
//        }
//
//        // Step 3: Assign stores to sales reps (based on nearest distance and constraints)
//        $salesReps = [];
//        for ($i = 1; $i <= 10; $i++) {
//            $salesReps['Sales' . $i] = [];
//        }
//
//        // Step 4: Generate the date range (from Oct 1 to Oct 31, excluding Sundays)
//        $dates = $this->generateDates();
//
//        // Step 5: Schedule visits based on frequency and dates
//        $this->scheduleVisits($salesReps, $weeklyStores, 4, $dates);  // Weekly stores (4 visits in a month)
//        $this->scheduleVisits($salesReps, $biweeklyStores, 2, $dates);  // Biweekly stores (2 visits in a month)
//        $this->scheduleVisits($salesReps, $monthlyStores, 1, $dates);  // Monthly stores (1 visit in a month)
//
//        // Step 6: Output routing plan to an Excel file
//        $this->exportToExcel($salesReps, $dates);
//    }
//
//    private function generateDates(): array
//    {
//        $dates = [];
//        $startDate = Carbon::createFromDate(2024, 10, 1);
//        $endDate = Carbon::createFromDate(2024, 10, 31);
//
//        // Loop through each day from Oct 1 to Oct 31 and Remove date when it's Sunday
//        while ($startDate->lte($endDate)) {
//            if (!$startDate->isSunday()) {
//                $dates[] = $startDate->toDateString();  // Use full date format
//            }
//            $startDate->addDay();
//        }
//
//        return $dates;
//    }
//
//    private function scheduleVisits(&$salesReps, $stores, $frequency, $dates): void
//    {
//        $storeIndex = 0;
//        $totalStores = count($stores);
//
//        // Assign stores based on frequency (weekly: 4, biweekly: 2, monthly: 1)
//        foreach ($dates as $date) {
//            foreach ($salesReps as $rep => &$repVisits) {
//                if (count($repVisits[$date] ?? []) < 30 && $storeIndex < $totalStores) {
//                    $repVisits[$date][] = $stores[$storeIndex]['Name'];
//
//                    $storeIndex = ($storeIndex + 1) % $totalStores;
//                }
//            }
//        }
//    }
//
//    private function exportToExcel($salesReps, $dates): void
//    {
//        Excel::store(new RoutingPlanExport($salesReps, $dates), 'routing_plan.xlsx');
//    }
//}
//
//


namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RoutingPlanExport;

class GenerateRoutingPlan extends Command
{
    protected $signature = 'generate:routing-plan';
    protected $description = 'Generate the most effective routing plan based on proximity and store visit frequency';

    // HQ coordinates
    private float $hqLatitude = -7.9826;
    private float $hqLongitude = 112.6308;

    /**
     * @throws UnavailableStream
     * @throws Exception
     */
    public function handle(): void
    {
        // Step 1: Load stores from CSV
        $csv = Reader::createFromPath(storage_path('app/stores.csv'), 'r');
        $csv->setHeaderOffset(0);
        $stores = iterator_to_array($csv->getRecords());

        // Step 2: Organize stores by frequency (weekly, biweekly, monthly)
        $weeklyStores = [];
        $biweeklyStores = [];
        $monthlyStores = [];
        foreach ($stores as $store) {
            switch ($store['FINAL CYCLE']) {
                case 'Weekly':
                    $weeklyStores[] = $store;
                    break;
                case 'Biweekly':
                    $biweeklyStores[] = $store;
                    break;
                case 'Monthly':
                    $monthlyStores[] = $store;
                    break;
            }
        }

        // Step 3: Assign stores to sales reps based on nearest distance (Haversine) and constraints
        $salesReps = [];
        for ($i = 1; $i <= 10; $i++) {
            $salesReps['Sales' . $i] = [];
        }

        // Step 4: Generate the date range (from Oct 1 to Oct 31, excluding Sundays)
        $dates = $this->generateDates();

        // Step 5: Schedule visits based on frequency and nearest stores
        $this->scheduleVisits($salesReps, $weeklyStores, 4, $dates);  // Weekly stores (4 visits in a month)
        $this->scheduleVisits($salesReps, $biweeklyStores, 2, $dates);  // Biweekly stores (2 visits in a month)
        $this->scheduleVisits($salesReps, $monthlyStores, 1, $dates);  // Monthly stores (1 visit in a month)

        // Step 6: Output routing plan to an Excel file
        $this->exportToExcel($salesReps, $dates);
    }

    private function generateDates(): array
    {
        $dates = [];
        $startDate = Carbon::createFromDate(2024, 10, 1);
        $endDate = Carbon::createFromDate(2024, 10, 31);

        // Loop through each day from Oct 1 to Oct 31, excluding Sundays
        while ($startDate->lte($endDate)) {
            if (!$startDate->isSunday()) {
                $dates[] = $startDate->toDateString();  // Use full date format
            }
            $startDate->addDay();
        }

        return $dates;
    }

    private function scheduleVisits(&$salesReps, $stores, $frequency, $dates): void
    {
        // Step 1: Calculate the distance of each store from the HQ using the Haversine formula
        foreach ($stores as &$store) {
            $store['distance'] = $this->haversineDistance(
                $this->hqLatitude,
                $this->hqLongitude,
                $store['Latitude'],
                $store['Longitude']
            );
        }
        // Sort stores by proximity to the HQ
        usort($stores, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        $storeIndex = 0;
        $totalStores = count($stores);

        // Step 2: Assign stores based on frequency and available dates
        foreach ($dates as $date) {
            foreach ($salesReps as $rep => &$repVisits) {
                if (count($repVisits[$date] ?? []) < 30 && $storeIndex < $totalStores) {
                    // Assign stores to the sales rep for the given date
                    $repVisits[$date][] = $stores[$storeIndex]['Name'];
                    $storeIndex++;
                    // Reset the store index if all stores have been assigned
                    if ($storeIndex >= $totalStores) {
                        $storeIndex = 0;
                    }
                }
            }
        }
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;  // Earth radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;  // Distance in kilometers
    }

    private function exportToExcel($salesReps, $dates): void
    {
        Excel::store(new RoutingPlanExport($salesReps, $dates), 'routing_plan.xlsx');
    }
}
