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
        /*foreach ($this->usersBrutalSum as $key => $sumToCompare) {
            $this->info($WhoIdDaddy[$key] . ' - ' . $sumToCompare);
        }*/


//"V_4"
       /* foreach ($this->averagesByUser as $key => $usersAverage) {
            $this->info($WhoIdDaddy[$key] . ' - ' . $usersAverage["V_4"]);
        }*/
// V_5
        // V_10 just interesting
        foreach ($this->averagesByUser as $key => $usersAverage) {
            $this->info($WhoIdDaddy[$key] . ' - ' . $usersAverage["V_29"]);
        }
        $averageDaddy = [];
        $averageOther = [];
        $averageDaddyCounter = 0;
        $averageOtherCounter = 0;

        $arrayKeys = array_keys($this->averagesByUser[1]);
        foreach ($arrayKeys as $key) {
            $averageDaddy[$key]=0;
            $averageOther[$key]=0;
        }

        foreach ($this->averagesByUser as $index => $usersAverage) {
            foreach ($arrayKeys as $key) {
                if ($WhoIdDaddy[$index]) {
                    $averageDaddyCounter += 1;
                    $averageDaddy[$key] += $usersAverage[$key];
                } else {
                    $averageOtherCounter += 1;
                    $averageOther[$key] += $usersAverage[$key];
                }
            }

        }
        foreach ($arrayKeys as $key) {
            $this->info($key.": ".$averageDaddy[$key] / $averageDaddyCounter . ' - ' . $averageOther[$key] / $averageOtherCounter);
        }


        /*
         *
V_1: 0.51261916922029 - 0.4447486593009
                                        !V_2: 0.28903855975485 - 0.17175082380306
                                        !V_3: 0.043188202247191 - 0.025271370420624
V_4: 0.019503319713994 - 0.019121115203205
V_5: 0.0147791113381 - 0.013350455514635
V_6: 0.00075544773578481 - 0.0008641855656781
V_7: 0.0092781750085121 - 0.006340052981844
                                        !V_8: 0.0142151855635 - 0.0088356916715126
V_9: 0.001085291113381 - 0.00096918007365769
V_10: 0.51055498808308 - 0.42443222200685
V_11: 0.11587078651685 - 0.09207210699748
V_12: 2.3106550051073 - 1.869788961039
V_13: 10.336995020429 - 7.8261686292563
V_14: 9.8977530217909 - 7.4713337936939
V_15: 0.45765651600272 - 0.34026511113265
                                        !!!V_16: 0.0045214078992169 - 0.024936357175163
V_17: 0.0022814521620701 - 0.006215513342379
V_18: 0.070880469016003 - 0.050959851715449
V_19: 0.13645939734423 - 0.092859565807327
V_20: 0.026834354783793 - 0.019654164243717
V_21: 0.0023408239700375 - 0.0025844801964205
                                        !V_22: 50.798803948172 - 23.655516760754
V_23: 10.424134639939 - 8.0510545083027
V_24: 9.6266663474634 - 7.5962532306002
V_25: 0.51154717824311 - 0.37643341732894
V_26: 0.07028419731018 - 0.063718340117594
V_27: 0.0010910367722165 - 0.0012798830522711
                                        !V_28: 4.427636618999 - 2.7763697745041
                                        !V_29: 4.1243935137896 - 2.4361956451509
V_30: 0.25918241402792 - 0.20838179233702
                                        !V_31: 0.0029260299625468 - 0.0059766104542224
                                        !V_32: 4.017141215526 - 2.6172909801641
V_33: 3.7951353421859 - 2.3286772307295
V_34: 0.31121254681648 - 0.21748400852878
V_35: 0.01141683690841 - 0.010099664017574
V_36: 0.0010533707865169 - 0.0011226335853202
V_37: 1.8493114785495 - 1.4576960166699
V_38: 0.17040944310264 - 0.21226971138581
V_39: 0.24977655771195 - 0.13600827033663
                                        !V_40: 41.498397058265 - 21.608669833705
V_41: 1.7133708929179 - 1.3451501421464
                                        !!!V_42: 0.030856315968676 - 0.013709859791949
                                        !V_43: 6.1118659560138 - 2.3578098803668

         * */



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
