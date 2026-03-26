<?php

namespace App\Http\Classes\modules\fams;

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
use App\Http\Classes\SBCPDF;



class issueitems
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ISSUE ITEMS';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  public $reporter;
  private $fields = ['dateid', 'itemid', 'clientid', 'locid', 'rem',  'ispermanent', 'month', 'numdays', 'requesttype', 'repairtype', 'isrepair'];

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2909,
      'save' => 2909,
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $obj = [];
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
    $fields = ['dateid', 'itemname', 'serialno', 'client', 'whname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.label', 'Transaction Date');
    data_set($col1, 'dateid.type', 'input');
    data_set($col1, 'dateid.readonly', true);

    data_set($col1, 'type.action', 'lookuprandom');
    data_set($col1, 'type.lookupclass', 'lookup_transtype_issueitems');

    data_set($col1, 'itemname.type', 'lookup');
    data_set($col1, 'itemname.readonly', true);
    data_set($col1, 'itemname.action', 'lookup_items_issuitems');
    data_set($col1, 'itemname.lookupclass', 'lookup_items_issuitems');

    data_set($col1, 'client.lookupclass', 'employee_issueitem');
    data_set($col1, 'client.name', 'empname');
    data_set($col1, 'client.label', 'Employee');

    data_set($col1, 'whname.type', 'input');
    data_set($col1, 'whname.label', 'Location');

    $fields = ['rem', ['numdays', 'month'], ['ispermanent', 'isrepair']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'numdays.readonly', false);
    data_set($col2, 'month.label', "# of Months");
    data_set($col2, 'rem.type', 'ctextarea');
    data_set($col2, 'rem.readonly', false);

    $fields = [['requesttype', 'repairtype'], 'refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'save');
    data_set($col3, 'refresh.label', 'Issue Item');

    $fields = ['create'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'create.action', 'reset');
    data_set($col4, 'create.label', 'Clear fields');
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select 
      left(now(),10) as dateid,
      '' as itemname,
      0 as itemid,
      '' as empcode,
      '' as empname,
      0 as empid,
      '' as dept,
      '' as whname,
      0 as locid,
      '' as type,
      '' as rem,'' as serialno,
      0 as month,
      0 as numdays,
      '0' as ispermanent,
      '0' as isrepair,
      '' as requesttype,
      '' as repairtype
    ");

    return $data[0];
  }

  public function loaddata($config)
  {

    $qry = "
         select 
        left(now(),10) as dateid,
        '' as itemname,
        0 as itemid,
        '' as empcode,
        '' as empname,
        0 as empid,
        '' as dept,
        '' as whname,
        0 as locid,
        '' as type,
        '' as rem,'' as serialno,
        0 as month,
        0 as numdays,
        '0' as ispermanent ,
        '0' as isrepair,
        '' as requesttype,
        '' as repairtype
    ";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'data' => $data[0]];
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case 'load':
        return $this->paramsdata($config);
        break;
      case 'save':
        return $this->save($config);
        break;
      case 'reset':
        return $this->loaddata($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check headtablestatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function save($config)
  {
    $user = $config['params']['user'];

    $iteminfo = $this->coreFunctions->opentable("select * from iteminfo where itemid=?", [$config['params']['dataparams']['itemid']]);
    if (!empty($iteminfo)) {
      if ($config['params']['dataparams']['empid'] == $iteminfo[0]->empid && $config['params']['dataparams']['deptid'] == $iteminfo[0]->locid) {
        return ['status' => false, 'msg' => 'Already issued to ' . $config['params']['dataparams']['empname'], 'action' => 'load'];
      }
    }

    $data = [];

    $data['type'] = 'Issuance';
    $data['createby'] = $user;
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();

    $data['dateid'] = $config['params']['dataparams']['dateid'];
    $data['itemid'] = $config['params']['dataparams']['itemid'];
    $data['clientid'] = $config['params']['dataparams']['empid'];
    $data['locid'] = $config['params']['dataparams']['deptid'];
    $data['rem'] = $config['params']['dataparams']['rem'];
    $data['ispermanent'] = $config['params']['dataparams']['ispermanent'];
    $data['numdays'] = $config['params']['dataparams']['numdays'];
    $data['month'] = $config['params']['dataparams']['month'];
    $data['requesttype'] = $config['params']['dataparams']['requesttype'];
    $data['repairtype'] = $config['params']['dataparams']['repairtype'];
    $data['isrepair'] = $config['params']['dataparams']['isrepair'];

    foreach ($this->fields as $key) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if ($data['clientid'] == 0) {
      return ['status' => false, 'msg' => 'Please select valid employee.', 'action' => 'load'];
    }

    if ($data['locid'] == 0) {
      return ['status' => false, 'msg' => 'Please setup the location of the selected employee', 'action' => 'load'];
    }

    if ($data['itemid'] == 0) {
      return ['status' => false, 'msg' => 'Please select valid item.', 'action' => 'load'];
    }


    if ($data['ispermanent'] == 0) {
      if ($data['numdays'] == 0 && $data['month'] == 0) {
        return ['status' => false, 'msg' => 'Days/Months is required.', 'action' => 'load'];
      }
    }

    $this->coreFunctions->sbcinsert("issueitem", $data);

    $this->coreFunctions->sbcupdate("iteminfo", [
      'empid' => $config['params']['dataparams']['empid'],
      'locid' => $config['params']['dataparams']['deptid'],
      'issuedate' => $this->othersClass->getCurrentTimeStamp(),

    ], ['itemid' => $config['params']['dataparams']['itemid']]);

    return ['status' => true, 'msg' => 'Success Issue item', 'action' => 'load'];
  }
} //end class
