<?php

namespace App\Http\Classes\modules\ati;

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
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

use Carbon\Carbon;

class barcodeassigning
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Item Barcode Assigning';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $payrollcommon;
  public $style = 'width:100%;max-width:100%;';
  private $fields = [];
  public $issearchshow = true;
  public $showclosebtn = false;
  public $rowperpage = 0;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->payrollcommon = new payrollcommon;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 3887,
      'view' => 3887,
      'edititem' => 3887,
      // 'new' => 24,
      'save' => 3887,
      'saveallentry' => 3887,
      // 'change' => 26,
      // 'delete' => 27,
      'print' => 3887
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    $btns = []; //actionload - sample of adding button in header - align with form/module name
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['assigntempbarcode', 'downloadexcel', 'uploadexcel']; //'saveallentry', 
    $obj = $this->tabClass->createtabbutton($tbuttons);
    // $obj[1]['label'] = 'ASSIGN BARCODE';
    // $obj[1]['icon'] = 'check';
    $obj[0]['icon'] = 'view_list';
    $obj[0]['label'] = '';

    $obj[1]['icon'] = 'download';
    $obj[1]['label'] = '';

    $obj[2]['icon'] = 'upload';
    $obj[2]['label'] = '';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['moduledesc', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['rem', ['update', 'unmarkall']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'rem.label', 'Search');
    data_set($col2, 'rem.type', 'input');
    data_set($col2, 'rem.readonly', false);
    data_set($col2, 'update.label', 'MARK ALL');

    $fields = ['refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'load');

    $fields = ['barcode', 'blstockcard']; //, 
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'barcode.label', 'Assigned barcode');
    data_set($col4, 'barcode.lookupclass', 'lookupitem');

    data_set($col4, 'barcode.cleartxt', true);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      '' as moduledesc,
      '' as moduledoc,
      '' as barcode,
      0 as itemid,
      '' as uom,
      left(DATE(NOW()-INTERVAL 1 YEAR), 10) as `start`,
      left(now(), 10) as `end`,
      '' as rem
    ");

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

  public function createTab($config)
  {
    $otapproved = 0;
    $ctrlno = 1;
    $itemdesc = 2;
    $specs = 3;
    $unit = 4;
    $docno = 5;
    $dept = 6;
    $cust = 7;
    $barcode = 8;
    $tab = [$this->gridname => [
      'gridcolumns' => [
        'otapproved', 'ctrlno', 'itemdesc', 'specs', 'unit', 'docno', 'deptname', 'clientname', 'barcode'
      ],
      'rowperpage' => 0
    ]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'ITEMS';

    $obj[0][$this->gridname]['columns'][$otapproved]['label'] = 'Select';
    $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Item Description';

    $obj[0][$this->gridname]['columns'][$otapproved]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0][$this->gridname]['columns'][$specs]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$unit]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dept]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$cust]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
    return $obj;
  }


  public function headtablestatus($config, $line = '')
  {

    $action = $config['params']["action2"];

    switch ($action) {
      case "load":
        return $this->loadData($config);
        break;

      case 'saveallentry':
        $this->savechanges($config);
        return $this->loadData($config);
        break;

      case "update":
        return $this->loadData($config, 'true');
        break;

      case "unmarkall":
        return $this->loadData($config, 'false');
        break;


      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  private function loadData($config, $select = 'false')
  {
    ini_set('memory_limit', '-1');
    $action = $config['params']["action2"];

    $doc = $config['params']['dataparams']['moduledoc'];
    $itemid = $config['params']['dataparams']['itemid'];
    $searchtxt = $config['params']['dataparams']['rem'];
    $date1 = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $date2 = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    if ($doc == '') {
      return ['status' => false, 'msg' => 'Select valid module.', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
    }

    $filter = '';
    $searchfield = ["info.itemdesc", "info.specs", "h.docno", "dept.clientname", "h.clientname", "info.unit", "info.ctrlno"];
    if ($searchtxt != "") {
      $filter = $this->othersClass->multisearch($searchfield, $searchtxt, true);
    }


    switch ($doc) {
      case 'PR':
        $query = "select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'U' as status, info.isasset, dept.clientname as deptname, h.clientname, info.unit, info.ctrlno, item.othcode, s.trno as reqtrno, s.line as reqline
              from " . strtolower($doc) . "head as h
              left join " . strtolower($doc) . "stock as s on s.trno=h.trno
              left join stockinfotrans as info on info.trno=s.trno and info.line=s.line 
              left join headinfotrans as hinfo on hinfo.trno=h.trno
              left join item on item.itemid=s.itemid
              left join client as dept on dept.clientid=h.deptid
              left join transnum as num on num.trno=h.trno
              where h.doc='" . $doc . "'  and num.postdate is null and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and info.isselected=0 " . $filter . "
              union all
              select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'P' as status, info.isasset, dept.clientname as deptname, h.clientname, info.unit, info.ctrlno, item.othcode, s.trno as reqtrno, s.line as reqline
              from h" . strtolower($doc) . "head as h
              left join h" . strtolower($doc) . "stock as s on s.trno=h.trno
              left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line 
              left join item on item.itemid=s.itemid
              left join client as dept on dept.clientid=h.deptid
              left join transnum as num on num.trno=h.trno
              where h.doc='" . $doc . "'  and num.postdate is not null and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and info.isselected=0 " . $filter;

        break;
      case 'CD':
      case 'PO':
        $query = "select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'U' as status, info.isasset, dept.clientname as deptname, hpr.clientname, info.unit, info.ctrlno, item.othcode, s.reqtrno, s.reqline
              from " . strtolower($doc) . "head as h
              left join " . strtolower($doc) . "stock as s on s.trno=h.trno
              left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline 
              left join item on item.itemid=s.itemid
              left join hprhead as hpr on hpr.trno=info.trno
              left join client as dept on dept.clientid=hpr.deptid
              left join stockinfotrans as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
              left join transnum as num on num.trno=h.trno
              where h.doc='" . $doc . "' and num.postdate is null and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and sinfo.isselected=0 " . $filter . "
              union all
              select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'P' as status, info.isasset, dept.clientname as deptname, hpr.clientname, info.unit, info.ctrlno, item.othcode, s.reqtrno, s.reqline
              from h" . strtolower($doc) . "head as h
              left join h" . strtolower($doc) . "stock as s on s.trno=h.trno
              left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline 
              left join item on item.itemid=s.itemid
              left join hprhead as hpr on hpr.trno=info.trno
              left join client as dept on dept.clientid=hpr.deptid
              left join hstockinfotrans as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
              left join transnum as num on num.trno=h.trno
              where h.doc='" . $doc . "' and num.postdate is not null and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and sinfo.isselected=0 " . $filter . "";
        break;
      case 'RR':
        $query = "select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'U' as status, info.isasset, dept.clientname as deptname, hpr.clientname, info.unit, info.ctrlno, item.othcode, s.reqtrno, s.reqline
              from lahead as h
              left join lastock as s on s.trno=h.trno 
              left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
              left join item on item.itemid=s.itemid
              left join hprhead as hpr on hpr.trno=info.trno
              left join client as dept on dept.clientid=hpr.deptid
              left join stockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
              where h.doc='" . $doc . "' and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and sinfo.isselected=0" . $filter;
        break;

      case 'RR2':
        $query = "select s.trno, s.line, 'RR2' as doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, item.shortname as specs, item.uom, '" . $select . "' as otapproved, 'U' as status, info.isasset, dept.clientname as deptname, hpr.clientname, info.unit, info.ctrlno, item.othcode, s.reqtrno, s.reqline
              from lahead as h
              left join lastock as s on s.trno=h.trno 
              left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
              left join item on item.itemid=s.itemid
              left join hprhead as hpr on hpr.trno=info.trno
              left join client as dept on dept.clientid=hpr.deptid
              left join stockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
              where h.doc='RR' and s.itemid<>0 and left(item.barcode,3)='ITM' and item.barcode=item.othcode and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and sinfo.isselected2=0" . $filter . "
              union all
              select s.trno, s.line, 'RR2' as doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, item.shortname as specs, item.uom, '" . $select . "' as otapproved, 'U' as status, info.isasset, dept.clientname as deptname, hpr.clientname, info.unit, info.ctrlno, item.othcode, s.reqtrno, s.reqline
              from glhead as h
              left join glstock as s on s.trno=h.trno 
              left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
              left join item on item.itemid=s.itemid
              left join hprhead as hpr on hpr.trno=info.trno
              left join client as dept on dept.clientid=hpr.deptid
              left join hstockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
              where h.doc='RR' and s.itemid<>0 and left(item.barcode,3)='ITM' and item.barcode=item.othcode and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and sinfo.isselected2=0" . $filter;
        break;
    }


    $data = $this->coreFunctions->opentable($query);

    return ['status' => true, 'msg' => 'Successfully created.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  private function savechanges($config)
  {
    $itemid = $config['params']['dataparams']['itemid'];
    $uom = $config['params']['dataparams']['uom'];
    if ($itemid == 0) {
      return ['status' => false, 'msg' => 'Please select valid barcode.', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
    }

    $rows = $config['params']['rows'];
    foreach ($rows as $key => $value) {
      if ($value['otapproved'] == "true") {
        $table = '';
        if ($value['status'] == 'P') {
          $table = 'h';
        }
        switch ($value['doc']) {
          case 'PR':
            $table .= 'prstock';
            break;
          case 'CD':
            $table .= 'cdstock';
            break;
          case 'PO':
            $table .= 'postock';
            break;
          case 'RR':
            $table = 'lastock';
            break;
        }
        if ($table != '') {
          $this->coreFunctions->sbcupdate($table, ['itemid' => $itemid, 'uom' => $uom], ['trno' => $value['trno'], 'line' => $value['line']]);
        }
      }
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'downloadexcel':
        return $this->downloadexcel($config);
        break;
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
    }

    return ['status' => true];
  }


  private function downloadexcel($config)
  {
    $data = [];

    $selected = 'true';

    $result = array_filter($config['params']['data'], function ($item) use ($selected) {
      if (stripos($item['otapproved'], $selected) !== false) {
        return true;
      }
      return false;
    });

    // ItemCode	ItemDescription	Category	SubCategory	Brand	Model	Classification	Color	Size	PartNo	SupplierCode	SupplierName	UOM	Price1	Discount1	Specs	IsAsset	SKU	Department	SerialNo

    foreach ($result as $key => $value) {
      $col = [
        'ItemCode' => '',
        'ItemDescription' => $value['itemdesc'],
        'Category' => '',
        'SubCategory' => '',
        'Brand' => '',
        'Model' => '',
        'Classification' => '',
        'Color' => '',
        'Size' => '',
        'PartNo' => '',
        'SupplierCode' => '',
        'SupplierName' => '',
        'UOM' => '',
        'Price1' => '',
        'Discount1' => '',
        'Specs' => $value['specs'],
        'IsGeneric' => '',
        'IsAsset' => '',
        'SKU' => '',
        'Department' => '',
        'SerialNo' => '',
        'TrNo' => $value['trno'],
        'Line' => $value['line'],
        'Doc' => $value['doc'],
        'DocNo' => $value['docno'],
        'PRIsAsset' => $value['isasset'],
        'PRDepartment' => $value['deptname'],
        'PRCustomer' => $value['clientname']
      ];
      array_push($data, $col);
    }

    return ['status' => true, 'msg' => 'Ready to download.', 'name' => 'Barcode_Assigning', 'data' => $data, 'filename' => 'Barcode_Assigning'];
  }

  private function uploadexcel($config)
  {
    $rawdata = $config['params']['data'];
    foreach ($rawdata as $key => $value) {
      $trno = 0;
      $line = 0;
      $doc = '';
      $barcode = '';
      if (isset($value['TrNo'])) $trno = $value['TrNo'];
      if (isset($value['Line'])) $line = $value['Line'];
      if (isset($value['Doc'])) $doc = $value['Doc'];
      if (isset($value['ItemCode'])) $barcode = $value['ItemCode'];

      if ($trno == 0) return ['status' => false, 'msg' => 'Invalid trno reference.'];
      if ($line == 0) return ['status' => false, 'msg' => 'Invalid line reference.'];
      if ($doc == '') return ['status' => false, 'msg' => 'Invalid doc reference.'];
      if ($barcode == '') continue;

      $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$barcode], '', true);
      if ($itemid == 0) return ['status' => false, 'msg' => 'Itemcode ' . $barcode . " doesn`t exist."];

      $uom = $this->coreFunctions->getfieldvalue("item", "uom", "itemid=?", [$itemid]);

      $table = '';
      switch ($doc) {
        case 'PR':
        case 'CD':
        case 'PO':
          $isposted = $this->othersClass->isposted2($trno, "transnum");
          break;
        case 'RR':
          $isposted = $this->othersClass->isposted2($trno, "transnum");
          break;
      }
      if ($isposted) {
        $table = 'h';
      }

      switch ($doc) {
        case 'PR':
          $table .= 'prstock';
          break;
        case 'CD':
          $table .= 'cdstock';
          break;
        case 'PO':
          $table .= 'postock';
          break;
        case 'RR':
          $table = 'lastock';
          break;
      }

      if ($table != '') {
        $this->coreFunctions->LogConsole(json_encode($value));
        $this->coreFunctions->sbcupdate($table, ['itemid' => $itemid, 'uom' => $uom], ['trno' => $trno, 'line' => $line]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully uploaded.', 'action' => 'load'];
  }
} //end class
