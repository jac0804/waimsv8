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
use App\Http\Classes\lookup\enrollmentlookup;

class viewallowancesetupdetails
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DETAILS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'allowsetup';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['dateid', 'dateeffect', 'dateend', 'remarks', 'allowance', 'type'];
  public $showclosebtn = false;
  private $enrollmentlookup;



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
    $companyid = $config['params']['companyid'];
    $codename = 0;
    $dateid = 1;
    $dateeffect = 2;
    $dateend = 3;
    $allowance = 4;
    $type = 5;
    $remarks = 6;

    switch ($companyid) {
      case 58:
        $cols = ['action', 'docno', 'code', 'codename', 'dateeffect', 'dateend', 'allowance', 'isliquidation'];
        break;

      default:
        $cols = ['codename', 'dateid', 'dateeffect', 'dateend', 'allowance', 'type', 'remarks'];
        break;
    }

    foreach ($cols as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $cols]];

    $stockbuttons = [];
    $period_access = $this->othersClass->checkAccess($config['params']['user'], 5599);
    if ($period_access) {
      array_push($stockbuttons, 'enddate');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    switch ($companyid) {
      case 58:

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:120px;max-width:120px;';

        $obj[0][$this->gridname]['columns'][$code]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$code]['style'] = 'width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Allowance Code';
        $obj[0][$this->gridname]['columns'][$codename]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$codename]['style'] = 'width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$codename]['label'] = 'Allowance Type';
        $obj[0][$this->gridname]['columns'][$dateeffect]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateeffect]['label'] = 'Period Start';
        $obj[0][$this->gridname]['columns'][$dateend]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateend]['label'] = 'Period End';
        $obj[0][$this->gridname]['columns'][$allowance]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$isliquidation]['type'] = 'label';
        break;

      default:
        $obj[0][$this->gridname]['columns'][$dateeffect]['label'] = 'Date Start';
        $obj[0][$this->gridname]['columns'][$dateeffect]['field'] = 'dateeffect';
        $obj[0][$this->gridname]['columns'][$dateeffect]['name'] = 'dateeffect';

        $obj[0][$this->gridname]['columns'][$dateend]['label'] = 'Date End';
        $obj[0][$this->gridname]['columns'][$dateend]['field'] = 'dateend';
        $obj[0][$this->gridname]['columns'][$dateend]['name'] = 'dateend';

        $obj[0][$this->gridname]['columns'][$codename]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateeffect]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateend]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$allowance]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$type]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$codename]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$dateeffect]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$dateend]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$allowance]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $obj[0][$this->gridname]['columns'][$type]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; text-align: center';
        $obj[0][$this->gridname]['columns'][$type]['align'] = 'center';

        $obj[0][$this->gridname]['columns'][$remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        break;
    }

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  private function selectqry($config)
  {
    $qry = "rs.trno, 
      date(rs.dateid) as dateid,
      date(rs.dateeffect) as dateeffect,
      date(rs.dateend) as dateend,
      rs.remarks,
      format(rs.allowance,2) as allowance,
      rs.type, p.codename,p.code,if(rs.isliquidation = 1,'YES','NO') as isliquidation
    ";
    return $qry;
  }

  public function loaddata($config)
  {
    $empid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    $select = $this->selectqry($config);

    $leftjoin = "";
    $addfields = "";
    if ($companyid == 58) { //cdohris
      $leftjoin = "left join heschange as hes on hes.trno = rs.refx";
      $addfields = ",hes.docno ";
    }

    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . ",
    concat(e.emplast, ', ', e.empfirst, ' ', e.empmiddle) as empname $addfields
    from " . $this->table . " as rs
    left join employee as e on e.empid=rs.empid
    left join paccount as p on p.line=rs.acnoid
    $leftjoin
    where rs.empid=? order by trno desc";


    $data = $this->coreFunctions->opentable($qry, [$empid]);
    return $data;
  }
} //end class
