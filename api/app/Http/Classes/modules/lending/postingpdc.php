<?php

namespace App\Http\Classes\modules\lending;

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
use Exception;



class postingpdc
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $logger;
  public $modulename = 'POST DATED CHECKS';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $fields = ['siref','prref', 'dateid', 'docno','clientname', 'amount'];
  public $loadtable = true;
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 224, 'save' => 224,
      'edititem' => 224
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $gridcolumns = ['action','prref','yourref', 'checkdate','checkno', 'docno','clientname', 'amount'];
    foreach ($gridcolumns as $key => $value) {
        $$value = $key;
      }

    $stockbuttons = ['save'];
    $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] ='LIST';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";
    $obj[0][$this->gridname]['columns'][$action]['btns']['save']['action'] = 'saveload';
    //$obj[0][$this->gridname]['columns'][$siref]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$prref]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$checkdate]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    //$obj[0][$this->gridname]['columns'][$siref]['readonly'] = false;
    //$obj[0][$this->gridname]['columns'][$siref]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$prref]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$checkdate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$checkno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';    

    $obj[0][$this->gridname]['columns'][$prref]['label'] = "AR#";
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Borrower';

    $obj[0][$this->gridname]['columns'][$yourref]['label'] = 'Application #';
    $obj[0][$this->gridname]['columns'][$yourref]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$yourref]['action'] = 'lookupappdocno';
    $obj[0][$this->gridname]['columns'][$yourref]['lookupclass'] = 'lookupappdocno';
    $obj[0][$this->gridname]['columns'][$yourref]['addedparams'] =['clientid'];
    
    return $obj;

  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = [];
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['dacnoname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dacnoname.lookupclass', 'lookupdepositto');
    return array('col1' => $col1);

  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
    select '' as dacnoname,'' as contra,'' as acnoname, 0 as acnoid");

    if (!empty($data)) {
      return $data[0];
    } else {
        return [];
    }

  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function stockstatus($config)
  {
    $action = $config['params']["action"];

    switch ($action) {
      case 'saveload':
        return $this->save($config);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    }
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case 'load':
        return $this->loadgrid($config);
        break;

      case 'saveallentry':
      case 'update':
        $this->save($config);
        return $this->loadgrid($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  public function loadgrid($config)
  {
    $center = $config['params']['center'];
    $qry = "select '' as siref,'' as prref,'' as yourref,0 as apptrno,date(head.dateid) as dateid,0 as principal,0 as otherinc,detail.trno,detail.line,
      head.docno, head.clientname, detail.checkno, format(ifnull(detail.amount,0),2) as amount,detail.checkdate,c.clientid,c.client
      from hrchead as head left join hrcdetail as detail on detail.trno = head.trno
      left join client as c on c.client=head.client
      left join transnum as num on num.trno=head.trno
      where detail.ortrno =0 and detail.checkdate <=now() and num.center='" . $center . "'
      order by head.dateid ";

    $data = $this->coreFunctions->opentable($qry);
    $this->coreFunctions->logconsole($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    
  }

  public function loaddata($config)
  {   
    $center = $config['params']['center'];
    $qry = "select '' as siref,'' as prref,'' as yourref,0 as apptrno,date(head.dateid) as dateid,0 as principal,0 as otherinc,detail.trno,detail.line,
      head.docno, head.clientname, detail.checkno, format(ifnull(detail.amount,0),2) as amount,detail.checkdate,c.clientid,c.client
      from hrchead as head left join hrcdetail as detail on detail.trno = head.trno
      left join client as c on c.client=head.client
      left join transnum as num on num.trno=head.trno
      where detail.ortrno =0 and detail.checkdate <=now() and num.center='" . $center . "'
      order by head.dateid ";

    $data = $this->coreFunctions->opentable($qry);
   return $data;
    
  }


  private function save($config)
  {
    $row =$config['params']['row'];
    $siref = $row['siref'];
    $prref = $row['prref'];
    
   
    if($prref == ''){
        return ['status' => false, 'msg'=> 'Please enter SI and AR number.'];
    }

    try{
      $this->coreFunctions->LogConsole('Create AR');
      $sicreate = $this->createSIAR($config,$row['apptrno'],$prref);
        if($sicreate['status'] == true){
          $ard = $sicreate['accounting'];
          $this->coreFunctions->execqry("update hrcdetail set ortrno = ".$ard[0]->trno ." where trno = ".$row['trno']." and line = ".$row['line'],"update");
          $d = $this->loaddata($config);          
          //$this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => $ard[0]->trno], ['trno' => $row['trno'], 'line' => $row['line']]);
          return ['status' => true, 'msg'=> 'Successfully apply to SD.', 'action' => 'load', 'griddata' => ['entrygrid' => $d], 'reloadgriddata'=>true];
          // $this->coreFunctions->LogConsole('Create SI');
          // $arcreate = $this->createSIAR($config,$row['apptrno'],"",$prref);
          // if($arcreate['status'] == true){
          //   $d = $this->loaddata($config);
          //   //$this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => $row['trno']], ['trno' => $row['trno'], 'line' => $row['line']]);
          //   return ['status' => true, 'msg'=> 'Successfully apply to SD.', 'action' => 'load', 'griddata' => ['entrygrid' => $d], 'reloadgriddata'=>true];
          // }else{
          //   return $arcreate;
          // }
        }else{
          return $sicreate;
        }

    }catch (Exception $e) {
        return ['status' => false, 'msg' => $e->getMessage()];
    }

  }

  private function createSIAR($config,$refx,$siref = "",$prref= ""){
    $row = $config['params']['row'];
    $user = $config['params']['user'];
    $pref = "AR";
    $seq = $siref;
    $amount = $row['amount'];
    $checkno = $row['checkno'];
    $checkdate = $row['checkdate'];
    $acno = $config['params']['headdata']['contra'];

  

    $path = 'App\Http\Classes\modules\lending\cr';
    // if($prref !=""){
    //     $pref = "AR";
    //     $seq = $prref;
    //    // $amount = $row['principal'];
    // }

    try{
      $trno = $this->othersClass->generatecntnum($config, "cntnum",'CR',$pref, 0, $seq, 'CR');
      if ($trno != -1) {
          $docno =  $this->coreFunctions->getfieldvalue("cntnum", 'docno', "trno=?", [$trno]);
  
          $head = ['trno' => $trno, 
                   'doc' => 'CR',
                   'docno' => $docno, 
                   'aftrno' => $refx,
                   'client' => $row['client'], 
                   'clientname' => $row['clientname'], 
                   'dateid' => date('Y-m-d'), 
                   'yourref' => $seq,
                   'ourref' => 'Cheque',
                   'amount' => $amount,
                   'contra' => $acno,
                   'checkno' => $checkno,
                   'checkdate' => $checkdate,
                   'createby' => $user,
                   'rctrno' => $row['trno'],
                   'rcline' => $row['line'],
                   'createdate'=> $this->othersClass->getCurrentTimeStamp()
                  ];

          foreach($head as $k => $value){
            $data[$k] = $this->othersClass->sanitizekeyfield($k,$head[$k]);
          }

          $inserthead = $this->coreFunctions->sbcinsert($this->head, $data);
          $config['params']['trno']=$trno;
          if($inserthead){
            $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - ' . $row['client'] . ' - ' . $row['clientname']);
            return app($path)->applytoar($config);
            
          }
  
                   
      }else{
        return $trno;
      }
    }catch (Exception $e) {
      return ['status' => false, 'msg' => $e->getMessage()];
    }

    
  }

  public function griddata($config)
  {
    $griddata = $this->loadgrid($config);
    return $griddata['griddata'];
  }

} //end class
