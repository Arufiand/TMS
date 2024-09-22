<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Exception;
use League\Csv\Reader;
use Carbon\Carbon;
use League\Csv\UnavailableStream;


class GenerateRoutingPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-routing-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws UnavailableStream
     * @throws Exception
     */
    public function handle(): void
    {
        // Step 1: Load the stores from the CSV file
        $csv = Reader::createFromPath(storage_path('app/stores.csv'), 'r');
        $csv->setHeaderOffset(0); // First row is the header
        $stores = iterator_to_array($csv->getRecords());

        // Step 2: Organize stores by frequency (weekly, biweekly, monthly)
        $weeklyStores = [];
        $biweeklyStores = [];
        $monthlyStores = [];
        foreach ($stores as $store) {
            switch (strtolower($store['FINAL CYCLE'])) {
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

        // Step 3: Prepare sales reps
        $salesReps = [];
        for ($i = 1; $i <= 10; $i++) {
            $salesReps['Sales' . $i] = [];
        }

        // Step 4: Schedule visits (weekly: 4 visits, biweekly: 2 visits, monthly: 1 visit)
        $this->scheduleVisits($salesReps, $weeklyStores, 4);
        $this->scheduleVisits($salesReps, $biweeklyStores, 2);
        $this->scheduleVisits($salesReps, $monthlyStores, 1);

        // Step 5: Output the routing plan
        $this->outputRoutingPlan($salesReps);
    }

    /**
     * Schedule store visits for the given frequency
     */
    private function scheduleVisits(&$salesReps, $stores, $visitTimes): void
    {
        $startDate = Carbon::create(2024, 10, 1); // Starting date (October 1st, 2024)
        $day = 0;
        $salesIndex = 0;

        foreach ($stores as $store) {
            for ($i = 0; $i < $visitTimes; $i++) {
                $currentDay = $startDate->copy()->addDays($day);

                // Ensure Sundays are skipped (day 0 is Sunday)
                if ($currentDay->isSunday()) {
                    $day++;
                    $currentDay = $startDate->copy()->addDays($day);
                }

                // Assign stores to sales reps ensuring no more than 30 stores per day
                $currentSalesRep = 'Sales' . ($salesIndex % 10 + 1);

                if (!isset($salesReps[$currentSalesRep][$currentDay->format('Y-m-d')])) {
                    $salesReps[$currentSalesRep][$currentDay->format('Y-m-d')] = [];
                }

                if (count($salesReps[$currentSalesRep][$currentDay->format('Y-m-d')]) < 30) {
                    $salesReps[$currentSalesRep][$currentDay->format('Y-m-d')][] = $store;
                } else {
                    // Move to the next available day
                    $day++;
                    $i--; // Reattempt for this visit
                }

                $day++; // Increment day for each visit
            }
        }
    }

    /**
     * Output the routing plan
     */
    private function outputRoutingPlan($salesReps): void
    {
        foreach ($salesReps as $salesRep => $days) {
            $this->info($salesRep);
            foreach ($days as $date => $stores) {
                $this->info($date);
                foreach ($stores as $store) {
                    $this->info(' - ' . $store['Name'] . ' (' . $store['Code'] . ')');
                }
                $this->info(''); // Add a blank line after each day's schedule
            }
            $this->info('====================');
        }
    }
}
