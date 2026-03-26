<?php

namespace App\Http\Classes\modules\payrollcustomform;

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



class emptimecardperday
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Employee`s Timecard Per Day';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1621,
      'edit' => 1624,
      // 'new' => 24,
      'save' => 1622,
      'saveallentry' => 1622,
      // 'change' => 26,
      // 'delete' => 27,
      'print' => 1623
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    $btns = []; //actionload - sample of adding button in header - align with form/module name
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => [
      'gridcolumns' => [
        'empcode', 'empname', 'dateid', 'daytype', 'schedin', 'schedbrkout', 'schedbrkin', 'schedout',
        'actualin', 'actualbrkout', 'actualbrkin', 'actualout',
        'reghrs', 'absdays', 'latehrs', 'underhrs', 'othrs', 'ndiffhrs', 'ndiffot'
      ]
    ]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][0]['type'] = "label";
    $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][12]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][13]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][14]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][15]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][16]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][17]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][18]['type'] = 'highlightinput';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    //$obj[0]
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['start', 'shiftcode'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'load');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      date_format(concat(year(curdate()),'-',month(curdate()),'-01'),'%Y-%m-%d') as start,
      '' as empcode,
      '' as empname,
      '' as shiftcode,
      0 as shiftid,
      '0' as checkall
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
    // should return action
    $action = $config['params']["action2"];

    switch ($action) {
      case "load":
        return $this->loaddetails($config);
        break;

      case 'saveallentry':
      case "update":
        $this->savechanges($config);
        return $this->loaddetails($config);
        break;

      case "postinout":
        break;

      default:
        return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
        break;
    }
  }

  private function loaddetails($config)
  {
    $data = $this->getempschedule($config);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function getempschedule($config)
  {
    $shiftid = $config['params']['dataparams']['shiftid'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));

    $qry = "select t.line, e.clientname as empname, e.client as empcode, t.empid, t.`daytype`, date(t.dateid) as dateid, 
    date_format(t.schedin,'%Y-%m-%d %H:%i') as schedin, date_format(t.schedout,'%Y-%m-%d %H:%i') as schedout,
    date_format(t.schedbrkin,'%Y-%m-%d %H:%i') as schedbrkin, date_format(t.schedbrkout,'%Y-%m-%d %H:%i') as schedbrkout, 
    date_format(t.actualin,'%Y-%m-%d %H:%i') as actualin, date_format(t.actualout,'%Y-%m-%d %H:%i') as actualout, 
    date_format(t.actualbrkin,'%Y-%m-%d %H:%i') as actualbrkin, date_format(t.actualbrkout,'%Y-%m-%d %H:%i') as actualbrkout, 
    t.reghrs, t.absdays, t.latehrs, t.underhrs, t.othrs, t.ndiffhrs, t.ndiffot,
    (case when t.`daytype`='RESTDAY' then 'bg-yellow-7' else '' end) as bgcolor
    from timecard as t 
    left join client as e on e.clientid = t.empid
    left join employee as emp on emp.empid = e.clientid
    left join tmshifts as sh on sh.line = emp.shiftid
    where date(t.dateid)=? and sh.line = ?
    and emp.isactive =1
    order by e.clientname, t.dateid";

    return $this->coreFunctions->opentable($qry, [$start, $shiftid]);
  }


  private function savechanges($config)
  {
    $rows = $config['params']['rows'];
    foreach ($rows as $key => $val) {
      if ($val["bgcolor"] != "") {
        unset($val["bgcolor"]);
        unset($val["daytype"]);
        unset($val["empname"]);
        unset($val["empcode"]);
        foreach ($val as $k => $v) {
          $val[$k] = $this->othersClass->sanitizekeyfield($k, $val[$k]);
          if ($k == 'dateid') {
            $val[$k] = date_format(date_create($val[$k]), "Y-m-d");
          }
        }
        $this->coreFunctions->sbcupdate("timecard", $val, ['dateid' => $val["dateid"], 'empid' => $val["empid"]]);
      }
    }
  }
} //end class
