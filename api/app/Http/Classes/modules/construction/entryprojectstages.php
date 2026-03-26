<?php

namespace App\Http\Classes\modules\construction;

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
use App\Http\Classes\lookup\constructionlookup;

class entryprojectstages
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ADD STAGES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'stages';
  private $othersClass;
  public $style = 'width:100%;max-width: 100%';
  private $fields = ['projectid', 'subproject', 'stage', 'cost', 'projectprice', 'ar', 'ap', 'projpercent', 'completed', 'completedar', 'paid', 'boq', 'pr', 'po', 'rr', 'jr', 'jo', 'jc', 'mi', 'wac', 'line'];
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->constructionlookup = new constructionlookup;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {

    switch ($config['params']['doc']) {
      case 'PM':
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'stagename', 'description', 'projpercent', 'cost', 'projectprice', 'ar', 'ap', 'boq', 'pr', 'po', 'rr', 'jr', 'jo', 'jc', 'mi', 'completed', 'completedar']]];
        break;

      default:
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'stagename', 'projpercent', 'cost', 'projectprice', 'ar', 'ap', 'boq', 'pr', 'po', 'rr', 'jr', 'jo', 'jc', 'mi', 'completed', 'completedar']]];
        break;
    }

    $stockbuttons = ['save', 'delete', 'addprojactivity'];

    if ($config['params']['doc'] != 'PM') {
      $tab = [$this->gridname => ['gridcolumns' => ['action', 'stagename']]];
      $stockbuttons = ['addprojactivity'];
      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][1]['label'] = 'Description';
      $obj[0][$this->gridname]['columns'][1]['style'] = 'width:280px;whiteSpace: normal;min-width:180px;';
    } else {
      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][1]['label'] = 'Stages';
      $obj[0][$this->gridname]['columns'][1]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
      $obj[0][$this->gridname]['columns'][2]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
      $obj[0][$this->gridname]['columns'][4]['label'] = 'Estimated Cost';
      $obj[0][$this->gridname]['columns'][3]['label'] = '%Stage';
      $obj[0][$this->gridname]['columns'][4]['readonly'] = false;
      $obj[0][$this->gridname]['columns'][4]['required'] = true;
      $obj[0][$this->gridname]['columns'][5]['required'] = true;
    }


    if (isset($config['params']['row'])) {
      $row = $config['params']['row'];
      $subname = $this->coreFunctions->getfieldvalue('subproject', 'subproject', 'line=?', [$row['line']]);
      $this->modulename = 'ADD STAGES -' . $subname;
    }

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addstages', 'saveallentry'];
    if ($config['params']['doc'] != 'PM') {
      $tbuttons = [];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {

    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'addstages':
        return $this->lookupstages($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $data = [];
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];
        $exist = $this->coreFunctions->getfieldvalue("stages", "stage", "trno=? and stage=? and subproject=?", [$trno, $row['line'], $row['subproject']]);
        if (strlen($exist) == 0) {
          $data['line'] = 0;
          $data['trno'] = $row['trno'];
          $data['stage'] = $row['line'];
          $data['ar'] = 0;
          $data['ap'] = 0;
          $data['cost'] = 0;
          $data['projectprice'] = 0;
          $data['paid'] = 0;
          $data['boq'] = 0;
          $data['pr'] = 0;
          $data['po'] = 0;
          $data['rr'] = 0;
          $data['jo'] = 0;
          $data['jc'] = 0;
          $data['mi'] = 0;
          $data['wac'] = 0;
          $data['jr'] = 0;
          $data['projpercent'] = '';
          $data['completed'] = '';
          $data['completedar'] = '';
          $data['stagename'] = $row['stage'];
          if ($config['params']['doc'] == 'PM') {
            $data['description'] = $row['description'];
          }
          $data['subproject'] = $row['subproject'];
          $data['projectid'] = $row['projectid'];
          $data['bgcolor'] = 'bg-blue-2';
          return ['status' => true, 'msg' => 'Add stage success.', 'data' => $data];
        } else {
          return [];
        }

        break;
    }
  }

  public function lookupstages($config)
  {
    $trno = $config['params']['tableid'];
    $sline = $config['params']['row']['line'];
    $projectid = $config['params']['row']['projectid'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Stages',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );


    // lookup columns
    $cols = [
      ['name' => 'stage', 'label' => 'Stage', 'align' => 'left', 'field' => 'stage', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select line,stage,description," . $trno . " as trno," . $sline . " as subproject," . $projectid . " as projectid from stagesmasterfile";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 852, '/tableentries/tableentry/entrystages');
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } //end function



  private function selectqry($config)
  {

    switch ($config['params']['doc']) {
      case 'PM':
        $desc = " , description";
        break;

      default:
        $desc = "";
        break;
    }
    return "
    select trno, line, subproject, projpercent,completed,completedar,
    FORMAT(ar," . $this->companysetup->getdecimal('price', $config['params']) . ") as ar,
    FORMAT(ap," . $this->companysetup->getdecimal('price', $config['params']) . ") as ap,
    stagename,
    FORMAT(cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
    FORMAT(projectprice," . $this->companysetup->getdecimal('price', $config['params']) . ") as projectprice,
    projectid,stage,'' as bgcolor,
    FORMAT(paid," . $this->companysetup->getdecimal('price', $config['params']) . ") as paid,
    FORMAT(boq," . $this->companysetup->getdecimal('price', $config['params']) . ")as boq,
    FORMAT(pr," . $this->companysetup->getdecimal('price', $config['params']) . ") as pr,
    FORMAT(po," . $this->companysetup->getdecimal('price', $config['params']) . ") as po,
    FORMAT(rr," . $this->companysetup->getdecimal('price', $config['params']) . ") as rr,
    FORMAT(jo," . $this->companysetup->getdecimal('price', $config['params']) . ") as jo,
    FORMAT(jc," . $this->companysetup->getdecimal('price', $config['params']) . ") as jc,
    FORMAT(mi," . $this->companysetup->getdecimal('price', $config['params']) . ") as mi,
    FORMAT(wac," . $this->companysetup->getdecimal('price', $config['params']) . ") as wac,
    FORMAT(jr," . $this->companysetup->getdecimal('price', $config['params']) . ") as jr $desc";
  }
  //case (jc+mi) when 0 then 0 else concat(ifnull(round(((jc+mi)/cost)*100,2),0),'%') end as 
  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $data['trno'] = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $this->computeestimatedcost($row['trno'], $row['subproject'], $row['stage'], $row['projpercent']);
        $returnrow = $this->loaddataperrecord($config, $row['trno'], $line, $row['subproject']);
        $this->logger->sbcwritelog(
          $config['params']['tableid'],
          $config,
          'STAGES',
          ' CREATE - '
            . ' SUB PROJECT ' . $row['subproject']
            . ' STAGE NAME ' . $row['stagename']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $this->computeestimatedcost($row['trno'], $row['subproject'], $row['stage'], $row['projpercent']);
        $returnrow = $this->loaddataperrecord($config, $row['trno'], $row['line'], $row['subproject']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2], $config['params']['doc'], $config['params']['companyid']);
        }

        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];

        if ($config['params']['companyid'] == 8 && $config['params']['doc'] == 'PM') { //maxipro
          if ($data[$key]['projpercent'] == 0 || $data[$key]['projectprice'] == 0) {
            return ['status' => false, 'msg' => '% Stages and Project Price should not be 0.'];
          }
        }


        if ($data[$key]['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          $this->computeestimatedcost($data[$key]['trno'], $data[$key]['subproject'], $data[$key]['stage'], $data[$key]['projpercent']);
        } else {
          $data2['trno'] = $data[$key]['trno'];
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->computeestimatedcost($data[$key]['trno'], $data[$key]['subproject'], $data[$key]['stage'], $data[$key]['projpercent']);
          $this->logger->sbcwritelog(
            $config['params']['tableid'],
            $config,
            'STAGES',
            ' CREATE - '
              . ' SUB PROJECT ' . $data[$key]['subproject']
              . ' STAGE NAME ' . $data[$key]['stagename']
          );
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function delete($config)
  {
    $row = $config['params']['row'];
    if (floatval($row['completed']) != 0 || floatval($row['completedar']) != 0) {
      return ['status' => false, 'msg' => 'Can`t delete, already processed.'];
    } else {
      $check = $this->coreFunctions->getfieldvalue("activity", "stage", "trno=? and subproject=? and stage =?", [$row['trno'], $row['subproject'], $row['stage']]); //check if with activity
      if (floatval($check) > 0) {
        return ['status' => false, 'msg' => 'Can`t delete, already have activities.'];
      } else {
        $qry = "delete from stages where trno=? and line=? and subproject=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['subproject']]);
        $this->logger->sbcwritelog(
          $config['params']['tableid'],
          $config,
          'STAGES',
          ' DELETE - '
            . ' SUB PROJECT ' . $row['subproject']
            . ' STAGE NAME ' . $row['stagename']
        );
        return ['status' => true, 'msg' => 'Successfully deleted.'];
      }
    }
  }

  private function computeestimatedcost($trno, $subproject, $stage, $stagepercent)
  {
    $projectcost = $this->coreFunctions->getfieldvalue("pmhead", "cost", "trno=?", [$trno]);
    $percentage = $this->coreFunctions->getfieldvalue("subproject", "projpercent", "trno=? and line=?", [$trno, $subproject]);

    $percentage = str_replace("%", "", $percentage);
    $stagepercent = str_replace("%", "", $stagepercent);
    $subcost = $projectcost * (floatval($percentage) / 100);
    $stagecost = round($subcost * (floatval($stagepercent) / 100), 2);

    $this->coreFunctions->sbcupdate($this->table, ["cost" => $stagecost], ["trno" => $trno, "stage" => $stage, "subproject" => $subproject]);
  }

  private function loaddataperrecord($config, $trno, $stageid, $subproject)
  {
    $select = $this->selectqry($config);
    $select = $select . " from (select st.trno, st.line,st.stage, st.subproject, st.projpercent,
    st.completed,st.completedar,st.ar,st.ap,st.cost,st.projectprice,st.projectid,'' as bgcolor,
    s.stage as stagename,st.paid,st.boq,st.pr,st.po,st.rr,st.jo,st.jc,st.mi,st.wac,st.jr";
    $qry = $select . " 
    from stages as st 
    left join stagesmasterfile as s on s.line = st.stage 
    where st.trno=?  and st.line=? and st.subproject=?) as A";


    $data = $this->coreFunctions->opentable($qry, [$trno, $stageid, $subproject]);
    return $data;
  }

  public function loaddata($config)
  {
    if ($config['params']['doc'] != 'PM') {
      $trno = $config['params']['tableid'];
      $htable = 'bahead';
      $hstock = 'bastock';
      $isposted = $this->othersClass->isposted2($trno, "transnum");
      if ($isposted) {
        $htable = "hbahead";
        $hstock = "hbastock";
      }
      $project = $this->coreFunctions->getfieldvalue($htable, "projectid", "trno=?", [$trno]);
      $sproject = $this->coreFunctions->getfieldvalue($htable, "subproject", "trno=?", [$trno]);
      $select = $this->selectqry($config);
      $select = $select . " from (select st.trno, st.line,st.stage, st.subproject, st.projpercent,st.completed,st.completedar,st.ar,st.ap,st.cost,st.projectprice,st.projectid,'' as bgcolor,
      s.stage as stagename,st.paid,st.boq,st.pr,st.po,st.rr,st.jo,st.jc,st.mi,st.wac,st.jr ";
      $qry = $select . " from stages as st left join stagesmasterfile as s on s.line = st.stage where st.projectid=? and st.subproject=?) as A";
      $data = $this->coreFunctions->opentable($qry, [$project, $sproject]);
    } else {
      $trno = $config['params']['row']['trno'];
      $line = $config['params']['row']['line'];

      $select = $this->selectqry($config);
      $select = $select . " from (select st.trno, st.line,st.stage, st.subproject, st.projpercent,
      st.completed,st.completedar,st.ar,st.ap,st.cost,st.projectprice,st.projectid,'' as bgcolor,
      s.stage as stagename,st.paid,st.boq,st.pr,st.po,st.rr,st.jo,st.jc,st.mi,st.wac,st.jr,s.description ";
      $qry = $select . " 
      from stages as st 
      left join stagesmasterfile as s on s.line = st.stage 
      where st.trno=? and st.subproject=?) as A";

      $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    }



    return $data;
  }

  public function lookuplogs($config)
  {

    $doc = strtoupper($config['params']['lookupclass']);
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['row']['line'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno 
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
