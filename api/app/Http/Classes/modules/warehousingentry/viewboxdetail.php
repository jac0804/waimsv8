<?php

namespace App\Http\Classes\modules\warehousingentry;

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

class viewboxdetail
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BOX DETAILS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%';
  public $issearchshow = true;
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
    $attrib = array('load' => 2037, 'view' => 2037, 'save' => 2038);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['boxno', 'action']]];
    $stockbuttons = ['showboxdetails'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['align'] = 'center';
    $obj[0][$this->gridname]['columns'][1]['align'] = 'center';
    $obj[0][$this->gridname]['columns'][0]['style'] = 'text-align:center';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'text-align:center';
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
    $trno = $config['params']['tableid'];
    $table = "boxinginfo";
    $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);
    if($posted){
      $table = "hboxinginfo";
    }
    $qry = "select distinct boxno, scandate, trno from " . $table . " where trno=? order by boxno";
    return $this->coreFunctions->opentable($qry, [$trno]);
  }
} //end class
