<?php

namespace App\Http\Classes\modules\documentmanagement;

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
use App\Http\Classes\lookup\documentmanagementlookup;

class dt_statusaccess
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STATUS ACCESS LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'dt_status';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['userid', 'statusdoc', 'statusid', 'statussort'];
  public $showclosebtn = false;
  private $documentmanagementlookup;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->documentmanagementlookup = new documentmanagementlookup;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 2505];
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'usertype', 'statusdoc', 'statussort']]];
    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['id'] = 0;
    $data['userid'] = '';
    $data['usertype'] = '';
    $data['statusid'] = 0;
    $data['statusdoc'] = '';
    $data['statussort'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "id";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data2['userid'] = $data['userid'];
    $data2['statusdoc'] = $data['statusid'];
    $data2['statussort'] = $data['statussort'];
    if ($data2['statussort'] == '') $data2['statussort'] = 0;
    if ($row['id'] == 0) {
      $id = $this->coreFunctions->insertGetId($this->table, $data2);
      $this->logger->sbcmasterlog($row['id'], $config, ' CREATE - ' . $data['userid'] . ' - ' . $data['statusdoc']);
      if ($id != 0) {
        $returnrow = $this->loaddataperrecord($id);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['id' => $row['id']]) == 1) {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $returnrow = $this->loaddataperrecord($row['id']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function


  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where id=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['id']]);
    $this->logger->sbcdelmaster_log($row['id'], $config, 'REMOVE - ' . $row['userid'] . ' - ' . $row['statusdoc']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($id)
  {
    $qry = "select dt_status.id, dt_status.userid, dt_statuslist.status as statusdoc, dt_status.statusdoc as statusid, dt_status.statussort, users.username as usertype, '' as bgcolor from dt_status left join users on users.idno=dt_status.userid left join dt_statuslist on dt_statuslist.id=dt_status.statusdoc where dt_status.id=?";
    $data = $this->coreFunctions->opentable($qry, [$id]);
    return $data;
  }

  public function loaddata($config)
  {
    $qry = "select dt_status.id, dt_status.userid, dt_status.statusdoc as statusid, dt_statuslist.status as statusdoc, dt_status.statussort, users.username as usertype, '' as bgcolor from dt_status left join users on users.idno=dt_status.userid left join dt_statuslist on dt_statuslist.id=dt_status.statusdoc order by dt_status.userid, dt_status.statussort";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data3['userid'] = $data2['userid'];
        $data3['statusdoc'] = $data2['statusid'];
        $data3['statussort'] = $data2['statussort'];
        if ($data3['statussort'] == '') $data3['statussort'] = 0;
        if ($data[$key]['id'] == 0) {
          $id = $this->coreFunctions->insertGetId($this->table, $data3);
          $this->logger->sbcmasterlog($data[$key]['id'], $config, ' CREATE - ' . $data[$key]['userid'] . ' - ' . $data[$key]['statusdoc']);
        } else {
          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data3, ['id' => $data[$key]['id']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'lookupusers':
        return $this->documentmanagementlookup->lookupusers($config);
        break;
      case 'lookupdtstatuslist':
        return $this->documentmanagementlookup->lookupdtstatuslist($config);
        break;
      case 'whlog':
        return $this->lookuplogs($config);
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Status Access Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
