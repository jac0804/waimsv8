<?php

namespace App\Http\Classes\modules\ati;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class ss
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STOCK ISSUANCE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $statlogs = 'cntnum_stat';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'EX1';
  public $rowperpage = 25;

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'wh', 'contra', 'sano', 'deptid', 'empid'];
  private $except = ['trno', 'dateid'];
  private $otherfields = ['trno', 'sono', 'isconfirmed'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'forposting', 'label' => 'For Posting', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 898,
      'edit' => 899,
      'new' => 900,
      'save' => 901,
      'delete' => 903,
      'print' => 904,
      'lock' => 905,
      'unlock' => 906,
      'post' => 907,
      'unpost' => 908,
      'additem' => 909,
      'deleteitem' => 910,
      'changeamt' => 911,
      'edititem' => 912,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'prref', 'poref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $limit = "limit 150";

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and num.statid = 0  and head.lockdate is null';
        break;
      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null'; // and num.statid=19
        break;
      case 'forposting':
        $condition = ' and num.postdate is null and num.statid=39';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $viewallwh = $this->othersClass->checkAccess($config['params']['user'], 4174);
    if (!$viewallwh) {
      $defaultwh = $this->coreFunctions->getfieldvalue("client", "wh", "clientid=?", [$config['params']['adminid']]);
      if ($defaultwh != "") {
        $condition .= " and warehouse.client='" . $defaultwh . "'";
      }
    }

    $qry = "select head.trno,head.docno,head.clientname,date(head.dateid) as dateid, 'DRAFT' as status,
                   head.createby,head.editby,head.viewby,num.postedby,
                   ifnull((select group_concat(distinct ref SEPARATOR '\r\n') from " . $this->stock . " as s 
                           where s.trno=head.trno),'') as prref,
                   ifnull((select group_concat(distinct poref SEPARATOR '\r\n')
                          from lastock as s where s.trno=head.trno),'') as poref
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join trxstatus as stat on stat.line=num.statid
            left join client as warehouse on warehouse.client = head.wh
            where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
            union all
            select head.trno,head.docno,head.clientname,date(head.dateid) as dateid,'POSTED' as status,
                   head.createby,head.editby,head.viewby, num.postedby,
                   ifnull((select group_concat(distinct ref SEPARATOR '\r\n') from " . $this->hstock . " as s 
                   where s.trno=head.trno),'') as prref,
                   ifnull((select group_concat(distinct poref SEPARATOR '\r\n') from glstock as s where s.trno=head.trno),'') as poref
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join trxstatus as stat on stat.line=num.statid
            left join client as warehouse on warehouse.clientid = head.whid
            where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
            order by dateid desc, docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'post',
      'unpost',
      'lock',
      'unlock',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown',
      'help'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'department', 'dateid', 'whcode', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'department', 'dateid', 'whcode', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add item/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createTab($access, $config)
  {
    $action = 0;
    $ctrlno = 1;
    $isqty = 2;
    $isqty2 = 3;
    $uom = 4;
    $itemdesc = 5;
    $itemdesc2 = 6;
    $specs = 7;
    $specs2 = 8;
    $isamt = 9;
    $disc = 10;
    $ext = 11;
    $ref = 12;
    $rem = 13;
    $poref = 14;
    $sano = 15;
    $svsnum = 16;
    $requestorname = 17;
    $wh = 18;
    $itemname = 19;
    $barcode = 20;

    $column = ['action', 'ctrlno', 'isqty', 'isqty2', 'uom', 'itemdesc', 'itemdesc2', 'specs', 'specs2', 'isamt', 'disc', 'ext', 'ref', 'rem', 'poref', 'sano', 'svsnum', 'requestorname', 'wh', 'itemname',  'barcode'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => ['viewdistribution'],
        'rowperpage' => 25
      ],
      //'adddocument'=>['event'=>['lookupclass' => 'entrycntnumpicture','action' => 'documententry','access' => 'view']] 
    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][$isqty2]['label'] = 'Release QTY';
    $obj[0]['inventory']['columns'][$itemdesc]['label'] = 'Item name (Requestor)';

    $obj[0]['inventory']['columns'][$sano]['type'] = 'label';
    $obj[0]['inventory']['columns'][$sano]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

    $obj[0]['inventory']['columns'][$itemdesc2]['label'] = 'Item name (Stockcard)';
    $obj[0]['inventory']['columns'][$specs2]['label'] = 'Specifications (Stockcard)';
    $obj[0]['inventory']['columns'][$specs]['label'] = 'Specifications (Requestor)';

    $obj[0]['inventory']['columns'][$isamt]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';


    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingpr', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['lookupclass'] = 'pendingprdetail';
    $obj[0]['action'] = 'pendingprdetail';
    return $obj;
  }

  public function createHeadField($config)
  {
    $viewallwh = $this->othersClass->checkAccess($config['params']['user'], 4174);

    $fields = ['docno', 'client', 'clientname', 'empname'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'empname.type', 'lookup');
    data_set($col1, 'empname.lookupclass', 'employee');
    data_set($col1, 'empname.action', 'lookupclient');

    $fields = [['dateid', 'postdate'], 'wh', 'ddeptname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'wh.required', true);
    data_set($col2, 'ddeptname.label', 'Department');
    if (!$viewallwh) {
      data_set($col2, 'wh.type', 'input');
    }

    $fields = [['yourref', 'ourref'], 'rem'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['forposting'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'forposting.label', 'Confirmed');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['deptname'] = '';
    $data[0]['dept'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['empid'] = 0;
    $data[0]['empname'] = '';
    $data[0]['sano'] = 0;
    $data[0]['sadesc'] = '';
    $data[0]['sono'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);;
    $data[0]['isconfirmed'] = '0';
    $data[0]['wh'] = '';

    if ($params['adminid'] != 0) {

      $viewall = $this->othersClass->checkAccess($params['user'], 4174);
      if ($viewall) {
        $data[0]['wh'] = $this->companysetup->getwh($params);
      } else {
        $defaultwh = $this->coreFunctions->getfieldvalue("client", "wh", "clientid=?", [$params['adminid']]);
        if ($defaultwh != "") {
          $data[0]['wh'] = $defaultwh;
        }
      }
    } else {
      $data[0]['wh'] = $this->companysetup->getwh($params);
    }

    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
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

    $filterwh = "";
    $viewallwh = $this->othersClass->checkAccess($config['params']['user'], 4174);
    if (!$viewallwh) {
      $defaultwh = $this->coreFunctions->getfieldvalue("client", "wh", "clientid=?", [$config['params']['adminid']]);
      if ($defaultwh != "") {
        $filterwh .= " and warehouse.client='" . $defaultwh . "'";
      }
    }


    $center = $config['params']['center'];
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select 
         num.center,
         date(num.postdate) as postdate,
         head.trno, 
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.vattype,
         '' as dvattype,
         warehouse.client as wh,
         warehouse.clientname as whname, 
         '' as dwhname,
          head.deptid,
         ifnull(dept.client,'') as dept, ifnull(dept.clientname,'') as deptname,
         left(head.due,10) as due, 
         ifnull(sa.sano,'') as sadesc, 
         head.sano,
         ifnull(info.sono,'') as sono,
         client.groupid,
         cast(ifnull(info.isconfirmed,0) as char) as isconfirmed, num.statid,
         head.empid, ifnull(emp.clientname,'') as empname";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        left join cntnuminfo as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join coa on coa.acno=head.contra 
        left join client as emp on emp.clientid=head.empid
        where head.trno = ? and num.doc=? and num.center = ? " . $filterwh . " 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as dept on dept.clientid = head.deptid
        left join hcntnuminfo as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join coa on coa.acno=head.contra
        left join client as emp on emp.clientid=head.empid 
        where head.trno = ? and num.doc=? and num.center=? " . $filterwh;

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $hideobj = [];
      if ($isposted) {
        $hideobj['forposting'] = true;
      } else {
        $hideobj['forposting'] = false;
        switch ($head[0]->statid) {
          case 39:
            $hideobj['forposting'] = true;
            break;
        }
      }


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
    $head = $config['params']['head'];
    $companyid = $config['params']['companyid'];
    $data = [];
    $dataother = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }

    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataother[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
        } //end if
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $whid = $this->coreFunctions->datareader("select whid as value from lastock where trno = ? limit 1", [$head['trno']]);
      $whhead = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$whid]);

      if ($whhead != $head['wh']) {
        $stock = $this->coreFunctions->opentable("select line as value from lastock where trno = ?", [$head['trno']]);
        if (!empty($stock)) {
          return ['status' => false, 'msg' => "Can`t update, already have stock/s."];
        }
      }

      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->recomputecost($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataother);
    } else {
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataother, ['trno' => $head['trno']]);
    }
  } // end function



  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);

    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function




  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $checkacct = $this->othersClass->checkcoaacct(['IN1', 'EX1']);

    // if ($checkacct != '') {
    //   return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    // }

    $stock = $this->openstock($trno, $config);
    $checkcosting = $this->checkisqty2($stock);
    if ($checkcosting != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
    }

    $empid = $this->coreFunctions->getfieldvalue($this->head, "empid", "trno=?", [$trno]);
    if ($empid == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. Please input valid employee'];
    }

    $statid = $this->othersClass->getstatid($config);

    if ($statid != 39) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. Transaction is not yet confirm'];
    }

    if (!$this->createdistribution($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
    } else {
      $return = $this->othersClass->posttranstock($config);
      $this->coreFunctions->execqry("update " . $this->hstock . " as stock
          left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
          set prs.statrem='Stock Issuance - Posted',prs.statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where stock.trno=" . $trno, 'update');

      return $return;
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    stock.itemid,
    stock.trno, 
    stock.line,
    stock.refx, 
    stock.linex, 
    item.barcode, 
    item.itemname,
    stock.uom, 
    FORMAT(stock.isqty2, " . $this->companysetup->getdecimal('qty', $config['params']) . " ) as isqty2, 
    stock." . $this->hamt . ", 
    stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
    FORMAT(stock.cost * stock.iss," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    FORMAT(stock.cost," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as cost,
    stock.void, 
    round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,     
    item.brand,
    stock.rem,
    ifnull(uom.factor,1) as uomfactor,
    ifnull(info.requestorname,'') as requestorname,
    ifnull(info.itemdesc,'') as itemdesc,ifnull(item.itemname,'') as itemdesc2,
    ifnull(info.specs,'') as specs,ifnull(item.shortname,'') as specs2,
    ifnull(sa.sano,'') as sano, ifnull(svs.sano,'') as svsnum, stock.poref,
    stock.reqtrno, 
    stock.reqline,
    '' as bgcolor,
    '' as errcolor,info.ctrlno";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid 
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hprhead as prh on prh.trno=info.trno
    left join clientsano as sa on sa.line=prh.sano
    left join clientsano as svs on svs.line=prh.svsno
    left join clientsano as po on po.line=prh.pono
    where stock.tstrno=0 and stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hprhead as prh on prh.trno=info.trno
    left join clientsano as sa on sa.line=prh.sano
    left join clientsano as svs on svs.line=prh.svsno
    left join clientsano as po on po.line=prh.pono
    where stock.tstrno=0 and stock.trno =? order by line";
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
  left join model_masterfile as mm on mm.model_id = item.model
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid 
  left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
  left join hprhead as prh on prh.trno=info.trno
  left join clientsano as sa on sa.line=prh.sano
  left join clientsano as svs on svs.line=prh.svsno
  left join clientsano as po on po.line=prh.pono
  where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
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
      case 'gettrsummary':
        return $this->gettrsummary($config);
        break;
      case 'gettrdetails':
        return $this->gettrdetails($config);
        break;
      case 'getprsummary':
        return $this->getprsummary($config);
        break;
      case 'getprdetails':
        return $this->getprdetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'forposting':
        return $this->forposting($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function forposting($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }
    $result = $this->checkpostedosi($config);
    if (!$result['status']) {
      return ['status' => false, 'msg' => $result['msg']];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $config['params']['trno']])) {
      // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tag CONFIRMED');
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Tag CONFIRMED');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for CONFIRMED'];
    }
  }

  public function checkpostedosi($config)
  {
    $trno = $config['params']['trno'];
    $stock = $this->openstock($trno, $config);

    foreach ($stock as $i => $value) {
      $reqtrno1 = $value->reqtrno;
      $reqline1 = $value->reqline;

      $tagisss = $this->coreFunctions->datareader('select cat.isss as value from hprhead as pr
              left join reqcategory as cat on cat.line=pr.ourref
              where pr.trno= ? and cat.isoracle=1 and cat.isss = 1', [$reqtrno1]);

      if ($tagisss != 1) {

        $chkcat = $this->coreFunctions->datareader('select trno as value from hprhead as pr
              left join reqcategory as cat on cat.line=pr.ourref
              where pr.trno= ? and cat.isoracle=1', [$reqtrno1]);

        if (!empty($chkcat)) {
          $qry = "select docno,statid from omhead as omh
                left join omstock as stock on stock.trno=omh.trno where reqtrno = $reqtrno1 and reqline = $reqline1
                union all
                select docno,statid from homhead as omh
                left join homstock as stock on stock.trno=omh.trno  where reqtrno = $reqtrno1 and reqline = $reqline1";

          $data2 = $this->coreFunctions->opentable($qry);
          $data2 = json_decode(json_encode($data2), true);
          if (!empty($data2)) {
            foreach ($data2 as $key => $data3) {
              if ($data3['statid'] == 0) {
                $docno = $data3['docno'];
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to confirm ' . $docno . ' ' . 'is not posted.'];
              }
            }
          } else {
            return ['trno' => $trno, 'status' => false, 'msg' => 'There is an item that has no OSI referrence.'];
          }
        }
      }
    }

    return ['status' => true];
  }


  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);
    // if(!$isupdate){
    //   $data[0]->errcolor = 'bg-red-2';
    // }
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function


  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  public function quickadd($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $wh = $config['params']['wh'];
    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
      $lprice = $this->getlatestprice($config);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) {
        $item[0]['amt'] = $lprice['data'][0]['amt'];
        $item[0]['disc'] = $lprice['data'][0]['disc'];
      }

      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  // insert and update item
  public function additem($action, $config)
  {
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $expiry = $config['params']['data']['expiry'];

    $poref = isset($config['params']['data']['poref']) ? $config['params']['data']['poref'] : '';
    $uom2 = isset($config['params']['data']['uom2']) ? $config['params']['data']['uom2'] : '';
    $ctrlno = isset($config['params']['data']['ctrlno']) ? $config['params']['data']['ctrlno'] : '';

    $rem = '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    $refx = 0;
    $linex = 0;
    $ref = '';

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
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
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];
      $isqty2 = 0;
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $isqty2 = $config['params']['data']['isqty2'];
      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $isqty2 = $this->othersClass->sanitizekeyfield('isqty2', $isqty2);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";

    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    $barcode = '';
    if (!empty($item)) {
      $barcode = $item[0]->barcode;
      $isnoninv = $item[0]->isnoninv;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    } else {
      if ($uom2 != '') {
        $factor = $this->coreFunctions->getfieldvalue("uomlist", "factor", "uom=? and isconvert=1", [$uom2], '', true);
        if ($factor == 0) {
          $factor = 1;
        }
      }
    }

    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $computedata['amt'],
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'isqty2' => $isqty2,
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'reqtrno' => $refx,
      'reqline' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'rem' => $rem,
      'poref' => $poref
    ];
    $datainfo = [
      'trno' => $trno,
      'line' => $line,
      'ctrlno' => $ctrlno
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    foreach ($datainfo as $key => $value) {
      $datainfo[$key] = $this->othersClass->sanitizekeyfield($key, $datainfo[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ($barcode <> '' ? ' barcode:' . $barcode : '') . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $this->coreFunctions->sbcinsert('stockinfo', $datainfo);

        $havestock = true;
        if ($isnoninv == 0) {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ($barcode <> '' ? ' barcode:' . $barcode : '') . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          }
        }

        $row = $this->openstockline($config);
        $msg = 'Item was successfully added.';
        if (!$havestock) {
          $row[0]->errcolor = 'bg-red-2';
          $msg = 'Out of Stock.';
        }
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      $this->coreFunctions->sbcupdate('stockinfo', $datainfo, ['trno' => $trno, 'line' => $line]);
      if ($isnoninv == 0) {
        $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ($barcode <> '' ? ' barcode:' . $barcode : '') . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          $return = false;
        }
      }
      if ($refx != 0) {
        if ($this->setservedpritems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedpritems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
        }
      }

      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    //$this->createdistribution($config);
    //return ['data'=>$this->acctg]; 

    $trno = $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
      foreach ($data2 as $key => $value) {
        $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
      }
    }

    $data = $this->coreFunctions->opentable('select refx,linex,reqtrno,reqline from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);

      if ($data[$key]->reqtrno != 0) {
        $this->setservedpritems($data[$key]->reqtrno, $data[$key]->reqline);
      }
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    //if(($data[0]->qa == $data[0]->qty)){
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    if ($this->companysetup->getserial($config['params'])) {
      $this->othersClass->deleteserialout($trno, $line);
    }

    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);

    if ($data[0]->refx != 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);

      if ($data[0]->reqtrno != 0) {
        $this->setservedpritems($data[0]->reqtrno, $data[0]->reqline);
      }
    }

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' wh:' . $data[0]['wh'] . ' ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(
  		  select head.docno,head.dateid,
          stock." . $this->damt . " as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid 
          where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.rrcost <> 0
          UNION ALL
          select head.docno,head.dateid,stock." . $this->damt . " as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock." . $this->damt . " <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function



  public function gettrsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        stock.disc
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function gettrdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        stock.disc
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as 
    stock on stock.trno=head.trno where (head.doc='ST' or head.doc='SS') and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where (glhead.doc='ST' or glhead.doc='SS') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update htrstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedpritems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as 
    stock on stock.trno=head.trno where head.doc='SS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='SS' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    return $this->coreFunctions->execqry("update hprstock set qa=" . $qty . ",statrem='Stock Issuance - Draft',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $refx . " and line=" . $linex, 'update');
  }


  public function getprsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, ifnull(item.itemid,0) as itemid,stock.trno, 
        stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,
        (stock.qty-(stock.qa)) as qty,stock.rrcost,
        round((stock.qty-(stock.qa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, stock.disc, stockinfo.itemdesc, stockinfo.unit, stockinfo.purpose, 
        stockinfo.requestorname, stockinfo.specs, stockinfo.dateneeded, stockinfo.ctrlno, head.deptid, head.sano, headinfo.proformainvoice as sono, client.clientid, head.ourref
        FROM hprhead as head 
        left join hprstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
        left join hheadinfotrans as headinfo on headinfo.trno=stockinfo.trno
        left join client on client.client=head.client
        where stock.trno = ? and stock.qty>(stock.qa+stock.voidqty)";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['rrqty2'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['reqtrno'] = $data[$key2]->trno;
          $config['params']['data']['reqline'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {

            if ($this->setservedpritems($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedpritems($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }

            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function


  public function getprdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];

    $msg = '';

    //2023.09.25 temp remove
    $filteruser = '';
    // $admin = $this->othersClass->checkAccess($config['params']['user'], 3767);
    // if (!$admin) {
    //   $filteruser = " and (stock.suppid=0 or (stock.status=0 and stock.suppid=" . $config['params']['adminid'] . "))";
    // }

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, ifnull(item.itemid,0) as itemid,stock.trno, 
        stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,
        (stock.qty-(stock.qa+stock.voidqty)) as qty,stock.rrcost,
        round((stock.qty-(stock.qa+stock.voidqty))/ case when stock.itemid=0 then ifnull(uom3.factor,1) when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2
        ) as rrqty,stock.disc,uom3.factor, stockinfo.itemdesc, stockinfo.unit, stockinfo.purpose, stockinfo.ctrlno,
        stockinfo.requestorname, stockinfo.specs, stockinfo.dateneeded, head.deptid, head.sano, headinfo.proformainvoice as sono, client.clientid, head.ourref,
        (select group_concat(yourref) from hpohead as h left join hpostock as s on s.trno=h.trno where s.reqtrno=stock.trno and s.reqline=stock.line) as pono
        FROM hprhead as head 
        left join hprstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
        left join hheadinfotrans as headinfo on headinfo.trno=stockinfo.trno
        left join client on client.client=head.client
        left join uomlist as uom3 on uom3.uom=stockinfo.uom3 and uom3.isconvert=1
        where stock.trno = ? and stock.line = ? and stock.qty>(stock.qa+stock.voidqty) " . $filteruser;

      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {

        $this->coreFunctions->execqry("update lahead set deptid=" . $data[0]->deptid . " where trno=" . $trno . " and deptid=0");
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['rrqty2'] = $data[$key2]->rrqty;
          // $config['params']['data']['isqty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['reqtrno'] = $data[$key2]->trno;
          $config['params']['data']['reqline'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['poref'] = $data[$key2]->pono;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedpritems($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedpritems($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          } else {
            $msg .= "  " . $return['msg'];
          }
        } // end foreach
      } //end if
    } //end foreach

    if ($msg == '') {
      $msg = 'Items were successfully added.';
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true, 'trno' => $trno];
  } //end function

  // private function setserveditemsPR($refx, $linex, $qtyfield)
  // {
  //   $qry1 = "select stock." . $qtyfield . " from " . $this->head . " as head left join " . $this->stock . " as
  //   stock on stock.trno=head.trno where head.doc='SS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

  //   $qry1 = $qry1 . " union all select stock." . $qtyfield . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
  //   stock.trno where head.doc='SS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

  //   $qry2 = "select ifnull(sum(" . $qtyfield . "),0) as value from (" . $qry1 . ") as t";
  //   $qty = $this->coreFunctions->datareader($qry2);
  //   if ($qty === '') {
  //     $qty = 0;
  //   }
  //   $result = $this->coreFunctions->execqry("update hprstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  //   return $result;
  // } //end function

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.expense,"") as expense,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost   
            from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid where head.trno=?';
    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $expacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['EX1']);
      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
        if ($vat !== 0) {
          $tax = round(($stock[$key]->ext / $tax1) * $tax2, 2);
        }
        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'expense' => $stock[$key]->expense !== '' ? $stock[$key]->expense : $expacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->isqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => round($stock[$key]->cost * $stock[$key]->iss, 2),
          'fcost' => round($stock[$key]->fcost * $stock[$key]->iss, 2)
        ];
        $this->distribution($params, $config);
      }
    }
    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
        $this->acctg[$key]['editdate'] = $current_timestamp;
        $this->acctg[$key]['editby'] = $config['params']['user'];
        $this->acctg[$key]['encodeddate'] = $current_timestamp;
        $this->acctg[$key]['encodedby'] = $config['params']['user'];
        $this->acctg[$key]['trno'] = $config['params']['trno'];
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {
    //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }

    //INV
    if ($params['cost'] != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      //exp
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['expense']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  //
  private function checkisqty2($stock)
  {
    $msg = '';

    foreach ($stock as $i => $value) {
      if (floatval($value->isqty2) <> 0) {
        $msg = "Release Qty has value";
      }
    }
    return $msg;
  } //end function

  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter($config);
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
    $this->logger->sbcviewreportlog($config);
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  // public function reportsetup($config)
  // {
  //   $txtfield = $this->createreportfilter();
  //   $txtdata = $this->reportparamsdata($config);
  //   $modulename = $this->modulename;
  //   $data = [];
  //   $style = 'width:500px;max-width:500px;';
  //   return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  // }

  // public function reportdata($config)
  // {
  //   $this->logger->sbcviewreportlog($config);
  //   $data = $this->report_default_query($config['params']['dataid']);
  //   $str = $this->reportplotting($config, $data);
  //   return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  // }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'default' as print,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  private function report_default_query($trno)
  {

    $query = "select head.docno,head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty, stock.isamt, stock.isqty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno 
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno'
        union all
        select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty, stock.isamt, stock.isqty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model
        from (glhead as head 
        left join glstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno'
        order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('STOCK ISSUANCE', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT : ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('DISC', '50px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '30px', '8px');

    return $str;
  }

  public function reportplotting($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $ext = number_format($data[$i]['ext'], $decimal);
      if ($ext < 1) {
        $ext = '-';
      }
      $netamt = number_format($data[$i]['netamt'], $decimal);
      if ($netamt < 1) {
        $netamt = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['isqty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '400px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['isamt'], '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ext, '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $computedata = $this->othersClass->computestock($data2[$key][$this->damt], $data[$key]->disc, $data2[$key][$this->dqty], $data[$key]->uomfactor, $head['tax']);
      $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
