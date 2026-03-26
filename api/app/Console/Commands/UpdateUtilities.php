<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\posClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Carbon\Carbon;

class UpdateUtilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:utilities';
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
        $this->coreFunction = new coreFunctions;
        $this->posClass = new posClass;

        $params = ['companyid' => 0, 'pos' => true];

        $this->coreFunction->sbclogger("Start api extraction", 'DLOCK');

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        try {

            $processSyncing = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='SYNCING'");
            if ($processSyncing == '') {

                $syncing = ['doc' => 'IOU', 'psection' => 'SYNCING', 'pvalue' => 1];
                $this->coreFunction->sbcinsert("profile", $syncing);

                $current_timestamp = date('Y-m-d H:i:s');

                $dlock = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='SDLOCK'");
                $this->coreFunction->LogConsole("Checking dlock " . $dlock);

                $this->coreFunction->sbclogger("Checking dlock " . $dlock, 'DLOCK');

                $this->coreFunction->sbclogger("Creating item file", 'DLOCK');
                $r = $this->posClass->itemlist();

                $this->coreFunction->sbclogger("Creating client file", 'DLOCK');
                $this->posClass->clientlist($params);

                $this->coreFunction->sbclogger("Creating pospaymentsetup file", 'DLOCK');
                $this->posClass->pospaymentsetup($dlock);

                $this->coreFunction->sbclogger("Creating pricescheme file (all)", 'DLOCK');
                $this->posClass->getpromotion();

                $this->coreFunction->sbclogger("Extract files", 'DLOCK');
                $this->posClass->ftpextractfiles();

                $this->coreFunction->sbclogger("Extract pending transactions (pos)", 'DLOCK');
                $this->posClass->extracttransactions($params);

                $this->coreFunction->LogConsole("Updating latest dlock " . $current_timestamp);
                $this->coreFunction->sbclogger("Updating latest dlock " . $current_timestamp, 'DLOCK');
                $data = ['doc' => 'IOU', 'psection' => 'SDLOCK', 'pvalue' => $current_timestamp];
                if ($dlock == '') {
                    $this->coreFunction->sbcinsert("profile", $data);
                } else {
                    $this->coreFunction->sbcupdate("profile", $data, ['doc' => 'IOU', 'psection' => 'SDLOCK']);
                }

                $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'SYNCING']);

                $this->coreFunction->execqry("delete from pos_log where e_detail='DLOCK' and date(date_executed)<'" . $currentdate . "'");
            } else {
                $lastlog = $this->coreFunction->datareader("select date_executed as value from pos_log where querystring<>'Start api extraction' and e_detail='DLOCK' order by e_id desc limit 1");
                if ($lastlog != '') {
                    $lastlog = Carbon::parse($lastlog);
                    $current_logtime = date('Y-m-d H:i:s');
                    $current_logtime =   Carbon::parse($current_logtime);

                    $idletime =  $lastlog->diffInMinutes($current_logtime, false);
                    if (abs($idletime) >= 30) {
                        $this->coreFunction->sbclogger("Reset extraction", 'DLOCK');

                        $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'SYNCING']);
                    }
                } else {
                    $this->coreFunction->sbclogger("Reset extraction no last log", 'DLOCK');

                    $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'SYNCING']);
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