<?php

namespace App\Http\Classes\modules\payrollentry;

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

class entryadvance
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ADVANCE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'standardtransadv';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['empid', 'acnoid', 'dateid', 'db', 'cr', 'docno', 'ismanual'];
  public $showclosebtn = false;



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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['batch', 'dateid', 'db', 'cr']]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function add($config)
  {
    return [];
  }

  private function selectqry()
  {
    $qry = "s.line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',s.' . $value;
    }
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

  private function loaddataperrecord($trno, $config = [])
  {
    return [];
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor, DATE_FORMAT(s.dateid, '%Y/%m/%d') as pdateid, 
      ifnull(b.batch,'') as batch ";

    $qry = "select " . $select . " 
    from " . $this->table . " as s
    left join batch as b on b.line=s.batchid 
    where s.trno = ? and ismanual = 0 order by s.line";

    $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
    return $data;
  }
} //end class
