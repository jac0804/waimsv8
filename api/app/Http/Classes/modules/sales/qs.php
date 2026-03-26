<?php

namespace App\Http\Classes\modules\sales;

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
use App\Http\Classes\modules\crm\ld;
use Symfony\Component\VarDumper\VarDumper;

class qs
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'QUOTATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'qshead';
  public $hhead = 'hqshead';
  public $stock = 'qsstock';
  public $hstock = 'hqsstock';
  public $sstock = 'qtstock';
  public $hsstock = 'hqtstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'dateid',  'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur',  'wh', 'address', 'agent', 'branch', 'deptid', 'position', 'agentcno', 'industry', 'shipid', 'billid', 'deldate', 'tax', 'vattype', 'shipcontactid', 'billcontactid', 'industryid', 'revisionref', 'projid'];
  public $except = ['trno', 'dateid', 'due', 'deldate'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'Primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'Primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'Primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'Primary'],
  ];
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
      'view' => 2453,
      'edit' => 2454,
      'new' => 2455,
      'save' => 2456,
      // 'change' => 2457, remove change doc
      'delete' => 2458,
      'print' => 2459,
      'lock' => 2460,
      'unlock' => 2461,
      'changeamt' => 2462,
      'post' => 2463,
      'unpost' => 2464,
      'additem' => 2465,
      'edititem' => 2466,
      'deleteitem' => 2467
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclient', 'listclientname', 'yourref', 'invoiceno', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $allowduplicate = $this->othersClass->checkAccess($config['params']['user'], 4626);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($allowduplicate) {
        array_push($stockbuttons, 'duplicatedoc');
      }
    }
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $cols[6]['label'] = 'Customer PO';
      $cols[4]['label'] = 'Customer';
    } else {
      $cols[6]['label'] = 'PO #';
      $cols[4]['type'] = 'coldel';
    }

    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $isshortcutpo = $this->companysetup->getisshortcutpo($config['params']);

    $fields = [];
    if ($isshortcutpo) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 2455);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }
    array_push($fields, 'selectprefix', 'docno');
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'pickpo.label', 'PICK SALES ACTIVITY');
    data_set($col1, 'pickpo.action', 'pendingopsummary');
    data_set($col1, 'pickpo.lookupclass', 'pendingopsummaryshortcut');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick SALES ACTIVITY?');

    data_set($col1, 'docno.type', 'input');
    data_set($col1, 'docno.label', 'Search');
    data_set($col1, 'selectprefix.label', 'Search by');
    data_set($col1, 'selectprefix.type', 'lookup');
    data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
    data_set($col1, 'selectprefix.action', 'lookupsearchby');

    // $list = array(
    //   ['label' => '', 'value' => ''],
    //   ['label' => 'Item Code', 'value' => 'barcode'],
    //   ['label' => 'Brand', 'value' => 'brand'],
    //   ['label' => 'Model', 'value' => 'modelname']
    // );
    // data_set($col1, 'selectprefix.options', $list);

    $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
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
    $limit = '';
    $join = '';
    $hjoin = '';
    $addparams = '';


    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null ';
        break;
    }
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
        if ($searchfilter == "") $limit = 'limit 25';
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        break;
    }

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if ($test['selectprefix'] != "") {
        switch ($test['selectprefix']) {
          case 'Item Code':
            $addparams = " and (item.partno like '%" . $test['docno'] . "%' or item2.partno like '%" . $test['docno'] . "%')";
            break;
          case 'Item Name':
            $addparams = " and (item.itemname like '%" . $test['docno'] . "%' or item2.itemname like '%" . $test['docno'] . "%')";
            break;
          case 'Model':
            $addparams = " and (model.model_name like '%" . $test['docno'] . "%' or model2.model_name like '%" . $test['docno'] . "%')";
            break;
          case 'Brand':
            $addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%' or brand2.brand_desc like '%" . $test['docno'] . "%')";
            break;
          case 'Item Group':
            $addparams = " and (p.name like '%" . $test['docno'] . "%' or p2.name like '%" . $test['docno'] . "%')";
            break;
          case 'Customer Code':
            $addparams = " and (head.client like '%" . $test['docno'] . "%')";
            break;
        }

        if (isset($test)) {
          $join = " left join qsstock on qsstock.trno = head.trno left join qtstock on qtstock.trno = head.trno 
          left join item on item.itemid = qsstock.itemid left join item as item2 on item2.itemid = qtstock.itemid
          left join model_masterfile as model on model.model_id = item.model 
          left join model_masterfile as model2 on model2.model_id = item2.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
          left join projectmasterfile as p on p.line = item.projectid 
          left join projectmasterfile as p2 on p2.line = item2.projectid ";

          $hjoin = " left join hqsstock as qsstock on qsstock.trno = head.trno left join hqtstock as qtstock on qtstock.trno = head.trno 
          left join item on item.itemid = qsstock.itemid left join item as item2 on item2.itemid = qtstock.itemid
          left join model_masterfile as model on model.model_id = item.model 
          left join model_masterfile as model2 on model2.model_id = item2.model
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
          left join projectmasterfile as p on p.line = item.projectid 
          left join projectmasterfile as p2 on p2.line = item2.projectid ";
          $limit = '';
        }
      }
    }


    // if ($searchfilter != '') {
    //   $condition = $condition . " and (head.docno like '%" . $searchfilter . "%' or pf.docno like '%" . $searchfilter . "%' or head.client like '%" . $searchfilter . "%' or head.clientname like '%" . $searchfilter . "%' or head.yourref like '%" . $searchfilter . "%' or num.postedby like '%".$searchfilter."%' or head.createby like '%".$searchfilter."%' or head.editby like '%".$searchfilter."%' or head.viewby like '%".$searchfilter."%')";
    //   $limit = '';
    // }
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.client', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'pf.docno'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select head.dateid as date2,head.trno,head.docno,head.client,head.clientname,$dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby,head.yourref,pf.docno as invoiceno  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join proformainv as pf on pf.trno = head.trno " . $join . "  where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     union all
     select head.dateid as date2,head.trno,head.docno,head.client,head.clientname,$dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby,head.yourref,pf.docno as invoiceno  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join proformainv as pf on pf.trno = head.trno  " . $hjoin . " where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     order by date2 desc,docno $limit";

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

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $taxtab = $this->othersClass->checkAccess($config['params']['user'], 2863);
    $proformatab = $this->othersClass->checkAccess($config['params']['user'], 4050);
    $companyid = $config['params']['companyid'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
      $otherinfo = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewotherinfo']];
      $termstaxandcharges = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewtermstaxcharges']];
      $proformainvoice = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewproformainvoice']];
      $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];
      $viewleadtimesetting = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewleadtimesetting']];

      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryqscalllog', 'label' => 'Call Log Entry']];
      $call = $this->tabClass->createtab($tab, []);

      $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
      $return['INSTRUCTION'] = ['icon' => 'fa fa-info', 'customform' => $instructiontab];
      $return['LEAD TIME DURATION'] = ['icon' => 'fa fa-clock', 'customform' => $viewleadtimesetting];
      $return['QOUTATION VALIDITY'] = ['icon' => 'fa fa-question-circle', 'customform' => $otherinfo];
      if ($taxtab) {
        $return['TERMS, TAXES AND CHARGES'] = ['icon' => 'fa fa-file-invoice', 'customform' => $termstaxandcharges];
      }
      if ($proformatab) {
        $return['PROFORMA INVOICE'] = ['icon' => 'fa fa-receipt', 'customform' => $proformainvoice];
      }

      $return['CALL LOG ENTRY'] = ['icon' => 'fa fa-envelope', 'tab' => $call];
    }

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $action = 0;
    $itemdesc = 1;
    $isqty = 2;
    $isqty = 3;
    $uom = 4;
    $isamt = 5;
    $amt = 7;
    $disc = 6;
    $ext = 8;
    $ref = 9;
    $stock_projectname = 10;
    $brand_desc = 11;
    $model = 12;
    $noprint = 13;
    $itemname = 14;
    $barcode = 15;


    $gridcolumn = ['action', 'itemdescription', 'isqty', 'voidqty', 'uom', 'isamt', 'disc', 'amt', 'ext', 'ref', 'stock_projectname', 'brand_desc', 'model', 'noprint', 'itemname', 'barcode'];

    $headgridbtns = ['itemvoiding', 'viewref', 'viewitemstockinfo', 'viewdiagram'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $gridcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],
    ];


    $stockbuttons = ['save', 'delete', 'showbalance', 'iteminfo'];

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$action]['style'] = 'width:170px;whiteSpace: normal;min-width:170px;';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Price';
      $obj[0]['inventory']['columns'][$amt]['label'] = 'Amount';
      $obj[0]['inventory']['columns'][$amt]['type'] = 'label';
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
      $obj[0]['inventory']['columns'][$action]['style'] = 'text-align: left; width: 200px;whiteSpace: normal;min-width:200px;max-width: 200px;';
    }

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isqty]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'][$ref]['type'] = 'label';

    $obj[0]['inventory']['columns'][$brand_desc]['type'] = 'label';
    $obj[0]['inventory']['columns'][$brand_desc]['align'] = 'text-left';
    $obj[0]['inventory']['columns'][$brand_desc]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    $obj[0]['inventory']['columns'][$model]['type'] = 'label';
    $obj[0]['inventory']['columns'][$model]['align'] = 'text-left';
    $obj[0]['inventory']['columns'][$model]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    if ($companyid != 10 && $companyid != 12) { //not afti & not afti usd
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$brand_desc]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$model]['type'] = 'coldel';

    $obj[0]['inventory']['columns'][$stock_projectname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem',  'saveitem', 'deleteallitem', 'pendingop'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['lookupclass'] = 'additemwplus';

    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['docno', 'client', 'clientname', 'tin'];
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      array_push($fields, 'industry');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'client.addedparams', ['agent']);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'tin.class', 'sbccsreadonly');
    data_set($col1, 'industry.type', 'lookup');
    data_set($col1, 'industry.class', 'sbccsreadonly');
    data_set($col1, 'industry.lookupclass', 'lookupindustry');
    data_set($col1, 'industry.action', 'lookuprandom');

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col1, 'clientname.type', 'textarea');
    }

    $fields = [['dateid', 'terms'], 'due', 'dwhname', 'dagentname', 'agentcno'];
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $fields = ['dateid', 'due', 'deldate', 'dagentname', 'agentcno', 'position', 'category'];
    }
    $col2 = $this->fieldClass->create($fields);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col2, 'dagentname.label', 'Sales Person');
      data_set($col2, 'dateid.label', 'Create Date');
      data_set($col2, 'due.label', 'PO Process Date');
      data_set($col2, 'deldate.required', true);
      data_set($col2, 'position.type', 'cinput');
      data_set($col2, 'position.maxlength', '50');
      data_set($col2, 'category.label', 'Bus. Style');
      data_set($col2, 'category.type', 'input');
      data_set($col2, 'category.class', 'sbccsreadonly');
      $override = $this->othersClass->checkAccess($config['params']['user'], 4163);
      if ($override == '0') {
        data_set($col2, 'due.class', 'sbccsreadonly');
      }
    }
    data_set($col2, 'agentcno.class', 'sbccsreadonly');

    if ($companyid == 10 || $companyid == 12) {
      $fields = ['dbranchname', 'ddeptname', 'yourref', ['cur', 'forex'], 'revisionref', 'projid'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'dbranchname.required', true);
      data_set($col3, 'ddeptname.required', true);
      data_set($col3, 'yourref.required', false);
      data_set($col3, 'yourref.label', 'Customer PO');
      data_set($col3, 'ddeptname.label', 'Department');
    } else {
      $fields = [['yourref', 'ourref'], ['cur', 'forex']];
      $col3 = $this->fieldClass->create($fields);
    }

    $fields = ['rem', 'creditinfo'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.required', false);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }



  public function createnewtransaction($docno, $params)
  {
    $agent = "";
    $agentname = "";
    $branch = 0;
    $branchcode = "";
    $branchname = "";
    $deptid = 0;
    $dept = "";
    $deptname = "";
    $contactno = "";

    if ($params['companyid'] == 10 || $params['companyid'] == 12) {
      $salesperson_qry = "
      select 
        ifnull(ag.client, '') as agent, 
        ifnull(ag.clientname, '') as agentname, 
        ifnull(branch.clientid, 0) as branchid, 
        ifnull(branch.client, '') as branchcode, 
        ifnull(branch.clientname, '') as branchname,
        ifnull(dept.clientid, 0) as deptid, 
        ifnull(dept.client, '') as dept, 
        ifnull(dept.clientname, '') as deptname,
        ifnull(ag.tel2, '') as contactno
      from client as ag
      left join client as branch on branch.clientid = ag.branchid
      left join client as dept on dept.clientid = ag.deptid
      where ag.clientid = ?";
      $salesperson_res = $this->coreFunctions->opentable($salesperson_qry, [$params['adminid']]);
      if (!empty($salesperson_res)) {
        $agent = $salesperson_res[0]->agent;
        $agentname = $salesperson_res[0]->agentname;
        $branch = $salesperson_res[0]->branchid;
        $branchcode = $salesperson_res[0]->branchcode;
        $branchname = $salesperson_res[0]->branchname;
        $deptid = $salesperson_res[0]->deptid;
        $dept = $salesperson_res[0]->dept;
        $deptname = $salesperson_res[0]->deptname;
        $contactno = $salesperson_res[0]->contactno;
      }
    }

    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['agent'] = $agent;
    $data[0]['agentname'] = $agentname;
    $data[0]['branch'] = $branch;
    $data[0]['branchcode'] = $branchcode;
    $data[0]['branchname'] = $branchname;
    $data[0]['deptid'] = $deptid;
    $data[0]['dept'] = $dept;
    $data[0]['deptname'] = $deptname;
    $data[0]['dagentname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['address'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;

    $data[0]['position'] = '';
    $data[0]['agentcno'] = $contactno;
    $data[0]['industry'] = '';
    $data[0]['industryid'] = 0;
    $data[0]['tin'] = '';
    $data[0]['shipid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['optrno'] = '0';
    $data[0]['tax'] = '0';
    $data[0]['vattype'] = '';
    $data[0]['dvattype'] = '';
    // $data[0]['probability'] = '';
    $data[0]['shipcontactid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['creditinfo'] = '';
    $data[0]['category'] = '';
    $data[0]['revisionref'] = '';
    $data[0]['projid'] = '';

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
         client.client,client.clientid,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         left(head.deldate,10) as deldate, 
         head.clientname,
         head.address, 
         head.shipto, 
         head.revisionref,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         ifnull(head.agent, '') as agent, 
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, 
         head.projectid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         client.groupid,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, 
         head.branch,'' as dbranchname,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,
         client.tin, head.position, agent.tel  as agentcno, ifnull(concat(rc.category, '~',rc.reqtype),'') as industry,
         head.shipid, head.billid,head.optrno,head.tax,head.vattype,head.shipcontactid, head.billcontactid,head.creditinfo,
         concat(head.tax, '~', head.vattype) as dvattype,ifnull(category.cat_name, '') as category,head.industryid,head.projid";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as p on p.line = head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join category_masterfile as category on client.category = category.cat_id
        left join reqcategory as rc on head.industryid = rc.line        
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as p on p.line = head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join category_masterfile as category on client.category = category.cat_id
        left join reqcategory as rc on head.industryid = rc.line
          where head.trno = ? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
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

  public function openhead($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,client.clientid,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         left(head.deldate,10) as deldate, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         ifnull(head.agent, '') as agent, 
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, 
         head.projectid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         client.groupid,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, 
         head.branch,'' as dbranchname,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,
         client.tin, head.position, agent.tel  as agentcno, head.industry,
         head.shipid, head.billid,head.optrno,head.tax,head.vattype,head.shipcontactid, head.billcontactid,head.creditinfo,
         concat(head.tax, '~', head.vattype) as dvattype,head.industryid,head.projid";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as p on p.line = head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as p on p.line = head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
          where head.trno = ? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    return $head;
  }

  public function updatehead($config, $isupdate)
  {
    $companyid = $config['params']['companyid'];
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
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if    
      }
    }

    if ($companyid != 10 && $companyid != 12) { // not aftech
      $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['termsdetails'] = $data['terms'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->othersClass->getcreditinfo($config, $this->head);
    } else {

      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $insert = $this->coreFunctions->sbcinsert($this->head, $data);
      $this->othersClass->getcreditinfo($config, $this->head);
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

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->sstock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from qscalllogs where trno=?", "delete", [$trno]);
    $this->coreFunctions->execqry("delete from proformainv where trno=?", "delete", [$trno]);
    $this->othersClass->deleteattachments($config);

    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $this->othersClass->getcreditinfo($config, $this->head); //recheck credit info

    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    $terms = $this->coreFunctions->getfieldvalue($this->head, "terms", "trno=?", [$trno]);

    $isdp = $this->coreFunctions->getfieldvalue("terms", "isdp", "terms=?", [$terms]);

    if (floatval($isdp) <> 0) {
      $crdp = $this->coreFunctions->getfieldvalue($this->head, "crtrno", "trno=?", [$trno]);
      if (floatval($crdp) == 0) {
        return ['status' => false, 'msg' => 'Posting failed. A down payment or full payment is required.'];
      }
    }

    $podate = $this->coreFunctions->getfieldvalue($this->head, "due", "trno=?", [$trno]);
    // if ($podate == null) {
    //   return ['status' => false, 'msg' => 'Posting failed. PO Date is required.'];
    // }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $deldate = $this->coreFunctions->getfieldvalue($this->head, "deldate", "trno=?", [$trno]);
    if ($deldate == null) {
      return ['status' => false, 'msg' => 'Posting failed. Delivery date is required.'];
    } else {
      $override = $this->othersClass->checkAccess($config['params']['user'], 4163);
      if ($override == '0') {
        if (date('Y-m-d', strtotime($deldate)) < date('Y-m-d', strtotime($current_timestamp))) {
          return ['status' => false, 'msg' => 'Posting failed. Please check, the delivery date should not be later than PO date.'];
        }
      }
    }

    $POnum_checking = $this->coreFunctions->getfieldvalue($this->head, "yourref", "trno=?", [$trno]);

    if ($POnum_checking == "") {
      return ['status' => false, 'msg' => 'Posting failed. Please check PO#.'];
    } else {
      $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
      $POnumexist = $this->coreFunctions->datareader("select trno as value from (select trno from qshead where client =? and yourref = ? and trno <> ? union all select trno from hqshead where client =? and yourref = ? and trno <> ?) as a limit 1", [$client, $POnum_checking, $trno, $client, $POnum_checking, $trno]);

      if (floatval($POnumexist) != 0) {
        $po =  $this->coreFunctions->datareader("select trno as value from (select trno from hqsstock where trno = ? and voidqty = 0 union all select trno from hqtstock where trno = ? and voidqty = 0) as a limit 1", [$POnumexist, $POnumexist]);
        if (floatval($po) != 0) {
          return ['status' => false, 'msg' => 'Posting failed. PO# already exists.'];
        }
      }
    }

    $billid = $this->coreFunctions->getfieldvalue($this->head, "billid", "trno=?", [$trno]);
    $billcontactid = $this->coreFunctions->getfieldvalue($this->head, "billcontactid", "trno=?", [$trno]);
    if ($billid == 0) {
      return ['status' => false, 'msg' => 'Posting failed. Billing address is required.'];
    }
    if ($billcontactid == 0) {
      return ['status' => false, 'msg' => 'Posting failed. Billing contact is required.'];
    }

    $shipid = $this->coreFunctions->getfieldvalue($this->head, "shipid", "trno=?", [$trno]);
    $shipcontactid = $this->coreFunctions->getfieldvalue($this->head, "shipcontactid", "trno=?", [$trno]);
    if ($shipid == 0) {
      return ['status' => false, 'msg' => 'Posting failed. Shipping address is required.'];
    }
    if ($shipcontactid == 0) {
      return ['status' => false, 'msg' => 'Posting failed. Shipping contact is required.'];
    }

    //new add 3/5/2026
    // $address1 = $this->coreFunctions->getfieldvalue($this->head, "address1", "trno=?", [$trno]);
    // if ($address1 == '') {
    //   return ['status' => false, 'msg' => 'Posting failed. Address in Collection Details is required.'];
    // }

    // $cperson = $this->coreFunctions->getfieldvalue($this->head, "cperson", "trno=?", [$trno]);
    // if ($cperson == '') {
    //   return ['status' => false, 'msg' => 'Posting failed. Contact person in Collection Details is required.'];
    // }

    // $contactno = $this->coreFunctions->getfieldvalue($this->head, "contactno", "trno=?", [$trno]);
    // if ($contactno == '') {
    //   return ['status' => false, 'msg' => 'Posting failed. Contact number  in Collection Details is required.'];
    // }

    // $rem2 = $this->coreFunctions->getfieldvalue($this->head, "rem2", "trno=?", [$trno]);
    // if ($rem2 == '') {
    //   return ['status' => false, 'msg' => 'Posting failed. Contact notes in Collection Details is required.'];
    // }
    //end here 3/5/2026


    $vattype = $this->coreFunctions->getfieldvalue($this->head, "vattype", "trno=?", [$trno]);
    if ($vattype == '') {
      return ['status' => false, 'msg' => 'Posting failed. Taxes and Charges is required.'];
    }

    $termsdetails = $this->coreFunctions->getfieldvalue($this->head, "termsdetails", "trno=?", [$trno]);
    if ($termsdetails == '') {
      return ['status' => false, 'msg' => 'Posting failed. Terms details is required.'];
    }


    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $prob = $this->coreFunctions->datareader("select probability as value from qscalllogs where trno =? and endtime is not null order by line desc limit 1", [$trno]);
    if ($prob == '0%' || $prob == '0' || $prob == '') {
      return ['status' => false, 'msg' => 'Posting failed. Please check probability.'];
      // $stock = $this->openstock($trno,$config);
      // foreach ($stock as $key => $value) {
      //   $this->coreFunctions->execqry('update ' . $this->stock . ' set void=1 where trno=? and line=?', 'update', [$stock[$key]->trno, $stock[$key]->line]);
      //   $this->coreFunctions->execqry('update ' . $this->sstock . ' set void=1 where trno=? and line=?', 'update', [$stock[$key]->trno, $stock[$key]->line]);
      // }      
    } else {
      $probline = $this->coreFunctions->datareader("select line as value from qscalllogs where trno =? and endtime is not null order by line desc limit 1", [$trno]);
      $this->coreFunctions->execqry("update qscalllogs set probability = '100%' where trno=? and line=?", 'update', [$trno, $probline]);
    }

    $ship = $this->coreFunctions->getfieldvalue("headinfotrans", "isshipmentnotif", "trno=?", [$trno]);
    if ($ship == '') {
      return ['status' => false, 'msg' => 'Posting failed. Shipment Permit Notification is required.'];
    }

    $override = $this->othersClass->checkAccess($config['params']['user'], 1729);

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $islimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);
    $tin = $this->coreFunctions->getfieldvalue("client", "tin", "client=?", [$client]);
    $crlimit = $this->coreFunctions->getfieldvalue("client", "crlimit", "client=?", [$client]);

    if ($tin == '') {
      return ['status' => false, 'msg' => "Posting failed. customer doesn't have a TIN."];
    }

    $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);
    if ($override == '0') {
      if ($cstatus <> 'ACTIVE') {
        $this->logger->sbcwritelog($trno, $config, 'POST', 'Customer Status is not Active.');
        return ['status' => false, 'msg' => 'Posting failed. The customer`s status is not active.'];
      }
    }

    if (floatval($islimit) == 0) {
      if (floatval($crlimit) == 0) {
        return ['status' => false, 'msg' => "Posting failed. Customer doesn't have a credit limit."];
      }

      if ($override == '0') {
        $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
        $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
        $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);


        //if (floatval($overdue) > 0) {
        if (floatval($crline) < floatval($totalso)) {
          $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit');
          return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
        }
        //}
      }
    }

    //for glhead
    $override = $this->othersClass->checkAccess($config['params']['user'], 4163); //override podate
    $podate = "'" . date('Y-m-d') . "'";
    if ($override == '1') {
      $podate = 'head.due';
    }
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,branch,deptid,
      position, agentcno, industry, billid, shipid,optrno, shipcontactid, billcontactid,deldate,tax,vattype,pdate,creditinfo,
      termsdetails,crtrno,industryid)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      $podate,head.cur,head.branch,head.deptid, head.position, head.agentcno, head.industry, head.billid,
      head.shipid,head.optrno,head.shipcontactid,head.billcontactid,head.deldate,head.tax,head.vattype,
      $podate ,head.creditinfo,
      head.termsdetails,head.crtrno,head.industryid
      FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $this->coreFunctions->logconsole($qry);
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      if (!$this->othersClass->postingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      if (!$this->othersClass->postingqscalllogs($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Call Logs'];
      }

      // for glstock
      $qry = "insert into " . $this->hstock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid,sgdrate,noprint,sortline)
        SELECT trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid ,sgdrate,noprint,sortline
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into " . $this->hsstock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid,sgdrate,sortline)
        SELECT trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid,sgdrate,sortline
        FROM " . $this->sstock . " where trno =?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          //update transnum
          $date = $this->othersClass->getCurrentTimeStamp();
          $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
          $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->sstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from qscalllogs where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
          if ($companyid == 12) {
            $sgdrate = $this->othersClass->getexchangerate('USD', 'SGD');
          } else {
            $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
          }

          // $qssgd = $this->coreFunctions->getfieldvalue("hqshead","sgdrate","trno=?",[$trno]);
          // if(floatval($qssgd)==0){
          $this->coreFunctions->execqry("update hqshead set sgdrate = ? where trno =?", "update", [$sgdrate, $trno]);
          $this->coreFunctions->execqry("update hqsstock set sgdrate = ? where trno =?", "update", [$sgdrate, $trno]);
          $this->coreFunctions->execqry("update hqtstock set sgdrate = ? where trno =?", "update", [$sgdrate, $trno]);
          // }          
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from hqscalllogs where trno=?", "delete", [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Service Stock'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hqscalllogs where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Prodict Stock'];
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

    $sotrno = $this->coreFunctions->getfieldvalue($this->hhead, "sotrno", "trno=?", [$trno]);
    if ($sotrno) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already served in Sales Order'];
    }

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0 or sjqa >0 or poqa >0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or connected to other documents...'];
    }

    $qry = "select trno from " . $this->hsstock . " where trno=? and (qa>0 or void<>0 or sjqa >0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or connected to other documents...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
  yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,agent,branch,deptid, 
  position, agentcno, industry, billid, shipid,optrno,shipcontactid,billcontactid,deldate,tax,vattype,creditinfo,
  termsdetails,crtrno,sgdrate,industryid)
  select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
  head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
  head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.agent,head.branch,head.deptid,
  head.position, head.agentcno, head.industry, head.billid, head.shipid,head.optrno,head.shipcontactid,head.billcontactid,head.deldate,head.tax,head.vattype,head.creditinfo,
  head.termsdetails,head.crtrno,head.sgdrate,head.industryid
  from (" . $this->hhead . " as head 
  left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)
  left join client on client.client=head.client
  where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while unposting head data.'];
      }

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      if (!$this->othersClass->unpostingqscalllogs($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting Call Logs'];
      }

      $qry = "insert into " . $this->stock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid,sgdrate,noprint,sortline)
      select trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid ,sgdrate, noprint,sortline
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into " . $this->sstock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid,sgdrate)
      select trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, mrsqa, kgs, itemid, whid, stageid, ref, refx, linex, projectid,sgdrate 
      from " . $this->hsstock . " where trno=?";
        //stock
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hsstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from hqscalllogs where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from headinfotrans where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from qscalllogs where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, service stock problems...'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from qscalllogs where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
      $decimal = 4;
      $qty_dec = 0;
    } else {
      $decimal = $this->companysetup->getdecimal('price', $config['params']);
      $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    }

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    item.barcode, 
    concat(item.itemname,'\\nBrand: ',ifnull(brand.brand_desc,'')) as itemname,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    stock.uom, 
    stock.iss,
    FORMAT(stock.isamt," . $decimal . ") as isamt,
    FORMAT(stock.isqty," . $qty_dec . ")  as isqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,stock.expiry,
    item.brand,
    stock.rem, 
    stock.ref,
    stock.refx,
    stock.linex,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then (case when stock.isqty <>0 then '' else 'bg-red-2' end) else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    brand.brand_desc as brand_desc,stock.sgdrate,
    case when stock.noprint=0 then 'false' else 'true' end as noprint,stock.voidqty/uom.factor as voidqty,
    FORMAT(stock.amt*uom.factor," . $decimal . ") as amt,stock.sortline,(case item.islabor when 1 then 'qtstock' else 'qsstock' end) as tblname";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno =? 
    union all " .
      $sqlselect . " 
    FROM $this->sstock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hsstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno =? order by sortline,line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno, $trno]);
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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno = ? and stock.line = ?
    union all " .
      $sqlselect . "  
    FROM $this->sstock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'createversion':
        $return = $this->posttrans($config);
        if ($return['status']) {
          return $this->othersClass->createversion($config);
        } else {
          return $return;
        }
        break;
      case 'additem':
        $return =  $this->additem('insert', $config);
        if ($return['status'] == true) {
          $this->othersClass->getcreditinfo($config, $this->head);
        }
        return $return;
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
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getopsummary':
        return $this->getopsummary($config);
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
      case 'duplicatedoc':
        return $this->othersClass->duplicateTransnum($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
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

    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
       CAST(concat('Total OP Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hophead as head
       left join hopstock as s on s.trno = head.trno
       left join hqsstock as qtstock on qtstock.refx = s.trno and qtstock.linex = s.line
       where qtstock.trno = ?
       group by head.trno,head.docno,head.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //qs quotation 
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 100,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => '#88DDFF',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'qt']);
        $a = $a + 100;

        // if(floatval($t[$key]->refx)!=0) {
        // quotation
        $qry = "
            select head.docno,left(head.dateid,10) as dateid,
            CAST(concat('Total QS Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hqshead as head 
            left join hqsstock as s on s.trno = head.trno
            where head.trno = ?
            group by head.docno,head.dateid";
        $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        $poref = $t[$key]->docno;
        if (!empty($x)) {
          foreach ($x as $key2 => $value) {
            data_set(
              $nodes,
              'qt',
              [
                'align' => 'left',
                'x' => 500,
                'y' => 50 + $a,
                'w' => 250,
                'h' => 80,
                'type' => $x[$key2]->docno,
                'label' => $x[$key2]->rem,
                'color' => '#ff88dd',
                'details' => [$x[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'qt', 'to' => 'so']);
            $a = $a + 100;
          }
        }
        // }

        // SO
        $qry = "
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from sqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          where qthead.trno = ?
          group by head.docno,head.dateid
          union all
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hsqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          where qthead.trno = ?
          group by head.docno,head.dateid";
        $sodata = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($sodata)) {
          foreach ($sodata as $sodatakey => $value) {
            data_set(
              $nodes,
              'so',
              [
                'align' => 'left',
                'x' => 600,
                'y' => 100 + $a,
                'w' => 250,
                'h' => 80,
                'type' => $sodata[$sodatakey]->docno,
                'label' => $sodata[$sodatakey]->rem,
                'color' => 'blue',
                'details' => [$sodata[$sodatakey]->dateid]
              ]
            );
            array_push($links, ['from' => 'so', 'to' => 'sj']);
            $a = $a + 100;
          }
        }
      }
    }

    //SJ
    $qry = "
    select sjhead.docno,
    date(sjhead.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    sjhead.trno
    from hqshead as head
    left join hqsstock as stock on stock.trno = head.trno
    left join glstock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join glhead as sjhead on sjhead.trno = sjstock.trno
    left join arledger as ar on ar.trno = sjhead.trno
    where head.trno = ? and sjhead.docno is not null
    group by sjhead.docno, sjhead.dateid, ar.bal, sjhead.trno
    union all 
    select sjhead.docno,
    date(sjhead.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(sum(sjstock.ext),2)) as CHAR) as rem, 
    sjhead.trno
    from hqshead as head
    left join hqsstock as stock on stock.trno = head.trno
    left join lastock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join lahead as sjhead on sjhead.trno = sjstock.trno
    where head.trno = ? and sjhead.docno is not null
    group by sjhead.docno, sjhead.dateid, sjhead.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => 450 + $startx,
          'y' => 300,
          'w' => 250,
          'h' => 80,
          'type' => $t[0]->docno,
          'label' => $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as $key => $value) {
        //CR
        $rrtrno = $t[$key]->trno;
        $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ?
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ?";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 800,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => '#6D50E8',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid
        where stock.refx=?
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where stock.refx=?
        group by head.docno, head.dateid";
        $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
        if (!empty($dmdata)) {
          foreach ($dmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 800,
                'y' => 300,
                'w' => 250,
                'h' => 80,
                'type' => $dmdata[$key2]->docno,
                'label' => $dmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$dmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
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
    $result = $this->additem('update', $config);
    //$this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => $result['msg']];
  }


  public function updateitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result = $this->additem('update', $config);
      if ($msg == '') {
        $msg = $result['msg'];
      } else {
        $msg = $msg . "\n" . $result['msg'];
      }
    }
    //$this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function

  public function addallitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result = $this->additem('insert', $config);
      if ($msg == '') {
        $msg = $result['msg'];
      } else {
        $msg = $msg . "\n" . $result['msg'];
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
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

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {

    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $refx = 0;
    $linex = 0;
    $void = 'false';
    $rem = '';
    $expiry = '';
    $ref = '';
    $projectid = 0;
    $table = $this->stock;
    $moq = 0;
    $mmoq = 0;
    $mmoq = 0;
    $noprint = 'false';


    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $projectid = $config['params']['data']['projectid'];
    }

    if (isset($config['params']['data']['sgdrate'])) {
      $sgdrate = $config['params']['data']['sgdrate'];
    } else {
      if ($companyid == 12) {
        $sgdrate = $this->othersClass->getexchangerate('USD', 'SGD');
      } else {
        $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
      }
    }


    if (isset($config['params']['data']['noprint'])) {
      $noprint = $config['params']['data']['noprint'];
    }

    $line = 0;
    if ($action == 'insert') {
      $qry = "select value from (select line as value from " . $this->stock . " where trno=? union all select line as value from " . $this->sstock . " where trno=?) as A order by value desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno, $trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];

      if ($companyid == 10 || $companyid == 12) {
        if ($projectid == 0) {
          $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
        }
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;

      if ($companyid == 10 || $companyid == 12) {
        $projectid = $config['params']['data']['projectid'];
        $sgdrate = $config['params']['data']['sgdrate'];
      }
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.moq,item.mmoq from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      $moq = $item[0]->moq;
      $mmoq = $item[0]->mmoq;
    }
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    if ($companyid == 10) {
      if ($disc != "") {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, '', 0, 1, 1);
      } else {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
      }
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    }

    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $isservice = $this->coreFunctions->getfieldvalue('item', 'islabor', 'itemid=?', [$itemid]);

    if (floatval($forex) == 0) {
      $forex = 1;
    }

    $hamt = $computedata['amt'] * $forex;
    if ($companyid == 10) {
      if ($disc != "") {
        $hamt = number_format($computedata['amt'] * $forex, 2, '.', '');
      }
    }
    $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isamt' => $amt,
      'amt' => $hamt,
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'void' => $void,
      'uom' => $uom,
      // 'rem' => $rem,
      'ref' => $ref,
      'expiry' => $expiry,
      'refx' => $refx,
      'linex' => $linex,
      'sgdrate' => $sgdrate,
      'noprint' => $noprint
    ];

    if ($companyid == 10 || $companyid == 12) {
      $data['projectid'] = $projectid;
    }

    if (floatval($isservice) == 1) {
      $table = $this->sstock;
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = 'Item was successfully added.';
    $return = true;
    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }
    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];
      if ($this->coreFunctions->sbcinsert($table, $data) == 1) {
        if ($rem != '') {
          $stockinfo_data = [
            'trno' => $trno,
            'line' => $line,
            'rem' => $rem
          ];
          $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Uom:' . $uom . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        //$this->loadheaddata($config);

        //checkingmoq       
        if ($moq != 0 && $mmoq != 0) {
          if ($qty < $moq) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($table, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Quantity ordered less than the minimum order required.";
          }

          if ($qty > $moq && (($qty % $mmoq) != 0)) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($table, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Invalid quantity, multiple order required is " . $mmoq . ".";
          }
        }

        return ['row' => $row, 'status' => $return, 'msg' => $msg, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = 'Update item Successfully';
      $this->coreFunctions->sbcupdate($table, $data, ['trno' => $trno, 'line' => $line]);
      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($table, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $return = false;
        $msg = "(" . $item[0]->barcode . ") Qoutation Qty is Greater than Sales Activity Qty.";
      }

      //checkingmoq
      if ($moq != 0 && $mmoq != 0) {
        if ($qty < $moq) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($table, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Quantity ordered less than the minimum order required.";
        }

        if ($qty > $moq && (($qty % $mmoq) != 0)) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($table, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Invalid quantity, multiple order required is " . $mmoq . ".";
        }
      }
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];

    $data = $this->coreFunctions->opentable('select refx,linex,stageid from ' . $this->stock . ' where trno=? and refx<>0 union all select refx,linex,stageid from ' . $this->sstock . ' where trno=? and refx<>0', [$trno, $trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->sstock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex, $this->hqty);
      $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$data[$key]->refx, $trno]);
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
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    $qry = "delete from " . $this->sstock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from qscalllogs where trno=? and line=?', 'delete', [$trno, $line]);

    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
        $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$data[$key]->refx, $trno]);
      }
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
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
    $trno = $config['params']['trno'];

    $usdprice = 0;
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
    $qry = "select amt,disc,uom,moq,mmoq from item where barcode=?";
    $data = $this->coreFunctions->opentable($qry, [$barcode]);
    if (floatval($forex) <> 1) {
      $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
      if ($cur == 'USD') {
        $data[0]->amt = $usdprice;
      } else {
        $data[0]->amt = round($usdprice * $dollarrate, $this->companysetup->getdecimal('price', $config['params']));
      }
    }


    if (floatval($data[0]->amt) == 0) {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    } else {
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    }
  } // end function


  public function getposummaryqry($config)
  {
    return "
          select head.docno, head.client, head.clientname, head.address, head.rem, head.cur, head.forex, head.shipto, head.agent, head.terms, head.ourref, head.yourref, head.branch,
          item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.amt,  stock.iss,stock.isamt as rrcost,
          round(stock.iss/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,
          " . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
          stock.disc, ifnull(st.line,0) as stageid,stock.projectid,head.deptid,head.designation as position,
          head.industry,agent.tel as contactno,client.tin,
          client.billid,client.shipid,client.billcontactid,client.shipcontactid,stock.sgdrate, head.contactname
          FROM hophead as head left join hopstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno 
          left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and
          uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid 
          left join client on client.client = head.client left join client as agent on agent.client = head.agent
          where stock.trno = ? and transnum.center='" . $config['params']['center'] . "' and stock.iss>stock.qa ";
  }


  public function getopsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $currentdate = $this->othersClass->getCurrentDate();
    $currenttime = date('H:i:s', strtotime($this->othersClass->getCurrentTimeStamp()));

    $rows = [];
    $optrno = 0;
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);

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
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['stageid'] =  $data[$key2]->stageid;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $line = $return['row'][0]->line;
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$trno, $data[$key2]->trno]);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }

            $rem = $this->coreFunctions->getfieldvalue("hstockinfotrans", "rem", "trno=? and line =?", [$data[$key2]->trno, $data[$key2]->line]);
            if (strlen($rem) != 0) {
              $this->coreFunctions->sbcinsert("stockinfotrans", ["rem" => $rem, "trno" => $trno, "line" => $line]);
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach

        //calllogs 
        $checkingcalllogs = $this->coreFunctions->datareader("select trno as value from qscalllogs where trno = ? ", [$trno],'',true);
        if ($checkingcalllogs == 0) {
          $data3 = [
            'trno' => $trno,
            'line' => 1,
            'contact' => $data[0]->contactname,
            'probability' => '25%',
            'dateid' => $currentdate,
            'starttime' => $currenttime
          ];
          $this->coreFunctions->sbcinsert('qscalllogs', $data3);
        }
      } //end if
    } //end foreach

    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  }

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from qshead as head left join qsstock as
      stock on stock.trno=head.trno where head.doc='QS' and stock.refx=" . $refx . " and stock.linex=" . $linex;
    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from hqshead as head left join hqsstock as stock on stock.trno=
      head.trno where head.doc='QS' and stock.refx=" . $refx . " and stock.linex=" . $linex;
    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from qshead as head left join qtstock as stock on stock.trno=
    head.trno where head.doc='QS' and stock.refx=" . $refx . " and stock.linex=" . $linex;
    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from hqshead as head left join hqtstock as stock on stock.trno=
    head.trno where head.doc='QS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";


    $qty = $this->coreFunctions->datareader($qry2);
    if (floatval($qty) == 0) {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hopstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  private function getcreditinfo($config, $head)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);
    $crlimit = $this->coreFunctions->getfieldvalue("client", "crlimit", "clientid=?", [$clientid]);
    $isnocrlimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "clientid=?", [$clientid]);
    $return = true;

    $ar = $this->coreFunctions->datareader("select ifnull(sum(a.bal),0) as value from arledger as a left join cntnum on cntnum.trno = a.trno where cntnum.center = ? and  a.clientid=?", [$center, $clientid]);
    $so = $this->coreFunctions->datareader("select ifnull(sum(amt),0) as value from (
      select sum(s.ext) as amt from hsqhead as h left join hqshead as qt on qt.sotrno = h.trno
      left join hqsstock as s on s.trno = qt.trno
      left join client on client.client = qt.client where s.iss<>s.sjqa and client.clientid = ?
      union all
      select sum(s.ext) as amt from hsshead as h left join hsrhead as qt on qt.sotrno = h.trno
      left join hsrstock as s on s.trno = qt.trno
      left join client on client.client = qt.client where s.iss<>s.qa and client.clientid = ?) as SO", [$clientid, $clientid]);

    $info = '';

    if ($ar != 0 || $so != 0) {
      if (floatval($isnocrlimit) == 0) {
        $info = '\nCredit Limit : ' . number_format(round($crlimit, 2), $this->companysetup->getdecimal('currency', $config['params'])) .
          '\nOutstanding Balance : ' . number_format(round($ar, 2), $this->companysetup->getdecimal('currency', $config['params'])) .
          '\nTotal Unserved Posted SO`s : ' . number_format(round($so, 2), $this->companysetup->getdecimal('currency', $config['params']));
      }
    }

    if ($info != '') {
      $this->coreFunctions->execqry("update " . $head . " set creditinfo ='" . $info . "' where trno = " . $trno, "update");
    }

    //getlastinv
    $dateid = $this->coreFunctions->getfieldvalue($head, "dateid", "trno=?", [$trno]);
    $lastinv = date_create($this->coreFunctions->datareader("
              select CONVERT(dateid,DATE) as value 
              from (select dateid from lahead 
                    left join client on client.client = lahead.client 
                    where doc ='SJ' and client.clientid =" . $clientid . " 
                    union all 
                    select dateid from glhead where doc = 'SJ' and clientid =" . $clientid . ") as a 
                    order by dateid desc limit 1"));

    $interval = $lastinv->diff(date_create($this->othersClass->getCurrentDate()));
    $diffInYears   = $interval->y;
    if ($diffInYears >= 1) {
      $this->coreFunctions->execqry("update " . $head . " set terms = 'COD',rem='Please reevaluate terms.' where trno =" . $trno, "update");
    }

    return $info;
  }

  // report 

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
    if ($companyid == 10 || $companyid != 12) {
    } else {
      $this->logger->sbcviewreportlog($config);
    }

    switch ($companyid) {
      case '10':
      case '12':
        $quote = $config['params']['dataparams']['radioquotation'];
        $printdefault = $config['params']['dataparams']['print'];
        $data = app($this->companysetup->getreportpath($config['params']))->report_quote_query($config['params']['dataid']);
        switch ($quote) {
          case 'vatinc':
            $str = app($this->companysetup->getreportpath($config['params']))->report_vat_inc_plottingpdf($config, $data);
            break;
          case 'quoteprint':
            $str = app($this->companysetup->getreportpath($config['params']))->reportquoteplottingpdf($config, $data);
            break;
          case 'instructionform':
            $str = app($this->companysetup->getreportpath($config['params']))->reportinstructionformpdf($config, $data);
            break;
          default:
            // $str = app($this->companysetup->getreportpath($config['params']))->reportproformaplottingpdf($config, $data);
            $str = app($this->companysetup->getreportpath($config['params']))->reportsalesinvoicepdf($config, $data);

            break;
        }
        break;

      default:
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        break;
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
