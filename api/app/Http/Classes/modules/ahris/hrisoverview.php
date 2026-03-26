<?php

namespace App\Http\Classes\modules\ahris;

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

class hrisoverview
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DAILY NOTIF2';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:900px;max-width:900px;';
  public $issearchshow = true;
  public $showclosebtn = true;

  private $config = [];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['hired', 'empname', 'designation']
      ]
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    return [];
  }

  public function paramsdata($config)
  {
    return [];
  }

  public function data($config)
  {
    $divid = $config['params']['addedparams']['divid'];
    $statline = $config['params']['addedparams']['statline'];

    switch ($statline) {
      case 0:
        $this->modulename = 'ACTIVE EMPLOYEES';
        break;
      case 47:
        $this->modulename = 'PROJECT BASE EMPLOYEES';
        break;
      case 48:
        $this->modulename = 'PROBATIONARY EMPLOYEES';
        break;
      case 49:
        $this->modulename = 'REGULAR EMPLOYEES';
        break;
      case 50:
        $this->modulename = 'CONTRACTUAL EMPLOYEES';
        break;
    }

    if ($statline == 0) {
      $condition = "and divid = ?";
      $params = [$divid];
    } else {
      $condition = "and divid = ? and empstatus = ?";
      $params = [$divid, $statline];
    }
    $sql = "select concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle) as empname, emp.empstatus, date(emp.hired) as hired, jt.jobtitle as designation
          from employee as emp
          left join jobthead as jt on jt.line = emp.jobid
          where isactive = 1 $condition";
    $data = $this->coreFunctions->opentable($sql, $params);
    return $data;
  }


  public function execute()
  {
    if (isset($this->config['allowlogin'])) {
      if (!$this->config['allowlogin']) {
        return response()->json(['status' => 'ipdenied', 'msg' => 'Sorry, Please contact your Network Administrator', 'xx' => $this->config], 200);
      }
    }

    return response()->json($this->config['return'], 200);
  } // end function

  public function loadcustomform($config)
  {
    $this->config = $config;
    $this->config['return'] = $this->loaddata($config);
    return $this;
  }

  public function loaddata($config)
  {
    return [];
  }

  public function getmodulename($config)
  {

    return 'EMPLOYEE LIST';
  }

  public function getgridname($config)
  {
    return $this->gridname;
  }

  public function getstyle($config)
  {
    return $this->style;
  }
} //end class
