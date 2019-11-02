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
        $this->info('I am ready!');
        Storage::disk('local')->put('file.txt', 'Contents');
        try {
        $file = Storage::disk('local')->get('tabular_data.csv');
        } catch (FileException $e) {
            return false;
        }

        $Data = str_getcsv($file, "\n");
        $DataKey = $this->scvParcer($Data);

        $this->info(sizeof($DataKey));

       foreach($DataKey as $index=>$item) {

           if($index <= 3871) {
               $this->activityCouner($item["ID"]);//all records with 3 row, 1294 - 1
                $this->info('Have results for id: '.$item["ID"]);
            } else {
                break;
            }
        }
       dd($this->usersActivityCounter);
        return 1;
    }

    private function scvParcer($Data) {
        $header = [];
        $DataKey = [];
        foreach($Data as $index=>$row) {
            if(!$index) {
                $header = explode(',', $row);
            } else {
                $DataKey[] = array_combine($header,explode(',',$row));
            }
        }
        return $DataKey;
    }

    private function activityCouner($id) {
        if(array_key_exists($id,$this->usersActivityCounter)) {
            $this->usersActivityCounter[$id] = $this->usersActivityCounter[$id]+=1;
        } else {
            $this->usersActivityCounter[$id] = 1;
        }
    }
}
