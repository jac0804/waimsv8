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

class entryearningdeduction
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EARNING AND DEDUCTION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'standardtrans';
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
    $attrib = array(
      'load' => 1585
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $docno = 0;
    $dateid = 1;
    $db = 2;
    $cr = 3;
    $batch = 4;
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['docno', 'dateid', 'db', 'cr', 'batch']
      ]
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$db]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$db]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][$cr]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$cr]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][$batch]['readonly'] = true;
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
    $select = $select . ",'' as bgcolor, DATE_FORMAT(s.dateid, '%Y/%m/%d') as dateid, ifnull(b.batch,'') as batch ";

    $qry = "select " . $select . " from " . $this->table . " as s
    left join batch as b on b.line=s.batchid where s.trno = ? and ismanual=0 order by s.line";

    $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
    return $data;
  }
} //end class
