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

class entryvrcustomeritems
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CUSTOMER / ITEM LIST';
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
    $passengername = $config['params']['row']['clientname'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['clientname', 'client', 'itemname']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][0]['label'] = "CUSTOMER NAME";
    $obj[0][$this->gridname]['columns'][0]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = "ITEM NAME";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['type'] = "input";
    $obj[0][$this->gridname]['columns'][1]['type'] = "input";

    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $this->modulename = 'CUSTOMER / ITEM LIST - ' . $passengername;

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
    $passengerid = $config['params']['row']['clientid'];

    $filtersearch = "";
    $searcfield = $this->fields;

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

    $qry = "select  item.itemname as client, client.clientname 
      from vrstock as vr
      left join vrpassenger as pass on pass.trno = vr.trno and pass.line = vr.line
      left join vritems as item on item.trno = vr.trno and item.line = vr.line
      left join client on client.clientid = vr.clientid
      where pass.trno = ? and pass.passengerid = ?  $filtersearch
      union all
      select item.itemname as client, client.clientname from hvrstock as vr
      left join hvrpassenger as pass on pass.trno = vr.trno and pass.line = vr.line
      left join hvritems as item on item.trno = vr.trno and item.line = vr.line
      left join client on client.clientid = vr.clientid
      where pass.trno = ? and pass.passengerid = ? $filtersearch
      group by itemname, clientname 
      order by clientname";
    $data = $this->coreFunctions->opentable($qry, [$trno, $passengerid, $trno, $passengerid]);
    return $data;
  }
} //end class
