<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\mirrorClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Collection;

use Exception;
use Carbon\Carbon;

class StandardDLMirror extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:standarddlmirror';
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

        try {

            $processSyncing = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='MIRROR'");

            if ($processSyncing == '') {
                $this->coreFunction->sbclogger("Starting database mirror", 'MIRROR');

                $syncing = ['doc' => 'IOU', 'psection' => 'MIRROR', 'pvalue' => 1];
                $this->coreFunction->sbcinsert("profile", $syncing);

                $this->coreFunction->sbclogger("Creating temp tables", 'MIRROR');
                $this->mirrorClass->createTempTables();

                $this->mirrorClass->ftpcreatefolder();
                $this->mirrorClass->ftpcreatefolder("downloaded");

                $this->coreFunction->sbclogger("Checking pending temp tables", 'MIRROR');
                if($this->mirrorClass->checkPendingTempTables()){

                    $this->coreFunction->sbclogger("Downloading files from ftp server", 'MIRROR');
                    $this->mirrorClass->downloadFromFtp();

                    $this->coreFunction->sbclogger("Extract files", 'MIRROR');
                    if ($this->mirrorClass->ftpextractmirrorfiles()) {
                        $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);

                        $this->coreFunction->execqry("delete from pos_log where e_detail in ('MIRROR','MIRROR2') and date(date_executed)<'" . $currentdate . "'");

                        $this->coreFunction->sbclogger("Database mirror completed", 'MIRROR');
                    }
                }else{
                    $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
                }

            } else {

                $this->coreFunction->sbclogger("Process already running", 'MIRROR2');

                $lastlog = $this->coreFunction->datareader("select date_executed as value from pos_log where e_detail='MIRROR' order by e_id desc limit 1");
                if ($lastlog != '') {
                    $lastlog = Carbon::parse($lastlog);
                    $current_logtime = date('Y-m-d H:i:s');
                    $current_logtime =   Carbon::parse($current_logtime);

                    $idletime =  $lastlog->diffInMinutes($current_logtime, false);
                    if (abs($idletime) >= 30) {
                        $this->coreFunction->sbclogger("Reset mirror after " . abs($idletime) . " minutes", 'MIRROR2');

                        $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
                    }
                }
            }
        } catch (Exception $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->sbclogger('UpdateUtilitiesMirror - ' . $msg);
            $this->coreFunction->LogConsole($msg);

            //$this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
        }

        // //$this->line('write file');

    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:utilities

// alter table useraccess modify column endtime varchar(20) default '00:00:00';
// alter table useraccess modify column starttime varchar(20) default '00:00:00';

// alter table useraccess_mirror modify column endtime varchar(20) default '00:00:00';
// alter table useraccess_mirror modify column starttime varchar(20) default '00:00:00';

}//end class