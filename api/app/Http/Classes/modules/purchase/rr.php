<?php

namespace App\Http\Classes\modules\purchase;

use Illuminate\Http\Request;
use DB;
use Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\headClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\modules\calendar\em;
use App\Http\Classes\sbcscript\sbcscript;
use Exception;

class rr
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RECEIVING ITEMS';
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
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'AP1';

  public $infohead = 'cntnuminfo';
  public $hinfohead = 'hcntnuminfo';
  public $infostock = 'stockinfo';
  public $hinfostock = 'hstockinfo';

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
    'subproject',
    'branch',
    'deptid',
    'billid',
    'shipid',
    'billcontactid',
    'shipcontactid',
    'invoiceno',
    'invoicedate',
    'ewt',
    'ewtrate',
    'driver',
    'plateno',
    'cur2',
    'forex2',
    'excess',
    'excessrate',
    'istrip',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid',
    'freight',
    'agentfee',
    'checkno',
    'checkdate',
    'isfa',
    'rrfactor'
  ];
  private $otherfields = ['transtyperr'];
  private $except = ['trno', 'dateid', 'due'];
  private $blnfields = ['isfa'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  private $headClass;
  private $sbcscript;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
  ];

  private $barcode;

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
    $this->barcode = new  DNS1D;
    $this->headClass = new headClass;
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 79,
      'edit' => 80,
      'new' => 81,
      'save' => 82,
      // 'change' => 83, remove change doc
      'delete' => 84,
      'print' => 85,
      'lock' => 86,
      'unlock' => 87,
      'acctg' => 90,
      'changeamt' => 91,
      'post' => 88,
      'unpost' => 89,
      'additem' => 811,
      'edititem' => 812,
      'deleteitem' => 813,
      'updatepostedinfo' => 4449,
      'tripapproved' => 4484,
      'tripdisapproved' => 4642,
      'viewcost' => 368,
      'viewamt' => 368
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    switch ($config['params']['companyid']) {
      case 3: //conti
        $this->showfilterlabel = [
          ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
          ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
          ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']
        ];
        break;
      case 19: //housegem
        $this->showfilterlabel = [
          ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
          ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
          ['val' => 'partial', 'label' => 'Partial', 'color' => 'primary'],
          ['val' => 'served', 'label' => 'Served', 'color' => 'primary'],
          ['val' => 'all', 'label' => 'All', 'color' => 'primary']
        ];
        break;
      default:
        if ($this->companysetup->linearapproval($config['params'])) {
          array_push(
            $this->showfilterlabel,
            ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
            ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary']
          );
        }
        array_push(
          $this->showfilterlabel,
          ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
          ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
          ['val' => 'all', 'label' => 'All', 'color' => 'primary']
        );

        break;
    }


    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'client', 'listclientname', 'invoiceno', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
      case 21: //kinggeorge
        $getcols = ['action', 'liststatus', 'listdocument', 'whname', 'listdate', 'client', 'listclientname', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
      case 28: //xcomp
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'client', 'listclientname', 'yourref', 'ourref', 'ext', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
      case 59: //roosevelt
        $getcols = ['action', 'liststatus', 'listdocument', 'yourref', 'listdate', 'client', 'listclientname', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
      case 60: //transpower
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'client', 'listclientname', 'ext', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;

      default:
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'client', 'listclientname', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
    }

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';


    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $cols[$invoiceno]['label'] = 'Supplier Invoice';
        $cols[$yourref]['label'] = 'Customer PO';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$liststatus]['name'] = 'statuscolor';
        break;
      case 8: //maxipro
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$rem]['style'] = 'width:650px;whiteSpace: normal;min-width:650px;';
        break;
      default:
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$liststatus]['name'] = 'statuscolor';
        $cols[$client]['label'] = 'Code';
        $cols[$rem]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
        if ($config['params']['companyid'] == 28) { //xcomp
          $cols[$ext]['label'] = 'Grand Total';
          $cols[$ext]['align'] = 'text-right';
        }
        if ($config['params']['companyid'] == 60) { //transpower
          $cols[$ext]['label'] = 'Amount';
          $cols[$ext]['align'] = 'text-left';
          $cols[$ext]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        }

        break;
    }

    if ($config['params']['companyid'] != 56) {
      $cols[$client]['type'] = 'coldel';
    }
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    $isshortcutpo = $this->companysetup->getisshortcutpo($config['params']);

    $fields = [];
    if ($isshortcutpo) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 81);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }

    $col1 = $this->fieldClass->create($fields);
    $data = [];
    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['selectprefix', 'docno'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'docno.type', 'input');
        data_set($col2, 'docno.label', 'Search');
        data_set($col2, 'selectprefix.label', 'Search by');
        data_set($col2, 'selectprefix.type', 'lookup');
        data_set($col2, 'selectprefix.lookupclass', 'lookupsearchby');
        data_set($col2, 'selectprefix.action', 'lookupsearchby');
        $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");
        $data = $data[0];
        break;
    }

    return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {

    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $isproject = $this->companysetup->getisproject($config['params']);
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $condition = '';
    $projectfilter = '';
    $limit = '';
    $fields = '';

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    $join = '';
    $hjoin = '';
    $addparams = '';

    $balfilter = '';
    $groupbylocal = '';
    $groupby = '';
    $having = '';
    $filterstat = " and num.statid = 0 ";
    if ($this->companysetup->linearapproval($config['params'])) {
      $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : $itemfilter;
      $user = $config['params']['user'];
      $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);
      if ($userid != 0) {
        $qry = "select s.isapprover as value
                from approversetup as s
                left join approverdetails as d on d.appline=s.line
                left join useraccess as u on u.username=d.approver
                where u.userid=? and s.doc =?";

        $isapprover = $this->coreFunctions->datareader($qry, [$userid, $doc]);
        if ($isapprover == 1) {
          $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
        }
      }
    }
    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }

    $status = "'DRAFT'";
    $lstatus = "'DRAFT'";
    $lstatcolor = "'red'";

    switch ($itemfilter) {
      case 'draft':
        $condition = " and head.lockdate is null and  num.postdate is null $filterstat ";
        if ($companyid == 3) { //conti
          $condition = ' and num.postdate is null and head.lockdate is null ';
        }

        break;
      case 'forapproval':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=10 
                        and num.appuser='" . $config['params']['user'] . "'";
        $lstatus = "'FOR APPROVAL'";
        break;
      case 'approved':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=36";
        $lstatus = "'APPROVED'";
        break;
      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        $lstatus = "'LOCKED'";
        $lstatcolor = "'green'";
        break;

      case 'partial':
        $balfilter = ' and num.postdate is not null and rrstatus.bal>0';
        $status = "'PARTIAL'";
        break;

      case 'served':
        $balfilter = ' and num.postdate is not null';
        $having = ' having sum(rrstatus.bal)=0';
        $status = "'SERVED'";
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'all':
        $lstatus = " case when num.statid=36 then 'Approved' when num.statid=10 then 'For approval' else 'Draft' end ";
        $lstatcolor = " case when num.statid=36 or num.statid=10 then 'grey' else 'red' end";
        break;
    }
    $companyid = $config['params']['companyid'];
    $status = "'POSTED'";
    $gstatcolor = "'grey'";
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $status = "(case (select format(sum(ar.bal),2) from apledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=head.trno and coa.alias ='AP1') when 0 then 'PAID'
        else 'UNPAID' end)";
        $gstatcolor = "(case (select format(sum(ar.bal),2) from apledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=head.trno and coa.alias ='AP1') when 0 then 'green'
        else 'orange' end)";
        $fields .= ",date_format(head.dateid,'%m-%d-%Y') as dateid, head.invoiceno,head.dateid as dateid2 ";
        if ($search == "") $limit = 'limit 25';
        $orderby =  "order by  dateid2 desc, docno desc";
        break;
      case 19: //housegem
        $fields .= ",left(head.dateid,10) as dateid";
        if ($search == "") $limit = 'limit 150';
        $orderby =  "order by docno desc, dateid desc";
        if ($itemfilter == 'partial' || $itemfilter == 'served') {
          $hjoin .= " left join glstock as stock on stock.trno=head.trno left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line";
          $groupby = ' group by head.trno,head.docno,head.clientname, head.createby,head.editby,head.viewby, num.postedby, num.postdate,head.yourref, head.ourref ,head.dateid';
        }
        break;
      case 21: //kinggeorge
        $fields .= ",left(head.dateid,10) as dateid,concat(wh.client,'~',wh.clientname) as whname";
        $join .= "left join client as wh on wh.client=head.wh";
        $hjoin .= "left join client as wh on wh.clientid=head.whid";
        if ($search == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      case 28: //xcomp
        $fields .= ",left(head.dateid,10) as dateid, format(sum(stock.ext),2) as ext";
        $join .= "left join " . $this->stock . " as stock on stock.trno=head.trno";
        $hjoin .= "left join " . $this->hstock . " as stock on stock.trno=head.trno";
        $groupby = ' group by head.trno,head.docno,head.clientname, num.statid,head.createby,head.editby,head.viewby, num.postedby, num.postdate,head.yourref, head.ourref ,head.dateid,head.lockdate,head.rem';
        $groupbylocal = $groupby;
        if ($search == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      case 60: //transpower
        $fields .= ",left(head.dateid,10) as dateid, format(sum(stock.ext),2) as ext";
        $join .= "left join " . $this->stock . " as stock on stock.trno=head.trno";
        $hjoin .= "left join " . $this->hstock . " as stock on stock.trno=head.trno";
        $groupby = ' group by head.trno,head.docno,client.client,head.clientname, num.statid,head.createby,head.editby,head.viewby, num.postedby, num.postdate,head.yourref, head.ourref ,head.dateid,head.lockdate,head.rem';
        $groupbylocal = ' group by head.trno,head.docno,head.client,head.clientname, num.statid,head.createby,head.editby,head.viewby, num.postedby, num.postdate,head.yourref, head.ourref ,head.dateid,head.lockdate,head.rem';
        $orderby =  "order by  dateid desc, docno desc";
        if ($search == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      default:
        $fields .= ",left(head.dateid,10) as dateid";
        if ($search == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
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
            $join .= " left join lastock on lastock.trno = head.trno
            left join item on item.itemid = lastock.itemid left join item as item2 on item2.itemid = lastock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin .= " left join glstock on glstock.trno = head.trno
            left join item on item.itemid = glstock.itemid left join item as item2 on item2.itemid = glstock.itemid
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

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          array_push($searchfield, 'head.invoiceno');
          break;
        case 28: //xcomp
          array_push($searchfield, 'head.rem');
          break;
      }

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }

      $limit = '';
    }


    $qry = "select head.trno,head.docno,head.client,head.clientname, case ifnull(head.lockdate,'') when '' then $lstatus else 'LOCKED' end as status,
    case ifnull(head.lockdate,'') when '' then $lstatcolor else 'green' end as statuscolor,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref, head.rem $fields
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     " . $join . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $filtersearch . $addparams . $groupbylocal . " "  . "
     union all
     select head.trno,head.docno,client.client,head.clientname,$status as status,$gstatcolor as statuscolor,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref, head.rem $fields
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno left join client on client.clientid=head.clientid
     " . $hjoin . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $filtersearch . $addparams . $balfilter . $groupby . $having . " "  . "
    $orderby  $limit";
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

    if ($config['params']['companyid'] == 14) { //majesty
      array_push($btns, 'others');
    }

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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

    switch ($config['params']['companyid']) {
      case 19: //housegem
        $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload HGC DR', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
        break;
      case 14: //majesty
      case 56: // homeworks
      case 40: //cdo
        $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
        $buttons['others']['items']['downloadexcel'] = ['label' => 'Download RR Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
        break;
    }

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'rr', 'title' => 'Receiving Items Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];

    $othercharges = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewothercharges']];
    if ($companyid == 59) { //roosevelt
      $return['OTHER CHARGES'] = ['icon' => 'fa fa-envelope', 'customform' => $othercharges];
    }

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    if ($companyid == 10) { //afti
      $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
    }

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    if ($config['params']['companyid'] == 60) { //transpower      
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5492);
      if ($changecode) {
        $changecode = ['customform' => ['action' => 'customform', 'lookupclass' => 'changebarcode']];
        $return['CHANGE CODE'] = ['icon' => 'fa fa-qrcode', 'customform' => $changecode];
      }
    }
    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $isproject = $this->companysetup->getisproject($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $isfa = $this->companysetup->getisfixasset($config['params']);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $rr_btnreceived_access = $this->othersClass->checkAccess($config['params']['user'], 2728);
    $rr_btnunreceived_access = $this->othersClass->checkAccess($config['params']['user'], 2729);
    $makecv = $this->othersClass->checkAccess($config['params']['user'], 3577);

    $trip_tab = $this->othersClass->checkAccess($config['params']['user'], 4482);
    $arrived_tab = $this->othersClass->checkAccess($config['params']['user'], 4483);
    $trip_approve = $this->othersClass->checkAccess($config['params']['user'], 4484);
    $trip_disapprove = $this->othersClass->checkAccess($config['params']['user'], 4642);

    $isserial = $this->companysetup->getserial($config['params']);
    $invonly = $this->companysetup->isinvonly($config['params']);

    $systype = $this->companysetup->getsystemtype($config['params']);

    $allowassettag = $this->othersClass->checkAccess($config['params']['user'], 3619);
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451); //kinggeorge
    $allowchange_amount = $this->othersClass->checkAccess($config['params']['user'], 91);


    $allowgenerateapv = false;
    if ($this->companysetup->isgenerateapv($config['params'])) {
      $allowgenerateapv = $this->othersClass->checkAccess($config['params']['user'], 5221);
    }

    $column = ['action', 'itemdescription', 'serialno', 'rrqty', 'uom', 'kgs', 'rrcost', 'disc', 'cost', 'ext', 'freight', 'wh', 'whname', 'ref', 'poref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'isbo', 'qa', 'void'];
    $sortcolumn =  ['action', 'itemdescription', 'serialno', 'rrqty', 'uom', 'kgs', 'rrcost', 'disc', 'cost', 'ext', 'freight', 'wh', 'whname', 'ref', 'poref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'isbo', 'qa', 'void'];

    if ($companyid == 40) { //cdo
      $column = ['action', 'itemdescription', 'rrqty', 'uom', 'serialno', 'pnp', 'kgs', 'rrcost', 'disc', 'cost', 'ext', 'freight', 'wh', 'whname', 'ref', 'poref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'isbo'];
      $sortcolumn =  ['action', 'itemdescription', 'rrqty', 'uom', 'serialno', 'pnp', 'kgs', 'rrcost', 'disc', 'cost', 'ext', 'freight', 'wh', 'whname', 'ref', 'poref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'isbo'];
    }

    if ($companyid == 63) { //ericco
       $column = ['action', 'itemdescription', 'serialno', 'rrqty', 'original_qty', 'uom', 'kgs', 'rrcost', 'disc', 'cost', 'ext', 'freight', 'wh', 'whname', 'ref', 'poref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'isbo', 'qa', 'void'];
      $sortcolumn =  ['action', 'itemdescription', 'serialno', 'rrqty', 'original_qty', 'uom', 'kgs', 'rrcost', 'disc', 'cost', 'ext', 'freight', 'wh', 'whname', 'ref', 'poref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'isbo', 'qa', 'void'];
    }
    foreach ($column as $key => $value) {
      $$value = $key;
    }

    switch ($systype) {
      case 'REALESTATE':
        $project = 29;
        $phasename = 30;
        $housemodel = 31;
        $blk = 32;
        $lot = 33;
        $amenityname = 34;
        $subamenityname = 35;
        array_push($column, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        array_push($sortcolumn, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }


    if ($invonly) {
      $headgridbtns = ['viewref', 'viewdiagram'];
    } else {
      if ($allowgenerateapv) {
        $headgridbtns = ['genapv', 'viewref', 'viewdiagram'];
      } else {
        $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram'];
      }
    }


    if ($isfa || $systype == 'FAMS') {
      array_push($headgridbtns, 'generatedepsched');
    }

    if ($companyid == 3) { //conti
      array_push($headgridbtns, 'tagreceived');
      array_push($headgridbtns, 'untagreceived');

      if ($rr_btnreceived_access == 0) {
        unset($headgridbtns[3]);
      }

      if ($rr_btnunreceived_access == 0) {
        unset($headgridbtns[4]);
      }
    }

    switch ($companyid) {
      case 10: //afti
      case 28: //xcomp
      case 36: //rozlab
        array_push($headgridbtns, 'viewitemstockinfo');
        if ($makecv != 0) {
          array_push($headgridbtns, 'makecv');
        }
        break;
      case 43: //mighty
        if ($trip_approve) array_push($headgridbtns, 'tripapproved');
        if ($trip_disapprove) array_push($headgridbtns, 'tripdisapproved');
        break;
      case 60://transpower
        array_push($headgridbtns, 'viewitemstockinfo');
        break;
    }

    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

    if ($iskgs) {
      $computefield['kgs'] = 'kgs';
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ]
    ];

    switch ($companyid) {
      case 43: //mighty
        if ($trip_tab) $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'tripdetails', 'access' => 'trip'], 'label' => 'TRIP'];
        if ($arrived_tab) $tab['customform2'] = ['event' => ['action' => 'customform', 'lookupclass' => 'tripdetails2', 'access' => 'arrived'], 'label' => 'ARRIVED'];
        break;

      case 37: //megacrystal
        $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'sjrelease', 'access' => 'release'], 'label' => 'UPDATE INFO'];
        break;
    }

    if ($systype == 'FAMS') {
      if ($allowassettag) {
        $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'viewrrfams', 'label' => 'Asset Tag'];
      }
    }

    if ($this->companysetup->getserial($config['params'])) {
      $stockbuttons = ['save', 'delete', 'serialin'];
    } else {
      
      switch ($companyid) {
        case 21: //kinggeorge
          if ($allowviewbalance != 0) {
            $stockbuttons = ['save', 'delete', 'showbalance'];
          } else {
            $stockbuttons = ['save', 'delete'];
          }
          break;
        case 59: //ROOSEVELT
          $stockbuttons = ['delete'];
          break;
        default:
          $stockbuttons = ['save', 'delete', 'showbalance'];
          break;
      }
    }

    if ($this->othersClass->checkAccess($config['params']['user'], 4609)) {
      $tab['stockinfotab'] = ['action' => 'tableentry', 'lookupclass' => 'rrtabstock', 'label' => 'UPDATE PNP&CSR#', 'checkchanges' => 'tableentry'];
    }

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    switch ($systype) {
      case 'AIMS':
        if ($companyid == 0 || $companyid == 36) { //main,rozlab
          array_push($stockbuttons, 'stockinfo');
        }
        break;
      case 'AIMSPOS':
        if ($companyid == 39) { //cbbsi
          array_push($stockbuttons, 'stockinfo');
        }
        break;
      case 'AIMSPAYROLL': //XCOMP
        array_push($stockbuttons, 'stockinfo');
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$rrcost]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Buying Kgs';
    $obj[0]['inventory']['columns'][$pallet]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
    $obj[0]['inventory']['columns'][$stage]['readonly'] = true;

    $obj[0]['inventory']['columns'][$partno]['label'] = 'Part No.';
    $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
    $obj[0]['inventory']['columns'][$partno]['align'] = 'left';
    $obj[0]['inventory']['columns'][$partno]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$subcode]['label'] = 'Old SKU';
    $obj[0]['inventory']['columns'][$subcode]['type'] = 'label';
    $obj[0]['inventory']['columns'][$subcode]['align'] = 'left';
    $obj[0]['inventory']['columns'][$subcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$boxcount]['label'] = 'QTY Per Box';
    $obj[0]['inventory']['columns'][$boxcount]['type'] = 'label';
    $obj[0]['inventory']['columns'][$boxcount]['align'] = 'left';
    $obj[0]['inventory']['columns'][$boxcount]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0]['inventory']['columns'][$loc]['type'] = 'input';

    if ($companyid == 59) { //roosevelt
      if ($allowchange_amount == '0') {
        $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
      }
    } else {
      if ($viewcost == '0') {
        $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
      }
    }

    if (!$isexpiry) {
      if ($companyid == 8) { // maxipro
        $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
        $obj[0]['inventory']['columns'][$wh]['type'] = 'label';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
      } else {
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
      }
    } else {
      $obj[0]['inventory']['columns'][$loc]['class'] = 'sbccsenablealways';
      $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
      $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
      $obj[0]['inventory']['columns'][$expiry]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    }

    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    if (!$isproject) {
      $obj[0]['inventory']['columns'][$stage]['type'] = 'coldel';
    }

    if (!$ispallet) {
      $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
    }

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $obj[0]['inventory']['columns'][$itemdescription]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
        $obj[0]['inventory']['columns'][$itemdescription]['readonly'] = true;
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
        $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
        $obj[0]['inventory']['columns'][$serialno]['readonly'] = true;
        $obj[0]['inventory']['columns'][$serialno]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$poref]['label'] = 'Customer PO';
        $obj[0]['inventory']['columns'][$isbo]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
        break;
      case 19: //housegem
        $obj[0]['inventory']['columns'][$isbo]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        break;
      case 24: //goodfound
        $obj[0]['inventory']['columns'][$cost]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
        $obj[0]['inventory']['columns'][$loc]['label'] = 'Batch No';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isbo]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        break;
      case 39: //cbbsi
        $obj[0]['inventory']['columns'][$rrqty]['checkfield'] = 'void';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refporr';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        break;
      case 42: //pdpi
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isbo]['type'] = 'coldel';
        break;
      case 40: //cdo
        $obj[0]['inventory']['columns'][$serialno]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
        $obj[0]['inventory']['columns'][$serialno]['type'] = 'textarea2';
        $obj[0]['inventory']['columns'][$serialno]['readonly'] = true;
        $obj[0]['inventory']['columns'][$serialno]['label'] = 'Engine/Chassis#';
        $obj[0]['inventory']['columns'][$pnpcsr]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$pnpcsr]['readonly'] = true;
        $obj[0]['inventory']['columns'][$pnpcsr]['label'] = 'PNP/CSR#';
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isbo]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        break;
      case 50: //unitech
        $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;

        $obj[0]['inventory']['columns'][$isbo]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        break;
      case 63://ericco
         $obj[0]['inventory']['columns'][$original_qty]['label'] = 'PO Qty';
         $obj[0]['inventory']['columns'][$original_qty]['type'] = 'label';
        $obj[0]['inventory']['columns'][$original_qty]['style'] = 'text-align: center; width: 80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        break;  

      default:
        $obj[0]['inventory']['columns'][$isbo]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        break;
    }

    if ($companyid != 10) { //not afti
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$poref]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$serialno]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
    }

    if ($companyid != 6) { //not mitsukoshi
      $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
    }

    if ($companyid != 24) { //not goodfound
      if ($viewcost == '0') {
        if ($viewcost == '0') {
          $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        }
      }
      $obj[0]['inventory']['columns'][$freight]['type'] = 'coldel';
    }


    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila, technolab
      $obj[0]['inventory']['columns'][$loc]['label'] = 'Lot/Serial#';
      $obj[0]['inventory']['columns'][$expiry]['label'] = 'Expiry/Mfr Date';
    }

    if ($companyid != 39) { //not cbbsi
      $obj[0]['inventory']['columns'][$qa]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$void]['type'] = 'coldel';
    }

    switch ($systype) {
      case 'REALESTATE':
        $obj[0]['inventory']['columns'][$blk]['readonly'] = true;
        $obj[0]['inventory']['columns'][$lot]['readonly'] = true;
        break;
    }

    if ($companyid == 47) { //kitchenstar
      if ($viewcost == '0') {
        $obj[0]['inventory']['showtotal'] = false;
      }
    }

    if ($companyid == 60) { //transpower
      $this->modulename = 'Receiving Report';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $isserial = $this->companysetup->getserial($config['params']);
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 10: //afti
        $this->modulename = 'PURCHASE RECEIVING';
        break;
      case 24: //goodfound
        $this->modulename = 'SUPPLIES RECEIVING REPORT';
        break;
      case 3: //conti
      case 28: //xcomp
      case 36: //rozlab
      case 39: //cbbsi
      case 43: //mighty
        $this->modulename = 'RECEIVING REPORT';
        break;
    }

    switch ($companyid) {
      case 60: //transpower
        $tbuttons = ['pendingsj', 'pendingpo', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;
      case 10: //afti
      case 14: //majesty
        $tbuttons = ['pendingpo', 'additem',  'saveitem', 'deleteallitem'];
        break;
      case 28: //xcomp
        $tbuttons = ['poserial', 'pendingpo', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;
      case 8: //maxipro
        $tbuttons = ['pendingpo', 'saveitem', 'deleteallitem'];
        break;
      case 63: //ericco
        $tbuttons = ['pendingpo', 'multiitem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;

      default:
        if ($isserial) {
          $tbuttons = ['poserial', 'pendingpo', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        } else {
          $tbuttons = ['pendingpo', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        }
        break;
    }

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      if ($viewall == '0') {
        $tbuttons = ['pendingpo', 'saveitem', 'deleteallitem'];
      }
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($companyid == 10) { //afti
      $obj[0]['action'] = "pendingpodetail";
    }

    if ($companyid == 8) { //maxipro
      $obj[1]['label'] = "SAVE ALL";
      $obj[2]['label'] = "DELETE ALL";
    }

    if ($companyid == 49) { //hotmix
      $obj[0]['access'] = "save";
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $inv = $this->companysetup->isinvonly($config['params']);
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4851);

    $fields = ['docno', 'client', 'clientname', 'address'];
    if ($companyid == 10) { //afti
      $fields = ['docno', 'client', 'clientname', 'dacnoname', 'dwhname'];
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    if ($inv) {
      $fields = [['dateid', 'terms'], 'due', 'dwhname'];
    } else {
      $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dwhname'];
    }

    switch ($companyid) {
      case 10: //afti
        data_set($col1, 'clientname.type', 'textarea');
        data_set($col1, 'dacnoname.label', 'AP Account');
        data_set($col1, 'dwhname.condition', ['checkstock']);
        $fields = [['dateid', 'terms'], 'due', ['dewt', 'dvattype'], 'invoiceno', 'invoicedate'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'invoiceno.label', 'Supplier Invoice No.');
        data_set($col2, 'invoiceno.required', true);
        data_set($col2, 'invoicedate.required', true);
        data_set($col2, 'invoiceno.type', 'cinput');
        data_set($col2, 'invoiceno.maxlength', 25);
        break;
      case 24: //goodfound
        $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dwhname', ['dewt', 'dexcess']];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dacnoname.label', 'AP Account');
        data_set($col2, 'dwhname.condition', ['checkstock']);
        break;

      case 8: //maxipro
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dacnoname.label', 'AP Account');
        // data_set($col2, 'dwhname.type', 'input');
        break;
      case 39: //cbbsi
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dacnoname.label', 'Purchase Expense Account');
        data_set($col2, 'dwhname.condition', ['checkstock']);
        break;
      case 40: //cdo
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dacnoname.label', 'AP Account');
        data_set($col2, 'dwhname.type', 'input');
        data_set($col2, 'dwhname.readonly', true);
        if ($noeditdate) {
          data_set($col2, 'dateid.class', 'sbccsreadonly');
        }
        break;

      default:
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dacnoname.label', 'AP Account');
        data_set($col2, 'dwhname.condition', ['checkstock']);
        break;
    }
    //col3
    $fields = ['yourref', 'ourref', ['cur', 'forex'], 'dprojectname'];

    switch ($companyid) {
      case 42: // PDPI
        $fields = ['yourref', 'ourref', ['cur', 'forex']];
        $col3 = $this->fieldClass->create($fields);
        break;
      case 10: //afti
        $fields = [['cur', 'forex'], 'dbranchname', 'ddeptname'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'dbranchname.required', true);
        data_set($col3, 'ddeptname.label', 'Department');
        break;
      case 19: //housegem
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'driver'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'driver.label', 'Driver Name');
        break;
      case 8: //maxipro
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'transtyperr'];
        $col3 = $this->fieldClass->create($fields);
        break;
      case 21: // kinggeogre
        $fields = ['yourref', 'ourref', ['cur', 'forex']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'ourref.label', 'DR No.');
        data_set($col3, 'yourref.label', 'SI No.');
        break;
      case 1: //vitaline
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname'];
        $col3 = $this->fieldClass->create($fields);
        break;
      case 39: //cbbsi
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'carrier'];
        $col3 = $this->fieldClass->create($fields);
        break;
      case 56: //homeworks
        $fields = ['yourref', 'ourref', ['cur', 'forex'], ['dprojectname', 'dewt'], 'isfa'];
        $col3 = $this->fieldClass->create($fields);
        break;
      default:
        if ($systype == 'REALESTATE') {
          $fields = ['yourref', 'ourref', ['cur', 'forex'], 'rem'];
        }
        $col3 = $this->fieldClass->create($fields);
        break;
    }

    if ($companyid == 22) { //eipi
      data_set($col3, 'yourref.label', 'Reference No.');
    }

    if ($companyid == 60) { //transpower
      data_set($col3, 'yourref.label', 'SI #');
      data_set($col3, 'ourref.label', 'PO #');
    }


    if ($this->companysetup->getisproject($config['params'])) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);

      if ($viewall) {
        data_set($col3, 'dprojectname.lookupclass', 'projectcode');
        data_set($col3, 'dprojectname.addedparams', []);
        data_set($col3, 'dprojectname.required', true);
        data_set($col3, 'dprojectname.condition', ['checkstock']);
        $fields = ['rem', 'subprojectname'];
        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }

        if ($companyid == 8) { //maxipro
          $accessupinfo = $this->othersClass->checkAccess($config['params']['user'], 4449);
          if ($accessupinfo) {
            array_push($fields, 'updatepostedinfo');
          }
        }
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.style', 'height: 130px; max-width: 400px;font-size:120%;');
        data_set($col4, 'subprojectname.required', false);

        if ($companyid == 8) { //maxipro
          data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');
          data_set($col4, 'updatepostedinfo.access', 'view');
        }
      } else {
        data_set($col3, 'dprojectname.type', 'input');
        $fields = ['rem', 'subprojectname'];
        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }

        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.style', 'height: 130px; max-width: 400px;font-size:120%;');
        data_set($col4, 'subprojectname.type', 'lookup');
        data_set($col4, 'subprojectname.lookupclass', 'lookupsubproject');
        data_set($col4, 'subprojectname.action', 'lookupsubproject');
        data_set($col4, 'subprojectname.addedparams', ['projectid']);
        data_set($col4, 'subprojectname.required', true);
      }
    } else {
      switch ($companyid) {
        case 3: //conti
          $fields = ['rem', 'lblreceived'];
          if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
          }
          $col4 = $this->fieldClass->create($fields);
          break;
        case 10: //afti
        case 12: //afti usd
          $fields = ['yourref', 'rem'];
          if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
          }
          array_push($fields, 'lblpaid');
          $col4 = $this->fieldClass->create($fields);
          data_set($col4, 'yourref.label', 'Customer PO');
          break;
        case 19: //housegem
          $fields = ['rem', 'plateno'];
          $col4 = $this->fieldClass->create($fields);
          break;
        case 28: //xcomp
          $fields = ['rem', 'updatepostedinfo'];
          $col4 = $this->fieldClass->create($fields);
          data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');
          break;
        case 39: //cbbsi
          $fields = ['rem', 'waybill'];
          if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
          }
          array_push($fields, 'updatepostedinfo');
          $col4 = $this->fieldClass->create($fields);
          data_set($col4, 'waybill.type', 'input');
          data_set($col4, 'waybill.maxlength', '');
          data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');
          break;
        case 43: //mighty
          $fields = ['rem', ['istrip', 'lblapproved']];
          $col4 = $this->fieldClass->create($fields);
          data_set($col4, 'lblapproved.type', 'label');
          data_set($col4, 'lblapproved.label', 'APPROVED!');
          data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');
          break;
        case 47: //kitchenstar
          $fields = ['rem', ['freight', 'agentfee']];
          $col4 = $this->fieldClass->create($fields);
          break;
        case 56: //homeworks
          $fields =  ['ref', ['checkdate', 'checkno'], 'rem', 'updatepostedinfo'];
          $col4 = $this->fieldClass->create($fields);
          data_set($col4, 'ref.label', 'APV No.');
          data_set($col4, 'checkdate.label', 'Counter Date');
          data_set($col4, 'checkno.label', 'Counter #');
          data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');
          break;
        case 63: //ericco
          $fields = ['rem', 'rrfactor'];
          $col4 = $this->fieldClass->create($fields);
          break;
        default:
          $fields = ['rem'];
          if ($systype == 'REALESTATE') {
            $fields = ['dprojectname', 'phase', 'housemodel', ['blklot', 'lot'], 'amenityname', 'subamenityname'];
            $col4 = $this->fieldClass->create($fields);
            data_set($col4, 'dprojectname.lookupclass', 'project');
            data_set($col4, 'phase.addedparams', ['projectid']);
            data_set($col4, 'housemodel.addedparams', ['projectid']);
            data_set($col4, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
            data_set($col4, 'subamenityname.addedparams', ['amenityid']);
          } else {
            if ($this->companysetup->getistodo($config['params'])) {
              array_push($fields, 'donetodo');
            }
            if ($this->companysetup->linearapproval($config['params'])) {
              array_push($fields, 'forapproval', 'doneapproved', 'lblapproved');
            }

            if ($systype == 'FAMS') {
              array_push($fields, 'create');
            }
            $col4 = $this->fieldClass->create($fields);
            data_set($col4, 'lblapproved.type', 'label');
            data_set($col4, 'lblapproved.label', 'APPROVED!');
            data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');

            if ($systype == 'FAMS') {
              data_set($col4, 'create.type', 'actionbtn');
              data_set($col4, 'create.label', 'GENERATE ASSET TAG');
              data_set($col4, 'create.confirm', true);
              data_set($col4, 'create.confirmlabel', 'Generate asset tag?');
              data_set($col4, 'create.access', 'save');
              data_set($col4, 'create.lookupclass', 'stockstatusposted');
              data_set($col4, 'create.action', 'generatetag');
              data_set($col4, 'create.style', 'width:100%');
            }
          }

          break;
      }
    }

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
    $data[0]['clientname'] = '';
    $data[0]['address'] = '';
    $data[0]['terms'] = '';
    if ($params['companyid'] == 39) { //cbbsi
      $data[0]['client'] = 'CL0000000000003';
      $clientname = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', ['CL0000000000003']);
      $address = $this->coreFunctions->getfieldvalue('client', 'addr', 'client=?', ['CL0000000000003']);
      $terms = $this->coreFunctions->getfieldvalue('client', 'terms', 'client=?', ['CL0000000000003']);
      $data[0]['due'] = $this->othersClass->computeterms($data[0]['dateid'], '', $terms);
      $data[0]['address'] = $address;
      $data[0]['clientname'] = $clientname;
      $data[0]['carrier'] = '';
      $data[0]['waybill'] = '';
    }
    $data[0]['yourref'] = '';
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);

    switch ($params['companyid']) {
      case 49: //hotmix
      case 8: //maxipro
        $data[0]['tax'] = 12;
        $data[0]['vattype'] = 'VATABLE';
        break;

      default:
        $data[0]['tax'] = 0;
        $data[0]['vattype'] = 'NON-VATABLE';
        break;
    }

    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    switch ($params['companyid']) {
      case 39: //cbbsi
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);
        break;
    }

    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;

    $isproject = $this->companysetup->getisproject($params);

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($params['user'], 2232);
      $data[0]['projectid'] = '0';
      $data[0]['projectname'] = '';
      $data[0]['projectcode'] = '';

      if ($viewall == '0') {
        $pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$params['user']]);
        $data[0]['projectid'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$pid]);
        $data[0]['projectcode'] =  $pid;
        $data[0]['projectname'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "name", "code=?", [$pid]);
      }
    } else {
      $data[0]['projectid'] = '0';
      $data[0]['projectname'] = '';
      $data[0]['projectcode'] = '';
    }

    $data[0]['dprojectname'] = '';
    $data[0]['subproject'] = '0';
    $data[0]['subprojectname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = '0';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['billid'] = 0;
    $data[0]['shipid'] = 0;
    $data[0]['billcontactid'] = 0;
    $data[0]['shipcontactid'] = 0;
    $data[0]['invoiceno'] = '';
    $data[0]['invoicedate'] = $this->othersClass->getCurrentDate();
    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['driver'] = '';
    $data[0]['plateno'] = '';
    $data[0]['forex2'] = 1;
    $data[0]['cur2'] = $this->companysetup->getdefaultcurrency($params);

    $data[0]['excess'] = '';
    $data[0]['dexcess'] = '';
    $data[0]['excessrate'] = 0;
    $data[0]['transtyperr'] = '';
    $data[0]['istrip'] = '0';


    $data[0]['phaseid'] = 0;
    $data[0]['phase'] = '';

    $data[0]['modelid'] = 0;
    $data[0]['housemodel'] = '';

    $data[0]['blklotid'] = 0;
    $data[0]['blklot'] = '';
    $data[0]['lot'] = '';


    $data[0]['amenityid'] = 0;
    $data[0]['amenityname'] = '';

    $data[0]['subamenityid'] = 0;
    $data[0]['subamenityname'] = '';

    $data[0]['freight'] = '';
    $data[0]['agentfee'] = '';
    $data[0]['checkdate'] = $this->othersClass->getCurrentDate();
    $data[0]['checkno'] = '';

    $data[0]['ref'] = '';
    $data[0]['isfa'] = 0;

    $data[0]['rrfactor'] = 0;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $tablenum = $this->tablenum;
    $isproject = $this->companysetup->getisproject($config['params']);
    $isapproved = $this->othersClass->isapproved($config['params']['trno'], "hcntnuminfo");
    $isgenerateapv = $this->companysetup->isgenerateapv($config['params']);

    $projectfilter = "";

    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $center = $config['params']['center'];
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $info = $this->infohead;
    $hinfo = $this->hinfohead;

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }

    $addedfields = ",'' as ref";
    $leftjoin = "";

    if ($isgenerateapv) {
      $addedfields = ",pvnum.docno as ref";
      $leftjoin = " LEFT JOIN cntnum as pvnum on pvnum.trno=head.pvtrno";
    }

    $qryselect = "select
        num.center,
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
        head.billid,
        head.shipid,
        head.isfa,
        head.billcontactid,
        head.shipcontactid,
        '' as dvattype,
        warehouse.client as wh,
        warehouse.clientname as whname,
        '' as dwhname,
        cast(ifnull(head.istrip,0) as char) as istrip,
        head.projectid,
        '' as dprojectname,
        '' as dexcess,
        left(head.due,10) as due,
        client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
        head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
        head.deptid,'' as ddeptname,head.invoiceno,left(head.invoicedate,10) as invoicedate,head.ewt,head.ewtrate,head.excess,head.excessrate,
        head.driver,head.plateno,head.cur2,head.forex2,hinfo.carrier,hinfo.waybill,cinfo.transtype as transtyperr,head.freight,head.agentfee,num.statid,
        
        head.phaseid, 
        ph.code as phase,

        head.modelid, 
        hm.model as housemodel, 
        
        head.blklotid, 
        bl.blk as blklot, 
        bl.lot,
        
        amh.line as amenityid,
        amh.description as amenityname,
        subamh.line as subamenityid,
        subamh.description as subamenityname, left(head.checkdate,10) as checkdate,head.checkno,head.rrfactor
        " . $addedfields;

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as b on b.clientid = head.branch
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        left join " . $info . " as hinfo on hinfo.trno=head.trno
        left join cntnuminfo as cinfo on cinfo.trno=head.trno

        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid
         $leftjoin
        
        where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as b on b.clientid = head.branch
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        left join " . $hinfo . " as hinfo on hinfo.trno=head.trno
        left join hcntnuminfo as cinfo on cinfo.trno=head.trno

        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid
         $leftjoin

        where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

      $receivedby = $this->coreFunctions->datareader("select receivedby as value from cntnum  where trno=?", [$trno]);

      $lblreceived_stat = $receivedby == "" ? true : false;
      $hideobj = ['lblreceived' => $lblreceived_stat];
      $hideheadergridbtns = ['tagreceived' => !$lblreceived_stat, 'untagreceived' => $lblreceived_stat];
      // fortesting
      if ($this->companysetup->linearapproval($config['params'])) {
        switch ($head[0]->statid) {
          case 10: // forapproval
            $hideobj = ['forapproval' => true, 'doneapproved' => false, 'lblapproved' => true];
            break;
          case 36: // approved
            $hideobj = ['forapproval' => true, 'doneapproved' => true, 'lblapproved' => false];
            break;
          case 0: // draf
            $hideobj = ['forapproval' => false, 'doneapproved' => true, 'lblapproved' => true];
            break;
        }
      }
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj['donetodo'] = !$btndonetodo;
      }
      $hideobj['updatepostedinfo'] = true;

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $lvlpaid = true;
          if ($isposted) {
            $bal = $this->coreFunctions->datareader("select sum(bal) as value from apledger  where trno=?", [$trno]);
            $lvlpaid = $bal == 0 ? false : true;
          }
          $hideobj = ['lblpaid' => $lvlpaid];
          break;
        case 8: //maxipro
          if ($isposted) {
            $hideobj['updatepostedinfo'] = false;
          }
          break;
        case 28: //xcomp
        case 56: //homeworks
          $hideobj['updatepostedinfo'] = !$isposted;
          break;
        case 43: //mighty
          $hideobj = ['lblapproved' => !$isapproved];
          $hideheadergridbtns = ['tripapproved' => $isapproved, 'tripdisapproved' => !$isapproved];
          break;
      }

      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
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

    if ($data['terms'] == '') {
      $data['due'] =  $data['dateid'];
    } else {
      $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($data['invoicedate'] == null) {
      $data['invoicedate'] = '';
    }
    if ($isupdate) {
      // var_dump($data['wh']);
      if ($companyid != 8) { //not maxipro
        $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      }

      switch ($companyid) {
        case 39: //cbbsi
          $info = [];
          $info['carrier'] = $head['carrier'];
          $info['waybill'] = $head['waybill'];
          $exist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
          if (floatval($exist) <> 0) {
            $this->coreFunctions->sbcupdate("cntnuminfo", $info, ['trno' => $head['trno']]);
          } else {
            $info['trno'] = $head['trno'];
            $this->coreFunctions->sbcinsert("cntnuminfo", $info);
          }

          break;
        case 8: //maxipro
          $wh = $data['wh'];
          $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);

          $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
          $this->coreFunctions->sbcupdate($this->stock, ['whid' => $whid], ['trno' => $head['trno']]);

          $chktranstype = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$config['params']['trno']]);
          $info = [];
          if (empty($chktranstype)) {
            $info['trno'] = $head['trno'];
            $info['transtype'] = $head['transtyperr'];
            $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          } else {
            $info['transtype'] = $head['transtyperr'];
            $this->coreFunctions->sbcupdate('cntnuminfo', $info, ['trno' => $head['trno']]);
          }

          break;
      }


      $this->recomputecost($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);


      switch ($companyid) {
        case 39: //cbbsi
          $info = [];
          $info['trno'] = $head['trno'];
          $info['carrier'] = $head['carrier'];
          $info['waybill'] = $head['waybill'];
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
        case 8: //maxipro
          $info = [];
          $info['trno'] = $head['trno'];
          $info['transtype'] = $head['transtyperr'];
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
        case 43: //mighty
          $info = [];
          $info['trno'] = $head['trno'];
          $info['tripdate'] = $head['dateid'];
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
      }

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
    $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    // var_dump($config['params']);
    // break;
    $trno = $config['params']['trno'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $companyid = $config['params']['companyid'];
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $serial = $this->companysetup->getserial($config['params']);


    if ($serial) {
      if (!$this->othersClass->checkserialin($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
      }
    }

    if ($companyid == 60) { //transpower
      $negativestock = $this->coreFunctions->datareader("select line as value from lastock where trno=? and rrqty<0 limit 1", [$trno], '', true);
      if ($negativestock != 0) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Negative quantity not allowed.'];
      }
      $ourref = $this->coreFunctions->datareader("select ourref as value from lahead where trno=?", [$trno]);
      if ($ourref == '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. PO # cannot be blank.'];
      }
    }

    // DO NOT REMOVE THIS BLOCK
    // //checking zero cost
    switch ($config['params']['resellerid']) {
      case 2;  //ms joy
        break;
      default:
        switch ($companyid) {
          case 40: //cdoaims
            $qry = "select trno from " . $this->stock . " where trno=? and rrcost=0";
            $isitemzerorrcost = $this->coreFunctions->opentable($qry, [$trno]);
            if (!empty($isitemzerorrcost)) {
              return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have no cost.'];
            }
            break;
          default:
            break;
        }
        // if ($companyid == 42) {
        // } else {
        //   $qry = "select trno from " . $this->stock . " where trno=? and rrcost=0";
        //   $isitemzerorrcost = $this->coreFunctions->opentable($qry, [$trno]);
        //   if (!empty($isitemzerorrcost)) {
        //     return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have no cost.'];
        //   }
        // }
        break;
    }

    // if ($companyid == 43) { //mighty
    //   $istrip = $this->coreFunctions->getfieldvalue($this->head, "istrip", "trno=?", [$trno], '', true);
    //   if ($istrip == 1) {
    //     $tripdata = $this->coreFunctions->opentable("select trno from tripdetail where trno=" . $trno);
    //     if (empty($tripdata)) {
    //       return ['status' => false, 'msg' => 'Posting failed. Please setup the trip details.'];
    //     }
    //   }
    // }

    if ($this->companysetup->isinvonly($config['params'])) {

      if ($systemtype == 'FAMS') {

        $generic = $this->getpendinggenericeitem($config);

        if (!empty($generic)) {
          return ['trno' => $trno, 'status' => false, 'msg' => 'Please generate asset tag for all items'];
        }

        $generic = $this->getpendinggenericeitem($config);
        if (empty($generic)) {
          $resultgeneric = $this->othersClass->generateAJFAMS($config);
          if (!$resultgeneric['status']) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, ' . $resultgeneric['msg']];
          }
        }
      }

      $return = $this->othersClass->posttranstock($config);
      if ($systemtype == 'FAMS') {
        $this->coreFunctions->execqry("update hrrfams as rr left join item on item.itemid=rr.itemid set item.isinactive=0 where rr.trno=" . $trno);
      }

      return  $return;
    } else {
      if ($periodic) {
        $checkacct = $this->othersClass->checkcoaacct(['AP1', 'IN1', 'PD1', 'TX1']);
      } else {
        $checkacct = $this->othersClass->checkcoaacct(['AP1', 'IN1', 'TX1']);
      }

      if ($companyid == 10) { //afti
        $checkacct = $this->othersClass->checkcoaacct(['AP1', 'TX1']);
      }

      if ($companyid == 24) $checkacct = $this->othersClass->checkcoaacct(['INS1']); //goodfound

      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      $checkcontra = $this->coreFunctions->getfieldvalue("lahead", "contra", "trno=?", [$trno]);
      if ($checkcontra == '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Missing AP Account'];
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
          if ($companyid == 63) { //ericco
          $qry="select refx, linex from lastock where refx <> 0 and linex <> 0 and trno = $trno ";
         $data2 = $this->coreFunctions->opentable($qry);
          if (!empty($data2)) {
          foreach ($data2 as $value) {
             $refx  = $value->refx;
             $linex = $value->linex;
            $checkqa = $this->coreFunctions->datareader("select stock.qa as value from hpostock as stock where trno=? and line=?", [$refx,$linex]);
            $checkqty = $this->coreFunctions->datareader("select stock.qty as value from hpostock as stock where trno=? and line=?", [$refx,$linex]);
            if($checkqa <> $checkqty){
              $this->coreFunctions->execqry("update hpostock  as stock set stock.void=1 where  stock.trno=" . $refx ." and stock.line = ".$linex);
            }
            }}
          }
         

        $this->othersClass->logConsole('posting');
        $return = $this->othersClass->posttranstock($config);


        if ($companyid == 63) { //ericco
          $this->updateitemsrp($config, $trno);
        }
        if ($this->companysetup->getisproject($config['params'])) {
          // $this->othersClass->logConsole('here 111');
          if ($return['status']) {
            // $this->othersClass->logConsole('here 222');
            $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno  where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");
            $this->updateprojmngmtap($config, $data);
          }
        }
        return $return;
      }
    }
  } //end function

  public function unposttrans($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $isfa = $this->companysetup->getisfixasset($config['params']);
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");

    switch ($companyid) {
      case 3: // conti
        $qry = "select receivedby, ifnull(date(receiveddate), '') as receiveddate from cntnum where trno = ?";
        $checking = $this->coreFunctions->opentable($qry, [$trno]);

        if ($checking[0]->receiveddate != "") {
          $msg = "Already Received! " . $checking[0]->receivedby . ' ' . $checking[0]->receiveddate;
          return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
        break;
    }

    if ($isfa) {
      $isexist = $this->coreFunctions->getfieldvalue("fasched", "rrtrno", "rrtrno = ? and jvtrno <>0", [$trno]);

      if (floatval($isexist) != 0) {
        return ['status' => false, 'msg' => 'Already have posted depreciation schedule.'];
      }
    }
    
      if ($companyid == 63) { //ericco
           $qry="select refx, linex from glstock where  refx <> 0 and linex <> 0 and trno = $trno ";
           $data2 = $this->coreFunctions->opentable($qry);
        if (!empty($data2)) {
        foreach ($data2 as $value) {
             $refx  = $value->refx;
             $linex = $value->linex;
            $checkqa = $this->coreFunctions->datareader("select stock.qa as value from hpostock as stock where trno=? and line=?", [$refx,$linex]);
            $checkqty = $this->coreFunctions->datareader("select stock.qty as value from hpostock as stock where trno=? and line=?", [$refx,$linex]);
            if($checkqa <> $checkqty){
              $this->coreFunctions->execqry("update hpostock  as stock set stock.void=0 where  stock.trno=" . $refx ." and stock.line = ".$linex);
            }} }  }


    $return = $this->othersClass->unposttranstock($config);
    if ($this->companysetup->getisproject($config['params'])) {
      if ($return['status']) {
        $this->updateprojmngmtap($config, $data);
      }
    }

    return $return;
  } //end function

  private function getstockselect($config)
  {
    // var_dump($config['params']);
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    $serialfield = '';
    $qafield = 'stock.qa';
    $costfield = 'stock.cost';
    $poqty='';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
      $serialfield = ",ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno";
    }

    if ($companyid == 40) { //cdo
      $serialfield = ",ifnull(group_concat(concat('Engine/Chassis#: ',rr.serial,'/',rr.chassis,'\\n','Color: ',rr.color) separator '\\n\\r'),'') as serialno ";
    }

    if ($companyid == 39) { //cbbsi
      $qafield = 'rrstatus.qa2';
      $costfield = "FORMAT(stock." . $this->hamt . " * uom.factor,6)";
    }

     if ($companyid == 63) { //ericco
      $poqty = ', format(po.qty,2) as original_qty';
    }

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.sortline,
    stock.refx,
    stock.linex,
    item.barcode,
    if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemname,
    stock.uom,
    stock.kgs,
   " . $costfield . " as " . $this->hamt . ",
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock." . $this->hqty . "-" . $qafield . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,
    item.brand,
    stock.rem,
    stock.palletid,
    stock.locid,
    stock.freight,
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,stock.fcost,ifnull(stock.stageid,0) as stageid ,ifnull(st.stage,'') as stage,
    '' as bgcolor,
    '' as errcolor,

    prj.name as stock_projectname,
    stock.projectid as projectid,
    prj.code as project,

    stock.phaseid, ph.code as phasename,

    stock.modelid, hm.model as housemodel, 

    stock.blklotid, bl.blk, bl.lot,
    
    am.line as amenity,
    am.description as amenityname,
    subam.line as subamenity,
    subam.description as subamenityname,

    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.poref,stock.sgdrate,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription
    " . $serialfield . " ,case when sit.isbo=1 then 'true' else 'false' end as isbo,ifnull(group_concat(concat('PNP#: ',rr.pnp,' / CSR#: ',rr.csr,'\\n','Remarks: ',rr.remarks) separator '\\n\\r'),'') as pnp,stock.rtrefx,
    stock.rtlinex, stock.sjrefx, stock.sjlinex " . $poqty . " ";

    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    $addgrpby = '';
    $qafield = 'stock.qa';

    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    if ($companyid == 39) { //cbbsi
      $qafield = 'rrstatus.qa2';
    }

    $grpby ="";
     if ($companyid == 63) { //ericco
      $grpby=', po.qty';
    }

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid

    left join projectmasterfile as prj on prj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
    left join hpostock as po on po.trno=stock.refx and po.line=stock.linex
    where stock.trno =?
    group by item.brand,
    mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ", stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ") ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,round((stock." . $this->hqty . "-" . $qafield . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") ,
    stock.ref,stock.whid,warehouse.client,warehouse.clientname, stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,stock.fcost,stock.stageid,st.stage,
    
    prj.name,stock.projectid,
    prj.code,

    stock.phaseid, ph.code,

    stock.modelid, hm.model, 

    stock.blklotid, bl.blk, bl.lot,
    
    am.line,
    am.description,
    subam.line,
    subam.description,
    
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . "),stock.poref,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.freight,sit.itemdesc,sit.isbo,stock.rtrefx,stock.rtlinex,stock.sjrefx, stock.sjlinex $grpby
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join stagesmasterfile as st on st.line = stock.stageid

    left join projectmasterfile as prj on prj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    left join hstockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
    left join hpostock as po on po.trno=stock.refx and po.line=stock.linex
    where stock.trno =? group by item.brand,
    mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ", stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ") ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,round((stock." . $this->hqty . "-" . $qafield . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") ,
    stock.ref,stock.whid,warehouse.client,warehouse.clientname, stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,stock.fcost,stock.stageid,st.stage,
    
    prj.name,stock.projectid,
    prj.code,

    stock.phaseid, ph.code,

    stock.modelid, hm.model, 

    stock.blklotid, bl.blk, bl.lot,
    
    am.line,
    am.description,
    subam.line,
    subam.description,
    
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . "),stock.poref,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.freight,sit.itemdesc ,sit.isbo,stock.rtrefx,stock.rtlinex,stock.sjrefx, stock.sjlinex $grpby order by sortline,line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qafield = 'stock.qa';

    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    if ($companyid == 39) { //cbbsi
      $qafield = 'rrstatus.qa2';
    }

    // $addgroupby = "";
      $grpby ="";
     if ($companyid == 63) { //ericco
      $grpby=', po.qty';
    }
    $addgroupby = ",stock.sjrefx, stock.sjlinex";

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
   FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid 
    
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
    left join hpostock as po on po.trno=stock.refx and po.line=stock.linex
    where stock.trno = ? and stock.line = ? 
    group by item.brand,
    mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ", stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ") ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,round((stock." . $this->hqty . "-" . $qafield . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") ,
    stock.ref,stock.whid,warehouse.client,warehouse.clientname, stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,stock.fcost,stock.stageid,st.stage,
    
    prj.name,stock.projectid,
    prj.code,

    stock.phaseid, ph.code,

    stock.modelid, hm.model, 

    stock.blklotid, bl.blk, bl.lot,
    
    am.line,
    am.description,
    subam.line,
    subam.description,
    
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . "),stock.poref,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.freight,sit.itemdesc,sit.isbo,stock.rtrefx,stock.rtlinex" . $addgroupby.$grpby;
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
      case 'getposummary':
        return $this->getposummary($config);
        break;
      case 'getpodetails':
        return $this->getpodetails($config);
        break;
      case 'getsjsummary':
        return $this->getsjsummary($config);
        break;
      case 'getsjdetails':
        return $this->getsjdetails($config);
        break;
      case 'getitem':
        return $this->othersClass->getmultiitem($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'flowchart':
        return $this->flowchart($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'tagreceived':
        return $this->tagreceived($config);
        break;
      case 'untagreceived':
        return $this->untagreceived($config);
        break;
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'RRCV');
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'tripapproved':
        return $this->tripapproved($config);
        break;
      case 'tripdisapproved':
        return $this->tripdisapproved($config);
        break;
      case 'uploadexcelpnp':
        return $this->uploadexcelpnp($config);
        break;
      case 'forapproval':
        $tablenum = $this->tablenum;
        return $this->othersClass->forapproval($config, $tablenum);
        break;
      case 'doneapproved':
        $tablenum = $this->tablenum;
        return $this->othersClass->approvedsetup($config, $tablenum);
        break;
      case 'generatetag':
        return $this->generateassettag($config);
        break;
      case 'generateapv':
        return $this->createapv($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function uploadexcel($config)
  {
    $rawdata = $config['params']['data'];
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['dataparams']['trno'];
    $msg = '';
    $status = true;
    $uniquefield = "itemcode";

    if ($companyid == 40) { //cdo
      $uniquefield = "partno";
    }

    if ($trno == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
    }

    foreach ($rawdata as $key => $value) {
      try {
        if ($companyid == 40) { //cdo
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "partno = '" . $rawdata[$key][$uniquefield] . "'");
        } else {
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key][$uniquefield] . "'");
        }

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
        $config['params']['data']['uom'] = $rawdata[$key]['uom'];
        $config['params']['data']['itemid'] = $itemid;
        $config['params']['data']['qty'] = $rawdata[$key]['qty'];
        $config['params']['data']['wh'] =  $this->coreFunctions->getfieldvalue($this->head, "wh", "trno = ?", [$trno]);
        $config['params']['data']['amt'] = isset($rawdata[$key]['cost']) ? $rawdata[$key]['cost'] : $rawdata[$key]['amt'];
        $config['params']['data']['loc'] = isset($rawdata[$key]['location']) ? $rawdata[$key]['location'] : "";
        $config['params']['data']['disc'] = isset($rawdata[$key]['disc']) ? $rawdata[$key]['disc'] : "";


        $expiry = isset($rawdata[$key]['expiry']) ? $rawdata[$key]['expiry'] : '';
        if ($expiry != '') {
          $UNIX_DATE = ($expiry - 25569) * 86400;
          $config['params']['data']['expiry'] = gmdate("Y-m-d", $UNIX_DATE);
        }

        if (isset($rawdata[$key]['kgs'])) {
          $config['params']['data']['kgs'] = $rawdata[$key]['kgs'];
        }

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
      $this->coreFunctions->execqry("delete from lastock where trno=" . $trno);
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function uploadexcelpnp($config)
  {
    $rawdata = $config['params']['data'];
    $trno = $config['params']['dataparams']['trno'];
    $data = [];
    $msg = '';
    $status = true;

    if ($trno == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
    }

    foreach ($rawdata as $key => $value) {
      try {
        $engine = $this->coreFunctions->getfieldvalue("serialin", "serial", "serial = '" . $rawdata[$key]['engine'] . "'");
        if ($engine == '') {
          $status = false;
          $msg .= 'Failed to upload. Engine #' . $rawdata[$key]['engine'] . ' does not exist. ';
          continue;
        }

        $data['pnp'] = $rawdata[$key]['pnpno'];
        $data['csr'] = $rawdata[$key]['csrno'];

        $datecreate = isset($rawdata[$key]['datecreate']) ? $rawdata[$key]['datecreate'] : '';

        if ($datecreate != '') {
          $UNIX_DATE = ($datecreate - 25569) * 86400;
          $data['dateid'] = gmdate("Y-m-d", $UNIX_DATE);
        }


        $return = $this->coreFunctions->sbcupdate("serialin", $data, ["trno" => $trno, "serial" => $rawdata[$key]['engine']]);
        if ($return == 0) {
          $status = false;
          $msg .= 'Failed to upload. ';
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
      $this->logger->sbcwritelog($trno, $config, 'IMPORT', 'UPLOAD PNP & CSR');
      $msg = 'Successfully uploaded.';
    }


    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function tripapproved($config)
  {
    $trno = $config['params']['trno'];

    $msg = "";
    $status = true;
    $qry = "select receiveby, ifnull(date(receivedate), '') as receivedate from hcntnuminfo where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receivedate == "") {
      $tag = $this->coreFunctions->execqry("update hcntnuminfo set receiveby= '" . $config['params']['user'] . "',receivedate = '" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Trip Approved";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'APPROVAL', $msg);
      } else {
        $msg = "Failed to approved";
        $status = false;
      }
    } else {
      $msg = "Already approved. " . $checking[0]->receiveby . ' ' . $checking[0]->receivedate;
    }
    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function tripdisapproved($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;
    $qry = "select date(receivedate) as receivedate from hcntnuminfo where trno = ? and receivedate is not null and receiveby <> ''";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);
    if ($checking[0]->receivedate != "") {
      $this->coreFunctions->execqry("update hcntnuminfo set receiveby='',receivedate = null where trno=? ", "update", [$trno]);
      $msg = "Trip Disapproved";
      $status = true;
      $this->logger->sbcwritelog($trno, $config, 'DISAPPROVAL', $msg);
    }
    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function tagreceived($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;
    $qry = "select receivedby, ifnull(date(receiveddate), '') as receiveddate from cntnum where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receiveddate != "") {
      $msg = "Already Received! " . $checking[0]->receivedby . ' ' . $checking[0]->receiveddate;
    } else {
      $tag = $this->coreFunctions->execqry("update cntnum set receivedby='" . $user . "',
      receiveddate = '" . date("Y-m-d") . "' where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Received Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'RECEIVED', 'CLICKED RECEIVED');
      } else {
        $msg = "Received Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Received', 'reloadhead' => true];
  }

  public function untagreceived($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;
    $qry = "select receivedby, ifnull(date(receiveddate), '') as receiveddate from cntnum where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receiveddate == "") {
      $msg = "Already Unreceived! " . $checking[0]->receivedby . ' ' . $checking[0]->receiveddate;
    } else {
      $tag = $this->coreFunctions->execqry("update cntnum set receivedby='',
        receiveddate = null where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Unreceived Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'UNRECEIVED', 'CLICKED UNRECEIVED');
      } else {
        $msg = "Unreceived Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Unreceived', 'reloadhead' => true];
  }

  public function gethead_receivedbutton($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $isproject = $this->companysetup->getisproject($config['params']);
    $projectfilter  = "";

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }

    $qryselect = "select
      num.center,
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
      head.billid,
      head.shipid,
      head.billcontactid,
      head.shipcontactid,
      '' as dvattype,
      warehouse.client as wh,
      warehouse.clientname as whname,
      '' as dwhname,
      head.projectid,
      '' as dprojectname,
      left(head.due,10) as due,
      client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
      head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
      head.deptid,'' as ddeptname,head.invoiceno,left(head.invoicedate,10) as invoicedate,head.ewt,head.ewtrate ";

    $qry = $qryselect . " from $table as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.client = client.client
      left join client as warehouse on warehouse.client = head.wh
      left join client as b on b.clientid = head.branch
      left join coa on coa.acno=head.contra
      left join projectmasterfile as p on p.line=head.projectid
      left join client as d on d.clientid = head.deptid
      left join subproject as s on s.line = head.subproject
      where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
      union all " . $qryselect . " from $htable as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.clientid = client.clientid
      left join client as warehouse on warehouse.clientid = head.whid
      left join client as b on b.clientid = head.branch
      left join coa on coa.acno=head.contra
      left join projectmasterfile as p on p.line=head.projectid
      left join client as d on d.clientid = head.deptid
      left join subproject as s on s.line = head.subproject
      where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

    if (empty($head)) {
      $head = [];
    }

    return $head;
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,concat('Total PO Amt: ',round(sum(s.ext),2)) as rem,s.refx 
    from hpohead as po left join hpostock as s on s.trno = po.trno left join glstock as g on g.refx = po.trno and g.linex = s.line where g.trno = ? group by po.trno,po.docno,po.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //PO
        data_set($nodes, $t[$key]->docno, ['align' => 'right', 'x' => 200, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'blue', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select po.docno,left(po.dateid,10) as dateid,concat('Total PR Qty: ',round(sum(s.qty),2)) as rem from hprhead as po left join hprstock as s on s.trno = po.trno  where po.trno = ? group by po.docno,po.dateid";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set($nodes, $x[$key2]->docno, ['align' => 'right', 'x' => 10, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $x[$key2]->docno, 'label' => $x[$key2]->rem, 'color' => 'yellow', 'details' => [$x[$key2]->dateid]]);
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    $qry = "select head.docno,
    left(head.dateid,10) as dateid,
    concat('Amount: ',round(ifnull(apledger.db, 0)+ifnull(apledger.cr, 0),2),'  -  ','BALANCE: ',
    round(ifnull(apledger.bal, 0),2)) as rem
    from glhead as head
    left join apledger on head.trno = apledger.trno
    where head.trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set($nodes, 'rr', ['align' => 'right', 'x' => $startx, 'y' => 100, 'w' => 250, 'h' => 130, 'type' => $t[0]->docno, 'label' => $t[0]->rem, 'color' => 'green', 'details' => [$t[0]->dateid]]);
    }

    $qry = "select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    where head.doc != ? and stock.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid = stock.itemid
    where head.doc != ? and stock.refx=?
    ";

    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno']]);
    if (!empty($t)) {
      $y = 0;
      foreach ($t as $key => $value) {
        data_set($nodes, $t[$key]->docno, ['align' => 'left', 'x' => $startx + 400, 'y' => 50 + $y, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'red', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => 'rr', 'to' => $t[$key]->docno]);
        $y = $y + 120;
      }
    }
    $data['nodes'] = $nodes;
    $data['links'] = $links;
    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function flowchart($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['centerX'] = 1024;
    $data['centerY'] = 140;
    $data['scale'] = 1;
    $qry = "select apledger.docno,left(apledger.dateid,10) as dateid,CAST(concat('Amount: ',round(apledger.db+apledger.cr,2),'  -  ','BALANCE: ',round(apledger.bal,2)) as CHAR) as rem from apledger where trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    array_push($nodes, ['id' => 2, 'x' => -500, 'y' => -120, 'type' => $t[0]->docno, 'label' => $t[0]->rem]);

    array_push($nodes, ['id' => 4, 'x' => -357, 'y' => 80, 'type' => 'Script', 'label' => 'test2']);
    array_push($nodes, ['id' => 6, 'x' => -557, 'y' => 80, 'type' => 'Rule', 'label' => 'test3']);
    $data['nodes'] = $nodes;
    array_push($links, ['id' => 3, 'from' => 2, 'to' => 4]);
    array_push($links, ['id' => 5, 'from' => 2, 'to' => 6]);
    $data['links'] = $links;
    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
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
      $msg = "";

      $minmax = $this->othersClass->getitemminmax($data2[0]['barcode'], $data2[0]['wh'], $data2[0]['qty']);
      if ($minmax <> "") {
        $msg = $minmax;
      }
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved -' . $msg];
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
    $row = [];
    foreach ($config['params']['row'] as $key => $value) {
      $msg = 'Successfully saved.';
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);
      if (isset($config['params']['data']['refx'])) {
        if ($config['params']['data']['refx'] != 0) {
          if ($this->othersClass->setserveditemsRR($config['params']['data']['refx'], $config['params']['data']['linex'], $this->hqty) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $row['row'][0]->trno, 'line' => $row['row'][0]->line]);
            $this->othersClass->setserveditemsRR($config['params']['data']['refx'], $config['params']['data']['linex'], $this->hqty);
          }
        }
      }
      if ($row['status'] == false) {
        $msg = $row['msg'];
        break;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    if ($config['params']['companyid'] == 8) { //maxipro
      return ['inventory' => $data, 'status' => true, 'msg' => $row['msg']];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => $msg];
    }
  } //end function

  public function quickadd($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $trno = $config['params']['trno'];
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $wh = $config['params']['wh'];
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,'' as disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);
    if ($config['params']['companyid'] == 10) { //afti
      $trno = $config['params']['trno'];
      $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
      if (floatval($forex) != 1) {
        $item = $this->coreFunctions->opentable("select item.itemid,case " . $forex . " when 1 then 0 else amt4 end as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
      }
    }
    $item = json_decode(json_encode($item), true);
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno = ?', [$trno]);
    $defuom = '';

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config, $forex);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) {
        $item[0]['amt'] = $lprice['data'][0]['amt'];
        $item[0]['disc'] = $lprice['data'][0]['disc'];
      }

      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
        if ($defuom != "") {
          $item[0]['uom'] = $defuom;
        }
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
    // var_dump($action);
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $isproject = $this->companysetup->getisproject($config['params']);

    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : '';
    $wh = $config['params']['data']['wh'];
    $loc =  isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';
    $expiry = isset($config['params']['data']['expiry']) ? $config['params']['data']['expiry'] : '';
    $rem = isset($config['params']['data']['rem']) ? $config['params']['data']['rem'] : '';
    $freight = isset($config['params']['data']['freight']) ? $config['params']['data']['freight'] : 0;
    $itemdesc = isset($config['params']['data']['itemdesc']) ? $config['params']['data']['itemdesc'] : '';

    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }



    $refx = 0;
    $linex = 0;
    $fcost = 0;
    $ref = '';
    $stageid = 0;
    $palletid = 0;
    $locid = 0;
    $stock_projectid = 0;
    $poref = '';
    $sgdrate = 0;
    $ext = 0;
    $isbo = 0;

    $rtrefx = 0;
    $rtlinex = 0;


    $projectid = 0;
    $phaseid = 0;
    $modelid = 0;
    $blklotid = 0;
    $amenityid = 0;
    $subamenityid = 0;
    $poqty=0;



    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['rtrefx'])) {
      $rtrefx = $config['params']['data']['rtrefx'];
    }
    if (isset($config['params']['data']['rtlinex'])) {
      $rtlinex = $config['params']['data']['rtlinex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['locid'])) {
      $locid = $config['params']['data']['locid'];
    }

    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['sgdrate'])) {
      $sgdrate = $config['params']['data']['sgdrate'];
    } else {
      $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
    }

    if (isset($config['params']['data']['isbo'])) {
      if ($config['params']['data']['isbo'] == 'true') {
        $isbo = 1;
      }
    }

    $sjrefx = isset($config['params']['data']['sjrefx']) ? $config['params']['data']['sjrefx'] : 0;
    $sjlinex = isset($config['params']['data']['sjlinex']) ? $config['params']['data']['sjlinex'] : 0;

    if ($companyid == 39) { //cbbsi
      $void = 'false';
      if (isset($config['params']['data']['void'])) {
        $void = $config['params']['data']['void'];
      }
    }


    if ($systype == 'REALESTATE') {
      if (isset($config['params']['data']['projectid'])) {
        $projectid = $config['params']['data']['projectid'];
      }
      if (isset($config['params']['data']['phaseid'])) {
        $phaseid = $config['params']['data']['phaseid'];
      }
      if (isset($config['params']['data']['modelid'])) {
        $modelid = $config['params']['data']['modelid'];
      }
      if (isset($config['params']['data']['blklotid'])) {
        $blklotid = $config['params']['data']['blklotid'];
      }
      if (isset($config['params']['data']['amenityid'])) {
        $amenityid = $config['params']['data']['amenityid'];
      }
      if (isset($config['params']['data']['subamenityid'])) {
        $subamenityid = $config['params']['data']['subamenityid'];
      }

      if ($projectid == 0) {
        $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
      }
      if ($phaseid == 0) {
        $phaseid = $this->coreFunctions->getfieldvalue($this->head, "phaseid", "trno=?", [$trno]);
      }
      if ($modelid == 0) {
        $modelid = $this->coreFunctions->getfieldvalue($this->head, "modelid", "trno=?", [$trno]);
      }
      if ($blklotid == 0) {
        $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      }
      if ($amenityid == 0) {
        $amenityid = $this->coreFunctions->getfieldvalue($this->head, "amenityid", "trno=?", [$trno]);
      }
      if ($subamenityid == 0) {
        $subamenityid = $this->coreFunctions->getfieldvalue($this->head, "subamenityid", "trno=?", [$trno]);
      }
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


      if ($companyid == 10) { //afti
        $stock_projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];

      $config['params']['line'] = $line;

      if ($companyid == 10) { //afti
        $stock_projectid = $config['params']['data']['projectid'];
      }
    }

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $kgs = $this->othersClass->sanitizekeyfield('qty', $kgs);
    $freight = $this->othersClass->sanitizekeyfield('amt', $freight);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $projectid = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);

    if (floatval($forex) <> 1) {
      $fcost = $amt;
    }

    $forex = $this->othersClass->val($forex);
    if ($forex == 0) $forex = 1;

    if ($companyid == 60) { //TRANSPOWER
      if ($disc != "") {
        $discper = "";
        if (!str_contains($disc, '%')) {
          $d = explode("/", $disc);
          foreach ($d as $k => $x) {
            if ($discper != "") {
              $discper .= "/";
            }

            $discper .= $x . '%';
          }
          $disc = $discper;
        }
      }
    }

    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    if ($this->companysetup->getvatexpurch($config['params'])) {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', $kgs);
    } else {
      switch ($companyid) {
        case 28: // xcomp  disc per unit
          $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat, 'P', 0, 0, 1);
          break;
        case 39: //cbbsi
          $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat, 'P', 0, 1, 1);
          break;
        default:
          $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat, 'P', $kgs);
          break;
      }
    }

    $hamt = number_format((($computedata['amt'] * $forex) + $freight), 6, '.', '');

    if ($companyid == 23) { //labsol cebu
      if ($forex == 1) {
        $hamt = number_format((($computedata['amt'] * $forex * 1.15) + $freight), 6, '.', '');
      } else {
        $hamt = number_format((($computedata['amt'] * $forex * 1.3) + $freight), 6, '.', '');
      }
    }

    if ($companyid == 41 || $companyid == 52) { //labsol manila, technolab
      $hamt = number_format((($computedata['amt'] * $forex * 1.05) + $freight), 6, '.', '');
    }

    if ($companyid == 59) { //roosevelt
      $totalcharges = $this->coreFunctions->datareader("select (ied+bankcharges+interest+brokerfee+arrastre) as value from $this->head where trno=" . $trno, [], '', true);
      if ($totalcharges != 0) {
        $hamt = number_format($hamt + $totalcharges, 6, '.', '');
      }
    }

    $ext = number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', '');

    if ($companyid == 40) { //cdo
      if ($loc == '') {
        $loc = $this->coreFunctions->getfieldvalue("rrstatus", "loc", "itemid =? and whid=?", [$itemid, $whid], "dateid desc");
      }
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $hamt,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => $ext,
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'rem' => $rem,
      'palletid' => $palletid,
      'locid' => $locid,
      'stageid' => $stageid,
      'freight' => $freight,
      'rtrefx' => $rtrefx,
      'rtlinex' => $rtlinex
    ];

    if ($companyid == 10) { //afti
      $data['projectid'] = $stock_projectid;
      $data['poref'] = $poref;
      $data['sgdrate'] = $sgdrate;
    }

    if ($companyid == 39) { //cbbsi
      $data['void'] = $void;
    }

    if ($systype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }

    if ($companyid == 60) { //transpower
      $data['sjrefx'] = $sjrefx;
      $data['sjlinex'] = $sjlinex;
    }

   


    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];
      if ($isproject) {
        $isho = $this->coreFunctions->getfieldvalue("projectmasterfile", "isho", "line = " . $projectid);
        if (!$isho) {
          if ($data['stageid'] == 0) {
            $msg = 'Stage cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
          }
        }
      }
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $stockinfo_data = [];
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMSPOS':
            if ($companyid == 39) { //cbbsi
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'isbo' => $isbo
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            }
            break;
          case 'AIMS':
            if ($companyid == 0) { //main
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            }
            if ($companyid == 36) { //ROZLAB
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'itemdesc' => $itemdesc
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            }
            break;
          case 'AIMSPAYROLL':
            $stockinfo_data = [
              'trno' => $trno,
              'line' => $line,
              'itemdesc' => $itemdesc
            ];
            $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            break;
        }

        if ($isproject) {
          $this->updateprojmngmt($config, $stageid);
        }


        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Uom:' . $uom . ' Ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);

        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      if ($companyid == 8) { //maxipro
        unset($data['whid']);
      }

      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($companyid == 39) { //cbbsi
        $this->coreFunctions->sbcupdate('stockinfo', ['isbo' => $isbo], ['trno' => $trno, 'line' => $line]);
      }

      $this->updateprojmngmt($config, $stageid);
      if ($this->othersClass->setserveditemsRR($refx, $linex, $this->hqty) === 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->othersClass->setserveditemsRR($refx, $linex, $this->hqty);
        $return = false;
      }

      if ($sjrefx <> 0) {
        if ($this->setservedsjitems($sjrefx, $sjlinex, $this->hqty) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedsjitems($sjrefx, $sjlinex, $this->hqty);
          $return = false;
        }
      }

      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {

    $trno = $config['params']['trno'];
    $addfields = "";
    $addfilter = "";
    if ($config['params']['companyid'] == 60) {
      $addfields = ",sjrefx, sjlinex";
      $addfilter = " or sjrefx<>0";
    }
    $data = $this->coreFunctions->opentable('select refx,linex,rtrefx,rtlinex,stageid ' . $addfields . ' from ' . $this->stock . ' where trno=? and (refx<>0 or rtrefx<>0' . $addfilter . ')', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from serialin where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->updateprojmngmt($config, $data[$key]->stageid);
      $this->othersClass->setserveditemsRR($data[$key]->refx, $data[$key]->linex, $this->hqty);
      $this->othersClass->setserveditemsTempRR($data[$key]->rtrefx, $data[$key]->rtlinex, $this->hqty);
      if ($config['params']['companyid'] == 60) { //transpower
        $this->setservedsjitems($data[$key]->sjrefx, $data[$key]->sjlinex);
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

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );
    $qry = "delete from serialin where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->updateprojmngmt($config, $data[0]->stageid);
    if ($data[0]->refx !== 0) {
      $this->othersClass->setserveditemsRR($data[0]->refx, $data[0]->linex, $this->hqty);
    }
    if ($data[0]->sjrefx !== 0) {
      $this->setservedsjitems($data[0]->sjrefx, $data[0]->sjlinex);
    }
    if ($data[0]->rtrefx !== 0) {
      $this->othersClass->setserveditemsTempRR($data[0]->rtrefx, $data[0]->rtlinex, $this->hqty);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config, $forex = 1)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    switch ($config['params']['companyid']) {
      case 10:
        $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
            case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else item.amt4 end as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
            and item.barcode = ? and head.client =?
            and stock.cost <> 0 and cntnum.trno <>?
            UNION ALL
            select head.docno,head.dateid,case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else item.amt4 end as amt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
            and item.barcode = ? and client.client =?
            and stock." . $this->hamt . " <> 0 and cntnum.trno <>?
            order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);
        break;

      case 56: //homeworks
        $supplier = $this->coreFunctions->opentable("select 'ITEM' as docno, left(client.client,3) as supplier, item.amt, item.avecost, '' as disc, item.uom from item left join client on client.clientid=item.supplier where barcode=?", [$barcode]);
        if (!empty($supplier)) {
          if ($supplier[0]->supplier == '161') {  // (outright) - base sa cost default ng items
            $supplier[0]->amt = $supplier[0]->avecost;
          }
          return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $supplier];
        }
        break;

      default:
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
            case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else stock." . $this->damt . " end as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
            and item.barcode = ? and head.client =?
            and stock.cost <> 0 and cntnum.trno <>?
            UNION ALL
            select head.docno,head.dateid,case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else stock." . $this->damt . " end as amt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
            and item.barcode = ? and client.client =?
            and stock." . $this->hamt . " <> 0 and cntnum.trno <>?
            order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);
        break;
    }

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

  public function getposummaryqry($config)
  {
    $joins = "";
    $fields = "";
    $companyid = $config['params']['companyid'];

    $ourref = 'head.ourref';
    switch ($companyid) {
      case 17: //unihome
      case 39: //CBBSI
        $joins = "left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line";
        $fields = ",concat(stock.rem, ' ', info.itemdesc) as stockinfo";
        break;
      case 28: //xcomp
        $ourref = 'head.docno';
        $joins = "left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line";
        $fields = ",info.itemdesc as itemdesc";
        break;
    }

    return "
        select head.doc,head.docno, head.client, head.clientname, head.address, ifnull(head.rem,'') as hrem, head.cur, head.forex, head.shipto, " . $ourref . " as ourref, head.yourref, head.projectid, head.terms,
        item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.cost, (stock.qty-stock.qa) as qty,stock.rrcost,stock.ext, head.wh,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,stock.rem as rem,
        stock.disc,stock.stageid,head.branch,head.billcontactid,head.shipcontactid,head.billid,head.shipid,head.tax,head.vattype,head.yourref,head.deptid,stock.sgdrate,wh.client as swh,stock.loc,
        head.ewt,head.ewtrate,head.wh,hwh.clientid as whid,
        stock.projectid as stock_projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        $fields
        FROM hpohead as head 
        left join hpostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as wh on wh.clientid=stock.whid left join client as hwh on hwh.client = head.wh
        $joins
        where stock.trno = ? and stock.qty>stock.qa and stock.void=0 ";
  }

  public function getposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);

      if ($companyid == 8) { //maxipro
        $wh = $data[0]->wh;
      }

      if (!empty($data)) {
        switch ($companyid) {
          case 3: //conti
            $this->coreFunctions->execqry("update lahead set ourref='" . $data[0]->docno . "', rem='" . $data[0]->rem . "' where trno = " . $trno, 'update');
            break;
          case 23: //labsol cebu
          case 41: //labsol manila
          case 52: //technolab
            $this->coreFunctions->execqry("update lahead set cur='" . $data[0]->cur . "', forex=" . $data[0]->forex . " where trno = " . $trno, 'update');
            break;
          case 39: //cbbsi
            $this->coreFunctions->execqry("update lahead set ourref='" . $data[0]->docno . "' where trno=" . $trno, "update");
            break;
          case 24: //goodfound
            $this->coreFunctions->execqry("update lahead set terms='" . $data[0]->terms . "', vattype='" . $data[0]->vattype . "', tax=" . $data[0]->tax . ", 
                                                  yourref='" . $data[0]->yourref . "', ourref='" . $data[0]->ourref . "',ewt='" . $data[0]->ewt . "' ,
                                                  ewtrate='" . $data[0]->ewtrate . "' where trno = " . $trno, 'update');
            break;
          case 43: //mighty
            $this->coreFunctions->sbcupdate($this->head, ['rem' => $data[0]->hrem, 'ourref' => $data[0]->docno],  ['trno' => $trno]);
            break;

          case 8: //maxipro
            $this->coreFunctions->sbcupdate($this->head, ['tax' => $data[0]->tax, 'vattype' => $data[0]->vattype], ['trno' => $trno]);
            break;
        }

        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;

          if ($companyid == 39) { //cbbsi
            $config['params']['data']['wh'] = $data[$key2]->swh;
          } else {
            $config['params']['data']['wh'] = $wh;
          }

          if ($companyid == 24) { //goodfound
            $config['params']['data']['loc'] = $data[$key2]->loc;
            if ($data[$key2]->yourref != "") {
              $this->coreFunctions->execqry("update lahead set yourref='" . $data[$key2]->yourref . "' where trno = " . $trno, 'update');
            }
            if ($data[$key2]->terms != "") {
              $this->coreFunctions->execqry("update lahead set terms='" . $data[$key2]->terms . "' where trno = " . $trno, 'update');
            }
          } else {
            if ($companyid == 40) { //cdo
              $config['params']['data']['loc'] = $this->coreFunctions->getfieldvalue("rrstatus", "loc", "itemid =? and whid=?", [$data[$key2]->itemid, $data[$key2]->whid], "dateid desc");
            } else {
              $config['params']['data']['loc'] = '';
            }
          }
          $config['params']['data']['expiry'] = '';
          if ($companyid == 17) { //unihome
            $config['params']['data']['rem'] = $data[$key2]->stockinfo;
          } else {
            $config['params']['data']['rem'] = $data[$key2]->rem;
          }
          if (strtoupper($data[$key2]->doc) == 'RT') {
            $config['params']['data']['rtrefx'] = $data[$key2]->trno;
            $config['params']['data']['rtlinex'] = $data[$key2]->line;
          } else {
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
          }

          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $config['params']['data']['ext'] = $data[$key2]->ext;
          if ($companyid == 10) { //afti
            $config['params']['data']['poref'] = $data[$key2]->yourref;
          }
          if ($companyid == 28) { //xcomp
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
          }
          if ($systype == 'REALESTATE') {
            $config['params']['data']['projectid'] = $data[$key2]->stock_projectid;
            $config['params']['data']['phaseid'] = $data[$key2]->phaseid;
            $config['params']['data']['modelid'] = $data[$key2]->modelid;
            $config['params']['data']['blklotid'] = $data[$key2]->blklotid;
            $config['params']['data']['amenityid'] = $data[$key2]->amenityid;
            $config['params']['data']['subamenityid'] = $data[$key2]->subamenityid;
          }
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($companyid == 8) { //maxipro
              $this->coreFunctions->sbcupdate($this->head, ['wh' => $wh], ['trno' => $trno]);
            }
            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

  public function getpodetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);

    $joins = "";
    $fields = "";

    switch ($companyid) {
      case 17: //unihome
      case 39: //CBBSI
        $joins = "left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line";
        $fields = ",concat(stock.rem, ' ', info.itemdesc) as stockinfo";
        break;
      case 28: //XCOMP
        $joins = "left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line";
        $fields = ",info.itemdesc as itemdesc";
        break;
    }

    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.doc,head.docno, head.rem as hrem, item.itemid,stock.trno,stock.rem,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,stock.ext,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.stageid,head.yourref,head.terms,head.cur,head.forex,stock.loc,
        head.vattype,head.tax,head.ourref,head.ewt,head.ewtrate,head.wh,wh.clientid as whid,
        stock.projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        " . $fields . "
        FROM hpohead as head 
        left join hpostock as stock on stock.trno=head.trno 
        left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join client as wh on wh.client = head.wh " . $joins . " where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
        
    ";

      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if ($companyid == 8) { //maxipro
        $wh = $data[0]->wh;
      }

      if (!empty($data)) {
        if ($companyid == 3) { //conti
          $this->coreFunctions->execqry("update lahead set ourref='" . $data[0]->docno . "', rem='" . $data[0]->hrem . "' where trno = " . $trno, 'update');
        }

        if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila, technolab
          $this->coreFunctions->execqry("update lahead set cur='" . $data[0]->cur . "', forex=" . $data[0]->forex . " where trno = " . $trno, 'update');
        }


        if ($companyid == 24) { //goodfound
          $this->coreFunctions->execqry("update lahead set 
          terms='" . $data[0]->terms . "', 
          vattype='" . $data[0]->vattype . "', 
          tax=" . $data[0]->tax . ", 
          yourref='" . $data[0]->yourref . "', 
          ourref='" . $data[0]->ourref . "',
          ewt='" . $data[0]->ewt . "' ,
          ewtrate='" . $data[0]->ewtrate . "'
          where trno = " . $trno, 'update');
        }

        if ($companyid == 8) { //maxipro
          $this->coreFunctions->sbcupdate($this->head, ['wh' => $wh, 'tax' => $data[0]->tax, 'vattype' => $data[0]->vattype], ['trno' => $trno]);
        }

        if ($companyid == 43) { //mighty
          $this->coreFunctions->sbcupdate(
            $this->head,
            [
              'rem' => $data[0]->hrem,
              'ourref' => $data[0]->docno
            ],
            ['trno' => $trno]
          );
        }

        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;

          if ($companyid == 24) { //goodfound
            $config['params']['data']['loc'] = $data[$key2]->loc;
            if ($data[$key2]->yourref != "") {
              $this->coreFunctions->execqry("update lahead set yourref='" . $data[$key2]->yourref . "' where trno = " . $trno, 'update');
            }
            if ($data[$key2]->terms != "") {
              $this->coreFunctions->execqry("update lahead set terms='" . $data[$key2]->terms . "' where trno = " . $trno, 'update');
            }
          } else {
            if ($companyid == 40) { //cdo
              $config['params']['data']['loc'] = $this->coreFunctions->getfieldvalue("rrstatus", "loc", "itemid =? and whid=?", [$data[$key2]->itemid, $data[$key2]->whid], "dateid desc");
            } else {
              $config['params']['data']['loc'] = '';
            }
          }

          $config['params']['data']['expiry'] = '';
          if ($companyid == 17) { //unihome
            $config['params']['data']['rem'] = $data[$key2]->stockinfo;
          } else {
            $config['params']['data']['rem'] = $data[$key2]->rem;
          }

          if (strtoupper($data[$key2]->doc) == 'RT') {
            $config['params']['data']['rtrefx'] = $data[$key2]->trno;
            $config['params']['data']['rtlinex'] = $data[$key2]->line;
          } else {
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
          }

          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $config['params']['data']['ext'] = $data[$key2]->ext;
          if ($companyid == 10) { //afti
            $config['params']['data']['poref'] = $data[$key2]->yourref;
          }
          if ($companyid == 28) { //xcomp
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
          }

          if ($systype == 'REALESTATE') {
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['phaseid'] = $data[$key2]->phaseid;
            $config['params']['data']['modelid'] = $data[$key2]->modelid;
            $config['params']['data']['blklotid'] = $data[$key2]->blklotid;
            $config['params']['data']['amenityid'] = $data[$key2]->amenityid;
            $config['params']['data']['subamenityid'] = $data[$key2]->subamenityid;
          }
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

  public function createdistribution($config, $createapv = false)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    if (!$createapv) {
      $generaveapv = $this->companysetup->isgenerateapv($config['params']);
      if ($generaveapv) {
        return true;
      }
    }

    $systype = $this->companysetup->getsystemtype($config['params']);
    $status = true;
    $isvatexpurch = $this->companysetup->getvatexpurch($config['params']);
    $isglc = $this->companysetup->isglc($config['params']);
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $fields = '';
    if ($systype == 'REALESTATE') {
      $fields = ', stock.projectid as sprojectid,stock.phaseid,stock.modelid,stock.blklotid,stock.amenityid,stock.subamenityid';
    }
    switch ($companyid) {
      case 12: //afti usd
      case 10: //afti
        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(a.acno,item.asset) as asset,ifnull(r.acno,item.revenue) as revenue,
        stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,stock.projectid,head.subproject,stock.stageid,head.branch,head.deptid,head.ewtrate,head.ewt,stock.freight
        from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid left join projectmasterfile as p on p.line = stock.projectid 
        left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid where head.trno=?';
        break;
      case 24: //goodfound
        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
        stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid,stock.freight,head.ewtrate,head.ewt,head.excess,head.excessrate
        from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid where head.trno=?';
        break;
      case 40: //cdo
        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
        stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid,stock.freight,head.ewtrate,head.ewt,cat.name as category
        from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid left join itemcategory as cat on cat.line = item.category where head.trno=?';
        break;

      default:

        if ($createapv) {
          $qry = "select head.dateid,client.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,'') as asset,ifnull(item.revenue,'') as revenue,
                stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid,stock.freight,head.ewtrate,head.ewt, ifnull(item.liability,'') as liability, client.isnonbdo, client.acctadvances
                from glhead as head  left join glstock as stock on stock.trno=head.trno left join client as wh on wh.clientid=stock.whid left join item on item.itemid=stock.itemid LEFT JOIN client ON client.clientid=head.clientid
                where head.trno=" . $config['params']['rrtrno'] . " and item.channel='OUTRIGHT'";
        } else {
          $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
                stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid,stock.freight,head.ewtrate,head.ewt' . $fields . '
                from ' . $this->head . ' as head  left join ' . $this->stock . ' as stock on stock.trno=head.trno left join client as wh on wh.clientid=stock.whid left join item on item.itemid=stock.itemid 
                where head.trno=?';
        }
        break;
    }

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    $ewt = 0;
    $excesstax = 0;
    $totalap = 0;
    $delcharge = 0;
    $cost = 0;
    $lcost = 0;

    if (!empty($stock)) {
      if ($periodic) {
        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['PS1']); //Purchases acct under asset 
      } else {
        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
        if ($companyid == 24) $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['INS1']); //goodfound        
      }

      $vat = $stock[0]->tax;
      $tax1 = 0;
      $tax2 = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }


      if ($stock[0]->ewtrate != 0) {
        $ewt = $stock[0]->ewtrate / 100;
      }

      if ($companyid == 24) { //goodfound
        if ($stock[0]->excessrate != 0) {
          $excesstax = $stock[0]->excessrate / 100;
        }
      }

      foreach ($stock as $key => $value) {
        $params = [];
        if ($this->companysetup->getisdiscperqty($config['params'])) {
          $discamt = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));
          $disc = $discamt * $stock[$key]->rrqty;
        } else {
          $disc = ($stock[$key]->rrcost * $stock[$key]->rrqty) - ($this->othersClass->discount($stock[$key]->rrcost * $stock[$key]->rrqty, $stock[$key]->disc));
        }

        if ($vat != 0) {
          if ($isvatexpurch) {
            $tax = ($stock[$key]->ext * $tax2);
          } else {

            $tax = number_format(($stock[$key]->ext / $tax1), 6, '.', '');
            $tax = $stock[$key]->ext - $tax;
          }
        }
        $delcharge += $stock[$key]->freight;

        if ($companyid == 24) $stock[$key]->asset = ''; //goodfound
        if ($companyid == 10) {
          if ($stock[$key]->asset == '') {
            return false;
          }
        }

        switch ($companyid) {
          case 8: //maxipro
          case 19: //housegem
            $cost = number_format($stock[$key]->cost * $stock[$key]->qty, 2, '.', '');
            break;
          case 23: //labsol
            $cost = $stock[$key]->cost * $stock[$key]->qty;
            if ($stock[$key]->forex == 1) {
              $lcost = $cost - ($cost / 1.15);
            } else {
              $lcost = $cost - ($cost / 1.30);
            }

            break;
          case 41: //labsolmla
          case 52: //technolab
            $cost = $stock[$key]->cost * $stock[$key]->qty;
            $lcost = $cost - ($cost / 1.05);
            break;
          default:
            $cost = number_format($stock[$key]->cost * $stock[$key]->qty, 6, '.', '');
            break;
        }

        if ($companyid == 40) { //cdo
          if (strtoupper($stock[$key]->category) <> "MC UNIT") {
            $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN2']);
          }
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'tax' =>  $tax,
          'discamt' => $disc,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' =>  $cost,
          'projectid' => $stock[$key]->projectid,
          'subproject' => $stock[$key]->subproject,
          'stageid' => $stock[$key]->stageid,
          'freight' => $stock[$key]->freight,
          'lcost' => $lcost
        ];

        if ($companyid == 56) { //homeworks
          $contraAP = $invacct2 = '';
          if ($stock[$key]->isnonbdo) {
            $contraAP = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$stock[$key]->acctadvances]);
          } else {
            $contraAP = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AP3']);
          }
          if ($contraAP == '') $contraAP = $stock[$key]->contra;

          $invacct2 = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN3']);
          if ($invacct2 != '') {
            $invacct = $invacct2;
          }

          $params['acno'] = $stock[$key]->liability !== '' ? $stock[$key]->liability : $contraAP;
          $params['inventory'] = $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct;
        }

        if ($systype == 'REALESTATE') {
          $params['projectid'] = $stock[$key]->sprojectid;
          $params['phaseid'] = $stock[$key]->phaseid;
          $params['modelid'] = $stock[$key]->modelid;
          $params['blklotid'] = $stock[$key]->blklotid;
          $params['amenityid'] = $stock[$key]->amenityid;
          $params['subamenityid'] = $stock[$key]->subamenityid;
        }

        //add new fields here

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $params['branch'] = $stock[$key]->branch;
            $params['deptid'] = $stock[$key]->deptid;
            $params['ewt'] = $ewt;
            $params['ewtcode'] = $stock[$key]->ewt;
            break;
          case 24: //goodfound
            $params['ewt'] = $ewt;
            $params['ewtcode'] = $stock[$key]->ewt;
            $params['excesstax'] = $excesstax;
            $params['excess'] = $stock[$key]->excess;
            break;
          default:
            if ($ewt != 0) {
              $params['ewt'] = $ewt;
              $params['ewtcode'] = $stock[$key]->ewt;
            }
            break;
        }

        if ($isvatexpurch) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
      }

      if ($delcharge != 0) {
        $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
        $d = $this->coreFunctions->opentable($qry, [$trno]);
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['DC1']);
        $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => 0, 'cr' => $delcharge, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0];
        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    if (!empty($this->acctg)) {
      $tdb = 0;
      $tcr = 0;
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      if ($isglc) {
        //loop to get totals
        foreach ($this->acctg as $key => $value) {
          $tdb = $tdb +  round($this->acctg[$key]['db'], 2);
          $tcr = $tcr +  round($this->acctg[$key]['cr'], 2);
        }

        $diff = $tdb - $tcr;
        $alias = 'GLC';

        if ($diff != 0) {
          $qry = "select client,forex,dateid,cur,branch,deptid,contra,projectid,wh from " . $this->head . " where trno = ?";
          $d = $this->coreFunctions->opentable($qry, [$trno]);

          if (abs(round($diff, 2)) != 0) {
            if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila, technolab
              $alias = 'SA5';
            } else {
              if ($config['params']['companyid'] == 8) {
                switch ($params['projectid']) {
                  case '3':
                  case '7':
                    $alias = 'GLC1';
                    break;

                  default:
                    $alias = 'GLC2';
                    break;
                }
              }
            }

            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$alias]);

            if ($diff < 0) {
              $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => abs(round($diff, 2)), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];
              if ($systype == 'REALESTATE') {
                $entry['projectid'] = $params['projectid'];
                $entry['phaseid'] = $params['phaseid'];
                $entry['modelid'] = $params['modelid'];
                $entry['blklotid'] = $params['blklotid'];
                $entry['amenityid'] = $params['amenityid'];
                $entry['subamenityid'] = $params['subamenityid'];
              }
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => 0, 'cr' => abs(round($diff, 2)), 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];
              if ($systype == 'REALESTATE') {
                $entry['projectid'] = $params['projectid'];
                $entry['phaseid'] = $params['phaseid'];
                $entry['modelid'] = $params['modelid'];
                $entry['blklotid'] = $params['blklotid'];
                $entry['amenityid'] = $params['amenityid'];
                $entry['subamenityid'] = $params['subamenityid'];
              }
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }
      }

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
        $status =  true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }

      //checking for less than 1 discrepancy
      $variance = $this->coreFunctions->datareader("select ifnull(sum(db-cr),0) as value from " . $this->detail . " where trno=?", [$trno], '', true);
      if (abs($variance) < 1 && abs($variance) <> 0) {

        $qry = "select client,forex,dateid,cur,branch,deptid,contra,projectid,wh from " . $this->head . " where trno = ?";
        $d = $this->coreFunctions->opentable($qry, [$trno]);
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['GLC']);

        if ($acnoid != 0) {
          $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => 0, 'cr' => $variance, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];

          if ($variance > 0) {
            $entry['cr'] = abs($variance);
            $entry['db'] = 0;
          } else {
            $entry['db'] = abs($variance);
            $entry['cr'] = 0;
          }

          if ($systype == 'REALESTATE') {
            $entry['projectid'] = $params['projectid'];
            $entry['phaseid'] = $params['phaseid'];
            $entry['modelid'] = $params['modelid'];
            $entry['blklotid'] = $params['blklotid'];
            $entry['amenityid'] = $params['amenityid'];
            $entry['subamenityid'] = $params['subamenityid'];
          }
          $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
          $line = $this->coreFunctions->datareader($qry, [$trno]);
          if ($line == '') {
            $line = 0;
          }
          $entry['line'] = $line + 1;
          $entry['trno'] = $trno;
          $this->coreFunctions->sbcinsert($this->detail, $entry);
        } else {
          if (abs($variance) == 0.01 || abs($variance) == 0.02) {
            $taxamt = $this->coreFunctions->datareader("select d.db as value from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and coa.alias='TX1'", [$trno], '', true);
            if ($taxamt != 0) {
              $salesentry = $this->coreFunctions->opentable("select d.line from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and left(coa.alias,2)='IN'  order by d.line desc limit 1", [$trno]);
              if ($salesentry) {
                $this->coreFunctions->execqry("update " . $this->detail . " set db=db-" . $variance . " where trno=" . $trno . " and line=" . $salesentry[0]->line);
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'FORCE BALANCE WITH ' . abs($variance) . ' VARIANCE');
              }
            }
          }
        }
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $entry = [];
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }
    $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);
    $periodic = $this->companysetup->getisperiodic($config['params']);

    if (!$this->companysetup->getispurchasedisc($config['params'])) {
      $params['discamt'] = 0;
    }

    $cur = $params['cur'];
    $invamt = $params['cost'];


    $ewt = isset($params['ewt']) ? $params['ewt'] : 0;
    $ext = $params['ext'];
    $excesstax =  isset($params['excesstax']) ? $params['excesstax'] : 0;

    if ($ewt != 0) {
      if ($params['tax'] != 0) {

        $ewt = round(($params['ext'] - $params['tax']) * $params['ewt'], 2);
        $ext = round($params['ext'] - $ewt, 2);
      } else {
        $ewt = round($params['ext'] * $params['ewt'], 2);
        $ext = round($params['ext'] - $ewt, 2);
      }
    }

    if ($companyid == 24) { //goodfound
      if ($params['excesstax'] != 0) {
        if ($params['tax'] != 0) {
          $excesstax = round(($params['ext'] - $params['tax']) * $params['excesstax'], 2);
          $ext = round($params['ext'] - $ewt - $excesstax, 2);
        } else {
          $excesstax = round($params['ext'] * $params['excesstax'], 2);
          $ext = round($params['ext'] - $ewt - $excesstax, 2);
        }
      }
    }

    //AP
    if (!$suppinvoice) {
      if (floatval($ext) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($ext * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ext, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }


        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      //disc
      if ($periodic) {
        if (floatval($params['discamt']) != 0) {
          $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
          $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

          if ($companyid == 10) { //afti
            $entry['branch'] = $params['branch'];
            $entry['deptid'] = $params['deptid'];
          }

          if ($systype == 'REALESTATE') {
            $entry['projectid'] = $params['projectid'];
            $entry['phaseid'] = $params['phaseid'];
            $entry['modelid'] = $params['modelid'];
            $entry['blklotid'] = $params['blklotid'];
            $entry['amenityid'] = $params['amenityid'];
            $entry['subamenityid'] = $params['subamenityid'];
          }
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }
      }


      if (floatval($params['tax']) != 0) {
        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($ewt) != 0) {
        // EWt
        $ewtAlias = 'APWT1';
        if ($companyid == 56) { //homeworks
          $ewtAlias = 'WT1';
        }
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$ewtAlias]);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => ($ewt * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($ewt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($excesstax) != 0) {
        // excesstax

        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX3']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => ($excesstax * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($excesstax), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV
    if (floatval($invamt) != 0) {
      $freight = $params['freight'];
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $invamt + $freight, 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->coreFunctions->LogConsole('INV ' . ($invamt + $freight));

      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }

      if ($systype == 'REALESTATE') {
        $entry['projectid'] = $params['projectid'];
        $entry['phaseid'] = $params['phaseid'];
        $entry['modelid'] = $params['modelid'];
        $entry['blklotid'] = $params['blklotid'];
        $entry['amenityid'] = $params['amenityid'];
        $entry['subamenityid'] = $params['subamenityid'];
      }

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      if ($suppinvoice) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => $invamt, 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila, technolab
      $lcost = $params['lcost'];
      if (floatval($lcost) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA5'");
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $lcost, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($lcost / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function distributionvatex($params, $config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $entry = [];
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }
    $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);

    $cur = $params['cur'];
    $invamt = $params['ext'];
    $ewt = isset($params['ewt']) ? $params['ewt'] : 0;
    $ext = $params['ext'];
    $excesstax = isset($params['excesstax']) ? $params['excesstax'] : 0;

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($params['ewt'] != 0) {
        $ewt = $params['ext'] * $params['ewt'];
        $ext = $params['ext'] - $ewt;
      }
    }

    if ($companyid == 24) { //goodfound
      if ($params['ewt'] != 0) {
        $ewt = round($params['ext'] * $params['ewt'], 2);
        $ext = round($params['ext'] - $ewt, 2);
      }

      if ($params['excesstax'] != 0) {
        $excesstax = round($params['ext'] * $params['excesstax'], 2);
        $ext = round($params['ext'] - $excesstax, 2);
      }
    }


    //AP
    if (!$suppinvoice) {
      if (floatval($ext) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => (($ext + $params['tax']) * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ext, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      //disc
      if (floatval($params['discamt']) != 0) {
        $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
        $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($params['tax']) != 0) {
        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($ewt) != 0) {
        // EWt
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => ($ewt * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($ewt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($excesstax) != 0) {
        // excesstax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX3']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => ($excesstax * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($excesstax), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV
    if (floatval($invamt) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['cost'] / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      if ($companyid == 10 || $companyid == 24) { //afti,goodfound
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        if ($ewt != 0) {
          $entry['isewt'] = 1;
          $entry['ewtcode'] = $params['ewtcode'];
        }

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }

        if (floatval($params['tax']) != 0) {
          $entry['isvat'] = 1;
        }
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      if ($suppinvoice) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => $params['cost'], 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['cost'] / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

        if ($systype == 'REALESTATE') {
          $entry['projectid'] = $params['projectid'];
          $entry['phaseid'] = $params['phaseid'];
          $entry['modelid'] = $params['modelid'];
          $entry['blklotid'] = $params['blklotid'];
          $entry['amenityid'] = $params['amenityid'];
          $entry['subamenityid'] = $params['subamenityid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  private function updateprojmngmt($config, $stage)
  {
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
    $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

    $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='RR' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='RR' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $editdate = $this->othersClass->getCurrentTimeStamp();
    $editby = $config['params']['user'];

    return $this->coreFunctions->execqry("update stages set rr=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
  }

  private function updateprojmngmtap($config, $data)
  {
    $trno = $config['params']['trno'];
    foreach ($data as $key => $value) {
      $data2 = $this->coreFunctions->datareader("select ifnull(sum(a.cr-a.db),0) as value from apledger as a left join gldetail as d on d.trno = a.trno and d.line = a.line  where d.projectid =" . $data[$key]->projectid . " and d.subproject=" . $data[$key]->subproject . " and d.stageid=" . $data[$key]->stageid);

      if (empty($data2)) {
        $data2 = 0;
      }
      $this->coreFunctions->execqry("update stages set ap=" . $data2 . " where projectid = " . $data[$key]->projectid . " and subproject=" . $data[$key]->subproject . " and stage=" . $data[$key]->stageid, 'update');
    }
    return true;
  }

  private function updateitemsrp($config, $trno)
  {

    $qry = "select head.docno,stock.itemid,stock.cost,head.rrfactor 
    from glstock as stock 
    left join glhead as head on head.trno=stock.trno
    where stock.trno = ? and head.rrfactor<>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);

    foreach ($data as $key => $value) {
      # code...
      $retail_price =  $this->coreFunctions->getfieldvalue('item', 'amt', "itemid=?", [$value->itemid]);
      $srp = $value->cost * $value->rrfactor;
      if ($retail_price != $srp) {
        $this->coreFunctions->sbcupdate('item', ['amt' => $srp, 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['itemid' => $value->itemid]);
        $this->logger->sbcwritelog($value->itemid, $config, 'AUTO UPDATE', $value->docno . ': CHANGED SRP: ' . number_format($srp, 2), 'item_log');
      }
    }
  }


  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $isreload = false;

    // auto lock
    switch ($config['params']['companyid']) {
      case 28: //xcomp
      case 10: //afti
      case 60: //transpower
        break;
      default:
        $config['params']['action'] = 'lock';
        $config['params']['locktype'] = 'AUTO';
        $this->headClass->lockunlock($config);
        $isreload = true;
        break;
    }


    if ($config['params']['companyid'] == 60) { //transpower
      $this->posttrans($config);
      $isreload = true;
    }
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => $isreload];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    $dataparams = $config['params']['dataparams'];
    if ($companyid == 36) { //cbbsi
      if (isset($dataparams['audited'])) $this->othersClass->writeSignatories($config, 'audited', $dataparams['audited']);
    } else if ($companyid == 3 || $companyid == 39 || $companyid == 40) { //conti,cbbsi,cdo
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    } else {
      if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
    }
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }

  public function getpaysummaryqry($config)
  {
    return "
    select apledger.docno,apledger.trno,apledger.line,ctbl.clientname,ctbl.client,forex.cur,forex.curtopeso as forex,apledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    apledger.clientid,apledger.db,apledger.cr, apledger.bal ,left(apledger.dateid,10) as dateid,
    abs(apledger.fdb-apledger.fcr) as fdb,glhead.yourref,gldetail.rem as drem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,
    gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate,coa.alias,gldetail.postdate,glhead.tax,case glhead.vattype when '' then 'NON-VATABLE' else glhead.vattype end as vattype,glhead.ewt,glhead.ewtrate from (apledger
    left join coa on coa.acnoid=apledger.acnoid)
    left join glhead on glhead.trno = apledger.trno
    left join gldetail on gldetail.trno=apledger.trno and gldetail.line=apledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = apledger.clientid
    left join forex_masterfile as forex on forex.line = ctbl.forexid
    where cntnum.trno = ? and apledger.bal<>0 and coa.alias <> 'APWT1'";
  }

  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));
      $kgs = $this->othersClass->sanitizekeyfield('qty', $data2[$key]['kgs']);

      if ($this->companysetup->getvatexpurch($config['params'])) {
        $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, 0, 'P', $kgs);
      } else {
        if ($config['params']['companyid'] == 28 || $config['params']['companyid'] == 39) { //xcomp,cbbsi
          $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, $head['tax'], 'P', $kgs,  0, 1);
        } else {
          $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, $head['tax'], 'P', $kgs);
        }
      }

      if ($config['params']['companyid'] == 23) { //labsol cebu
        if ($head['forex'] == 1) {
          $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.15 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        } else {
          $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.30 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        }
      } elseif ($config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) { //labsol manila, technolab
        $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.05 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      } else {
        $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      }
    }
    return $exec;
  }

  public function generateassettag($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;

    try {
      $generic = $this->getpendinggenericeitem($config);

      if (empty($generic)) {
        return ['trno' => $trno, 'status' => $status, 'msg' => 'There is nothing to generate asset tags.', 'reloadhead' => true];
      }

      foreach ($generic as $key => $value) {
        $qry = "select s.trno, s.line, s.itemid, s.qty as rrqty, s.uom, item.barcode, item.itemname, 
                       item.brand, item.model, item.groupid, item.class, item.part, item.category, item.sizeid, 
                       item.body, client.clientid, h.dateid
                from lastock as s 
                left join item on item.itemid=s.itemid 
                left join lahead as h on h.trno=s.trno 
                left join client on client.client=h.client
                left join rrfams as rrf on rrf.trno=s.trno and rrf.line=s.line
                where s.trno=? and item.isgeneric=1 and s.itemid=? and s.trno=? and s.line=?
                group by s.trno, s.line, s.itemid, s.qty, s.uom, item.barcode, item.itemname, item.brand, item.model, 
                item.groupid, item.class, item.part, item.category, item.sizeid, item.body, client.clientid, h.dateid";
        $generics = $this->coreFunctions->opentable($qry, [$trno, $value->itemid, $value->trno, $value->line]);

        $isnsi = 0;
        foreach ($generics as $k => $v) {
          for ($index = 1; $index <= ($v->rrqty - $value->qty); $index++) {

            $itemseq = $this->coreFunctions->datareader("select itemseq as value from item where subcode='" . $v->barcode . "' and isfa=1 order by itemseq desc limit 1");
            if ($itemseq == '') {
              $itemseq = 1;
            } else {
              $itemseq = $itemseq + 1;
            }
            $barcode =  $v->barcode . '-' . $itemseq;

            $this->othersClass->logConsole("index:" . $index . ' - count:' . ($v->rrqty - $value->qty) . ' - barcode:' . $barcode);

            $data = [
              'barcode' => $barcode,
              'subcode' => $v->barcode,
              'itemname' => $v->itemname,
              'isfa' => 1,
              'uom' => $v->uom,
              'brand' => $v->brand,
              'model' => $v->model,
              'groupid' => $v->groupid,
              'class' => $v->class,
              'part' => $v->part,
              'category' => $v->category,
              'sizeid' => $v->sizeid,
              'body' => $v->body,
              'isnsi' => $isnsi,
              'supplier' => $v->clientid,
              'itemseq' => $itemseq,
              'isinactive' => 1,
              'othcode' => ''
            ];
            $fa_itemid = $this->coreFunctions->insertGetId('item', $data);
            if ($fa_itemid != 0) {
              $rrfams = [
                'trno' => $trno,
                'line' => $v->line,
                'itemid' => $fa_itemid,
                'qty' => 1
              ];
              $this->coreFunctions->sbcinsert('rrfams', $rrfams);
              $iteminfo = [
                'itemid' => $fa_itemid,
                'icondition' => 0,
                'dateacquired' => $v->dateid
              ];
              $this->coreFunctions->sbcinsert('iteminfo', $iteminfo);
            }
          }
        }
      }
    } catch (Exception $e) {
      $status = false;
      $msg .= 'Failed to generate asset tag. Exception error ' . $e->getMessage();
      goto exithere;
    }

    exithere:
    if ($msg == '') {
      $msg = 'Successfully uploaded.';
    }
    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function getpendinggenericeitem($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select s.itemid, s.trno, s.line, s.rrqty, ifnull(sum(rr.qty),0) as qty
            from lastock as s 
            left join item on item.itemid=s.itemid 
            left join rrfams as rr on rr.trno=s.trno and rr.line=s.line 
            where s.trno=? and item.isgeneric=1
            group by s.itemid, s.trno, s.line, s.rrqty 
            having s.rrqty<>ifnull(sum(rr.qty),0)";
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function createapv($config)
  {
    $status = true;
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $tablelogs = 'table_log';
    $companyid = $config['params']['companyid'];
    $addf = '';
    if ($companyid == 56) {
      $addf = ',head.checkdate,head.checkno';
    }
    $msg = '';

    $qry = "select head.docno,head.doc,head.dateid,head.yourref,head.ourref, wh.client as wh,head.forex,head.clientid,client.client,head.clientname,head.terms,head.cur,head.projectid,
      head.ewt,head.ewtrate,head.vattype,head.tax,head.due,head.rem,head.address,head.contra,head.pvtrno $addf
      from glhead as head LEFT JOIN client ON client.clientid=head.clientid left join client as wh on wh.clientid=head.whid where head.trno = ?";
    $rrdata = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($rrdata)) {

      if ($rrdata[0]->pvtrno) {
        return ['status' => false, 'msg' => 'Failed to generate, APV was already generated.', 'reloadhead' => true];
      }

      $data = [
        'dateid' =>  $rrdata[0]->dateid,
        'due' => $rrdata[0]->due,
        'client' => $rrdata[0]->client,
        'clientname' => $rrdata[0]->clientname,
        'address' => $rrdata[0]->address,
        'terms' => $rrdata[0]->terms,
        'wh' => $rrdata[0]->wh,
        'yourref' => $rrdata[0]->yourref,
        'contra' => $rrdata[0]->contra,
        'ourref' => $rrdata[0]->ourref,
        'forex' => $rrdata[0]->forex,
        'cur' => $rrdata[0]->cur,
        'projectid' => $rrdata[0]->projectid,
        'ewt' => $rrdata[0]->ewt,
        'ewtrate' => $rrdata[0]->ewtrate,
        'rem' => $rrdata[0]->rem,
        'vattype' => $rrdata[0]->vattype,
        'tax' => $rrdata[0]->tax,
        'rrtrno' => $trno,
        'lockuser' => $config['params']['user'],
        'lockdate' => $this->othersClass->getCurrentTimeStamp()
      ];

      if ($companyid == 56) { //homeworks
        $data['checkdate'] = $rrdata[0]->checkdate;
        $data['checkno'] = $rrdata[0]->checkno;
      }

      $pref1 = 'PV';
      // $brprefix = $this->coreFunctions->datareader("SELECT client.prefix AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE c.code='" . $config['params']['center'] . "'");
      // $pref = $pref1 . $brprefix; //PVCW

      $pvtrno = $this->othersClass->generatecntnum($config, 'cntnum', 'PV', $pref1);
      if ($pvtrno != -1) {
        $docno =  $this->coreFunctions->getfieldvalue('cntnum', 'docno', "trno=?", [$pvtrno]);
        $doc =  $this->coreFunctions->getfieldvalue('cntnum', 'doc', "trno=?", [$pvtrno]);

        $data['trno'] = $pvtrno;
        $data['doc'] = $doc;
        $data['docno'] = $docno;
        $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['createby'] = $config['params']['user'];

        $insert = $this->coreFunctions->sbcinsert('lahead', $data);

        if ($insert) {
          $this->coreFunctions->execqry("update glhead set pvtrno=" . $pvtrno . " where trno=? ", "update", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'CREATE', 'AUTO-GENERATE ' . $docno, $tablelogs);
          $this->logger->sbcwritelog($pvtrno, $config, 'CREATE', 'AUTO-GENERATED ' . $docno, $tablelogs);

          $config['params']['rrtrno'] = $trno;
          $config['params']['trno'] = $pvtrno;
          $result = $this->createdistribution($config, true);
          if (!$result) {
            $msg .= "Failed to generate accounting distribution.";
            $status = false;
          }
        }
      }
    }
    exithere:
    if (!$status) {
      if ($pvtrno != 0) {
        $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$pvtrno]);
        $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', [$pvtrno]);
        $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$pvtrno]);
      }
    }

    if ($msg == '') {
      $msg = "Successfully created. PV Document: " . $docno . "";
    }
    return ['status' => false, 'msg' => $msg, 'reloadhead' => true];
  }

  public function getsjsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $this->coreFunctions->getfieldvalue($this->head, 'wh', 'trno=?', [$trno]);
    $companyid = $config['params']['companyid'];
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno = ?', [$trno]);
    $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
    $cl = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $config['params']['client'] = $this->coreFunctions->getfieldvalue($this->head, 'client', 'trno=?', [$trno]);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.iss-stock.rrqa) as iss,stock.isamt,
        round((stock.iss-stock.rrqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.fcost,stock.loc,stock.expiry,stock.projectid,stock.sgdrate,stock.rem as srem
        FROM glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.rrqa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = $data[$key2]->srem;
          $config['params']['data']['sjrefx'] = $data[$key2]->trno;
          $config['params']['data']['sjlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['barcode'] = $data[$key2]->barcode;

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedsjitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsjitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function


  public function getsjdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno = ?', [$trno]);
    $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
    $cl = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $config['params']['client'] = $this->coreFunctions->getfieldvalue($this->head, 'client', 'trno=?', [$trno]);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.iss-stock.rrqa) as iss,stock.isamt,
        round((stock.iss-stock.rrqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.fcost,stock.loc,stock.expiry,stock.projectid,stock.sgdrate,stock.rem as srem
        FROM glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.rrqa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = $data[$key2]->srem;
          $config['params']['data']['sjrefx'] = $data[$key2]->trno;
          $config['params']['data']['sjlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['barcode'] = $data[$key2]->barcode;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['disc'] = $data[$key2]->disc;

          // $lprice = $this->getlatestprice($config, $forex);
          // $lprice = json_decode(json_encode($lprice), true);
          // if (!empty($lprice['data'])) {
          //   $config['params']['data']['amt'] = $lprice['data'][0]['amt'];
          //   $config['params']['data']['disc'] = $lprice['data'][0]['disc'];
          // }
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedsjitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsjitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function

  public function setservedsjitems($refx, $linex)
  {
    $qry = "select stock." . $this->hqty . " from lahead as head left join lastock as stock on stock.trno=head.trno where head.doc='RR' and stock.sjrefx=" . $refx . " and stock.sjlinex=" . $linex . "
    union all
    select stock." . $this->hqty . " from glhead as head left join glstock as stock on stock.trno=head.trno where head.doc='RR' and stock.sjrefx=" . $refx . " and stock.sjlinex=" . $linex;
    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry . ") as t";
    $qty = $this->coreFunctions->datareader($qry2, [], '', true);
    $result = $this->coreFunctions->execqry("update glstock set rrqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from glstock where trno=? and qty>rrqa", [$refx], '', true);
    if ($status != 0) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from glstock where trno=? and rrqa<>0", [$refx], '', true);
      if ($status != 0) {
        $this->coreFunctions->execqry("update cntnum set statid=6 where trno=" . $refx);
      } else {
        $this->coreFunctions->execqry("update cntnum set statid=5 where trno=" . $refx);
      }
    } else {
      $this->coreFunctions->execqry("update cntnum set statid=7 where trno=" . $refx);
    }
    return $result;
  }

  public function sbcscript($config)
  {
    if ($config['params']['companyid'] == 60) { //transpower
      return $this->sbcscript->loaditembal($config);
    } else {
      return true;
    }
  }
} //end class
