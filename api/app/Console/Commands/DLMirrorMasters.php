<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\posClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Carbon\Carbon;

class DLMirrorMasters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:dlmirrormasters';
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

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        try {

            $processSyncing = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='MIRROR'");
            if ($processSyncing == '') {

                $syncing = ['doc' => 'IOU', 'psection' => 'MIRROR', 'pvalue' => 1];
                $this->coreFunction->sbcinsert("profile", $syncing);

                $this->coreFunction->sbclogger("Mirror - Extract files", 'DLOCK');
                $this->coreFunction->LogConsole("Mirror - Extract files...");
                $this->posClass->ftpextractmirrorfiles();

                $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);

                $this->coreFunction->execqry("delete from pos_log where e_detail='DLOCK' and date(date_executed)<'" . $currentdate . "'");
            } else {

                $lastlog = $this->coreFunction->datareader("select date_executed as value from pos_log order by e_id desc limit 1");
                if ($lastlog != '') {
                    $lastlog = Carbon::parse($lastlog);
                    $current_logtime = date('Y-m-d H:i:s');
                    $current_logtime =   Carbon::parse($current_logtime);

                    $idletime =  $lastlog->diffInMinutes($current_logtime, false);
                    if (abs($idletime) >= 30) {
                        $this->coreFunction->sbclogger("Reset mirror", 'DLOCK');

                        $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
                    }
                }
            }
        } catch (Exception $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->sbclogger('UpdateUtilitiesMirror - ' . $msg);
            $this->coreFunction->LogConsole($msg);

            $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
        }

        //$this->line('write file');
    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:utilities

}//end class