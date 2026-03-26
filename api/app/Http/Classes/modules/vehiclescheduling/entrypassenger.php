<?php

namespace App\Http\Classes\modules\vehiclescheduling;

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
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entrypassenger
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PASSENGER LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'vrpassenger';
  private $htable = 'hvrpassenger';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['client', 'clientname'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2894
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'client', 'clientname', 'itemname']
      ]
    ];

    $stockbuttons = ['view_customeritems'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:150px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:350px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = "Passenger Code";
    $obj[0][$this->gridname]['columns'][2]['label'] = "Passenger Name";
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][1]['type'] = "input";
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
    return [];
  }

  private function selectqry()
  {
    $qry = "vr.trno, p.clientname, p.client, p.clientid";
    return $qry;
  }

  public function saveallentry($config)
  {
    return [];
  } // end function

  public function save($config)
  {
    return [];
  } //end function

  public function delete($config)
  {
    return [];
  }


  private function loaddataperrecord($model_id)
  {
    return [];
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $filtersearch = "";
    $searcfield = $this->fields;
    $limit = "100";

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as vr
    left join client as p on p.clientid = vr.passengerid
    where vr.trno = ? " . $filtersearch . " 
    group by vr.trno, p.clientname, p.client, p.clientid
    union all " .
      $qry = "select " . $select . " from " . $this->htable . " as vr
    left join client as p on p.clientid = vr.passengerid
    where vr.trno = ? " . $filtersearch . " 
    group by vr.trno, p.clientname, p.client, p.clientid
    order by clientname";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }
} //end class
