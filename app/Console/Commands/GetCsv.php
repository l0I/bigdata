<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class GetCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:csv';

    /**
     * @var array
     */
    public $usersActivityCounter = [];

    public $usersRows = [];

    public $averagesByUser = [];

    public $usersBrutalSum = [];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        try {
            $file = Storage::disk('local')->get('tabular_data.csv');
        } catch (FileException $e) {
            return false;
        }
        $Data = str_getcsv($file, "\n");
        $DataKey = $this->scvParcer($Data);

        try {
            $trainFile = Storage::disk('local')->get('train_target.csv');
        } catch (FileException $e) {
            return false;
        }
        $TrainData = str_getcsv($trainFile, "\n");
        $WhoIdDaddy = [];
        foreach ($TrainData as $toCheck) {
            $asArr = explode(',', $toCheck);

            $WhoIdDaddy[$asArr[0]] = $asArr[1];
        }

        foreach ($DataKey as $index => $item) {
            if ($index <= 3871) {
                $this->activityGrouper($item);//almost all records with 3 row, 1294 - 1
                //$this->info('Have results for id: '.$item["ID"]);
            } else {
                break;
            }
        }

        foreach ($this->usersRows as $usersRows) {
            $arrayKeys = array_keys($usersRows[0]);
            $sumForEveryValueForUnicUser = [];
            $rowCounter = 0;
            foreach ($usersRows as $row) {
                $rowCounter += 1;
                foreach ($arrayKeys as $key) {
                    if ($key != "PERIOD" && $key != "ID") {
                        // check if for current user sum for $key started early
                        if (array_key_exists($key, $sumForEveryValueForUnicUser) && is_numeric($sumForEveryValueForUnicUser[$key])) {
                            $previousValue = $sumForEveryValueForUnicUser[$key];
                            if (is_numeric($row[$key])) {
                                $sumForEveryValueForUnicUser[$key] = $row[$key] + $previousValue;
                            } else {
                                $sumForEveryValueForUnicUser[$key] = $previousValue;
                            }

                        } else {
                            $sumForEveryValueForUnicUser[$key] = $row[$key];
                        }
                    }
                }
            }
            // have sum all rows for unic uses at last - get average now
            $arrayOfUsersAverages = [];
            foreach ($arrayKeys as $key) {
                if ($key != "PERIOD" && $key != "ID") {
                    $arrayOfUsersAverages[$key] = $sumForEveryValueForUnicUser[$key] != 0 ? $sumForEveryValueForUnicUser[$key] / $rowCounter : 0;
                }
            }
            $arrayOfUsersAverages["ID"] = $usersRows[0]["ID"];
            $this->averagesByUser[$usersRows[0]["ID"]] = $arrayOfUsersAverages;
        }

        // brutal sum
        $arrayKeys = array_keys($this->averagesByUser[1]);
        foreach ($this->averagesByUser as $usersAverage) {

            $brutalSum = 0;
            foreach ($arrayKeys as $key) {
                if ($key != "PERIOD" && $key != "ID" && is_numeric($usersAverage[$key])) {
                    $brutalSum += $usersAverage[$key];
                }

            }
            $this->usersBrutalSum[$usersAverage["ID"]] = $brutalSum;
        }

        // brutal to compare
        foreach ($this->usersBrutalSum as $key => $sumToCompare) {
            $this->info($WhoIdDaddy[$key] . ' - ' . $sumToCompare);
        }

        \
        return 1;
    }

    private function scvParcer($Data)
    {
        $header = [];
        $DataKey = [];
        foreach ($Data as $index => $row) {
            if (!$index) {
                $header = explode(',', $row);
            } else {
                $DataKey[] = array_combine($header, explode(',', $row));
            }
        }
        return $DataKey;
    }

    private function activityGrouper($item)
    {
        if (array_key_exists($item["ID"], $this->usersActivityCounter)) {
            $rowArray = $this->usersRows[$item["ID"]];
            $this->usersActivityCounter[$item["ID"]] = $this->usersActivityCounter[$item["ID"]] += 1;
        } else {
            $rowArray = [];
            $this->usersActivityCounter[$item["ID"]] = 1;
        }
        $rowArray[] = $item;
        $this->usersRows[$item["ID"]] = $rowArray;
    }


}
