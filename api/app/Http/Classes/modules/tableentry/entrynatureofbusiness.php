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

class entrynatureofbusiness
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'NATURE OF BUSINESS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'othermaster';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['businessnature', 'isbusinessnature'];
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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $clientname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['tableid']]);
    $action = 0;
    $businessnature = 1;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'businessnature']]];
    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$businessnature]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$action]['btns']['save']['checkfield'] = "isallowed";
    $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['checkfield'] = "isallowed";

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
    $data['businessnature'] = '';
    $data['isbusinessnature'] = 1;
    $data['bgcolor'] = 'bg-blue-2';
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
          $status = $this->coreFunctions->insertGetId($this->table, $data2);
          $config['params']['doc'] = strtoupper("natureofbusiness_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $config,
            ' CREATE - '
              . ', LINE: ' . $status
              . ', CLIENTID: ' . $data2['clientid']
              . ', BUSINESS NATURE: ' . $data2['businessnature']
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
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        $config['params']['doc'] = strtoupper("natureofbusiness_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $config,
          ' CREATE - '
            . ', LINE: ' . $line
            . ', CLIENTID: ' . $data['clientid']
            . ', BUSINESS NATURE: ' . $data['businessnature']
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
    $qry = "delete from " . $this->table . " where  clientid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['clientid'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function selectqry()
  {
    $select = ", line, businessnature, isbusinessnature, clientid";
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

    $qry = "select '' as bgcolor,case " . $access . " when 0 then 'true' else 'false' end as isallowed " . $select . " from " . $this->table . " where clientid=? and line=?";
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
    from " . $this->table . " where clientid=?";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupbusinessnature':
        return $this->lookupbusinessnature($config);
        break;
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

  public function lookupbusinessnature($config)
  {
    $rowindex = $config['params']['index'];
    $lookupsetup = [
      'type' => 'single',
      'title' => 'List of Business Nature',
      'style' => 'width: 900px;max-width:900px;'
    ];
    $plotsetup = [
      'plottype' => 'plotgrid',
      'plotting' => ['businessnature' => 'businessnature']
    ];
    $cols = [['name' => 'businessnature', 'label' => 'Business Nature', 'align' => 'left', 'field' => 'businessnature', 'sortable' => true, 'style' => 'font-size:16px;']];
    $qry = "select distinct businessnature from othermaster where isbusinessnature";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
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

    $qry = "
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
