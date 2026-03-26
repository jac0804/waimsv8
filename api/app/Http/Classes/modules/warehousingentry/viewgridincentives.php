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

class viewgridincentives
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

    $added = 0;
    $docno = 1;
    $ourref = 2;
    $clientname = 3;
    $paymenttype = 4;
    $depodate = 5;
    $amt = 6;
    $agentname = 7;
    $agentcom = 8;
    $agentcomamt = 9;


    $cols = ['added', 'docno', 'ourref', 'clientname', 'paymenttype', 'depodate', 'amt', 'agentname', 'agentcom', 'agentcomamt'];

    $tab = [$this->gridname => ['gridcolumns' => $cols]];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$added]['label'] = 'Release';
    $obj[0][$this->gridname]['columns'][$ourref]['label'] = 'DR #';
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer';

    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ourref]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$depodate]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$agentcom]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$agentcomamt]['align'] = 'right';

    $obj[0][$this->gridname]['columns'][$added]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;;max-width:40px;';
    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align:right;width:50px;whiteSpace: normal;min-width:50px;;max-width:50px;';
    $obj[0][$this->gridname]['columns'][$depodate]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;;max-width:50px;';
    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:80px;whiteSpace: normal;min-width:60px;;max-width:80px;';
    $obj[0][$this->gridname]['columns'][$ourref]['style'] = 'width:80px;whiteSpace: normal;min-width:60px;;max-width:80px;';

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
