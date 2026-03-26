<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use Illuminate\Http\Request;
use App\Http\Requests;
use Carbon\Carbon;

class documentmanagementlookup {
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;

  public function __construct() {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
  }

  public function lookupusers($config) {
    $lookupsetup = [
      'type' => 'single',
      'title' => 'List of Users',
      'style' => 'width:900px;max-width:900px;'
    ];
    $plotsetup = [
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => ['userid'=>'userid', 'usertype'=>'usertype']
    ];
    $cols = [
      ['name' => 'usertype', 'label' => 'Name', 'align' => 'left', 'field' => 'usertype', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select idno as userid, username as usertype from users");
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }

  public function lookupdtdivision($config) {
    $lookupsetup = [
      'type' => 'single',
      'title' => 'Division List',
      'style' => 'width:900px;max-width:900px;'
    ];
    $plotsetup = [
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => ['dtdivid'=>'dtdivid', 'dtdivname'=>'dtdivname']
    ];
    $cols = [
      ['name'=>'dtdivname', 'label'=>'Division', 'align'=>'left', 'field'=>'dtdivname', 'sortable'=>true, 'style'=>'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select id as dtdivid, division as dtdivname from dt_division order by id");
    return ['status'=>true, 'msg'=>'ok', 'data'=>$data, 'lookupsetup'=>$lookupsetup, 'cols'=>$cols, 'plotsetup'=>$plotsetup];
  }

  public function lookupdtdocumenttype($config) {
    $lookupsetup = [
      'type' => 'single',
      'title' => 'Document Type List',
      'style' => 'width:900px;max-width:900px;'
    ];
    $plotsetup = [
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => ['doctypeid'=>'doctypeid', 'documenttype'=>'documenttype']
    ];
    $cols = [
      ['name'=>'documenttype', 'label'=>'Document Type', 'align'=>'left', 'field'=>'documenttype', 'sortable'=>true, 'style'=>'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select id as doctypeid, documenttype from dt_documenttype order by id");
    return ['status'=>true, 'msg'=>'ok', 'data'=>$data, 'lookupsetup'=>$lookupsetup, 'cols'=>$cols, 'plotsetup'=>$plotsetup];
  }

  public function lookupdtdetails($config) {
    $lookupsetup = [
      'type' => 'single',
      'title' => 'Detail List',
      'style' => 'width:900px;max-width:900px;'
    ];
    $plotsetup = [
      'plottype' => 'plotledger',
      'action' => '',
      'plotting' => ['dtdetailid'=>'dtdetailid', 'dtdetail'=>'dtdetail']
    ];
    $cols = [
      ['name'=>'dtdetail', 'label'=>'Detail', 'align'=>'left', 'field'=>'dtdetail', 'sortable'=>true, 'style'=>'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select id as dtdetailid, details as dtdetail from dt_details order by id");
    return ['status'=>true, 'msg'=>'ok', 'data'=>$data, 'lookupsetup'=>$lookupsetup, 'cols'=>$cols, 'plotsetup'=>$plotsetup];
  }

  public function lookupdtissues($config) {
    $lookupsetup = [
      'type' => 'single',
      'title' => 'Issue List',
      'style' => 'width:900px;max-width:900px;'
    ];
    $plotsetup = [
      'plottype' => 'plotledger',
      'action' => '',
      'plotting' => ['dtissueid'=>'dtissueid', 'dtissue'=>'dtissue']
    ];
    $cols = [
      ['name'=>'dtissue', 'label'=>'Detail', 'align'=>'left', 'field'=>'dtissue', 'sortable'=>true, 'style'=>'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select id as dtissueid, issues as dtissue from dt_issues order by id");
    return ['status'=>true, 'msg'=>'ok', 'data'=>$data, 'lookupsetup'=>$lookupsetup, 'cols'=>$cols, 'plotsetup'=>$plotsetup];
  }

  public function lookupdtstatus($config) {
    $trno = $config['params']['trno'];
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'id',
      'title' => 'Status List',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'statuslist'
    );
    $cols = [
      ['name'=>'statusdoc', 'label'=>'Status', 'align'=>'left', 'field'=>'statusdoc', 'sortable'=>true, 'style'=>'font-size:16px;']
    ];
    $userid = $this->coreFunctions->getfieldvalue('useraccess', 'accessid', 'username=?', [$config['params']['user']]);
    $data = $this->coreFunctions->opentable("select dt_status.id, dt_statuslist.status as statusdoc, dt_statuslist.alias as statusalias,
      $trno as trno from dt_status left join dt_statuslist on dt_statuslist.id=dt_status.statusdoc
      where userid=?", [$userid]);
    return ['status'=>true, 'msg'=>'ok', 'data'=>$data, 'lookupsetup'=>$lookupsetup, 'cols'=>$cols, 'plotsetup'=>$plotsetup];
  }

  public function lookupdtstatuslist($config) {
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'id',
      'title' => 'Status List',
      'style' => 'width:900px;max-width:900px;'
    );
    switch($config['params']['lookupclass']) {
      case 'lookupdtstatuslistrep':
        $plottype = 'plothead';
        $plotting = ['statusid'=>'statusid', 'dtstatus'=>'statusdoc'];
        break;
      default:
        $plottype = 'plotgrid';
        $plotting = ['statusid'=>'statusid', 'statusdoc'=>'statusdoc'];
        break;
    }
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    $cols = [
      ['name'=>'statusdoc', 'label'=>'Status', 'align'=>'left', 'field'=>'statusdoc', 'sortable'=>true, 'style'=>'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select id as statusid, status as statusdoc from dt_statuslist order by id");
    switch($config['params']['lookupclass']) {
      case 'lookupdtstatuslistrep':
        return ['status'=>true, 'msg'=>'ok', 'data'=>$data, 'lookupsetup'=>$lookupsetup, 'cols'=>$cols, 'plotsetup'=>$plotsetup];
        break;
      default:
        $index = $config['params']['index'];
        return ['status'=>true, 'msg'=>'ok', 'data'=>$data, 'lookupsetup'=>$lookupsetup, 'cols'=>$cols, 'plotsetup'=>$plotsetup, 'index'=>$index];
        break;
    }
  }

  public function lookuplevel($config) {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of User Level',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => ['dtuserlevel' => 'username']
    );
    // lookup columns
    $cols = [
      ['name' => 'username', 'label' => 'Username', 'align' => 'left', 'field' => 'username', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select idno as userid, username from users");
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

} // class
