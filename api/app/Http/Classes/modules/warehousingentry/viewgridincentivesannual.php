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

class viewgridincentivesannual
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
    $agentname = 1;
    $amt = 2;
    $agentquota = 3;
    $agentcom = 4;
    $agentcomamt = 5;
    $agtype = 6;

    $tab = [$this->gridname => ['gridcolumns' => ['added', 'agentname', 'amt', 'agentquota', 'agentcom', 'agentcomamt', 'agtype']]];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$added]['label'] = 'Release';
    $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$agentquota]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$agentquota]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$agentcom]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$agentcomamt]['align'] = 'right';

    $obj[0][$this->gridname]['columns'][$added]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;;max-width:40px;';
    $obj[0][$this->gridname]['columns'][$agentname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;;max-width:100px;';
    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align:right;width:50px;whiteSpace: normal;min-width:50px;;max-width:50px;';
    $obj[0][$this->gridname]['columns'][$agentquota]['style'] = 'text-align:right;width:50px;whiteSpace: normal;min-width:50px;;max-width:50px;';
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
    $msg = '';
    $status = true;
    $return = [];
    $data = $config['params']['data'];
    $user = $config['params']['user'];

    $releasedate = $this->othersClass->getCurrentTimeStamp();

    foreach ($data as $key => $value) {
      if ($value['added'] == 'true') {
        $agentquota = $this->othersClass->sanitizekeyfield('amt', $value['agentquota']);
        $sales = $this->othersClass->sanitizekeyfield('amt', $value['amt']);

        if (floatval($sales) >= floatval($agentquota)) {
          $this->coreFunctions->execqry("update incentivesyr set agrelease='" . $releasedate . "', agreleaseby='" . $user . "' where agrelease is null and agentid=? and agtype=?", "UPDATE", [$value['agentid'], $value['agtypecode']]);

          $start = date('Y-m-d', strtotime($value['sdate']));
          $end = date('Y-m-d', strtotime($value['edate']));

          if ($value['agtypecode'] == 1) {
            $this->coreFunctions->execqry("update incentives set ag2releaseyr='" . $releasedate . "', ag2releaseyrby='" . $user . "' where ag2releaseyr is null and agentid2=? and date(depodate) between ? and ?", "UPDATE", [$value['agentid'], $start, $end]);
          } else {
            $this->coreFunctions->execqry("update incentives set agreleaseyr='" . $releasedate . "', agreleaseyrby='" . $user . "' where agreleaseyr is null and agentid=? and date(depodate) between ? and ?", "UPDATE", [$value['agentid'], $start, $end]);
          }
        }else{
          $value['added'] = 'false';
          $msg .= $value['agentname'].' doesn`t reach the required quota. ';
          array_push($return, $value);
        }
      } else {
        array_push($return, $value);
      }
    }
    
    exithere:
    if($msg == ''){
      $msg = 'Successfully tagged as released';
    }else{
      $msg = 'Failed to release. '.$msg;
    }
    return ['status' => $status, 'msg' => $msg, 'data' => $return];
  }
} //end class
