<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\posClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Carbon\Carbon;

class UpdateUtilitiesHW extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:utilitieshw';
    private $coreFunction;
    private $posClass;

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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $this->coreFunction = new coreFunctions;
        $this->posClass = new posClass;

        $blnLogs = true;

        $this->coreFunction->LogConsole("homeworks setup...");
        $this->coreFunction->sbclogger("Start api extraction", 'DLOCK');

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        try {

            $processSyncing = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='SYNCING'");
            if ($processSyncing == '') {

                $syncing = ['doc' => 'IOU', 'psection' => 'SYNCING', 'pvalue' => 1];
                $this->coreFunction->sbcinsert("profile", $syncing);

                $params = ['companyid' => 56];

                $current_timestamp = date('Y-m-d H:i:s');
                $dlock = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='SDLOCK'");
                $this->coreFunction->LogConsole("Checking dlock " . $dlock);

                $this->coreFunction->sbclogger("Checking dlock " . $dlock, 'DLOCK');

                if ($blnLogs) $this->coreFunction->sbclogger("Creating item file", 'DLOCK');
                $this->coreFunction->LogConsole("Creating item file...");
                $r = $this->posClass->itemlist();

                if ($blnLogs) $this->coreFunction->sbclogger("Creating price list file", 'DLOCK');
                $this->coreFunction->LogConsole("Creating price list file...");
                $this->posClass->pricelist($dlock);

                if ($blnLogs) $this->coreFunction->sbclogger("Creating client file", 'DLOCK');
                $this->coreFunction->LogConsole("Creating client file...");
                $this->posClass->clientlist();

                if ($blnLogs) $this->coreFunction->sbclogger("Creating pospaymentsetup file", 'DLOCK');
                $this->coreFunction->LogConsole("Creating pospaymentsetup file...");
                $this->posClass->pospaymentsetup($dlock);

                $this->coreFunction->LogConsole("Updating latest dlock " . $current_timestamp);

                if ($blnLogs) $this->coreFunction->sbclogger("Updating latest dlock " . $current_timestamp, 'DLOCK');

                $data = ['doc' => 'IOU', 'psection' => 'SDLOCK', 'pvalue' => $current_timestamp];
                if ($dlock == '') {
                    $this->coreFunction->sbcinsert("profile", $data);
                } else {
                    $this->coreFunction->sbcupdate("profile", $data, ['doc' => 'IOU', 'psection' => 'SDLOCK']);
                }

                if ($blnLogs) $this->coreFunction->sbclogger("Creating pricescheme file", 'DLOCK');
                $this->coreFunction->LogConsole("Creating pricescheme...");
                $this->posClass->getpromotion_homeworks();

                if ($blnLogs) $this->coreFunction->sbclogger("Creating pricescheme file (all)", 'DLOCK');
                $this->coreFunction->LogConsole("Creating pricescheme...");
                $this->posClass->getpromotion_homeworks_all();

                if ($blnLogs) $this->coreFunction->sbclogger("Creating promo per item file", 'DLOCK');
                $this->coreFunction->LogConsole("Creating promo per item...");
                $this->posClass->getpromotion_pp_homeworks();

                if ($blnLogs) $this->coreFunction->sbclogger("Creating promo per item file (all)", 'DLOCK');
                $this->coreFunction->LogConsole("Creating promo per item...");
                $this->posClass->getpromotion_pp_homeworks_all();

                if ($blnLogs) $this->coreFunction->sbclogger("Extract files", 'DLOCK');
                $this->coreFunction->LogConsole("Extract files...");
                $this->posClass->ftpextractfiles();

                if ($blnLogs) $this->coreFunction->sbclogger("Extract pending transactions (pos)", 'DLOCK');
                $this->coreFunction->LogConsole("Extract pending transactions (pos)...");
                $this->posClass->extracttransactions($params);

                if ($blnLogs) $this->coreFunction->sbclogger("Done extraction", 'DLOCK');

                $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'SYNCING']);

                $this->coreFunction->execqry("delete from pos_log where e_detail='DLOCK' and date(date_executed)<'" . $currentdate . "'");

                // $current_timestamp2 = date('H');
                // $this->coreFunction->execqry("delete from pos_log where e_detail='DLOCK' and date(date_executed)='" . $currentdate . "' and hour(date_executed)<" . $current_timestamp2);
            } else {

                $lastlog = $this->coreFunction->datareader("select date_executed as value from pos_log order by e_id desc limit 1");
                if ($lastlog != '') {
                    $lastlog = Carbon::parse($lastlog);
                    $current_logtime = date('Y-m-d H:i:s');
                    $current_logtime =   Carbon::parse($current_logtime);

                    $idletime =  $lastlog->diffInMinutes($current_logtime, false);
                    if (abs($idletime) >= 30) {
                        if ($blnLogs) $this->coreFunction->sbclogger("Reset extraction", 'DLOCK');

                        $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'SYNCING']);
                    }
                }
            }
        } catch (Exception $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->sbclogger('UpdateUtilitiesHW - ' . $msg);
            $this->coreFunction->LogConsole($msg);

            $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'SYNCING']);
        }

        //$this->line('write file');
    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:utilities

}//end class