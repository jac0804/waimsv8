<?php

namespace App\Http\Classes\modules\customform;

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

class stockcardcomponent
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Components';
  public $gridname = 'tableentry';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function createTab($config)
  {
    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycomponent', 'label' => 'component']];
    $obj = $this->tabClass->createtab($tab, []);
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
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $col2 = $this->fieldClass->create($fields);

    $fields = ['refresh'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['amount'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'amount.readonly', true);
    data_set($col4, 'amount.label', 'Total Amount');


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $itemid = $config['params']['clientid'];
    return $this->coreFunctions->opentable('select format(sum(qty*cost),2) as amount from component where itemid=?', [$itemid]);
  }

  public function data($config)
  {
    return [];
  } //end function

  public function loaddata($config)
  {
    $itemid = $config['params']['itemid'];
    $qry = "select format(sum(qty*cost),2) as amount from component where itemid=?";
    $data2 = $this->coreFunctions->opentable($qry, [$itemid]);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'txtdata' => $data2];
  } //end function
































} //end class
