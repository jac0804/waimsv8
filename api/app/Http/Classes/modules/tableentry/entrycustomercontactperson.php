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
use App\Http\Classes\SBCPDF;

class entrycustomercontactperson
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CONTACT PERSON';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'contactperson';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['salutation', 'fname', 'mname', 'lname', 'email', 'contactno', 'bday', 'designation', 'department', 'mobile', 'activity', 'deptid'];
  public $showclosebtn = false;
  private $reporter;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
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
    $clientname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['tableid']]);
    $this->modulename = 'CONTACT PERSON - ' . $clientname;
    $action = 0;
    $salutation = 1;
    $fname = 2;
    $mname = 3;
    $lname = 4;
    $email = 5;
    $contactno = 6;
    $mobile = 7;
    $bday = 8;
    $department = 9;
    $designation = 10;
    $activity = 11;
    $deptname = 12;

    $tab = [$this->gridname => ['gridcolumns' => [
      'action', 'salutation', 'fname', 'mname', 'lname',
      'email', 'contactno', 'mobile', 'bday', 'department', 'designation', 'activity', 'deptname'
    ]]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$contactno]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$bday]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$department]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$mobile]['style'] = "width:150;whiteSpace: normal;min-width:150px;";

    $obj[0][$this->gridname]['columns'][$department]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$department]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$action]['btns']['save']['checkfield'] = "isallowed";
    $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['checkfield'] = "isallowed";


    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        if ($config['params']['companyid'] == 16) { //ati
          $obj[0][$this->gridname]['columns'][$deptname]['label'] = "Assigned Department";
          $obj[0][$this->gridname]['columns'][$deptname]['type'] = "label";
          $obj[0][$this->gridname]['columns'][$deptname]['readonly'] = true;
          $obj[0][$this->gridname]['columns'][$deptname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        } else {
          $obj[0][$this->gridname]['columns'][$deptname]['type'] = "coldel";
        }
        break;
      case 'SUPPLIER':
        if ($config['params']['companyid'] == 16) { //ati
          $obj[0][$this->gridname]['columns'][$deptname]['type'] = "coldel";
        }
        break;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $access = 1;
    $companyid = $config['params']['companyid'];
    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        if ($companyid == 16) { //ati
          $access = $this->othersClass->checkAccess($config['params']['user'], 2741);
        } else {
          $access = $this->othersClass->checkAccess($config['params']['user'], 23);
        }
        break;
      case 'SUPPLIER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 33);
        break;
    }
    if ($access == 0) {
      $tbuttons = ['masterfilelogs'];
    } else {
      $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['salutation'] = '';
    $data['fname'] = '';
    $data['mname'] = '';
    $data['lname'] = '';
    $data['email'] = '';
    $data['mobile'] = '';
    $data['contactno'] = '';
    $data['bday'] = null;
    $data['department'] = '';
    $data['deptname'] = '';
    $data['designation'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    $data['activity'] = '';

    if ($config['params']['companyid'] == 16 && $config['params']['doc'] == 'CUSTOMER') { //ati
      $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$config['params']['adminid']]);
      $data['deptid'] = $deptid;
    } else {
      $data['deptid'] = 0;
    }
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    unset($data['isallowed']);
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2], '', $companyid);
        }
        $data2['clientid'] = $tableid;
        if ($data[$key]['line'] == 0) {
          $exist = $this->coreFunctions->getfieldvalue($this->table, "line", "concat(fname,mname,lname) = ? and clientid = ?", [$data2['fname'] . $data2['mname'] . $data2['lname'], $tableid]);
          if (strlen(($exist)) != 0) {
            return ['status' => false, 'msg' => $data2['fname'] . ' ' . $data2['mname'] . ' ' . $data2['lname'] . ' already exist.', 'data' => []];
          }
          $status = $this->coreFunctions->insertGetId($this->table, $data2);

          $config['params']['doc'] = strtoupper("contactperson_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $config,
            ' CREATE - '
              . ', LINE: ' . $status
              . ', SALUTATION: ' . $data2['salutation']
              . ', FNAME: ' . $data2['fname']
              . ', MNAME: ' . $data2['mname']
              . ', LNAME: ' . $data2['lname']
              . ', EMAIL: ' . $data2['email']
              . ', CONTACT #: ' . $data2['contactno']
              . ', BDAY: ' . $data2['bday']
              . ', DEPT: ' . $data2['department']
              . ', DESIGNATION: ' . $data2['designation']
              . ', ACTIVITY: ' . $data2['activity']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['clientid' => $data2['clientid'], 'line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value], '', $companyid);
    }

    $data['clientid'] = $tableid;
    if ($row['line'] == 0) {
      $exist = $this->coreFunctions->getfieldvalue($this->table, "line", "concat(fname,mname,lname) = ? and clientid = ?", [$data['fname'] . $data['mname'] . $data['lname'], $tableid]);
      if (strlen(($exist)) != 0) {
        return ['status' => false, 'msg' => $data['fname'] . ' ' . $data['mname'] . ' ' . $data['lname'] . ' already exist.', 'data' => []];
      }
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);

        $config['params']['doc'] = strtoupper("contactperson_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $config,
          ' CREATE - '
            . ', LINE: ' . $line
            . ', SALUTATION: ' . $data['salutation']
            . ', FNAME: ' . $data['fname']
            . ', MNAME: ' . $data['mname']
            . ', LNAME: ' . $data['lname']
            . ', EMAIL: ' . $data['email']
            . ', CONTACT #: ' . $data['contactno']
            . ', BDAY: ' . $data['bday']
            . ', DEPT: ' . $data['department']
            . ', DESIGNATION: ' . $data['designation']
            . ', ACTIVITY: ' . $data['activity']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['clientid' => $row['clientid'], 'line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $tableid = $config['params']['tableid'];
    $row = $config['params']['row'];

    $exist = $this->coreFunctions->datareader("
                select trno as value from vrstock where shipcontactid=?
                union all
                select trno as value from hvrstock where shipcontactid=?", [$row['line'], $row['line']]);

    if ($exist) {
      return ['status' => false, 'msg' => 'Cannot delete, already used in transaction'];
    }


    $qry = "delete from " . $this->table . " where  clientid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['clientid'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function selectqry()
  {
    $select = ", cperson.line as line, cperson.clientid as clientid, cperson.salutation as salutation, 
    cperson.fname as fname, cperson.mname as mname, 
    cperson.lname as lname, cperson.email as email, 
    cperson.contactno as contactno, cperson.bday as bday,
    cperson.designation as designation,
    cperson.deptid as deptid,
    cperson.department as department,cperson.mobile,cperson.activity as activity, ifnull(dept.clientname, '') as deptname";

    return $select;
  }

  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();

    $access = 1;
    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        if ($config['params']['companyid'] == 16) { //ati
          $access = $this->othersClass->checkAccess($config['params']['user'], 2741);
        } else {
          $access = $this->othersClass->checkAccess($config['params']['user'], 23);
        }
        break;
      case 'SUPPLIER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 33);
        break;
    }

    $qry = "select '' as bgcolor,case " . $access . " when 0 then 'true' else 'false' end as isallowed " . $select . "
    from contactperson as cperson left join client as dept on dept.clientid=cperson.deptid
    where cperson.clientid = ? and cperson.line = ?";

    if ($config['params']['doc'] == 'CUSTOMER' && $config['params']['companyid'] == 16) { //ati
      $limitview = $this->othersClass->checkAccess($config['params']['user'], 3745);
      if ($limitview) {
        $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$config['params']['adminid']]);
        $qry .= " and cperson.deptid=" . $deptid;
      }
    }

    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];

    $access = 1;
    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        if ($config['params']['companyid'] == 16) { //ati
          $access = $this->othersClass->checkAccess($config['params']['user'], 2741);
        } else {
          $access = $this->othersClass->checkAccess($config['params']['user'], 23);
        }
        break;
      case 'SUPPLIER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 33);
        break;
    }

    $select = $this->selectqry();

    $qry = "select '' as bgcolor,case " . $access . " when 0 then 'true' else 'false' end as isallowed " . $select . "
    from contactperson as cperson left join client as dept on dept.clientid=cperson.deptid
    where cperson.clientid = ?";
    if ($config['params']['doc'] == 'CUSTOMER' &&  $config['params']['companyid'] == 16) { //ati
      $limitview = $this->othersClass->checkAccess($config['params']['user'], 3745);
      if ($limitview) {
        $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$config['params']['adminid']]);
        $qry .= " and cperson.deptid=" . $deptid;
      }
    }

    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupdept':
        return $this->lookupdept($config);
        break;
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      case 'lookupsalutation':
        return $this->lookupsalutation($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function lookupdept($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Department',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['deptid' => 'clientid', 'dept' => 'clientname']
    );

    $cols = array(
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select client, clientname, clientid from client
      where isdepartment = 1";

    $data = $this->coreFunctions->opentable($qry);

    return [
      'status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup,
      'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex
    ];
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper("contactperson_tab");

    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  private function lookupsalutation($config)
  {

    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Salutation',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['salutation' => 'salutation']
    );

    $cols = [
      ['name' => 'salutation', 'label' => 'Salutation', 'align' => 'left', 'field' => 'salutation', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select '' as salutation union all 
      select 'Mr.' as salutation
      union all 
      select 'Ms.' as salutation
      union all 
      select 'Mrs.' as salutation
      union all 
      select 'Engr.' as salutation
      union all 
      select 'Dr.' as salutation
      union all 
      select 'Atty.' as salutation";

    $data = $this->coreFunctions->opentable($qry);

    return [
      'status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup,
      'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex
    ];
  } //end function

  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }


  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
