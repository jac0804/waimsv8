<?php

namespace App\Http\Classes\modules\actionlisting;

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

class vrapproval
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'VEHICLE REQUEST APPROVAL LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'vrhead';
  private $tablenum = 'transnum';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['terms', 'days'];
  public $showclosebtn = false;
  public $showfilteroption = true;
  public $showfilter = true;
  public $issearchshow = true;


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
      'load' => 2235
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $docno = 1;
    $dateid = 2;
    $clientname = 3;
    $customer = 4;
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'docno', 'dateid', 'clientname', 'customer']
      ]
    ];

    $stockbuttons = ['approvedtrans'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";
    $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$customer]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$customer]['type'] = 'input';

    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Employee Name';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    return $data;
  }

  private function selectqry($config)
  {
    $center = $config['params']['center'];

    $qry = "select 
    head.trno,
    head.docno, 
    left(head.dateid,10) as dateid, 
    head.schedin,
    head.schedout, 
    head.driverid, 
    head.clientid, 
    head.vehicleid, 
    head.deptid, 
    driver.clientname as drivername, 
    driver.client as driver, 
    emp.clientname, 
    emp.client, 
    vehicle.clientname as vehiclename, 
    vehicle.client as vehicle, 
    dept.clientname as ddeptname, 
    dept.client as dept, 
    head.rem,
    head.approvedby,
    (select group_concat(client.clientname separator '\r\n') from vrstock as s left join client on client.clientid=s.clientid where s.trno=head.trno) as customer,
    'VEHICLE SCHEDULE REQUEST' as itemname,
    'viewvrapproval' as lookupclass,
    'customform' as action
    ";
    $qry = $qry . " from " . $this->table . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno 
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.doc='VR' and num.center = '" . $center . "' and num.statid=0";

    return $qry;
  }

  public function save($config)
  {
    return [];
  } //end function

  public function delete($config)
  {
    return [];
  }


  private function loaddataperrecord($config, $trno)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno = ?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $qry = $this->selectqry($config);
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
