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



class postingsd
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $logger;
  public $modulename = 'SALARY DEDUCTIONS';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $fields = ['siref','prref', 'dateid', 'docno','sbu','clientname', 'amount'];
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
    $gridcolumns = ['action','siref','prref', 'dateid', 'docno','sbu','clientname', 'amount'];
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
    $obj[0][$this->gridname]['columns'][$siref]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$prref]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$siref]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$siref]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$prref]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$sbu]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';    

    $obj[0][$this->gridname]['columns'][$prref]['label'] = "AR#";
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Borrower';
    
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
    $fields = ['checkno', 'checkdate','dacnoname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'checkno.readonly', false);
    data_set($col1, 'checkdate.readonly', false);
    data_set($col1, 'checkdate.type', 'date');
    //data_set($col1, 'refresh.action', 'load');
    data_set($col1, 'dacnoname.lookupclass', 'lookupdepositto');
    return array('col1' => $col1);

  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
    select '' as checkno,'' as checkdate,'' as dacnoname,'' as contra,'' as acnoname, 0 as acnoid");

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
    $qry = "select '' as siref,'' as prref,
      date(head.dateid) as dateid,
      head.docno,head.trno,
      c.clientname,c.client,
      format(ifnull(sum(head.db),0),2) as amount,ifnull(r.category,'')  as sbu,
      (select principal from htempdetailinfo where trno = app.trno limit 1) as principal,
      (select interest+pfnf+nf from htempdetailinfo where trno = app.trno limit 1) as otherinc
      from arledger as head left join cntnum as num on num.trno = head.trno
      left join heahead as app on app.trno = num.dptrno
      left join heainfo as info on info.trno = app.trno
      left join client as c on c.clientid=head.clientid
      left join reqcategory as r on r.line = info.sbuid
      where  num.dptrno<> 0 and head.dateid <=now() and head.bal <>0 and num.center='".$center."' and info.isselfemployed =1
      group by head.dateid,head.docno,head.trno,c.clientname,r.category,c.client,app.trno
      order by head.dateid ";

    $data = $this->coreFunctions->opentable($qry);
    $this->coreFunctions->logconsole($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    
  }

  public function loaddata($config)
  {
    $center = $config['params']['center'];
    $qry = "select '' as siref,'' as prref,
      date(head.dateid) as dateid,
      head.docno,head.trno,
      c.clientname,c.client,
      format(ifnull(sum(head.db),0),2) as amount,ifnull(r.category,'')  as sbu,
      (select principal from htempdetailinfo where trno = app.trno limit 1) as principal,
      (select interest+pfnf+nf from htempdetailinfo where trno = app.trno limit 1) as otherinc
      from arledger as head left join cntnum as num on num.trno = head.trno
      left join heahead as app on app.trno = num.dptrno
      left join heainfo as info on info.trno = app.trno
      left join client as c on c.clientid=head.clientid
      left join reqcategory as r on r.line = info.sbuid
      where  num.dptrno<> 0 and head.dateid <=now() and head.bal <>0 and num.center='".$center."' and info.isselfemployed =1
      group by head.dateid,head.docno,head.trno,c.clientname,r.category,c.client,app.trno
      order by head.dateid ";

    $data = $this->coreFunctions->opentable($qry);
   return $data;
    
  }


  private function save($config)
  {
    $row =$config['params']['row'];
    $siref = $row['siref'];
    $prref = $row['prref'];
    

    if($siref =='' || $prref == ''){
        return ['status' => false, 'msg'=> 'Please enter SI and AR number.'];
    }

    try{
      $sicreate = $this->createSIAR($config,$row['trno'],$siref,$prref);
        if($sicreate['status'] == true){
          $d = $this->loaddata($config);
          return ['status' => true, 'msg'=> 'Successfully apply to SD.', 'action' => 'load', 'griddata' => ['entrygrid' => $d], 'reloadgriddata'=>true];
          // $arcreate = $this->createSIAR($config,$row['trno'],"",$prref);
          // if($arcreate['status'] == true){
          //   $d = $this->loaddata($config);
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
    $pref = "SI";
    $seq = $siref;
    $amount = $row['amount'];
    $checkno = $config['params']['headdata']['checkno'];
    $checkdate = $config['params']['headdata']['checkdate'];
    $acno = $config['params']['headdata']['contra'];

    $path = 'App\Http\Classes\modules\lending\cr';
    // if($prref !=""){
    //     $pref = "AR";
    //     $seq = $prref;
    //    // $amount = $row['principal'];
    // }

    try{
      $trno = $this->othersClass->generatecntnum($config, "cntnum", 'CR',$pref, 0, $seq, 'CR');
      if ($trno != -1) {
          $docno =  $this->coreFunctions->getfieldvalue("cntnum", 'docno', "trno=?", [$trno]);
  
          $head = ['trno' => $trno, 
                   'doc' => 'CR',
                   'docno' => $docno, 
                   'aftrno' => $refx,
                   'client' => $row['client'], 
                   'clientname' => $row['clientname'], 
                   'dateid' => date('Y-m-d'), 
                   'yourref' => $prref,
                   'ourref' => 'Salary Deduction',
                   'amount' => $amount,
                   'contra' => $acno,
                   'checkno' => $checkno,
                   'checkdate' => $checkdate,
                   'createby' => $user,
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
