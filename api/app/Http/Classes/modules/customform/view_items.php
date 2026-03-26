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

class view_items
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'VIEW ITEMS';
  public $gridname = 'customformlisting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
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
  }

  public function createTab($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $companyid = $config['params']['companyid'];

    $column = ['partno', 'itemdescription', 'isqty', 'isamt', 'disc', 'ext'];
    foreach ($column as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = [];

    $tab = [$this->gridname => ['gridcolumns' => $column]];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'text-align:right;';
    $obj[0][$this->gridname]['columns'][$isamt]['style'] = 'text-align:right;';
    $obj[0][$this->gridname]['columns'][$isamt]['label'] = 'Price';

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

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("
    select 
    '' as partno,
    '' as itemdescription,
    0.0 as isqty,
    0.0 as isamt,
    '' as disc,
    0.0 as ext");
  }

  public function data($config)
  {

    $addedfield = "";

    $trno = $config['params']['trno'];
    $qry = "select 
    i.partno,i.itemname as itemdescription,
    FORMAT(stock.isamt,2) as isamt,
    FORMAT(stock.isqty,2) as isqty,
    FORMAT(stock.iss,2) as iss,
    stock.disc,
    FORMAT(stock.ext,2) as ext
    from glhead as head 
    left join glstock as stock on stock.trno = head.trno 
    left join item as i on i.itemid=stock.itemid
    where head.doc in ('MJ','CI') and stock.trno in (select distinct refx from hmcdetail where trno=$trno)";

    return $this->coreFunctions->opentable($qry);
  }
} //end class
