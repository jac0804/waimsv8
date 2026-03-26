<?php

namespace App\Http\Classes\modules\production;

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

class tr
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STOCK REQUEST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'trhead';
  public $hhead = 'htrhead';
  public $stock = 'trstock';
  public $hstock = 'htrstock';
  public $detail = '';
  public $hdetail = '';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'IS1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'wh'];
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
      'view' => 785,
      'edit' => 786,
      'new' => 787,
      'save' => 788,
      // 'change' => 789, remove change doc
      'delete' => 790,
      'print' => 791,
      'lock' => 792,
      'unlock' => 793,
      'changeamt' => 842,
      'post' => 794,
      'unpost' => 795,
      'additem' => 839,
      'edititem' => 840,
      'deleteitem' => 841
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listapprovedate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
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
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby, null as approvedate  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby, head.approvedate  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
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
      'help',
      'others'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'department', 'dateid', 'whcode', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'department', 'dateid', 'whcode', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'reqqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['reqqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $tr_btndisapprove_access = $this->othersClass->checkAccess($config['params']['user'], 3589);


    $headgridbtns = ['viewref', 'generatepr', 'disapprove'];

    if ($tr_btndisapprove_access == 0) {
      unset($headgridbtns[2]);
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'reqqty', 'rrqty', 'qa', 'uom', 'rem', 'wh', 'itemname', 'barcode'],
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ]
    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][2]['label'] = 'Approved Qty';
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][8]['type'] = 'hidden';
    $obj[0][$this->gridname]['columns'][8]['label'] = '';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingpd', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Department Code');
    data_set($col1, 'client.lookupclass', 'lookupdeptwh');
    data_set($col1, 'clientname.label', 'Department Name');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'wh', 'rem'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['yourref', 'ourref'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'ourref.label', 'PR Reference');

    $fields = ['approved', 'approvedate'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'ourref.class', 'csourref sbccsreadonly');
    data_set($col4, 'approved.class', 'csapproved sbccsreadonly');

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
    $data[0]['approved'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
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
         head.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         '' as dacnoname,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.wh,
         warehouse.clientname as whname, 
         '' as dwhname,
         left(head.due,10) as due, 
         client.groupid  ";

    $qry = $qryselect . ",null as approvedate,'' as approved from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . ",head.approvedate,head.approved from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
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
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and reqqty=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,yourref,ourref,createdate,createby,
          editby,editdate,lockdate,lockuser,agent,wh,due,cur,trroute,trpricegrp)
          SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
          head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
          head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
          head.due,head.cur,head.trroute,head.trpricegrp FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
          where head.trno=? limit 1";

    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,whid, disc,cost,qty,void,rrcost,rrqty,
            ext,encodeddate,qa,encodedby,editdate,editby,loc,rem,expiry,reqqty,stageid,refx,linex)
            SELECT trno, line, itemid, uom,whid,disc,cost, qty,void,rrcost, rrqty,
            ext, encodeddate,qa, encodedby,editdate,editby,loc,rem,expiry,reqqty, stageid,refx,linex
            FROM " . $this->stock . " where trno =?";
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
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $result = $this->checkallowunpost($config);
    if (!$result['status']) {
      return ['trno' => $trno, 'status' => false, 'msg' => $result['msg']];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
        yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur)
        select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
        head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur
        from (" . $this->hhead . " as head left join cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
        where head.trno=? limit 1";

    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(trno,line,itemid,uom,whid,disc,cost,qty,void,rrcost,
            rrqty,ext,encodeddate,qa,rem,encodedby,editdate,editby,loc,expiry,reqqty,stageid,refx,linex)
            select trno, line, itemid, uom,whid,disc,cost, qty,void, rrcost,
            rrqty, ext, encodeddate, qa,rem, encodedby, editdate, editby,loc,expiry,reqqty,stageid,refx,linex
            from " . $this->hstock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Unposting Head'];
    }
  } //end function

  private function checkallowunpost($config)
  {
    $trno = $config['params']['trno'];

    $qry = "select trno as value from " . $this->hhead . " where trno=? and prdate is not null";
    $data = $this->coreFunctions->datareader($qry, [$trno]);
    if ($data) {
      return ['status' => false, 'msg' => 'UNPOST FAILED, already have purchase requistion reference...'];
    }

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }

    $qry = "select trno as value from " . $this->hhead . " where trno=? and approvedate is not null";
    $data = $this->coreFunctions->datareader($qry, [$trno]);
    if ($data) {
      return ['status' => false, 'msg' => 'UNPOST FAILED, already approved...'];
    }

    return ['status' => true, 'msg' => ''];
  }

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
    FORMAT(stock.reqqty," . $this->companysetup->getdecimal('price', $config['params']) . ") as reqqty,
    stock." . $this->hamt . ", 
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
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
    stock.stageid,
    '' as bgcolor,
    '' as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid where stock.trno =? order by line";

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
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid where stock.trno = ? and stock.line = ? ";
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
      case 'getpddetails':
        return $this->getpddetails($config);
        break;
      case 'getpdsummary':
        return $this->getpdsummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function getpdsummary($config)
  {
    $trno = $config['params']['trno'];
    $msg = '';
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $data = $this->coreFunctions->opentable("select s.line, i.itemid, s.uom, s.disc, s.rem, s.cost,
        round(s.qty-s.qa," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        s.rrcost, s.qty, s.ext, s.qa, s.void, s.refx, s.linex, s.ref, s.sku, s.loc, s.iss, s.stageid
        from hpdstock as s left join item as i on i.barcode=s.barcode where s.trno=? and s.stageid =?", [$value['trno'], $value['stageid']]);
      foreach ($data as $d) {
        $item = $this->coreFunctions->opentable("select item.barcode, item.itemname, ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?", [$d->uom, $d->itemid]);
        $factor = 1;
        if (!empty($item)) {
          $item[0]->factor = $this->othersClass->val($item[0]->factor);
          if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$config['params']['wh']]);
        $computedata = $this->othersClass->computestock($d->rrcost, $d->disc, $d->qty, $factor, 0);

        $line = $this->coreFunctions->datareader("select line as value from " . $this->stock . " where trno=? order by line desc limit 1", [$trno]);
        if ($line == '') $line = 0;
        $line = $line + 1;
        $config['params']['data']['trno'] = $trno;
        $config['params']['data']['line'] = $line;
        $config['params']['data']['itemid'] = $d->itemid;
        $config['params']['data']['uom'] = $d->uom;
        $config['params']['data']['disc'] = $d->disc;
        $config['params']['data']['rem'] = $d->rem;
        $config['params']['data'][$this->damt] = $d->rrcost;
        $config['params']['data'][$this->hamt] = round($computedata['amt'], 2);
        $config['params']['data'][$this->dqty] = $d->rrqty;
        $config['params']['data'][$this->hqty] = $computedata['qty'];
        $config['params']['data']['ext'] = $computedata['ext'];
        $config['params']['data']['qa'] = $d->qa;
        $config['params']['data']['void'] = $d->void;
        $config['params']['data']['refx'] = $value['trno'];
        $config['params']['data']['linex'] = $d->line;
        $config['params']['data']['ref'] = $d->ref;
        $config['params']['data']['sku'] = $d->sku;
        $config['params']['data']['loc'] = $d->loc;
        $config['params']['data']['whid'] = $whid;
        $config['params']['data']['wh'] = $config['params']['wh'];
        $config['params']['data']['stageid'] = $d->stageid;
        $return = $this->additem('insert', $config);
        if ($msg == '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }
        if ($return['status']) {
          $line = $return['row'][0]->line;
          $config['params']['trno'] = $trno;
          $config['params']['line'] = $line;
          $row = $this->openstockline($config);
          $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          array_push($rows, $return['row'][0]);
        }
      }
    }
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  }

  public function getpddetails($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $data = $this->coreFunctions->opentable("select s.line, i.itemid, s.uom, s.disc, s.rem, s.cost,
        round(s.qty-s.qa," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        s.rrcost, s.qty, s.ext, s.qa, s.void, s.refx, s.linex, s.ref, s.sku, s.loc, s.iss, s.stageid 
        from hpdstock as s left join item as i on i.barcode=s.barcode where s.trno=? and s.line=?", [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      $qry = "select item.barcode, item.itemname, ifnull(uom.factor,1) as factor, item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
      $item = $this->coreFunctions->opentable($qry, [$data[0]->uom, $data[0]->itemid]);
      $isnoninv = 0;
      $factor = 1;
      if (!empty($item)) {
        $isnoninv = $item[0]->isnoninv;
        $item[0]->factor = $this->othersClass->val($item[0]->factor);
        if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      }
      $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$config['params']['rows'][$key]['wh']]);

      $qry = "select line as value from trstock where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line = $line + 1;
      $config['params']['data']['trno'] = $trno;
      $config['params']['data']['line'] = $line;
      $config['params']['data']['itemid'] = $data[0]->itemid;
      $config['params']['data']['uom'] = $data[0]->uom;
      $config['params']['data']['disc'] = $data[0]->disc;
      $config['params']['data']['rem'] = $data[0]->rem;
      $config['params']['data'][$this->damt] = $data[0]->rrcost;
      $config['params']['data'][$this->hamt] = $data[0]->cost;
      $config['params']['data'][$this->dqty] = $data[0]->rrqty;
      $config['params']['data'][$this->hqty] = $data[0]->rrqty;
      $config['params']['data']['ext'] = 0;
      $config['params']['data']['qa'] = $data[0]->qa;
      $config['params']['data']['void'] = $data[0]->void;
      $config['params']['data']['refx'] = $config['params']['rows'][$key]['trno'];
      $config['params']['data']['linex'] = $data[0]->line;
      $config['params']['data']['ref'] = $data[0]->ref;
      $config['params']['data']['sku'] = $data[0]->sku;
      $config['params']['data']['loc'] = $data[0]->loc;
      $config['params']['data']['whid'] = $whid;
      $config['params']['data']['wh'] = $config['params']['rows'][$key]['wh'];
      $config['params']['data']['stageid'] = $data[0]->stageid;

      $return = $this->additem('insert', $config);
      if ($msg == '') {
        $msg = $return['msg'];
      } else {
        $msg = $msg . $return['msg'];
      }
      if ($return['status']) {
        $line = $return['row'][0]->line;
        $config['params']['trno'] = $trno;
        $config['params']['line'] = $line;
        $row = $this->openstockline($config);
        $return = ['row' => $row, 'status' => true, 'msg' => $msg];
        array_push($rows, $return['row'][0]);
      }
    }
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'disapprove':
        return $this->disapprove($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function disapprove($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['status' => false, 'msg' => 'DISAPPROVE FAILED, either already served or have item voided...'];
    }

    $qry = "select approved,ifnull(date(approvedate), '') as approvedate from htrhead where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->approvedate == "") {
      $msg = "Already Disapprove! " . $checking[0]->approved . ' ' . $checking[0]->approvedate;
    } else {

      $tag = $this->coreFunctions->execqry("update htrhead set approved='',approvedate = null where trno=? ", "update", [$trno]);
      if ($tag) {
        $tag =    $this->coreFunctions->execqry("update htrstock set rrqty=0,qty=0,ext=0 where trno=? ", "update", [$trno]);
      }

      if ($tag) {
        $msg = "Disapprove Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'DISAPPROVED', 'CLICKED DISAPPROVED');
      } else {
        $msg = "Disapprove Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Disapprove', 'reloadhead' => true];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);

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
      if ($data2[$key]['reqqty'] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
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
    $qty = $config['params']['data']['qty'];
    $wh = $config['params']['data']['wh'];
    $stageid = $refx = $linex = $pending = 0;
    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
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
      $qty = $config['params']['data']['reqqty'];
      $config['params']['line'] = $line;
    }
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";

    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = 0;
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'reqqty' => $qty,
      $this->damt => 0,
      $this->hamt => 0,
      $this->dqty => 0,
      $this->hqty => 0,
      'ext' => 0,
      'disc' => $disc,
      'whid' => $whid,
      'uom' => $uom,
      'rem' => $rem,
      'stageid' => $stageid,
      'refx' => $refx,
      'linex' => $linex,
      'loc' => ''
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' wh:' . $wh . ' Uom:' . $uom);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($refx != 0 && $linex != 0) {
        $pending = $this->getpendingpd($refx, $linex);
        if ($qty > $pending) {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'reqqty' => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          return ['status' => false, 'msg' => 'Qty is more than PD Qty'];
        }
      }
      return $return;
    }
  } // end function

  public function getpendingpd($refx, $linex)
  {
    $pending = $this->coreFunctions->datareader("select qty-qa as value from hpdstock where trno=? and line=?", [$refx, $linex]);
    if ($pending == '') $pending = 0;
    return $pending;
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
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
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' wh:' . $data[0]['wh'] . ' ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
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

  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));

      $computedata = $this->othersClass->computestock($damt, $data[$key]->disc, $dqty, $data[$key]->uomfactor,  $head['tax']);
      $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
