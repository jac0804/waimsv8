<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class approvebody
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  private $logger;
  public $modulename = 'Approving Body';
  public $gridname = 'customformacctg';
  private $fields = ['barcode'];
  private $table = 'item';

  public $info = 'eainfo';
  public $hinfo = 'heainfo';
  

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';

  public $style = 'width:50%;max-width:50%;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 4957, 'edit' => 4958);
    return $attrib;
  }

  public function createHeadField($config)
  {


    $fields = ['payrolltype','employeetype','expiration','loanlimit','loanamt','refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'expiration.readonly', false);
    data_set($col1, 'loanlimit.readonly', false);
    data_set($col1, 'loanamt.readonly', false);

    
    data_set($col1, 'refresh.label', 'Save');
    

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function cntnuminfo_qry($trno)
  {
    $qry = "
    select ".$trno." as trno, payrolltype,employeetype,expiration, loanlimit,loanamt from eainfo 
    where trno=$trno
    union all
    select ".$trno." as trno, payrolltype,employeetype,expiration, loanlimit,loanamt from heainfo 
    where trno=$trno";
    $data =$this->coreFunctions->opentable($qry);

    if(empty($data)){
      $data= $this->coreFunctions->opentable("
      select $trno as trno, '' as payrolltype,'' as employeetype,now() as expiration, '0' as loanlimit,'0' as loanamt");
    }

    return $data;
  }

  public function getheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $trno = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['dataparams']['itemid'];

    $data = $this->cntnuminfo_qry($trno);
    return $data;
  }

  public function data($config)
  {
    return [];
    
  }

  public function createTab($config)
  {
    $obj=[];
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function loaddata($config)
  {
    $trno  = $config['params']['dataparams']['trno'];
    $payrolltype  = $config['params']['dataparams']['payrolltype'];
    $employeetype  = $config['params']['dataparams']['employeetype'];
    $loanlimit  = $config['params']['dataparams']['loanlimit'];
    $loanamt  = $config['params']['dataparams']['loanamt'];
    $expiration  = $config['params']['dataparams']['expiration'];

     $qry = "update eainfo set 
      payrolltype = '".$payrolltype."',
      employeetype = '".$employeetype."',
      loanlimit = '".$loanlimit."',
      loanamt = '".$loanamt."',
      expiration = '".$expiration."' 
      where trno = '".$trno."'";
     $this->coreFunctions->execqry($qry,"update");

    $config['params']['clientid'] = $trno;
    $txtdata = $this->getheaddata($config);
    return ['status' => true, 'msg' => 'Successfully change barcode.','trno'=>$trno,'reloadhead'=>true,'txtdata' => $txtdata];
  }

 
}
