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

class entrycalllog
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CALL LOG ENTRY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'calllogs';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  private $fields = ['line', 'dateid', 'starttime', 'endtime', 'rem', 'calltype', 'contact', 'status'];
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
      'load' => 2564,
      'save' => 2567,
      'delete' => 2569,
    );
    return $attrib;
  }

  public function createTab($config)
  {

    $action = 0;
    $contact = 1;
    $dateid = 2;
    $starttime = 3;
    $endtime = 4;
    $calltype = 5;
    $status = 6;
    $rem = 7;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'contact', 'dateid', 'starttime', 'endtime', 'calltype', 'status', 'rem']
      ]
    ];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$contact]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$starttime]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$endtime]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$calltype]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$calltype]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$status]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$status]['label'] = "Inquiry Status";
    $obj[0][$this->gridname]['columns'][$status]['lookupclass'] = "inquirystat";
    $obj[0][$this->gridname]['columns'][$status]['action'] = "lookupsetup";

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['defaults', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "ADD NEW";
    $obj[1]['label'] = "END CALL";
    $obj[1]['icon'] = "phone";
    return $obj;
  }

  public function adddefaults($config)
  {
    $this->othersClass->setDefaultTimeZone();
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    if ($isposted) {
      $qstrno = $this->coreFunctions->getfieldvalue("qshead","trno","optrno = ?",[$trno],'',true);
      if($qstrno = 0){ //check sa posted
        $qstrno = $this->coreFunctions->getfieldvalue("hqshead","trno","optrno = ?",[$trno],'',true);
        if($qstrno !=0){
          $call = app('App\Http\Classes\modules\tableentry\entrycalllog')->loaddata($config);
          return ['status' => false, 'msg' => 'Already Posted', 'data' => $call];
        }
      }
      
    }

    $select = $this->selectqry();
    $tableid = $config['params']['tableid'];
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno = '$tableid' order by endtime  desc limit 1 ";
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    $calltype='';
    $contact='';
    $stat='Active';
    if (!empty($result)) {
      $contact= $result[0]['contact'] != '' ? $result[0]['contact'] : '';
      $calltype= $result[0]['calltype'] != '' ? $result[0]['calltype'] : '';
      $stat= $result[0]['status'] != '' ? $result[0]['status'] : '';
    }
    $data = [];
    $data['trno'] = $trno;
    $data['dateid'] = date("Y/m/d");
    $data['starttime'] = date("H:i:s");
    $data['endtime'] = '';
    $data['calltype'] = $calltype;
    $data['rem'] = '';
    $data['status']= $stat;
    $data['contact']= $contact;

    if ($this->checkcalllogs($trno) == true) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      $this->logger->sbcmasterlog($trno, $config, ' CREATE CALL LOG', 0, 1);
      $call = app('App\Http\Classes\modules\tableentry\entrycalllog')->loaddata($config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $call];
    } else {
      $call = app('App\Http\Classes\modules\tableentry\entrycalllog')->loaddata($config);
      return ['status' => false, 'msg' => 'Please endcall before proceed to new transaction', 'data' => $call];
    }
  }


  private function selectqry()
  {
    $qry = "trno, line,left(dateid, 10) as dateid,starttime,endtime,rem,calltype, contact,status";
    return $qry;
  }

  public function saveallentry($config)
  {
    $this->othersClass->setDefaultTimeZone();
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      foreach ($this->fields as $key2 => $value2) {
        $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2], '', $config['params']['companyid']);
      }
      if ($data2['endtime'] == '') {
        $data2['endtime'] = date("H:i:s");
      }

      if ($data2['calltype'] == '') {
        $returndata = $this->loaddata($config);
        return ['status' => false, 'msg' => 'Call type is required', 'data' => $returndata];
      }

      if ($data2['rem'] == '') {
        $returndata = $this->loaddata($config);
        return ['status' => false, 'msg' => 'Notes is required', 'data' => $returndata];
      }
      $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data2['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line']]);
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Call ended successfully.', 'data' => $returndata];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];

    if ($this->checkcalllogsperrow($row['trno'], $row['line']) == true) {
      $data = $this->loaddataperrecord($row['trno'], $row['line']);
      return ['status' => false, 'msg' => " this call entry is already has ended, you cant't make any changes."];
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value], '', $config['params']['companyid']);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($data['rem'] == '') {
      $data = $this->loaddataperrecord($row['trno'], $row['line']);
      return ['status' => false, 'msg' => 'Notes is required', 'data' => $data];
    }
    
    if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
      $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Save Failed...'];
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];

    if ($this->checkcalllogsperrow($row['trno'], $row['line']) == true) {
      $data = $this->loaddataperrecord($row['trno'], $row['line']);
      return ['status' => false, 'msg' => " this call entry is already have endtime you cant't delete this line "];
    }

    $qry = "delete from " . $this->table . " where trno =? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    $this->logger->sbcdelmaster_log($row['trno'], $config, 'REMOVE - Line : ' . $row['line'] . 'CONTACT:' . $row['contact'], 0, 1);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $tableid = $config['params']['tableid'];
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno =?";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'calltype':
        return $this->lookupcalltype($config);
        break;

      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'inquirystat':
        return $this->lookupstatus($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper($config['params']['lookupclass']);
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Call Logs',
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
    where log.doc = '" . $doc . "' and trno = " . $trno . "
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = " . $trno . " ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function lookupcalltype($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Call Type',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'calltype' => 'calltype',
      )
    );

    $cols = array(
      array('name' => 'calltype', 'label' => 'Call Type', 'align' => 'left', 'field' => 'calltype', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select 'Follow Up' as calltype
    union all
    select 'Prospecting'
    union all 
    select 'Others'";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupstatus($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Status',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'status' => 'status',
      )
    );

    $cols = array(
      array('name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select 'Active' as status
    union all
    select 'Inactive'";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  private function checkcalllogs($trno)
  {
    $res = $this->coreFunctions->opentable('select endtime from calllogs where trno = ?', [$trno]);
    if (!empty($res)) {
      foreach ($res as $key => $value) {
        if ($value->endtime != '') {
          $status = true;
        } else {
          $status = false;
        }
      }
    } else {
      $status = true;
    }
    return $status;
  }

  private function checkcalllogsperrow($trno, $line)
  {
    $res = $this->coreFunctions->opentable('select endtime from calllogs where trno = ? and line = ?', [$trno, $line]);
    foreach ($res as $key => $value) {
      if ($value->endtime != '') {
        $status = true;
      } else {
        $status = false;
      }
    }
    return $status;
  }

  // -> Print Function
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'default' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select model_id, model_code, model_name from model_masterfile
      order by model_id";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_model_masterfile_layout($data, $config);
    return ['status' => true, 'msg' => 'Generating report successful.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MODEL MASTERFILE', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model Name', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_model_masterfile_layout($data, $filters)
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
      $str .= $this->reporter->col($data[$i]['model_name'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
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

} //end class
