<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;
use App\Http\Classes\othersClass;
use App\Http\Classes\modules\tableentry\ls;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;
use Carbon\Carbon;

class UpdateDailyTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:updatedailytask';
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

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        try {

            $this->coreFunction->sbclogger("UpdateDailyTask running...", 'DLOCK');

            $this->coreFunction->execqry("update dailytask set statid=4, donedate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='AUTO', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where statid = 0");

            $this->coreFunction->execqry("insert into pendingapp (trno,doc,clientid) select trno,'DY',userid from dailytask where statid=4 and isneglect=0;");

            $this->coreFunction->execqry("update dailytask set isneglect=1 where isneglect=0 and statid=4");

            $this->coreFunction->execqry("delete from pos_log where e_detail='DLOCK' and date(date_executed)<'" . $currentdate . "'");
        } catch (Exception $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->LogConsole($msg);
        }

        //$this->line('write file');
    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:updatedailytask

}//end class