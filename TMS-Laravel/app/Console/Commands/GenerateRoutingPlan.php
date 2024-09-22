<?php

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
        // Step 1: Load the stores from CSV
        $csv = Reader::createFromPath(storage_path('app/stores.csv'), 'r');
        $csv->setHeaderOffset(0); // Assumes first row is header
        $stores = iterator_to_array($csv->getRecords());

        // Step 2: Organize stores by frequency (weekly, biweekly, monthly)
        $weeklyStores = [];
        $biweeklyStores = [];
        $monthlyStores = [];
        foreach ($stores as $store) {
            switch ($store['FINAL CYCLE']) {
                case 'weekly':
                    $weeklyStores[] = $store;
                    break;
                case 'biweekly':
                    $biweeklyStores[] = $store;
                    break;
                case 'monthly':
                    $monthlyStores[] = $store;
                    break;
            }
        }

        // Step 3: Group stores by distance using Haversine calculation
        $hqCoordinates = ['lat' => -7.9826, 'lng' => 112.6308]; // HQ Coordinates

        $stores = array_map(function($store) use ($hqCoordinates) {
            $store['distance'] = $this->haversineDistance($hqCoordinates['lat'], $hqCoordinates['lng'], $store['Latitude'], $store['Longitude']);
            return $store;
        }, $stores);

        // Sort the stores by distance from HQ (nearest first)
        usort($stores, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // Step 4: Assign stores to sales reps, distribute evenly
        $salesReps = [];
        $totalSalesReps = 10; // Number of sales reps
        for ($i = 1; $i <= $totalSalesReps; $i++) {
            $salesReps['Sales' . $i] = [];
        }

        // Step 5: Schedule visits with equal distribution
        $daysInMonth = 31;
        $storesPerDay = 30;
        $currentDay = 1;

        // Initialize sales rep round-robin counter
        $currentRep = 1;

        foreach ($stores as $store) {
            $salesRep = 'Sales' . $currentRep;

            // If the current sales rep has visited 30 stores in the current day, move to the next day
            if (count($salesReps[$salesRep][$currentDay] ?? []) >= $storesPerDay) {
                $currentDay++;
                if ($currentDay > $daysInMonth) {
                    $currentDay = 1; // Reset to the first day of the month if we go over 31 days
                }
            }

            // Assign the store to the current sales rep for the current day
            $salesReps[$salesRep][$currentDay][] = $store['Name'];

            // Move to the next sales rep in round-robin
            $currentRep++;
            if ($currentRep > $totalSalesReps) {
                $currentRep = 1; // Reset to the first sales rep
            }
        }

        // Step 6: Output the routing plan for each sales rep
        Excel::store(new RoutingPlanExport($salesReps), 'routing_plan.xlsx');
        $this->info('Routing plan generated and saved to storage.');
    }

// Haversine Distance Calculation
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; // Distance in kilometers

        return $distance;
    }
}


