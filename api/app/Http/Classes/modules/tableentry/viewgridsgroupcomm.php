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

class viewgridsgroupcomm
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
    $attrib = array('load' => 2991, 'view' => 2991);
    return $attrib;
  }

  public function createTab($config)
  {
    $doc = $config['params']['doc'];

    $isselected = 0;
    $headagent = 1;
    $agent = 3;
    $stockgroup = 2;
    $agentcomamt = 4;


    $cols = ['isselected','headagentname','stock_itemgroup',  'agentname', 'agentcomamt'];

    $tab = [$this->gridname => ['gridcolumns' => $cols]];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$agentcomamt]['style'] = "text-align:left;width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][$stockgroup]['type'] = "label";
    $obj[0][$this->gridname]['showtotal'] = true;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'RELEASE';
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

    foreach ($data as $key => $value) {
      if ($value['added'] == 'true') {
        if ($value['agtype'] == 'agent') {
          $update = [
            'agrelease' => $value['releasedate'],
            'agreleaseby' => $user
          ];
          $this->coreFunctions->sbcupdate("incentives", $update, ['ptrno' => $value['ptrno'], 'trno' => $value['trno'], 'line' => $value['line'], 'agentid' => $value['agentid']]);
        } else {
          $update = [
            'ag2release' => $value['releasedate'],
            'ag2releaseby' => $user
          ];
          $this->coreFunctions->sbcupdate("incentives", $update, ['ptrno' => $value['ptrno'], 'trno' => $value['trno'], 'line' => $value['line'], 'agentid2' => $value['agentid2']]);
        }
      } else {
        array_push($return, $value);
      }
    }

    return ['status' => $status, 'msg' => $msg, 'data' => $return];
  }
} //end class
