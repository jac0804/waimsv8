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

class hmsrates
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RATES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $head = 'htrhead';
  private $stock = 'htrstock';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['itemname', 'rrqty'];
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
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'rrqty', 'reqqty', 'qa', 'uom',  'rem', 'wh']]];

    $stockbuttons = ['showbalance'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['label'] = 'Approve Qty';
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][5]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][7]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][7]['type'] = 'input';

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function getdata($config)
  {
    $trno = $config['params']['tableid'];
    $msg = '';
    $status = true;
    $data = [];
    return ['status' => $status, 'msg' => $msg, 'data' => $data];
  } //end function

  public function approveall($config)
  {
    $status = true;
    $msg = '';
    $trno = $config['params']['tableid'];
    $data = [];
    return ['status' => $status, 'msg' => $msg, 'data' => $data];
  } //end function

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $data = [];
    return $data;
  }
} //end class
