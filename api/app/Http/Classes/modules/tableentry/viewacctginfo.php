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

use Carbon\Carbon;


class viewacctginfo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Accounting Entry';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:100%;max-width:70%;';
  private $fields = [];
  public $showclosebtn = true;


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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];

    $action = 0;
    $acno = 1;
    $acnoname = 2;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'acno', 'acnoname']]];
    $stockbuttons = ['detailinfo'];

    if ($companyid == 16) { //ati
      array_push($stockbuttons, 'rrstockinfoposted', 'viewcvitemsposted');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$acno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$acnoname]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";

    $obj[0][$this->gridname]['columns'][$acno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$acnoname]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$acnoname]['label'] = "Account Name";

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
    $sql = "select coa.acno, coa.acnoname, s.line, s.trno, s.refx from gldetail as s left join coa on coa.acnoid = s.acnoid where s.trno=?";
    return $sql;
  }

  public function loaddata($config)
  {
    $qry = $this->selectqry($config);
    $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
    return $data;
  }
} //end class
