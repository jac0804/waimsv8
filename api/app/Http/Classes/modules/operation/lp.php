<?php

namespace App\Http\Classes\modules\operation;

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

class lp
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LEASE PROVISION';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'lphead';
  public $hhead = 'hlphead';
  public $info = 'tenantinfo';
  public $hinfo = 'tenantinfo';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';

  private $fields = [
    'trno', 'docno', 'clientname', 'dateid', 'address', 'bstyle', 'category', 'email', 'locid', 'start', 'enddate', 'escalation', 'contract',
    'tin', 'tel', 'contact', 'isnonvat', 'type', 'position'
  ];

  private $infofields = [
    'trno', 'leaserate', 'acrate', 'cusarate', 'billtype', 'rentcat', 'mcharge', 'percentsales',
    'tenanttype', 'emulti', 'elecrate', 'penalty', 'eratecat', 'wratecat', 'classification', 'selecrate',
    'wmulti', 'waterrate', 'isspecialrate', 'semulti', 'ewcharges', 'concharges', 'fencecharge', 'powercharges',
    'watercharges', 'housekeeping', 'docstamp', 'consbond', 'emeterdep', 'servicedep', 'secdep', 'rem', 'secdepmos'
  ];

  private $blnfields = ['isnonvat', 'isspecialrate'];

  private $except = ['trno'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;


  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Approved', 'color' => 'red'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
    ['val' => 'all', 'label' => 'All', 'color' => 'blue']
  ];


  public $labelposted = 'Posted';
  public $labellocked = 'Approved';

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
      'view' => 2260,
      'edit' => 2278,
      'new' => 1801,
      'save' => 623,
      'delete' => 1816,
      'print' => 1831,
      'lock' => 4071,
      'unlock' => 4072,
      'post' => 4052,
      'unpost' => 4070
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
    $listplanholder = 5;
    $yourref = 6;
    $ourref = 7;
    $postdate = 9;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname',  'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $cols[$listclientname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $cols[$listclientname]['label'] = 'Tenant';
    $cols[$postdate]['label'] = 'Post Date';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $companyid = $config['params']['companyid'];

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $searchfilter = $config['params']['search'];
    $limit = '';
    $condition = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null';
        break;
      case 'locked':
        $condition .= ' and num.postdate is null and head.lockdate is not null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $dateid = "left(head.dateid,10) as dateid";
    if ($searchfilter == "") $limit = 'limit 150';
    $orderby =  "order by  dateid desc, docno desc";

    if (isset($searchfilter)) {
      $searchfield = ['head.docno', 'head.clientname',  'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

      if ($searchfilter != "") {
        $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
      }
      $limit = "";
    }


    $qry = "select head.trno,head.docno,head.clientname,$dateid, case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'APPROVED' end as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor, head.rem
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
     union all
     select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
    'blue' as statuscolor, head.rem
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno  where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition  . " 
    $orderby $limit";


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

    $buttons['lock']['label'] = 'APPROVE';
    $buttons['unlock']['label'] = 'DISAPPROVE';


    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
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
    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];
    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf', 'access' => 'view', 'type' => 'viewmanual']];
    }

    $buttons['others']['items']['createprofile'] = ['label' => 'Create Profile', 'todo' => ['lookupclass' => 'generateclient', 'action' => 'generateclient', 'access' => 'view', 'type' => 'navigation']];

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

    $fields = ['leaserate', 'acrate', 'cusarate', 'penalty', 'mcharge', 'percentsales', 'msales', 'secdep', 'secdepmos'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'penalty.label', 'Penalty Percentage');
    data_set($col1, 'penalty.class', 'cspenalty');
    data_set($col1, 'penalty.readonly', false);
    data_set($col1, 'secdep.class', 'sbccsreadonly');

    $fields = ['emeter', 'semeter', 'wmeter', 'emulti', 'semulti', 'wmulti', 'elecrate', 'selecrate', 'waterrate'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'emeter.class', 'sbccsreadonly');
    data_set($col2, 'semeter.class', 'sbccsreadonly');
    data_set($col2, 'wmeter.class', 'sbccsreadonly');

    $fields = ['ewcharges', 'concharges', 'fencecharge', 'powercharges', 'watercharges', 'housekeeping', 'docstamp', 'consbond', 'emeterdep'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['servicedep', 'rem'];
    $col4 = $this->fieldClass->create($fields);


    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4], 'label' => 'OTHER INFO'],
      'passengertab' => ['action' => 'tableentry', 'lookupclass' => 'leaseprovision_escalation_tab', 'label' => 'ESCALATION', 'checkchanges' => 'tableentry'],
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['docno', 'dateid', 'clientname', 'address', 'bstyle', 'category', 'escalation', 'contract'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'address.type', 'cinput');
    data_set($col1, 'bstyle.type', 'cinput');

    data_set($col1, 'address.label', 'Business Address');
    data_set($col1, 'category.label', 'Nature of Business');
    data_set($col1, 'contract.label', 'Term of Contract');

    $fields = ['loc', 'start', 'enddate', 'tin', 'contact', 'position', 'tel', 'email'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'loc.action', 'lookuploc_mms');
    data_set($col2, 'loc.lookupclass', 'lookuploc_mms');

    data_set($col2, 'tel.type', 'cinput');
    data_set($col2, 'tel.label', 'Contact No.');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'email.type', 'cinput');

    $fields = ['area', 'billtype', 'rentcat', 'tenanttype', 'classification', 'eratecatname', 'wratecatname'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'area.type', 'input');
    data_set($col3, 'area.class', 'sbccsreadonly');

    $fields = ['isnonvat'];
    $col4 = $this->fieldClass->create($fields);

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
    $data[0]['address'] = '';
    $data[0]['category'] = '';
    $data[0]['bstyle'] = '';
    $data[0]['escalation'] = '';
    $data[0]['contract'] = '';

    // col2
    $data[0]['locid'] = '0';
    $data[0]['loc'] = '';
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['enddate'] =  $this->othersClass->getCurrentDate();
    $data[0]['tin'] = '';
    $data[0]['tel'] = '';
    $data[0]['contact'] = '';
    $data[0]['position'] = '';
    $data[0]['email'] = '';

    // col4
    $data[0]['isnonvat'] = '0';
    $data[0]['type'] = '';

    //info
    $data[0]['leaserate'] = 0;
    $data[0]['acrate'] = 0;
    $data[0]['cusarate'] = 0;
    $data[0]['billtype'] = '';
    $data[0]['rentcat'] = '';
    $data[0]['mcharge'] = 0;
    $data[0]['percentsales'] = 0;
    $data[0]['tenanttype'] = '';
    $data[0]['penalty'] = 0;
    $data[0]['eratecat'] = 0;
    $data[0]['eratecatname'] = '';
    $data[0]['wratecat'] = 0;
    $data[0]['wratecatname'] = '';
    $data[0]['msales'] = 0;
    $data[0]['rem'] = '';
    $data[0]['classification'] = '';
    $data[0]['emulti'] = 1;
    $data[0]['elecrate'] = 0;
    $data[0]['semulti'] = 1;
    $data[0]['selecrate'] = 0;
    $data[0]['wmulti'] = 1;
    $data[0]['waterrate'] = 0;
    $data[0]['isspecialrate'] = '0';

    //dep&charges
    $data[0]['ewcharges'] = 0;
    $data[0]['concharges'] = 0;
    $data[0]['fencecharge'] = 0;
    $data[0]['powercharges'] = 0;
    $data[0]['watercharges'] = 0;
    $data[0]['housekeeping'] = 0;
    $data[0]['docstamp'] = 0;
    $data[0]['consbond'] = 0;
    $data[0]['emeterdep'] = 0;
    $data[0]['servicedep'] = 0;
    $data[0]['secdep'] = 0;
    $data[0]['secdepmos'] = 0;

    return $data;
  }

  private function selecthead($config)
  {
    return "select num.trno, num.docno,head.client, head.clientname,date_format(head.dateid,'%m/%d/%Y') as dateid, head.address, head.bstyle, head.category, head.email, 
    head.locid, date_format(head.start,'%m/%d/%Y') as start, date_format(head.enddate,'%m/%d/%Y') as enddate, head.escalation, head.contract,head.tin, head.tel, head.contact,
    head.isnonvat,head.type, head.position, format(tinfo.leaserate,2) as leaserate, format(tinfo.acrate,2) as acrate, format(tinfo.cusarate,2) as cusarate, format(tinfo.drent,2) as drent,
    tinfo.dac, tinfo.dcusa, tinfo.billtype, tinfo.rentcat, tinfo.emulti, tinfo.semulti, tinfo.wmulti, tinfo.penalty, format(tinfo.mcharge,2) as mcharge, tinfo.percentsales,
    format(tinfo.msales,2) as msales, format(tinfo.elecrate,2) as elecrate, format(tinfo.selecrate,2) as selecrate, format(tinfo.waterrate,2) as waterrate, tinfo.classification, 
    tinfo.eratecat,tinfo.wratecat, format(tinfo.secdep,2) as secdep, tinfo.secdepmos, format(tinfo.ewcharges,2) as ewcharges, format(tinfo.concharges,2) as concharges, 
    format(tinfo.fencecharge,2) as fencecharge,format(tinfo.powercharges,2) as powercharges, format(tinfo.watercharges,2) as watercharges, format(tinfo.housekeeping,2) as housekeeping, 
    format(tinfo.docstamp,2) as docstamp, format(tinfo.consbond,2) as consbond, format(tinfo.emeterdep,2) as emeterdep, format(tinfo.servicedep,2) as servicedep, tinfo.rem,
    tinfo.tenanttype,loc.name as loc, loc.area, loc.emeter, loc.semeter, loc.wmeter,
    elect.category as eratecatname, water.category as wratecatname,
    tinfo.isspecialrate";
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
    $info = $this->info;
    $hinfo = $this->hinfo;

    if ($isposted) {
      $this->labellocked = '';
    }

    $qryselect = "select num.trno, num.docno,head.client, head.clientname,date_format(head.dateid,'%m/%d/%Y') as dateid, head.address, head.bstyle, head.category, head.email, 
    head.locid, date_format(head.start,'%m/%d/%Y') as start, date_format(head.enddate,'%m/%d/%Y') as enddate, head.escalation, head.contract,head.tin, head.tel, head.contact,
    head.isnonvat,head.type, head.position, format(tinfo.leaserate,2) as leaserate, format(tinfo.acrate,2) as acrate, format(tinfo.cusarate,2) as cusarate, format(tinfo.drent,2) as drent,
    tinfo.dac, tinfo.dcusa, tinfo.billtype, tinfo.rentcat, tinfo.emulti, tinfo.semulti, tinfo.wmulti, tinfo.penalty, format(tinfo.mcharge,2) as mcharge, tinfo.percentsales,
    format(tinfo.msales,2) as msales, format(tinfo.elecrate,2) as elecrate, format(tinfo.selecrate,2) as selecrate, format(tinfo.waterrate,2) as waterrate, tinfo.classification, 
    tinfo.eratecat,tinfo.wratecat, format(tinfo.secdep,2) as secdep, tinfo.secdepmos, format(tinfo.ewcharges,2) as ewcharges, format(tinfo.concharges,2) as concharges, 
    format(tinfo.fencecharge,2) as fencecharge,format(tinfo.powercharges,2) as powercharges, format(tinfo.watercharges,2) as watercharges, format(tinfo.housekeeping,2) as housekeeping, 
    format(tinfo.docstamp,2) as docstamp, format(tinfo.consbond,2) as consbond, format(tinfo.emeterdep,2) as emeterdep, format(tinfo.servicedep,2) as servicedep, tinfo.rem,
    tinfo.tenanttype,loc.name as loc, loc.area, loc.emeter, loc.semeter, loc.wmeter,
    elect.category as eratecatname, water.category as wratecatname,
    tinfo.isspecialrate";


    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno     
        left join $info as tinfo on tinfo.trno = head.trno    
        left join loc as loc on loc.line = head.locid
        left join ratecategory as elect on elect.line = tinfo.eratecat
        left join ratecategory as water on water.line = tinfo.wratecat
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno      
        left join $hinfo as tinfo on tinfo.trno = head.trno   
        left join loc as loc on loc.line = head.locid
        left join ratecategory as elect on elect.line = tinfo.eratecat
        left join ratecategory as water on water.line = tinfo.wratecat
        where head.trno = ? and num.doc=? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }

      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }


      return  ['head' => $head, 'griddata' => ['accounting' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or you are not allowed to view the information...'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $vatex = $this->companysetup->isvatexsales($config);
    $data = [];
    $info = [];

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
    $info['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $info['editby'] = $config['params']['user'];

    if ($head['secdepmos'] != 0) {
      $lease = $this->othersClass->sanitizekeyfield('amt', $head['leaserate']);
      $area = $this->othersClass->sanitizekeyfield('amt', $head['area']);
      if ($vatex) {
        $head['secdep'] =  $lease * $area * $head['secdepmos'];
      } else {
        if ($head['isnonvat'] == 1) {
          $head['secdep'] =  $lease * $area * $head['secdepmos'];
        } else {
          $head['secdep'] =  ($lease / 1.12) * $head['secdepmos'];
        }
      }
    }

    foreach ($this->infofields as $key2) {
      $info[$key2] = $head[$key2];
      if (!in_array($key2, $this->except)) {
        $info[$key2] = $this->othersClass->sanitizekeyfield($key2, $info[$key2]);
      } //end if    
    }

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      // for info table
      $this->coreFunctions->sbcupdate($this->info, $info, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);

      // for info table
      $this->coreFunctions->sbcinsert($this->info, $info);
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

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->info . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from beneficiary where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  //  to follow posting
  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    if (!$this->othersClass->islocked($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Not yet approved.'];
    }


    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

    if ($client == '') {
      if (!$this->createclient($config)) {
        return ['status' => false, 'msg' => 'Posting failed. Error while creating tenant code.'];
      }
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,dateid,address,bstyle,category,email,locid,start,enddate,escalation,contract,tin,tel,contact,isnonvat,type,position,
    users,createdate,createby,editby,editdate,viewby,viewdate,lockuser,lockdate)
    select trno,doc,docno,client,clientname,dateid,address,bstyle,category,email,locid,start,enddate,escalation,contract,tin,tel,contact,isnonvat,type,position,
    users,createdate,createby,editby,editdate,viewby,viewdate,lockuser,lockdate from " . $this->head . " where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
      $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Lease Provision'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $client = $this->coreFunctions->getfieldvalue($this->hhead, "client", "trno=?", [$trno]);
    $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);

    if ($clientid != 0) {
      $qry = "select lahead.trno as value from lahead where client=?
      union all
      select glhead.trno as value from glhead where clientid=?
      union all
      select sohead.trno as value from sohead where client=?
      union all
      select hsohead.trno as value from hsohead where client=? 
      union all
      select trno as value from vrstock where clientid=?
      union all
      select trno as value from hvrstock where clientid=?
      union all
      select trno as value from eahead where client=?
      union all
      select trno as value from heahead where client=?
      union all
      select trno as value from eainfo where client=?
      union all
      select trno as value from heainfo where client=? limit 1";

      $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $client, $clientid, $clientid, $client, $client, $client, $client]);

      if (($count != '')) {
        return ['status' => false, 'msg' => 'Tenant already have transactions...'];
      }
    }

    if (!$this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Unpost FAILED, Already unposted...'];
    }

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,dateid,address,bstyle,category,email,locid,start,enddate,escalation,contract,tin,tel,contact,isnonvat,type,position,
    users,createdate,createby,editby,editdate,viewby,viewdate,lockuser,lockdate)
    select trno,doc,docno,client,clientname,dateid,address,bstyle,category,email,locid,start,enddate,escalation,contract,tin,tel,contact,isnonvat,type,position,
    users,createdate,createby,editby,editdate,viewby,viewdate,lockuser,lockdate
    from " . $this->hhead . " where trno=? limit 1";
    $unposthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($unposthead) {
      $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from client where clientid=?", "delete", [$clientid]);
      $this->coreFunctions->execqry("update " . $this->head . " set client ='' where trno=?", "update", [$trno]);
      $this->coreFunctions->execqry("update tenantinfo set clientid =0 where trno=?", "update", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function stockstatus($config)
  {
    return [];
  }

  public function updateperitem($config)
  {
    return [];
  }


  public function updateitem($config)
  {
    return [];
  } //end function

  public function addallitem($config)
  {
    return [];
  } //end function


  // insert and update detail
  public function additem($action, $config)
  {
    return [];
  } // end function

  public function deleteallitem($config)
  {
    return [];
  }

  public function deleteitem($config)
  {
    return [];
  } //end function


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;

      case 'generateclient':
        return $this->createclient($config);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  private function createclient($config)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $data = [];

    $clientname = $this->coreFunctions->getfieldvalue($this->head, "clientname", "trno=?", [$trno]);

    if ($clientname == '') {
      return ['status' => false, 'msg' => 'Please complete details for tenant.'];
    }

    $clientcode = $this->getnewclient($config);

    $qryselect = $this->selecthead($config);

    $qry = $qryselect . " from $this->head as head
    left join $this->tablenum as num on num.trno = head.trno     
    left join $this->info as tinfo on tinfo.trno = head.trno    
    left join loc as loc on loc.line = head.locid
    left join ratecategory as elect on elect.line = tinfo.eratecat
    left join ratecategory as water on water.line = tinfo.wratecat
    where head.trno = ? and num.doc='LP' and num.center = ? 
    union all " . $qryselect . " from $this->hhead as head
    left join $this->tablenum as num on num.trno = head.trno      
    left join $this->hinfo as tinfo on tinfo.trno = head.trno   
    left join loc as loc on loc.line = head.locid
    left join ratecategory as elect on elect.line = tinfo.eratecat
    left join ratecategory as water on water.line = tinfo.wratecat
    where head.trno = ? and num.doc='LP' and num.center=? ";


    $res = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

    unset($this->fields[0]);
    unset($this->fields[1]);
    unset($this->fields[3]);
    unset($this->fields[4]);

    foreach ($this->fields as $key) {
      $data[$key] = $res[0]->$key;
      if (!in_array($key, $this->except)) {
        $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
      } //end if    
    }

    $data['client'] = $clientcode;
    $data['addr'] = $res[0]->address;
    $data['center'] = $center;
    $data['istenant'] = 1;

    //create client
    $clientid = $this->coreFunctions->insertGetId('client', $data);

    if ($clientid != 0) {
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', 'LeaseProvision - ' . $clientid . ' - ' . $clientcode . ' - ' . $res[0]->clientname);

      $this->coreFunctions->execqry("update " . $this->head . " set client = ? where trno = ?", 'update', [$clientcode, $trno]);
      $this->coreFunctions->execqry("update " . $this->info . " set clientid = ? where trno = ?", 'update', [$clientid, $trno]);
      $this->coreFunctions->execqry("update escalation set clientid = ? where trno = ?", 'update', [$clientid, $trno]);

      return true;
    } else {
      return false;
    }
  }

  private function getnewclient($config)
  {
    $pref = 'TL';
    $docnolength =  $this->companysetup->getclientlength($config['params']);
    $last = $this->othersClass->getlastclient($pref, 'tenant');
    $start = $this->othersClass->SearchPosition($last);
    $seq = substr($last, $start) + 1;
    $poseq = $pref . $seq;
    $newclient = $this->othersClass->PadJ($poseq, $docnolength);
    return $newclient;
  }

  public function reportsetup($config)
  {
    $islocked = $this->othersClass->islocked($config);
    if (!$islocked) return ['status' => false, 'msg' => 'Application not yet approved.'];
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
