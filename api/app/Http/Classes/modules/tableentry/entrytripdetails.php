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

class entrytripdetails
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TRIP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'LIST';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $head = 'tripdetail';
  public $hhead = 'htripdetail';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'itemid', 'clientid', 'activity', 'rate'];
  public $showclosebtn = false;
  public $showsearch = false;


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
    $isposted = $this->othersClass->isposted2($config['params']['tableid'], "cntnum");
    $isapproved = $this->othersClass->isapproved($config['params']['tableid'], "hcntnuminfo");

    $action = 0;
    $barcode = 1;
    $itemname = 2;
    $empcode = 3;
    $empname = 4;
    $activity = 5;
    $rate = 6;

    $gridcolumns = ['action', 'barcode', 'itemname', 'empcode', 'empname', 'activity', 'rate'];
    $stockbuttons = ['delete'];
    $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if (!$isposted) $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 40px;whiteSpace: normal;min-width:40px;max-width:40px;';

    $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'Code';
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Description';
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$empcode]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$empcode]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$empcode]['lookupclass'] = 'lookupemployee';

    $obj[0][$this->gridname]['columns'][$rate]['label'] = 'Rate';

    if ($isposted) {
      switch ($config['params']['doc']) {
        case 'RR':
          if ($isapproved) {
            $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$empcode]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$activity]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$rate]['type'] = 'input';
            $obj[0][$this->gridname]['columns'][$rate]['readonly'] = true;
          }
          break;
      }
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from tripdetail where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }

  public function createtabbutton($config)
  {
    $isposted = $this->othersClass->isposted2($config['params']['tableid'], "cntnum");
    $isapproved = $this->othersClass->isapproved($config['params']['tableid'], "hcntnuminfo");
    $tbuttons = ['additemcomponent', 'saveallentry', 'addrecord'];
    if ($isposted) {
      switch ($config['params']['doc']) {
        case 'RR':
          if ($isapproved) $tbuttons = [];
          break;
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'ADD TRUCK/ASSET';
    $obj[1]['label'] = 'SAVE DETAILS';
    $obj[2]['label'] = 'ADD ROW';
    return $obj;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select d.trno, d.line, d.itemid, item.barcode, item.itemname, d.clientid, emp.client as empcode, emp.clientname as empname, d.activity, d.rate, '' as bgcolor
    from tripdetail as d left join item on item.itemid=d.itemid left join client as emp on emp.clientid=d.clientid where d.trno=?
    union all
    select d.trno, d.line, d.itemid, item.barcode, item.itemname, d.clientid, emp.client as empcode, emp.clientname as empname, d.activity, d.rate, '' as bgcolor
    from htripdetail as d left join item on item.itemid=d.itemid left join client as emp on emp.clientid=d.clientid where d.trno=? order by line desc";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'additemcomponent':
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        $isapproved = $this->othersClass->isapproved($trno, "hcntnuminfo");
        if ($isposted) {
          switch ($config['params']['doc']) {
            case 'RR':
              if ($isapproved) {
                return ['status' => false, 'msg' => 'Transaction has already been posted and approved.', 'data' => []];
              }
              break;
          }
        }
        return $this->lookupitem($config);
        break;
      case 'lookupemployee':
        return $this->lookupemployee($config);
        break;
      case 'saveallentry':
        return $this->saveallentry($config);
        break;
      case 'addrecored':
        return $this->add($config);
        break;
    }
  } //end function

  public function saveallentry($config)
  {
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");
    $isapproved = $this->othersClass->isapproved($config['params']['tableid'], "hcntnuminfo");
    $table = $this->head;
    if ($isposted) {
      $table = $this->hhead;
      switch ($config['params']['doc']) {
        case 'RR':
          if ($isapproved) {
            return ['status' => false, 'msg' => 'Transaction has already been posted and approved.', 'data' => []];
          }
          break;
      }
    }
    $row = $config['params']['data'];
    foreach ($row as $key => $rows) {
      $data = [];
      foreach ($this->fields as $key2 => $value) {
        $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$key][$value]);
      }
      if ($data['line'] == 0) {
        if (isset($rows['bgcolor'])) {
          if ($rows['bgcolor'] != '') {
            $qry = "select line as value from $table where trno=$trno order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry);
            if ($line == '') {
              $line = 0;
            }
            $line = $line + 1;
            $data['line'] = $line;
            $this->coreFunctions->sbcinsert($table, $data);
          }
        }
      } else {
        if (isset($rows['bgcolor']) && $data['line'] != 0) {
          if ($rows['bgcolor'] != '') {
            if ($isposted) {
              switch ($config['params']['doc']) {
                case 'RR':
                  if ($isapproved) {
                    return ['status' => false, 'msg' => 'Transaction has already been posted and approved.', 'data' => []];
                  }
                  break;
              }
            }

            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($table, $data, ['trno' => $data['trno'], 'line' => $data['line']]);
          }
        }
      }
    }

    $config['params']['trno'] = $trno;
    $txtdata = app('App\Http\Classes\modules\customform\\tripdetails')->paramsdata($config);

    $returnrow = $this->loaddata($config);

    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returnrow, 'reloadledgerdata' => true, 'txtdata' => $txtdata];
  }

  private function loaddataperrecord($config, $trno, $line)
  {
    $qry = "select d.trno, d.line, d.itemid, item.barcode, item.itemname, '' as bgcolor from tripdetail as d left join item on item.itemid=d.itemid where d.trno=? and d.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function lookupitem($config)
  {
    $lookupsetup = array(
      'type' => 'singlesearch',
      'actionsearch' => 'searchitem2',
      'title' => 'List of Products',
      'style' => 'width:900px;max-width:900px;height:80%'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array();
    $col = array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col = array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $data = $this->coreFunctions->opentable("select itemid, barcode, itemname,'' as bgcolor from item where fg_isequipmenttool=1");

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function> 

  public function lookupcallback($config)
  {

    $isposted = $this->othersClass->isposted2($config['params']['tableid'], "cntnum");
    $infotab = $this->head;
    if ($isposted) {
      $infotab = $this->hhead;
    }
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];

        $data = [];
        $data['trno'] = $trno;
        $data['line'] = 0;
        $data['clientid'] = 0;
        $data['itemid'] = $row['itemid'];
        $data['barcode'] = $row['barcode'];
        $data['itemname'] = $row['itemname'];
        $data['activity'] = '';
        $data['rate'] = "0.00";
        $data['empcode'] = '';
        $data['empname'] = '';

        $insertdata = [];
        foreach ($this->fields as $key => $value) {
          $insertdata[$value] = $this->othersClass->sanitizekeyfield($value, $data[$value]);
        }

        $qry = "select line as value from $infotab where trno=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$insertdata['trno']]);
        if ($line == '') {
          $line = 0;
        }

        $line += 1;
        $data['bgcolor'] = '';
        $data['line'] = $line;
        $insertdata['line'] = $data['line'];
        $insertdata['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
        $insertdata['encodedby'] = $config['params']['user'];
        $this->coreFunctions->sbcinsert($infotab, $insertdata);

        return ['status' => true, 'msg' => 'New data was added.', 'data' => $data];
        break;
    }
  } // end function

  public function lookupemployee($config)
  {
    //default
    $plotting = array('clientid' => 'clientid', 'empcode' => 'client',  'empname' => 'clientname');
    $plottype = 'plotgrid';
    $title = 'List of Employe';
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select clientid,client,clientname from client where isemployee=1 order by clientname";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function

  public function add($config)
  {
    $data = [];
    $trno = $config['params']['tableid'];
    $data['trno'] = $trno;
    $data['line'] = 0;
    $data['clientid'] = 0;
    $data['itemid'] = 0;
    $data['barcode'] = '';
    $data['itemname'] = '';
    $data['activity'] = '';
    $data['rate'] = "0.00";
    $data['empcode'] = '';
    $data['empname'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }
} //end class
