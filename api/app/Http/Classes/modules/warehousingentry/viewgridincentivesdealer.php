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

class viewgridincentivesdealer
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DETAILS';
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
    $attrib = array('load' => 2030, 'view' => 2030);
    return $attrib;
  }

  public function createTab($config)
  {
    $doc = $config['params']['doc'];

    $action = 0;
    $clientname = 1;
    $amt = 2;
    $clientquota = 3;
    $clientcom = 4;
    $clientcomamt = 5;
    $isquota = 6;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'clientname', 'amt', 'clientquota', 'clientcom', 'clientcomamt', 'isquota']]];
    $stockbuttons = ['incentivedocno'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientquota]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$isquota]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;;max-width:40px;';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;;max-width:100px;';
    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align:right;width:50px;whiteSpace: normal;min-width:50px;;max-width:50px;';
    $obj[0][$this->gridname]['columns'][$isquota]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;;max-width:40px;';
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
    return [];
  }

  public function saveallentry($config)
  {
    $msg = 'Successfully tagged as released';
    $status = true;
    $return = [];
    $data = $config['params']['data'];
    $user = $config['params']['user'];

    $releasedate = $this->othersClass->getCurrentTimeStamp();

    foreach ($data as $key => $value) {
      if ($value['added'] == 'true') {
        $agentquota = $this->othersClass->sanitizekeyfield('amt', $value['agentquota']);
        $sales = $this->othersClass->sanitizekeyfield('amt', $value['amt']);
      } else {
        array_push($return, $value);
      }
    }
    exithere:
    return ['status' => $status, 'msg' => $msg, 'data' => $return];
  }
} //end class
