<?php

namespace App\Http\Classes\modules\tableentry;

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

class issuemultipleexpiry
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LOC/EXPIRY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'rrstatus';
  private $othersClass;
  public $style = 'width:80%;max-width:80%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = [];
  public $showclosebtn = true;
  private $reporter;
  public $logger;
  private $sqlquery;
  private $reportheader;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0,
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $columns = ['itemdesc', 'qty','bal', 'loc', 'expiry', 'wh'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$loc]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$expiry]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$qty]['label'] = "Quantity";
    $obj[0][$this->gridname]['columns'][$loc]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$expiry]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$wh]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$bal]['type'] = "label";
    if(isset($config['params']['row']['pending'])){
      $this->modulename = "LOC/EXPIRY ( Pending Qty: " .$config['params']['row']['pending'] .")";
    }
    
    return $obj;
  }


  public function createtabbutton($config)
  {
   // $tableid = $config['params']['tableid'];
    $tbuttons = ['saveallentry'];
   
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'ACCEPT';
    $obj[0]['action'] = 'issuemultipleexpiry';
    $obj[0]['lookupclass'] = 'issuemultipleexpiry';
    return $obj;
  }


  public function add($config)
  {
    $trno = $config['params']['tableid'];
    $data = [];
    return $data;
  }

  private function selectqry()
  {
    $qry = ""; 
    return $qry;
  }

  public function saveallentry($config)
  {
    
  } // end function

  
  public function save($config)
  {
    $d = $config['params']['data'];
    $trno = $config['params']['tableid'];
    $data = array_filter($d, function($r) { return  $r['qty'] != 0; }); //gets only qty <>0
    $path = $this->getapppath($config['params']['doc']);
    $rows = [];
    $refx =0;
    $client ='';
    if (!empty($data)) {
      $refx = $data[0]['refx'];
      $client = $data[0]['client'];
      foreach ($data as $key2 => $value) {
        $config['params']['data']['uom'] = $data[$key2]['uom'];
        $config['params']['data']['itemid'] = $data[$key2]['itemid'];
        $config['params']['trno'] = $trno;
        $config['params']['data']['disc'] = $data[$key2]['disc'];
        $config['params']['data']['qty'] = $data[$key2]['qty'];
        $config['params']['data']['wh'] =$data[$key2]['wh'];
        $config['params']['data']['loc'] = $data[$key2]['loc'];
        $config['params']['data']['expiry'] = $data[$key2]['expiry'];
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $data[$key2]['refx'];
        $config['params']['data']['linex'] = $data[$key2]['linex'];
        $config['params']['data']['ref'] = (isset($data[$key2]['docno']) ? $data[$key2]['docno'] :'') ;
        $config['params']['data']['amt'] = $data[$key2]['amt'];

        $return = app($path)->additem('insert', $config);
        if ($return['status']) {
          if($data[$key2]['refx'] !=0){
            if (app($path)->setserveditems($data[$key2]['refx'], $data[$key2]['linex']) == 0) {
              $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
              app($path)->setserveditems($data[$key2]['refx'], $data[$key2]['linex']);
              $row = app($path)->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
          }
          
          //array_push($rows, $return['row'][0]);
        }
      } // end foreach
    } //end if

  $stock = app($path)->openstock($trno,$config);

    if($refx != 0){
      //condition per lookup
      $config['params']['client'] = $client;
      $lookupdata = $this->sqlquery->getpendingsodetailsperserial($config);
      return ['status' => true, 'msg'=> 'Success','closemodal' =>true , 'lookupdata'=>$lookupdata, 'reloadgriddata' => ['inventory' => $stock]];
    }else{
      return ['status' => true, 'msg'=> 'Success','closemodal' =>true,'reloadgriddata' => ['inventory' => $stock]];
    }
    
  } //end function

  public function delete($config)
  {
  }

  private function loaddataperrecord($trno, $line)
  {
    $data =[];
    return $data;
  }


  public function loaddata($config)
  {
    // var_dump($config);
    $company = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $itemid = $config['params']['row']['itemid'];
    $row = $config['params']['row'];
    $limit = '';
    $filtersearch = "";
    $searcfield = ['item.barcode','item.itemname'];
    $search = ''; 
    $latestprice=[];

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    $refx = 0;
    $linex = 0;
    $uom = '';
    $amt =0;
    $disc ='';
    $ref = '';
    $client ='';

    if (isset($row['docno'])){
      $ref= $row['docno'];
    }

    if (isset($row['refx'])){
      $refx= $row['refx'];
    }

    if (isset($row['linex'])){
      $linex= $row['linex'];
    }

    if (isset($row['uom'])){
      $uom= $row['uom'];
    }

    if (isset($row['amt'])){
      $amt= $row['amt'];
    }

    if (isset($row['disc'])){
      $disc= $row['disc'];
    }

    if (isset($row['client'])){
      $client= $row['client'];
    }

    if($refx == 0){
      $config['params']['barcode'] = $row['barcode'];
      $config['params']['client'] = $row['client'];      

      $path = $this->getapppath($doc);
      $latesprice = app($path)->getlatestprice($config);

      if(!empty($latesprice[0])){
        $disc = $latesprice[0]->disc;
        $amt = $latesprice[0]->amt;
        $uom = $latesprice[0]->uom;
      }
    }
    
    
    $qry = "select '".$client."' as client,$refx as refx, $linex as linex,'".$uom."' as uom,'".$disc."' as disc,".$amt." as amt,'".$ref."' as docno,item.itemid,item.barcode,item.itemname as itemdesc,0 as qty,rr.loc,rr.expiry,wh.client as wh,
    format(sum(bal),2) as bal from rrstatus as rr left join item on item.itemid = rr.itemid
    left join client as wh on wh.clientid = rr.whid
    where rr.itemid =? and rr.bal<>0  " . $filtersearch . " group by  item.barcode,item.itemname,wh.client,rr.loc,rr.expiry order by rr.expiry";

    $data = $this->coreFunctions->opentable($qry,[$itemid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
     
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  private function getapppath($doc)
  {
    switch($doc){
      case 'SO': case 'SJ': case 'CM':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        break;
      default:
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
      break;
    }
    return $path;
  }

  public function tableentrystatus($config)
  {
    return $this->save($config, true);
  }
  // // -> Print Function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    $paramstr = "select 
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";
    if ($config['params']['companyid'] == 8) { //maxipro
      $paramstr .= " , '$username' as prepared ";
    } else {
      $paramstr .= " ,'' as prepared ";
    }
    return $this->coreFunctions->opentable($paramstr);
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select line, category, reqtype from reqcategory
        order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $data = $this->report_default_query($config);
    $str = '';

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }




} //end loantype
