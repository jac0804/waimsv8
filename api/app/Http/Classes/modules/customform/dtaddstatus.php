<?php

namespace App\Http\Classes\modules\customform;

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

class dtaddstatus
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Add Status';
  public $gridname = 'editgrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:400px;max-width:400px;';
  public $issearchshow = false;
  public $showclosebtn = true;
  public $tablenum = 'docunum';



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
    $tab = [];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
    $fields = ['dtdetail', 'dtissue', 'dtrem', 'refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.action', 'savedtstatus');
    data_set($col1, 'refresh.label', 'Save');
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $id = $config['params']['row']['id'];
    $trno = $config['params']['row']['trno'];
    return $this->coreFunctions->opentable("select '' as dtrem, '' as dtdetail, 0 as dtdetailid, 0 as dtissueid, '' as dtissue, " . $id . " as id," . $trno . " as trno");
  }

  public function data($config)
  {
    return [];
  } //end function

  public function loaddata($config)
  {
    $params = $config['params'];
    $data = $params['dataparams'];

    $config['params']['trno'] = $data['trno'];
    unset($config['params']['clientid']);
    $isposted = $this->othersClass->isposted($config);
    if ($isposted) {
      $headtable = 'hdt_dthead';
      $stocktable = 'hdt_dtstock';
    } else {
      $headtable = 'dt_dthead';
      $stocktable = 'dt_dtstock';
    }

    $userid = $this->coreFunctions->getfieldvalue('useraccess', 'userid', 'username=?', [$params['user']]);
    $usertypeid = $this->coreFunctions->getfieldvalue('useraccess', 'accessid', 'username=?', [$params['user']]);
    $dateid = date('Y-m-d H:i:s');

    $line = 0;
    $line = $this->coreFunctions->datareader("select line as value from $stocktable where trno=? order by line desc limit 1", [$data['trno']]);
    if ($line == '') $line = 0;
    $line = $line + 1;

    $data2 = [
      'trno' => $data['trno'],
      'line' => $line,
      'userid' => $userid,
      'usertypeid' => $usertypeid,
      'dateid' => $dateid,
      'docstatusid' => $data['id'],
      'detailid' => $data['dtdetailid'],
      'issueid' => $data['dtissueid'],
      'rem' => $data['dtrem']
    ];
    if ($data2['trno'] == 0) {
      return ['status' => false, 'msg' => 'An error occurred; please try again.'];
    } else {
      if ($data2['detailid'] != 0 && $data2['issueid'] != 0) {
        if ($this->coreFunctions->sbcinsert($stocktable, $data2) == 1) {
          $this->coreFunctions->execqry("update $headtable set currentstatusid=?, currentdate=?, currentuserid=?, currentusertypeid=? where trno=?", 'update', [$data2['docstatusid'], $data2['dateid'], $data2['userid'], $data2['usertypeid'], $data2['trno']]);
          $rows = $this->othersClass->opendtstock($data['trno']);
          $txtdata = $this->coreFunctions->opentable("select '' as dtrem, '' as dtdetail, 0 as dtdetailid, 0 as dtissueid, '' as dtissue, {$data['id']} as id, {$data['trno']} as trno");
          return ['reloadgriddata' => ['inventory' => $rows], 'status' => true, 'msg' => 'Add status successfully.', 'txtdata' => $txtdata];
        } else {
          return ['status' => false, 'msg' => 'Add item failed'];
        }
      } else {
        return ['status' => false, 'msg' => 'Detail and Issue required.'];
      }
    }
  } //end function

































} //end class
