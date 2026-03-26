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
use App\Http\Classes\posClass;

class entrybranchbrand
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Brand';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'branchbrand';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['clientid', 'brandid', 'isinactive'];
  public $showclosebtn = true;
  private $posClass;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->posClass = new posClass;
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'brand', 'isinactive']]];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns']['1']['readonly'] = true;
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'syncall'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['action'] = 'lookupsetup';
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['brandid'] = 0;
    $data['brand'] = '';
    $data['isinactive'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line,wh,whname,isdefault,isinactive,clientid,whid";
    return $qry;
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
        $data2['dlock'] = $this->othersClass->getCurrentTimeStamp();
        if ($data[$key]['line'] == 0) {
          $exist = $this->coreFunctions->getfieldvalue("branchbrand", "brandid", "clientid=? and brandid=? and isinactive =0 ", [$data[$key]['clientid'], $data[$key]['brandid']]);
          if ($exist == '') {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
          }
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    if ($row['line'] == 0) {
      $exist = $this->coreFunctions->getfieldvalue("branchbrand", "brandid", "clientid=? and brandid=? and isinactive =0 ", [$data['clientid'], $data['brandid']]);
      if ($exist == '') {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
      }

      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function


  public function lookupsetup($config)
  {
    return $this->lookupbrand($config);
  }


  public function lookupbrand($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Brands',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    $cols = [
      ['name' => 'brand', 'label' => 'Brand', 'align' => 'left', 'field' => 'brand', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select 0 as brandid, '' as brand union all select brandid , brand_desc as brand from frontend_ebrands";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $data = [];
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];
        $exist = $this->coreFunctions->getfieldvalue("branchbrand", "brandid", "clientid=? and brandid=? and isinactive =0", [$trno, $row['brandid']]);
        if (strlen($exist) == 0) {
          $data['line'] = 0;
          $data['clientid'] = $config['params']['tableid'];
          $data['brandid'] = $row['brandid'];
          $data['brand'] = $row['brand'];
          $data['isinactive'] = 0;
          $data['bgcolor'] = 'bg-blue-2';
          return ['status' => true, 'msg' => 'Add brand success...', 'data' => $data];
        } else {
          return [];
        }

        break;
    }
  }

  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select b.line,b.clientid,b.brandid,c.brand_desc as brand,case b.isinactive when 1 then 'true' else 'false' end as isinactive,'' as bgcolor from " . $this->table . " as b left join frontend_ebrands as c  on c.brandid = b.brandid where b.clientid =? and b.line=?";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select b.line,b.clientid,b.brandid,c.brand_desc as brand,case b.isinactive when 1 then 'true' else 'false' end as isinactive,'' as bgcolor from " . $this->table . " as b left join frontend_ebrands as c on c.brandid = b.brandid where b.clientid =? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }



  public function tableentrystatus($config)
  {
    switch ($config['params']['action2']) {
      case 'syncallentry':
        $tableid = $config['params']['tableid'];
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $station = $this->coreFunctions->opentable($qry, [$tableid]);
        $branchcode = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$tableid]);
        $qry = "select b.line,b.clientid,b.brandid,c.brand_desc as brand,b.isinactive as isinactive from " . $this->table . " as b left join frontend_ebrands as c on c.brandid = b.brandid where b.clientid =? order by line";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        $csv = $this->posClass->createcsv($data, 1);
        foreach ($station as $key => $value) {
          $this->posClass->ftpcreatefile($csv, $branchcode, $value->station, 'download', 'brand');
        }
        return ['status' => true, 'msg' => 'File created...', 'data' => $config['params']['data']];
        break;
      default:
        if (isset($config['params']['data'])) {
          return ['status' => true, 'msg' => 'No function yet', 'data' => $config['params']['data']];
        } else {
          return ['status' => true, 'msg' => 'No function yet', 'data' => []];
        }
        break;
    }
  } //end function

} //end class
