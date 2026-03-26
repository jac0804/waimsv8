<?php

namespace App\Http\Classes\modules\construction;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;

class entryprojectsubactivity
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SUB ACTIVITY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'psubactivity';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['stage', 'trno', 'line', 'substage', 'rrqty', 'qty', 'uom', 'rrcost', 'ext', 'subproject', 'cost', 'totalcost', 'subactid'];
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $logger;

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
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $subactid = 1;
    $subactivity = 2;
    $desc = 3;
    $rrqty = 4;
    $qty = 5;
    $uom = 6;
    $rrcost = 7;
    $ext = 8;
    $cost = 9;
    $totalcost = 10;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'subactid', 'subactivity', 'description', 'rrqty', 'qty', 'uom', 'rrcost', 'ext', 'cost', 'totalcost']]];

    $stockbuttons = ['delete', 'addsubitems'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($config['params']['doc'] == 'PM') {
      $obj[0][$this->gridname]['columns'][$subactivity]['style'] = 'width:70%;whiteSpace: normal;min-width:150px;';
      $obj[0][$this->gridname]['columns'][$subactivity]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$desc]['style'] = 'width:70%;whiteSpace: normal;min-width:180px;';
      $obj[0][$this->gridname]['columns'][$desc]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';

      $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Contract Qty';
      $obj[0][$this->gridname]['columns'][$rrqty]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
      $obj[0][$this->gridname]['columns'][$qty]['label'] = 'Estimated Qty';
      $obj[0][$this->gridname]['columns'][$qty]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
      $obj[0][$this->gridname]['columns'][$qty]['align'] = 'text-right';
      $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Unit Contract Price';
      $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Total Amount';
      $obj[0][$this->gridname]['columns'][$cost]['readonly'] = false;
      $obj[0][$this->gridname]['columns'][$cost]['label'] = 'Unit Estimated Cost';

      $obj[0][$this->gridname]['columns'][$totalcost]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$totalcost]['label'] = 'Total Cost';
      $obj[0][$this->gridname]['columns'][$totalcost]['style'] = 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;';
    } else {
      $trno = $config['params']['tableid'];
      $isposted = $this->othersClass->isposted2($trno, "transnum");

      $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:10%;whiteSpace: normal;min-width:180px;';
      $obj[0][$this->gridname]['columns'][$action]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$desc]['type'] = 'label';

      if ($config['params']['doc'] == 'BA') {
        $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Contract Qty';
        $obj[0][$this->gridname]['columns'][$subactivity]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$subactivity]['style'] = 'width:250px;whiteSpace: normal;min-width:220px;';
        $obj[0][$this->gridname]['columns'][$desc]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$desc]['style'] = 'width:350px;whiteSpace: normal;min-width:220px;';
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rrcost]['align'] = 'text-right';
        $obj[0][$this->gridname]['columns'][$rrcost]['style'] = 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$ext]['align'] = 'text-right';
        $obj[0][$this->gridname]['columns'][$ext]['style'] = 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$cost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$totalcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$qty]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$subactid]['type'] = 'label';
      }
      if ($isposted) {
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
        $obj[0][$this->gridname]['columns'][$action]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$desc]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$desc]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rrqty]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
      }
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addsubactivity', 'saveallentry'];
    if ($config['params']['doc'] != 'PM') {
      $trno = $config['params']['tableid'];
      $isposted = $this->othersClass->isposted2($trno, "transnum");
      $tbuttons = ['saveallentry'];
      if ($isposted) {
        $tbuttons = [];
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($config['params']['doc'] == 'PM') {
      $obj[0]['label'] = 'Add Sub Activity';
    }

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $msg = '';

    foreach ($data as $key => $value) {
      $data2 = [];

      $rrqty = floatval(preg_replace('/[^\d.]/', '', $data[$key]['rrqty']));
      $rrcost = floatval(preg_replace('/[^\d.]/', '', $data[$key]['rrcost']));
      $cost = floatval(preg_replace('/[^\d.]/', '', $data[$key]['cost']));
      $qty = floatval(preg_replace('/[^\d.]/', '', $data[$key]['qty']));
      $data[$key]['ext'] = $rrqty * $rrcost;
      $data[$key]['totalcost'] = $qty * $cost;

      if ($data[$key]['bgcolor'] != '') {
        //insert on bastock
        if ($config['params']['doc'] != 'PM') {
          $trno = $config['params']['tableid'];
          $this->fields = ['stage', 'activity', 'subactivity', 'rrqty', 'rrcost', 'ext', 'subactid'];

          $data[$key]['activity'] = $config['params']['sourcerow']['substageline'];
          $data[$key]['subactivity'] = $data[$key]['subline'];
          foreach ($this->fields as $key2 => $value2) {
            $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
          }

          $data2['editby'] = $config['params']['user'];
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();

          if ($data[$key]['trno'] == 0) {
            $data2['trno'] = $trno;
            $qry = "select line as value from bastock where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
              $line = 0;
            }
            $line = $line + 1;
            $data2['line'] = $line;
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $data2['encodeddate'] = $current_timestamp;
            $data2['encodedby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert('bastock', $data2)) {
              $msg = 'Successfully added.';
            } else {
              $msg = 'Error';
            }
          } else {
            $line = $data[$key]['line'];
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $data2['editdate'] = $current_timestamp;
            $data2['editby'] = $config['params']['user'];

            if ($config['params']['doc'] == "BA") {
              $existqty = $this->coreFunctions->getfieldvalue(
                $this->table,
                "rrqty",
                "trno =? and line=? and  substage =? and stage =? and subproject = ? and subactid = ?",
                [$config['params']['sourcerow']['trno'], $data2['subactivity'], $data2['activity'], $data2['stage'], $data[$key]['subproject'], $data2['subactid']]
              );
              if (floatval($data2['rrqty']) > floatval($existqty)) {
                $returndata = $this->loaddata($config);
                return [
                  'status' => true, 'msg' => "The QTY is greater than Existing QTY", 'data' => $returndata,
                ];
              }
            }
            $this->coreFunctions->sbcupdate('bastock', $data2, ["trno" => $trno, "line" => $line, "subactid" => $data2['subactid']]);
            $msg = 'All saved successfully.';
          }
        } else {    // pm update       
          $trno = $config['params']['tableid'];
          foreach ($this->fields as $key2 => $value2) {
            $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
          }

          $exist = $this->coreFunctions->getfieldvalue($this->table, "line", "trno =? and line = ? and subproject =? and stage =? and subactid =?", [$data2['trno'], $data2['line'], $data2['subproject'], $data2['stage'], $data2['subactid']]);
          if (empty($exist)) {
            $this->coreFunctions->sbcinsert($this->table, $data2);
            $this->logger->sbcwritelog($trno, $config, 'SUB ACTIVITY', ' CREATE - ' . $data[$key]['subactid'] . ' - ' . $data[$key]['description']);
            $msg = 'All saved successfully.';
          } else {
            $id = $this->coreFunctions->opentable("select line,trno,subactid from psubactivity where trno = ? and line =? and subactid = ?", [$data2['trno'], $data2['line'], $data2['subactid']]);
            if (empty($id)) {
              $this->coreFunctions->sbcinsert($this->table, $data2);
              $this->logger->sbcwritelog($trno, $config, 'SUB ACTIVITY', ' CREATE - ' . $data[$key]['subactid'] . ' - ' . $data[$key]['description']);
              $msg = 'All saved successfully.';
            } else {
              $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
              $data2['editby'] = $config['params']['user'];
              $this->coreFunctions->sbcupdate($this->table, $data2, ["trno" => $data2['trno'], "line" => $data2['line'], "subproject" => $data2['subproject'], "stage" => $data2['stage'], "subactid" => $data2['subactid']]);
              $msg = 'All saved successfully.';
            }
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => $msg, 'data' => $returndata];
  } // end function 


  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "select sohead.trno as value from sohead left join sostock on sostock.trno = sohead.trno where sohead.projectid = " . $row['trno'] . " and 
    sohead.subproject = " . $row['subproject'] . " and sostock.substage = " . $row['substage'] . " and sostock.stageid = " . $row['stage'] . " and sostock.subactivity =" . $row['line'] . " limit 1";
    $check = $this->coreFunctions->datareader($qry);
    if (floatval($check) > 0) {
      return ['status' => false, 'msg' => 'DELETE failed,already have BOQ.'];
    } else {
      $qry = "select sohead.trno as value from hsohead as sohead left join hsostock as sostock on sostock.trno = sohead.trno where sohead.projectid = " . $row['trno'] . " and 
    sohead.subproject = " . $row['subproject'] . " and sostock.substage = " . $row['substage'] . " and sostock.stageid = " . $row['stage'] . " and sostock.subactivity =" . $row['line'] . " limit 1";
      $check = $this->coreFunctions->datareader($qry);
      if (floatval($check) > 0) {
        return ['status' => false, 'msg' => 'DELETE failed,already have BOQ.'];
      } else {
        $qry = "select bahead.trno as value from bahead left join bastock on bastock.trno = bahead.trno where bahead.projectid = " . $row['trno'] . " and 
        bahead.subproject = " . $row['subproject'] . " and bastock.activity = " . $row['substage'] . " and bastock.stage = " . $row['stage'] . " and bastock.subactivity =" . $row['line'] . " limit 1";

        $check = $this->coreFunctions->datareader($qry);
        if (floatval($check) > 0) {
          return ['status' => false, 'msg' => 'DELETE failed,already have billing accomplishment.'];
        } else {
          $qry = "select bahead.trno as value from hbahead as bahead left join hbastock as bastock on bastock.trno = bahead.trno where bahead.projectid = " . $row['trno'] . " and 
          bahead.subproject = " . $row['subproject'] . " and bastock.activity = " . $row['substage'] . " and bastock.stage = " . $row['stage'] . " and bastock.subactivity =" . $row['line'] . " limit 1";

          $check = $this->coreFunctions->datareader($qry);
          if (floatval($check) > 0) {
            return ['status' => false, 'msg' => 'DELETE failed,already have posted billing accomplishment.'];
          } else {
            $qry = "delete from " . $this->table . " where trno =? and  line=? and subproject = ? and subactid =?";
            $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['subproject'], $row['subactid']]);
            $this->logger->sbcwritelog($row['trno'], $config, 'SUB ACTIVITY', 'REMOVE - ' . $row['subactid'] . ' - ' . $row['description']);
            return ['status' => true, 'msg' => 'Successfully deleted.'];
          }
        }
      }
    }
  }

  public function loaddata($config)
  {
    $stage = isset($config['params']['row']['stage']) ? $config['params']['row']['stage'] : $config['params']['sourcerow']['stage'];
    $trno = isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : $config['params']['sourcerow']['trno'];
    $substage = isset($config['params']['row']['line'])  ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
    $subproject = isset($config['params']['row']['subproject'])  ? $config['params']['row']['subproject'] : $config['params']['sourcerow']['subproject'];
    $htable = 'bahead';
    $hstock = 'bastock';
    if ($config['params']['doc'] != 'PM') {
      $trno = $config['params']['tableid'];
      $isposted = $this->othersClass->isposted2($trno, "transnum");

      if ($isposted) {
        $htable = "hbahead";
        $hstock = "hbastock";
      }
      $qry = "select ifnull(stock.trno,0) as trno,ifnull(stock.line,0) as line,a.line as subline,s.subactivity,a.stage,s.substage,'' as bgcolor,
      FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,FORMAT(a.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
      FORMAT(ifnull(stock.rrcost,a.rrcost)," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
      FORMAT(ifnull(stock.ext,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as ext,a.uom,a.subproject,
      FORMAT(a.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost, FORMAT(ifnull(a.cost,0)*stock.rrqty," . $this->companysetup->getdecimal('price', $config['params']) . ") as totalcost,
      ifnull(s.description, '') as description, a.subactid
      from psubactivity as a 
      left join subactivity as s on s.line = a.line
      left join " . $hstock . " as stock on stock.subactivity = a.line and stock.activity = a.substage and stock.stage = a.stage and stock.subactid=a.subactid
      and stock.trno = ? 
      where a.stage = ? and a.substage =? and a.subproject = ? and a.qty <> 0 and a.rrcost <> 0
      order by stock.rrqty desc,s.subactivity,a.line";
      $data = $this->coreFunctions->opentable($qry, [$trno, $stage, $substage, $subproject]);
      if (empty($data)) {
        $qry = "select 0 as trno,0 as line,a.line as subline,s.subactivity,a.stage,s.substage,'' as bgcolor,
        0 as rrqty,0 as qty,ifnull(a.rrcost,0) as rrcost,ifnull(a.ext,0) as ext,a.uom,a.subproject ,ifnull(a.cost,0) as cost,ifnull(a.totalcost,0) as totalcost,
        ifnull(s.description, '') as description
        from psubactivity as a 
        left join subactivity as s on s.line = a.line
        where a.trno = ? and a.stage = ? and a.substage =? order by s.subactivity,s.line";
        $data = $this->coreFunctions->opentable($qry, [$trno, $stage, $substage, $subproject]);
      }
    } else {
      $qry = "select a.trno,a.line,a.line as subline,s.subactivity,a.stage,s.substage,'' as bgcolor,
      FORMAT(a.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
      FORMAT(a.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,a.uom,
      FORMAT(a.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
      FORMAT(a.ext," . $this->companysetup->getdecimal('price', $config['params']) . ") as ext,
      FORMAT(a.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
      FORMAT(a.totalcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as totalcost,
      a.uom,a.subproject, ifnull(s.description, '') as description, a.subactid
      from psubactivity as a 
      left join subactivity as s on s.line = a.line
      where a.trno = ? and a.subproject = ?  and a.stage = ? and a.substage =? 
      order by s.subactivity,s.line";
      $data = $this->coreFunctions->opentable($qry, [$trno, $subproject, $stage, $substage]);
    }


    return $data;
  }

  public function lookupsetup($config)
  {
    return $this->lookupitem($config);
  } //end function


  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $trno = $config['params']['sourcerow']['trno'];
        $row = $config['params']['row'];
        $data = [];
        $data['line'] = $row['line'];;
        $data['trno'] = $trno;
        $data['substage'] =  $config['params']['sourcerow']['line'];;
        $data['stage'] = $config['params']['sourcerow']['stage'];
        $data['subactivity'] = $row['subactivity'];
        $data['subproject'] = $config['params']['sourcerow']['subproject'];
        $data['description'] = $row['description'];
        $data['rrqty'] = 0;
        $data['uom'] = '';
        $data['rrcost'] = 0;
        $data['qty'] = 0;
        $data['cost'] = 0;
        $data['subactid'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return ['status' => true, 'msg' => 'Item was successfully added.', 'data' => $data];
        break;
    }
  } // end function


  public function lookupitem($config)
  {
    $stage = $config['params']['sourcerow']['stage'];
    $substage = $config['params']['sourcerow']['line'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Sub Activity',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array();
    $col = array('name' => 'subactivity', 'label' => 'Sub Activity', 'align' => 'left', 'field' => 'subactivity', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col2 = array('name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col2);

    $qry = "select line,subactivity, description from subactivity where stage = " . $stage . " and substage =" . $substage . " order by line,subactivity";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookuplogs($config)
  {

    $doc = strtoupper($config['params']['lookupclass']);
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['sourcerow']['line'];

    $qry = "
  select trno, doc, task, log.user, dateid, 
  if(pic='','blank_user.png',pic) as pic
  from " . $this->tablelogs . " as log
  left join useraccess as u on u.username=log.user
  where log.doc = '" . $doc . "' and trno = $trno 
  union all
  select trno, doc, task, log.user, dateid, 
  if(pic='','blank_user.png',pic) as pic
  from  " . $this->tablelogs_del . " as log
  left join useraccess as u on u.username=log.user
  where log.doc = '" . $doc . "' and trno = $trno ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
