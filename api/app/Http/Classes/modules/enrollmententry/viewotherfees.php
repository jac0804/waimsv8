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

class viewotherfees
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OTHER FEES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_sootherfees';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'feesid', 'acnoid', 'isamt'];
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
    $tab = [$this->gridname => ['gridcolumns' => ['feescode','rem','isamt','acno','acnoname']]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; 
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; 
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true; 

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function loaddataperrecord($trno,$line){
    $select = $this->selectqry();
    $qry = $select." FROM en_sootherfees as stock left join transnum as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? and stock.line=? 
      union all 
      ".$select." FROM en_glotherfees as stock left join transnum as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? and stock.line=?  ";

    $data = $this->coreFunctions->opentable($qry,[$trno,$line,$trno,$line]);    
    return $data;
  }

  private  function selectqry()
  {
    return " select stock.trno,stock.line,f.feesdesc as rem,f.feescode,stock.feestype,coa.acno,coa.acnoname,stock.feesid,stock.acnoid,stock.isamt,
    '' as bgcolor,
    '' as errcolor  ";
  }

  public function loaddata($config){  
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    
    switch ($doc) {
      case 'ER': case 'ED':
        $table = 'en_sjotherfees';
        $htable = 'glotherfees';
        $tablenum = 'cntnum';
        break;
        default:
        $table = 'en_sootherfees';
        $htable = 'en_glotherfees';
        $tablenum = 'transnum';
        break;
    }

    $select = $this->selectqry();

    $qry =  $select." FROM ".$table." as stock left join ".$tablenum." as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? 
      union all 
      ".$select." FROM ".$htable." as stock left join ".$tablenum." as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? ";

    $data = $this->coreFunctions->opentable($qry,[$tableid,$tableid]);
    return $data;
  }




























} //end class
