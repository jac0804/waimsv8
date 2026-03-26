<?php

namespace App\Http\Classes\modules\cdo;

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

class st
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STOCK TRANSFER';
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
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'IS1';

  private $fields = ['trno', 'docno', 'dateid', 'due', 'deptid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'wh'];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
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
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 882,
      'edit' => 883,
      'new' => 884,
      'save' => 885,
      // 'change' => 886, remove change doc
      'delete' => 887,
      'print' => 888,
      'lock' => 889,
      'unlock' => 890,
      'post' => 891,
      'unpost' => 892,
      'additem' => 893,
      'deleteitem' => 894,
      'changeamt' => 895,
      'edititem' => 896,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate',  'listclientname', 'rem', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }


    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$listclientname]['label'] = 'Destination Warehouse';

    $cols = $this->tabClass->delcollisting($cols);
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
      $searchfield = ['head.docno', 'head.clientname', 'head.rem', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby,head.rem
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby,head.rem
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
     order by dateid desc, docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $isshortcutdr = $this->companysetup->getisshortcutdr($config['params']);

    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
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
      'help',
      'others'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'dept', 'dateid', 'warehouse', 'yourref', 'whcode', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'dept', 'dateid', 'warehouse', 'yourref', 'whcode', 'csrem', 'btnsave']);
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
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createTab($access, $config)
  {
    $isexpiry = $this->companysetup->getisexpiry($config['params']);

    $action = 0;
    $isqty = 1;
    $uom = 2;
    $serial = 3;
    $pnpcsr = 4;
    $isamt = 5;
    $ext = 6;
    $wh = 7;
    $ref = 8;
    $rem = 9;
    $loc = 10;
    $expiry = 11;
    $itemname = 12;
    $barcode = 13;
    $column = ['action', 'isqty', 'uom', 'serialno', 'pnp', 'isamt', 'ext', 'wh', 'ref', 'rem', 'loc', 'expiry', 'itemname', 'barcode'];


    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => ['viewdistribution', 'viewref']
      ],
    ];

    $stockbuttons = ['save', 'delete', 'showbalance', 'serialout'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Cost';
    $obj[0]['inventory']['columns'][$isamt]['type'] = 'label';

    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$serial]['type'] = 'input';
    $obj[0]['inventory']['columns'][$serial]['readonly'] = true;
    $obj[0]['inventory']['columns'][$serial]['label'] = 'Engine/Chassis#';
    $obj[0]['inventory']['columns'][$serial]['type'] = 'textarea';
    $obj[0]['inventory']['columns'][$serial]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
    $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'textarea';
    $obj[0]['inventory']['columns'][$pnpcsr]['readonly'] = true;
    $obj[0]['inventory']['columns'][$pnpcsr]['label'] = 'PNP/CSR#';
    $obj[0]['inventory']['columns'][$pnpcsr]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
    $obj[0]['inventory']['columns'][$wh]['type'] = 'label';
    $obj[0]['inventory']['columns'][$ref]['type'] = 'label';
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  } //end function


  public function createtabbutton($config)
  {
    $tbuttons = ['pendingtr', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4852);
    $fields = ['docno', 'dept', 'clientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dept.label', 'Destination Branch');
    data_set($col1, 'dept.lookupclass', 'stbranch');
    data_set($col1, 'dept.action', 'lookupcenter');
    data_set($col1, 'dept.condition', ['checkstock']);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'clientname.label', 'Branch Name');
    data_set($col1, 'clientname.type', 'input');
    data_set($col1, 'clientname.class', 'sbccsreadonly');

    $fields = ['dateid', 'client', 'wh2'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'client.label', 'Destination Code');
    data_set($col2, 'client.type', 'input');
    data_set($col2, 'client.readonly', true);
    data_set($col2, 'wh2.label', 'Destination Warehouse');
    data_set($col2, 'wh2.type', 'input');
    data_set($col2, 'wh2.readonly', true);

    if ($noeditdate) {
      data_set($col2, 'dateid.class', 'sbccsreadonly');
    }

    $fields = [['yourref', 'ourref'], 'wh', 'whname'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'wh.label', 'Source WH Code');
    data_set($col3, 'wh.type', 'input');
    data_set($col3, 'wh.readonly', true);
    data_set($col3, 'whname.label', 'Source Warehouse');
    data_set($col3, 'whname.readonly', true);

    $fields = ['rem'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['dept'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['wh2'] = '';
    $data[0]['trnxtype'] = '';
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
    $center = $config['params']['center'];
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         head.deptid,
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
         left(head.due,10) as due, 
         client.groupid,d.code as dept,client.clientname as wh2  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join center as d on d.line = head.deptid
        left join coa on coa.acno=head.contra
        left join cntnuminfo as info on info.trno=head.trno
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join center as d on d.line = head.deptid
        left join coa on coa.acno=head.contra 
        left join hcntnuminfo as info on info.trno=head.trno
        where head.trno = ? and num.doc=? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
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
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->recomputecost($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function




  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $checkacct = $this->othersClass->checkcoaacct(['IN1']);
    $headwh = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $defwh = $this->companysetup->getwh($config['params']);

    if ($headwh != $defwh) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. This transaction can only be posted by the receiver of the destination warehouse.'];
    }

    if (!$this->othersClass->checkserialout($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }

    if ($checkacct != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    }

    $stock = $this->openstock($trno, $config);
    $checkcosting = $this->othersClass->checkcosting($stock);
    if ($checkcosting != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
    }

    $return = $this->createdistribution($config);
    if (!$return['status']) {
      if ($return['msg'] == '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        return ['trno' => $trno, 'status' => false, 'msg' => $return['msg']];
      }
    } else {
      return $this->othersClass->posttranstock($config);
    }
  } //end function

  public function getstsummaryqry($config)
  {
    return 'select head.docno,head.dateid,client.client,sum(stock.ext) as ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
    client.ass as destass,client.rev as destrev,client.clientname,wh.clientname as sourcewh,
    wh.ass as sourceass,wh.rev as sourcerev,cat.name as category,d.code as center
    from ' . $this->hhead . ' as head left join ' . $this->hstock . ' as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid left join itemcategory as cat on cat.line = item.category 
    left join client on client.clientid = head.clientid left join client as wh on wh.clientid = stock.whid
    left join center as d on d.line = head.deptid where head.trno=? and stock.tstrno =0 group by head.dateid,client.client,wh.client,item.asset,item.revenue,
    client.ass,client.rev ,client.clientname,head.docno,
    wh.ass,wh.rev ,cat.name ,d.code,wh.clientname';
  }

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
    stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.ass as destass,client.rev as destrev,
    stock.rebate,head.subproject,stock.stageid,head.projectto,head.subprojectto ,wh.ass as sourceass,wh.rev as sourcerev,cat.name as category
    from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid left join itemcategory as cat on cat.line = item.category 
    left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    $sourceacct = '';
    $destacct = '';

    if (!empty($stock)) {
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
          $tax = round(($stock[$key]->ext / $tax1), 2);
          $tax = round($stock[$key]->ext - $tax, 2);
        }

        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
        $sourceacct = $stock[$key]->sourceass;
        $destacct = $stock[$key]->destass;

        if (strtoupper($stock[$key]->category) != "MC UNIT") {
          $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN2']);
          $sourceacct = $stock[$key]->sourcerev;
          $destacct = $stock[$key]->destrev;
        }

        if ($sourceacct == '' || $destacct == '') {
          return ['status' => false, 'msg' => 'Warehouse accounts not yet setup.'];
        }

        $this->coreFunctions->LogConsole(strtoupper($stock[$key]->category));

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => round($stock[$key]->ext, 2),
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $invacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->isqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => round($stock[$key]->cost * $stock[$key]->iss, 2),
          'fcost' => round($stock[$key]->fcost * $stock[$key]->iss, 2),
          'projectid' => $stock[$key]->projectid,
          'subproject' => $stock[$key]->subproject,
          'projectto' => $stock[$key]->projectto,
          'subprojectto' => $stock[$key]->subprojectto,
          'stage' => $stock[$key]->stageid,
          'sourceacct' => $sourceacct,
          'destacct' => $destacct

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
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    return ['status' => true, 'msg' => ''];
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

    if (floatval($params['ext']) <> floatval($params['cost'])) {
      $sales = floatval($params['ext']) - floatval($params['cost']);
    }


    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['destacct']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => ($params['ext'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ext'], 'fcr' => 0, 'projectid' => $params['projectto'], 'subproject' => $params['subprojectto'], 'stageid' => $params['stage']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'] * $forex, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['ext'], 'fdb' => 0, 'projectid' => $params['projectid'], 'subproject' => $params['subproject']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if ($sales != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA5'");
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid'], 'subproject' => $params['subproject']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    stock.refx, 
    stock.linex, 
    item.barcode, 
    item.itemname,
    stock.uom, 
    stock." . $this->hamt . ", 
    stock." . $this->hqty . " as qty,
    stock.iss,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    case stock.isqty2 when 0 then FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ") else FORMAT(stock.isqty2," . $this->companysetup->getdecimal('qty', $config['params']) . ") end   as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,stock.cost,
    stock.disc, 
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
    '' as bgcolor,
    '' as errcolor,
    ifnull(group_concat(concat('Engine/Chassis#: ',rr.serial,'/',rr.chassis,'\\n','Color: ',rr.color) separator '\\n\\r'),'') as serialno,
    ifnull(group_concat(concat('PNP#: ',rr.pnp,' / CSR#: ',rr.csr) separator '\\n\\r'),'') as pnp,item.isserial,stock.isqty2 ";
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
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
     where stock.tstrno=0 and stock.trno =? 
     group by item.brand,mm.model_name,item.itemid,stock.trno, stock.line,stock.refx, stock.linex, item.barcode, item.itemname,
     stock.uom, stock." . $this->hamt . ",stock." . $this->hqty . " , stock.iss,stock." . $this->damt . ",stock." . $this->dqty . ",
     stock.ext, stock.encodeddate,stock.cost,
     stock.disc,stock.void,stock.qa,stock.ref,stock.whid, warehouse.client,warehouse.clientname,stock.loc, stock.expiry,stock.rem,uom.factor,stock.isqty2,item.isserial
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
     where stock.tstrno=0 and stock.trno =? 
     group by item.brand,mm.model_name,item.itemid,stock.trno, stock.line,stock.refx, stock.linex, item.barcode, item.itemname,
     stock.uom, stock." . $this->hamt . ",stock." . $this->hqty . " , stock.iss,stock." . $this->damt . ",stock." . $this->dqty . ",
     stock.ext,stock.encodeddate,stock.cost, stock.disc,stock.void,stock.qa,stock.ref,stock.whid, warehouse.client,warehouse.clientname,
     stock.loc, stock.expiry,stock.rem,uom.factor,stock.isqty2,item.isserial order by line";

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
  left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
  where stock.trno = ? and stock.line = ? 
  group by item.brand,mm.model_name,item.itemid,stock.trno, stock.line,stock.refx, stock.linex, item.barcode, item.itemname,
  stock.uom, stock." . $this->hamt . ",stock." . $this->hqty . " , stock.iss,stock." . $this->damt . ",stock." . $this->dqty . ",
  stock.ext, stock.encodeddate,stock.cost,stock.disc,stock.void, stock.qa,stock.ref,stock.whid, warehouse.client,warehouse.clientname,stock.loc, stock.expiry,
  stock.rem,uom.factor,stock.isqty2,item.isserial";
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
      case 'getlookupserial':
        return $this->getserialout($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    $msg = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0  && $data[$key]->isserial != 1) {
          $msg1 = ' Out of stock ';
        } elseif ($data[$key]->isserial == 1 && $data[$key]->refx == 0) {
          $msg1 = ' Out of stock, please select sufficient engine #. ';
        } elseif ($data[$key]->isserial == 1 && $data[$key]->refx != 0) {
          $msg1 = ' Qty Received is Greater than Request Qty , please select sufficient engine #. ';
        } else {
          $msg2 = ' Qty Received is Greater than Request Qty ';
        }
      }
    }

    if ($msg2 == "") {
      $msg = $msg1;
    } else {
      $msg = $msg1 . '/' . $msg2;
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg];
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
    $msg = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0 && $data[$key]->isserial != 1) {
          $msg1 = ' Out of stock ';
        } elseif ($data[$key]->isserial == 1 && $data[$key]->refx == 0) {
          $msg1 = ' Out of stock, please select sufficient engine #. ';
        } elseif ($data[$key]->isserial == 1 && $data[$key]->refx != 0) {
          $msg1 = ' Qty Received is Greater than Request Qty , please select sufficient engine #. ';
        } else {
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
      }
    }

    if ($msg2 == "") {
      $msg = $msg1;
    } else {
      $msg = $msg1 . '/' . $msg2;
    }

    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg . ')'];
    }
  } //end function


  public function addallitem($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);

    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;

      additemdefaulthere:
      $res = $this->additem('insert', $config);
      if ($res['status']) {
        if ($res['msg'] != '') {
          $msg .= $res['msg'] . " " . $config['params']['data']['itemname'] . "<br/>";
        }
      }
    }

    if ($msg == '') {
      $msg = 'Successfully saved.';
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function


  public function insertfifoexpiration($config, $value, $wh, $setlog = false)
  {
    $trno = $config['trno'];
    $return_row = [];

    $sql = "select rrstatus.expiry,rrstatus.loc,rrstatus.whid,ifnull(sum(rrstatus.bal),0) as bal from rrstatus
        left join item on item.itemid = rrstatus.itemid left join client on client.clientid=rrstatus.whid
        where rrstatus.itemid = " . $value['data']['itemid'] . " and client.client = '" . $wh . "' and rrstatus.bal <> 0 
        group by rrstatus.expiry,rrstatus.loc,rrstatus.whid order by rrstatus.expiry,rrstatus.loc,rrstatus.whid asc";

    $invdata = $this->coreFunctions->opentable($sql);

    $running_qty = $value->isqty;
    $qty = 0;

    foreach ($invdata as $key => $val) {

      $expiry  = $val->expiry;
      $loc = $val->loc;

      if ($running_qty > 0) {
        if ($running_qty > $val->bal) {
          $qty = $val->bal;
        } else {
          $qty = $running_qty;
        }

        inserthere:
        $running_qty = $running_qty - $qty;

        $config['params']['data']['uom'] = $value['data']['uom'];
        $config['params']['data']['itemid'] = $value['data']['itemid'];
        $config['params']['trno'] = $trno;
        $config['params']['data']['qty'] = $qty;
        $config['params']['data']['wh'] = $wh;
        $config['params']['data']['loc'] = $loc;
        $config['params']['data']['expiry'] = $expiry;
        $return = $this->additem('insert', $config, $setlog);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          array_push($return_row, $return['row'][0]);
        }
      }

      $this->coreFunctions->LogConsole('key: ' . $key . ' - count: ' . count($invdata) . ' - bal:' . $running_qty);

      if ($key >= (count($invdata) - 1)) {
        if ($running_qty > 0) {
          $qty = $running_qty;
          $expiry  = '';
          $loc = '';
          goto inserthere;
        }
        break;
      }
    } //end foreach

    return $return_row;
  }


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
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];

      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isserial from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isserial = 0;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) {
        $factor = $item[0]->factor;
        $isserial = $item[0]->isserial;
      }
    }

    $isqty2 = $qty;

    if ($isserial == 1 && $action == 'insert') {
      $qty = 0;
    }

    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
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
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'rem' => $rem,
      'isqty2' => $isqty2
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $cost2 = 0;

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $havestock = true;
        $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        if ($cost != -1) {
          $cost2 = $cost / $factor;
          $computedata = $this->othersClass->computestock($cost2, $disc, $qty, $factor, $vat);
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'isamt' => $cost2, 'amt' => $computedata['amt'], 'ext' => $computedata['ext']], ['trno' => $trno, 'line' => $line]); //amt is also the cost
        } else {
          $havestock = false;
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
        }
        $row = $this->openstockline($config);
        $msg = 'Item was successfully added.';
        if ($isserial == 1) {
          $msg = 'Item was successfully added. Please enter Engine #';
        }
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

      if ($isserial == 0) {
        $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        if ($cost == -1) {
          $msg = 'Out of Stock';
        }
      } else {
        $rrref = '';
        $soutline = $this->coreFunctions->datareader("select group_concat(sline separator ',') as value from serialout as sj  where sj.trno = " . $trno . " and sj.line =" . $line);
        if ($soutline != '') {
          $rrref = $this->coreFunctions->datareader("select group_concat(sline separator ',') as value from serialin as sj  where sj.outline in (" . $soutline . ")");
        }

        $this->coreFunctions->LogConsole('else serialize ' . $rrref);
        if ($rrref != '') {
          $cost = $this->othersClass->computecostingserial($data['itemid'], $data['whid'], $trno, $line, $data['iss'], $config['params']['doc'], '', $rrref, $loc);
          if ($cost == -1) {
            $msg = "Please select sufficient Engine #";
          }
        } else {
          $cost = -1;
          if ($refx == 0) {
            $this->coreFunctions->sbcupdate($this->stock, ['isqty2' => $qty], ['trno' => $trno, 'line' => $line]);
          }
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'ENTER SERIAL', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $rrref = $this->coreFunctions->datareader("select group_concat(sline separator ',') as value from serialout as sj  where sj.trno = " . $trno . " and sj.line =" . $line);
          $this->coreFunctions->execqry('delete from serialout where trno=? and line=?', 'delete', [$trno, $line]);
          if ($rrref != '') {
            $this->coreFunctions->execqry("update serialin set outline =0 where outline in (" . $rrref . ")", 'update');
          }
          $this->setserveditems($refx, $linex);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'NO SERIAL - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          $msg = "(" . $item[0]->barcode . ") Please select Engine#!!!";
          $return = false;
        }
      }

      if ($cost != -1) {
        $cost2 = $cost / $factor;
        $computedata = $this->othersClass->computestock($cost2, $disc, $qty, $factor, $vat);
        $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'isamt' => $cost2, 'amt' => $computedata['amt'], 'ext' => $computedata['ext'], 'isqty2' => 0], ['trno' => $trno, 'line' => $line]); //amt is also the cost

        if ($this->setserveditems($refx, $linex) === 0) {
          $this->coreFunctions->sbcupdate($this->stock, ['isqty2' => $this->dqty], ['trno' => $trno, 'line' => $line]);
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $rrref = $this->coreFunctions->datareader("select group_concat(sline separator ',') as value from serialout as sj  where sj.trno = " . $trno . " and sj.line =" . $line);
          $this->coreFunctions->execqry('delete from serialout where trno=? and line=?', 'delete', [$trno, $line]);
          if ($rrref != '') {
            $this->coreFunctions->execqry("update serialin set outline =0 where outline in (" . $rrref . ")", 'update');
          }
          $this->setserveditems($refx, $linex);
          $return = false;
        }
      } else {
        if ($refx == 0) {
          $this->coreFunctions->sbcupdate($this->stock, ['isqty2' => $qty], ['trno' => $trno, 'line' => $line]);
        }

        $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
        $rrref = $this->coreFunctions->datareader("select group_concat(sline separator ',') as value from serialout as sj  where sj.trno = " . $trno . " and sj.line =" . $line);
        $this->coreFunctions->execqry('delete from serialout where trno=? and line=?', 'delete', [$trno, $line]);
        if ($rrref != '') {
          $this->coreFunctions->execqry("update serialin set outline =0 where outline in (" . $rrref . ")", 'update');
        }
        $this->setserveditems($refx, $linex);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', $msg . ' - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
        $return = false;
      }

      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
      foreach ($data2 as $key => $value) {
        $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
      }
    }

    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

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

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    if ($this->companysetup->getserial($config['params'])) {
      $this->othersClass->deleteserialout($trno, $line);
    }

    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
    if ($data[0]->refx != 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' wh:' . $data[0]['wh'] . ' ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    return $this->othersClass->getlatestcostTS($config, $barcode, $client, $center, $trno);
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

  private function getserialout($config)
  {
    $qty = 0;
    $eline = '';
    $return = true;
    $msg = 'Engine Successfully added.';
    if (!empty($config['params']['rows'])) {
      $trno = $config['params']['rows'][0]['trno'];
      $line = $config['params']['rows'][0]['line'];
      $row = $config['params']['rows'];
      $doc = $config['params']['doc'];
      $config['params']['line'] = $line;

      //$item = $this->coreFunctions->opentable("select s.itemid, s.whid, s.loc, s.expiry,uom.factor as uomfactor from ".$this->stock."  as s left join uom on uom.itemid = s.itemid and uom.uom = s.uom where s.trno=? and s.line=?", [$trno, $line]);
      $item  = $this->openstockline($config);
      $dinsert = [];
      if (!empty($item)) {
        foreach ($config['params']['rows'] as $row) {
          $dinsert['trno'] = $trno;
          $dinsert['line'] = $line;
          $dinsert['serial'] = $row['serial'];
          $dinsert['chassis'] = $row['chassis'];
          $dinsert['color'] = $row['color'];
          $dinsert['pnp'] = $row['pnp'];
          $dinsert['csr'] = $row['csr'];
          $outline = $this->coreFunctions->insertGetId('serialout', $dinsert);
          if ($outline != 0) {
            $this->coreFunctions->execqry("update serialin set outline=? where sline=? and outline=0", 'update', [$outline, $row['sline']]);
            $this->coreFunctions->execqry("update " . $this->stock . " set color=? where trno=? and line=?", 'update', [$row['color'], $trno, $line]);
          }

          if ($eline == '') {
            $eline = $row['sline'];
          } else {
            $eline = $eline . ',' . $row['sline'];
          }
          $qty += 1;
        }
      }

      //computecosting
      $this->coreFunctions->LogConsole(($eline));
      if ($eline <> '') {
        //$rrref = $this->coreFunctions->getfieldvalue("serialin","concat(trno,'~',line)","sline = ?",[$sline]);
        $cost = $this->othersClass->computecostingserial($item[0]->itemid, $item[0]->whid, $trno, $line, $qty, $doc, '', $eline, $item[0]->loc);
        if ($cost != -1) {
          $cost2 = $cost / $item[0]->uomfactor;
          $damt = $this->othersClass->sanitizekeyfield('amt', $cost2);
          $dqty = $this->othersClass->sanitizekeyfield('amt', $qty);
          $computedata = $this->othersClass->computestock($damt, '', $dqty, $item[0]->uomfactor);
          $cost2 = $cost / $item[0]->uomfactor;
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => $dqty, $this->hqty => $computedata['qty'], 'cost' => $cost, 'amt' => $cost, 'isamt' => $cost2, 'ext' => $computedata['ext'], 'isqty2' => 0], ['trno' => $trno, 'line' => $line]);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD ENDGINE# - Line:' . $line . ' barcode:' . $item[0]->barcode, $this->tablelogs);
          if (!$this->setserveditems($item[0]->refx, $item[0]->linex)) {
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $serialout = $this->coreFunctions->getfieldvalue("serialout", "group_concat(sline separator ',')", "trno = ? and line =?", [$trno, $line]);
            $this->coreFunctions->execqry('delete from serialout where trno=? and line=?', 'delete', [$trno, $line]);
            $this->coreFunctions->execqry("update serialin set outline = 0  where outline in (" . $serialout . ")", 'update');
            $this->setserveditems($item[0]->refx, $item[0]->linex);
            $return = false;
            $msg = 'Qty greater that requested qty.';
          }
        } else {
          $this->coreFunctions->sbcupdate($this->stock, ['isqty2' => $this->dqty], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $serialout = $this->coreFunctions->getfieldvalue("serialout", "group_concat(sline separator ',')", "trno = ? and line =?", [$trno, $line]);
          $this->coreFunctions->execqry('delete from serialout where trno=? and line=?', 'delete', [$trno, $line]);
          $this->coreFunctions->execqry("update serialin set outline = 0  where outline in (" . $serialout . ")", 'update');
          $this->setserveditems($item[0]->refx, $item[0]->linex);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode, $this->tablelogs);
          $return = false;
          $msg = 'Out of Stock';
        }
      }

      $qry = "select sline, trno, line, rftrno, rfline, chassis, color, pnp, csr, serial, '' as bgcolor from serialout where trno=? and line=? order by sline";
      $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
      $stock = $this->openstock($trno, $config);
      return ['status' => $return, 'msg' => $msg, 'tableentrydata' => $data, 'reloadtableentry' => true, 'reloadhead' => true];
    }
  }

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

    $dataparams = $config['params']['dataparams'];
    if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));

      $uomfactor = $this->othersClass->sanitizekeyfield('amt', $data[$key]->uomfactor);

      $computedata = $this->othersClass->computestock(
        $damt,
        $data[$key]->disc,
        round($dqty, $this->companysetup->getdecimal('qty', $config['params'])),
        $uomfactor,
        $head['tax']
      );
      $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
