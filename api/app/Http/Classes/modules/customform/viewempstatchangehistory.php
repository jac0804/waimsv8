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

class viewempstatchangehistory
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DETAILS';
  public $gridname = 'viewrefgrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  private $fields = ['dateid', 'docno', 'effdate', 'scode', 'statdesc'];
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

    $tab = [$this->gridname => ['gridcolumns' => ['dateid', 'docno', 'basicrate', 'effdate', 'scode', 'statdesc']]];

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
    $fields = ['start'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.readonly', false);

    $fields = ['end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'end.readonly', false);

    $fields = ['refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'ar');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('
      select adddate(left(now(),10),-360) as start,
      adddate(left(now(),10),0) as end
    ');
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $qryselect = "
      select '' as bgcolor, 
      es.trno, 
      date(es.dateid) as dateid,
      es.docno, 
      date(es.effdate) as effdate,
      stat.stat as scode,
      es.description as statdesc, es.tbasicrate as basicrate
      ";

    $qry = "
      " . $qryselect . " 
      from eschange as es
      left join client as emp on emp.clientid=es.empid
      left join statchange as stat on stat.code = es.statcode
      where emp.clientid=? and
      date(dateid) between '" . $start . "' and '" . $end . "'
      union all
      " . $qryselect . " 
      from heschange as es
      left join client as emp on emp.clientid=es.empid
      left join statchange as stat on stat.code = es.statcode
      where emp.clientid=? and
      date(dateid) between '" . $start . "' and '" . $end . "'
      order by trno";

    $data = $this->coreFunctions->opentable($qry, [$clientid, $clientid]);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
