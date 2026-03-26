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
use App\Http\Classes\lookup\enrollmentlookup;

class entryattendancestudents
{
  private $fieldClass;
  private $tabClass;
  private $logger;
  public $modulename = 'STUDENTS ATTENDANCE';
  public $gridname = 'inventory';
  public $tablenum = 'transnum';
  public $head = 'en_athead';
  public $hhead = 'en_glhead';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_atstudents';
  public $tablelogs = 'transnum_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['id', 'clientid', 'attendancetypeline', 'line'];
  public $showclosebtn = true;
  private $reporter;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->enrollmentlookup = new enrollmentlookup;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 856
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'listclientname', 'attendancetype']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['label'] = 'Student';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][2]['lookupclass'] = 'lookupattendancetype';
    $obj[0][$this->gridname]['columns'][2]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['label'] = 'Attendance Type';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupattendancetype':
        return $this->enrollmentlookup->lookupattendancetype($config);
        break;
    }
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $config['params']['trno'] = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted($config);
    $islocked = $this->othersClass->islocked($config);
    if (!$isposted && !$islocked) {
      foreach ($data as $key => $value) {
        $data2 = [];
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $id = explode('-', $data2['id']);
        $datas['trno'] = $id[0];
        $datas['atdate'] = $id[2];
        $datas['status'] = $data2['attendancetypeline'];
        $datas['clientid'] = $data2['clientid'];
        $datas['line'] = $id[1];
        if ($data2['line'] == 0) {
          $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno=? order by line desc limit 1", [$datas['trno']]);
          if ($line == '') $line = 0;
          $line += 1;
          $this->coreFunctions->execqry("insert into en_atstudents(trno, line, atdate, status, clientid) values(?, ?, ?, ?, ?)", 'insert', [$datas['trno'], $line, $datas['atdate'], $datas['status'], $datas['clientid']]);
        } else {
          $this->coreFunctions->execqry("update en_atstudents set status=? where trno=? and line=?", 'update', [$datas['status'], $datas['trno'], $datas['line']]);
        }
      } // foreach
      $returndata = $this->loaddata($config, 'update');
      return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } else if ($isposted) {
      $returndata = $this->loaddata($config, 'update');
      return ['status' => false, 'msg' => 'Document already posted...', 'data' => $returndata];
    } else {
      return ['status' => false, 'msg' => 'Document locked...', 'data' => []];
    }
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $id = explode('-', $data['id']);
    $data2['trno'] = $id[0];
    $data2['atdate'] = $id[2];
    $data2['status'] = $data['attendancetypeline'];
    $data2['clientid'] = $data['clientid'];
    $data2['line'] = $data['line'];

    $config['params']['trno'] = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted($config);
    $islocked = $this->othersClass->islocked($config);

    if (!$isposted && !$islocked) {
      if ($data2['line'] == 0) {
        $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno=? order by line desc limit 1", [$data2['trno']]);
        if ($line == '') $line = 0;
        $line += 1;
        if ($this->coreFunctions->execqry("insert into en_atstudents(trno, line, atdate, status, clientid) values(?, ?, ?, ?, ?)", 'insert', [$data2['trno'], $line, $data2['atdate'], $data2['status'], $data2['clientid']]) > 0) {
          $this->logger->sbcwritelog($data2['trno'], $config, 'Attendance Entry', 'ADD - Line:' . $line . ' Date:' . $data2['atdate'] . ' status:' . $data2['status'] . ' student:' . $data2['clientid']);
          $returnrow = $this->loaddataperrecord($data2['trno'], $line);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      } else {
        if ($this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data2['trno'], 'line' => $data2['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($data2['trno'], $data2['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else if ($isposted) {
      return ['status' => false, 'msg' => 'Document already posted...'];
    } else {
      return ['status' => false, 'msg' => 'Document locked...'];
    }
  } //end function

  private function loaddataperrecord($trno, $line)
  {
    $data = $this->coreFunctions->opentable("
      select a.line, ifnull(at.type,'') as attendancetype, ifnull(a.status,0) as attendancetypeline, '' as bgcolor, concat(a.trno, '-', a.line, '-', date(a.atdate)) as id, concat(stud.fname, ' ', stud.mname, ' ', stud.lname) as clientname, a.clientid
      from en_atstudents as a
      left join en_attendancetype as at on at.line = a.status
      left join en_studentinfo as stud on stud.clientid = a.clientid
      where a.line=? and a.trno=?
      union all 
      select a.line, ifnull(at.type,'') as attendancetype, ifnull(a.status,0) as attendancetypeline, '' as bgcolor, concat(a.trno, '-', a.line, '-', date(a.atdate)) as id, concat(stud.fname, ' ', stud.mname, ' ', stud.lname) as clientname, a.clientid
      from en_glstudents as a
      left join en_attendancetype as at on at.line = a.status
      left join en_studentinfo as stud on stud.clientid = a.clientid
      where a.line=? and a.trno=?", [$line, $trno, $line, $trno]);
    return $data;
  }

  public function loaddata($config, $type = '')
  {
    $trno = $config['params']['tableid'];
    if ($type == '') {
      $date = date_create($config['params']['timestamp']['date']);
      $date = date_format($date, 'Y/m/d');
    } else {
      if (!empty($config['params']['data'])) {
        $data1 = $config['params']['data'][0]['id'];
        $data1 = explode('-', $data1);
        $date = $data1[2];
      } else {
        $date = '';
      }
    }
    $head = $this->coreFunctions->opentable("select head.courseid, head.syid, head.subjectid, head.scheddocno from en_athead as head  where head.trno=?
    union all 
    select head.courseid, head.syid, head.subjectid, head.scheddocno from en_glhead as head  where head.trno=?", [$trno, $trno]);
    $courseid = $syid = $subjectid = 0;
    if (!empty($head)) {
      $courseid = $head[0]->courseid;
      $syid = $head[0]->syid;
      $subjectid = $head[0]->subjectid;
      $scheddocno = $head[0]->scheddocno;
    }
    $qry = "select " . $trno . " as trno, 0 as line, '' as id, concat(stud.fname,' ', stud.mname,' ', stud.lname) as clientname, head.clientid, '' as bgcolor,'' as attendancetype
      from glhead as head
      left join glsubject as subj on subj.trno=head.trno
      left join en_studentinfo as stud on stud.clientid = head.clientid
      left join en_subject as sub on sub.trno = subj.subjectid
        left join en_glsubject as scs on scs.trno=subj.screfx and scs.line=subj.sclinex
        left join en_glhead as sc on sc.trno=scs.trno
      left join client on client.clientid=head.clientid left join transnum as t on t.trno=scs.trno
      where head.doc='ER' and head.courseid=? and head.syid=? and subj.subjectid=? and t.docno=?";
    $data = $this->coreFunctions->opentable($qry, [$courseid, $syid, $subjectid, $scheddocno]);
    if (!empty($data)) {
      foreach ($data as $d) {
        $stat = $this->coreFunctions->opentable("select ifnull(a.line,0) as line, ifnull(at.type,'') as attendancetype, ifnull(a.status,0) as attendancetypeline from en_atstudents as a left join en_attendancetype as at on at.line = a.status where date(a.atdate)=date(?) and a.clientid=? 
        union all 
        select ifnull(a.line,0) as line, ifnull(at.type,'') as attendancetype, ifnull(a.status,0) as attendancetypeline from en_glstudents as a left join en_attendancetype as at on at.line = a.status where date(a.atdate)=date(?) and a.clientid=?", [$date, $d->clientid, $date, $d->clientid]);
        if (!empty($stat)) {
          $d->attendancetype = $stat[0]->attendancetype;
          $d->attendancetypeline = $stat[0]->attendancetypeline;
          $d->line = $stat[0]->line;
        } else {
          $d->attendancetype = '';
          $d->attendancetypeline = $d->line = 0;
        }
        $d->id = $trno . '-' . $d->line . '-' . $date;
      }
    }
    return $data;
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
    $query = "select brandid, brand_desc from frontend_ebrands
    order by brandid";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_brand_masterfile_layout($data, $config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
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
    $str .= $this->reporter->col('BRAND MASTERFILE', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand Name', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_brand_masterfile_layout($data, $filters)
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
      $str .= $this->reporter->col($data[$i]['brand_desc'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
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
