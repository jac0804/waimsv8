<?php

namespace App\Http\Classes\modules\payrollentry;

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



class entryotapproval
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OVERTIME APPROVAL';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $reporter;

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
      'view' => 1631,
      'save' => 1632,
      'saveallentry' => 1632,
      'print' => 1633
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {

    $tab = [$this->gridname => ['gridcolumns' => ['otapproved', 'dateid', 'empname', 'othrs']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // $obj[0][$this->gridname]['obj'] = 'editgrid';
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:120px;whiteSpace: normal;min-width:120px; text-align:center;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][2]['label'] = "Employee";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";

    $obj[0][$this->gridname]['label'] = 'DETAILS';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "SAVE";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['start', 'end', 'ottype', 'refresh', 'create']; //, ['create', 'refresh', 'print']
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'From');
    data_set($col1, 'end.label', 'To');
    data_set($col1, 'create.label', 'Mark All');
    data_set($col1, 'create.action', 'mark');
    data_set($col1, 'refresh.action', 'load');
    data_set($col1, 'refresh.style', 'width:100%');
    data_set($col1, 'create.style', 'width:100%');

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'print.style', 'width:100%');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end,
      '' as ottype,
      '' as checkall
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

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case 'print':
        return $this->setupreport($config);
        //return ['status'=>true,'msg'=>'test','action'=>'print','data'=>];
        break;

      case 'saveallentry':
      case 'update':
        if (empty($config['params']['rows'])) {
          return ['status' => true, 'msg' => 'No Data', 'data' => []];
        } else {
          $this->save($config);
          return $this->loadgrid($config);
        }
        break;

      case 'load':
        return $this->loadgrid($config);
        break;

      case 'mark':
        return $this->loadgrid($config, 1);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loadgrid($config, $mark = 0)
  {
    $center = $config['params']['center'];

    $ottype  = $config['params']['dataparams']['ottype'];
    $from = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $to = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    if ($ottype == '') {
      return ['status' => false, 'msg' => 'Select OT Type First', 'data' => []];
    }

    switch ($ottype) {
      case 'EARLY OT':
        $filters = " and tc.earlyotapproved=0 and tc.earlyothrs <> 0 and tc.daytype='WORKING' ";
        $fields = 'earlyotapproved';
        $fields2 = 'earlyothrs';
        break;

      case 'REGULAR OT':
        $filters = " and tc.otapproved=0 and tc.othrs <> 0 and tc.daytype='WORKING' ";
        $fields = 'otapproved';
        $fields2 = 'othrs';
        break;

      case 'NIGHT DIFF OT':
        $filters = ' and tc.ndiffapproved=0 and tc.ndiffot <> 0';
        $fields = 'ndiffapproved';
        $fields2 = 'ndiffot';
        break;

      case 'NIGHT DIFF':
        $filters = ' and tc.ndiffsapprvd=0 and tc.ndiffhrs <> 0';
        $fields = 'ndiffsapprvd';
        $fields2 = 'ndiffhrs';
        break;

      case 'RESTDAY':
        $filters = ' and tc.rdapprvd=0 and tc.reghrs<>0 and tc.daytype="RESTDAY" ';
        $fields = 'rdapprvd';
        $fields2 = 'reghrs';
        break;

      case 'RESTDAY OT':
        $filters = " and tc.rdotapprvd=0 and tc.othrs <>0 and tc.daytype='RESTDAY' ";
        $fields = 'rdotapprvd';
        $fields2 = 'othrs';
        break;

      case 'SPECIAL HOLIDAY':
        $filters = " and tc.spapprvd=0 and tc.reghrs<>0 and tc.daytype='SP' ";
        $fields = 'spapprvd';
        $fields2 = 'reghrs';
        $daytype = 'SP';
        break;

      case 'SPECIAL OT':
        $filters = " and tc.spotapprvd=0 and tc.othrs<>0 and tc.daytype='SP' ";
        $fields = 'spotapprvd';
        $fields2 = 'othrs';
        break;

      case 'LEGAL HOLIDAY':
        $filters = " and tc.legapprvd=0 and tc.reghrs<>0 and tc.daytype='LEG'";
        $fields = 'legapprvd';
        $fields2 = 'reghrs';
        break;

      case 'LEGAL OT':
        $filters = " and tc.legotapprvd=0 and tc.othrs<>0 and tc.daytype='LEG'";
        $fields = 'legotapprvd';
        $fields2 = 'othrs';
        break;

      default:
        return ['status' => true, 'msg' => 'Invalid OT type', 'data' => []];
        break;
    } // end switch

    if ($mark) {
      $qry = "select tc.dateid, client.client as empcode, concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle) AS empname,
      tc." . $fields2 . " as othrs, tc.line, 'true' as otapproved
      from timecard tc left join employee emp on emp.empid=tc.empid left join client on client.clientid=emp.empid
      where tc.dateid between '" . $from . "' AND '" . $to . "' and emp.isactive=1 " . $filters . " 
      order by tc.dateid, concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle)";
    } else {
      $qry = "select tc.dateid, client.client as empcode, concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle) AS empname,
      tc." . $fields2 . " as othrs, tc.line, case when tc." . $fields . "=0 then 'false' else 'true' end as otapproved
      from timecard tc left join employee emp on emp.empid=tc.empid  left join client on client.clientid=emp.empid
      where tc.dateid between '" . $from . "' AND '" . $to . "' and emp.isactive=1 " . $filters . " 
      order by tc.dateid, concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle)";
    }
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function save($config)
  {
    $ottype  = $config['params']['dataparams']['ottype'];
    $rows = $config['params']['rows'];

    switch ($ottype) {
      case 'EARLY OT':
        $field_approved = 'earlyotapproved';
        $fieldOT = 'earlyothrs';
        break;

      case 'REGULAR OT':
        $field_approved = 'otapproved';
        $fieldOT = 'othrs';
        break;

      case 'NIGHT DIFF OT':
        $field_approved = 'ndiffapproved';
        $fieldOT = 'ndiffot';
        break;

      case 'NIGHT DIFF':
        $field_approved = 'ndiffsapprvd';
        $fieldOT = 'ndiffhrs';
        break;

      case 'RESTDAY':
        $field_approved = 'rdapprvd';
        $fieldOT = 'reghrs';
        break;

      case 'RESTDAY OT':
        $field_approved = 'rdotapprvd';
        $fieldOT = 'othrs';
        break;

      case 'SPECIAL HOLIDAY':
        $field_approved = 'spapprvd';
        $fieldOT = 'reghrs';
        break;

      case 'SPECIAL OT':
        $field_approved = 'spotapprvd';
        $fieldOT = 'othrs';
        break;

      case 'LEGAL HOLIDAY':
        $field_approved = 'legapprvd';
        $fieldOT = 'reghrs';
        break;

      case 'LEGAL OT':
        $field_approved = 'legotapprvd';
        $fieldOT = 'othrs';
        break;

      default:
        return ['status' => true, 'msg' => 'Please Select OT Type', 'data' => []];
        break;
    } // end switch

    foreach ($rows as $key => $val) {
      $data = [];

      if ($val["otapproved"] != "false") {
        // unset($val["dateid"]);
        // unset($val["othrs"]);
        // unset($val["otapproved"]);
        // unset($val["empcode"]);
        // unset($val["empname"]);
        // $val[$fields] = 1;
        $data[$field_approved] = 1;
      }
      $data[$fieldOT] = $val['othrs'];
      $this->coreFunctions->sbcupdate("timecard", $data, ['line' => $val["line"]]);
    }
  }


  public function setupreport($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'action' => 'print'];
  }


  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'start', 'end', 'ottype', 'prepared', 'approved', 'received', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'ottype.type', 'hidden');
    data_set($col1, 'start.type', 'hidden');
    data_set($col1, 'end.type', 'hidden');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $ottype = $config['params']['dataparams']['ottype'];
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '" . $start . "' as start,
        '" . $end . "' as end,
        '' as prepared,
        '' as approved,
        '' as received,
        '" . $ottype . "' as ottype
    "
    );
  }

  public function default_query($config)
  {
    $ottype = $config['params']['dataparams']['ottype'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    switch ($ottype) {
      case 'EARLY OT':
        $filters = " and tc.earlyotapproved=0 and tc.earlyothrs <> 0 and tc.daytype='WORKING' ";
        $fields = 'earlyotapproved';
        $fields2 = 'earlyothrs';
        break;

      case 'REGULAR OT':
        $filters = " and tc.otapproved=0 and tc.othrs <> 0 and tc.daytype='WORKING' ";
        $fields = 'otapproved';
        $fields2 = 'othrs';
        break;

      case 'NIGHT DIFF OT':
        $filters = ' and tc.ndiffapproved=0 and tc.ndiffot <> 0';
        $fields = 'ndiffapproved';
        $fields2 = 'ndiffot';
        break;

      case 'NIGHT DIFF':
        $filters = ' and tc.ndiffsapprvd=0 and tc.ndiffhrs <> 0';
        $fields = 'ndiffsapprvd';
        $fields2 = 'ndiffhrs';
        break;

      case 'RESTDAY':
        $filters = ' and tc.rdapprvd=0 and tc.reghrs<>0 and tc.daytype="RESTDAY" ';
        $fields = 'rdapprvd';
        $fields2 = 'reghrs';
        break;

      case 'RESTDAY OT':
        $filters = " and tc.rdotapprvd=0 and tc.othrs <>0 and tc.daytype='RESTDAY' ";
        $fields = 'rdotapprvd';
        $fields2 = 'othrs';
        break;

      case 'SPECIAL HOLIDAY':
        $filters = " and tc.spapprvd=0 and tc.reghrs<>0 and tc.daytype='SP' ";
        $fields = 'spapprvd';
        $fields2 = 'reghrs';
        $daytype = 'SP';
        break;

      case 'SPECIAL OT':
        $filters = " and tc.spotapprvd=0 and tc.othrs<>0 and tc.daytype='SP' ";
        $fields = 'spotapprvd';
        $fields2 = 'othrs';
        break;

      case 'LEGAL HOLIDAY':
        $filters = " and tc.legapprvd=0 and tc.reghrs<>0 and tc.daytype='LEG'";
        $fields = 'legapprvd';
        $fields2 = 'reghrs';
        break;

      case 'LEGAL OT':
        $filters = " and tc.legotapprvd=0 and tc.othrs<>0 and tc.daytype='LEG'";
        $fields = 'legotapprvd';
        $fields2 = 'othrs';
        break;

      default:
        return ['status' => true, 'msg' => 'Invalid OT type', 'data' => []];
        break;
    } // end switch

    $qry = "select tc.dateid, client.client as empcode, 
    concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle) AS empname,
      tc." . $fields2 . " as othrs, tc.line, 
      case 
        when tc." . $fields . " = 0 then 'false' 
        else 'true' 
      end as otapproved
      from timecard tc 
      left join employee emp on emp.empid=tc.empid  
      left join client on client.clientid=emp.empid
      where tc.dateid between '" . $start . "' AND '" . $end . "' and 
      emp.isactive=1 " . $filters . " 
      order by tc.dateid, 
      concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle)";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
  public function reportdata($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $ottype = $config['params']['dataparams']['ottype'];
    $start = date('F d, Y', strtotime($config['params']['dataparams']['start']));
    $end = date('F d, Y', strtotime($config['params']['dataparams']['end']));

    $data = $this->default_query($config);

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('OT TYPE:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($ottype, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($start, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TO:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($end, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee Name', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('OT Hours', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Approve', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');

    foreach ($data as $key => $val) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($val->empname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->othrs, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      if ($val->otapproved == 'true') {
        $isapproved = "YES";
      } else {
        $isapproved = "NO";
      }
      $str .= $this->reporter->col($isapproved, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'directprint' => false, 'action' => 'print'];
  }
} //end class
