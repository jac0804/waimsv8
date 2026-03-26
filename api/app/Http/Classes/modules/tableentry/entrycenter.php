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

class entrycenter
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Branch Masterfile';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'center';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['code', 'name', 'address', 'tel', 'tin', 'email', 'zipcode', 'station', 'branchid', 'warehouse', 'ismain', 'shortname', 'accountno', 'billingclerk', 'project', 'petty'];
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
    $attrib = array('load' => 798);
    return $attrib;
  }


  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 39:
        $columns = ['action', 'code', 'name', 'shortname', 'address', 'tel', 'email', 'tin', 'zipcode', 'station', 'warehouse', 'ismain',  'accountno', 'billingclerk', 'project'];
        break;
      case 56:
        $columns = ['action', 'code', 'name', 'branch', 'warehouse', 'address', 'tel', 'email', 'tin', 'zipcode', 'station', 'ismain', 'shortname', 'accountno', 'billingclerk', 'project', 'area', 'petty'];
        break;
      default:
        $columns = ['action', 'code', 'name', 'address', 'tel', 'email', 'tin', 'zipcode', 'station', 'branch', 'warehouse', 'ismain', 'shortname', 'accountno', 'billingclerk', 'project', 'area', 'petty'];
        break;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    if ($this->companysetup->getmultibranch($config['params'])) {
    } else {
      $this->modulename = 'Company Information';
    }

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:75px;whiteSpace: normal;min-width:75px;";
    $obj[0][$this->gridname]['columns'][$code]['style'] = "width:75px;whiteSpace: normal;min-width:75px;";
    $obj[0][$this->gridname]['columns'][$name]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";
    $obj[0][$this->gridname]['columns'][$address]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$tel]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$email]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$tin]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$zipcode]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$station]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$warehouse]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$ismain]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$accountno]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$billingclerk]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$project]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$shortname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$warehouse]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$project]['action'] = "lookupsetup";

    switch ($companyid) {
      case 10:
      case 12: //afti
        $obj[0][$this->gridname]['columns'][$email]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$accountno]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$billingclerk]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$project]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$petty]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$branch]['type'] = "coldel";
        break;
      case 35: //aquamax
        $obj[0][$this->gridname]['columns'][$project]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$petty]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$branch]['type'] = "coldel";
        break;
      case 39: //cbbsi
        $obj[0][$this->gridname]['columns'][$accountno]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$billingclerk]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$shortname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$petty]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$branch]['type'] = "coldel";
        break;
      case 49: //hotmix
        $obj[0][$this->gridname]['columns'][$accountno]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$billingclerk]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$project]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$shortname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$petty]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$branch]['type'] = "coldel";
        break;
      case 40:
      case 57: //cdo
        $obj[0][$this->gridname]['columns'][$accountno]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$billingclerk]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$project]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$accountno]['label'] = "Due to/from Account";
        $obj[0][$this->gridname]['columns'][$accountno]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$accountno]['lookupclass'] = "lookupcoa";
        $obj[0][$this->gridname]['columns'][$accountno]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$branch]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$station]['type'] = "coldel";
        break;
      case 56: //homeworks
        $obj[0][$this->gridname]['columns'][$accountno]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$billingclerk]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$project]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$petty]['type'] = "coldel";

        $obj[0][$this->gridname]['columns'][$warehouse]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$branch]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";

        $obj[0][$this->gridname]['columns'][$branch]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$branch]['lookupclass'] = "lookupbranch";
        $obj[0][$this->gridname]['columns'][$branch]['type'] = "lookup";
        break;
      case 37: //megacrystal
        $obj[0][$this->gridname]['columns'][$shortname]['type'] = "coldel";
        break;
    }

    $obj[0][$this->gridname]['columns'][$code]['type'] = "label";

    // if ($companyid != 35) { //not aquamax
    //   $obj[0][$this->gridname]['columns'][$accountno]['type'] = "coldel";
    //   $obj[0][$this->gridname]['columns'][$billingclerk]['type'] = "coldel";
    // }

    // if ($companyid != 39) { //not cbbsi
    //   $obj[0][$this->gridname]['columns'][$project]['type'] = "coldel";
    // }

    // if ($companyid == 39 || $companyid == 49) { //cbbsi, hotmix
    //   $obj[0][$this->gridname]['columns'][$shortname]['type'] = "input";
    //   $obj[0][$this->gridname]['columns'][$shortname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    // } else {
    //   $obj[0][$this->gridname]['columns'][$shortname]['type'] = "label";
    // }



    // if ($companyid != 40 || $companyid != 57) { //not cdo
    //   $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
    //   $obj[0][$this->gridname]['columns'][$petty]['type'] = "coldel";
    // } else {
    //   $obj[0][$this->gridname]['columns'][$accountno]['label'] = "Due to/from Account";
    //   $obj[0][$this->gridname]['columns'][$accountno]['type'] = "lookup";
    //   $obj[0][$this->gridname]['columns'][$accountno]['lookupclass'] = "lookupcoa";
    //   $obj[0][$this->gridname]['columns'][$accountno]['action'] = "lookupsetup";
    //   $obj[0][$this->gridname]['columns'][$area]['type'] = "coldel";
    //   $obj[0][$this->gridname]['columns'][$shortname]['type'] = "coldel";
    // }

    // if ($companyid == 56) { //homweworks
    //   $obj[0][$this->gridname]['columns'][$warehouse]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    //   $obj[0][$this->gridname]['columns'][$branch]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";

    //   $obj[0][$this->gridname]['columns'][$branch]['action'] = "lookupsetup";
    //   $obj[0][$this->gridname]['columns'][$branch]['lookupclass'] = "lookupbranch";
    //   $obj[0][$this->gridname]['columns'][$branch]['type'] = "lookup";
    // } else {
    //   $obj[0][$this->gridname]['columns'][$branch]['type'] = "coldel";
    // }


    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['code'] = '';
    $data['name'] = '';
    $data['address'] = '';
    $data['tel'] = '';
    $data['tin'] = '';
    $data['zipcode'] = '';
    $data['station'] = '';
    $data['ismain'] = 'false';
    $data['warehouse'] = '';
    $data['email'] = '';
    $data['shortname'] = '';
    $data['accountno'] = '';
    $data['billingclerk'] = '';
    $data['project'] = '';
    $data['areaid'] = 0;
    $data['branchid'] = 0;
    $data['petty'] = 0;
    $data['branch'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry($config)
  {
    $addfield = "";
    if ($config['params']['companyid'] == 40) { //cdo
      $addfield .= ",area.area";
    }

    $qry = "center.line,center.code,center.name,center.address,center.tel,center.tin,center.zipcode,center.station,center.warehouse,center.email,center.shortname,
      case when center.ismain=0 then 'false' else 'true' end as ismain,center.accountno,center.billingclerk,center.project,center.branchid,ifnull(br.client,'') as branch,center.petty $addfield";
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
        if ($config['params']['companyid'] == 40) { //cdo
          $data2["accountno"] = $this->othersClass->sanitizekeyfield("acno", $data[$key]['accountno']);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
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
    $code = $row['code'];

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($config['params']['companyid'] == 40) { //cdo
      $data2["accountno"] = $this->othersClass->sanitizekeyfield("acno", $data[$key]['accountno']);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $qry = "select code as value from center where line =?";
      $ocode = $this->coreFunctions->datareader($qry, [$row['line']]);

      if ($ocode != $code) {
        $qry = "select center as value from cntnum where center = ?";
        $count = $this->coreFunctions->datareader($qry, [$ocode]);
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($count != '') {
          $data['code'] = $ocode;
          if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
            $returnrow = $this->loaddataperrecord($config, $row['line']);
            return ['status' => true, 'msg' => 'Successfully saved. Cannot update branch code, already have transaction...', 'row' => $returnrow];
          } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
          }
        }
      } else {
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($config, $row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $code = $row['code'];

    $qry = "select center as value from cntnum where center = ?";
    $count = $this->coreFunctions->datareader($qry, [$code]);
    if ($count != '') {
      return ['code' => $code, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($config, $line)
  {
    $leftjoin = "";
    if ($config['params']['companyid'] == 40) { //cdo
      $leftjoin = " left join area on area.line = center.areaid";
    }
    $leftjoin .= " left join client as br on br.clientid = center.branchid";

    $select = $this->selectqry($config);
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . "  $leftjoin where center.line=? ";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $leftjoin = "";
    if ($config['params']['companyid'] == 40) { //cdo
      $leftjoin = " left join area on area.line = center.areaid";
    }
    $leftjoin .= " left join client as br on br.clientid = center.branchid";

    $sort = 'center.line';
    if ($config['params']['companyid'] == 56) {
      $sort = 'center.name';
    }

    $select = $this->selectqry($config);
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " $leftjoin  order by " . $sort;

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  public function lookupsetup($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'whstock':
        return $this->lookupwh($config);
        break;
      case 'dproject':
        return $this->lookupproj($config);
        break;
      case 'lookupcoa':
        return $this->lookupcoa($config);
        break;
      case 'lookarea':
        return $this->lookuparea($config);
        break;
      case 'lookupbranch':
        return $this->lookupbranch($config);
        break;
    }
  }

  public function lookupbranch($config)
  {
    //default
    $plotting = array('branch' => 'client', 'branchid' => 'clientid');
    $plottype = 'plotgrid';
    $title = 'List of Branches';
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
    $col = array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select clientid,client,clientname from client where isbranch=1 order by client";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }

  public function lookupwh($config)
  {
    //default
    $plotting = array('warehouse' => 'client');
    $plottype = 'plotgrid';
    $title = 'List of Warehouse';
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
    $col = array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select client,clientname from client where iswarehouse=1 order by client";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function


  public function lookupproj($config)
  {
    //default
    $plotting = array('project' => 'code');
    $plottype = 'plotgrid';
    $title = 'List of Project';
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
    $col = array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select code,name from projectmasterfile order by code";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function

  public function lookupcoa($config)
  {
    //default
    $plotting = array('accountno' => 'acno');
    $plottype = 'plotgrid';
    $title = 'List of Accounts';
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
    $col = array('name' => 'acno', 'label' => 'Code', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select acno,acnoname from coa order by acno";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function


  function lookuparea($config)
  {
    $plotting = array('area' => 'area', 'areaid' => 'line');
    $plottype = 'plotgrid';
    $title = 'List of Area';
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
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'area', 'label' => 'Area', 'align' => 'left', 'field' => 'area', 'sortable' => true, 'style' => 'font-size:16px;')
    );
    $qry = "select code,area,line from area where inactive = 0 order by area,line";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }
} //end class
