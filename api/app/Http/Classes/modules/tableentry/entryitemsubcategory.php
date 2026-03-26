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
use App\Http\Classes\SBCPDF;

class entryitemsubcategory
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Item Sub-Category';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'itemsubcategory';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['name'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 2517, 'save' => 2517);
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 22: //eipi
        $this->modulename = 'Category 3';
        break;
    }
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'name']]];

    $stockbuttons = ['save', 'delete'];

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $stockbuttons = [];
      }
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $tbuttons = ['whlog'];
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['name'] = '';
    $data['bgcolor'] = 'bg-blue-2';
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
    $companyid = $config['params']['companyid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0 && $data[$key]['name'] != '') {
          $qry = "select name from itemsubcategory where name = '" . $data[$key]['name'] . "' limit 1";
          $opendata = $this->coreFunctions->opentable($qry);
          $resultdata =  json_decode(json_encode($opendata), true);
          if (!empty($resultdata[0]['name'])) {
            if (trim($resultdata[0]['name']) == trim($data[$key]['name'])) {
              return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata]];
            }
          }
        }
        if (trim($data[$key]['name'] == '')) {
          return ['status' => false, 'msg' => 'Name is empty'];
        }
        if ($companyid == 37) $data2['name'] = strtoupper($data2['name']); //mega crystal

        if ($data[$key]['line'] == 0) {
          $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['name']);
        } else {

          if ($data[$key]['line'] != 0 && $data[$key]['name'] != '') {
            $qry = "select name,line from itemsubcategory where name = '" . $data[$key]['name'] . "' limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['name'])) {
              if (trim($resultdata[0]['name']) == trim($data[$key]['name'])) {
                if ($data[$key]['line'] == $resultdata[0]['line']) {
                  goto update;
                }
                return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
              } else {
                update:
                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];
                $data2['ismirror'] = 0;

                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['name']);
              }
            } else {
              goto update;
            }
          }
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
    $companyid = $config['params']['companyid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0 && $row['name'] != '') {
      $qry = "select name from itemsubcategory where name = '" . $row['name'] . "' limit 1";
      $opendata = $this->coreFunctions->opentable($qry);
      $resultdata =  json_decode(json_encode($opendata), true);
      if (!empty($resultdata[0]['name'])) {
        if (trim($resultdata[0]['name']) == trim($row['name'])) {
          return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata]];
        }
      }
    }
    if (trim($data['name'] == '')) {
      return ['status' => false, 'msg' => 'Name is empty'];
    }

    if ($companyid == 37)  $data['name'] = strtoupper($data['name']); //megacrystal

    if ($row['line'] == 0) {

      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['name']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($row['line'] != 0 && $row['name'] != '') {
        $qry = "select name,line from itemsubcategory where name = '" . $row['name'] . "' limit 1";
        $opendata = $this->coreFunctions->opentable($qry);
        $resultdata =  json_decode(json_encode($opendata), true);
        if (!empty($resultdata[0]['name'])) {
          if (trim($resultdata[0]['name']) == trim($row['name'])) {
            if ($row['line'] == $resultdata[0]['line']) {
              goto update;
            }
            return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$row['line'] . ' -- ' . $resultdata[0]['line']]];
          } else {
            update:
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $data['ismirror'] = 0;

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
              $returnrow = $this->loaddataperrecord($row['line']);
              $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['name']);
              return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
              return ['status' => false, 'msg' => 'Saving failed.'];
            }
          }
        } else {
          goto update;
        }
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['name']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $company = $config['params']['companyid'];
    $limit = '';
    $filtersearch = "";
    $searcfield = $this->fields;
    $search = '';
    if ($company == 10 || $company == 12) { //afti, afti usd
      $limit = '25';
    }
    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    if ($search != "") {
      $l = '';
    } else {
      $l =  $limit == '' ? '' : " limit " . $limit;
    }
    $qry = "select " . $select . " from " . $this->table . " where 1=1 " . $filtersearch . " order by line $l";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Item Sub-Category Master Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
