<?php

namespace App\Http\Classes\modules\payrollcustomform;

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

class pieceentry
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PIECE RATE ENTRY';
  public $gridname = 'entrygrid';
  public $head = 'piecetrans';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $fields = ['empname', 'dcode', 'dname', 'drate', 'dqty', 'daddon', 'damt', 'diqty'];
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;

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
      'view' => 1601,
      'save' => 1602,
      'edititem' => 1603,
      'deleteitem' => 1604,
      'print' => 1605,
      'saveallentry' => 1606,
      // 'new' => 24,
      // 'change' => 26,
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'dateid', 'empname', 'dname', 'dqty', 'drate', 'daddon', 'ext', 'batch'
      ]
    ]];

    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'PIECE RATE';

    $obj[0][$this->gridname]['columns'][2]['label'] = "Employee Name";
    $obj[0][$this->gridname]['columns'][8]['label'] = "Applied Batch";

    $obj[0][$this->gridname]['columns'][1]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][6]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][8]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][3]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][4]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][5]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][6]['align'] = 'text-left';

    return $obj;
  }

  public function createHeadbutton($config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['empid', 'empcode', 'empname', 'start', 'dcode', 'dname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.name', 'dateid');
    data_set($col1, 'start.label', 'Date');
    data_set($col1, 'dname.action', 'lookupdcode');
    data_set($col1, 'dname.lookupclass', 'lookupdcode');

    $fields = ['dqty', 'drate', 'daddon',];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dqty.type', 'cinput');
    data_set($col2, 'drate.type', 'cinput');
    data_set($col2, 'daddon.type', 'cinput');

    $fields = ['rem', 'create'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'rem.type', 'ctextarea');
    data_set($col3, 'rem.readonly', false);
    data_set($col3, 'create.label', 'CREATE PIECE RATE ENTRY');


    $fields = ['refresh'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'refresh.action', 'load');
    data_set($col4, 'refresh.label', 'SHOW ALL TRANSACTIONS');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      adddate(left(now(),10),-1) as dateid,
      '' as empcode,
      '' as empname,
      0 as empid,
      '' as dateid,
      '' as dcode,
      '' as dname,
      0 as dqty,
      0 as diqty,
      0 as drate,
      0 as daddon,
      0 as damt,
      '' as rem,
      '' as bgcolor
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

  private function selectqry()
  {
    $qry = "pt.line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function loaddata($config, $checkings = 0)
  {

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor , date(pt.dateid) as dateid, 
    e.empid, client.client as empcode, pt.damt as ext, b.batch, pt.batchid";
    $qry = "select " . $select . " from " . $this->head . " as pt
          left join employee as e on pt.empid = e.empid
          left join client on client.clientid=e.empid
          left join batch as b on b.line=pt.batchid
    order by pt.line";

    $data = $this->coreFunctions->opentable($qry);

    if ($checkings) {
      return [
        'clientid' => $checkings, 'status' => false, 'msg' => 'Already have transaction...',
        'action' => 'load',  'griddata' => ['entrygrid' => $data]
      ];
    } else {
      return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
  }

  public function headtablestatus($config)
  {

    $action = $config['params']["action2"];

    switch ($action) {
      case "load":
        return $this->loaddata($config);
        break;

      case 'create':

        $empid = $config['params']['dataparams']['empid'];

        if ($empid == 0) {
          return ['status' => false, 'msg' => 'Select valid employee', 'data' => []];
        }

        $head = $config['params']['dataparams'];
        $data = [];
        $fields = ['empid', 'empname', 'dcode', 'dname', 'drate', 'dqty', 'daddon', 'damt', 'dateid', 'rem', 'diqty'];
        foreach ($fields as $key) {
          $data[$key] = $head[$key];
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $data['damt'] = ($data['dqty'] * $data['drate']) + $data['daddon'];
        $data['damt'] = $this->othersClass->sanitizekeyfield('damt', $data['damt']);
        $this->coreFunctions->sbcinsert($this->head, $data);
        return $this->loaddata($config);
        break;

      case 'saveallentry':
        $this->savechanges($config);
        return $this->loaddata($config);
        break;

      case 'print':

        break;
    }
  }

  public function stockstatus($config)
  {
    $action = $config['params']["action"];

    switch ($action) {
      case 'deleteitem':
        $row = $config['params']['row'];
        $qry = "select batchid as value from piecetrans where empid=? and line=?";
        $count = $this->coreFunctions->datareader($qry, [$row['empid'], $row['line']]);

        if ($count) {
          return ['status' => false, 'msg' => 'Cannot delete, already used in payroll process'];
        } else {
          $this->delete($config);
          return ['status' => true, 'msg' => 'Successfully deleted.'];
        }
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    }
  }

  private function savechanges($config)
  {
    $rows = $config['params']['rows'];

    foreach ($rows as $k => $val) {
      if ($val["bgcolor"] != "") {
        unset($val["bgcolor"]);
        unset($val["empcode"]);
        $this->coreFunctions->sbcupdate("piecetrans", $val, ['line' => $val["line"]]);
      }
    }
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->head . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
  }
} //end class
