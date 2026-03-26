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

class entrybranchstation
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Station';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'branchstation';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['clientid', 'station', 'ipaddress', 'localport', 'localdb', 'username', 'password', 'compname', 'compaddress', 'comptel', 'tin', 'operatedby', 'footer1', 'footer2', 'footer3', 'footer4', 'footer5', 'serialno', 'min', 'permitno', 'accredno', 'dateissued', 'validuntil', 'seniorpwddisc', 'projectid', 'isinactive'];
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
    $columns = ['action', 'station', 'compname', 'compaddress', 'comptel', 'project', 'tin', 'operatedby', 'footer1', 'footer2', 'footer3', 'footer4', 'footer5', 'serialno', 'min', 'permitno', 'accredno', 'dateissued', 'validuntil', 'seniorpwddisc', 'isinactive', 'barcode'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $stockbuttons = ['save', 'sync'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$compname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$compaddress]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;max-width:500px;';
    $obj[0][$this->gridname]['columns'][$compaddress]['type'] = 'textarea';

    $obj[0][$this->gridname]['columns'][$comptel]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$tin]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$dateissued]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';

    $obj[0][$this->gridname]['columns'][$operatedby]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$footer1]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$footer2]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$footer3]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$footer4]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$footer5]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';

    $obj[0][$this->gridname]['columns'][$serialno]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$min]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$permitno]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$accredno]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';

    $obj[0][$this->gridname]['columns'][$min]['label'] = 'MIN';
    $obj[0][$this->gridname]['columns'][$project]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'][$barcode]['label'] = '';
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['station'] = '';
    $data['ipaddress'] = '';
    $data['localport'] = '';
    $data['localdb'] = '';
    $data['username'] = '';
    $data['password'] = '';
    $data['compname'] = '';
    $data['compaddress'] = '';
    $data['comptel'] = '';
    $data['tin'] = '';
    $data['operatedby'] = '';
    $data['footer1'] = '';
    $data['footer2'] = '';
    $data['footer3'] = '';
    $data['footer4'] = '';
    $data['footer5'] = '';
    $data['serialno'] = '';
    $data['min'] = '';
    $data['permitno'] = '';
    $data['accredno'] = '';
    $data['dateissued'] = '';
    $data['validuntil'] = '';
    $data['project'] = '';
    $data['projectid'] = 0;
    $data['seniorpwddisc'] = '0';
    $data['isinactive'] = 'false';
    $data['bgcolor'] = 'bg-blue-2';
    $data['barcode'] = ''; //used to properly follow the column width
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
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $index = array_search('station', $data);
          if ($index !== FALSE) {
            unset($data[$index]);
          }
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
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $index = array_search('station', $data);
      if ($index !== FALSE) {
        unset($data[$index]);
      }
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function


  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select b.line,b.clientid,b.station,b.ipaddress,b.localport,b.localdb,b.username,b.password,b.compname,b.compaddress,b.comptel,b.tin,b.operatedby,b.footer1,b.footer2,b.footer3,b.footer4,b.footer5,b.serialno,b.min,b.permitno,b.accredno,b.dateissued,b.validuntil,b.seniorpwddisc,b.projectid,pm.name as project,if(b.isinactive=1,'true','false') as isinactive,'' as bgcolor,'' as barcode from " . $this->table . " as b
    left join projectmasterfile as pm on pm.line = b.projectid
    where b.clientid =? and b.line=?";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select b.line,b.clientid,b.station,b.ipaddress,b.localport,b.localdb,b.username,b.password,b.compname,b.compaddress,b.comptel,b.tin,b.operatedby,b.footer1,b.footer2,b.footer3,b.footer4,b.footer5,b.serialno,b.min,b.permitno,b.accredno,b.dateissued,b.validuntil,b.seniorpwddisc,b.projectid,pm.name as project,if(b.isinactive=1,'true','false') as isinactive,'' as bgcolor,'' as barcode from " . $this->table . " as b
    left join projectmasterfile as pm on pm.line = b.projectid
    where b.clientid =? order by b.line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function tableentrystatus($config)
  {
    ini_set('max_execution_time', -1); //3mins

    switch ($config['params']['action2']) {
      case 'syncperitem':
        $tableid = $config['params']['tableid'];
        $line = $config['params']['row']['line'];
        $station = $config['params']['row']['station'];
        $branchcode = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$tableid]);
        $qry = "select line,clientid,station,ipaddress,localport,localdb,username,password,compname,compaddress,comptel,tin,operatedby,footer1,footer2,footer3,footer4,footer5,serialno,min,permitno,accredno,dateissued,seniorpwddisc,validuntil,isinactive from " . $this->table . " where clientid =? and line=?";
        $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
        $csv = $this->posClass->createcsv($data, 1);
        $this->posClass->ftpcreatefile($csv, $branchcode, $station, 'download', 'station');

        $now = $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->execqry("update item set dlock='" . $now . "'");
        $this->posClass->itemlist();

        $this->coreFunctions->execqry("update client set dlock='" . $now . "'");
        $this->posClass->clientlist($config['params']);

        $this->posClass->ftpcreatefolder($branchcode, $station, 'upload');

        return ['status' => true, 'msg' => 'File created...'];
        break;
    }
  } //end function


  public function lookupsetup($config)
  {
    return $this->lookupproject($config);
  }

  public function lookupproject($config)
  {
    //default
    $plotting = [
      'projectid' => 'line',
      'project' => 'name',
      'projectname' => 'name'
    ];
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
    $cols = [
      ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "
      select 0 as line, ' ' as code, ' ' as name
      union all
      select line,code,name 
      from projectmasterfile 
      order by line";
    $data = $this->coreFunctions->opentable($qry);

    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function

} //end class
