<?php

namespace App\Http\Classes\modules\enrollmententry;

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

class viewcurriculum
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CURRICULUM';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_sootherfees';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno','year','term','subjectcode','subjectname','lecture','hours','laboratory','units'];
  public $showclosebtn = true;


  public function __construct()
  {
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

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['year','term','subjectcode','subjectname','lecture','hours','laboratory','units']]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    
    $obj[0][$this->gridname]['columns'][0]['type'] = "label";
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:80px;"; //action
    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:40px;whiteSpace: normal;min-width:120px;"; //action
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:40px;whiteSpace: normal;min-width:120px;"; //action
    $obj[0][$this->gridname]['columns'][3]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:40px;whiteSpace: normal;min-width:180px;"; //action
    $obj[0][$this->gridname]['columns'][4]['type'] = "label";
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
    $obj[0][$this->gridname]['columns'][5]['type'] = "label";
    $obj[0][$this->gridname]['columns'][5]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
    $obj[0][$this->gridname]['columns'][6]['type'] = "label";
    $obj[0][$this->gridname]['columns'][6]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
    $obj[0][$this->gridname]['columns'][7]['type'] = "label";
    $obj[0][$this->gridname]['columns'][7]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
    $obj[0][$this->gridname]['columns'][8]['type'] = "label";
    $obj[0][$this->gridname]['columns'][8]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function loaddataperrecord($trno,$line){
   
    $qry = " select cs.trno,cy.year,sem.term,s.subjectcode,s.subjectname,cs.lecture,cs.hours,cs.laboratory,cs.units
    FROM en_glsubject as cs left join en_subject as s on s.trno=cs.subjectid
    left join en_glyear as cy on cy.trno=cs.trno and cy.line=cs.cline left join en_term as sem on sem.line=cy.semid where cs.trno=?";

    $data = $this->coreFunctions->opentable($qry,[$trno]);    
    return $data;
  }

  public function loaddata($config){  
    $tableid = $config['params']['tableid'];
    
    $qry =  " select cs.trno,cy.year,sem.term,s.subjectcode,s.subjectname,cs.lecture,cs.hours,cs.laboratory,cs.units
    FROM en_glsubject as cs left join en_subject as s on s.trno=cs.subjectid
    left join en_glyear as cy on cy.trno=cs.trno and cy.line=cs.cline left join en_term as sem on sem.line=cy.semid where cs.trno=? order by cy.year,sem.term";
    $data = $this->coreFunctions->opentable($qry,[$tableid]);

    return $data;
  }




























} //end class
