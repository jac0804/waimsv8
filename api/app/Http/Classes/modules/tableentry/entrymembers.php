<?php

namespace App\Http\Classes\modules\tableentry;

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

class entrymembers
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MEMBERS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
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
      'load' => 861
    );
    return $attrib;
  }

  public function createTab($config)
  {
    if (isset($config['params']['tableid'])) {
      $clientid = $config['params']['tableid'];
      $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
      $this->modulename = $this->modulename . ' - ' . $customername;
    }

    $clientname = 0;
    $addr = 1;
    $tel = 2;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['clientname', 'addr', 'tel']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Employee Name';
    $obj[0][$this->gridname]['columns'][$addr]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$tel]['readonly'] = true;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    return [];
  }

  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {

    $qry = "select clientname, addr, tel  from client where deptid = ? order by clientname";
    $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
    return $data;
  }
} //end class
