<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\mirrorClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Carbon\Carbon;

class StandardMirror extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:standardmirror';
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
        $this->coreFunction = new coreFunctions;
        $this->mirrorClass = new mirrorClass;

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        $currenttime = date('Y-m-d H:i:s');
        if($currenttime >= $currentdate . ' 06:00:00' && $currenttime < $currentdate . ' 20:00:00'){
            $this->coreFunction->sbclogger("No mirror within operating hours", 'MIRROR2');
            return;
        }

        $params = ['companyid' => 0];

        try {

            $processSyncing = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='MIRROR'");
            if ($processSyncing == '') {
                $this->coreFunction->sbclogger("Starting database mirror", 'MIRROR');

                $syncing = ['doc' => 'IOU', 'psection' => 'MIRROR', 'pvalue' => 1];
                $this->coreFunction->sbcinsert("profile", $syncing);

                $this->mirrorClass->ftpcreatefolder();
                $this->mirrorClass->ftpcreatefolder("uploaded");

                $this->mirrorClass->masterfilemirror("item", ["itemid"]);
                $this->mirrorClass->masterfilemirror("uom", ["itemid", "uom"]);
                $this->mirrorClass->masterfilemirror("iteminfo", ["itemid"]);

                $this->mirrorClass->masterfilemirror("client", ["clientid"]);
                $this->mirrorClass->masterfilemirror("clientinfo", ["clientid"]);

                $this->mirrorClass->masterfilemirror("model_masterfile", ["model_id"]);
                $this->mirrorClass->masterfilemirror("part_masterfile", ["part_id"]);
                $this->mirrorClass->masterfilemirror("stockgrp_masterfile", ["stockgrp_id"]);
                $this->mirrorClass->masterfilemirror("frontend_ebrands", ["brandid"]);
                $this->mirrorClass->masterfilemirror("item_class", ["cl_id"]);
                $this->mirrorClass->masterfilemirror("category_masterfile", ["cat_id"]);
                $this->mirrorClass->masterfilemirror("projectmasterfile", ["line"]);
                $this->mirrorClass->masterfilemirror("itemcategory", ["line"]);
                $this->mirrorClass->masterfilemirror("itemsubcategory", ["line"]);

                $this->mirrorClass->masterfilemirror("coa", ["AcnoID"]);
                $this->mirrorClass->masterfilemirror("useraccess", ["userid"]);
                $this->mirrorClass->masterfilemirror("users", ["idno"]);
                // // $this->mirrorClass->masterfilemirror("moduleaccess", ["idno"]);
                $this->mirrorClass->masterfilemirror("center", ["line"]);
                // // $this->mirrorClass->masterfilemirror("centeraccess", ["userid"]);

                $this->mirrorClass->masterfilemirror("ewtlist", ["line"]);
                $this->mirrorClass->masterfilemirror("terms", ["line"]);

                $this->coreFunction->sbclogger('uploading files', "MIRROR");
                $this->mirrorClass->processMirrorFolder();

                $this->mirrorClass->transactionsmirror("");

                $this->coreFunction->sbclogger('uploading files', "MIRROR");
                $this->mirrorClass->processMirrorFolder();

                $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);

                $this->coreFunction->execqry("delete from pos_log where e_detail in ('DLOCK','MIRROR') and date(date_executed)<'" . $currentdate . "'");

                $this->coreFunction->sbclogger("Database mirror completed", 'MIRROR');
            } else {

                $this->coreFunction->sbclogger("Process already running", 'MIRROR2');

                $lastlog = $this->coreFunction->datareader("select date_executed as value from pos_log where e_detail='MIRROR' order by e_id desc limit 1");
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
        } catch (\Throwable $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->sbclogger('UpdateStandardMirror - ' . $msg);
            $this->coreFunction->LogConsole($msg);

            // $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
        }
    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:utilities

}//end class