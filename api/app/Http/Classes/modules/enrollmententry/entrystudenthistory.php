<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\lookup\enrollmentlookup;

class entrystudenthistory
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'HISTORY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_studentcredentials';
  private $othersClass;
  public $style = 'width:900px;min-width:900px;';
  private $fields = ['line', 'credentialid', 'amt', 'clientid', 'percentdisc', 'ref'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
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
    $tab = [$this->gridname => ['gridcolumns' => ['sy', 'period', 'docno', 'dateid', 'pydocno', 'reqdate', 'coursename', 'section', 'instructorname', 'origdocno']]];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][0]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'EI Doc#';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['label'] = 'EI Date';
    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['label'] = 'ER Doc#';
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][5]['label'] = 'ER Date';
    $obj[0][$this->gridname]['columns'][6]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][6]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][7]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][7]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][8]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][8]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][8]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][8]['label'] = 'Adviser';
    $obj[0][$this->gridname]['columns'][9]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][9]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][9]['label'] = 'Schedule';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['clientid'], $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['clientid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from en_studentcredentials where clientid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['clientid'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($clientid, $line)
  {
    return [];
  }

  private  function selectqry()
  {
    return "select instr.clientname as instructorname,es.docno as origdocno,head.trno, head.docno as docno,date(head.dateid) as dateid,st.clientname as student,course.coursecode,course.coursename,period.name as period,period.sy,sec.section,h.docno as pydocno,h.dateid as reqdate";
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $qry = $select .= " from en_glhead as head left join en_glsubject as sub on sub.trno=head.trno
      left join client as st on st.clientid=head.clientid left join en_course as course on course.line=head.courseid
      left join en_period as period on period.line=head.periodid
      left join en_section as sec on sec.line=head.sectionid left join en_glhead as es on es.trno=sub.refx left join client as instr on instr.clientid=es.adviserid
      left join (
      select sh.trno,sh.sotrno,sh.docno,sh.dateid from en_sjhead as sh where sh.doc='ER'
      union all
      select sh.trno,sh.sotrno,sh.docno,sh.dateid from glhead as sh where sh.doc='ER') as h on h.sotrno=head.trno
      where head.doc='EI'  and st.clientid=?
      group by es.docno,head.trno,head.docno,head.dateid,st.clientname,course.coursecode,course.coursename,
        period.code,period.name,period.sy,sec.section,h.docno,h.dateid, instr.clientname
      union all
      ".$select." from en_sohead as head left join en_sosubject as sub on sub.trno=head.trno
      left join client as st on st.client=head.client left join en_course as course on course.line=head.courseid
      left join en_period as period on period.line=head.periodid
      left join en_section as sec on sec.line=head.sectionid left join en_glhead as es on es.trno=sub.refx left join client as instr on instr.clientid=es.adviserid
      left join (
      select sh.trno,sh.sotrno,sh.docno,sh.dateid from en_sjhead as sh where sh.doc='ER'
      union all
      select sh.trno,sh.sotrno,sh.docno,sh.dateid from glhead as sh where sh.doc='ER') as h on h.sotrno=head.trno
      where head.doc='EI'  and st.clientid=?
      group by es.docno,head.trno,head.docno,head.dateid,st.clientname,course.coursecode,course.coursename,
        period.code,period.name,period.sy,sec.section,h.docno,h.dateid, instr.clientname";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid]);
    return $data;
  }
} //end class
