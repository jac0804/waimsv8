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

class editboq
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'List of Items';
  public $gridname = 'inventory';
  private $table = 'hsostock';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
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

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $barcode = 0;
    $itemname = 1;
    $uom = 2;
    $isqty = 3;
    $qa = 4;
    $tab = [$this->gridname => ['gridcolumns' => ['barcode', 'itemdesc', 'uom', 'isqty', 'qa']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$uom]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$qa]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "min-width:50px;";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "min-width:100px;";
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'SAVE';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.action', 'editboq');
    data_set($col1, 'refresh.label', 'SAVE');

    return array('col1' => $col1);
  }


  public function saveallentry($config)
  {
    $data = $config['params']['data'];

    foreach ($data as $key => $value) {
      if ($data[$key]['bgcolor'] != '') {
        if ($data[$key]['isqty'] < ($data[$key]['oqty'] - $data[$key]['qa'])) {
          $returndata = $this->loaddata($config);
          return ['status' => false, 'msg' => 'Cannot update, item quantity is already served.', 'data' => $data, 'reloaddata' => true];
        }
        $isqty = $this->othersClass->sanitizekeyfield("isqty", $data[$key]['isqty']);
        $this->coreFunctions->execqry("update " . $this->table . " set oqty=isqty where trno=? and line=?", 'update', [$data[$key]['trno'], $data[$key]['line']]);
        $this->coreFunctions->execqry("update " . $this->table . " set isqty=" . $isqty . ",iss=" . $isqty . " where trno=? and line=?", 'update', [$data[$key]['trno'], $data[$key]['line']]);
      } // end if
    } // foreach

    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloaddata' => true];
  } // end function  


  public function loaddata($config)
  {
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['tableid'];
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname as itemdesc,stock.uom,wh.client as wh,format(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,format(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as oqty,round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,'' as bgcolor from hsostock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and qa<>iss and void<>1 ";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  } //end function
































} //end class
