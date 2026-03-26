<?php

namespace App\Http\Classes\modules\hmsentry;

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
use App\Http\Classes\lookup\hmslookup;

class entryrates
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RATE CODE SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'hmsrates';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['roomtypeid', 'ratecodeid', 'rate', 'isdefault', 'isinactive'];
  public $showclosebtn = false;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->hrislookup = new hmslookup;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 3502);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'code', 'rate', 'description', 'isdefault', 'isinactive'
      ]
    ]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:20px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = "Rate Code";
    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['label'] = "Rate (Night)";

    $obj[0][$this->gridname]['columns'][3]['type'] = "label";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addratecode', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'addratecode':
        return $this->hrislookup->lookupratecode($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $data = [];
    foreach ($row  as $key2 => $value) {
      $config['params']['row']['line'] = 0;
      $config['params']['row']['roomtypeid'] = $id;
      $config['params']['row']['ratecodeid'] = $value['line'];
      $config['params']['row']['description'] = $value['description'];
      $config['params']['row']['rate'] = '0.00';
      $config['params']['row']['isdefault'] = 'false';
      $config['params']['row']['isinactive'] = 'false';
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  } // end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function    

  private function selectqry()
  {
    $qry = "s.line, s.roomtypeid, s.ratecodeid, r.code, r.description, s.rate,
    (case when s.isdefault=0 then 'false' else 'true' end) as isdefault,
    (case when s.isinactive=0 then 'false' else 'true' end) as isinactive";
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['roomtypeid'], $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['roomtypeid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($id, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as s " . $this->leftjointables() . " where s.roomtypeid=? and s.line=?";
    $data = $this->coreFunctions->opentable($qry, [$id, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $id = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as s " . $this->leftjointables() . " where roomtypeid=?  order by s.line";
    $data = $this->coreFunctions->opentable($qry, [$id]);
    return $data;
  }

  public function leftjointables()
  {
    return "
        left join hmsratesetup as r on r.line=s.ratecodeid
        ";
  }
} //end class
