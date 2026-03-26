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

class viewemprate
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RATE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'ratesetup';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['dateid', 'dateeffect', 'dateend', 'basicrate', 'remarks', 'type'];
  public $showclosebtn = false;


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
    $attrib = array(
      'load' => 1294
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 58: //cdohris
        $cols = ['docno', 'type', 'effdate', 'rate', 'salary', 'cola', 'remarks'];
        break;

      default:
        $cols = ['dateid', 'dateeffect', 'dateend', 'basicrate', 'remarks', 'type'];
        break;
    }
    foreach ($cols as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $cols]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    switch ($companyid) {
      case 58: //cdohris
        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:200px;max-width:200px;";
        $obj[0][$this->gridname]['columns'][$type]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$type]['label'] = "Salary Type";
        $obj[0][$this->gridname]['columns'][$effdate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rate]['label'] = "Class Rate";
        $obj[0][$this->gridname]['columns'][$rate]['style'] = "text-align:right;";
        $obj[0][$this->gridname]['columns'][$salary]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$salary]['style'] =  "width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$cola]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$cola]['style'] =  "width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:300px;max-width:300px;";
        break;

      default:
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateeffect]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateend]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$basicrate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$basicrate]['align'] = "right";
        $obj[0][$this->gridname]['columns'][$basicrate]['style'] = "text-align: right;width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$remarks]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$type]['type'] = "label";
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

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];


    switch ($companyid) {
      case 58:
        $qry = "select rate.trno, hes.docno,hes.salarytype as type,date(hes.effdate) as effdate,hes.hsperiod as rate,hes.tbasicrate as salary,hes.tcola as cola,hes.remarks FROM ratesetup as rate
                left join hrisnum as hs on hs.trno = rate.hstrno
                left join heschange as hes on hes.trno = hs.trno
                where rate.empid = ? and rate.hstrno <> 0 
                union all
                select rate.trno, hjo.docno,'' as type,date(hjo.effectdate) as effdate,hjo.classrate as rate,hjo.rate as salary,0 as cola,'' as remarks   FROM ratesetup as rate
                left join hrisnum as hj on hj.trno = rate.hjtrno
                left join hjoboffer as hjo on hjo.trno = rate.hjtrno
                where rate.empid = ? and rate.hjtrno <> 0 
                union all
                select trno, '' as docno, '' as type, date(dateeffect) as effdate, type as rate, basicrate as salary, 0 as cola, remarks
                from " . $this->table . " 
                where empid=? and hstrno=0 and hjtrno=0
                order by trno desc";
        $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid, $tableid]);
        break;
      default:
        $qry = "select empid, date(dateid) as dateid, date(dateeffect) as dateeffect, date(dateend) as dateend, basicrate, remarks, type 
                from " . $this->table . " 
                where empid=? order by trno desc ";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        break;
    }


    return $data;
  }
} //end class
