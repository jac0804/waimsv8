<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewsupervisors
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;
  private $logger;

  public $modulename = 'SUPERVISORS';
  public $gridname = 'inventory';
  private $fields = ['clientid', 'seq'];
  private $table = 'approvers';

  public $tablelogs = 'table_log';

  public $style = 'width:100%;max-width:80%;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 5363
    );
    return $attrib;
  }

  public function createHeadField($config)
  {
    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];
    } else {
      $trno = $config['params']['dataparams']['trno'];
    }

    return $this->getheaddata($trno, $config['params']['doc']);
  }

  public function getheaddata($trno, $doc)
  {
    return [];
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $companyid =  $config['params']['companyid'];
    $line = $config['params']['row']['line'];
    $labelname = $config['params']['row']['labelname'];
    $column = ['action', 'clientname', 'seq', 'itemname'];

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $column]];

    $stockbuttons = ['delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $obj[0][$this->gridname]['columns'][$seq]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;';
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';

    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$clientname]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$clientname]['lookupclass'] = 'lookupemployee';

    if ($companyid != 58) $obj[0][$this->gridname]['columns'][$seq]['type'] = 'coldel';

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    $this->modulename .= ' - ' . $labelname;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid =  $config['params']['companyid'];
    $line = $config['params']['row']['line'];

    $tbuttons = ['addrecord'];
    if ($companyid == 58) $tbuttons = ['addrecord', 'saveallentry'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['lookupclass'] = 'lookupemployee';
    $obj[0]['action'] = 'lookupsetup';
    return $obj;
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
        $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line'], 'clientid' => $data[$key]['clientid']]);
      }
    }

    $returndata = $this->loaddata($config);
    $sourcerow = $this->loadsourcerow($config, $data[0]['trno']);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadtableentry' => $sourcerow];
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupemployee':
        return $this->lookupemployee($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['row'];
    $data = [];
    $returndata = [];

    $this->othersClass->logConsole(json_encode($row));
    $config['params']['row'] = [
      'line' => 0,
      'trno' => $config['params']['sourcerow']['line'],
      'clientid' => $row['clientid'],
      'issupervisor' => 1,
      'isapprover' => 0,
      'seq' => 0,
      'bgcolor' => 'bg-blue-2'
    ];
    $return = $this->save($config);
    if ($return['status']) {
      $returndata = $return['row'][0];
      return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata, 'reloadtableentry' => $return['sourcerow']];
    } else {
      return ['status' => false, 'msg' => $return['msg']];
    }
  } // end function

  public function lookupemployee($config)
  {
    $modulename = $config['params']['sourcerow']['modulename'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Employee',
      'style' => 'width:80%;max-width:80%;height:700px'
    );
    $plotsetup = [
      'action' => 'addtogrid',
      'plottype' => 'tableentry'
    ];
    $cols = [
      ['name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;']
    ];


    $supervisor_id = [];
    $filter = "";

    $query = "select apr.clientid as id from approvers as apr 
    left join moduleapproval as approval on approval.line = apr.trno 
    where apr.issupervisor = 1  and approval.modulename = '$modulename'";

    $supervisor = $this->coreFunctions->opentable($query);


    foreach ($supervisor as $sup_id) {
      array_push($supervisor_id, $sup_id->id);
    }
    $id = !empty($supervisor_id) ? implode(",", $supervisor_id) : '0';
    if ($id != '0') {
      $filter = " and e.empid not in (" . $id . ")";
    }

    $data = $this->coreFunctions->opentable("select c.clientid, c.client, c.clientname, 0 as seq from client as c left join employee as e on e.empid=c.clientid where c.isemployee=1 and e.issupervisor=1 and c.isinactive=0 $filter ");
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = '';
    $data['clientname'] = '';
    $data['isapprover'] = 0;
    $data['issupervisor'] = 1;
    $data['bgcolor'] = 'bg-blue-2';
    $data['seq'] = 0;
    $data['itemname'] = '';
    return $data;
  }

  public function delete($config)
  {
    $clientid = $config['params']['row']['clientid'];
    $pendingapp = $this->coreFunctions->datareader("select clientid as value from pendingapp where clientid=? and approver='issupervisor'", [$clientid], '', true);
    if ($pendingapp != 0) {
      return ['status' => false, 'msg' => 'Cannot delete supervisor: pending applications exist.'];
    } else {
      $row = $config['params']['row'];
      $this->coreFunctions->execqry("delete from " . $this->table . " where line=" . $row['line'] . " and trno=" . $row['trno'], 'delete');
      $this->updateCounts($row['trno']);
      $sourcerow = $this->loadsourcerow($config, $row['trno']);
      return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadtableentry' => $sourcerow];
    }
  }

  public function loaddataperrecord($config, $trno, $line)
  {
    $data = $this->coreFunctions->opentable("select h.trno, h.line, h.clientid, c.clientname, h.issupervisor, h.isapprover, '' as bgcolor, h.seq, '' as itemname from " . $this->table . " as h left join client as c on c.clientid=h.clientid where h.trno=" . $trno . " and h.line=" . $line);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];

    $data = $this->coreFunctions->opentable("select h.trno, h.line, h.clientid, c.clientname, h.issupervisor, h.isapprover, '' as bgcolor, h.seq, '' as itemname from " . $this->table . " as h left join client as c on c.clientid=h.clientid where h.trno=" . $trno . " and h.issupervisor=1 order by h.seq");
    return $data;
  }

  public function updateCounts($trno)
  {
    $supervisors = $this->coreFunctions->datareader("select count(line) as value from " . $this->table . " where trno=" . $trno . " and issupervisor=1", [], '', true);
    $this->coreFunctions->execqry("update moduleapproval set countsupervisor=? where line=?", 'update', [$supervisors, $trno]);
  }

  public function loadsourcerow($config, $line)
  {
    $data = $this->coreFunctions->opentable("select line, modulename, labelname, countsupervisor, countapprover, approverseq, '' as bgcolor from moduleapproval");
    return $data;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $data['trno'] = $config['params']['sourcerow']['line'];
      $checking = $this->coreFunctions->datareader("select line as value from approvers where clientid=" . $data['clientid'] . " and trno=" . $data['trno'] . " and issupervisor=1", [], '', true);
      if ($checking != 0) return ['status' => false, 'msg' => 'Supervisor already exists.'];
      $data['isapprover'] = 0;
      $data['issupervisor'] = 1;
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      $this->updateCounts($data['trno']);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $data['trno'], $line);
        $sourcerow = $this->loadsourcerow($config, $data['trno']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'sourcerow' => $sourcerow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line'], 'trno' => $row['trno']]) == 1) {
        $this->updateCounts($row['trno']);
        $returnrow = $this->loaddataperrecord($config, $row['trno'], $row['line']);
        $sourcerow = $this->loadsourcerow($config, $row['trno']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'sourcerow' => $sourcerow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function
}
