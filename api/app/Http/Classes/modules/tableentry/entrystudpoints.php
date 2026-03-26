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

class entrystudpoints
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STUDENT POINTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_gegrades';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'clientid', 'gcsubcode', 'gcsubtopic', 'gcsubnoofitems', 'points', 'ctrno', 'cline', 'scline', 'gsline'];
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'listclientname', 'gcsubcode', 'gcsubtopic', 'gcsubnoofitems', 'ehpoints']]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:90px;whiteSpace:normal;min-width:90px;";

    // clientname     
    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';

    $style = 'width:100px;whiteSpace:normal;min-width:100px;';
    // gcsubcode
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['style'] = $style;

    // gcsubtopic
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['style'] = $style;

    // gcsubnoofitems
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['style'] = $style;

    // ehpoints
    $obj[0][$this->gridname]['columns'][5]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][5]['style'] = $style;

    $obj[0][$this->gridname]['visiblecol'][1] = 'clientname';
    $obj[0][$this->gridname]['visiblecol'][5] = 'points';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['generateestud', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $data = [];
    $data['trno'] = $trno;
    $data['compid'] = $line;
    $data['line'] = 0;
    $data['gcsubcode'] = '';
    $data['gcsubtopic'] = '';
    $data['gcsubnoofitems'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    return "select s.trno, s.line, s.clientid, c.client, c.clientname, s.gcsubcode, s.topic as gcsubtopic, s.noofitems as gcsubnoofitems, s.points, s.ctrno, s.cline, s.scline, s.gsline";
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $d = [];
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
        $d['trno'] = $data2['trno'];
        $d['clientid'] = $data2['clientid'];
        $d['ctrno'] = $data2['ctrno'];
        $d['cline'] = $data2['cline'];
        $d['scline'] = $data2['scline'];
        $d['gsline'] = $data2['gsline'];
        if ($datas['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $datas, ['trno' => $datas['trno'], 'line' => $datas['line']]);
        }
        if (!empty($d)) {
          $total = $this->gettotalpoints($d);
          $this->updatetotal($d, $total);
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
    $data2 = [
      'trno' => $data['trno'],
      'line' => $data['line'],
      'points' => $data['points']
    ];
    if ($data2['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
        $total = $this->gettotalpoints($data);
        $this->updatetotal($data, $total);
        $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function updatetotal($data, $total)
  {
    $this->coreFunctions->execqry("update en_scurriculum set grade=? where trno=? and line=? and cline=?", 'update', [$total, $data['ctrno'], $data['scline'], $data['cline']]);
  }

  public function gettotalpoints($data)
  {
    $data = $this->coreFunctions->opentable("select sum(points) as points from " . $this->table . " where trno=? and clientid=?", [$data['trno'], $data['clientid']]);
    if (!empty($data)) {
      return $data[0]->points;
    }
    return 0;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from en_gegrades where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = $select . " from " . $this->table . " as s left join client as c on c.clientid=s.clientid where s.trno=? and s.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = $select . " from " . $this->table . " as s left join client as c on c.clientid=s.clientid where s.trno=? and s.gsline=? order by s.gcsubcode";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }


  public function generateEStud($config)
  {
    $data2 = [];
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $checktrno = $this->coreFunctions->datareader("select trno as value from en_gegrades where trno=? and gsline=?", [$trno, $line]);
    if (!empty($checktrno)) {
      return ['msg' => 'Cannot generate; the table is not empty.', 'status' => false, 'data' => $this->loaddata($config)];
    }
    $d = $this->coreFunctions->opentable("select adviserid, yr, semid, syid, periodid, subjectid, schedtrno from en_gehead where trno=?", [$trno]);
    if (!empty($d)) {
      $adviserid = $d[0]->adviserid;
      $yr = $d[0]->yr;
      $semid = $d[0]->semid;
      $syid = $d[0]->syid;
      $periodid = $d[0]->periodid;
      $subjectid = $d[0]->subjectid;
      $schedtrno = $d[0]->schedtrno;
    } else {
      $adviserid = $yr = $semid = $syid = $periodid = $subjectid = $schedtrno = 0;
    }
    $sql = "select s.trno, s.line, h.dateid, h.docno, h.clientid, client.client, client.clientname, s.trno, s.screfx, s.screfx,
      hb.docno,  s.subjectid, h.courseid, sb.instructorid, h.yr, h.semid, h.syid, h.periodid, s.ctrno, s.cline, s.scline
      from glhead as h
      left join glsubject as s on s.trno = h.trno
      left join client on client.clientid = h.clientid
      left join en_glsubject as sb on sb.trno = s.refx and sb.line = s.linex
      left join en_glhead as hb on hb.trno = sb.trno
      left join en_glsubject as sc on sc.trno=s.screfx and sc.line=s.sclinex
      where h.doc='ER' and s.qa=0 and sc.trno=? and s.subjectid=?
      union all
      select s.trno, s.line, h.dateid, h.docno, h.clientid, client.client, client.clientname, s.trno, s.screfx, s.screfx,
      hb.docno,s.subjectid, h.courseid, sb.instructorid, h.yr, h.semid, h.syid, h.periodid, s.ctrno, s.cline, s.scline
      from glhead as h
      left join glsubject as s on s.trno = h.trno
      left join client on client.clientid = h.clientid
      left join en_glsubject as sb on sb.trno = s.refx and sb.line = s.linex
      left join en_glhead as hb on hb.trno = sb.trno
      left join en_glsubject as sc on sc.trno=s.screfx and sc.line=s.sclinex
      where h.doc='ED' and s.isdrop=0 and sc.trno=? and s.subjectid=?";
    $data = $this->coreFunctions->opentable($sql, [$schedtrno, $subjectid, $schedtrno, $subjectid]);

    if (!empty($data)) {
      foreach ($data as $dt) {
        $sql = "select head.trno, head.docno, head.dateid, head.sy, head.section, head.adviserid, head.scheddocno,
          head.roomid, head.schedday, head.schedtime, subcomp.component, subcomp.gccode, subcomp.gcsubcode, subcomp.topic, subcomp.noofitems, subcomp.line
        from en_gehead as head
        left join en_gesubcomponent as subcomp on subcomp.trno = head.trno
        where head.doc='EH' and head.trno=? and subcomp.line=?";

        $dd = $this->coreFunctions->opentable($sql, [$trno, $line]);

        if (!empty($dd)) {
          foreach ($dd as $datas) {
            $qry = "select line as value from en_gegrades where trno=? order by line desc limit 1";
            $eline = $this->coreFunctions->datareader($qry, [$trno]);
            if ($eline == '') $eline = 0;
            $eline += 1;
            $s = [
              'trno' => $trno,
              'line' => $eline,
              'clientid' => $dt->clientid,
              'gccode' => $datas->gccode,
              'gcsubcode' => $datas->gcsubcode,
              'components' => $datas->component,
              'topic' => $datas->topic,
              'noofitems' => $datas->noofitems,
              'points' => 0,
              'gstrno' => $datas->trno,
              'gsdocno' => $datas->docno,
              'refx' => $dt->trno,
              'linex' => $dt->line,
              'ctrno' => $dt->ctrno,
              'cline' => $dt->cline,
              'scline' => $dt->scline,
              'gsline' => $datas->line
            ];
            $return = $this->insertstudents($s);

            if ($return['status']) {
              array_push($data2, $return['row'][0]);
            }
          }
        }
      }
    }
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $data2];
  }

  public function insertstudents($row)
  {
    $data = $this->othersClass->sanitize($row, 'ARRAY');

    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcinsert($this->table, $data) == 1) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Insert failed.'];
      }
    } else {
      return ['status' => false, 'msg' => 'Insert failed.'];
    }
  }
} //end class
