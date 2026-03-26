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

class postingpdc
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'POSTDATED CHECKS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';

  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablenum = 'cntnum';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'qty', 'addons'];
  public $showclosebtn = false;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $gridcolumns = ['action', 'dateid', 'docno', 'loc2', 'loc','rem'];
    $stockbuttons = ['viewtsdetail'];
    $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";    
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";    
    $obj[0][$this->gridname]['columns'][5]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";    
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][5]['type'] = 'label';    
    $obj[0][$this->gridname]['columns'][4]['label'] = 'Source Location';
    return $obj;
  }


  public function createtabbutton($config)
  {
    return [];
  }


  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    return $data;
  }

  private function selectqry()
  {
  }

  public function save($config)
  {
  } //end function

  public function saveallentry($config)
  {
  }

  public function delete($config)
  {
  }

  private function loaddataperrecord($trno, $line)
  {
  }

  public function loaddata($config)
  {
    $center = $config['params']['center'];
    $wh = $this->companysetup->getwh($config['params']);
    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, d.clientname as loc2, wh.clientname as loc,'tableentries/tableentry/postingst' as url,head.rem
    from lahead as head left join cntnum on cntnum.trno=head.trno 
    left join client as wh on wh.client=head.wh 
    left join client as d on d.client = head.client
    where head.doc='ST' 
    and head.client ='" . $wh . "'";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
  }
} //end class
