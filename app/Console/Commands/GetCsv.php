<?php

namespace App\Console\Commands;

use App\BigData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use phpDocumentor\Reflection\File;

class GetCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:csv';

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
        $file = Storage::disk('local')->get('data.csv');
        $Data = str_getcsv($file, "\n");
        $DataKey = $this->scvParcer($Data);
        dd($DataKey);

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

}
