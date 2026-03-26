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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryrole
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ROLE MASTERFILE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'rolesetup';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['name', 'divid', 'deptid', 'sectionid', 'supervisorid'];
  public $showclosebtn = false;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $reporter;
  private $logger;


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

    $columns = ['action', 'sortline', 'name', 'divname', 'deptname', 'sectname', 'supervisorname'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$name]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$divname]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";
    $obj[0][$this->gridname]['columns'][$deptname]['style'] = "width:230px;whiteSpace: normal;min-width:230px;";
    $obj[0][$this->gridname]['columns'][$sectname]['style'] = "width:230px;whiteSpace: normal;min-width:230px;";
    $obj[0][$this->gridname]['columns'][$supervisorname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$divname]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$deptname]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$sectname]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$supervisorname]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$divname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$deptname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$sectname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$supervisorname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$divname]['lookupclass'] = "divlookup";
    $obj[0][$this->gridname]['columns'][$deptname]['lookupclass'] = "deptlookup";
    $obj[0][$this->gridname]['columns'][$sectname]['lookupclass'] = "sectlookup";
    $obj[0][$this->gridname]['columns'][$supervisorname]['lookupclass'] = "supervlookup";

    $obj[0][$this->gridname]['columns'][$divname]['label'] = "Company";
    $obj[0][$this->gridname]['columns'][$sectname]['label'] = "Section";

    $obj[0][$this->gridname]['columns'][$sortline]['type'] = "label";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['name'] = '';
    $data['divid'] = 0;
    $data['deptid'] = 0;
    $data['sectionid'] = 0;
    $data['supervisorid'] = 0;
    $data['divname'] = '';
    $data['deptname'] = '';
    $data['sectname'] = '';
    $data['supervisorname'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "role.line, role.name, role.deptid, role.deptid, role.sectionid, role.supervisorid, role.divid, role.line as sortline";
    return $qry;
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


        if ($data[$key]['line'] == 0) {
          $qry = "select name as value from " . $this->table . " where name = '" . $data[$key]['name'] . "'";
          $checking = $this->coreFunctions->datareader($qry);

          if (!empty($checking)) {
            return ['status' => false, 'msg' => 'Code Already Exist. - ' . $data[$key]['name'], 'data' => $data];
          }

          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $qry = "select name as value from " . $this->table . " where name = '" . $data[$key]['name'] . "' and line = '" . $data[$key]['line'] . "'";
          $checking = $this->coreFunctions->datareader($qry);

          $qry = "select supervisorid as value from " . $this->table . " where name = '" . $data[$key]['name'] . "' and line = '" . $data[$key]['line'] . "'";
          $supervisorid = $this->coreFunctions->datareader($qry, [], '', true);

          if ($supervisorid != $data2['supervisorid']) {
            if ($data2['supervisorid'] != 0) {
              $qry = "select empid, branchid, roleid, jobid, divid, deptid, sectid from employee where roleid = '" . $data[$key]['line'] . "'";
              $empid = $this->coreFunctions->opentable($qry);
              for ($i = 0; $i < count($empid); $i++) {

                $this->logger->sbcwritelog($empid[$i]->empid, $config, 'ROLE', 'Update Role Supervisor', 'client_log');

                $this->coreFunctions->execqry("update employee set supervisorid = " . $data2['supervisorid'] . ", 
                  editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $config['params']['user'] . "' 
                  where empid = '" . $empid[$i]->empid . "' ", "update");

                $designation = [
                  'empid' => $empid[$i]->empid,
                  'branchid' => $empid[$i]->branchid,
                  'roleid' => $empid[$i]->roleid,
                  'jobid' => $empid[$i]->jobid,
                  'supervisorid' => $data2['supervisorid'],
                  'category' => 2,
                  'effectdate' => $this->othersClass->getCurrentDate(),
                  'divid' => $empid[$i]->divid,
                  'deptid' => $empid[$i]->deptid,
                  'sectid' => $empid[$i]->sectid,
                  'encodeddate' => $this->othersClass->getCurrentTimeStamp(),
                  'encodedby' => $config['params']['user'],
                  'isrole' => 1
                ];

                $this->coreFunctions->sbcinsert("designation", $designation);

                $this->coreFunctions->LogConsole(json_encode($designation));
              }
            }
          }

          if (!empty($checking)) {
            unset($data["name"]);
          } else {
            $qry = "select name as value from " . $this->table . " where name = '" . $data[$key]['name'] . "'";
            $checking1 = $this->coreFunctions->datareader($qry);

            if (!empty($checking1)) {
              $returndata = $this->loaddata($config);
              return ['status' => false, 'msg' => 'Code Already Exist. - ' . $data[$key]['name'], 'data' => $data];
            }
          }
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
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
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }


    if ($row['line'] == 0) {
      $qry = "select name as value from " . $this->table . " where name = '" . $data['name'] . "'";
      $checking = $this->coreFunctions->datareader($qry);

      if (!empty($checking)) {
        // $returndata = $this->loaddata($config);
        return ['status' => false, 'msg' => 'Code Already Exist. - ' . $data['name'], 'data' => $data];
      }

      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $qry = "select name as value from " . $this->table . " where name = '" . $data['name'] . "' and line = '" . $row['line'] . "'";
      $checking = $this->coreFunctions->datareader($qry);

      $qry = "select supervisorid as value from " . $this->table . " where name = '" . $row['name'] . "' and line = '" . $row['line'] . "'";
      $supervisorid = $this->coreFunctions->datareader($qry);

      if ($supervisorid != $data['supervisorid']) {
        if ($data['supervisorid'] != 0) {
          $qry = "select empid, branchid, roleid, jobid, divid, deptid, sectid from employee where roleid = '" . $row['line'] . "'";
          $empid = $this->coreFunctions->opentable($qry);

          for ($i = 0; $i < count($empid); $i++) {
            $this->logger->sbcwritelog($empid[$i]->empid, $config, 'ROLE', 'Update Role Supervisor', 'client_log');

            $this->coreFunctions->execqry("update employee set supervisorid = " . $data['supervisorid'] . ",
              editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $config['params']['user'] . "' 
              where empid = '" . $empid[$i]->empid . "' ", "update");

            $designation = [
              'empid' => $empid[$i]->empid,
              'branchid' => $empid[$i]->branchid,
              'roleid' => $empid[$i]->roleid,
              'jobid' => $empid[$i]->jobid,
              'supervisorid' => $data['supervisorid'],
              'category' => 2,
              'effectdate' => $this->othersClass->getCurrentDate(),
              'divid' => $empid[$i]->divid,
              'deptid' => $empid[$i]->deptid,
              'sectid' => $empid[$i]->sectid,
              'encodeddate' => $this->othersClass->getCurrentTimeStamp(),
              'encodedby' => $config['params']['user'],
              'isrole' => 1
            ];

            $this->coreFunctions->sbcinsert("designation", $designation);

            $this->coreFunctions->LogConsole(json_encode($designation));
          }
        }
      }

      if (!empty($checking)) {
        unset($data["name"]);
      } else {
        $qry = "select name as value from " . $this->table . " where name = '" . $data['name'] . "'";
        $checking1 = $this->coreFunctions->datareader($qry);

        if (!empty($checking1)) {
          $returndata = $this->loaddata($config);
          return ['status' => false, 'msg' => 'Code Already Exist. - ' . $data['name'], 'data' => $data];
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
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ", divs.divname, depart.clientname as deptname, 
    sec.sectname, super.clientname as supervisorname, '' as bgcolor ";

    $qry = "select " . $select . " from " . $this->table . " as role
    left join division as divs on divs.divid = role.divid
    left join client as depart on depart.clientid = role.deptid
    left join section as sec on sec.sectid = role.sectionid
    left join client as super on super.clientid = role.supervisorid
    where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ", divs.divname, depart.clientname as deptname, 
    sec.sectname, super.clientname as supervisorname, '' as bgcolor ";

    $qry = "select " . $select . " from " . $this->table . " as role
    left join division as divs on divs.divid = role.divid
    left join client as depart on depart.clientid = role.deptid
    left join section as sec on sec.sectid = role.sectionid
    left join client as super on super.clientid = role.supervisorid
    order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {

      case 'divlookup':
        return $this->lookupdivision($config);
        break;

      case 'deptlookup':
        return $this->lookupdepartment($config);
        break;

      case 'sectlookup':
        return $this->lookupsection($config);
        break;

      case 'supervlookup':
        return $this->lookupsupervisor($config);
        break;

      case 'whlog':
        return $this->lookuplogs($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:100%;max-width:100%;height:80%;'
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


  public function lookupdivision($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Division',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'divid' => 'divid',
        'divname' => 'divname',
        'divcode' => 'divcode',
      )
    );

    $cols = array(
      array('name' => 'divcode', 'label' => 'Division Code', 'align' => 'left', 'field' => 'divcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'divname', 'label' => 'Division Name', 'align' => 'left', 'field' => 'divname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select divid, divcode, divname from division order by divcode";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupdepartment($config)
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
      'plotting' => array(
        'deptid' => 'clientid',
        'deptname' => 'clientname',
      )
    );

    $cols = array(
      array('name' => 'client', 'label' => 'Department Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Department Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select clientid, client, clientname from client where isdepartment = 1";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupsection($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Section',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'sectionid' => 'sectid',
        'sectname' => 'sectname',
        'sectcode' => 'sectcode',
      )
    );

    $cols = array(
      array('name' => 'sectcode', 'label' => 'Section Code', 'align' => 'left', 'field' => 'sectcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'sectname', 'label' => 'Section Name', 'align' => 'left', 'field' => 'sectname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select sectid, sectcode, sectname from section";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupsupervisor($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Supervisor',
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'supervisorid' => 'clientid',
        'supervisorname' => 'clientname',
      )
    );

    $cols = array(
      array('name' => 'client', 'label' => 'Employee Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Employee Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select cl.client, cl.clientname, cl.clientid
      from client as cl 
      left join employee  as emp on emp.empid = cl.clientid
      where cl.isemployee = 1 and (emp.isapprover = 1 or emp.issupervisor = 1)";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }



  // -> print function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter()
  {
    // $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select role.line, role.name, role.deptid, role.deptid, role.sectionid, role.supervisorid, role.divid,
      ifnull(divs.divname,'') as divname, ifnull(depart.clientname,'') as deptname,
          ifnull(sec.sectname,'') as sectname, ifnull(super.clientname,'') as supervisorname, '' as bgcolor from rolesetup as role
          left join division as divs on divs.divid = role.divid
          left join client as depart on depart.clientid = role.deptid
          left join section as sec on sec.sectid = role.sectionid
          left join client as super on super.clientid = role.supervisorid
      order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_forex_masterfile_PDF($data, $config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ROLE MASTERFILE', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name', '160', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Division Name', '160', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Department', '160', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Section Name', '160', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Supervisor', '160', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_forex_masterfile_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['name'], '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['divname'], '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['deptname'], '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['sectname'], '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['supervisorname'], '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function rpt_default_header_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    $reporttimestamp = $this->reporter->setreporttimestamp($filters, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n", '', 'C');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, "ROLE MASTERFILE", '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', 'T');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(130, 0, "Name", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "Division Name", '', 'L', false, 0);
    PDF::MultiCell(150, 0, "Department", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "Section Name", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "Supervisor", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', 'B');
  }

  private function rpt_forex_masterfile_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->rpt_default_header_PDF($data, $filters);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarrdiv = 0;


    for ($i = 0; $i < count($data); $i++) {
      ///////////////// start divname
      $arrdiv = array();
      $divname = [];

      $divword = [];
      $divword = explode(' ', $data[$i]['divname']);
      $divwordstring = '';
      foreach ($divword as $word) {
        $divwordstring = $divwordstring . $word . ' ';
        if (strlen($divwordstring) > 50) {
          $divwordstring = str_replace($word, '', $divwordstring);
          array_push($arrdiv, $divwordstring);
          $divwordstring = '';
          $divwordstring = $divwordstring . $word . ' ';
        }
      }
      array_push($arrdiv, $divwordstring);
      $divwordstring = '';
      ///////////////// divname

      if (!empty($arrdiv)) {
        foreach ($arrdiv as $arri) {
          if (strstr($arri, "\n")) {
            $array = preg_split("/\r\n|\n|\r/", $arri);
            foreach ($array as $arr) {
              array_push($divname, $arr);
            }
          } else {
            array_push($divname, $arri);
          }
        }
      }
      ////////////////////// end divname

      $maxrow = 1;

      $countarrdiv = count($divname);

      $maxrow = $countarrdiv;
      if ($data[$i]['divname'] == '') {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 0, $data[$i]['name'], '', 'L', false, 0, '', '', true, 1);
        PDF::MultiCell(160, 0, $data[$i]['divname'], '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(150, 0, $data[$i]['deptname'], '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(160, 0, $data[$i]['sectname'], '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(160, 0, $data[$i]['supervisorname'], '', 'L', false, 1, '', '', false, 0);
      } else {
        for ($r = 0; $r < $maxrow; $r++) {
          if ($r == 0) {
            $name = $data[$i]['name'];
            $deptname = $data[$i]['deptname'];
            $sectname = $data[$i]['sectname'];
            $supervisor = $data[$i]['supervisorname'];
          } else {
            $name = '';
            $deptname = '';
            $sectname = '';
            $supervisor = '';
          }
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(130, 0, $name, '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(160, 0, isset($divname[$r]) ? $divname[$r] : '', '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(150, 0, $deptname, '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(160, 0, $sectname, '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(160, 0, $supervisor, '', 'L', false, 1, '', '', false, 1);
        }
      }

      if (intVal($i) + 1 == $page) {
        $this->rpt_default_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn


} //end class
