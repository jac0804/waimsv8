<?php

namespace App\Http\Classes\modules\seastar;

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
use App\Http\Classes\headClass;
use App\Http\Classes\builder\helpClass;
use Exception;

class sj
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'WAYBILL';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
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
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AR1';
  private $stockselect;
  private $fields = [
    'trno',
    'docno',
    'dateid',
    'due',
    'client',
    'clientname',
    'yourref',
    'ourref',
    'rem',
    'terms',
    'forex',
    'cur',
    'wh',
    'address',
    'contra',
    'tax',
    'vattype',
    'projectid',
    'ewt',
    'ewtrate',
    'consigneeid',
    'conaddr',
    'shipperid',
    'whto'

  ];
  private $except = ['trno', 'dateid', 'due', 'creditinfo'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  private $headClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
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
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
    $this->headClass = new headClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 169,
      'edit' => 170,
      'new' => 171,
      'save' => 172,
      // 'change' => 173, remove change doc
      'delete' => 174,
      'print' => 175,
      'lock' => 176,
      'unlock' => 177,
      'acctg' => 183,
      'changeamt' => 180,
      'post' => 178,
      'unpost' => 179,
      'additem' => 802,
      'edititem' => 803,
      'deleteitem' => 804,
      'release' => 2994,
      'whinfo' => 3959,
      'tripapproved' => 4494,
      'tripdisapproved' => 4738,

    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $userid = $config['params']['adminid'];
    $dept = '';

    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;
    $total = 7;
    $rem = 8;
    $ar = 9;
    $postedby = 10;
    $createby = 11;
    $editby = 12;
    $viewby = 13;
    $receiveby = 14;
    $receivedate = 15;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'total', 'rem', 'ar', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby', 'receiveby', 'receivedate'];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$total]['label'] = 'Total Amount';
    $cols[$ar]['label'] = 'AR Balance';
    $cols[$total]['align'] = 'text-left';
    $cols[$total]['type'] = 'coldel';
    $cols[$rem]['type'] = 'coldel';
    $cols[$ar]['type'] = 'coldel';
    $cols[$liststatus]['name'] = 'statuscolor';
    $cols[$receiveby]['type'] = 'coldel';
    $cols[$receivedate]['type'] = 'coldel';

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = '';
    $lfield = '';
    $gfield = '';
    $ljoin = '';
    $gjoin = '';
    $group = '';
    $lstat = "'DRAFT'";
    $gstat = "'POSTED'";
    $lstatcolor = "'blue'";
    $gstatcolor = "'grey'";

    $rem = '';
    $join = '';
    $hjoin = '';
    $addparams = '';

    $userid = $config['params']['adminid'];
    $dept = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and head.lockdate is null and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        break;
      case 'forwtinput':
        $condition = ' and num.postdate is null and num.statid=74';
        break;
      case 'forposting':
        $condition = ' and num.postdate is null and num.statid=39';
        break;
    }

    $linkstock = false;

    $dateid = "left(head.dateid,10) as dateid";
    $orderby = "order by dateid desc, docno desc";

    if ($searchfilter == "") $limit = 'limit 150';
    $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
    $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.rem'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    if ($linkstock) {
      if ($group == '') {
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref, head.ourref';
      }
    }
    $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $lstat as status, $lstatcolor as statuscolor,$rem
    head.createby,head.editby,head.viewby,num.postedby,
     head.yourref, head.ourref $lfield
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $ljoin
     " . $join . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and num.bref <> 'SJS' 
     $group
     union all
     select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid,$gstat as status,$gstatcolor as statuscolor,$rem
     head.createby,head.editby,head.viewby, num.postedby,
      head.yourref, head.ourref $gfield
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $gjoin
     " . $hjoin . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and num.bref <> 'SJS' 
     $group
    $orderby $limit";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    $prefix = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'doc=? and psection=?', ['SED', 'SJ']);
    if ($prefix != '') {
      $prefixes = explode(",", $prefix);
      $list = array();
      foreach ($prefixes as $key) {
        array_push($list, ['label' => $key, 'value' => $key]);
      }
      data_set($col2, 'selectprefix.options', $list);
    }
    $data = $this->coreFunctions->opentable("select '' as docno, '' as selectprefix");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
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
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
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

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    return $return;
  }


  public function createTab($access, $config)
  {
    $action = 0;
    $itemdesc = 1;
    $uom = 2;
    $isqty = 3;
    $weight = 4;
    $isamt = 5;
    $qa = 6;

    $gridcolumn = ['action', 'itemdesc', 'uom', 'isqty', 'weight', 'isamt', 'qa'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $fields = ['totalcharges'];
    $col2 = $this->fieldClass->create($fields);
    $fields = ['totalweight'];
    $col3 = $this->fieldClass->create($fields);
    $gridheadinput = ['col0' => [], 'col1' => $col1, 'col2' => $col2, 'col3' => $col3];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $gridcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'total' => 'ext'],
        'headgridbtns' => ['viewdistribution', 'viewref'],
        'gridheadinput' => $gridheadinput,
      ],
    ];

    $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'wbcharges', 'access' => 'charges'], 'label' => 'CHARGES'];
    $tab['customform2'] = ['event' => ['action' => 'customform', 'lookupclass' => 'wbreceived', 'access' => 'received'], 'label' => 'RECEIVED'];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width: 250px;whiteSpace: normal;min-width:250px;max-width:250px;';
    $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'width: 250px;whiteSpace: normal;min-width:250px;max-width:250px;';
    $obj[0][$this->gridname]['columns'][$weight]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$isamt]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$qa]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';



    $obj[0][$this->gridname]['columns'][$isqty]['label'] = 'Quantity';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$itemdesc]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$weight]['label'] = 'Weight';
    $obj[0][$this->gridname]['columns'][$weight]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$isamt]['label'] = 'Declared Value';

    $obj[0][$this->gridname]['columns'][$isqty]['checkfield'] = 'isserved';

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0]['inventory']['totalfield'] = 'isamt';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "SAVE ALL";
    $obj[2]['label'] = "DELETE ALL";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address', 'dprojectname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    //data_set($col1, 'client.action', 'customerlookup');

    data_set($col1, 'client.required', false);
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', ['terms', 'due'], ['yourref', 'ourref'], ['cur', 'forex'], 'dvattype'];
    $col2 = $this->fieldClass->create($fields);

    //col3
    $fields = ['rem', 'dacnoname', 'trnxtype'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dacnoname.label', 'AR Account');
    data_set($col3, 'dacnoname.lookupclass', 'AR');
    data_set($col3, 'trnxtype.required', true);

    $fields = ['consignee', 'conaddr', 'shipper', 'dwhname', 'dwhto'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'conaddr.readonly', true);
    data_set($col4, 'dwhname.label', 'From');
    data_set($col4, 'dwhto.label', 'To');
    data_set($col4, 'dwhname.required', true);
    data_set($col4, 'dwhto.required', true);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function defaultheaddata($params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = '';
    $data[0]['dateid'] = date('Y-m-d');
    $data[0]['due'] = date('Y-m-d');
    $data[0]['client'] = 'CL0000000000001';
    $data[0]['clientname'] = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['client']]);
    $data[0]['address'] = $this->coreFunctions->getfieldvalue('client', 'addr', 'client=?', [$data[0]['client']]);
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;
    $data[0]['dvattype'] = '';
    $data[0]['dacnoname'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';
    $data[0]['whto'] = '';
    $data[0]['whtoname'] = '';
    $data[0]['dwhto'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['deldate'] = date('Y-m-d');
    $data[0]['consigneeid'] = '';
    $data[0]['consignee'] = '';
    $data[0]['shipperid'] = '';
    $data[0]['conaddr'] = '';
    $data[0]['trnxtype'] = '';

    return $data;
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['tax'] = 12;
    $data[0]['dvattype'] = '';
    $data[0]['dacnoname'] = '';
    $data[0]['vattype'] = 'VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['wh'] = '';
    $data[0]['whname'] = '';
    $data[0]['dwhname'] = '';
    $data[0]['whto'] = '';
    $data[0]['whtoname'] = '';
    $data[0]['dwhto'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();

    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;

    // $data
    $data[0]['consigneeid'] = '';
    $data[0]['shipperid'] = '';
    $data[0]['conaddr'] = '';
    $data[0]['consignee'] = '';

    $data[0]['trnxtype'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $isapproved = $this->othersClass->isapproved($config['params']['trno'], "hcntnuminfo");
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value 
        from " . $this->tablenum . " 
        where doc=? and center=? and bref <> 'SJS'
        order by trno desc limit 1", [$doc, $center]);
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

    $qryselect = "select head.trno,head.docno,client.client,head.terms,head.cur,head.forex,
                          head.yourref,head.ourref,head.contra,coa.acnoname,'' as dacnoname,
                          left(head.dateid,10) as dateid,head.clientname,head.address,
                          date_format(head.createdate,'%Y-%m-%d') as createdate,head.rem,head.tax,
                          head.vattype,'' as dvattype,warehouse.client as wh,
                          warehouse.clientname as whname,'' as dwhname,left(head.due,10) as due,
                          date(head.deldate) as deldate,head.projectid,
                          ifnull(project.name,'') as projectname,'' as dprojectname,
                          ifnull(project.code,'') as projectcode,head.ewt,head.ewtrate,'' as dewt,
                          head.consigneeid,head.conaddr,cs.clientname as consignee,hinfo.trnxtype,
                          head.shipperid,sh.clientname as shipper,head.whto,whto.clientname as whtoname,
                          '' as dwhto ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        left join client as cs on cs.clientid=head.consigneeid
        left join client as sh on sh.clientid=head.shipperid
        left join client as whto on whto.client=head.whto
        where head.trno = ? and num.doc=? and num.center = ? and num.bref <> 'SJS'
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        left join client as cs on cs.clientid=head.consigneeid
        left join client as sh on sh.clientid=head.shipperid
        left join client as whto on whto.client=head.whto
         where head.trno = ? and num.doc=? and num.center=? and num.bref <> 'SJS' ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $gridheaddata = $this->gridheaddata($config);

      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }

      $receivedby = $this->coreFunctions->datareader("select receivedby as value from cntnum  where trno=?", [$trno]);

      $lblreceived_stat = $receivedby == "" ? true : false;
      $hideobj = ['lblreceived' => $lblreceived_stat];

      $hideheadergridbtns = [];

      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
        'gridheaddata' => $gridheaddata,
        'islocked' => $islocked,
        'isposted' => $isposted,
        'isnew' => false,
        'status' => true,
        'msg' => $msg,
        'hideobj' => $hideobj,
        'hideheadgridbtns' => $hideheadergridbtns
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed', 'gridheaddata' => []];
    }
  }


  public function gridheaddata($config)
  {
    $trno = $config['params']['trno'];

    $charges = $this->coreFunctions->datareader("
              select sum(totalcharges) as value
              from (select sum(info.weight+info.valamt+info.cumsmt+info.delivery) as totalcharges
                    from " . $this->head . " as head
                    left join cntnuminfo as info on info.trno=head.trno
                    where head.trno=?
                    union all
                    select sum(info.weight+info.valamt+info.cumsmt+info.delivery) as totalcharges
                    from " . $this->hhead . " as head
                    left join hcntnuminfo as info on info.trno=head.trno
                    where head.trno=?) as k", [$trno, $trno]);

    $weight = $this->coreFunctions->datareader("
              select sum(weight) as value
              from (select sum(info.weight) as weight
                    from " . $this->stock . "  as stock
                    left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
                    where stock.trno=?
                    union all
                    select sum(info.weight) as weight
                    from " . $this->hstock . " as stock
                    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                    where stock.trno=?) as s", [$trno, $trno]);

    if ($weight == '') $weight = 0;
    if ($charges == '') $charges = 0;

    return $this->coreFunctions->opentable("select FORMAT(" . $weight . ",2) as totalweight,FORMAT(" . $charges . ",2) as totalcharges");
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $companyid = $config['params']['companyid'];
    $data = [];
    $info = [];
    $dataothers = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], $config['params']['doc'], $companyid);
        } //end if
      }
    }
    if ($data['terms'] != '') {
      $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    } else {
      $data['due'] = null;
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->othersClass->getcreditinfo($config, $this->head);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->othersClass->getcreditinfo($config, $this->head);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $dataothers['trno'] = $head['trno'];
    $dataothers['trnxtype'] = $head['trnxtype'];
    $arrcols = array_keys($dataothers);
    foreach ($arrcols as $key) {
      $dataothers[$key] = $this->othersClass->sanitizekeyfield($key, $dataothers[$key]);
    }
    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataothers);
    } else {
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataothers, ['trno' => $head['trno']]);
    }
  } // end function



  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
    $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $table . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from delstatus where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function



  public function posttrans($config)
  {
    $trno = $config['params']['trno'];

    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1']);
      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      $stock = $this->openstock($trno, $config);


      $override = $this->othersClass->checkAccess($config['params']['user'], 1729);

      $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
      $islimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);
      if (floatval($islimit) == 0) {
        if ($override == '0') {
          $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
          $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
          $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
          $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);

          if ($cstatus <> 'ACTIVE') {
            $this->logger->sbcwritelog(
              $trno,
              $config,
              'POST',
              'Customer Status is not Active'
            );
            return ['status' => false, 'msg' => 'Posting failed. The customer`s status is not active.'];
          }

          if (floatval($crline) < floatval($totalso)) {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit.');
            return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
          }
        }
      }
      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        $return = $this->othersClass->posttranstock($config);
        return $return;
      }
    }
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno,stock.line,info.itemdesc,info.unit as uom,FORMAT(info.weight,2) as weight,
           FORMAT(stock.isamt,2) as isamt,ROUND(stock.isqty)  as isqty,
           FORMAT(stock.isqty,2)  as qty,FORMAT(stock.ext,2) as ext,round((stock.isqty - stock.qa)) as qa,'' as bgcolor,'' as errcolor, if(stock.qa>0,'true','false') as isserved ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
    where stock.trno =?
    UNION ALL
    " . $sqlselect . " FROM $this->hstock as stock
    left join $this->hhead as head on head.trno = stock.trno
    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
    where stock.trno =?";

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
    left join $this->head as head on head.trno = stock.trno
    left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
    where stock.trno =? and stock.line = ?
    ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
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

      default:
        return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ') SJ'];
        break;
    }
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1650;
    $startx = 100;
    $a = 0;

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so
     left join hsostock as s on s.trno = so.trno
     left join glstock as sstock on sstock.refx = s.trno and sstock.linex = s.line
     where sstock.trno = ?
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      foreach ($t as $key => $value) {
        //SO
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
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'sj']);
        $a = $a + 100;
      }
    }

    //SJ
    $qry = "
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), if(head.ms_freight<>0,concat('\rOther Charges: ',round(head.ms_freight,2)),''),'\r\r', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem,
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where head.trno=?
    group by head.docno, head.dateid, head.trno, ar.bal, head.ms_freight";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
          'w' => 400,
          'h' => 80,
          'type' => $t[0]->docno,
          'label' => $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as $key => $value) {
        //CR
        $sjtrno = $t[$key]->trno;
        $crqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'";
        $crdata = $this->coreFunctions->opentable($crqry, [$sjtrno, $sjtrno]);
        if (!empty($crdata)) {
          foreach ($crdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $crdata[$key2]->docno,
                'label' => $crdata[$key2]->rem,
                'color' => 'red',
                'details' => [$crdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $cmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid";
        $cmdata = $this->coreFunctions->opentable($cmqry, [$sjtrno, $sjtrno]);
        if (!empty($cmdata)) {
          foreach ($cmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $cmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' => $cmdata[$key2]->docno,
                'label' => $cmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$cmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $cmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
      }
    }
    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }
    switch ($action) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'SJCR');
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

  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];

    $wh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$trno]);
    $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);

    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['itemid'] = 0;
    $data['isqty'] = 0;
    $data['iss'] = 0;
    $data['uom'] = '';
    $data['isamt'] = 0;
    $data['ext'] = 0;
    $data['amt'] = 0;
    $data['disc'] = '';
    $data['itemname'] = '';
    $data['itemdesc'] = '';
    $data['barcode'] = '';
    $data['rem'] = '';
    $data['unit'] = '';
    $data['weight'] = '0';
    $data['wh'] = $wh;
    $data['whid'] = $whid;
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    if ($config['params']['line'] != 0) {
      $this->additem('update', $config);
      $data = $this->openstockline($config);
      $gridheaddata = $this->gridheaddata($config);
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata];
    } else {
      $data = $this->additem('insert', $config);
      $gridheaddata = $this->gridheaddata($config);
      if ($data['status'] == true) {
        return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata, 'reloadhead' => true];
      } else {
        return ['row' => $data['data'], 'status' => false, 'msg' => $data['msg']];
      }
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      if ($value['line'] != 0) {
        $this->additem('update', $config);
      } else {
        $this->additem('insert', $config);
      }
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
      }
    }

    $gridheaddata = $this->gridheaddata($config);
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function



  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $line = $config['params']['data']['line'];
    $itemname = $config['params']['data']['itemdesc'];
    $qty = $config['params']['data']['isqty'];
    $uom = $config['params']['data']['uom'];
    $isamt = $config['params']['data']['isamt'];
    $weight = $config['params']['data']['weight'];
    $ext = $config['params']['data']['ext'];
    $data = [
      'trno' => $trno,
      'line' => $line,
      'isqty' => $qty,
      'qty' => $qty,
      'isamt' => $isamt,
      'ext' => $ext
    ];

    $stockinfo = [
      'trno' => $trno,
      'line' => $line,
      'itemdesc' => $itemname,
      'unit' => $uom,
      'weight' => $weight
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    foreach ($stockinfo as $key => $value) {
      $stockinfo[$key] = $this->othersClass->sanitizekeyfield($key, $stockinfo[$key], $config['params']['doc'], $companyid);
    }


    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];

      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";

      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if (
        $line == ''
      ) {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;
      $stockinfo['line'] = $line;
      if ($this->coreFunctions->sbcinsert($this->stock, $data)) {
        $config['params']['line'] = $line;

        $this->coreFunctions->sbcinsert('stockinfo', $stockinfo);

        $data =  $this->openstockline($config);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line: ' . $line . ' Particulars: ' . $itemname . ' Amt: ' . $ext);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
      $this->coreFunctions->sbcupdate('stockinfo', $stockinfo, ['trno' => $trno, 'line' => $data['line']]);
      $return = true;
    }
    return $return;
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $msg = 'Successfully deleted.';

    if ($this->companysetup->getserial($config['params'])) {
      $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
      foreach ($data2 as $key => $value) {
        $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
      }
    }

    $applied = $this->coreFunctions->opentable("select stock.trno,stock.line,info.itemdesc from " . $this->stock . " as stock left join stockinfo as info on info.trno=stock.trno and info.line=stock.line where stock.trno=?", [$trno]);
    if (count($applied) > 0) {
      $status = false;
      $msg = "Can't delete, item " . $applied[0]->itemdesc . " already applied in Loading List";
      goto ExitHere;
    }

    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');

    ExitHere:
    $gridheaddata = $this->gridheaddata($config);
    return ['status' => $status, 'msg' => $msg, 'inventory' => [], 'gridheaddata' => $gridheaddata];
  }


  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc in ('SJ','BO') and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc in ('SJ','BO') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    $result = $this->coreFunctions->execqry("update hsostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');

    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and iss>qa", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and qa<>0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx);
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx);
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx);
    }

    return $result;
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];

    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $msg = 'Item was successfully deleted.';
    $status = true;


    if ($data[0]->isserved == 'true') {
      $status = false;
      $msg = "Can't delete " . $data[0]->itemdesc . ', already applied in Loading List';
      goto ExitHere;
    }

    if ($line != 0) {
      if ($this->companysetup->getserial($config['params'])) {
        $this->othersClass->deleteserialout($trno, $line);
      }

      $qry = "delete from " . $this->stock . " where trno=? and line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
      $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);

      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Item:' . $data[0]->itemdesc . ' Qty:' . $data[0]->isqty);
    }

    ExitHere:
    $gridheaddata = $this->gridheaddata($config);
    return ['status' => $status, 'msg' => $msg, 'gridheaddata' => $gridheaddata];
  } // end function 

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $totalar = 0;
    $ewt = 0;
    $ewtamt = 0;
    $isvatexsales = $this->companysetup->getvatexsales($config['params']);

    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,
                    sum(info.weight + info.valamt + info.cumsmt + info.delivery) as ext,
                    "" as asset, "" as revenue,"" as expense,head.projectid,
                    client.rev,head.taxdef,head.deldate,head.ewt,head.ewtrate 
          from ' . $this->head . ' as head 
          left join client on client.client = head.client
          left join cntnuminfo as info on info.trno=head.trno
          where head.trno=?
          group by head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,
         head.projectid,client.rev,head.taxdef,head.deldate,head.ewt,head.ewtrate';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SA1']);
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

        if ($vat != 0) {
          if ($isvatexsales) {
            $tax = number_format(($stock[$key]->ext * $tax2), 4, '.', '');
            $totalar = $totalar + $stock[$key]->ext;
          } else {
            $tax = number_format(($stock[$key]->ext / $tax1), 4, '.', '');
            $tax = number_format($stock[$key]->ext - $tax, 4, '.', '');
            $totalar = $totalar + number_format($stock[$key]->ext, 4, '.', '');
          }
        }

        if ($stock[$key]->revenue != '') {
          $revacct = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct = $stock[$key]->rev;
          }
        }

        $expense = isset($stock[$key]->expense) ? $stock[$key]->expense : '';

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => number_format($stock[$key]->ext, 2, '.', ''),
          'ar' => $stock[$key]->taxdef == 0 ? number_format($stock[$key]->ext, 2, '.', '') : 0,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct,
          'expense' => $expense,
          'tax' =>  $stock[$key]->taxdef == 0 ? $tax : 0,
          'discamt' => 0,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => 0,
          'fcost' => 0,
          'projectid' => $stock[$key]->projectid,
          'deldate' => $stock[$key]->deldate,
          'ewt' => $ewt
        ];


        if ($isvatexsales) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
      }
    }

    //entry ar and vat if with default tax    
    $taxdef = $this->coreFunctions->getfieldvalue($this->head, "taxdef", "trno=?", [$trno]);
    if ($taxdef != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$d[0]->contra]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => (($totalar + $taxdef) * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $totalar + $taxdef, 'fcr' => 0];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ["TX2"]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'cr' => ($taxdef * $d[0]->forex), 'db' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $taxdef, 'fcr' => 0];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
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
        $this->acctg[$key]['poref'] = $this->acctg[$key]['poref'];
        $this->acctg[$key]['podate'] = $this->acctg[$key]['podate'];
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }

      //checking for 0.01 discrepancy
      $variance = $this->coreFunctions->datareader("select sum(db-cr) as value from " . $this->detail . " where trno=?", [$trno], '', true);
      if (abs($variance) == 0.01) {
        $taxamt = $this->coreFunctions->datareader("select d.cr as value from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and coa.alias='TX2'", [$trno], '', true);
        if ($taxamt != 0) {
          $salesentry = $this->coreFunctions->opentable("select d.line from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and left(coa.alias,2)='SA'  order by d.line desc limit 1", [$trno]);
          if ($salesentry) {
            $this->coreFunctions->execqry("update " . $this->detail . " set cr=cr+" . $variance . " where trno=" . $trno . " and line=" . $salesentry[0]->line);
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'FORCE BALANCE WITH 0.01 VARIANCE');
          }
        }
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    $ewtamt = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }

    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ar'] - $ewtamt) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : ($params['ar'] - $ewtamt), 'fcr' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        if ($params['expense'] == '') {
          $cogs = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        } else {
          $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['expense']]);
        }
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }


    if (floatval($params['tax']) != 0) {
      //sales
      $sales = ($params['ext'] - $params['tax']);
      // $sales  = $sales + $params['discamt'];
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
      // output tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sales
      $sales = ($params['ext']);
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function distributionvatex($params, $config)
  {
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }

    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ar'] + $params['tax']) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ar'] + $params['tax'], 'fcr' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if ($this->companysetup->getissalesdisc($config['params'])) {
      if (floatval($params['discamt']) != 0) {
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        $cogs =  $params['expense'] == 0 ? $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']) : $params['expense'];
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //sales
    $sales = $params['ext'];
    if ($this->companysetup->getissalesdisc($config['params'])) {
      $sales = round(($sales + $params['discamt']), 2);
    }

    if (floatval($sales) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    // output tax
    if ($params['tax'] != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  public function getpaysummaryqry($config)
  {
    return "
    select arledger.docno,arledger.trno,arledger.line,ctbl.clientname,ctbl.client,forex.cur,forex.curtopeso as forex,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,arledger.db,arledger.cr, arledger.bal ,left(arledger.dateid,10) as dateid,
    abs(arledger.fdb-arledger.fcr) as fdb,glhead.yourref,gldetail.rem as drem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,
    gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate,coa.alias,gldetail.postdate,glhead.tax,glhead.vattype,glhead.ewt,glhead.ewtrate,a.client as agent from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid
    left join client as a on a.clientid = glhead.agentid
    left join forex_masterfile as forex on forex.line = ctbl.forexid
    where cntnum.trno = ? and arledger.bal<>0";
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
    $dataparams = $config['params']['dataparams'];
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }

  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $deci = $this->companysetup->getdecimal('price', $config['params']);
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = $this->othersClass->sanitizekeyfield('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])));

      $computedata = $this->othersClass->computestock(
        $damt * $head['forex'],
        $data[$key]->disc,
        $dqty,
        $data[$key]->uomfactor,
        0
      );

      $computedata['amt']  = number_format($computedata['amt'], $deci, '.', '');
      $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);

      $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class