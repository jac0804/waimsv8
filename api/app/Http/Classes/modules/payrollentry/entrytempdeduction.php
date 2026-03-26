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

class entrytempdeduction
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DEDUCTIONS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'standardtransadv';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['camt'];
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
    $docno = 0;
    $acnoname = 1;
    $dateid = 2;
    $balance = 3;
    $camt = 4;
    $tab = [$this->gridname => ['gridcolumns' => ['docno', 'acnoname', 'dateid', 'balance', 'camt']]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$acnoname]['label'] = 'Account Name';

    $obj[0][$this->gridname]['columns'][$camt]['label'] = 'Amount';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'SAVE DEDUCTIONS';
    return $obj;
  }

  public function add($config)
  {
    return [];
  }

  private function selectqry($batch)
  {
    $week = '';
    // $week = substr($batch, -2);
    // switch ($week) {
    //   case '01':
    //     $week = " and s.w1=1";
    //     break;

    //   case '02':
    //     $week = " and s.w2=1";
    //     break;

    //   case '03':
    //     $week = " and s.w3=1";
    //     break;

    //   case '04':
    //     $week = " and s.w4=1";
    //     break;

    //   case '05':
    //     $week = " and s.w5=1";
    //     break;

    //   case '13':
    //     $week = " and s.w13=1";
    //     break;

    //   default:
    //     $week = '';
    //     break;
    // }

    $qry = "select 'standardsetup' as tbl, p.codename as acnoname, s.trno, s.docno,  s.balance, date(s.dateid) as dateid, s.camt, s.trno, '' as bgcolor, s.empid, '" . $batch . "' as batch
        from standardsetup as s left join paccount as p on p.line=s.acnoid where s.empid=? and  s.balance<>0 " . $week . "
        union all
        select 'standardsetupadv' as tbl, p.codename as acnoname, s.trno, s.docno,  s.balance, date(s.dateid) as dateid, s.camt, s.trno, '' as bgcolor, s.empid, '" . $batch . "' as batch
        from standardsetupadv as s left join paccount as p on p.line=s.acnoid where s.empid=? and s.balance<>0 " . $week . "
        order by docno";
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
    $batch = '';
    $empid = 0;
    if (isset($config['params']['addedparams'])) {
      $empid = $config['params']['addedparams'][0];
      $batch = $config['params']['addedparams'][1];
    } else {
      if (isset($config['params']['data'])) {
        $empid = $config['params']['data'][0]['empid'];
        $batch = $config['params']['data'][0]['batch'];
      }
    }

    $data = $this->coreFunctions->opentable($this->selectqry($batch), [$empid, $empid]);
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data2['camt'] < 0) {
          $data2['camt'] = 0;
        }
        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($data[$key]['tbl'], $data2, ['trno' => $data[$key]['trno']]);
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function    
} //end class
