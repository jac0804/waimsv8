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

class locationhisotry_fixasset_tab
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Location History';
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
    $empname = 0;
    $deptname = 1;
    $dateid = 2;
    $rem = 3;
    $listcreateby = 4;
    $createdate = 5;

    $columns = ['empname', 'deptname', 'dateid', 'rem', 'listcreateby', 'createdate'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$empname]['label'] = "Employee";
    $obj[0][$this->gridname]['columns'][$listcreateby]['label'] = "Created By";
    $obj[0][$this->gridname]['columns'][$createdate]['label'] = "Created On";

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
    $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $qry = "
      select issueitem.itemid, cl.clientname as empname,
      left(issueitem.dateid, 10) as dateid, issueitem.rem as rem,
      issueitem.createby, issueitem.createdate,
      loc.clientname as deptname
      from issueitem as issueitem
      left join client as cl on cl.clientid = issueitem.clientid
      left join client as loc on loc.clientid = issueitem.locid
      left join client as dept on dept.client = cl.wh
      left join issueitemstock as s on s.trno=issueitem.trno
      where date(issueitem.dateid) between '$start' and '$end' and s.itemid = '" . $itemid . "'
    ";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  } //end function



  public function getbal($config, $itemid, $wh, $uom)
  {
    $qry = "";


  } //end function




























} //end class
