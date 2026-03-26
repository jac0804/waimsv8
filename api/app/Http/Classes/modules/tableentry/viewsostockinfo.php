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

use Carbon\Carbon;


class viewsostockinfo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEM LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  public $tablelogs_del = 'del_table_log';
  private $table = '';
  private $othersClass;
  public $style = 'width:100%;max-width:70%;';
  private $fields = [];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $doc = $config['params']['doc'];
    $company = $config['params']['companyid'];

    if ($doc == 'SK' && $company == 39) { //CBBSI

      $action = 0;
      $docno = 1;
      $dateid = 2;
      $ext = 3;


      $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno', 'dateid', 'ext']]];

      $stockbuttons = ['delete'];
      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

      $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    } else {
      $barcode = 0;
      $itemname = 1;
      $isqty = 2;
      $weight = 3;
      $weight2 = 4;
      $totalestweight = 5;
      $totalactualweight = 6;

      $tab = [$this->gridname => ['gridcolumns' => ['barcode', 'itemname', 'isqty', 'weight', 'weight2', 'totalestweight', 'totalactualweight']]];

      $stockbuttons = ['stockinfo'];
      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
      $obj[0][$this->gridname]['columns'][$weight2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

      $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$weight]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$isqty]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$weight2]['type'] = "input";
      $obj[0][$this->gridname]['columns'][$weight2]['readonly'] = false;

      $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item Name";
      $obj[0][$this->gridname]['columns'][$weight]['label'] = "Estimated Weight";

      $obj[0][$this->gridname]['columns'][$totalestweight]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
      $obj[0][$this->gridname]['columns'][$totalactualweight]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    }

    return $obj;
  }

  public function createtabbutton($config)
  {
    $doc = $config['params']['doc'];
    $company = $config['params']['companyid'];
    $trno = $config['params']['tableid'];
    switch ($config['params']['doc']) {
      case 'SJ':
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        break;
      case 'SO':
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        break;
      default:
        return [];
    }
    if ($isposted) {
      $tbuttons = [];
    } else {
      if ($doc == 'SK' && $company == 39) { //CBBSI
        $tbuttons = [];
      } else {
        $tbuttons = ['saveallentry'];
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($isposted) {
    } else {
      if ($doc == 'SK' && $company == 39) { //CBBSI
      } else {
        $obj[0]['access'] = 'whinfo';
      }
    }
    return $obj;
  }


  private function selectqry($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['tableid'];
    switch ($doc) {
      case 'SO':
        $sql = "select item.itemid, item.barcode, item.itemname, s.line, s.trno, s.weight, s.weight2,s.iss,s.isqty,(s.isqty*s.weight) as totalestweight,(s.isqty*s.weight2) as totalactualweight
        from sostock as s 
        left join item on item.itemid=s.itemid left join sohead as h on h.trno=s.trno
        where s.trno=? and h.lockdate is not null
        union all
        select item.itemid, item.barcode, item.itemname, s.line, s.trno, s.weight, s.weight2,s.iss,s.isqty,(s.isqty*s.weight) as totalestweight,(s.isqty*s.weight2) as totalactualweight
        from hsostock as s 
        left join item on item.itemid=s.itemid 
        where s.trno=?";
        break;
      case 'SJ':
        $sql = "select item.itemid, item.barcode, item.itemname, s.line, s.trno, sinfo.weight, sinfo.weight2,s.iss,s.isqty,(s.isqty*sinfo.weight) as totalestweight,(s.isqty*sinfo.weight2) as totalactualweight
        from lastock as s left join item on item.itemid=s.itemid left join lahead as h on h.trno=s.trno left join stockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
        where s.trno=? and h.lockdate is not null
        union all
        select item.itemid, item.barcode, item.itemname, s.line, s.trno, sinfo.weight, sinfo.weight2,s.iss,s.isqty,(s.isqty*sinfo.weight) as totalestweight,(s.isqty*sinfo.weight2) as totalactualweight
        from glstock as s left join item on item.itemid=s.itemid left join hstockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
        where s.trno=? 
        order by line";
        break;
      case 'SK': //CBBSI
        $sql = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
        FORMAT(sum(stock.ext),2) as ext,
        head.yourref,head.ourref
        from hsohead as head
        right join hsostock as stock on stock.trno = head.trno
        left join transnum on transnum.trno = head.trno
        left join client on client.client=head.client
        where transnum.center = '001' and transnum.sitagging= $trno
        and stock.void = 0
        group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref";
        break;
    }


    return $sql;
  }

  public function loaddata($config)
  {
    $qry = $this->selectqry($config);
    $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid'], $config['params']['tableid']]);
    return $data;
  }

  public function saveallentry($config)
  {
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'SO':
        $table = 'sostock';
        break;
      case 'SJ':
        $table = 'stockinfo';
        break;
    }
    foreach ($config['params']['data'] as $key => $value) {
      $update = [
        'editby' => $config['params']['user'],
        'editdate' => $this->othersClass->getCurrentTimeStamp(),
        'weight2' => $value['weight2']
      ];
      $this->coreFunctions->sbcupdate($table, $update, ['trno' => $value['trno'], 'line' => $value['line']]);
    }

    $data = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Data was refresh.', 'data' => $data];
  }


  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $company = $config['params']['companyid'];

    if ($doc == 'SK' && $company == 39) { //CBBSI
      $this->coreFunctions->LogConsole($config['params']['row']);
      $this->coreFunctions->LogConsole('zxczxczx');

      $qry = "update transnum set sitagging=0 where trno=?";
      $this->coreFunctions->execqry($qry, 'update', [$row['trno']]);
      $this->logger->sbcdelmaster_log($row['trno'], $config, 'REMOVE SO TAG - ' . $row['docno']);
      return ['status' => true, 'msg' => 'DELETE SO TAGGING SUCCESS...', 'reloadhead' => true];
    }
  }

  public function approveall($config)
  {

    return ['status' => true, 'msg' => 'Successfully done', 'data' => [], 'backlisting' => true];
  }
} //end class
