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

class entryleavesetup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LEAVE SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'leavetrans';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['refno', 'dateid', 'daytype', 'adays'];
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
      'load' => 1545
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['dateid', 'effectivity', 'adays', 'status', 'batch']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][0]['label'] = 'Created Date';
    $obj[0][$this->gridname]['columns'][1]['label'] = 'Effectivity Date';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'Hour(s)';

    $obj[0][$this->gridname]['columns'][3]['align'] = 'center';

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
    $data['trno'] = 0;
    $data['refno'] = $config['params']['tableid'];
    $data['dateid'] = date('Y-m-d');
    $data['daytype'] = '';
    $data['status'] = '';
    $data['batch'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "lt.trno, b.batch, lt.effectivity,
    case 
      when lt.status = 'A' then 'APPROVED'
      when lt.status = 'E' then 'ENTRY'
      when lt.status = 'O' then 'ON-HOLD'
      when lt.status = 'P' then 'PROCESSED'
    end as status";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',lt.' . $value;
    }
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['trno'] == 0) {
      $trno = $this->coreFunctions->insertGetId($this->table, $data);
      if ($trno != 0) {
        $returnrow = $this->loaddataperrecord($trno);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($trno, $config = [])
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and refno = ?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $config['params']['tableid']]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as lt
    left join batch as b on b.line=lt.batchid
    where lt.trno = ? order by lt.trno";
    $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
    return $data;
  }
} //end class
