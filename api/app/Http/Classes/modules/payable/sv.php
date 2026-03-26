<?php

namespace App\Http\Classes\modules\payable;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

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

class sv
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PETTY CASH VOUCHER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'svhead';
  public $hhead = 'hsvhead';
  public $detail = 'svdetail';
  public $hdetail = 'hsvdetail';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $defaultContra = 'PC1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'address', 'tax', 'vattype', 'projectid', 'ewt', 'ewtrate', 'contra', 'amt', 'ref'];
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
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2076,
      'edit' => 2077,
      'new' => 2078,
      'save' => 2079,
      // 'change'=>2080, remove change doc
      'delete' => 2081,
      'print' => 2082,
      'lock' => 2083,
      'unlock' => 2084,
      'post' => 2085,
      'unpost' => 2086,
      'additem' => 2087,
      'edititem' => 2088,
      'deleteitem' => 2089
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;
    $postdate = 7;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $isshortcutpo = $this->companysetup->getisshortcutpo($config['params']);
    $companyid = $config['params']['companyid'];


    $fields = [];
    if ($isshortcutpo) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 2078);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'pickpo.label', 'PICK PCR');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick posted PCR?');
    data_set($col1, 'pickpo.action', 'pendingpcr');
    data_set($col1, 'pickpo.lookupclass', 'pendingpcrsummaryshortcut');


    $fields = [];
    switch ($companyid) {
      case 43:
        data_set($col1, 'pickpo.addedparams', ['selectprefix']);

        array_push($fields, 'selectprefix');
        break;
    }
    $col2 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 43:
        $prefix = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'doc=? and psection=?', ['SED', 'SV']);
        if ($prefix != '') {
          $prefixes = explode(",", $prefix);
          $list = array();
          foreach ($prefixes as $key) {
            array_push($list, ['label' => $key, 'value' => $key]);
          }
          data_set($col2, 'selectprefix.options', $list);
        }
        $data = $this->coreFunctions->opentable("select '' as selectprefix");
        break;

      default:
        $data = [];
        break;
    }

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

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
    $qry = "
    select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate, 
    head.yourref, head.ourref      
    from " . $this->head . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno 
    where head.doc=? and num.center = ? 
    and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? 
    " . $condition . "  " . $filtersearch . "
    union all
    select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
    head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref    
    from " . $this->hhead . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno 
    where head.doc=? and num.center = ? 
    and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? 
    " . $condition . "  " . $filtersearch . "
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
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnaddaccount', 'db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step4 = $this->helpClass->getFields(['db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add account/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit account details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action',
          'isvewt', 'isvat', 'isewt', 'db', 'cr', 'postdate', 'ewtcode', 'ewtrate', 'rem', 'project', 'client', 'ref', 'acnoname'
        ],
        'headgridbtns' => ['viewdiagram']
      ],
      //'adddocument'=>['event'=>['lookupclass' => 'entrytransnumpicture','action' => 'documententry','access' => 'view']] 
    ];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // $obj[0]['accounting']['columns'][4]['readonly'] = true;
    // $obj[0]['accounting']['columns'][5]['readonly'] = true;
    $obj[0]['accounting']['columns'][6]['readonly'] = true;
    $obj[0]['accounting']['columns'][9]['readonly'] = true;
    // 11 - ref 
    $obj[0]['accounting']['columns'][11]['type'] = 'input';
    $obj[0]['accounting']['columns'][11]['readonly'] = true;
    //10 - client      
    // $obj[0]['accounting']['columns'][10]['type'] = 'input';
    // $obj[0]['accounting']['columns'][10]['readonly'] = true;


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingpcr', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[2]['label'] = "DELETE ACCOUNT";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address', 'dacnoname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Employee');
    data_set($col1, 'client.lookupclass', 'employee');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'dacnoname.label', 'Petty Cash Account');
    data_set($col1, 'dacnoname.lookupclass', 'PC');
    data_set($col1, 'dacnoname.required', true);

    $fields = ['dateid', 'dprojectname', 'dewt', 'dvattype', 'amt'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'amt.label', 'Amount Released');

    $fields = [['yourref', 'ourref'], 'ref', 'rem'];
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'ref.class', 'sbccsreadonly');


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['address'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['ref'] = '';
    $data[0]['rem'] = '';
    $data[0]['projectcode'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['projectname'] = '';
    $data[0]['tax'] = 0;
    $data[0]['ewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['amt'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['cvtrno'] = 0;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      } else {
        $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
        if ($t == '') {
          $trno = 0;
        }
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.ewt,head.ewtrate,'' as dewt, 
         head.vattype,
         '' as dvattype,
         head.projectid,
         head.ref,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,ifnull(project.code,'') as projectcode,round(head.amt,2) as amt,
         head.cvtrno ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid         
        where head.trno = ? and num.doc=? and num.center=? ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->opendetail($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }

      return  [
        'head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked,
        'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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
    $qry = "select trno from " . $this->detail . " where trno=? and db=0 and cr=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Both debit and credit amount are zero.'];
    }

    $check = "select sum(db) as db, sum(cr) as cr from " . $this->detail . " where trno=?";
    $chkbal = $this->coreFunctions->opentable($check, [$trno]);

    if ($chkbal[0]->db != $chkbal[0]->cr) {
      return ['status' => false, 'msg' => 'Posting failed. Debit and credit are not equal.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,dateid,
      rem,ewt,ewtrate,yourref,ourref,viewby,viewdate,createdate,createby,editby,editdate,lockdate,lockuser,projectid,contra,amt, vattype, tax, ref)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,
      head.dateid as dateid, head.rem, head.ewt,head.ewtrate,head.yourref, head.ourref,head.viewby,head.viewdate,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,
      head.projectid,head.contra,head.amt, head.vattype, head.tax, head.ref
      FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hdetail . "(trno,line,acnoid,client,rem,postdate,db,cr,
        encodeddate, encodedby,editdate,editby,refx,linex,isewt,isvat,isvewt,ewtcode,ewtrate,ref,projectid)
        SELECT trno,line,acnoid,client,rem,postdate,db,cr,
        encodeddate, encodedby,editdate,editby,refx,linex,isewt,isvat,isvewt,ewtcode,ewtrate,ref,projectid FROM " . $this->detail . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting detail'];
      }
      //if($posthead){      
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hdetail . " where trno=? and cvtrno<>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already served...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,dateid,
      rem,ewt,ewtrate,yourref,ourref,viewby,viewdate,createdate,createby,editby,editdate,lockdate,lockuser,projectid,contra,amt, vattype,tax,ref)
  select head.trno, head.doc, head.docno, client.client, head.clientname, head.address,
  head.dateid as dateid, head.rem, head.ewt,head.ewtrate, head.yourref, head.ourref,head.viewby,head.viewdate, head.createdate,
  head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.projectid,head.contra,head.amt, head.vattype,head.tax,head.ref
  from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
  where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->detail . "(trno,line,acnoid,client,rem,postdate,db,cr,
        encodeddate, encodedby,editdate,editby,refx,linex,isewt,isvat,isvewt,ewtcode,ewtrate,ref,projectid)
      select trno,line,acnoid,client,rem,postdate,db,cr,
        encodeddate, encodedby,editdate,editby,refx,linex,isewt,isvat,isvewt,ewtcode,ewtrate,ref,projectid
      from " . $this->hdetail . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function


  private function getdetailselect($config)
  {
    $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,coa.acno,coa.acnoname,
  client.client,client.clientname,d.rem,
  FORMAT(d.db,2) as db,FORMAT(d.cr,2) as cr,d.refx,d.linex,
  left(d.postdate,10) as postdate,coa.alias,head.cvtrno,
  d.projectid,ifnull(proj.name,'') as projectname,d.subproject,d.stageid,proj.code as project,
  case d.isewt when 0 then 'false' else 'true' end as isewt,
  case d.isvat when 0 then 'false' else 'true' end as isvat,
  case d.isvewt when 0 then 'false' else 'true' end as isvewt,d.ewtcode,d.ewtrate,'' as bgcolor,'' as 
  errcolor ";
    return $qry;
  }


  public function opendetail($trno, $config)
  {
    $sqlselect = $this->getdetailselect($config);

    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join coa on d.acnoid=coa.acnoid
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join coa on coa.acnoid=d.acnoid
    where d.trno=?
  ";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $detail;
  }


  public function opendetailline($config)
  {
    $sqlselect = $this->getdetailselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join coa on d.acnoid=coa.acnoid
    where d.trno=? and d.line=?";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $detail;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'saveitem': //save all detail edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'getpcrselected':
        return $this->getpcrselected($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
    CAST(concat('Total SV Amt: ',round(sum(s.db),2)) as CHAR) as rem,s.refx,po.cvtrno
    from hsvhead as po
    left join hsvdetail as s on s.trno = po.trno
    where s.trno = ?
    group by po.trno,po.docno,po.dateid,s.refx,po.cvtrno
    union all
    select po.trno,po.docno,left(po.dateid,10) as dateid,
    CAST(concat('Total SV Amt: ',round(sum(s.db),2)) as CHAR) as rem,s.refx,po.cvtrno
    from svhead as po
    left join svdetail as s on s.trno = po.trno
    where s.trno = ?
    group by po.trno,po.docno,po.dateid,s.refx,po.cvtrno";

    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;

      foreach ($t as $key => $value) {
        //PO
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 200,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => '#B5EAEA',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select pr.docno,left(pr.dateid,10) as dateid,
          CAST(concat('Total PQ Amt: ',round(sum(s.amt),2)) as CHAR) as rem
          from hpqhead as pr left join hpqdetail as s on s.trno = pr.trno
          left join hsvdetail sv on sv.refx = pr.trno
          where sv.trno = ?
          group by pr.docno,pr.dateid";

          $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => 10,
                  'y' => 50 + $a,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => '#F5FCC1',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
        if (floatval($t[$key]->cvtrno) != 0) {
          //pr
          $qry = "select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total CV Amt: ',round(sum(detail.db-detail.cr),2)) as CHAR) as rem
          from lahead as head
          left join ladetail as detail on detail.trno = head.trno
          
          where detail.pcvtrno = ?
          group by head.docno,head.dateid
          union all
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total CV Amt: ',round(sum(detail.db-detail.cr),2)) as CHAR) as rem
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          
          where detail.pcvtrno = ?
          group by head.docno,head.dateid";

          $x = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
          $cvref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => 500,
                  'y' => -200 + $a,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => '#F5FCC5',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $cvref]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->opendetailline($config);
    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Payment amount is greater than setup amount.'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $isupdate = $this->additem('update', $config);
      if ($isupdate['status'] == false) {
        break;
      }
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    //$isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key]['db'] == 0 && $data2[$key]['cr'] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Some entries have zero value both debit and credit ';
        } else {
          $msg2 = ' Reference Amount is lower than encoded amount ';
        }
      }
    }
    if ($isupdate['status']) {
      return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      if ($isupdate['msg'] == '') {
        return ['accounting' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
      } else {
        return ['accounting' => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
      }
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  // insert and update detail
  public function additem($action, $config, $setlog = false)
  {
    $acno = $config['params']['data']['acno'];
    $acnoname = $config['params']['data']['acnoname'];
    $trno = $config['params']['trno'];
    $db = $config['params']['data']['db'];
    $cr = $config['params']['data']['cr'];
    $postdate = $config['params']['data']['postdate'];
    $rem = $config['params']['data']['rem'];
    $client = $config['params']['data']['client'];
    $project = $config['params']['data']['project'];
    $refx = 0;
    $linex = 0;
    $ref = '';
    $isewt = false;
    $isvat = false;
    $isvewt = false;
    //  $project =0;
    $ewtcode = '';
    $ewtrate = '';


    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['isvat'])) {
      $isvat = $config['params']['data']['isvat'];
    }
    if (isset($config['params']['data']['isewt'])) {
      $isewt = $config['params']['data']['isewt'];
    }
    if (isset($config['params']['data']['ewtcode'])) {
      $ewtcode = $config['params']['data']['ewtcode'];
    }

    //  if(isset($config['params']['data']['project'])){
    //    $project = $config['params']['data']['projectid'];
    //  }

    if ($ewtcode == '') {
      $ewtcode = $this->coreFunctions->getfieldvalue($this->head, "ewt", "trno=?", [$trno]);
    }

    // if ($project == ''){
    //   $project =$this->coreFunctions->getfieldvalue($this->head,"projectid","trno=?",[$trno]);
    // }

    if (isset($config['params']['data']['ewtrate'])) {
      $ewtrate = $config['params']['data']['ewtrate'];
    }

    if ($ewtrate == '') {
      $ewtrate = $this->coreFunctions->getfieldvalue($this->head, "ewtrate", "trno=?", [$trno]);
    }

    if (isset($config['params']['data']['isvewt'])) {
      $isvewt = $config['params']['data']['isvewt'];
    }


    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
    } elseif ($action == 'update') {
      $project = $config['params']['data']['projectid'];
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
    }
    $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'acnoid' => $acnoid,
      'client' => $client,
      'db' => $db,
      'cr' => $cr,
      'postdate' => $postdate,
      'rem' => $rem,
      'projectid' => $project,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'isewt' => $isewt,
      'isvat' => $isvat,
      'isvewt' => $isvewt,
      'ewtcode' => $ewtcode,
      'ewtrate' => $ewtrate
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';
    $status = true;

    if ($isvewt == "true" && ($isewt == "true" || $isvat == "true")) {
      $msg = 'Already tagged as VEWT, remove tagging for EWT/VAT';
      return ['status' => false, 'msg' => $msg];
    }

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' db:' . $db . ' cr:' . $cr . ' client:' . $client . ' date:' . $postdate, $setlog ? $this->tablelogs : '');
        if ($refx != 0) {
          $this->coreFunctions->execqry("update hpqdetail set isok =1 where trno =? and line =?", "update", [$refx, $linex]);
        }
        $row = $this->opendetailline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add Account Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
        if ($refx != 0) {
          $this->coreFunctions->execqry("update hpqdetail set isok =1 where trno =? and line =?", "update", [$refx, $linex]);
        }
      } else {
        $return = false;
      }
      return ['status' => $return, 'msg' => ''];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select coa.acno,t.refx,t.linex from ' . $this->detail . ' as t left join coa on coa.acnoid=t.acnoid where t.trno=? and t.refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->coreFunctions->execqry("update hpqdetail set isok =0 where trno =? and line =?", "update", [$data[$key]->refx, $data[$key]->linex]);
      $this->coreFunctions->execqry("update " . $this->head . " set ourref='' where trno =? ", "update", [$trno]);
    }
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }



  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->opendetailline($config);
    //if(($data[0]->qa == $data[0]->qty)){
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    if ($data[0]->refx != 0) {
      $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' code:' . $data[0]['acno'] . ' db:' . $data[0]['db'] . ' cr:' . $data[0]['cr'] . ' client:' . $data[0]['client'] . ' date:' . $data[0]['postdate'] . ' ref:' . $data[0]['ref']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end function

  public function getposummaryqry($config)
  {
    return "
    select head.trno,head.docno,head.client,head.clientname,head.address,head.contra,coa.acnoname as contraname, 'NON-VATABLE' as vattype,head.yourref,head.ourref,
    head.dateid,detail.amt,detail.rem as drem,detail.projectid, head.projectid as headprjid,coa2.acno,coa2.acnoname,detail.line,detail.postdate,head.rem
    from hpqhead as head left join hpqdetail as detail on detail.trno = head.trno
    left join coa on coa.acno = head.contra left join coa as coa2 on coa2.acnoid = detail.acnoid
    left join transnum on transnum.trno = head.trno 
    where detail.isok =0 and head.trno=? order by dateid,docno";
  }

  public function getpcrselected($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $total = 0;

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);

      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['acno'] = $data[$key2]->acno;
          $config['params']['data']['acnoname'] = $data[$key2]->acnoname;
          $config['params']['data']['db'] = $data[$key2]->amt;
          $config['params']['data']['cr'] = 0;
          $config['params']['data']['postdate'] = $data[$key2]->postdate;
          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['project'] = $data[$key2]->projectid;
          $config['params']['data']['client'] = $data[$key2]->client;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $total = $total + $data[$key2]->amt;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          }
        } //end foreach

      }
    }

    if ($total != 0) {
      //credit account
      $config['params']['data']['acno'] = $data[0]->contra;
      $config['params']['data']['acnoname'] = $data[0]->contraname;
      $config['params']['data']['db'] = 0;
      $config['params']['data']['cr'] = $total;
      $config['params']['data']['postdate'] = $data[0]->dateid;
      $config['params']['data']['rem'] = '';
      $config['params']['data']['project'] = $data[0]->headprjid;
      $config['params']['data']['client'] = $data[0]->client;
      $config['params']['data']['refx'] = 0;
      $config['params']['data']['linex'] = 0;
      $config['params']['data']['ref'] = '';

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    }

    $this->coreFunctions->execqry("update " . $this->head . " set ourref ='" . $data[0]->docno . "' where trno = " . $trno, "update");
    return ['row' => $rows, 'status' => true, 'msg' => 'Added accounts Successfull...'];
  } //end function


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
    $dataparams = $config['params']['dataparams'];
    if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
