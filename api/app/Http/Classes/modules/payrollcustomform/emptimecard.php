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



class emptimecard
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Employee`s Timecard';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  public $fields = ['empid', 'dateid', 'daytype', 'schedin', 'schedout', 'schedbrkin', 'schedbrkout', 'actualin', 'actualout', 'actualbrkin', 'actualbrkout', 'brk1stin', 'brk1stout', 'brk2ndin', 'brk2ndout', 'abrk1stin', 'abrk1stout', 'abrk2ndin', 'abrk2ndout', 'reghrs', 'absdays', 'latehrs', 'underhrs', 'earlyothrs', 'othrs', 'ndiffhrs', 'ndiffot', 'ismactualin', 'ismactualout', 'isobactualin', 'isobactualout', 'ischangesched', 'ismbrkin', 'ismbrkout', 'ismlunchin', 'ismlunchout',   'logactualin',   'logactualout',   'loglunchin',   'loglunchout'];

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
    $btns = array(
      'others'
    );
    $buttons = $this->btnClass->create($btns);


    // $addedparams = ['empcode', 'empname', 'empdivname', 'deptname', 'section', 'shiftcode', 'start', 'end'];
    $buttons['others']['items']['first'] =  ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigationht']];
    $buttons['others']['items']['prev'] =  ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigationht']];
    $buttons['others']['items']['next'] = ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigationht']];
    $buttons['others']['items']['last'] = ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigationht']];
    return $buttons;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];

    $columns = ['action', 'details', 'dateid', 'daytype', 'schedin', 'schedbrkout', 'schedbrkin', 'schedout', 'actualin', 'actualbrkout', 'actualbrkin', 'actualout', 'abrk1stout', 'abrk1stin',  'abrk2ndout', 'abrk2ndin', 'reghrs', 'absdays', 'latehrs', 'underhrs', 'earlyothrs', 'othrs', 'ndiffhrs', 'ndiffot'];


    $sortcolumn =  ['action', 'details', 'dateid', 'daytype', 'schedin', 'schedbrkout', 'schedbrkin', 'schedout', 'actualin', 'actualbrkout', 'actualbrkin', 'actualout', 'abrk1stout', 'abrk1stin',  'abrk2ndout', 'abrk2ndin', 'reghrs', 'absdays', 'latehrs', 'underhrs', 'earlyothrs', 'othrs', 'ndiffhrs', 'ndiffot'];

    if ($companyid == 62) { //one sky
      $sortcolumn = ['action', 'details', 'dateid', 'daytype', 'schedin', 'schedbrkout', 'schedbrkin', 'schedout', 'actualin', 'actualbrkout', 'actualbrkin', 'actualout', 'abrk1stout', 'abrk1stin',  'abrk2ndout', 'abrk2ndin', 'reghrs', 'absdays', 'latehrs', 'underhrs', 'earlyothrs', 'othrs', 'ndiffhrs', 'ndiffot'];
    }

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'sortcolumns' => $sortcolumn
      ]
    ];

    $stockbuttons = [];
    if ($companyid == 58) { //cdo
      array_push($stockbuttons, 'timecardinfo', 'applications'); //applications
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // $obj[0][$this->gridname]['obj'] = 'editgrid';
    $obj[0][$this->gridname]['descriptionrow'] = [];


    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$daytype]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$details]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$daytype]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$reghrs]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][$absdays]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][$latehrs]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][$underhrs]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][$othrs]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][$earlyothrs]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][$ndiffhrs]['type'] = 'highlightinput';
    $obj[0][$this->gridname]['columns'][$ndiffot]['type'] = 'highlightinput';

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 40px;whiteSpace: normal;min-width:40px;max-width:40px';
    $obj[0][$this->gridname]['columns'][$details]['style'] = 'width: 400px;whiteSpace: normal;min-width:400px;max-width:400px';

    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0][$this->gridname]['columns'][$daytype]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:90px';

    $obj[0][$this->gridname]['columns'][$schedin]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';
    $obj[0][$this->gridname]['columns'][$schedbrkout]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';
    $obj[0][$this->gridname]['columns'][$schedbrkin]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';
    $obj[0][$this->gridname]['columns'][$schedout]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';
    $obj[0][$this->gridname]['columns'][$actualin]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';
    $obj[0][$this->gridname]['columns'][$actualbrkout]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';
    $obj[0][$this->gridname]['columns'][$actualbrkin]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';
    $obj[0][$this->gridname]['columns'][$actualout]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px';

    if ($companyid != 58) { //cdo
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$abrk1stin]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$abrk1stout]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$abrk2ndin]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$abrk2ndout]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$details]['type'] = 'coldel';
    }

    if ($companyid != 62) { //onesky
      $obj[0][$this->gridname]['columns'][$earlyothrs]['type'] = 'coldel';
    }

    switch ($companyid) { //allow edit daytype
      case 62: //onesky
        $obj[0][$this->gridname]['columns'][$daytype]['action'] = 'lookupdaytype';
        $obj[0][$this->gridname]['columns'][$daytype]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$daytype]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
        $obj[0][$this->gridname]['columns'][$schedbrkout]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$schedbrkin]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$actualbrkout]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$actualbrkin]['type'] = 'coldel';
        break;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $tbuttons = ['saveallentry'];

    if ($companyid == 51 || $companyid == 53) {
      $tbuttons = [];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    //$obj[0]
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['empid', 'empcode', 'empname', 'empdivname', 'deptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'empcode.style', 'padding:0px;');
    data_set($col1, 'empdivname.type', 'input');

    $fields = [['start', 'end'], 'shiftcode', 'section', 'refresh'];

    if ($companyid != 44) { // not stonepro
      if (($key = array_search('refresh', $fields)) !== false) {
        unset($fields[$key]);
      }
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, "shiftcode.type", "input");
    data_set($col2, 'section.type', 'input');
    data_set($col2, 'refresh.action', 'load');
    data_set($col2, 'start.style', 'padding:0px;');
    data_set($col2, 'end.style', 'padding:0px;');
    $fields = ['refresh'];
    if ($companyid == 44) { //stonpro
      $fields = [['lblsource', 'lblrem'], ['lblpassbook', 'lbldestination'], ['lblearned', 'lblreconcile'], ['lblrecondate', 'lblcleared']];
    }
    if ($companyid == 58) { // cdohris
      $fields = ['refresh', ['lblrecondate', 'lblcleared'], ['lblcostuom', 'lblbilling']];
    }
    $col3 = $this->fieldClass->create($fields);

    if ($companyid == 44) { //stonepro
      data_set($col3, 'lblrem.label', 'COLOR CODING');
      data_set($col3, 'lblrem.style', 'font-size:12px;font-weight:bold;background-color:green;color:green;border:2px solid green;');
      data_set($col3, 'lblsource.label', 'Change Schedule');
      data_set($col3, 'lblsource.style', 'font-size:12px;font-weight:bold;');

      data_set($col3, 'lbldestination.label', 'COLOR CODING');
      data_set($col3, 'lbldestination.style', 'font-size:12px;font-weight:bold;background-color:blue;color:blue;border:2px solid blue;');
      data_set($col3, 'lblpassbook.label', 'DTR From Manual Entry');
      data_set($col3, 'lblpassbook.style', 'font-size:12px;font-weight:bold;width:550px;');

      data_set($col3, 'lblreconcile.label', 'COLOR CODING');
      data_set($col3, 'lblreconcile.style', 'font-size:12px;font-weight:bold;background-color:pink;color:pink;border:2px solid pink;');
      data_set($col3, 'lblearned.label', 'DTR From OB');
      data_set($col3, 'lblearned.style', 'font-size:12px;font-weight:bold;');
    }
    if ($companyid == 58) { // cdohris
      data_set($col3, 'lblcleared.label', 'COLOR CODING');
      data_set($col3, 'lblcleared.style', 'font-size:12px;font-weight:bold;background-color:grey;color:grey;border:2px solid grey;');
      data_set($col3, 'lblrecondate.label', 'No Log Undertime');
      data_set($col3, 'lblrecondate.style', 'font-size:12px;font-weight:bold;');

      data_set($col3, 'lblbilling.label', 'COLOR CODING');
      data_set($col3, 'lblbilling.style', 'font-size:12px;font-weight:bold;background-color:orange;color:orange;border:2px solid orange;');
      data_set($col3, 'lblcostuom.label', 'Suspended');
      data_set($col3, 'lblcostuom.style', 'font-size:12px;font-weight:bold;');
    }

    data_set($col3, 'refresh.action', 'load');


    $fields = [];
    if ($companyid == 44) { // stonepro
      $fields = [['lblunclear', 'lblendingbal'], ['lblattached', 'lblreceived'], ['lblforapproval', 'lblinvreq']];
    }
    if ($companyid == 58) { //cdo
      $fields = [['lblsource', 'lblrem'], ['lblpassbook', 'lbldestination'], ['lblearned', 'lblreconcile']];
    }
    $col4 = $this->fieldClass->create($fields);

    if ($companyid == 44) { // stonepro
      data_set($col4, 'lblendingbal.label', 'COLOR CODING');
      data_set($col4, 'lblendingbal.style', 'font-size:12px;font-weight:bold;background-color:purple;color:purple;border:2px solid purple;');
      data_set($col4, 'lblunclear.label', 'DTR From Wilcon');
      data_set($col4, 'lblunclear.style', 'font-size:12px;font-weight:bold;');

      data_set($col4, 'lblreceived.label', 'COLOR CODING');
      data_set($col4, 'lblreceived.style', 'font-size:12px;font-weight:bold;background-color:red;color:red;border:2px solid red;');
      data_set($col4, 'lblattached.label', 'DTR From Bundy Clock');
      data_set($col4, 'lblattached.style', 'font-size:12px;font-weight:bold;');

      data_set($col4, 'lblinvreq.label', 'COLOR CODING');
      data_set($col4, 'lblinvreq.style', 'font-size:12px;font-weight:bold;background-color:teal;color:teal;border:2px solid teal;');
      data_set($col4, 'lblforapproval.label', 'DTR From IT Excel');
      data_set($col4, 'lblforapproval.style', 'font-size:12px;font-weight:bold;');
    }

    if ($companyid == 58) { //cdo
      data_set($col4, 'lblrem.label', 'COLOR CODING');
      data_set($col4, 'lblrem.style', 'font-size:12px;font-weight:bold;background-color:green;color:green;border:2px solid green;');
      data_set($col4, 'lblsource.label', 'No Time-In');
      data_set($col4, 'lblsource.style', 'font-size:12px;font-weight:bold;');

      data_set($col4, 'lbldestination.label', 'COLOR CODING');
      data_set($col4, 'lbldestination.style', 'font-size:12px;font-weight:bold;background-color:blue;color:blue;border:2px solid blue;');
      data_set($col4, 'lblpassbook.label', 'No Time-Out');
      data_set($col4, 'lblpassbook.style', 'font-size:12px;font-weight:bold;width:550px;');

      data_set($col4, 'lblreconcile.label', 'COLOR CODING');
      data_set($col4, 'lblreconcile.style', 'font-size:12px;font-weight:bold;background-color:pink;color:pink;border:2px solid pink;');
      data_set($col4, 'lblearned.label', 'No Log Break-Out/In');
      data_set($col4, 'lblearned.style', 'font-size:12px;font-weight:bold;');
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      date_format(concat(year(curdate()),'-',month(curdate()),'-01'),'%Y-%m-%d') as start,
      date_format(adddate(date(concat(year(curdate()),'-',month(curdate()),'-01')), 14),'%Y-%m-%d') as end,
      '' as empcode,
      '' as empname,
      0 as empid,
      '' as empdivname,
      '' as deptname,
      '' as section,
      '' as shiftcode,
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


      case "navigation":
        return $this->navigation($config);
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
    $empid = $config['params']['dataparams']['empid'];
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];

    if ($empid == 0) {
      return ['status' => false, 'msg' => 'Select valid employee', 'data' => []];
    }

    $data = $this->getempschedule($empid, $start, $end, $config);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function getempschedule($empid, $start, $end, $config)
  {
    $companyid = $config['params']['companyid'];
    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $addfields = "";

    switch ($companyid) {
      case 44: //stonepro
        $addfields = ",t.ismactualin,t.ismactualout,t.isobactualin,t.isobactualout,t.ischangesched,
                      t.ismbrkin,t.ismbrkout,t.ismlunchin,t.ismlunchout,t.logactualin,t.logactualout,
                      t.loglunchin,t.loglunchout,

                      '' as actualin_bgcolor,'' as actualout_bgcolor,
                      '' as schedin_bgcolor,'' as schedout_bgcolor,
                      '' as schedbrkin_bgcolor, '' as schedbrkout_bgcolor,
                      '' as actualbrkin_bgcolor,'' as actualbrkout_bgcolor";
        break;

      case 58:
        $addfields = ", time(t.actualin) as timeisnologin,t.isnombrkout,
                        time(t.brk1stout) as timeisnombrkout,t.isnombrkin, time(t.brk1stin) as timeisnombrkin,
                        t.isnolunchout, time(t.actualbrkout) as timeisnolunchout,t.isnolunchin, 
                        time(t.actualbrkin) as timeisnolunchin,t.isnopbrkout,time(t.brk2ndout) as timeisnopbrkout,
                        t.isnopbrkin,time(t.brk2ndin) as timeisnopbrkin,
                        time(t.actualout) as timeisnologout,t.isnologpin,'' as details";
        break;

      case 62: //onesky
        $addfields = ",
                      '' as schedin_bgcolor,'' as schedout_bgcolor,
                      '' as actualbrkin_bgcolor,'' as actualbrkout_bgcolor";
        break;
    }

    $qry = "select t.empid, t.`daytype`, date(t.dateid) as dateid,date_format(t.schedin,'%Y-%m-%d %H:%i') as schedin, 
                  date_format(t.schedout,'%Y-%m-%d %H:%i') as schedout,date_format(t.schedbrkin,'%Y-%m-%d %H:%i') as schedbrkin, 
                  date_format(t.schedbrkout,'%Y-%m-%d %H:%i') as schedbrkout,date_format(t.actualin,'%Y-%m-%d %H:%i') as actualin, 
                  date_format(t.actualout,'%Y-%m-%d %H:%i') as actualout,date_format(t.actualbrkin,'%Y-%m-%d %H:%i') as actualbrkin, 
                  date_format(t.actualbrkout,'%Y-%m-%d %H:%i') as actualbrkout,t.reghrs, t.absdays, t.latehrs, t.latehrs2, t.lateoffset, t.underhrs, t.othrs, t.earlyothrs,
                  date_format(t.abrk1stin,'%Y-%m-%d %H:%i') as abrk1stin, date_format(t.abrk1stout,'%Y-%m-%d %H:%i') as abrk1stout,
                  date_format(t.abrk2ndin,'%Y-%m-%d %H:%i') as abrk2ndin, date_format(t.abrk2ndout,'%Y-%m-%d %H:%i') as abrk2ndout,
                  t.ndiffhrs, t.ndiffot,(case when t.`daytype`='RESTDAY' then 'bg-yellow-7' else '' end) as bgcolor, t.isnologin, 
                  t.isnologout, t.isnologbreak, t.isnologunder,t.isuspended,'' as isnologin_bgcolor,'' as isnologout_bgcolor,
                  '' as isnologbreak_bgcolor,'' as isnologunder_bgcolor $addfields
          from timecard as t 
          where date(t.dateid)>=? and date(t.dateid)<=? " . ($empid == 0 ? "" : " and t.empid=" . $empid) . "
          order by t.dateid";
    $returndata = [];

    $data = $this->coreFunctions->opentable($qry, [$start, $end]);
    $separator = "@@@";
    switch ($companyid) {
      case 44:   // stonepro

        $blue = 'bg-blue';
        $red = 'bg-red';
        $purple = 'bg-purple';
        $teal = 'bg-teal';
        $silver = 'bg-grey';
        $pink = 'bg-pink';
        $green = 'bg-green';

        foreach ($data as $key => $value) {
          $getbg = [];
          if ($value->actualin != null) {
            if ($value->ismactualin != 0) {
              $value->actualin_bgcolor = $blue;
            } else {

              switch ($value->logactualin) {
                case 1:
                case 2:
                  $value->actualin_bgcolor = $silver;
                  break;
                case 3:
                  $value->actualin_bgcolor = $teal;
                  break;
                case 4:
                  $value->actualin_bgcolor = $purple;
                  break;
                case 10:
                  $value->actualin_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->actualin_bgcolor = '';
          }
          if ($value->actualout != null) {
            if ($value->ismactualout != 0) {
              $value->actualout_bgcolor = $blue;
            } else {

              switch ($value->logactualout) {
                case 1:
                case 2:
                  $value->actualout_bgcolor = $silver;
                  break;
                case 3:
                  $value->actualout_bgcolor = $teal;
                  break;
                case 4:
                  $value->actualout_bgcolor = $purple;
                  break;
                case 10:
                  $value->actualout_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->actualout_bgcolor = '';
          }
          if ($value->actualin != null) {
            if ($value->isobactualin != 0) {
              $value->actualin_bgcolor = $pink;
            } else {

              switch ($value->logactualin) {
                case 1:
                case 2:
                  $value->actualin_bgcolor = $silver;
                  break;
                case 3:
                  $value->actualin_bgcolor = $teal;
                  break;
                case 4:
                  $value->actualin_bgcolor = $purple;
                  break;
                case 10:
                  $value->actualin_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->actualin_bgcolor = '';
          }
          if ($value->actualout != null) {
            if ($value->isobactualout != 0) {
              $value->actualout_bgcolor = $pink;
            } else {

              switch ($value->logactualout) {
                case 1:
                case 2:
                  $value->actualout_bgcolor = $silver;
                  break;
                case 3:
                  $value->actualout_bgcolor = $teal;
                  break;
                case 4:
                  $value->actualout_bgcolor = $purple;
                  break;
                case 10:
                  $value->actualout_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->actualout_bgcolor = '';
          }

          if ($value->schedbrkin != null) {
            if ($value->ismbrkin != 0) {
              $value->schedbrkin_bgcolor = $blue;
            } else {

              switch ($value->loglunchin) {
                case 1:
                case 2:
                  $value->schedbrkin_bgcolor = $silver;
                  break;
                case 3:
                  $value->schedbrkin_bgcolor = $teal;
                  break;
                case 4:
                  $value->schedbrkin_bgcolor = $purple;
                  break;
                case 10:
                  $value->schedbrkin_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->schedbrkin_bgcolor = '';
          }

          if ($value->schedbrkout != null) {
            if ($value->ismbrkout != 0) {
              $value->schedbrkout_bgcolor = $blue;
            } else {

              switch ($value->loglunchout) {
                case 1:
                case 2:
                  $value->schedbrkout_bgcolor = $silver;
                  break;
                case 3:
                  $value->schedbrkout_bgcolor = $teal;
                  break;
                case 4:
                  $value->schedbrkout_bgcolor = $purple;
                  break;
                case 10:
                  $value->schedbrkout_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->schedbrkout_bgcolor = '';
          }

          if ($value->actualbrkin != null) {
            if ($value->ismlunchin != 0) {
              $value->actualbrkin_bgcolor = $blue;
            } else {

              switch ($value->loglunchin) {
                case 1:
                case 2:
                  $value->actualbrkin_bgcolor = $silver;
                  break;
                case 3:
                  $value->actualbrkin_bgcolor = $teal;
                  break;
                case 4:
                  $value->actualbrkin_bgcolor = $purple;
                  break;
                case 10:
                  $value->actualbrkin_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->actualbrkin_bgcolor = '';
          }
          if ($value->actualbrkout != null) {
            if ($value->ismlunchout != 0) {
              $value->actualbrkout_bgcolor = $blue;
            } else {

              switch ($value->loglunchout) {
                case 1:
                case 2:
                  $value->actualbrkout_bgcolor = $silver;
                  break;
                case 3:
                  $value->actualbrkout_bgcolor = $teal;
                  break;
                case 4:
                  $value->actualbrkout_bgcolor = $purple;
                  break;
                case 10:
                  $value->actualbrkout_bgcolor = $red;
                  break;
              }
            }
          } else {
            $value->actualbrkout_bgcolor = '';
          }

          if ($value->schedin != null && $value->schedout != null) {
            if ($value->ischangesched != 0) {
              $value->schedin_bgcolor = $green;
              $value->schedout_bgcolor = $green;
            }
          } else {
            $value->schedin_bgcolor = '';
            $value->schedout_bgcolor = '';
          }

          array_push($returndata, $value);
        }
        return $returndata;
        break;

      case 58: //cdo
        foreach ($data as $key => $value) {

          if ($value->isuspended) {
            $value->bgcolor = "bg-orange";
          } else {

            if ($value->absdays > 0) {
              $value->details .= ' ABSENT: ' . $value->absdays . ' hrs ';
            }

            if ($value->latehrs > 0) {
              $value->details .= ($value->details != '' ? ',' : '') . ' LATE: ' . (float) $value->latehrs2 . ' min(s) ';
            }

            if ($value->lateoffset > 0) {
              $value->details .= ($value->details != '' ? ',' : '') . ' LATE OFFSET: ' . (float) $value->lateoffset . ' min(s) ';
            }

            if ($value->underhrs > 0) {
              $value->details .= ($value->details != '' ? ',' : '') . ' UNDERTIME: ' . $value->underhrs . ' hrs ';
            }

            if ($value->isnologin) {
              $value->actualin_bgcolor = 'bg-green';
              $value->details .= ($value->details != '' ? ',' : '') . ' NO MORNING IN: ' . $value->timeisnologin;
            }

            if ($value->isnologout) {
              $value->actualout_bgcolor = 'bg-blue';
              $value->details .= ($value->details != '' ? ',' : '') . ' NO AFTERNOON OUT: ' . $value->timeisnologout;
            }
            //////////////////////////////
            if ($value->isnombrkout) {
              $value->details .= ($value->details != '' ? ',' : '') . ' NO MORNING BREAK OUT: ' . $value->timeisnombrkout;
            }
            if ($value->isnombrkin) {
              $value->details .= ($value->details != '' ? ',' : '') . ' NO MORNING BREAK IN: ' . $value->timeisnombrkin;
            }
            if ($value->isnolunchout) {
              $value->details .= ($value->details != '' ? ',' : '') . ' NO LUNCH BREAK OUT: ' . $value->timeisnolunchout;
            }
            if ($value->isnolunchin) {
              $value->details .= ($value->details != '' ? ',' : '') . ' NO LUNCH BREAK IN: ' . $value->timeisnolunchin;
            }
            if ($value->isnopbrkout) {
              $value->details .= ($value->details != '' ? ',' : '') . ' NO AFTERNOON BREAK OUT: ' . $value->timeisnopbrkout;
            }
            if ($value->isnopbrkin) {
              $value->details .= ($value->details != '' ? ',' : '') . ' NO AFTERNOON BREAK IN: ' . $value->timeisnopbrkin;
            }
            if ($value->isnologpin) {
              $value->details .= ($value->details != '' ? ',' : '') . ' NO AFTERNOON IN ';
            }
            /////////////////////
            if ($value->isnologbreak) {
              $value->actualbrkin_bgcolor = 'bg-pink';
              $value->actualbrkout_bgcolor = 'bg-pink';
            }

            if ($value->isnologunder) {
              $value->actualout_bgcolor = 'bg-grey';
              $value->details .= ($value->details != '' ? ',' : '') . ' NO IN/OUT UNDERTIME';
            }
          }
        }
        return  $data;
        break;
      case 62: //one sky
        foreach ($data as $key => $value) {
          $value->schedin_bgcolor = 'bg-light-green-3';
          $value->schedout_bgcolor = 'bg-light-green-3';
        }
        return  $data;
        break;
      default:
        return  $data;
        break;
    }
  }


  private function savechanges($config)
  {
    $rows = $config['params']['rows'];
    $data = [];
    foreach ($rows as $key => $val) {
      if ($val["bgcolor"] != "") {
        foreach ($this->fields as $k) {
          if (isset($val[$k])) {
            $data[$k] = $this->othersClass->sanitizekeyfield($k, $val[$k]);
            if ($k == 'dateid') {
              $data[$k] = date_format(date_create($val[$k]), "Y-m-d");
            }
          }
        }
        $this->coreFunctions->sbcupdate("timecard", $data, ['empid' => $val["empid"], 'dateid' => $val["dateid"]]);
        $data = [];
      }
    }
  }
  public function navigation($config)
  {

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $data = $config['params']['dataparams'];
    $navigation = $config['params']['lookupclass2'];
    $return_module = 'headtable';
    $filter = '';
    $condition = '';
    $orderby = "order by client.clientname,emp.classrate limit 1";
    $filterdivision = "'" . $data['empdivname'] . "'";

    switch ($navigation) {
      case 'first':
        $condition = " $orderby";
        break;
      case 'prev':
        $orderby = " order by client.clientname desc,emp.classrate limit 1";
        $condition = " and client.clientname < '" . $data['empname'] . "' and divi.divname = '" . $data['empdivname'] . "' $orderby";
        break;
      case 'next':
        $condition = " and client.clientname > '" . $data['empname'] . "' and divi.divname = '" . $data['empdivname'] . "' $orderby";
        break;
      case 'last':
        $orderby = " order by client.clientname desc,emp.classrate limit 1";
        $condition = $orderby;
        break;
    }

    $query  = "select client.clientname,divi.divname as empdivname from employee as emp 
              left join client as client on client.clientid = emp.empid
              left join division as divi on divi.divid = emp.divid
              where 1=1 $condition ";
    $checkparams = $this->coreFunctions->opentable($query);
    $division = '';
    if (empty($checkparams)) { // same division and filter new division
      checkdivision:
      switch ($navigation) {
        case 'prev':
          $division = $this->coreFunctions->datareader("select divname as value from division where divname not in ( $filterdivision ) order by divname desc limit 1");
          break;
        case 'next':
          $division = $this->coreFunctions->datareader("select divname as value from division where divname not in ( $filterdivision ) order by divname limit 1");
          break;
      }
    }

    if (!empty($division)) {
      $filter = " and divi.divname = '" . $division . "' order by divi.divname,emp.classrate,client.clientname limit 1";
    } else {
      if (!empty($checkparams)) {
        $data['empname'] = $checkparams[0]->clientname;
      }
      $filter = " and client.clientname = '" . $data['empname'] . "' limit 1";
    }

    $qry = "select shft.shftcode as shiftcode,client.clientid as empid,client.client as empcode,client.clientname as empname,divi.divname as empdivname,
				dept.clientname as deptname,sect.sectname as section, '" . $data['start'] . "' as start,'" . $data['end'] . "' as end from client
				left join employee as emp on emp.empid = client.clientid
				left join client as dept on dept.clientid = emp.deptid
				left join division as divi on divi.divid = emp.divid
				left join section as sect on sect.sectid = emp.sectid
        left join tmshifts as shft on shft.line = emp.shiftid
				where client.isemployee = 1 $filter ";
    $addedparams = $this->coreFunctions->opentable($qry);

    if (empty($addedparams)) {
      $filterdivision .= ",'" . $division . "'";
      goto checkdivision;
    }
    $data2 = $this->getempschedule($addedparams[0]->empid, $data['start'], $data['end'], $config);
    return ['status' => true, 'msg' => '', 'trno' => 0, 'moduletype' => $return_module, 'data' => $addedparams[0], 'griddata' => ['entrygrid' => $data2], 'action' => 'load'];
  }
} //end class
