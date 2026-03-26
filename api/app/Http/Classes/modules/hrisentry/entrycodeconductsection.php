<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\lookup\hrislookup;

class entrycodeconductsection
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SECTION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'codedetail';
  public $tablelogs = 'masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [
    'section',
    'description',
    'd1a',
    'd1b',
    'd2a',
    'd2b',
    'd3a',
    'd3b',
    'd4a',
    'd4b',
    'd5a',
    'd5b'
  ];
  public $showclosebtn = false;
  private $hrislookup;
  private $logger;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->hrislookup = new hrislookup;
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
    $access = $this->othersClass->checkAccess($config['params']['user'], 1338);
    $columns = ['action', 'section', 'description', 'd1a', 'd1b', 'd2a', 'd2b', 'd3a', 'd3b', 'd4a', 'd4b', 'd5a', 'd5b'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];

    if ($access == 1) {
      $stockbuttons = ['save', 'delete'];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$section]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:1000px;whiteSpace: normal;min-width:1000px;";
    $obj[0][$this->gridname]['columns'][$d1a]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][$d1b]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

    $obj[0][$this->gridname]['columns'][$d2a]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][$d2b]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

    $obj[0][$this->gridname]['columns'][$d3a]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][$d3b]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

    $obj[0][$this->gridname]['columns'][$d4a]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][$d4b]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

    $obj[0][$this->gridname]['columns'][$d5a]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][$d5b]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

    $obj[0][$this->gridname]['columns'][$section]['type'] = "input";

    if ($access == 0) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';

      $obj[0][$this->gridname]['columns'][$section]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d1a]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d1b]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d2a]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d2b]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d3a]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d3b]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d4a]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d4b]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d5a]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$d5b]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }


  public function createtabbutton($config)
  {
    $access = $this->othersClass->checkAccess($config['params']['user'], 1338);
    $tbuttons = [];
    if ($access == 1) {
      $tbuttons = ['addrecord', 'saveallentry'];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['section'] = '';
    $data['description'] = '';
    $data['d1a'] = '';
    $data['d1b'] = 0;
    $data['d2a'] = '';
    $data['d2b'] = 0;
    $data['d3a'] = '';
    $data['d3b'] = 0;
    $data['d4a'] = '';
    $data['d4b'] = 0;
    $data['d5a'] = '';
    $data['d5b'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    if ($config['params']['tableid'] == 0)  return ['status' => false, 'msg' => 'Saving failed. Invalid tableid'];

    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['artid'] = $config['params']['tableid'];
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog(
            $config['params']['tableid'],
            $config,
            'CREATE DETAILS - ' . ' LINE: ' . $line
              . ' , SECTION: ' . $data2['section']
              . ' , DESCRIPTION: ' . $data2['description']
              . ' , FIRST: ' . $data2['d1a']
              . ' , # OF DAYS: ' . $data2['d1b']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line'], 'artid' => $config['params']['tableid']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    if ($config['params']['tableid'] == 0)  return ['status' => false, 'msg' => 'Saving failed. Invalid tableid'];

    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['artid'] = $config['params']['tableid'];

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($data['artid'], $line);
        $this->logger->sbcmasterlog(
          $config['params']['tableid'],
          $config,
          'CREATE DETAILS - ' . ' LINE: ' . $line
            . ' , SECTION: ' . $data['section']
            . ' , DESCRIPTION: ' . $data['description']
            . ' , FIRST: ' . $data['d1a']
            . ' , # OF DAYS: ' . $data['d1b']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line'], 'artid' => $data['artid']]) == 1) {
        $returnrow = $this->loaddataperrecord($data['artid'], $row['line']);
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

    $this->logger->sbcmasterlog(

      $config['params']['tableid'],
      $config,
      'DELETE DETAILS - ' . ' LINE: ' . $row['line']
        . ' , SECTION: ' . $row['section']
        . ' , DESCRIPTION: ' . $row['description']
        . ' , FIRST: ' . $row['d1a']
        . ' , # OF DAYS: ' . $row['d1b']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  private function loaddataperrecord($artid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where artid=? and line=? and artid<>0";
    $data = $this->coreFunctions->opentable($qry, [$artid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $artid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where artid=? and artid<>0 order by line";
    $data = $this->coreFunctions->opentable($qry, [$artid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupdsectionhris':
        return $this->hrislookup->lookupdsectionhris($config);
        break;
    }
  }
} //end class
