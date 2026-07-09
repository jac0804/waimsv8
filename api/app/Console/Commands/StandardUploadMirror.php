<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\mirrorClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Carbon\Carbon;

class StandardUploadMirror extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:standarduploadmirror';
    private $coreFunction;
    private $mirrorClass;

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
        $this->mirrorClass = new mirrorClass;

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        $currenttime = date('Y-m-d H:i:s');
        if ($currenttime >= $currentdate . ' 06:00:00' && $currenttime < $currentdate . ' 20:00:00') {
            $this->coreFunction->sbclogger("No mirror within operating hours", 'MIRROR2');
            return;
        }

        $params = ['companyid' => 0];

        try {

            $processSyncing = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='MIRRORUPLOAD'");
            if ($processSyncing == '') {
                $this->coreFunction->sbclogger("Starting uploading files", 'MIRROR3');

                $syncing = ['doc' => 'IOU', 'psection' => 'MIRRORUPLOAD', 'pvalue' => 1];
                $this->coreFunction->sbcinsert("profile", $syncing);

                $this->coreFunction->sbclogger('uploading files', "MIRROR3");
                $this->mirrorClass->processMirrorFolder();

                $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRRORUPLOAD']);

                $this->coreFunction->execqry("delete from pos_log where e_detail in ('MIRROR3') and date(date_executed)<'" . $currentdate . "'");

                $this->coreFunction->sbclogger("File uploading completed", 'MIRROR3');
            } else {

                $this->coreFunction->sbclogger("Uploading process already running", 'MIRROR2');

                $lastlog = $this->coreFunction->datareader("select date_executed as value from pos_log where e_detail='MIRROR3' order by e_id desc limit 1");
                if ($lastlog != '') {
                    $lastlog = Carbon::parse($lastlog);
                    $current_logtime = date('Y-m-d H:i:s');
                    $current_logtime =   Carbon::parse($current_logtime);

                    $idletime =  $lastlog->diffInMinutes($current_logtime, false);
                    if (abs($idletime) >= 30) {
                        $this->coreFunction->sbclogger("Reset uploading process", 'MIRROR3');

                        $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRRORUPLOAD']);
                    }
                }
            }
        } catch (\Throwable $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->sbclogger('StandardUploadMirror - ' . $msg);
            $this->coreFunction->LogConsole($msg);

            // $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
        }
    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:utilities

}//end class