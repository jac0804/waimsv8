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

class viewempallowances
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ALLOWANCE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'allowsetup';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['dateid', 'dateeffect', 'dateend', 'basicrate', 'remarks', 'acno'];
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
      'load' => 1298
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $dateid = 0;
    $dateeffect = 1;
    $dateend = 2;
    $allowance = 3;
    $remarks = 4;
    $acnoname = 5;

    $tab = [$this->gridname => ['gridcolumns' => ['dateid', 'dateeffect', 'dateend', 'allowance', 'remarks', 'acnoname']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$acnoname]['label'] = "Account Name";

    switch (strtolower($config['params']['doc'])) {
      case 'employee':
      case 'myinfo':
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateeffect]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateend]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$allowance]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$acnoname]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$dateeffect]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$dateend]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$allowance]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align: right;";
        $obj[0][$this->gridname]['columns'][$allowance]['align'] = "right";

        $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$acnoname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
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
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $qry = "select trno,empid,dateid,dateeffect,dateend,allowance,remarks,type,acno,acnoname 
    from(select a.trno,a.empid, date(a.dateid) as dateid, date(a.dateeffect) as dateeffect, date(a.dateend) as dateend, 
    a.allowance, a.remarks, a.type, a.acno, p.codename as acnoname
    from " . $this->table . " as a 
    left join paccount as p on p.code = a.acno 
    where a.empid=? and a.allowance <> 0 and p.code in ('PT31', 'PT4', 'PT67')
    union all
    select ss.trno,ss.empid, date(ss.dateid) as dateid, date(ss.effdate) as dateeffect,
    '' as dateend,
    ss.amt, ss.docno,ss.balance, ss.acno, p.codename acnoname 
    from standardsetup as ss 
    left join paccount as p on p.line=ss.acnoid 
    where ss.empid=? and p.code in ('PT31', 'PT4', 'PT67')) as tb
    order by tb.trno desc";

    $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid]);
    return $data;
  }
} //end class
