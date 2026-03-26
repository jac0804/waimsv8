<?php

namespace App\Http\Classes\modules\customform;

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

class viewdistribution
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ACCOUNT DISTRIBUTION';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:90%;max-width:90%;';
  public $issearchshow = true;
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $companyid = $config['params']['companyid'];

    $columns = ['acno', 'acnoname', 'client', 'clientname', 'checkno', 'postdate', 'db', 'cr', 'fdb', 'fcr', 'project', 'subproject', 'stage', 'branch', 'deptname', 'poref', 'podate', 'rem'];
    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = [];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // totalfield
    $obj[0][$this->gridname]['totalfield'] = ['db', 'cr'];

    // 2 = client
    switch ($config['params']['doc']) {
      case 'SS':
      case 'MT':
      case 'ST':
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Warehouse';
        break;
      case 'CP':
      case 'SJ':
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer';
        break;
    }


    $obj[0][$this->gridname]['columns'][$client]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0][$this->gridname]['columns'][$postdate]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0][$this->gridname]['columns'][$acnoname]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';

    // 4 = db
    $obj[0][$this->gridname]['columns'][$db]['align'] = 'right';
    // 5 = cr
    $obj[0][$this->gridname]['columns'][$cr]['align'] = 'right';
    // 6 = fdb
    $obj[0][$this->gridname]['columns'][$fdb]['align'] = 'right';
    // 7 = fcr
    $obj[0][$this->gridname]['columns'][$fcr]['align'] = 'right';

    $obj[0][$this->gridname]['columns'][$branch]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$deptname]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$acnoname]['label'] = 'Account Name';

    if (!$isproject) {
      $obj[0][$this->gridname]['columns'][$project]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$subproject]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$stage]['type'] = 'coldel';
    }

    if ($companyid != 10) { //not afti
      $obj[0][$this->gridname]['columns'][$branch]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$deptname]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$project]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$poref]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$podate]['type'] = 'coldel';
    }

    if ($companyid != 35) { //not aquamax
      $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$client]['type'] = 'coldel';
    }

    if ($companyid != 39) { //not cbbsi
      $obj[0][$this->gridname]['columns'][$rem]['type'] = 'coldel';
    }

    if ($companyid == 59) { //roosevelt
      if ($config['params']['doc'] == 'BE' || $config['params']['doc'] == 'RE') {
        $obj[0][$this->gridname]['columns'][$checkno]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
        $obj[0][$this->gridname]['columns'][$acnoname]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
        $obj[0][$this->gridname]['columns'][$client]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
        $obj[0][$this->gridname]['columns'][$fdb]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$fcr]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$project]['type'] = 'coldel';

        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer';
      }
    } else {
      $obj[0][$this->gridname]['columns'][$checkno]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['db'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['cr'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    if ($config['params']['doc'] == 'LA') {
      $trno = isset($config['params']['trno']) ? $config['params']['trno'] : $config['params']['clientid'];
      return $this->coreFunctions->opentable('select format(sum(db),2) as db, format(sum(cr),2) as cr from gldetail where trno = ' . $trno);
    } else {
      return $this->coreFunctions->opentable('select 0.00 as db, 0.00 as cr ');
    }
  }

  public function data($config)
  {
    $addedfield = "";
    switch ($config['params']['doc']) {
      case 'EA':
      case 'ER':
      case 'ED':
        $detail = 'en_gldetail';
        $addedfield = "";
        break;
      default:
        $detail = 'gldetail';
        $addedfield = ", detail.poref, left(detail.podate, 10) as podate";
        break;
    }

    if ($config['params']['doc'] == 'LA') {
      $trno = isset($config['params']['trno']) ? $config['params']['trno'] : $config['params']['clientid'];
    } else {
      $trno = $config['params']['trno'];
    }

    if ($config['params']['doc'] == 'BE' || $config['params']['doc'] == 'RE') {
      $addedfield .= ",concat(client.client,'~',client.clientname) as client";
    } else {
      $addedfield .= ",client.client,client.clientname";
    }


    $qry = "select coa.acno,coa.acnoname,left(detail.postdate,10) as postdate,FORMAT(ifnull(detail.db,0),2) as db,FORMAT(ifnull(detail.cr,0),2) as cr, 
    FORMAT(ifnull(detail.fdb,0),2) as fdb,FORMAT(ifnull(detail.fcr,0),2) as fcr,ifnull(p.code,'') as project,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,
    ifnull(b.clientname,'') as branch,ifnull(d.clientname,'') as deptname,detail.rem,detail.checkno
    " . $addedfield . "
    from " . $detail . " as detail 
    left join coa on coa.acnoid=detail.acnoid 
    left join client on client.clientid=detail.clientid 
    left join projectmasterfile as p on p.line = detail.projectid 
    left join subproject as s on s.line = detail.subproject and s.projectid = detail.projectid 
    left join stagesmasterfile as st on st.line = detail.stageid 
    left join client as b on b.clientid = detail.branch 
    left join client as d on d.clientid = detail.deptid  where detail.trno=?";

    switch ($config['params']['companyid']) {
      case 19: //housegem
        $qry = $qry . " order by detail.db desc, detail.cr";
        break;

      case 35: //aquamax
        $qry = $qry . " order by client.clientname, detail.line";
        break;

      case 56: //homeworks
        $qry = $qry . " order by detail.line";
        break;

      case 59: //roosevelt
        break;

      default:
        $qry = $qry . " order by db desc";
        break;
    }
    return $this->coreFunctions->opentable($qry, [$trno]);
  }
} //end class
