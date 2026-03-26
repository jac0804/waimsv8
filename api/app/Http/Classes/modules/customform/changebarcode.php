<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class changebarcode
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  private $logger;
  public $modulename = 'Change Barcode';
  public $gridname = 'customformacctg';
  private $fields = ['barcode'];
  private $table = 'item';
  

  public $tablelogs = 'item_log';
  public $tablelogs_del = 'del_table_log';

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
    $attrib = array('load' => 12, 'edit' => 13);
    return $attrib;
  }

  public function createHeadField($config)
  {

    $trno = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $label = "";

    switch (strtoupper($doc)){
      case 'STOCKCARD':
        $label ="Barcode";
        break;
      default:
        $label = "Doc#";
      break;
    }

    $this->modulename = "Change ".$label;
    $fields = ['barcode','refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'barcode.type', 'input');
    data_set($col1, 'barcode.label', $label);
    data_set($col1, 'barcode.readonly', false);

    
    data_set($col1, 'refresh.label', 'change');
    

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function cntnuminfo_qry($trno,$doc)
  {
    switch (strtoupper($doc)){
      case 'STOCKCARD':
        $qry = "
        select ".$trno." as itemid, barcode, barcode as obarcode from item 
        where itemid=?";
        break;
      case 'SJ':
      case 'CM':  
      case 'RR':  
      case 'DM':  
      case 'AJ':  
      case 'TS':  
      case 'IS':
      case 'AP':
      case 'PV':  
      case 'CV':  
      case 'AR': 
      case 'CR': 
      case 'GJ': 
      case 'DS': 
      case 'GD': 
      case 'GC': 
        $qry = "
        select ".$trno." as itemid, docno as barcode, docno as obarcode from cntnum
        where trno=?";
        break;
      case 'SO':
      case 'PO':
      case 'PC':
      case 'KR':
        $qry = "
        select ".$trno." as itemid, docno as barcode, docno as obarcode from transnum
        where trno=?";
        break;
      
    }
   
    $data =$this->coreFunctions->opentable($qry, [$trno]);

    if(empty($data)){
      $data= $this->coreFunctions->opentable("select  $trno as itemid,'' as barcode,'' as obarcode");
    }

    return $data;
  }

  public function getheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $trno = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['dataparams']['itemid'];
    $data = $this->cntnuminfo_qry($trno,$doc);
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
    $itemid  = $config['params']['dataparams']['itemid'];
    $barcode  = $config['params']['dataparams']['barcode'];
    $obarcode  = $config['params']['dataparams']['obarcode'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];

    switch (strtoupper($doc)){
      case 'STOCKCARD':
        $db = env('DB_DATABASE');
        $qry = "SELECT DISTINCT
        TABLE_NAME as tbl
        FROM
        INFORMATION_SCHEMA.COLUMNS
        WHERE
        COLUMN_NAME IN('barcode')
        AND TABLE_SCHEMA = '".$db."'";

        
        $tbls = $this->coreFunctions->opentable($qry);
        
        foreach($tbls as $k => $v){
        $qry = "update ".$tbls[$k]->tbl." set barcode = '".$barcode."' where barcode = '".$obarcode."'";
        $this->coreFunctions->execqry($qry,"update");
        }

        $config['params']['itemid'] = $itemid;
        $txtdata = $this->getheaddata($config);
        return ['status' => true, 'msg' => 'Successfully change barcode.','itemid'=>$itemid,'trno' => $itemid, 'reloadhead'=>true,'txtdata' => $txtdata];
        break;
      default:

        $path = '';
        
        switch (strtoupper($doc)) {
          case 'IS': case 'AJ': case 'TS': case 'PC':
            $path = 'App\Http\Classes\modules\inventory\\'.strtolower($doc);
            break;    
          case 'DM': case 'RR': case 'PO': case 'PR':
            $path = 'App\Http\Classes\modules\purchase\\'.strtolower($doc);
            break;
          case 'SJ': case 'SO':
            $folder = "sales";
            if($companyid == 60){//transpower
              $folder = "t70e33c92835b1ef8cd37fb7d031d02db";
            }
            $path = 'App\Http\Classes\modules\\'.$folder.'\\'.strtolower($doc);
            break;
          case 'CM':
            $path = 'App\Http\Classes\modules\sales\cm';
            break;
          case 'AP': case 'CV': case 'PV':
            $path = 'App\Http\Classes\modules\payable\\'.strtolower($doc);
            break;
          case 'CR':case 'KR': case 'AR':
            $path = 'App\Http\Classes\modules\receivable\\'.strtolower($doc);
            break;
          case 'DS': case 'GJ': case 'GD': case 'GC':
            $path = 'App\Http\Classes\modules\accounting\\'.strtolower($doc);
            break;
        }
        $docnolength = $this->companysetup->documentlength;
        $inputlength = strlen($barcode);
        $isposted = $this->othersClass->isposted2($itemid,app($path)->tablenum);

        if($isposted){
          $txtdata = $this->getheaddata($config);
          return ['status' => false, 'msg' => "Sorry, you can't changed this document because it is already Posted..",'itemid'=>$itemid,'trno' => $itemid, 'reloadhead'=>true,'txtdata' => $txtdata];
        }

        $newdocno = $this->othersClass->PadJ($barcode,$docnolength);
        $exist = $this->coreFunctions->datareader("select trno as value from ".app($path)->tablenum." where docno = '".$newdocno."'",[],'',true);

        if($exist){
          $txtdata = $this->getheaddata($config);
          return ['status' => false, 'msg' => 'Document # already exist.','itemid'=>$itemid,'trno' => $itemid, 'reloadhead'=>true,'txtdata' => $txtdata];
        }else{
          $pref = $this->othersClass->GetPrefix($newdocno);
          $seq = (substr($newdocno, $this->othersClass->SearchPosition($newdocno), strlen($newdocno)));
          $this->coreFunctions->execqry("update ".app($path)->tablenum." set bref ='".$pref."',seq =".$seq.",docno='".$newdocno."' where trno = ".$itemid);
          $this->coreFunctions->execqry("update ".app($path)->head." set docno='".$newdocno."',editdate = '".$this->othersClass->getCurrentTimeStamp()."',editby = '".$config['params']['user']."' where trno = ".$itemid);
          $this->logger->sbcwritelog($itemid,$config,"CHANGE DOC#","Change Doc# ".$obarcode." to ". $newdocno,app($path)->tablelogs);
          $txtdata = $this->getheaddata($config);
          return ['status' => true, 'msg' => 'Document # successfully changed.','itemid'=>$itemid,'trno' => $itemid, 'reloadhead'=>true,'txtdata' => $txtdata];
        }

      break;
    }

    
  
  }

 
}
