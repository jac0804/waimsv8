<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class replenish
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Replenishment Form';
  public $gridname = 'voidgrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = false;
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function createTab($config)
  {
    $allowapprove = $this->othersClass->checkAccess($config['params']['user'], 5089);
    $columns =['dateid','rem', 'ref','amount', 'deduction','itemname' ];

    foreach ($columns as $key => $value) {
          $$value = $key;
      }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Particulars';
    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ref]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$deduction]['type'] = 'label'; 
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label'; 
    
    $obj[0][$this->gridname]['columns'][$rem]['style']  = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$ref]['style']  = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$amount]['style']  = 'text-align:right; align:text-right; width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$deduction]['style']  = 'text-align:right; align:text-right; width:120px;whiteSpace: normal;min-width:120px;';
   
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [];
    $col1 = [];
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $qry = " ";
    $data = [];
    return [];
  }

  public function data($config)
  {
    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];
    }else{
      $trno= $config['params']['clientid'];
    }

    $center =$config['params']['center'];

    $qry = "select concat(head.trno,d.line) as keyid,date(head.dateid) as dateid,head.trno,d.line,d.rem,d.ref,format(d.amount,2) as amount,format(d.deduction,2) as deduction,
    case when d.isreplenish = 1 then 'true' else 'false' end as isreplenish
    from tcdetail as d
    left join tchead as head on head.trno=d.trno
    left join transnum as num on num.trno = head.trno
    where head.doc='TC' and d.isreplenish =0 and num.center =?
    union all
    select  concat(head.trno,d.line) as keyid,date(head.dateid) as dateid,head.trno,d.line,d.rem,d.ref,format(d.amount,2) as amount,format(d.deduction,2) as deduction,
    case when d.isreplenish = 1 then 'true' else 'false' end as isreplenish
    from htcdetail as d
    left join htchead as head on head.trno=d.trno
    left join transnum as num on num.trno = head.trno
    where head.doc='TC' and d.isreplenish =0 and num.center =? ";
    $data = $this->coreFunctions->opentable($qry,[$center,$center]);
    return $data;
  } //end function

  public function loaddata($config)
  {
    $rows = $config['params']['rows'];
    $user = $config['params']['user'];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $trno= $config['params']['trno'];

    $isposted = $this->othersClass->isposted2($trno,"transnum");

    if ($isposted){
      return ['status' => false, 'msg' => 'Cannot replenish, transaction already posted.'];
    }
    
    $runningbal =0;
    $end = $this->coreFunctions->getfieldvalue("tchead","endingbal","trno=?",[$rows[0]['trno']]);
    // if ($begbal!=0){
    //     $runningbal = $begbal;
    // }

    foreach ($rows as $key) {
      $config['params']['row']['trno'] = $rows[0]['trno'];
      $this->coreFunctions->execqry('update tcdetail set isreplenish=1,replenishdate=now() where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
      $this->coreFunctions->execqry('update htcdetail set isreplenish=1,replenishdate=now() where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
      $amount = $this->othersClass->sanitizekeyfield('amt',  $key['amount']);
      $deduction = $this->othersClass->sanitizekeyfield('amt',  $key['deduction']);
      $runningbal = $runningbal +($deduction  - $amount);
    }

    $runningbal = $this->othersClass->sanitizekeyfield('amt', $runningbal);

    $line = $this->coreFunctions->getfieldvalue("tcdetail","max(line)+1","trno=?",[$trno]);

    $r['trno']=$trno;
    $r['line']=$line;
    $r['amount']=$this->othersClass->sanitizekeyfield('amt', $runningbal);
    $r['deduction'] = 0;
    $r['isreplenish'] = 1;
    $r['rem'] = 'Replenishment';
    $r['ref'] = 'Replenishment';
    $r['encodedby']=$config['params']['user'];
    $r['encodeddate']=$current_timestamp;

    $this->coreFunctions->sbcinsert("tcdetail",$r);
    $this->coreFunctions->sbcupdate("tchead",["endingbal"=> $runningbal+$end],["trno" => $trno]);

    $this->logger->sbcwritelog($trno, $config, 'REPLENISH', 'REPLENISH');

    $data = $this->data($config);
    return ['status' => true, 'msg' => 'Successfully updated.', 'data' => $data,'reloadhead' => true];
  } //end function

  
} //end class
