<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class inventoryhistory_employee_tab
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Inventory History';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $dateid = 0;
    $barcode = 1;
    $itemname = 2;
    $serialno = 3;
    $plateno = 4;

    $columns = ['dateid', 'barcode', 'itemname', 'serialno', 'plateno'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$serialno]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$plateno]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

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
    $fields = [['start', 'end']];
    $col1 = $this->fieldClass->create($fields);

    $fields = [['refresh']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'history');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $itemid = $config['params']['clientid'];
    return $this->coreFunctions->opentable("
      select 
      adddate(left(now(), 10),-360) as start,
      left(now(), 10) as end
    ");
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $qry = "
      select issueitem.itemid, item.barcode, item.itemname, cl.clientname as empname,
      left(issueitem.dateid, 10) as dateid, issueitem.rem as rem,
      iteminfo.serialno, iteminfo.plateno,
      issueitem.createby, issueitem.createdate,
      dept.clientname as deptname
      from issueitem as issueitem
      left join client as cl on cl.clientid = issueitem.clientid
      left join client as loc on loc.clientid = issueitem.locid
      left join client as dept on dept.client = cl.wh
      left join item as item on item.itemid = issueitem.itemid
      left join iteminfo as iteminfo on iteminfo.itemid = issueitem.itemid
      where date(issueitem.dateid) between '$start' and '$end' and issueitem.clientid = '" . $clientid . "'
    ";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  } //end function



  public function getbal($config, $itemid, $wh, $uom)
  {
    $qry = "";


  } //end function




























} //end class
