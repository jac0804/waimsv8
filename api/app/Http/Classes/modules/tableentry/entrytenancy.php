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
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class entrytenancy
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'UPDATE TENANCY STATUS';
  public $gridname = 'inventory';
  public $stock = 'tenanycystatus';
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:1100px;max-width:1100px;';
  private $fields = [];
  public $showclosebtn = false;
  public $issearchshow = false;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->warehousinglookup = new warehousinglookup;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 4216
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $status = 0;
    $monthsno = 1;
    $effectdate = 2;
    $start = 3;
    $end = 4;
    $rem = 5;
    $tab = [
      $this->gridname => ['gridcolumns' => ['status', 'months','dateeffect', 'start', 'end', 'rem']] //'pallet',
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:50px;whiteSpace: normal;min-width:50px; max-width:50px; align: text-left; text-align: left;';
    $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$monthsno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$monthsno]['style'] = 'width:50px;whiteSpace: normal;min-width:50px; max-width:50px; align: text-left; text-align: left;';
    $obj[0][$this->gridname]['columns'][$effectdate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$start]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$end]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = []; //saveallentry
    $obj = $this->tabClass->createtabbutton($tbuttons);
   
    return $obj;
  }

    public function loaddata($config)
  {
    $clientid = $config['params']['tableid'];

    $qry = "select cl.client,cl.clientname,ts.status,date_format(ts.effectdate,'%m/%d/%Y') as dateeffect,ts.monthsno as months,date_format(ts.datefrom,'%m/%d/%Y') as start,date_format(ts.dateto,'%m/%d/%Y') as end,ts.rem from tenancystatus as ts left join client as cl on ts.clientid = cl.clientid where cl.clientid = ?";

    $data = $this->coreFunctions->opentable($qry, [$clientid]);
    return $data;
  }

 
} //end class
