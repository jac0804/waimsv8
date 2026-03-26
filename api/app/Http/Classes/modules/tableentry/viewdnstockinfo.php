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


class viewdnstockinfo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DR Return';
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
    return $obj;
  }

  public function createtabbutton($config)
  {
    $trno = $config['params']['tableid'];
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  private function selectqry($config)
  {
    $trno = $config['params']['tableid'];

    $sql = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
    FORMAT(sum(stock.ext),2) as ext,
    head.yourref,head.ourref
    from glhead as head
    right join glstock as stock on stock.trno = head.trno
    left join cntnum on cntnum.trno = head.trno
    left join client on client.clientid=head.clientid
    where head.doc in ('DN') and cntnum.center = '001' and cntnum.svnum= $trno
    and stock.void = 0
    group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref";


    return $sql;
  }

  public function loaddata($config)
  {
    $qry = $this->selectqry($config);
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $company = $config['params']['companyid'];

    if ($doc == 'SK' && $company == 39) { //CBBSI
      $this->coreFunctions->LogConsole('viewdnstockinfo delete ' . $this->othersClass->getCurrentTimeStamp());
      $this->coreFunctions->LogConsole($config['params']['row']);


      $qry = "update cntnum set svnum=0 where trno=?";
      $this->coreFunctions->execqry($qry, 'update', [$row['trno']]);
      $this->logger->sbcdelmaster_log($row['trno'], $config, 'REMOVE DN TAG - ' . $row['docno']);
      return ['status' => true, 'msg' => 'DELETE DN TAGGING SUCCESS...', 'reloadhead' => true];
    }
  }

  public function approveall($config)
  {

    return ['status' => true, 'msg' => 'Successfully done', 'data' => [], 'backlisting' => true];
  }
} //end class
