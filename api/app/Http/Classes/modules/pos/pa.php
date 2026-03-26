<?php

namespace App\Http\Classes\modules\pos;

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
use Exception;

class pa
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PRICE SCHEME';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pahead';
  public $hhead = 'hpahead';
  public $stock = 'pastock';
  public $hstock = 'hpastock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'dateid', 'due', 'yourref', 'ourref', 'rem',  'branchid', 'isall'];
  public $except = ['trno', 'dateid', 'due'];
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
      'view' => 2319,
      'edit' => 2320,
      'new' => 2321,
      'save' => 2322,
      'delete' => 2324,
      'print' => 2325,
      'lock' => 2326,
      'unlock' => 2327,
      'post' => 2328,
      'unpost' => 2329,
      'additem' => 2330,
      'edititem' => 2331,
      'deleteitem' => 2332,
      'void' => 3621
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'branch', 'startdate', 'enddate', 'rem', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[3]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
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
      $searchfield = ['head.docno', 'head.rem', 'branch.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
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
    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,left(head.dateid,10) as startdate, left(head.due,10) as enddate, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, branch.clientname as branch, head.rem
            from " . $this->head . " as head left join " . $this->tablenum . " as num 
            on num.trno=head.trno left join client as branch on branch.clientid = head.branchid
            where head.doc=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
            union all
            select head.trno,head.docno,left(head.dateid,10) as dateid,left(head.dateid,10) as startdate, left(head.due,10) as enddate,'POSTED' as status,
            head.createby,head.editby,head.viewby, num.postedby , branch.clientname as branch, head.rem
            from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
            on num.trno=head.trno left join client as branch on branch.clientid = head.branchid
            where head.doc=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
            order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $date1, $date2, $doc, $date1, $date2]);
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
      'help',
      'others'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
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
    if ($config['params']['companyid'] == 56) { // homeworks
      $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
      $buttons['others']['items']['downloadexcel'] = ['label' => 'Download Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
    }
    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($companyid == 56) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryppbranch', 'label' => 'Branch Lists']];
      $minmax = $this->tabClass->createtab($tab, []);
      $return['Branch Lists'] = ['icon' => 'fa fa-code-branch', 'tab' => $minmax];
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $column = ['action', 'barcode', 'itemname', 'uom', 'isamt', 'disc', 'ext', 'rem'];

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $headgridbtns = [];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],
      //'adddocument' => ['event' => ['lookupclass' => 'entrytransnumpicture', 'action' => 'documententry', 'access' => 'view']]
    ];

    if ($config['params']['companyid'] == 56) { //homeworks
      $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'entryppbranch', 'label' => 'BRANCH LIST'];
    }


    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Description";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "Item Code";
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$uom]['type'] = "label";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function createHeadField($config)
  {
    $fields = ['docno', ['dateid', 'due']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.label', 'Start Date');
    data_set($col1, 'due.label', 'End Date');
    data_set($col1, 'due.type', 'input');
    data_set($col1, 'due.readonly', false);

    data_set($col1, 'dateid.type', 'input');
    data_set($col1, 'dateid.readonly', false);


    $fields = ['yourref', 'ourref', 'isall'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'rem.required', false);

    $fields = ['voidtrans'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'voidtrans.access', 'void');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $date = $this->othersClass->getCurrentDate();
    $data[0]['dateid'] = date('m/d/Y', strtotime($date));
    $data[0]['due'] = date('m/d/Y', strtotime($date));
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['isall'] = '0';
    // $data[0]['wh'] = $this->companysetup->getwh($params);
    // $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    // $whid = $this->coreFunctions->datareader("select clientid as value from client where client='" . $data[0]['wh'] . "'");
    // $data[0]['whname'] = $name;
    // $data[0]['whid'] = $whid;
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branchid'] = 0;

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
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
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         head.yourref,
         head.ourref,
         date_format(left(head.dateid,10),'%m/%d/%Y') as dateid,
         date_format(left(head.due,10),'%m/%d/%Y') as due,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.whid,warehouse.client as wh,warehouse.clientname as whname,'' as dwhname, 
         head.branchid,branch.client as branchcode,branch.clientname as branchname,'' as dbranchname,
         (case when head.isall=1 then '1' else '0' end) as isall, head.voiddate";

    $qry = $qryselect . ", 1 as ended 
        from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as branch on branch.clientid = head.branchid
        where head.trno = ? 
        union all " . $qryselect . ", if(head.due>'" . $this->othersClass->getCurrentDate() . "',1,0) as ended
         from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as branch on branch.clientid = head.branchid
        where head.trno = ? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $trno]);

    $pa_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3621);

    $hideobj = ['voidtrans' =>  $pa_btnvoid_access == 1 ? false : true];

    if (!empty($head)) {

      if ($head[0]->voiddate != null) {
        $hideobj['voidtrans'] = true;
      }

      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
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
    $data = [];
    $array_date = ['dateid', 'due'];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          if (in_array($key, $array_date)) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], 'dateid');
          } else {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
          }
        } //end if    
      }
    }
    if ($data['dateid'] != null && $data['due'] != null) {
      $data['dateid'] = $this->othersClass->sbcdateformat($data['dateid']);
      $data['due'] = $this->othersClass->sbcdateformat($data['due']);
    } else {
      return ['status' => false, 'msg' => "Invalid Date"];
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
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['branchcode'] . ' - ' . $head['branchname']);
    }
  } // end function

  private function getstockselect($config)
  {
    $sqlselect = "select
    item.itemid,
    stock.trno, 
    stock.line,
    item.barcode, 
    item.itemname,
    stock.uom, 
    stock.iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    stock.isqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    case when stock.void=0 then 'false' else 'true' end as void,
    stock.rem,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    where stock.trno =? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
          stock.isamt as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and stock.isamt <> 0
          UNION ALL
          select head.docno,head.dateid,stock.isamt as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ?  and stock.isamt <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $center, $barcode]);

    $usdprice = 0;
    $forex = 1;
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      $qry = "select amt,disc,uom from item where barcode=?";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);
      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, 2);
        }
      }

      if (floatval($data[0]->amt) == 0) {
        return ['status' => false, 'msg' => 'No Latest price found...'];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
      }
    }
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
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
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
   FROM $this->stock as stock
  left join item on item.itemid=stock.itemid 
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
  where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function additem($action, $config)
  {
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $void = 'false';
    $rem = '';
    $ref = '';
    $expiry = '';
    $refx = 0;
    $linex = 0;

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

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
      $amt = $config['params']['data']['amt'];
      $qty = 1;
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = 1;
      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

    if (floatval($forex) == 0) {
      $forex = 1;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isamt' => $amt,
      'amt' => $computedata['amt'] * $forex,
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'void' => $void,
      'uom' => $uom,
      'rem' => $rem
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    if ($action == 'insert') {
      $msg = 'Item was successfully added.';
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' ext:' . $computedata['ext']);
        $row = $this->openstockline($config);
        $this->loadheaddata($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

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
    $wh = ''; //$config['params']['wh'];

    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
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

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - All items');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
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
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and isamt=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Please check, some items have no amount.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, due, whid, rem, voiddate, yourref, ourref, lockuser, lockdate, openby, createdate, createby, editby, editdate, viewby, viewdate, isall, branchid)
      SELECT head.trno, head.doc, head.docno, head.dateid, head.due, head.whid, head.rem, head.voiddate, head.yourref, head.ourref, head.lockuser, head.lockdate, head.openby, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.isall, head.branchid 
      FROM " . $this->head . " as head
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . "(trno, line, itemid, uom, isqty, iss, isamt, amt, ext, disc, rem, void, encodeddate, encodedby, editdate, editby)
        SELECT trno, line, itemid, uom, isqty, iss, isamt, amt, ext, disc, rem, void, encodeddate, encodedby, editdate, editby FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
      //if($posthead){      
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function


  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting is not applicable on this transaction'];

    //   $trno = $config['params']['trno'];
    //   $user = $config['params']['user'];
    //   // $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    //   // $data = $this->coreFunctions->opentable($qry, [$trno]);
    //   // if (!empty($data)) {
    //   //   return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    //   // }
    //   $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    //   $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, due, whid, rem, voiddate, yourref, ourref, lockuser, lockdate, openby, createdate, createby, editby, editdate, viewby, viewdate, isall, branchid)
    // select head.trno, head.doc, head.docno, head.dateid, head.due, head.whid, head.rem, head.voiddate, head.yourref, head.ourref, head.lockuser, head.lockdate, head.openby, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.isall, head.branchid 
    // from " . $this->hhead . " as head left join " . $this->tablenum . " as c on c.trno=head.trno
    // where head.trno=? limit 1";
    //   //head
    //   if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
    //     $qry = "insert into " . $this->stock . "(trno, line, itemid, uom, isqty, iss, isamt, amt, ext, disc, rem, void, encodeddate, encodedby, editdate, editby)
    //     select trno, line, itemid, uom, isqty, iss, isamt, amt, ext, disc, rem, void, encodeddate, encodedby, editdate, editby from " . $this->hstock . " where trno=?";
    //     //stock
    //     if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
    //       $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
    //       $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
    //       $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
    //       $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
    //       return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    //     } else {
    //       $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
    //       return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
    //     }
    //   }
  } //end function
  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    switch ($action) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      case 'voidtrans':
        return $this->othersClass->voidtransaction($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }
  public function uploadexcel($config)
  {
    $rawdata = $config['params']['data'];
    $trno = $config['params']['dataparams']['trno'];
    $msg = '';
    $status = true;
    $uniquefield = "itemcode";

    if ($trno == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
    }

    foreach ($rawdata as $key => $value) {
      try {

        $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key][$uniquefield] . "'");
        if ($itemid == '') {
          $status = false;
          $msg .= 'Failed to upload. ' . $rawdata[$key][$uniquefield] . ' does not exist. ';
          continue;
        }

        $uom_exist = $this->coreFunctions->getfieldvalue("uom", "uom", "itemid = " . $itemid);
        if ($uom_exist == '') {
          $msg .= 'Failed to upload ' . $rawdata[$key][$uniquefield] . ' uom does not exist. ';
          continue;
        }

        $config['params']['trno'] = $trno;
        $config['params']['data']['itemid'] = $itemid;
        $config['params']['data']['uom'] = $uom_exist;
        $config['params']['data']['qty'] = 1;
        $config['params']['data']['amt'] = $rawdata[$key]['price'];
        $config['params']['data']['disc'] = isset($rawdata[$key]['disc']) ? $rawdata[$key]['disc'] : "";

        $return = $this->additem('insert', $config);
        if (!$return['status']) {
          $status = false;
          $msg .= 'Failed to upload. ' . $return['msg'];
          goto exithere;
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
        goto exithere;
      }
    }

    exithere:
    if ($msg == '') {
      $this->logger->sbcwritelog($trno, $config, 'IMPORT', 'UPLOAD EXCEL FILE');
      $msg = 'Successfully uploaded.';
    }

    if (!$status) {
      $this->coreFunctions->execqry("delete from pastock where trno=" . $trno);
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }
  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $dataparams = $config['params']['dataparams'];
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }
}
