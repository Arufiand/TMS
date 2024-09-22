<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class StoreFrequencyExport implements FromArray
{
    protected array $storesByFrequency;

    public function __construct(array $storesByFrequency)
    {
        $this->storesByFrequency = $storesByFrequency;
    }

    public function array(): array
    {
        $header = ['Visit Frequency', 'Store'];

        $rows = [];
        foreach ($this->storesByFrequency as $frequency => $stores) {
            foreach ($stores as $store) {
                $rows[] = [$frequency, $store['Name']];
            }
        }

        return array_merge([$header], $rows);
    }
}
