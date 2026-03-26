<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\posClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CDOSendSummaryreport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcsendemail:emailtransactionsummaryreport';
    private $coreFunctions;
    private $posClass;
    private $otherClass;

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
        $this->coreFunctions = new coreFunctions;
        $this->posClass = new posClass;
        $this->otherClass = new othersClass;
        date_default_timezone_set('Asia/Singapore');
        $current_timestamp = date('Y-m-d H:i:s');
        $currentdate = date('Y-m-d',strtotime($current_timestamp));
        $currenttime = date("H",strtotime($current_timestamp));
        $dlock = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='SDLOCK'");
        $dlockdate = date("Y-m-d",strtotime($dlock));
        //$this->coreFunctions->LogConsole("Checking dlock " . $dlock . "current time:". $current_timestamp);
       
        if($dlock == ''){
            $dlock = $current_timestamp;
            $dlockdate = date("Y-m-d",strtotime('-1 day',strtotime($dlock)));
            $data = ['doc' => 'IOU', 'psection' => 'SDLOCK', 'pvalue' => $dlock];
            $this->coreFunctions->sbcinsert("profile", $data);
            //$this->coreFunctions->LogConsole("Here...");
        }

        //$this->coreFunctions->LogConsole("Dates: " . $current_timestamp .' '.$currenttime. 'Dlock: '.$dlock .' '.$dlockdate);

        //$this->coreFunctions->LogConsole("Creating report file...");
       
        $classname = 'App\\Http\\Classes\\modules\\reportlist\\other_reports\\summary_of_transaction_report';
          
        $exfile =  app($classname)->reportdata();       
      

        //if($current_timestamp >= $dlock){
            //$this->coreFunctions->LogConsole("1");
       //     if($currentdate > $dlockdate){
                //$this->coreFunctions->LogConsole("2"); 
          //      if($currenttime >= 22){
              //      $this->coreFunctions->LogConsole("Updating latest dlock " . $current_timestamp .' '.$currenttime);
             //       $data = ['doc' => 'IOU', 'psection' => 'SDLOCK', 'pvalue' => $current_timestamp];                   
              //      $this->coreFunctions->sbcupdate("profile", $data, ['doc' => 'IOU', 'psection' => 'SDLOCK']);
                    
                    
               //     $info['email'] = 'cdo2cycles.agm39@gmail.com';//'jacalawod@gmail.com';//
               //     $info['subject'] = 'Transaction Summary';
               //     $info['title'] = 'Test';
              //      $info['view'] = 'emails.firstnotice';
               //     $info['msg'] = $exfile;
              //      $info['cc'] = ['erick0601@yahoo.com','jacalawod@gmail.com'];
                 
             //       return $this->otherClass->sbcsendemail([],$info);
             //   }
                
          //  }
            
      //  }
        
        	    $this->coreFunctions->LogConsole("Updating latest dlock " . $current_timestamp .' '.$currenttime);
                    $data = ['doc' => 'IOU', 'psection' => 'SDLOCK', 'pvalue' => $current_timestamp];                   
                    $this->coreFunctions->sbcupdate("profile", $data, ['doc' => 'IOU', 'psection' => 'SDLOCK']);
                    
                    
                    $info['email'] ='jacalawod@gmail.com';// ['cdo2cycles.agm39@gmail.com','acctgruel29@gmail.com','cdo2cycles.imd@gmail.com','cdo2cycles.shok.credit@gmail.com'];//
                    $info['subject'] = '2Cycles-AIMS Daily Updates'. date('m/d/Y',strtotime($current_timestamp));
                    $info['title'] = '2Cycles-AIMS Daily Updates'. date('m/d/Y',strtotime($current_timestamp));
                    $info['view'] = 'emails.firstnotice';
                    $info['msg'] = $exfile;
                   //$info['cc'] = ['erick0601@yahoo.com','jacalawod@gmail.com'];
                 
                    return $this->otherClass->sbcsendemail([],$info);

      
          //$this->config['reportdir'] = ['path' => $path, 'code' => $code];
        
        
    } // end function

}//end class