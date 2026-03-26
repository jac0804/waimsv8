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
use App\Http\Classes\builder\lookupclass;

class entryapprovers
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'APPROVERS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'approverdetails';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['approver', 'appline', 'ordernum'];
  public $showclosebtn = false;
  private $lookupclass;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupclass = new lookupclass;
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
    $clientname = 1;
    $moduletype = 2;
    $ordernum = 3;
    $ischecker = 4;
    $isapprover = 5;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'clientname', 'moduletype', 'ordernum', 'ischecker', 'isapprover']]];

    $stockbuttons = ['save', 'delete', 'addapprovercat', 'addapproverdept'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:15%;whiteSpace: normal;min-width:15%;";
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:40%;whiteSpace: normal;min-width:40%;";
    $obj[0][$this->gridname]['columns'][$moduletype]['style'] = "width:35%;whiteSpace: normal;min-width:35%;";
    $obj[0][$this->gridname]['columns'][$ordernum]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";

    $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Name";
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$moduletype]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$moduletype]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$moduletype]['lookupclass'] = "lookupapproverdoc";

    $obj[0][$this->gridname]['columns'][$ordernum]['label'] = "Order";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['assignuser', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'ADD APPROVERS';
    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['sourcerow']['line'];
    $data = [];
    $data['line'] = 0;
    $data['appline'] = $id;
    $data['approver'] = '';
    $data['moduletype'] = '';
    $data['clientname'] = '';
    $data['ordernum'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
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
      $data['createby'] = $config['params']['user'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $this->othersClass->logConsole($line);
        $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $row['clientname']);
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

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $qry = "delete from approverdept where appid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $qry = "delete from approverrcat where appid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $this->logger->sbcmasterlog($row['line'], $config, ' REMOVE - ' . $row['clientname']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($config, $line)
  {
    return $this->loaddata($config, $line);
  }

  public function loaddata($config, $line = 0)
  {
    $filter = '';
    if ($line != 0) {
      $filter = ' where app.line=' . $line;
    }

    $qry = "select app.line, app.appline, app.approver, client.clientname, 
        case s.doc
        when 'CD' then 'Canvass Sheet'
        when 'PO' then 'Purchase Order'
        when 'CV' then 'Cash/Check Voucher'
        else '' end moduletype,
    '' as bgcolor, app.ordernum, 
    if(s.ischecker=1,'true','false') as ischecker, 
    if(s.isapprover=1,'true','false') as isapprover 
    from approverdetails as app left join client on client.email=app.approver left join approversetup as s on s.line=app.appline " . $filter . " order by s.doc";
    $this->othersClass->logConsole($qry);
    $data = $this->coreFunctions->opentable($qry);
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
        if ($data[$key]['line'] == 0) {
          $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['clientname']);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['clientname']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'assignuser':
        return $this->assignuserapprovers($config);
        break;

      case 'lookupapproverdoc':
        return $this->lookupapproverdoc($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function assignuserapprovers($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Approvers',
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array(
      array('name' => 'clientname', 'label' => 'Users', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select concat(0,clientid) as keyid, clientid, client.email, clientname
    from client left join employee as emp on emp.empid=client.clientid where client.isemployee=1 and emp.isapprover=1 and client.email<>'' order by clientname";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $data = [];
    $returndata = [];

    $this->othersClass->logConsole(json_encode($row));

    foreach ($row  as $key2 => $value) {
      $config['params']['row']['line'] = 0;
      $config['params']['row']['appline'] = 0;
      $config['params']['row']['ordernum'] = 0;
      $config['params']['row']['approver'] = $row[$key2]['email'];
      $config['params']['row']['clientname'] = $row[$key2]['clientname'];
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $config['params']['row']['ischecker'] = 0;
      $config['params']['row']['isapprover'] = 0;
      $return = $this->save($config);
      if ($return['status']) {
        array_push($returndata, $return['row'][0]);
      }
    }
    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata];
  } // end function

  public function lookupapproverdoc($config)
  {
    $plotting = array('appline' => 'line', 'moduletype' => 'moduletype');
    $plottype = 'plotgrid';
    $title = 'List of Module';

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
      ['name' => 'moduletype', 'label' => 'Name', 'align' => 'left', 'field' => 'moduletype', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select line, 
        case doc
        when 'CD' then 'Canvass Sheet (Approver)'
        when 'PO' then 'Purchase Order (Approver)'
        when 'CV' then 'Cash/Check Voucher (Approver)'
        else '' 
        end moduletype, isapprover, ischecker  from approversetup where isapprover=1 
        union all
        select line, 
        case doc
        when 'CD' then 'Canvass Sheet (Checker)'
        when 'PO' then 'Purchase Order (Checker)'
        when 'CV' then 'Cash/Check Voucher (Checker)'
        else '' 
        end moduletype, isapprover, ischecker from approversetup where ischecker=1 
        order by moduletype";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }


  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Item Sub Category Master Logs',
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
