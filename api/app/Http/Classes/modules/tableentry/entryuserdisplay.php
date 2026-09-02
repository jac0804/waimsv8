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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryuserdisplay
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'User Display';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'userdisplay';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['userid', 'usergrp'];
  public $showclosebtn = false;
  private $reporter;
  public $logger;
  private $reportheader;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
    $this->reportheader = new reportheader;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 5721
    );
    return $attrib;
  }

  public function createTab($config)
  {

    $columns = [
      'action',
      'name',
      'groupname'
    ];

    foreach ($columns as $key => $value) {
      $$value = $key; //declare
    }

    $stockbuttons = ['save', 'delete'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$name]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$name]['lookupclass'] = 'lookupusers';
    $obj[0][$this->gridname]['columns'][$name]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$groupname]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$groupname]['lookupclass'] = 'lookupgroup';
    $obj[0][$this->gridname]['columns'][$groupname]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$groupname]['label'] = 'User Group';
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog']; // tab button
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config)
  {
    $searcfield = $this->fields;
    $filtersearch = "";
    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select distinct " . $select . " from " . $this->table . " left join useraccess as user on user.userid = userdisplay.userid 
    left join reqcategory as req on req.groupname = userdisplay.usergrp where 1 = 1 " . $filtersearch . " order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function selectqry()
  {
    $qry = "userdisplay.line, user.name, userdisplay.userid, userdisplay.usergrp, req.groupname";

    return $qry;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['name'] = '';
    $data['userid'] = 0;
    $data['groupname'] = '';
    $data['usergrp'] = '';
    $data['bgcolor'] = 'bg-green-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $companyid = $config['params']['companyid'];
    $dateTables = [$this->table];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    $data = $config['params']['data'];
    $qry = "Select max(line) as maxLine from  {$this->table}";
    $res = $this->coreFunctions->opentable($qry);
    $maxLine = (!empty($res) && !empty($res[0]->maxLine)) ? (int)$res[0]->maxLine : 0;
    foreach ($data as $key => $value) {
      $data2 = [];
      if (!empty($data[$key]['bgcolor'])) {
        foreach ($this->fields as $key2 => $field) {
          $value = isset($data[$key][$field]) ? $data[$key][$field] : null;
          $data2[$field] = $this->othersClass->sanitizekeyfieldFast($field, $value,$lookups);
        }
        if (empty(trim($data2['userid']))) {
          return ['status' => false, 'msg' => 'Saving failed. Please complete the empty userid.'];
        } elseif (empty(trim($data[$key]['groupname']))) {
          return ['status' => false, 'msg' => 'Saving failed. Please complete the empty user group.'];
        }
        $data2['usergrp'] = $data[$key]['groupname']; // FIX: map groupname display field to usergrp saved field
        $lineValue = isset($data[$key]["line"]) ? $data[$key]['line'] : 0;
        if ($lineValue === 0) {
          $maxLine++;
          $data2['line'] = $maxLine;
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog(
            $line,
            $config,
            ' CREATE - ' . (isset($data[$key]['userid']) ? $data[$key]['userid'] : '')
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['encodedby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate(
            $this->table,
            $data2,
            ['line' => $data[$key]['line']]
          );
        }
      }
    }
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved Successfully.', 'data' => $returndata];
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px; max-width:1000px;'
    );
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => 'true', 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => 'true', 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => 'true', 'style' => 'font-size:16px;'),
    );

    $trno = $config['params']['tableid'];

    $qry = "select trno, doc, task, log.user, dateid, 
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

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'lookupusers':
        return $this->lookupusers($config);
        break;
      case 'lookupgroup':
        return $this->lookupgroup($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action' . $config['params']['actions'] . 'is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupusers($config)
  {
    $index = $config['params']['index'];
    $plotting = array();
    $plottype = '';

    $title = 'List of User';
    $plotting = array('userid' => 'userid', 'name' => 'name');
    $plottype = 'plotgrid';


    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = [
      ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select '' as userid,'' as accessid, '' as username,'' as name
                union all
                select userid, accessid, username, name
                from useraccess
                ";


    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupgroup($config)
  {
    $index = $config['params']['index'];
    $plotting = array();
    $plottype = '';

    $title = 'List of Groups';
    $plotting = array('groupname' => 'groupname');
    $plottype = 'plotgrid';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['groupname' => 'groupname', 'label' => 'Groups', 'align' => 'left', 'field' => 'groupname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select  line, groupname from reqcategory where isservice = 1 order by line";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function save($config)  //stockgrid button 
  {
    $data = [];
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    
    $dateTables = [$this->table];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value],$lookups);
    }
    $row['usergrp'] = isset($row['groupname']) ? $row['groupname'] : ''; // FIX: map groupname to usergrp
    $data['usergrp'] = $row['usergrp']; // FIX: update data array with correct usergrp value

    if ($row['line'] == 0 && $row['userid'] != '' &&  $row['usergrp'] != '') {
      $qry = "select userid from userdisplay where userid = '" . $row['userid'] .  "' and usergrp = '" . $row['usergrp'] . "' limit 1";
      $opendata = $this->coreFunctions->opentable($qry);
      $resultdata = json_decode(json_encode($opendata), true);
      if (!empty($resultdata[0]['userid'])) {
        if (trim($resultdata[0]['userid']) == trim($row['userid'])) {
          return ['status' => false, 'msg' => ' User Display ( ' . $resultdata[0]['userid'] . ' ) is already exist', 'data' => [$resultdata]];
        }
      }
    }

    if (trim($row['userid']) == '') {
      return ['status' => false, 'msg' => 'User Display userid is empty'];
    }

    if (trim($row['usergrp']) == '') {
      return ['status' => false, 'msg' => 'User Display usergrp is empty'];
    }

    if ($row['line'] == 0) {
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['userid']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {

      if ($row['line'] != 0 && $row['userid'] != '' && $row['usergrp'] != '') {
        $qry = "select userid,line from userdisplay where userid = '" . $row['userid'] . "' and usergrp = '" . $row['usergrp'] . "' limit 1";
        $opendata = $this->coreFunctions->opentable($qry);
        $resultdata = json_decode(json_encode($opendata), true);
        if (!empty($resultdata[0]['userid'])) {
          if (trim($resultdata[0]['userid']) == trim($row['userid'])) {
            if ($row['line'] == $resultdata[0]['line']) {
              goto update;
            }
            return ['status' => false, 'msg' => ' user ( ' . $resultdata[0]['userid'] . ' ) is already exist', 'data' => [$resultdata], 'rowid' => [$row['line'] . ' -- ' . $resultdata[0]['line']]];
          } else {
            update:
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
            $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $row['userid'] . ' - ' . $row['usergrp']);
          }
        } else {
          goto update;
        }
      }

      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];

      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  }

  public function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor";
    $qry = "select " . $select . " from " . $this->table . " left join useraccess as user on user.userid = userdisplay.userid
    left join reqcategory as req on req.groupname = userdisplay.usergrp where userdisplay.line =? ";
    $data = $this->coreFunctions->opentable(
      $qry,
      [$line]
    );
    return $data;
  }

  public function delete($config) // needed restrictions
  {
    $row = $config['params']['row'];

    $qry = "delete from " . $this->table . " where line = ?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $count = $this->coreFunctions->datareader($qry, [$row['userid']]);


    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['userid']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }
}// end function