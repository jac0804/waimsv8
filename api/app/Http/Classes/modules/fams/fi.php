<?php

namespace App\Http\Classes\modules\fams;

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
use App\Http\Classes\builder\helpClass;
use Exception;

class fi
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Issue Items';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  public $expirystatus = ['readonly' => false, 'show' => false];
  public $tablenum = 'transnum';
  public $head = 'issueitem';
  public $stock = 'issueitemstock';
  private $fields = ['trno', 'docno', 'dateid', 'clientid', 'locid', 'rem',  'ispermanent', 'month', 'numdays', 'requesttype', 'repairtype', 'isrepair'];
  private $except = ['trno', 'dateid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4155,
      'save' => 4158,
      'new' => 4157,
      'edit' => 4156,
      'delete' => 4159,
      'post' => 4160,
      'unpost' => 4161,
      'print' => 4205,
      'deleteitem' => 4200,
      'additem' => 4201,
      'edititem' => 4158
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listdocument', 'listclientname', 'loc', 'listdate', 'rem'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$loc]['type'] = 'label';
    if ($config['params']['companyid'] == 16) { //ati
      $cols[$loc]['label'] = 'Department';
    } else {
      $cols[$loc]['label'] = 'Location';
    }

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[$listclientname]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $cols[$loc]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $cols[$listdate]['style'] = 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px';
    $cols[$rem]['style'] = 'width: 600px;whiteSpace: normal;min-width:600px;max-width:600px';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];

    $condition = '';
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['client.clientname', 'loc.clientname', 'item.itemname', 'issueitem.rem'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $qry = "select issueitem.trno, issueitem.docno, issueitem.line, client.clientname, loc.clientname as loc, item.itemname, date(issueitem.dateid) as dateid, issueitem.rem
      from issueitem
      left join client on client.clientid=issueitem.clientid
      left join client as loc on loc.clientid=issueitem.locid
      left join item on item.itemid=issueitem.itemid
      left join transnum as num on num.trno=issueitem.trno
      where convert(issueitem.dateid,date) >= '" . $date1 . "' and convert(issueitem.dateid,date) <= '" . $date2 . "' " . $condition . " " . $filtersearch . " order by issueitem.dateid desc, issueitem.docno desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'cancel',
      'edit',
      'delete',
      'print',
      'post',
      'unpost',
      'logs',
      'backlisting',
      'toggleup',
      'toggledown',
      'others'
    );
    $buttons = $this->btnClass->create($btns);
    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  }


  public function createHeadField($config)
  {
    $fields = ['docno', 'dateid',  'client', 'whname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.label', 'Transaction Date');
    data_set($col1, 'dateid.type', 'input');
    data_set($col1, 'dateid.readonly', true);
    data_set($col1, 'dateid.class', 'sbccsreadonly');

    data_set($col1, 'type.action', 'lookuprandom');
    data_set($col1, 'type.lookupclass', 'lookup_transtype_issueitems');

    data_set($col1, 'client.lookupclass', 'employee_issueitem');
    data_set($col1, 'client.name', 'empname');
    data_set($col1, 'client.label', 'Employee');
    data_set($col1, 'client.required', true);

    data_set($col1, 'whname.type', 'input');
    data_set($col1, 'whname.label', 'Location');

    $fields = ['rem', ['numdays', 'month'], ['ispermanent', 'isrepair']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'numdays.class', 'csnumdays');
    data_set($col2, 'numdays.readonly', false);
    data_set($col2, 'numdays.required', true);
    data_set($col2, 'month.label', "# of Months");
    data_set($col2, 'rem.type', 'ctextarea');
    data_set($col2, 'rem.readonly', false);

    $fields = ['requesttype', 'repairtype'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'save');
    data_set($col3, 'refresh.label', 'Issue Item');
    data_set($col3, 'requesttype.class', 'csrequesttype sbccsreadonly');
    data_set($col3, 'repairtype.class', 'csrepairtype sbccsreadonly');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'fiitemlookup':
        return $this->fiitemlookup($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
    return [];
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    // $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Update failed.'];
    }
  } //end function


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $itemid = $this->coreFunctions->getfieldvalue($this->stock, 'itemid', 'trno=? and line=?', [$trno, $line]);
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->sbcupdate("iteminfo", ['empid' => 0, 'locid' => 0, 'issuedate' => null], ['itemid' => $itemid]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function


  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $stock = $this->openstock($trno, $config);
    foreach ($stock as $i => $value) {
      $line = $value->line;
      $itemid = $this->coreFunctions->getfieldvalue($this->stock, 'itemid', 'trno=? and line=? ', [$trno, $line]);
      $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=? and line=?', 'delete', [$trno, $line]);
      $this->coreFunctions->sbcupdate("iteminfo", ['empid' => 0, 'locid' => 0, 'issuedate' => null], ['itemid' => $itemid]);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  private function getstockselect($config)
  {
    $sqlselect = "select 
    item.itemname,
    item.barcode,
    item.itemid,
    stock.line,stock.trno,info.serialno,
    stock.returnrem,stock.rem,
     '' as bgcolor";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join iteminfo as info on info.itemid=item.itemid 
    where stock.trno =? order by line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);

    return $stock;
  } //end function


  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
   FROM $this->stock as stock
   left join item on item.itemid=stock.itemid
   left join iteminfo as info on info.itemid=item.itemid 
   where stock.trno = ? and stock.line = ? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);

    return $stock;
  } // end function

  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    if ($itemid == '') {
      $itemid = 0;
    }

    $rem = '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rem' => $rem
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line);
        $row = $this->openstockline($config);
        $head = $this->loadheaddata($config);
        $this->coreFunctions->sbcupdate(
          "iteminfo",
          ['empid' => $head['head'][0]->clientid, 'locid' => $head['head'][0]->locid, 'issuedate' => $this->othersClass->getCurrentTimeStamp()],
          ['itemid' => $data['itemid']]
        );

        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $result = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return $result;
    }
    // end function
  }


  public function fiitemlookup($config)
  {

    $trno = $config['params']['trno'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['data']['itemid'] = $value['itemid'];
      $config['params']['data']['trno'] = $trno;

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      } else {
        $insert_success = false;
      }
    }
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  }


  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['itemid'] = 0;
    $data[0]['clientid'] = 0;
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['empid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['whname'] = '';
    $data[0]['locid'] = 0;
    $data[0]['type'] = '';
    $data[0]['rem'] = '';
    $data[0]['month'] = 0;
    $data[0]['numdays'] = 0;
    $data[0]['ispermanent'] = '0';
    $data[0]['isrepair'] = '0';
    $data[0]['requesttype'] = '';
    $data[0]['repairtype'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $head = [];
    $table = $this->head;
    $tablenum = $this->tablenum;
    $qry = "select issue.docno, issue.trno, issue.line, issue.clientid, issue.locid, date(issue.dateid) as dateid, client.client as empcode, loc.clientname as whname, 
    client.clientname as empname, issue.ispermanent, issue.isrepair, issue.rem, issue.numdays, issue.month, issue.requesttype, issue.repairtype
      from issueitem as issue
      left join transnum as num on num.trno=issue.trno
      left join client on client.clientid=issue.clientid
      left join client as loc on loc.clientid=issue.locid
      where issue.trno=? and num.doc=? and num.center=?";


    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      if ($head[0]->ispermanent) {
        $head[0]->ispermanent = "1";
      } else {
        $head[0]->ispermanent = "0";
      }
      if ($head[0]->isrepair) {
        $head[0]->isrepair = "1";
      } else {
        $head[0]->isrepair = "0";
      }
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $islocked = $this->othersClass->islocked($config);
      $isposted = $this->othersClass->isposted($config);
      $hideobj = [];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $trno = $config['params']['trno'];
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['empcode'] . ' - ' . $head['empname']);
    }

    $stock = $this->openstock($trno, $config);
    foreach ($stock as $i => $value) {
      $data2 = $value->itemid;
      $this->coreFunctions->sbcupdate("iteminfo", [
        'empid' => $data['clientid'],
        'locid' => $data['locid'],
        'issuedate' => $this->othersClass->getCurrentTimeStamp()
      ], ['itemid' => $data2]);
    }
  } // end function


  public function createtabbutton($config)
  {
    $tbuttons = ['itemlookup', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $columns = ['action', 'barcode', 'itemname', 'serialno', 'rem', 'returnrem'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];


    $stockbuttons = ['delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['descriptionrow'] = ['itemname', 'partno', 'Itemname'];
    $obj[0]['inventory']['columns'][$action]['style'] = 'width: 40px;whiteSpace: normal;min-width:40px;max-width:40px';
    $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][$itemname]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][$serialno]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$rem]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][$returnrem]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][$serialno]['readonly'] = true;
    $obj[0]['inventory']['columns'][$returnrem]['readonly'] = true;
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$barcode]['action'] = 'lookupbarcode';
    $obj[0]['inventory']['columns'][$barcode]['lookupclass'] = 'gridbarcode';

    $obj[0]['inventory']['columns'][$itemname]['type'] = 'label';
    $obj[0]['inventory']['columns'][$itemname]['label'] = 'Desctription';

    if ($companyid != 16) { //ati
      $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
    $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $table . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $stock = $this->coreFunctions->datareader("select count(trno) as value from " . $this->stock . " where trno=?", [$config['params']['trno']], '', true);
    if ($stock == 0) {
      return ['status' => false, 'msg' => 'Unable to post. Please add the item(s) first.'];
    }

    $data = ['postdate' => $this->othersClass->getCurrentTimeStamp(), 'postedby' => $config['params']['user'], 'statid' => 5];
    $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
    $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
    return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];

    $returnby = $this->coreFunctions->datareader('select returnby as value from ' . $this->head . ' where trno=?', [$trno]);
    if ($returnby != '') {
      return ['status' => false, 'msg' => 'Unpost failed. Issued item has returned reference.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    $data = ['postdate' => null, 'postedby' => '', 'statid' => 0];
    $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
    $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
    return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
  } //end function


  public function reportsetup($config)
  {

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
