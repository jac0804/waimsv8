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

class rf
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'REQUEST FOR REPLACEMENT/RETURN';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'rfhead';
  public $hhead = 'hrfhead';
  public $stock = 'rfstock';
  public $hstock = 'hrfstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = [
    'trno', 'docno', 'client', 'clientname', 'empid', 'cperson',  'email', 'dateid', 'yourref',
    'sotrno', 'tel',  'shipid', 'billid', 'shipcontactid', 'billcontactid', 'reason', 'recommend', 'others', 'rfnno', 'ourref', 'invoiceno'
  ];
  public $except = ['trno', 'dateid'];
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
      'view' => 2825,
      'edit' => 2826,
      'new' => 2827,
      'save' => 2828,
      // 'change' => 2829, remove change doc
      'delete' => 2830,
      'print' => 2831,
      'lock' => 2832,
      'unlock' => 2833,
      'changeamt' => 2834,
      'crlimit' => 2835,
      'post' => 2836,
      'unpost' => 2837,
      'additem' => 2838,
      'edititem' => 2839,
      'deleteitem' => 2840
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

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$yourref]['label'] = 'PO#';
    $cols[$ourref]['label'] = 'ERP#';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['selectprefix', 'docno'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.type', 'input');
        data_set($col1, 'docno.label', 'Search');
        data_set($col1, 'selectprefix.label', 'Search by');
        data_set($col1, 'selectprefix.type', 'lookup');
        data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
        data_set($col1, 'selectprefix.action', 'lookupsearchby');
        $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
        break;
      default:
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
        break;
    }
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
      if (isset($test['selectprefix'])) {
        if ($test['selectprefix'] != "") {
          if ($test['docno'] != '') {
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
            }
          }

          if (isset($test)) {
            $join = " left join rfstock on rfstock.trno = head.trno
            left join item on item.itemid = rfstock.itemid left join item as item2 on item2.itemid = rfstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin = " left join hrfstock on hrfstock.trno = head.trno
            left join item on item.itemid = hrfstock.itemid left join item as item2 on item2.itemid = hrfstock.itemid
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
    }


    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby,
     head.yourref, head.ourref  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno 
     " . $join . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     union all
     select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby,
      head.yourref, head.ourref  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno 
     " . $hjoin . "
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     order by dateid desc,docno desc $limit";

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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $return  = [];
    $rts_access = $this->othersClass->checkAccess($config['params']['user'], 2841);
    $rtc_access = $this->othersClass->checkAccess($config['params']['user'], 2842);


    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $viewrfreturnsup = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewrfreturnsup']];
    $viewrfreturncust = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewrfreturncust']];

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($rts_access != 0) {
      $return['RETURN TO SUPPLIER'] = ['icon' => 'fa fa-angle-left', 'customform' => $viewrfreturnsup];
    }

    if ($rtc_access != 0) {
      $return['RETURN TO CUSTOMER'] = ['icon' => 'fa fa-angle-left', 'customform' => $viewrfreturncust];
    }


    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    $changeamt = $this->othersClass->checkAccess($config['params']['user'], 2834);

    $action = 0;
    $itemdesc = 1;
    $serialno = 2;
    $isqty = 3;
    $uom = 4;
    $isamt = 5;
    $cost = 6;
    $disc = 7;
    $ext = 8;
    $ref = 9;
    $itemname = 10;
    $barcode = 11;



    $column = ['action', 'itemdescription', 'serialno', 'isqty', 'uom', 'isamt', 'cost', 'disc', 'ext', 'ref', 'itemname', 'barcode'];
    $sortcolumn = ['action', 'itemdescription', 'serialno', 'isqty', 'uom', 'isamt', 'cost', 'disc', 'ext', 'ref', 'itemname', 'barcode'];

    $headgridbtns = ['viewref', 'viewdiagram', 'viewitemstockinfo'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'], 'headgridbtns' => $headgridbtns
      ],
    ];

    if ($this->companysetup->getserial($config['params'])) {
      $stockbuttons = ['save', 'delete', 'showbalance', 'iteminfo', 'serialout'];
    } else {
      $stockbuttons = ['save', 'delete', 'showbalance'];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][$action]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
    $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    $obj[0]['inventory']['columns'][$ref]['type'] = 'input';
    $obj[0]['inventory']['columns'][$ref]['readonly'] = true;
    $obj[0]['inventory']['columns'][$uom]['type'] = 'input';
    $obj[0]['inventory']['columns'][$uom]['readonly'] = true;
    $obj[0]['inventory']['columns'][$isqty]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0]['inventory']['columns'][$uom]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0]['inventory']['columns'][$cost]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0]['inventory']['columns'][$ref]['style'] = 'width: 250px;whiteSpace: normal;min-width:200px;max-width:250px';
    $obj[0]['inventory']['columns'][$cost]['readonly'] = true;
    $obj[0]['inventory']['columns'][$cost]['label'] = 'Latest Cost';

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['descriptionrow'] = [];
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
      $obj[0]['inventory']['columns'][$serialno]['type'] = 'editlookup';
      $obj[0]['inventory']['columns'][$serialno]['lookupclass'] = 'lookupserialoutrf';
      $obj[0]['inventory']['columns'][$serialno]['action'] = 'lookupserialout';
      $obj[0]['inventory']['columns'][$serialno]['readonly'] = false;
      $obj[0]['inventory']['columns'][$ref]['readonly'] = false;
      if ($changeamt) {
        $obj[0]['inventory']['columns'][$isamt]['readonly'] = false;
      }
    } else {
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$serialno]['type'] = 'coldel';
    }

    if (!$this->othersClass->checkAccess($config['params']['user'], 3587)) { //  Allow View RFR Cost
      $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'][$itemname]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$itemname]['label'] = '';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'saveitem', 'deleteallitem', 'pendingrfso'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $editrf = $this->othersClass->checkAccess($config['params']['user'], 3586);
    $fields = ['docno', 'client', 'clientname', 'email', 'rfnno', 'ourref'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Company Code');
    data_set($col1, 'clientname.label', 'Company Name');
    data_set($col1, 'email.label', 'Email Address');
    data_set($col1, 'client.lookupclass', 'rfcustomer');
    data_set($col1, 'email.required', true);
    data_set($col1, 'ourref.label', 'ERP#');
    data_set($col1, 'rfnno.label', "RFR No.");

    if ($editrf == '0') {
      data_set($col1, 'rfnno.readonly', true);
      data_set($col1, 'rfnno.class', 'sbccsreadonly');
    }

    $fields = ['dateid', 'empname', 'cperson', 'tel', 'yourref', 'invoiceno'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.label', 'Date');
    data_set($col2, 'empname.type', 'lookup');
    data_set($col2, 'empname.label', 'Filed By');
    data_set($col2, 'empname.action', 'lookupclient');
    data_set($col2, 'empname.lookupclass', 'employee');
    data_set($col2, 'tel.label', 'Contact #');
    data_set($col2, 'invoiceno.label', 'Invoice #');
    data_set($col2, 'invoiceno.type', 'lookup');
    data_set($col2, 'invoiceno.action', 'lookuprrinvoiceno');
    data_set($col2, 'invoiceno.addedparams', ['yourref']);
    data_set($col2, 'tel.required', true);
    data_set($col2, 'cperson.required', true);

    data_set($col2, 'yourref.type', 'lookup');
    data_set($col2, 'yourref.lookupclass', 'lookupyourrefrf');
    data_set($col2, 'yourref.action', 'lookupyourrefrf');
    data_set($col2, 'yourref.label', 'PO #');
    data_set($col2, 'yourref.readonly', true);
    data_set($col2, 'yourref.addedparams', ['client']);
    data_set($col2, 'yourref.class', 'csyourref');

    $fields = ['shipaddress', 'reason'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'shipaddress.type', 'textarea');
    data_set($col3, 'reason.label', 'Reason');
    data_set($col3, 'reason.required', true);
    data_set($col3, 'reason.maxlength', '1000');

    $fields = ['recommend', 'others'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'recommend.label', 'Recommendation');
    data_set($col4, 'recommend.type', 'lookup');
    data_set($col4, 'recommend.action', 'lookuprandom');
    data_set($col4, 'recommend.lookupclass', 'lookuprecommend');
    data_set($col4, 'recommend.required', true);


    data_set($col4, 'others.label', 'Please Specify');
    data_set($col4, 'others.type', 'textarea');
    data_set($col4, 'others.maxlength', '1000');



    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['complain'] = '';
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['empid'] = $params['adminid'];
    $data[0]['empname'] =  $this->coreFunctions->datareader("select clientname as value from client where clientid='" . $data[0]['empid'] . "'");
    $data[0]['cperson'] = '';
    $data[0]['shipaddress'] = '';
    $data[0]['email'] = '';
    $data[0]['tel'] = '';
    $data[0]['recommend'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['sotrno'] = 0;
    $data[0]['billcontactid'] = 0;
    $data[0]['billid'] = 0;
    $data[0]['shipid'] = 0;
    $data[0]['shipcontactid'] = 0;
    $data[0]['reason'] = '';
    $data[0]['recommend'] = '';
    $data[0]['others'] = '';
    $data[0]['invoiceno'] = '';
    $data[0]['rfnno'] = '';
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
         client.client,
         head.clientname,
         head.docno,
         head.email,
         head.fileby,
         head.cperson,
         head.tel,
         concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode)  as shipaddress,
         left(head.dateid,10) as dateid, 
         head.complain,
         head.sotrno,
         head.yourref,
         head.ourref,
         head.empid,
         head.shipid,
         head.billid,
         head.billcontactid,
         head.shipcontactid,
         emp.clientname as empname,
         head.recommend,
         head.others,
         head.rfnno,
         head.reason,head.invoiceno";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as emp on head.empid = emp.clientid
        left join billingaddr as b on b.line = head.shipid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as emp on head.empid = emp.clientid
        left join billingaddr as b on b.line = head.shipid
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

  public function updatehead($config, $isupdate)
  {
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

    if ($head['recommend'] == 'Others, please Specify' && $head['others'] == '') {
      return ['status' => false, 'msg' => 'specify recommendation for Others is Required'];
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


  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data2 = $this->coreFunctions->opentable('select s.trno,s.line from ' . $this->stock . ' as s left join item on item.itemid = s.itemid where item.isserial = 1 and  s.trno=?', [$trno]);
      foreach ($data2 as $key => $value) {
        $this->coreFunctions->execqry("update serialout set rftrno =0,rfline = 0 where rftrno =? and rfline =? ", 'update', [$data2[$key]->trno, $data2[$key]->line]);
      }
    }
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);

    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if (!$this->othersClass->checkserialoutrf($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }


    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,client,clientname,supplierid,email,fileby,cperson,shipaddress,
    complain,recommend,yourref,sotrno,returndate_supby,returndate_sup,returndate_custby,returndate_cust,awb,action,dateclose,empid,
    tel,billid,shipid,shipcontactid,billcontactid,createdate,createby,editby,editdate,lockdate,
    reason,others,rfnno,ourref,invoiceno)
      SELECT head.trno,head.doc,head.docno,dateid,head.client,head.clientname,head.supplierid,
      head.email,head.fileby,head.cperson,head.shipaddress,head.complain,head.recommend,
      head.yourref,head.sotrno,head.returndate_supby,head.returndate_sup,head.returndate_custby,
      head.returndate_cust,head.awb,head.action,head.dateclose,head.empid,head.tel,head.billid,
      head.shipid,head.shipcontactid,head.billcontactid,head.createdate,head.createby
      ,head.editby,head.editdate,head.lockdate,
      head.reason,head.others,head.rfnno,head.ourref,head.invoiceno
      FROM " . $this->head . " as head 
      left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,iss,isqty,amt,isamt,ext,disc,
        encodeddate,encodedby,editdate,editby,refx,linex,ref,serialno)
        SELECT trno,line,itemid,uom,
        whid,iss,isqty,amt,isamt,ext,disc,
        encodeddate,encodedby,editdate,editby,refx,linex,ref,serialno FROM " . $this->stock . " where trno =?";

      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        if (!$this->othersClass->postingstockinfotrans($config)) {
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
        } else {
          //update transnum
          $date = $this->othersClass->getCurrentTimeStamp();
          $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
          $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
          $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['status' => false, 'msg' => 'Error on Posting Stock'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $qry = "
    select dateclose as value from hrfhead where trno = ?";
    if ($this->coreFunctions->datareader($qry, [$trno])) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Already have close date', 'data' => []];
    }

    if (!$this->othersClass->checkserialoutrf($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,client,clientname,supplierid,email,fileby,cperson,shipaddress,
    complain,recommend,yourref,sotrno,returndate_supby,returndate_sup,returndate_custby,returndate_cust,awb,action,dateclose,empid,
    tel,billid,shipid,shipcontactid,billcontactid,createdate,createby,editby,editdate,lockdate,
    reason,others,rfnno,ourref,invoiceno)
    SELECT head.trno,head.doc,head.docno,dateid,head.client,head.clientname,head.supplierid,head.email,
    head.fileby,head.cperson,head.shipaddress,head.complain,head.recommend,head.yourref,head.sotrno,
    head.returndate_supby,head.returndate_sup,head.returndate_custby,head.returndate_cust,head.awb,
    head.action,head.dateclose,head.empid,head.tel,head.billid,head.shipid,head.shipcontactid,
    head.billcontactid,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,
    head.reason, head.others,head.rfnno,head.ourref,head.invoiceno
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      $qry = "insert into " . $this->stock . "(trno,line,itemid,uom,
        whid,iss,isqty,amt,isamt,ext,disc,
        encodeddate,encodedby,editdate,editby,refx,linex,ref,serialno)
        SELECT trno,line,itemid,uom,
        whid,iss,isqty,amt,isamt,ext,disc,
        encodeddate,encodedby,editdate,editby,refx,linex,ref,serialno FROM " . $this->hstock . " where trno =?";

      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        if (!$this->othersClass->unpostingstockinfotrans($config)) {
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting stockinfo'];
        } else {
          $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    } else {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
    }
  } //end function

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
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getsodetails':
        return $this->getsodetails($config);
        break;
      case 'getserialout':
        return $this->getserialout($config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
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
        $msg1 = ' Out of stock ';
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ')'];
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $msg = 'Successfully saved.';
      $config['params']['data'] = $value;
      $return = $this->additem('insert', $config);
      if ($return['status'] == false) {
        $msg = $return['msg'];
        break;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    if ($this->companysetup->getserial($config['params'])) {
      $qry = "update serialout set rftrno =0,rfline=0 where rftrno=? and rfline=?";
      $this->coreFunctions->execqry($qry, 'update', [$trno, $line]);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' client:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    item.barcode, 
    item.itemname,
    stock.uom, 
    stock.iss,
    FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ")  as cost,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.whid,
    stock.disc, 
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.rem, stock.refx,stock.linex,stock.ref,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription, 
    ifnull(group_concat(rr.serial separator '\\n'),stock.serialno) as serialno
    ";
    return $sqlselect;
  }

  public function additem($action, $config)
  {

    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $loc = '';
    $void = 'false';
    $rem = '';
    $ref = '';
    $wh = '';
    $expiry = '';
    $refx = 0;
    $linex = 0;
    $cost = 0;
    $serial = '';

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    if (isset($config['params']['data']['loc'])) {
      $loc = $config['params']['data']['loc'];
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
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['wh'])) {
      $wh = $config['params']['data']['wh'];
    }

    if (isset($config['params']['data']['serialno'])) {
      $serial = $config['params']['data']['serialno'];
    }

    if ($wh == '') {
      $wh = $this->companysetup->getwh($config['params']);
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
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $forex = 1;
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
      'uom' => $uom,
      'rem' => $rem,
      'refx' => $refx,
      'linex' => $linex,
      'cost' => $this->othersClass->getlatestcost($itemid, $this->othersClass->getCurrentTimeStamp(), $config, $wh),
      'ref' => $ref,
      'serialno' => $serial
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
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0) { //main
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            }
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode);
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

  public function getsodetails($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid, wh.client as wh
        FROM glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom 
        left join client as wh on wh.clientid=stock.whid
        where stock.trno = ? and stock.line=?
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['wh'] = $data[$key2]->wh;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

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
    left join serialout as rr on rr.rftrno = stock.trno and rr.rfline = stock.line
    where stock.trno =? 
    group by item.brand, mm.model_name, item.itemid, stock.trno, stock.line,
    item.barcode, item.itemname, stock.uom, stock.iss,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . "),
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "), 
    stock.encodeddate, stock.whid, stock.disc,  warehouse.client, warehouse.clientname, 
    stock.rem, stock.refx, stock.linex, stock.ref, uom.factor, brand.brand_desc, i.itemdescription, stock.cost,stock.serialno
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
    left join serialout as rr on rr.rftrno = stock.trno and rr.rfline = stock.line
    where stock.trno =? 
    group by item.brand, mm.model_name, item.itemid, stock.trno, stock.line,
    item.barcode, item.itemname, stock.uom, stock.iss,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . "),
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "), 
    stock.encodeddate, stock.whid, stock.disc,  warehouse.client, warehouse.clientname, 
    stock.rem, stock.refx, stock.linex, stock.ref, uom.factor, brand.brand_desc, i.itemdescription, stock.cost,stock.serialno";

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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.rftrno = stock.trno and rr.rfline = stock.line
    where stock.trno = ? and stock.line = ? 
    group by item.brand, mm.model_name, item.itemid, stock.trno, stock.line,
    item.barcode, item.itemname, stock.uom, stock.iss,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . "),
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "), 
    stock.encodeddate, stock.whid, stock.disc,  warehouse.client, warehouse.clientname, 
    stock.rem, stock.refx, stock.linex, stock.ref, uom.factor, brand.brand_desc, i.itemdescription, stock.cost,stock.serialno";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
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

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hrfhead as so 
     left join hrfstock as s on s.trno = so.trno
     where so.trno = ? 
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
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
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where stock.refx=? and head.doc = 'SJ'
    group by head.docno, head.dateid, head.trno, ar.bal
    union all 
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem, 
    head.trno
    from lahead as head
    left join lastock as stock on head.trno = stock.trno
    where stock.refx=? and head.doc = 'SJ'
    group by head.docno, head.dateid, head.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
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
        where detail.refx = ? and head.doc = 'CR'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => 'red',
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
        $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
        if (!empty($dmdata)) {
          foreach ($dmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
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
    $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='rf' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='rf' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hqtstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

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
    $data = app($this->companysetup->getreportpath($config['params']))->report_rf_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplottingpdf($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function getserialout($config)
  {
    $dinsert = [];
    $trno = $config['params']['trno'];

    foreach ($config['params']['rows'] as $key => $value) {
      $strno =  $config['params']['rows'][$key]['trno'];
      $line = $config['params']['rows'][$key]['line'];
      $stockline = $config['params']['rows'][$key]['stockline'];
      $sline = $config['params']['rows'][$key]['sline'];
      $qry = "update serialout set rftrno=?,rfline =?  where trno=? and line=? and sline=?";
      $this->coreFunctions->execqry($qry, 'update', [$trno, $stockline, $strno, $line, $sline]);
    }
    $data = $this->openstock($trno, $config);
    return ['status' => true, 'reloadgriddata' => true, 'msg' => 'Serial has been added.', 'griddata' => ['inventory' => $data]];
  } //end function  

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
          stock.cost/uom.factor as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ?
          and stock.rrcost <> 0 and cntnum.trno <>?
          UNION ALL
          select head.docno,head.dateid,stock.cost/uom.factor as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join uom on uom.itemid = item.itemid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ?
          and stock." . $this->damt . " <> 0 and cntnum.trno <>?
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function
} //end class
