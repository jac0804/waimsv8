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

class viewserialhistory
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Serial History';
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

    if (isset($config['params']['clientid'])) {
      if ($config['params']['clientid'] != 0) {
        $itemid = $config['params']['clientid'];
        $item = $this->othersClass->getitemname($itemid);
        $this->modulename = $this->modulename . ' - ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;
      }
    }

    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['docno', 'dateid', 'client', 'listclientname', 'units']
      ]
    ];


    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';


    //client
    $obj[0][$this->gridname]['columns'][2]['label'] = 'WH Code';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    //listclientname
    $obj[0][$this->gridname]['columns'][3]['label'] = 'Serial';
    // listclientname
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    //units
    $obj[0][$this->gridname]['columns'][4]['label'] = 'Type';
    $obj[0][$this->gridname]['columns'][4]['align'] = 'text-left';

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
    $fields = ['wh', 'yourref'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'wh.lookupclass', 'whledger');
    data_set($col1, 'yourref.label', 'Enter Serial');
    data_set($col1, 'yourref.readonly', false);
    data_set($col1, 'yourref.maxlength', 40);



    $fields = ['update', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'update.action', 'perwh');
    data_set($col2, 'update.label', 'All Available Serial of this selected Warehouse');
    data_set($col2, 'refresh.action', 'history');
    data_set($col2, 'refresh.label', 'Search Specific Serial for all Warehouse');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $itemid = $config['params']['clientid'];
    $wh = $this->companysetup->getwh($config['params']);
    return $this->coreFunctions->opentable("select '$wh' as wh,'' as yourref");
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $wh = $config['params']['dataparams']['wh'];
    $serial = '';
    if (isset($config['params']['dataparams']['yourref'])) {
      $serial = $config['params']['dataparams']['yourref'];
    }
    if ($config['params']['action2'] == 'history') {
      $qry = "
        select rrstatus.dateid,rrstatus.docno,wh.client,serialin.serial as clientname,'IN' as units
        from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
        left join client as wh on wh.clientid=rrstatus.whid
        left join serialout on serialout.sline=serialin.outline
        where rrstatus.itemid=? and serialin.serial=?
        union all
        select lahead.dateid,lahead.docno,wh.client,serialout.serial as clientname,'OUT'
        from lahead left join lastock on lastock.trno=lahead.trno left join serialout on serialout.trno=lastock.trno and serialout.line=lastock.line
        left join client as wh on wh.clientid=lastock.whid
        where lastock.itemid=? and serialout.serial=?
        union all
        select lahead.dateid,lahead.docno,wh.client,serialout.serial as clientname,'OUT'
        from glhead as lahead left join glstock as lastock on lastock.trno=lahead.trno left join serialout on serialout.trno=lastock.trno and serialout.line=lastock.line
        left join client as wh on wh.clientid=lastock.whid
        where lastock.itemid=? and serialout.serial=?;
        
        ";
      $data = $this->coreFunctions->opentable($qry, [$itemid, $serial, $itemid, $serial, $itemid, $serial]);
    } else {
      $qry = "
        select rrstatus.dateid,rrstatus.docno,wh.client,serialin.serial as clientname,'IN' as units
        from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
        left join client as wh on wh.clientid=rrstatus.whid
        left join serialout on serialout.sline=serialin.outline
        where rrstatus.itemid=? and wh.client=? and serialin.outline=0 limit 60000
        ";
      $data = $this->coreFunctions->opentable($qry, [$itemid, $wh]);
    }

    $txtdata = $this->coreFunctions->opentable("select '$wh' as wh, '$serial' as yourref");

    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata];
  } //end function































} //end class
