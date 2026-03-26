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
use App\Http\Classes\othersC;

class viewschedulehistory
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SCHEDULE HISTORY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%';
  public $issearchshow = true;
  public $showclosebtn = false;

  function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
      $attrib = array('load' => 2030);
      return $attrib;
  }

  public function createTab($config)
  {
    $dispatchdate = 0;
    $dispatchby = 1;
    $scheddate = 2;
    $truck = 3;
    $dateid = 4;
    $userid = 5;

    $tab = [$this->gridname=>['gridcolumns'=>['dispatchdate', 'dispatchby', 'scheddate', 'truck', 'dateid', 'userid']]];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$dispatchdate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dispatchby]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$scheddate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$truck]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$userid]['type'] = 'label';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config)
  {
      $trno = $config['params']['tableid'];
      $qry = "select r.trno, left(r.dispatchdate, 10) as dispatchdate, r.dispatchby, left(r.scheddate, 10) as scheddate, client.clientname as truck, left(r.dateid, 10) as dateid, r.userid
      from reschedule as r left join client on client.clientid=r.truckid
      where trno=?
      order by r.scheddate";
      return $this->coreFunctions->opentable($qry, [$trno]);
  }
}



 ?>
