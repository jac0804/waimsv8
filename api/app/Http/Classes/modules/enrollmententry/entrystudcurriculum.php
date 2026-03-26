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

class entrystudcurriculum
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STUDENT CURRICULUM';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_scurriculum';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'clientid', 'cline', 'grade', 'subjectid', 'courseid'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'year', 'term', 'subjectcode', 'subjectname', 'grade']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:90px;whiteSpace:normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['generatestudcurriculum', 'deleteallitem', 'archivestudcurriculum'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['lookupclass'] = 'loaddata';
    $obj[1]['label'] = 'DELETE CURRICULUM';
    $obj[2]['lookupclass'] = 'loaddata';
    return $obj;
  }

  public function generatecurriculum($config)
  {
    $clientid = $config['params']['tableid'];

    $sctrno  = $this->coreFunctions->datareader("select trno as value from en_scurriculum where clientid=?", [$clientid]);
    if ($sctrno > 0) {
      $data = $this->loaddata($config);
      return ['status' => false, 'msg' => 'Student Curriculum is not empty! Delete Curriculum first! ', 'data' => $data];
    }

    $trno = $this->coreFunctions->datareader("select curriculumtrno as value from en_studentinfo where clientid=?", [$clientid]);
    $courseid = $this->coreFunctions->datareader("select courseid as value from en_studentinfo where clientid=?", [$clientid]);
    if ($trno == '') return json_encode(['msg' => 'Please select curriculum first.', 'status' => false]);
    $sub = $this->coreFunctions->opentable("select line, cline, subjectid from en_glsubject where trno=?", [$trno]);
    if (empty($sub)) return json_encode(['msg' => 'Curriculum empty. Please try again.', 'status' => false]);
    $data = [];
    foreach ($sub as $subject) {
      $this->coreFunctions->execqry("insert into en_scurriculum(trno, line, clientid, cline, subjectid, courseid, grade) values(?, ?, ?, ?, ?, ?, ?)", 'insert', [$trno, $subject->line, $clientid, $subject->cline, $subject->subjectid, $courseid, 0]);
      $row = $this->loaddataperrecord($trno, $subject->line, $clientid, $subject->cline);
      array_push($data, $row[0]);
    }
    return ['status' => true, 'msg' => 'Curriculum generated.', 'data' => $data];
  }

  public function archivecurriculum($config)
  {
    $clientid = $config['params']['tableid'];
    $qry = "insert into en_sarchive select * from en_scurriculum where clientid=?";
    $this->coreFunctions->execqry($qry, 'insert', [$clientid]);

    $this->coreFunctions->execqry('delete from ' . $this->table . ' where clientid=?', 'delete', [$clientid]);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
  }


  public function add($config)
  {
   
    return;
   
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
        $datas = [
          'trno' => $data2['trno'],
          'line' => $data2['line'],
          'points' => $data2['points']
        ];
        if ($datas['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $datas, ['trno' => $datas['trno'], 'line' => $datas['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function deleteallitem($config)
  {
    $isallow = true;
    $clientid = $config['params']['tableid'];
    $this->coreFunctions->execqry('delete from ' . $this->table . ' where clientid=?', 'delete', [$clientid]);

    return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from en_gssubcomponent where trno=? and line=? and compid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['compid']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($trno, $line, $clientid, $cline)
  {
    $qry = "select c.clientid, c.trno, c.line, c.grade, c.cline, c.subjectid, c.courseid, sub.subjectcode,
      sub.subjectname, '' as bgcolor, yr.year, yr.semid, sem.term
      from en_scurriculum as c
      left join en_subject as sub on sub.trno=c.subjectid
      left join en_glyear as yr on yr.line=c.cline and yr.trno=c.trno
      left join en_term as sem on sem.line=yr.semid
      where c.clientid=? and c.trno=? and c.line=? and c.cline=? order by c.cline,sub.subjectcode  ";
    return $this->coreFunctions->opentable($qry, [$clientid, $trno, $line, $cline]);
    
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['tableid'];
    $qry = "select c.clientid, c.trno, c.line, c.grade, c.cline, c.subjectid, c.courseid, sub.subjectcode,
      sub.subjectname, '' as bgcolor, yr.year, yr.semid, sem.term
      from en_scurriculum as c
      left join en_subject as sub on sub.trno=c.subjectid
      left join en_glyear as yr on yr.line=c.cline and yr.trno=c.trno
      left join en_term as sem on sem.line=yr.semid
      where c.clientid=? order by c.cline,sub.subjectcode ";
    $data = $this->coreFunctions->opentable($qry, [$clientid]);
    return $data;
  }
} //end class
