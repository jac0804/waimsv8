<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\modules\tableentry\ls;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Carbon\Carbon;

class ComputeLeave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:computeleave';
    private $coreFunction;
    private $othersClass;
    private $ls;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SBC Web Service';

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
        $this->coreFunction = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->ls = new ls;

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        try {

            $this->coreFunction->sbclogger("Auto compute leave", 'DLOCK');

            $config = [];

            $data = $this->ls->loaddata($config);

            $config['params']['companyid'] = 58;
            $config['params']['data'] =  json_decode(json_encode($data), true);
            $config['params']['currentdate'] = $this->othersClass->getCurrentDate();
            $config['params']['year'] = date('Y', strtotime($config['params']['currentdate']));
            $result = $this->ls->generateleave($config, true);

            $month = date('n', strtotime($config['params']['currentdate']));
            if ($month == 12) {
                $year =  $config['params']['year'];
                $config['params']['currentdate'] = date('Y-m-d', strtotime("$year-01-01" . ' +1 year'));
                $config['params']['year'] = date('Y', strtotime($config['params']['currentdate']));

                $this->coreFunction->sbclogger("Auto compute leave for the following year " . $config['params']['year'], 'DLOCK');
                $result = $this->ls->generateleave($config, true);
            }


            if ($result['status']) {
                $this->coreFunction->sbclogger("Finish compute leave", 'DLOCK');
            } else {
                $this->coreFunction->sbclogger("Failed compute leave", 'DLOCK');
            }

            $this->coreFunction->execqry("delete from pos_log where e_detail='DLOCK' and date(date_executed)<DATE_ADD('" . $currentdate . "', INTERVAL -7 DAY)");
        } catch (Exception $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->sbclogger('ComputeLeave - ' . $msg);
            $this->coreFunction->LogConsole($msg);
        }

        //$this->line('write file');
    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:utilities

}//end class