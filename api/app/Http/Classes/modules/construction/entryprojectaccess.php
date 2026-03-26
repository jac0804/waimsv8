<?php

namespace App\Http\Classes\modules\construction;

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



class entryprojectaccess
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PROJECT ACCESS';
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
      'view' => 2162
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
  }

  public function createtabbutton($config)
  {
    $obj = [];
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['username', 'project', ['update']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'project.lookupclass', 'userproject');
    data_set($col1, 'username.lookupclass', 'lookupusers');

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select '' as username,0 as userid,'' as project ");

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
      case 'update':
        return $this->save($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loadgrid($config, $mark = 0)
  {
    return [];
  }


  private function save($config)
  {
    $userid  = $config['params']['dataparams']['userid'];
    $project  = $config['params']['dataparams']['project'];

    if ($this->coreFunctions->execqry("update useraccess set project = '" . $project . "' where userid =?", "update", [$userid])) {
      return ['status' => 'true', 'msg' => 'Successfully saved.', 'griddata' => [], 'action' => 'load'];
    } else {
      return ['status' => 'false', 'msg' => 'Error on saving', 'griddata' => [], 'action' => 'load'];
    }
  }
} //end class
