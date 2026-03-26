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

class viewemptraining {
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TRAINING';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'traininghead';
  private $tabledetail = 'trainingdetail';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['tdate1','tdate2','title','venue','speaker','remarks','amt'];
  public $showclosebtn = false;
 

  public function __construct() {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib(){
    $attrib = array('load'=>1299
                    );
    return $attrib;
}


  public function createTab($config){
    $tr = 0;
    $tdate1 = 1;
    $tdate2 = 2;
    $title = 3;
    $venue = 4;
    $speaker = 5;
    $remarks = 6;
    $amt = 7;

    $tab = [$this->gridname=>['gridcolumns'=>['tr','tdate1','tdate2','title','venue','speaker','remarks','amt']]];
    
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab,$stockbuttons);    
    $obj[0][$this->gridname]['columns'][$amt]['label'] = "Budget";
    $obj[0][$this->gridname]['columns'][$amt]['readonly']=true;
    $obj[0][$this->gridname]['columns'][$remarks]['label'] = "Notes";
    $obj[0][$this->gridname]['columns'][$remarks]['readonly']=true;

    $obj[0][$this->gridname]['columns'][$tr]['style'] = "width:100px;whiteSpace: normal;min-width:100px;"; 
    $obj[0][$this->gridname]['columns'][$tdate1]['style'] = "width:100px;whiteSpace: normal;min-width:100px;"; 
    $obj[0][$this->gridname]['columns'][$tdate2]['style'] = "width:100px;whiteSpace: normal;min-width:100px;"; 
    $obj[0][$this->gridname]['columns'][$title]['style'] = "width:100px;whiteSpace: normal;min-width:100px;"; 
    $obj[0][$this->gridname]['columns'][$venue]['style'] = "width:100px;whiteSpace: normal;min-width:100px;"; 
    $obj[0][$this->gridname]['columns'][$speaker]['style'] = "width:100px;whiteSpace: normal;min-width:100px;"; 
    $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:100px;whiteSpace: normal;min-width:100px;"; 
    $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align: right;";

    $obj[0][$this->gridname]['columns'][$tr]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$tdate1]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$tdate2]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$title]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$venue]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$speaker]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$remarks]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$amt]['type'] = "label"; 

    return $obj;
  }


  public function createtabbutton($config){
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function loaddata($config){
    $tableid = $config['params']['tableid'];
    $qry = "select 'UNPOSTED' as tr, date(th.tdate1) as tdate1, date(th.tdate2) as tdate2, 
      th.title, th.venue, th.speaker, th.remarks, th.amt 
      from traininghead as th 
      left join trainingdetail as td on td.trno = th.trno 
      where td.empid = ?
      union all
      select 'POSTED' as tr, date(th.tdate1) as tdate1, date(th.tdate2) as tdate2, 
      th.title, th.venue, th.speaker, th.remarks, th.amt 
      from htraininghead as th 
      left join htrainingdetail as td on td.trno = th.trno
      where td.empid = ? ";
    $data = $this->coreFunctions->opentable($qry,[$tableid,$tableid]);
    return $data;
  }
































} //end class
