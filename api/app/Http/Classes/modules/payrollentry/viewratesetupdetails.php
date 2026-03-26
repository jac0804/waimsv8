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

class viewratesetupdetails {
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DETAILS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'ratesetup';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['dateid','dateeffect','dateend','remarks','basicrate', 'type'];
  public $showclosebtn = false;
  private $enrollmentlookup;

 

  public function __construct() {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
  }

  public function getAttrib(){
    $attrib = array('load'=>0
                    );
    return $attrib;
  }

  public function createTab($config){
    $empname = 0;
    $dateid = 1;
    $dateeffect = 2;
    $dateend = 3;
    $basicrate = 4;
    $type = 5;
    $remarks = 6;

    $tab = [$this->gridname=>['gridcolumns'=>['empname', 'dateid','dateeffect','dateend','basicrate', 'type','remarks']]];
    
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$dateeffect]['label'] = 'Date Start';
    $obj[0][$this->gridname]['columns'][$dateeffect]['field'] = 'dateeffect';
    $obj[0][$this->gridname]['columns'][$dateeffect]['name'] = 'dateeffect';

    $obj[0][$this->gridname]['columns'][$dateend]['label'] = 'Date End';
    $obj[0][$this->gridname]['columns'][$dateend]['field'] = 'dateend';
    $obj[0][$this->gridname]['columns'][$dateend]['name'] = 'dateend';

    $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dateeffect]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dateend]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$basicrate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$type]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$empname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$dateeffect]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$dateend]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$basicrate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    
    $obj[0][$this->gridname]['columns'][$type]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; text-align: center';
    $obj[0][$this->gridname]['columns'][$type]['align'] = 'center';

    $obj[0][$this->gridname]['columns'][$remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    return $obj;
  }


  public function createtabbutton($config) {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  private function selectqry() {
    $qry = "rs.trno, 
      date(rs.dateid) as dateid,
      date(rs.dateeffect) as dateeffect,
      date(rs.dateend) as dateend,
      rs.remarks,
      rs.basicrate,
      rs.type
    ";
    return $qry;
  }

  public function loaddata($config) {
    $empid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select.",'' as bgcolor ";
    $qry = "select ".$select.",
    concat(e.emplast, ', ', e.empfirst, ' ', e.empmiddle) as empname
    from ".$this->table." as rs
    left join employee as e on e.empid=rs.empid
    where rs.empid=? order by trno desc";
    $data = $this->coreFunctions->opentable($qry,[$empid]);
    return $data;
  }
































} //end class
