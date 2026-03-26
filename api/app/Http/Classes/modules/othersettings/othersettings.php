<?php

namespace App\Http\Classes\modules\othersettings;

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



class othersettings
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OTHER SETTINGS';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2221,
      'save' => 2222
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {

    return [];
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $fields = [];

    $blnUpdate = false;

    if ($this->othersClass->checkAccess($config['params']['user'], 2222)) {
      array_push($fields, 'start');
      $blnUpdate = true;
    }

    if ($this->othersClass->checkAccess($config['params']['user'], 4195)) {
      array_push($fields, 'surcharge');
      $blnUpdate = true;
    }


    if ($this->othersClass->checkAccess($config['params']['user'], 5209)) {
      array_push($fields, 'yr');
      $blnUpdate = true;
    }

    if ($blnUpdate) array_push($fields, 'refresh');

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'Lock Date');
    data_set($col1, 'refresh.action', 'save');
    data_set($col1, 'refresh.label', 'Save');
    data_set($col1, 'refresh.access', 'view');

    if ($config['params']['companyid'] == 21) {
      data_set($col1, 'yr.type', 'input');
      data_set($col1, 'yr.label', 'SJ ADD DATE');
      data_set($col1, 'yr.readonly', false);
    }

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("select 
    (select pvalue as start from profile where doc = 'SYSL') as start,
    (select yr from profile where doc = 'SYS' and psection='SJADDDATE') as yr,
    (select pvalue as start from profile where doc = 'SYS' and psection='SURCHARGE') as surcharge");

    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];


    switch ($action) {
      case 'save':
        if ($this->othersClass->checkAccess($config['params']['user'], 2222)) {
          $date = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
          $status = $this->coreFunctions->sbcupdate("profile", ["pvalue" => $date], ['doc' => 'SYSL']);
        }

        if ($this->othersClass->checkAccess($config['params']['user'], 5209)) {
          $sjdate = $config['params']['dataparams']['yr'];
          $exist = $this->coreFunctions->opentable("select yr from profile where doc='SYS' and psection='SJADDDATE'");
          if (empty($exist)) {
            $status = $this->coreFunctions->sbcinsert("profile", ['doc' => 'SYS', 'psection' => 'SJADDDATE', "yr" => $sjdate]);
          } else {
            $status = $this->coreFunctions->sbcupdate("profile", ["yr" => $sjdate], ['doc' => 'SYS', 'psection' => 'SJADDDATE']);
          }
        }

        if ($this->othersClass->checkAccess($config['params']['user'], 4195)) {
          $surcharge = round($this->othersClass->sanitizekeyfield('amt', $config['params']['dataparams']['surcharge']), 2);
          $exist = $this->coreFunctions->opentable("select pvalue from profile where doc='SYS' and psection='SURCHARGE'");
          if (empty($exist)) {
            $status = $this->coreFunctions->sbcinsert("profile", ['doc' => 'SYS', 'psection' => 'SURCHARGE', "pvalue" => $surcharge]);
          } else {
            $status = $this->coreFunctions->sbcupdate("profile", ["pvalue" => $surcharge], ['doc' => 'SYS', 'psection' => 'SURCHARGE']);
          }
        }

        if ($status) {
          $msg = 'Settings successfully updated.';
        } else {
          $msg = 'There was an error during updating. Please try again.';
        }
        return ['status' => true, 'msg' => $msg, 'action' => 'load', 'griddata' => []];
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }
} //end class
