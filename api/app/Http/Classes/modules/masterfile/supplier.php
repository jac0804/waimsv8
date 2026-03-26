<?php

namespace App\Http\Classes\modules\masterfile;

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

class supplier
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SUPPLIER LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'SL';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $tagging = "issupplier";

  private $fields = [
    'client',
    'clientname',
    'addr',
    'start',
    'status',
    'tel',
    'tel2',
    'email',
    'terms',
    'contact',
    'tin',
    'fax',
    'groupid',
    'rem',
    'type',
    'disc',
    'category',
    'area',
    'province',
    'region',
    'zipcode',
    'forexid',
    'iscustomer',
    'issupplier',
    'isagent',
    'iswarehouse',
    'isinactive',
    'isemployee',
    'isdepartment',
    'isnonbdo',
    'picture',
    'iscontractor',
    'tax',
    'vattype',
    'bstyle',
    'ewtid',
    'accountname',
    'accountnum',
    'bstyle',
    'regnum',
    'prefix',
    'ispickupdate',
    'floor',
    'brgy',
    'acctadvances'
  ];
  private $otherinfo = ['city', 'street'];

  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isinactive', 'isemployee', 'isdepartment', 'iscontractor', 'ispickupdate', 'isnonbdo'];
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
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
      'view' => 32,
      'edit' => 33,
      'new' => 34,
      'save' => 35,
      'change' => 36,
      'delete' => 37,
      'print' => 38,
      'load' => 31,
      'artab' => 2742,
      'aptab' => 2743,
      'invtab' => 2744,
      'defaultaddresstab' => 2745,
      'setupaddresstab' => 2746

    );

    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $listclient = 1;
    $listclientname = 2;
    $listaddr = 3;
    $notes = 4;
    $cat_name = 5;
    $businessnature = 6;
    $tel = 7;
    $fax = 8;
    $contact = 9;
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr', 'notes', 'cat_name', 'businessnature', 'tel', 'fax', 'contact'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $cols[$tel]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    if ($config['params']['companyid'] != 6) { //not mitsukoshi
      $cols[$cat_name]['type'] = 'coldel';
    }

    if ($config['params']['companyid'] != 16) { //not ati
      $cols[$businessnature]['type'] = 'coldel';
    }

    if ($config['params']['companyid'] != 24) { //not goodfound
      $cols[$tel]['type'] = 'coldel';
      $cols[$fax]['type'] = 'coldel';
      $cols[$contact]['type'] = 'coldel';
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = $config['params']['date1'];
    $date2 = $config['params']['date2'];
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $company = $config['params']['companyid'];
    $limit = "limit " . $this->companysetup->getmasterlimit($config['params']);
    $search = $config['params']['search'];
    if ($company == 10 || $company == 12) { //afti & afti usd
      if ($search == '') {
        $limit = 'limit ' . $this->companysetup->getmasterlimit($config['params']);
      }
    }
    $condition = "";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $addedfields = '';
    switch ($company) {
      case 16: //ati
        $addedfields = ", (select ifnull(group_concat(businessnature separator ',\n\r'),'') from othermaster where isbusinessnature=1 and clientid=client.clientid) as businessnature";
        break;
      case 24: //goodfound
        $addedfields = ",client.tel,client.fax,client.contact";
        break;
    }


    $qry = "select client.clientid,client.client,client.clientname,
    client.addr,category.cat_name, client.rem as notes" .  $addedfields . "
    from client 
    left join category_masterfile as category on category.cat_id = client.category
    where (issupplier =1 or iscontractor =1) " . $condition . " " . $filtersearch . "
    order by client " . $limit;

    $data = $this->coreFunctions->opentable($qry);
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
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $btns = array(
          'load',
          'print',
          'logs',
          'backlisting',
          'toggleup',
          'toggledown'
        );
      }
    }

    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'supplier', 'title' => 'SUPPLIER_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $artab_access = $this->othersClass->checkAccess($config['params']['user'], 2742);
    $aptab_access = $this->othersClass->checkAccess($config['params']['user'], 2743);
    $invtab_access = $this->othersClass->checkAccess($config['params']['user'], 2744);
    $defaultaddresstab_access = $this->othersClass->checkAccess($config['params']['user'], 2745);
    $setaddresstab_access = $this->othersClass->checkAccess($config['params']['user'], 2746);
    $unpaidarap_access = $this->othersClass->checkAccess($config['params']['user'], 2993);
    $commisionlist = $this->othersClass->checkAccess($config['params']['user'], 5017);
    $itemlist = $this->othersClass->checkAccess($config['params']['user'], 5024);
    $itementry = $this->othersClass->checkAccess($config['params']['user'], 5117);

    $ar = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewar']];
    $ap = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewap']];
    $unpaidarap = ['customform' => ['action' => 'customform', 'lookupclass' => 'unpaidar']];

    if ($systemtype == "FAMS") {
      $inv = ['customform' => ['action' => 'customform', 'lookupclass' => 'inventoryhistory_supplier_tab']];
    } else {
      $inv = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewsupplierinv']];
    }
    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingdefault']];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrybillingaddr', 'label' => 'BILLING/SHIPPING']];
    $billship = $this->tabClass->createtab($tab, []);

    $return = [];

    if ($systemtype == 'MIS' || $systemtype == 'MISPOS') {
      if ($invtab_access != 0) {
        $return['INVENTORY HISTORY'] = ['icon' => 'fa fa-envelope', 'customform' => $inv];
      }
    } else {
      if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12 || $config['params']['companyid'] == 16) {
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycustomercontactperson', 'label' => 'CONTACT PERSON']];
        $contactperson = $this->tabClass->createtab($tab, []);

        $return['CONTACT PERSON'] = ['icon' => 'fa fa-list-ul', 'tab' => $contactperson];
      }

      if ($artab_access != 0) {
        $return['ACCOUNT RECEIVABLE HISTORY'] = ['icon' => 'fa fa-envelope', 'customform' => $ar];
      }
      if ($aptab_access != 0) {
        $return['ACCOUNT PAYABLE HISTORY'] = ['icon' => 'fa fa-envelope', 'customform' => $ap];
      }
      if ($invtab_access != 0) {
        $return['INVENTORY HISTORY'] = ['icon' => 'fa fa-envelope', 'customform' => $inv];
      }

      if ($unpaidarap_access != 0) {
        $return['UNPAID AR/AP'] = ['icon' => 'fa fa-coins', 'customform' => $unpaidarap];
      }
    }


    switch ($config['params']['companyid']) {
      case 6:
        break;
      case 16:
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrynatureofbusiness', 'label' => 'NATURE OF BUSINESS']];
        $natureofbusiness = $this->tabClass->createtab($tab, []);
        $return['NATURE OF BUSINESS'] = ['icon' => 'fa fa-building', 'tab' => $natureofbusiness];
        break;
      default:
        if ($config['params']['companyid'] != 56) { //not homeworks
          if ($defaultaddresstab_access != 0) {
            $return['DEFAULT SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
          }
          if ($setaddresstab_access != 0) {
            $return['SHIPPING/BILLING ADDRESS SETUP'] = ['icon' => 'fa fa-address-book', 'tab' => $billship];
          }
        }
        break;
    }

    switch ($config['params']['companyid']) {
      case 16:
      case 8:
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
        $attach = $this->tabClass->createtab($tab, []);
        $return['ATTACHMENT'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];
        break;
      case 56: //homeworks
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'commissionlist', 'label' => 'COMMISSION LIST']];
        $attach = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'itemlist', 'label' => 'ITEM LIST']];
        $attach2 = $this->tabClass->createtab($tab, []);

        if ($commisionlist) {
          $return['COMMISSION LIST'] = ['icon' => 'fa fa-hand-holding-usd', 'tab' => $attach];
        }
        if ($itemlist) {
          $return['ITEM LIST'] = ['icon' => 'fa fa-clipboard-list sub_menu_ico', 'tab' => $attach2];
        }
        break;
      case 19: //housegem
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'itementry', 'label' => 'ITEM ENTRY']];
        $attach2 = $this->tabClass->createtab($tab, []);
        if ($itementry) {
          $return['ITEM ENTRY'] = ['icon' => 'fa fa-clipboard-list sub_menu_ico', 'tab' => $attach2];
        }
        break;
       case 63://ericco
          $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysku', 'label' => 'SKU']];
          $sku = $this->tabClass->createtab($tab, []);
          $return['ITEM LIST'] = ['icon' => 'fa fa-list-ul', 'tab' => $sku];
        break;  
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];
    return [];
  }

  public function createtabbutton($config)
  {

    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $inactivesupplierbutton = $this->othersClass->checkAccess($config['params']['user'], 5028); //homeworks

    if ($companyid == 10 || $companyid == 12) {
      $fields = ['client', 'clientname', 'start', 'clientstatus', 'dvattype', 'dewt'];
    } else {
      $fields = ['client', 'clientname', 'addr', 'start', 'clientstatus', 'tel', 'tel2', 'email'];
    }

    switch ($companyid) {
      case 8:
        array_push($fields, 'accountname', 'accountnum');
        break;
      case 16: //ati
        array_push($fields, 'dvattype', 'prefix');
        break;
      case 36: //rozlab
        array_push($fields, 'dvattype');
        break;
      case 56: //homeworks
        array_push($fields, 'dvattype', 'dewt', 'acctadvances');
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Supplier Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'supplier');
    data_set($col1, 'client.action', 'lookupledgerclient');

    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'clientname.required', true);
    data_set($col1, 'addr.type', 'cinput');
    data_set($col1, 'tel.type', 'cinput');
    data_set($col1, 'tel2.type', 'cinput');
    data_set($col1, 'email.type', 'cinput');

    data_set($col1, 'prefix.label', 'PO Prefix');

    if ($companyid == 10 || $companyid == 12) {
      data_set($col1, 'dvattype.label', 'Tax and Charges');
      data_set($col1, 'dewt.label', 'Tax Code');
    }

    data_set($col1, 'clientstatus.label', 'Supplier Status');

    if ($companyid == 10 || $companyid == 12) {
      $fields = ['terms', 'dcur', 'type', 'tin', 'dcategory'];
    } else {
      $fields = ['terms', 'contact', 'tin', 'fax', 'groupid', 'rem', 'disc', 'type'];
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'terms.lookupclass', 'ledgerterms');
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'rem.required', false);
    data_set($col2, 'type.required', false);
    data_set($col2, 'type.type', 'input');
    data_set($col2, 'type.class', 'cstype');

    data_set($col2, 'groupid.lookupclass', 'lookupclientgroupledger');
    data_set($col2, 'groupid.action', 'lookupclientgroupledger');
    data_set($col2, 'groupid.class', 'csgroup ');
    data_set($col2, 'groupid.readonly', false);

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'tin.type', 'cinput');
    data_set($col2, 'fax.type', 'cinput');
    data_set($col2, 'disc.type', 'cinput');

    if ($companyid == 10 || $companyid == 12) {
      data_set($col2, 'type.label', 'Organizational Structure');
      data_set($col2, 'type.type', 'lookup');
      data_set($col2, 'type.lookupclass', 'lookuporgstructure');
      data_set($col2, 'type.action', 'lookuprandom');
      data_set($col2, 'dcategory.label', 'Business Style');
      $fields = ['rem'];
    } else {
      $fields = ['dcategory', 'dcur', 'area', 'province', 'region', 'zipcode'];
      if ($companyid == 16) {
        array_push($fields, 'businesstype', 'regnum');
      }
      if ($companyid == 56) { //homeworks
        array_push($fields, 'businesstype', 'city', 'brgy', 'street', 'floor');
      }
    }
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'zipcode.type', 'cinput');
    data_set($col3, 'businesstype.label', 'Nature of Business');
    data_set($col3, 'businesstype.type', 'cinput');
    data_set($col3, 'businesstype.readonly', false);
    data_set($col3, 'businesstype.class', 'csbusinesstype');
    data_set($col3, 'businesstype.name', 'bstyle');
    data_set($col3, 'regnum.label', '2303 Registration Number');
    data_set($col3, 'brgy.type', 'input');
    data_set($col3, 'city.label', 'City');
    data_set($col3, 'floor.required', false);


    $fields = ['picture', 'issupplier', 'iscustomer', 'isagent', 'iswarehouse', 'isinactive', 'isemployee', 'isdepartment', 'iscontractor'];
    if ($companyid == 16) {
      array_push($fields, 'ispickupdate');
    }
    if ($companyid == 56) { //homeworks
      array_push($fields, 'isnonbdo');
    }

    if ($inactivesupplierbutton) { //homeworks
      array_push($fields, 'submit'); // inactivesupplier
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'issupplier.class', 'csissupplier sbccsreadonly');
    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'supplier');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');

    if ($companyid == '8') {
      data_set($col4, 'isagent.label', 'Admin');
    }

    if ($companyid == '56') { //homeworks
      data_set($col4, 'submit.label', 'INACTIVE SUPPLIER AND ITEM LIST');
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }


  public function newclient($config)
  {
    $companyid = $config['params']['companyid'];

    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['status'] = '';
    $data[0]['terms'] = '';
    $data[0]['contact'] = '';
    $data[0]['tin'] = '';
    $data[0]['tel'] = '';
    $data[0]['fax'] = '';
    $data[0]['tel2'] = '';
    $data[0]['email'] = '';
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['category'] = '';
    $data[0]['categoryname'] = '';
    $data[0]['groupid'] = '';
    $data[0]['disc'] = '';
    $data[0]['area'] = '';
    $data[0]['province'] = '';
    $data[0]['region'] = '';
    $data[0]['type'] = '';
    $data[0]['rem'] = '';
    $data[0]['zipcode'] = '';
    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '1';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isemployee'] = '0';
    $data[0]['isdepartment'] = '0';
    $data[0]['iscontractor'] = '0';
    $data[0]['ispickupdate'] = '0';
    $data[0]['forexid'] = '';
    $data[0]['cur'] = '';
    $data[0]['picture'] = '';
    $data[0]['bstyle'] = '';
    $data[0]['ewtid'] = 0;
    $data[0]['tax'] = '0';
    $data[0]['dvattype'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewt'] = '';
    $data[0]['ewtrate'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['accountname'] = '';
    $data[0]['accountnum'] = '';
    $data[0]['bstyle'] = '';
    $data[0]['regnum'] = '';
    $data[0]['prefix'] = '';
    $data[0]['floor'] = '';
    $data[0]['street'] = '';
    $data[0]['city'] = '';
    $data[0]['brgy'] = '';
    $data[0]['acctadvances'] = '';
    $data[0]['isnonbdo'] = '0';

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    switch ($action) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'submit':
        return $this->inactive($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where issupplier=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = 'client.clientid, client.client as docno,client.isinactive';

    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    $qryselect = "select " . $fields . ", ifnull(category.cat_name, '') as categoryname,
                    ifnull(forex.cur, '') as cur,
                    client.ewtid, '' as dewt,
                    ifnull(ewt.code, '') as ewt,
                    ifnull(ewt.rate, '') as ewtrate,
                    info.city,info.street";

    $qry = $qryselect . " from client
        left join clientinfo as info on info.clientid = client.clientid  
        left join category_masterfile as category on category.cat_id = client.category
        left join forex_masterfile as forex on forex.line = client.forexid
        left join ewtlist as ewt on ewt.line = client.ewtid
        left join coa on coa.acnoname = client.acctadvances
        where client.clientid = ? and issupplier = 1";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $hideobj = [];
      if ($companyid == 56) { // homework
        $submit = (isset($head[0]->isinactive) && $head[0]->isinactive != 0) ? true : false;
        $hideobj['submit'] = $submit;
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj, 'action' => 'backlisting'];
    } else {
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['clientname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified.'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    $companyid = $config['params']['companyid'];
    $otherdata = [];

    if ($isupdate) {
      unset($this->fields[0]);
    }
    $clientid = 0;
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if ($key == 'floor') {
          if (is_null($data[$key])) $data[$key] = '';
          continue;
        }
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], 'SUPPLIER', $companyid);
        } //end if    
      }
    }

    foreach ($this->otherinfo as $key) {
      if (array_key_exists($key, $head)) {
        $otherdata[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $otherdata[$key] = $this->othersClass->sanitizekeyfield($key, $otherdata[$key], 'SUPPLIER', $companyid);
        } //end if    
      }
    }

    $msg = '';

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    $data['ismirror'] = 0;

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $cinfochk = $this->coreFunctions->getfieldvalue("clientinfo", "clientid", "clientid=?", [$head['clientid']]);
      $otherdata['ismirror'] = 0;
      if (!$cinfochk) {
        $otherdata['clientid'] = $head['clientid'];
        if ($head['clientid'] != 0) $this->coreFunctions->sbcinsert('clientinfo', $otherdata);
      } else {
        if ($head['clientid'] != 0) $this->coreFunctions->sbcupdate('clientinfo', $otherdata, ['clientid' => $head['clientid']]);
      }

      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['issupplier'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $otherdata['clientid'] = $clientid;
      $otherdata['ismirror'] = 0;
      if ($otherdata['clientid'] != 0) $this->coreFunctions->sbcinsert('clientinfo', $otherdata);
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  issupplier=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  issupplier=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select lahead.trno as value from lahead where client=?
            union all 
            select glhead.trno as value from glhead where clientid=?
            union all
            select pohead.trno as value from pohead where client=?
            union all
            select hpohead.trno as value from hpohead where client=? limit 1";
    $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $client]);
    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and issupplier=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry("delete from othermaster where clientid=?", 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $client);
    $this->othersClass->deleteattachments($config); // attachment delete
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  //printout
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
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    if ($companyid == 40) { // cdo
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }

    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }


  public function inactive($config)
  {
    $trno = $config['params']['trno'];
    $inactiveclient = $this->coreFunctions->getfieldvalue('client', 'isinactive', 'clientid=?', [$trno]);
    $qry = "select item.itemname,item.isinactive  from  item
         left join client as cl on cl.clientid=item.supplier where cl.clientid=$trno";
    $result = $this->coreFunctions->opentable($qry);
    // 26 rows
    if ($inactiveclient == 0) {
      $this->coreFunctions->sbcupdate($this->head,   ['isinactive' => 1], ['clientid' => $trno]);
      foreach ($result as $key => $value) {
        $this->coreFunctions->sbcupdate('item', ['isinactive' => 1], ['supplier' => $trno, 'itemname' => $value->itemname]);
      }
    }
    return ['row' => [], 'status' => true, 'msg' => 'Success', 'backlisting' => true];
  }
} //end class
