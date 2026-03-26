<?php

namespace App\Http\Classes\modules\operation;

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

class tacrf
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TACRF';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'tacrf';
  public $detail = 'tacrfdet';
  public $prefix = 'TF';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'dateid',
    'authrep', 'refno', 'orno',
    'clientid'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = false;
  private $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 4216,
      'edit' => 4303,
      'save' => 4303,
      'view' => 4217,
      'additem' => 4303,
      'edititem' => 4303,
      'deleteitem' => 4303,
      'saveitem' => 4303,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'client', 'clientname'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[1]['label'] = 'Tenant Code';
    $cols[2]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[2]['label'] = 'Tenant Name';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['c.client', 'c.clientname'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }
    $qry = "
        select
        c.clientid,
        c.client,
        c.clientname
        from client as c left join tenancystatus as ts on c.clientid = ts.clientid
        where ts.status = 'Non-Renewable' and ts.applied<>1 and ts.inactive <> 1 $filtersearch
        order by c.clientname";
    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      // 'new',
      'save',
      // 'delete',
      'cancel',
      'print',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $action = 0;
    $dept = 1;
    $rem = 2;
    $clearedby = 3;
    $cleardate = 4;
    $gridcolumn = ['action', 'dept', 'rem', 'clearedby', 'cleareddate'];

    $tab = [
      $this->gridname => ['gridcolumns' => $gridcolumn],

    ];


    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['label'] = 'Details';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Accountability';
    $obj[0][$this->gridname]['columns'][$dept]['action'] = 'lookupclient';
    $obj[0][$this->gridname]['columns'][$dept]['lookupclass'] = 'lookuptenantdept';
    $obj[0][$this->gridname]['showtotal'] = false;

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['client', 'clientname', 'prepared'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.required', false);
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookup
    client');
    data_set($col1, 'client.lookupclass', 'tenant');
    data_set($col1, 'prepared.name', 'authrep');
    data_set($col1, 'prepared.label', 'Authorized Representative');

    data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');

    $fields = ['dateid', 'loc', 'ref', 'leasecontract'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'ref.name', 'refno');
    data_set($col2, 'leasecontract.class', 'csleasecontract sbccsreadonly');
    data_set($col2, 'loc.class', 'sbccsreadonly');
    data_set($col2, 'ref.class', 'sbccsreadonly');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['clientname'] = '';
    $data[0]['authrep'] = '';
    $data[0]['loc'] = '';
    $data[0]['refno'] = '';
    $data[0]['leasecontract'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();

    return $data;
  }


  public function loadheaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $qryselect = "
        select
          client.client,
          client.clientname,client.clientid,
          ifnull(loc.name,'') as loc,concat(date_format(client.start,'%m/%d/%Y'),' to ',date_format(client.enddate,'%m/%d/%Y')) as leasecontract,ifnull(t.authrep,'') as authrep,ifnull(t.refno,'') as refno,
          ifnull(t.trno,0) as trno,date_format(now(),'%m/%d/%Y') as dateid
        ";

    $qry = $qryselect . " from client left join loc as loc on loc.line = client.locid
        left join tacrf as t on t.clientid = client.clientid
        where client.clientid = ?";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      $stock = $this->openstock($head[0]->trno, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'reloadtableentry' => true];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];

    $data = [];

    $clientid = 0;
    $msg = "";

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if
      }
    }

    $clientid = $head['clientid'];
    $trno = $head['trno'];

    if ($trno == 0) {
      $lastseq = $this->coreFunctions->getfieldvalue('tacrf', 'seq', '', [], 'seq desc') + 1;
      $invlength = $this->companysetup->getdocumentlength($config['params']);

      if (floatval($lastseq) == 0) {
        $lastseq = 1;
      }
      $curseq = 'TACRF' . $lastseq;
      $newinvno = $this->othersClass->Padj($curseq, $invlength);
      $docno = $newinvno;
      $data['refno'] = $docno;
      $this->coreFunctions->LogConsole($docno);

      $trno = $this->coreFunctions->insertGetId($this->head, $data);
    } else {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno]);
    }


    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    return '';
  }

  public function openstock($trno, $config)
  {
    $clientid = $config['params']['clientid'];
    $qry = "select line, trno, division as dept,accountability as rem,clearedby,cleareddate,clientid,'' as bgcolor,'' as errcolor  from tacrfdet where trno=?";
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function openstockline($config)
  {
    $trno = $config['params']['trno'];
    $line = $config['params']['row']['line'];

    $qry = "select line, trno, division as dept,accountability as rem,clearedby,cleareddate,clientid,'' as bgcolor,'' as errcolor  from tacrfdet where trno=$trno and line =$line";
    $this->coreFunctions->LogConsole($qry);
    return $this->coreFunctions->opentable($qry, [$trno, $line]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $this->coreFunctions->execqry('delete from ' . $this->head . ' where clientid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where clientid=?', 'delete', [$clientid]);
    return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        $return =  $this->additem('insert', $config);

        return $return;
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'addrow':
        return $this->addrow($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function addrow($config)
  {
    $data = [];
    $clientid = $config['params']['trno'];
    $trno = $this->coreFunctions->getfieldvalue("tacrf", "trno", "clientid=?", [$clientid]);
    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['clientid'] = $clientid;
    $data['division'] = '';
    $data['dept'] = '';
    $data['rem']  = '';
    $data['clearedby'] = '';
    $data['cleareddate'] = '';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }


  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];

    if ($config['params']['line'] != 0) {
      $this->additem('update', $config);
      $data = $this->openstockline($config['params']['clientid'], $config['params']['line'], $config);
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      $data = $this->additem('update', $config);
      if ($data['status'] == true) {
        return ['row' => $data['data'], 'status' => true, 'msg' => 'Successfully saved.'];
      } else {
        return ['row' => $data['data'], 'status' => false, 'msg' => $data['msg']];
      }
    }
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function  


  // insert and update item
  public function additem($action, $config)
  {
    $clientid = $config['params']['trno'];
    $user = $config['params']['user'];
    $trno = $config['params']['data']['trno'];
    // $line = $config['params']['data']['line'];    

    $data = [
      'clientid'            => $config['params']['data']['clientid'],
      'trno'            => $config['params']['data']['trno'],
      'line'            => $config['params']['data']['line'],
      'accountability'       => $config['params']['data']['rem'],
      'division'     => $config['params']['data']['dept'],
      'clearedby'      => $config['params']['data']['clearedby'],
      'cleareddate' => $config['params']['data']['cleareddate'],
    ];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    // $data['editdate'] = $current_timestamp;
    // $data['editby'] = $config['params']['user'];
    // $subproject = $config['params']['data']['subproject'];
    if ($data['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->detail, $data);
      if ($line != 0) {
        $config['params']['line'] = $line;
        $data =  $this->openstockline($clientid, $line, $config);
        //$this->logger->sbcwritelog($trno, $config, 'SUBPROJECT', 'ADD - Line: ' . $line . ' Subproject: ' . $subproject);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $data['line']]);
    }
    return true;
  }

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];

    $stat = $this->coreFunctions->getfieldvalue("subproject", "completed", "completed <>'' and trno=?", [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=? and completed=""', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stages where trno=? and subproject not in (select line from subproject)', 'delete', [$trno]);
    if (strlen($stat) > 0) {
      $data = $this->openstock($trno, $config);
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED SUBPROJECT');
      return ['status' => true, 'msg' => 'Successfully deleted.. Some subproject already procesed.', 'inventory' => $data];
    } else {
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL SUBPROJECT');
      return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];

    if ($config['params']['row']['completed'] != '') {
      $data = $this->openstock($config['params']['trno'], $config);
      return ['status' => false, 'msg' => 'Cannot delete, already procesed.'];
    } else {
      $trno = $config['params']['trno'];
      $line = $config['params']['line'];

      $qry = "delete from " . $this->stock . " where trno=? and line=?";
      $qry2 = "delete from stages where trno=? and subproject=?";
      $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
      $this->coreFunctions->execqry($qry2, 'delete', [$trno, $line]);
      $data = $this->openstock($config['params']['trno'], $config);
      $this->logger->sbcwritelog($trno, $config, 'SUBPROJECT', 'REMOVED - Line:' . $line);
      return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    }
  }

  public function lookupsetup($config)
  {

    $lookupclass2 = $config['params']['lookupclass'];

    switch ($lookupclass2) {
      case 'lookupdept':
        return $this->lookupdept($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function lookupdept($config)
  {

    $title = 'List of Department';


    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'division' => 'clientname'
      )
    );


    $cols = [
      ['name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'salesperson', 'sortable' => true, 'style' => 'font-size:16px;']

    ];

    $qry = "select client.clientid,client.client,client.clientname 
    from client
    where isdepartment=1 and isinactive=0 order by client";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function


  // -> print function
  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter();
    // $txtdata = $this->reportparamsdata($config);

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
