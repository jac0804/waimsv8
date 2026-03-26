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

class viewincidentmemo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'INCIDENT REPORT';
  public $gridname = 'viewrefgrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  private $fields = ['docno', 'idate', 'idescription', 'dateid'];
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
    $tab = [$this->gridname => ['gridcolumns' => ['docno', 'idate', 'idescription']]];

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

  private function selectqry()
  {
    $qry = "trno";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " 
      from (
      select head.trno,head.docno,date(head.idate) as idate,head.idescription,date(head.dateid) as dateid 
      from incidenthead as head
      left join incidentdtail as detail on detail.trno=head.trno
      where (head.tempid='" . $clientid . "' or detail.empid='" . $clientid . "')
      union all
      select head.trno,head.docno,date(head.idate) as idate,head.idescription,date(head.dateid) as dateid
      from hincidenthead as head
      left join hincidentdtail as detail on detail.trno=head.trno
      where (head.tempid='" . $clientid . "' or detail.empid='" . $clientid . "')) as a 
      where date(a.dateid) between '" . $start . "' and '" . $end . "' 
      group by a.trno,a.docno,a.idate,a.idescription,a.dateid
      order by a.docno";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'qw' => $qry];
  }
} //end class
