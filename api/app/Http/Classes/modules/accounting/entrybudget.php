<?php

namespace App\Http\Classes\modules\accounting;

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



class entrybudget
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BUDGET';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $fields = ['acnoid', 'projectid', 'amt1', 'amt2', 'amt3', 'amt4', 'amt5', 'amt6', 'amt7', 'amt8', 'amt9', 'amt10', 'amt11', 'amt12', 'deptid', 'branch'];

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
      'view' => 2371,
      'save' => 2372,
      'saveallentry' => 2372,
      'print' => 3623
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'acno', 'total', 'amt1', 'amt2', 'amt3', 'amt4', 'amt5', 'amt6', 'amt7', 'amt8', 'amt9', 'amt10', 'amt11', 'amt12']
      ]
    ];
    $stockbuttons = ['addempbudget'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0]['entrygrid']['label'] = 'Budget Entry';
    $obj[0]['entrygrid']['columns'][1]['label'] = 'Account';
    $obj[0]['entrygrid']['columns'][1]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0]['entrygrid']['columns'][2]['readonly'] = false;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['year', 'project', ['refresh', 'print'], ['update']];
    if ($companyid == 10) { //afti
      $fields = ['year', 'project', 'branchname', 'deptname', ['refresh']];
    }
    $col1 = $this->fieldClass->create($fields);
    if ($companyid == 10) { //afti
      data_set($col1, 'deptname.type', 'lookup');
      data_set($col1, 'deptname.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptname.action', 'lookupclient');
    }
    data_set($col1, 'year.type', 'lookup');
    data_set($col1, 'year.class', 'sbccsreadonly');
    data_set($col1, 'year.lookupclass', 'lookupyear');
    data_set($col1, 'year.action', 'lookupyear');
    data_set($col1, 'refresh.action', 'load');
    data_set($col1, 'update.action', 'copyprevyear');
    data_set($col1, 'update.label', 'Copy Previous Year');
    data_set($col1, 'print.action', 'print');
    data_set($col1, 'refresh.style', 'width:100%;');
    data_set($col1, 'update.style', 'width:100%;');
    data_set($col1, 'update.confirm', true);
    data_set($col1, 'update.confirmlabel', 'Are you sure you want to copy previous year budget?');
    data_set($col1, 'print.style', 'width:100%;');


    $fields = ['radiooption'];
    $col2 = $this->fieldClass->create($fields);

    if ($companyid == 68) { //jda
      data_set($col2, 'radiooption.options', [
        ['label' => 'Balance Sheet', 'value' => 0, 'color' => 'red'],
        ['label' => 'Profit and Loss', 'value' => 1, 'color' => 'green']
      ]);
    }

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select '' as year,'' as project,1 as poption,0 as projectid,0 as deptid,'' as deptname,0 as branch,'' as branchname,'' as dept,'' as ddeptname ");

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
      case 'load':
        return $this->loadgrid($config);
        break;

      case 'copyprevyear':
        return $this->copyprevyear($config);
        break;

      case 'saveallentry':
      case 'update':
        $this->save($config);
        return $this->loadgrid($config);
        break;
      case 'print':
        return $this->reportsetup($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loadgrid($config)
  {
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $year  = $config['params']['dataparams']['year'];
    $project  = $config['params']['dataparams']['project'];
    $projectid  = $config['params']['dataparams']['projectid'];
    $deptid  = $config['params']['dataparams']['deptid'];
    $deptname  = $config['params']['dataparams']['deptname'];
    $branchid  = $config['params']['dataparams']['branch'];
    $branchname  = $config['params']['dataparams']['branchname'];
    $poption  = $config['params']['dataparams']['poption'];
    $budget = [];

    if ($poption == 1) {
      $cat = "('R','E')";
    } else {
      $cat = "('A','L','C')";
    }

    $addonfilter = '';
    if ($deptid != 0) $addonfilter .= " and b.deptid=" . $deptid;
    if ($branchid != 0) $addonfilter .= " and b.branch=" . $branchid;


    $qry = "select b.line,b.year,concat(c.acno,' ',c.acnoname) as acno,c.acnoname,b.acnoid,b.projectid,format(b.amt1," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt1,format(b.amt2," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt2,format(b.amt3," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt3,format(b.amt4," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt4,format(b.amt5," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt5,format(b.amt6," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt6,format(b.amt7," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt7,format(b.amt8," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt8,format(b.amt9," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt9,format(b.amt10," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt10,format(b.amt11," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt11,format(b.amt12," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt12,format((b.amt1+b.amt2+b.amt3+b.amt4+b.amt5+b.amt6+b.amt7+b.amt8+b.amt9+b.amt10+b.amt11+b.amt12)," . $this->companysetup->getdecimal('price', $config['params']) . ") as total,'' as bgcolor ,
    d.client as dept,d.clientid as deptid,br.clientid as branch,br.client as branchcode,d.clientname as deptname,br.clientname as branchname,'' as ddeptname    
    from budget as b left join coa as c on c.acnoid = b.acnoid left join projectmasterfile as p on p.line = b.projectid 
    left join client as d on d.clientid = b.deptid left join client as br on br.clientid = b.branch where c.cat in " . $cat . " and b.projectid=" . $projectid . " and b.year=" . $year . " " . $addonfilter;
    $data = $this->coreFunctions->opentable($qry);

    if (empty($data)) {
      if ($year == '' || $project == '') {
        return ['status' => 'false', 'msg' => 'Please fill all fields.', 'griddata' => [], 'action' => 'load'];
      }

      if ($companyid == 10) { //afti
        if ($deptname == '') return ['status' => 'false', 'msg' => 'Please fill department.', 'griddata' => [], 'action' => 'load'];
        if ($branchname == '') return ['status' => 'false', 'msg' => 'Please fill branch.', 'griddata' => [], 'action' => 'load'];
      }
    }

    $addonfilter2 = '';
    if ($deptid != 0) $addonfilter2 .= " and deptid=" . $deptid;
    if ($branchid != 0) $addonfilter2 .= " and branch=" . $branchid;

    $accts = "select acno,acnoid,acnoname from coa where acno not in (select distinct parent from coa) and acnoid not in 
      (select acnoid from budget where projectid=" . $projectid . " and year =" . $year . ") and cat in " . $cat . " order by acno";
    $dataacc  = $this->coreFunctions->opentable($accts);

    if (!empty($dataacc)) {
      foreach ($dataacc as $key => $value) {
        $budget[$key]['acnoid'] = $dataacc[$key]->acnoid;
        $budget[$key]['projectid'] = $projectid;
        $budget[$key]['branch'] = $branchid;
        $budget[$key]['deptid'] = $deptid;
        $budget[$key]['year'] = $year;
      }


      if ($this->coreFunctions->sbcinsert("budget", $budget)) {
        $this->coreFunctions->logconsole($qry . " - " . $project . "-" . $year . "-" . $deptid . "-" . $branchid);
        $data = $this->coreFunctions->opentable($qry, [$projectid, $year, $deptid, $branchid]);
        return ['status' => true, 'msg' => 'Saved. Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
      } else {
        return ['status' => false, 'msg' => 'Error getting accounts', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
      }
    } else {
      $this->coreFunctions->logconsole($qry);
      return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
  }

  private function copyprevyear($config)
  {
    $companyid  = $config['params']['companyid'];
    $year       = $config['params']['dataparams']['year'];
    $projectid  = $config['params']['dataparams']['projectid'];
    $project    = $config['params']['dataparams']['project'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $branchid   = $config['params']['dataparams']['branch'];
    $branchname = $config['params']['dataparams']['branchname'];
    $poption    = $config['params']['dataparams']['poption'];

    if ($year == '' || $project == '') {
      return ['status' => 'false', 'msg' => 'Please fill all fields.', 'griddata' => [], 'action' => 'load'];
    }



    $prevYear = $year - 1;

    if ($poption == 1) {
      $cat = "('R','E')";
    } else {
      $cat = "('A','L','C')";
    }

    $addonfilter = '';
    if ($deptid != 0) $addonfilter .= " and b.deptid=" . $deptid;
    if ($branchid != 0) $addonfilter .= " and b.branch=" . $branchid;


    $qryPrev = "select b.acnoid, b.amt1, b.amt2, b.amt3, b.amt4, b.amt5, b.amt6,
                       b.amt7, b.amt8, b.amt9, b.amt10, b.amt11, b.amt12
                from budget as b
                left join coa as c on c.acnoid = b.acnoid
                where c.cat in " . $cat . " and b.projectid=? and b.year=?" . $addonfilter;

    $prevData = $this->coreFunctions->opentable($qryPrev, [$projectid, $prevYear]);


    if (empty($prevData)) {
      $result = $this->loadgrid($config);
      $result['msg'] = 'No data found for previous year (' . $prevYear . ').';
      $result['status'] = 'false';
      return $result;
    }


    foreach ($prevData as $row) {
      $qryUpdate = "update budget set 
            amt1=?, amt2=?, amt3=?, amt4=?, amt5=?, amt6=?,
            amt7=?, amt8=?, amt9=?, amt10=?, amt11=?, amt12=?
            where acnoid=? and projectid=? and year=? and deptid=? and branch=?";

      $this->coreFunctions->opentable($qryUpdate, [
        $row->amt1,
        $row->amt2,
        $row->amt3,
        $row->amt4,
        $row->amt5,
        $row->amt6,
        $row->amt7,
        $row->amt8,
        $row->amt9,
        $row->amt10,
        $row->amt11,
        $row->amt12,
        $row->acnoid,
        $projectid,
        $year,
        $deptid,
        $branchid
      ]);
    }


    $result = $this->loadgrid($config);
    $result['msg'] = 'Previous year (' . $prevYear . ') data copied successfully.';
    $result['status'] = true;
    return $result;
  }


  private function save($config)
  {
    $companyid = $config['params']['companyid'];

    $rows = $config['params']['rows'];

    $dateTables = ['budget'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    foreach ($rows as $key => $val) {
      if ($val["bgcolor"] != "") {
        if ($companyid == 10) { //afti
            $val['total'] = $this->othersClass->sanitizekeyfieldFast("amt", $val['total'], $lookups);
            $budget = round($val['total'] / 12, 2);
            $val['amt1'] = $budget;
            $val['amt2'] = $budget;
            $val['amt3'] = $budget;
            $val['amt4'] = $budget;
            $val['amt5'] = $budget;
            $val['amt6'] = $budget;
            $val['amt7'] = $budget;
            $val['amt8'] = $budget;
            $val['amt9'] = $budget;
            $val['amt10'] = $budget;
            $val['amt11'] = $budget;
            $val['amt12'] = $budget;
          }
        }

        foreach ($this->fields as $k) {
          $val[$k] = $this->othersClass->sanitizekeyfieldFast($k, $val[$k], $lookups);
        }

        unset($val['acno']);
        unset($val['acnoname']);
        unset($val['dept']);
        unset($val['branchcode']);
        unset($val['deptname']);
        unset($val['branchname']);
        unset($val['ddeptname']);
        unset($val['bgcolor']);
        unset($val['total']);
        $this->coreFunctions->sbcupdate("budget", $val, ['line' => $val["line"]]);
      }
    }
  }

  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'action' => 'print'];
  }

  public function reportdata($config)
  {
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'radioreporttype', 'print', 'year', 'project', 'projectid', 'deptid', 'branch', 'poption'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Monthly Format', 'value' => '0', 'color' => 'red'],
      ['label' => 'Annual Format', 'value' => '1', 'color' => 'red']
    ]);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'year.type', 'hidden');
    data_set($col1, 'project.type', 'hidden');
    data_set($col1, 'projectid.type', 'hidden');
    data_set($col1, 'deptid.type', 'hidden');
    data_set($col1, 'branch.type', 'hidden');
    data_set($col1, 'poption.type', 'hidden');
    data_set($col1, 'start.type', 'hidden');
    data_set($col1, 'end.type', 'hidden');
    data_set($col1, 'username.type', 'hidden');
    data_set($col1, 'moduledesc.type', 'hidden');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $year      = isset($config['params']['dataparams']['year'])      ? $config['params']['dataparams']['year']      : '';
    $projectid = isset($config['params']['dataparams']['projectid']) ? $config['params']['dataparams']['projectid'] : 0;
    $deptid    = isset($config['params']['dataparams']['deptid'])    ? $config['params']['dataparams']['deptid']    : 0;
    $branch    = isset($config['params']['dataparams']['branch'])    ? $config['params']['dataparams']['branch']    : 0;
    $poption   = isset($config['params']['dataparams']['poption'])   ? $config['params']['dataparams']['poption']   : 1;
    $project   = isset($config['params']['dataparams']['project'])   ? $config['params']['dataparams']['project']   : '';
    $reporttype = isset($config['params']['dataparams']['reporttype']) ? $config['params']['dataparams']['reporttype'] : '0';

    return $this->coreFunctions->opentable(
      "select
            'PDFM' as print,
            '$reporttype' as reporttype,
            '' as moduledoc,
            '' as modulename,
            '$year' as year,
            '$project' as project,
            $projectid as projectid,
            $deptid as deptid,
            $branch as branch,
            $poption as poption
        "
    );
  }
} //end class