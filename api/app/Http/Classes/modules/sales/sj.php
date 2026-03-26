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
  public $modulename = 'SALES JOURNAL';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $statlogs = 'cntnum_stat';
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
    'agent',
    'projectid',
    'creditinfo',
    'billid',
    'shipid',
    'branch',
    'deptid',
    'taxdef',
    'billcontactid',
    'shipcontactid',
    'ms_freight',
    'mlcp_freight',
    'shipto',
    'salestype',
    'sotrno',
    'statid',
    'deldate',
    'crref',
    'istrip',
    'ewt',
    'ewtrate',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid',
    'bpo',
    'ctnsno',
    'invoiceno'
  ];

  private $except = ['trno', 'dateid', 'due'];
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
    $companyid = $config['params']['companyid'];
    $userid = $config['params']['adminid'];
    $dept = '';
    if ($companyid == 10) { //afti
      if ($userid != 0) {
        $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid = ?", [$userid]);
        $dept = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid = ?", [$deptid]);
      }

      $this->showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
      ];
    }

    if ($companyid == 19 && $this->companysetup->getcompanyalias($config['params']) == 'HOUSEGEM') { //housegem
      $this->showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'forwtinput', 'label' => 'For Weight Input', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'forposting', 'label' => 'For Posting', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
      ];
    }
    if ($this->companysetup->linearapproval($config['params'])) {
      $this->showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
        ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
      ];
    }


    // $action = 0;
    // $liststatus = 1;
    // $listdocument = 2;
    // $listdate = 3;
    // $listclientname = 4;
    // $shipto = 5;
    // $yourref = 6;
    // $ourref = 7;
    // $total = 8;
    // $rem = 9;
    // $ar = 10;
    // $postedby = 11;
    // $createby = 12;
    // $editby = 13;
    // $viewby = 14;
    // $receiveby = 15;
    // $receivedate = 16;

    if ($companyid == 29) {
      $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'terms', 'shipto', 'yourref', 'ourref', 'rem', 'total', 'ar', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby', 'receiveby', 'receivedate'];
    } else {
      $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'terms', 'shipto', 'yourref', 'ourref', 'total', 'rem', 'ar', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby', 'receiveby', 'receivedate'];
    }
    // $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'terms', 'shipto', 'yourref', 'ourref', 'total', 'rem', 'ar', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby', 'receiveby', 'receivedate'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }


    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    if ($companyid == 24) { //goodfound
      $cols[$liststatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    } else {
      $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    }


    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    if ($companyid == 10 or $companyid == 12) { //afti, afti usd
      $cols[$yourref]['label'] = 'Customer PO';
    }
    $cols[$total]['label'] = 'Total Amount';
    $cols[$ar]['label'] = 'AR Balance';
    $cols[$total]['align'] = 'text-left';

    if ($companyid != 19 && $companyid != 28) { //not housegem & not xcomp
      $cols[$total]['type'] = 'coldel';
    }
    if ($companyid != 28 && $companyid != 37 && $companyid != 29) { //not xcomp & mega crystal
      $cols[$rem]['type'] = 'coldel';
    }
    if ($companyid != 19) { //not housegem
      $cols[$ar]['type'] = 'coldel';
    }

    if ($companyid != 22) { //not eipi
      $cols[$shipto]['type'] = 'coldel';
    }

    if ($companyid != 21) { //not kinggeorge
      $cols[$terms]['type'] = 'coldel';
    }

    $cols[$liststatus]['name'] = 'statuscolor';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $cols[$ourref]['type'] = 'coldel';
      $cols[$listeditby]['type'] = 'coldel';
      $cols[$listviewby]['type'] = 'coldel';
      $cols[$liststatus]['name'] = 'statuscolor';
      $cols[$ar]['type'] = 'input';
      $cols[$ar]['label'] = 'Balance';
      if ($dept != 'ACCTG') {
        $cols[$ar]['type'] = 'coldel';
        $cols[$liststatus]['name'] = 'status';
      }
    } else {
      $cols[$receiveby]['type'] = 'coldel';
      $cols[$receivedate]['type'] = 'coldel';
    }

    if ($companyid == 20) { //proline
      $cols[$createby]['label'] = 'Created by';
      $cols[$editby]['label'] = 'Edited by';
      $cols[$viewby]['label'] = 'Viewed by';
    }

    if ($companyid == 29) {
      $cols[$rem]['style'] = 'width:320px;whiteSpace: normal;min-width:320px;';
    }



    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];

    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
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
    if ($companyid == 10) { //afti
      if ($userid != 0) {
        $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid = ?", [$userid]);
        $dept = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid = ?", [$deptid]);
      }
    }
    if ($this->companysetup->linearapproval($config['params'])) {
      $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : $itemfilter;
      $user = $config['params']['user'];
      $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);
      if ($userid != 0) {
        $qry = "select s.isapprover as value
                from approversetup as s
                left join approverdetails as d on d.appline=s.line
                left join useraccess as u on u.username=d.approver
                where u.userid=? and s.doc=? ";

        $isapprover = $this->coreFunctions->datareader($qry, [$userid, $doc]);
        if ($isapprover == 1) {
          $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
        }
      }
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and head.lockdate is null and num.postdate is null and num.statid=0';
        break;
      case 'forapproval':
        $condition = " and num.postdate is null and head.lockdate is null and num.statid=10 
        and num.appuser='" . $config['params']['user'] . "'";
        break;
      case 'approved':
        $condition = ' and num.postdate is null and head.lockdate is null and num.statid=36';
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
    switch ($companyid) {
      case 8: //maxipro
      case 10: //afti
        if (isset($config['params']['doclistingparam'])) {
          $test = $config['params']['doclistingparam'];
          if (isset($test['selectprefix'])) {
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
              }

              if (isset($test)) {
                $join = " left join lastock on lastock.trno = head.trno
            left join item on item.itemid = lastock.itemid left join item as item2 on item2.itemid = lastock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

                $hjoin = " left join glstock as glstock on glstock.trno = head.trno
            left join item on item.itemid = glstock.itemid left join item as item2 on item2.itemid = glstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";
                $limit = '';

                $linkstock = true;
              }
            }
          }
        }
        break;
    }

    $dateid = "left(head.dateid,10) as dateid";
    $orderby = "order by dateid desc, docno desc";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $lstat = "'DRAFT'";
        $gstat = "case '" . $dept . "' when 'ACCTG' then (case (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) when 0 then 'PAID' 
        else (case when date(date_add(ifnull(ds.receivedate,head.dateid), interval terms.days day))<now() then 'OVERDUE' else 'UNPAID' end ) end)  
        else 'POSTED' end ";

        $gstatcolor = "case '" . $dept . "' when 'ACCTG' then (case (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) when 0 then 'green' 
        else (case when date(date_add(ifnull(ds.receivedate,head.dateid), interval terms.days day))<now() then 'red' else 'orange' end ) end)  
        else 'grey' end ";

        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
        $gfield = ",ds.receiveby,date_format(ds.receivedate,'%m-%d-%Y') as receivedate,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar";
        $lfield = ",ds.receiveby,date_format(ds.receivedate,'%m-%d-%Y') as receivedate,
        format(sum(stock.ext),2) as ar";
        $ljoin = 'left join ' . $this->stock . ' as stock on stock.trno=head.trno left join delstatus as ds on ds.trno=head.trno left join terms on terms.terms = head.terms';
        $gjoin = 'left join ' . $this->hstock . ' as stock on stock.trno=head.trno left join delstatus as ds on ds.trno=head.trno left join terms on terms.terms = head.terms ';
        if ($searchfilter == "") $limit = 'limit 25';
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref, head.ourref,ds.receiveby,ds.receivedate,terms.terms,terms.days,head.shipto';
        $orderby = "order by date2 desc, docno desc";
        break;
      case 19: //housegem
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $lfield = ',format(sum(stock.ext),2) as total,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar';
        $gfield = ',format(sum(stock.ext),2) as total,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar';
        $ljoin = 'left join ' . $this->stock . ' as stock on stock.trno=head.trno';
        $gjoin = 'left join ' . $this->hstock . ' as stock on stock.trno=head.trno';
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref, head.ourref,head.shipto';
        $orderby = "order by docno desc, dateid desc";
        break;
      case 24: //goodfound
        $lstat = "ifnull(stat.status,'DRAFT')";
        break;
      case 21: //kinggeorge
        $lfield = ',head.terms';
        $gfield = ',head.terms';
        if ($itemfilter == 'draft') {
          $orderby = "order by dateid, docno ";
        }
        if ($searchfilter == "") $limit = 'limit 150';
        break;
      case 28: //xcomp
        if ($searchfilter == "") $limit = 'limit 150';
        $lfield = ',format(sum(stock.ext),2) as total,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar,head.rem';
        $gfield = ',format(sum(stock.ext),2) as total,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar,head.rem';
        $ljoin = 'left join ' . $this->stock . ' as stock on stock.trno=head.trno';
        $gjoin = 'left join ' . $this->hstock . ' as stock on stock.trno=head.trno';
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref, head.ourref,head.rem,head.shipto';
        break;
      case 37: //mega crystal
      case 29: //sbc
        $rem = "head.rem,";
        if ($searchfilter == "") $limit = 'limit 150';
        $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
        $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";
        break;
      default:
        if ($searchfilter == "") $limit = 'limit 150';
        $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
        $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";
        if ($this->companysetup->linearapproval($config['params'])) {
          $lstat = "case when num.postdate is null and head.lockdate is null and num.statid=10 then 'FOR APPROVAL' 
          when num.postdate is null and num.statid=36 then 'APPROVED' else 'DRAFT' end";
          $lstatcolor = "case when num.postdate is null and num.statid=36 or num.statid=10 then 'grey' when head.lockdate is not null then 'green' else 'red' end";
        }

        break;
    }



    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = [
        'head.docno',
        'head.clientname',
        'head.yourref',
        'head.ourref',
        'num.postedby',
        'head.createby',
        'head.editby',
        'head.viewby',
        'head.rem'
      ];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    if ($linkstock) {
      if ($group == '') {
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref, head.ourref,head.shipto';
      }
    }
    $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $lstat as status, $lstatcolor as statuscolor,$rem
    head.createby,head.editby,head.viewby,num.postedby,
     head.yourref, head.ourref,head.shipto $lfield
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $ljoin
     " . $join . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and left(num.bref,3) <> 'SJS' 
     $group
     union all
     select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid,$gstat as status,$gstatcolor as statuscolor,$rem
     head.createby,head.editby,head.viewby, num.postedby,
      head.yourref, head.ourref,head.shipto $gfield
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $gjoin
     " . $hjoin . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and left(num.bref,3) <> 'SJS' 
     $group
    $orderby $limit";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    $isshortcutso = $this->companysetup->getisshortcutso($config['params']);

    $fields = [];
    switch ($companyid) {
      case 11: //summit
        $post = $this->othersClass->checkAccess($config['params']['user'], 178);
        if ($post) {
          array_push($fields, 'batchpostsj');
        }
        break;
    }

    if ($isshortcutso) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 171);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }

    $col1 = $this->fieldClass->create($fields);
    if ($companyid == 20) { //proline
      data_set($col1, 'pickpo.label', 'PICK JO');
    } else {
      data_set($col1, 'pickpo.label', 'PICK SO');
    }
    data_set($col1, 'pickpo.lookupclass', 'pendingsosummaryshortcut');
    data_set($col1, 'pickpo.action', 'pendingsosummary');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick SO?');
    data_set($col1, 'pickpo.addedparams', ['docno', 'selectprefix']);

    $fields = [];
    switch ($companyid) {
      case 17: //unihome
      case 20: //proline
      case 10: //afti
      case 12: //afti usd
      case 27: //NTE
      case 28: //xcomp
      case 36: //ROZLAB
      case 39: //CBBSI
        array_push($fields, ['selectprefix', 'docno']);
        break;
    }
    $col2 = $this->fieldClass->create($fields);
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        data_set($col2, 'docno.type', 'input');
        data_set($col2, 'docno.label', 'Search');
        data_set($col2, 'selectprefix.label', 'Search by');
        data_set($col2, 'selectprefix.type', 'lookup');
        data_set($col2, 'selectprefix.lookupclass', 'lookupsearchby');
        data_set($col2, 'selectprefix.action', 'lookupsearchby');
        break;
      default:
        data_set($col2, 'docno.type', 'input');
        data_set($col2, 'docno.label', 'Seq. No');
        break;
    }

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

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
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

    switch ($config['params']['companyid']) {
      case 19: //housegem
        $buttons['others']['items']['downloadexcel'] = ['label' => 'Download DR (Excel)', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
        break;

      case 21: //kinggeorge
        $buttons['lock']['vshow']['print'] = false;
        $buttons['post']['vshow']['print'] = false;
        break;
      case 56: // homeworks
        $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
        $buttons['others']['items']['downloadexcel'] = ['label' => 'Download SJ Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
        break;
    }

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
    $deliverystatus = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewdeliverystatus']];
    $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    switch ($companyid) {
      case 10: //afti
        $return['INSTRUCTION'] = ['icon' => 'fa fa-info', 'customform' => $instructiontab];
        $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
        $return['DELIVERY STATUS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $deliverystatus];
        break;
    }

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $release = $this->othersClass->checkAccess($config['params']['user'], 2994);
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $makecr = $this->othersClass->checkAccess($config['params']['user'], 3578);
    $inv = $this->companysetup->isinvonly($config['params']);

    $trip_tab = $this->othersClass->checkAccess($config['params']['user'], 4488);
    $arrived_tab = $this->othersClass->checkAccess($config['params']['user'], 4489);
    $trip_approve = $this->othersClass->checkAccess($config['params']['user'], 4494);
    $trip_disapprove = $this->othersClass->checkAccess($config['params']['user'], 4738);

    $viewfieldsforgate2users = $this->othersClass->checkAccess($config['params']['user'], 2509);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451); //kinggeorge


    $action = 0;
    $itemdesc = 1;
    $serial = 2;
    $isqty = 3;
    $uom = 4;
    $kgs = 5;
    $isamt = 6;
    $disc = 7;
    $ext = 8;
    $itemstatus = 9;
    $cost = 10;
    $markup = 11;
    $rebate = 12;
    $gprofit = 13;
    $itemd = 14;
    $wh = 15;
    $whname = 16;
    $ref = 17;
    $loc = 18;
    $expiry = 19;
    $rem = 20;
    $itemname = 21;
    $stock_projectname = 22;
    $noprint = 23;
    $barcode = 24;


    if ($inv) {
      $headgridbtns = ['viewref', 'viewdiagram', 'viewitemstockinfo'];
    } else {
      $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram', 'viewitemstockinfo'];
    }

    $column = [
      'action',
      'itemdescription',
      'serialno',
      'isqty',
      'uom',
      'kgs',
      'isamt',
      'disc',
      'ext',
      'itemstatus',
      'cost',
      'markup',
      'rebate',
      'gprofit',
      'itemdesc',
      'wh',
      'whname',
      'ref',
      'loc',
      'expiry',
      'rem',
      'itemname',
      'stock_projectname',
      'noprint',
      'barcode'
    ];
    $sortcolumn = [
      'action',
      'itemdescription',
      'serialno',
      'isqty',
      'uom',
      'kgs',
      'isamt',
      'disc',
      'ext',
      'itemstatus',
      'cost',
      'markup',
      'rebate',
      'gprofit',
      'itemdesc',
      'wh',
      'whname',
      'ref',
      'loc',
      'expiry',
      'rem',
      'itemname',
      'stock_projectname',
      'noprint',
      'barcode'
    ];

    switch ($systemtype) {
      case 'REALESTATE':
        $project = 24;
        $phasename = 25;
        $housemodel = 26;
        $blk = 27;
        $lot = 28;
        $amenityname = 29;
        $subamenityname = 30;
        array_push($column, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        array_push($sortcolumn, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }

    switch ($companyid) {
      case 40: //cdo
        $action = 0;
        $itemdesc = 1;
        $isqty = 2;
        $uom = 3;
        $serial = 4;
        $color = 5;
        $pnpcsr = 6;
        $kgs = 7;
        $isamt = 8;
        $disc = 9;
        $ext = 10;
        $itemstatus = 11;
        $cost = 12;
        $markup = 13;
        $rebate = 14;
        $gprofit = 15;
        $wh = 16;
        $whname = 17;
        $ref = 18;
        $loc = 19;
        $expiry = 20;
        $rem = 21;
        $itemname = 22;
        $stock_projectname = 23;
        $noprint = 24;
        $barcode = 25;

        $column = ['action', 'itemdescription',  'isqty', 'uom', 'serialno', 'color', 'pnp', 'kgs', 'isamt', 'disc', 'ext', 'itemstatus', 'cost', 'markup', 'rebate', 'gprofit', 'wh', 'whname', 'ref', 'loc', 'expiry', 'rem', 'itemname', 'stock_projectname', 'noprint', 'barcode'];
        $sortcolumn = ['action', 'itemdescription',  'isqty', 'uom', 'serialno', 'color', 'pnp', 'kgs', 'isamt', 'disc', 'ext', 'itemstatus', 'cost', 'markup', 'rebate', 'gprofit', 'wh', 'whname', 'ref', 'loc', 'expiry', 'rem', 'itemname', 'stock_projectname', 'noprint', 'barcode'];
        break;
      case 60: //transpower

        $action = 0;
        $barcode = 1;
        $isqty = 2;
        $uom = 3;
        $itemname = 4;
        $rem = 5;
        $isamt = 6;
        $disc = 7;
        $agentamt = 8;
        $ext = 9;
        $startwire = 10;
        $endwire = 11;
        $cost = 12;
        $markup = 13;
        $wh = 14;
        $whname = 15;
        $ref = 16;

        $column = ['action', 'barcode', 'isqty',  'uom', 'itemname', 'rem',  'isamt',  'disc', 'agentamt', 'ext', 'startwire', 'endwire', 'cost', 'markup', 'wh', 'whname', 'ref'];
        $sortcolumn = ['action', 'barcode',  'isqty', 'uom', 'itemname', 'rem',  'isamt',  'disc', 'agentamt', 'ext', 'startwire', 'endwire', 'cost', 'markup', 'wh', 'whname', 'ref'];
        break;
      case 59:
        $column = ['action', 'barcode', 'isqty',  'uom', 'itemname', 'rem',  'isamt',  'disc', 'ext', 'cost', 'markup', 'wh', 'whname', 'ref'];
        $sortcolumn = ['action', 'barcode',  'isqty', 'uom', 'itemname', 'rem',  'isamt',  'disc', 'ext', 'cost', 'markup', 'wh', 'whname', 'ref'];
        foreach ($column as $key => $value) {
          $$value = $key;
        }
        break;
    }

    switch ($companyid) {
      case 10: //afti
        if ($makecr != 0) {
          array_push($headgridbtns, 'makecv');
        }
        break;
      case 43: //mighty
        if ($trip_approve) array_push($headgridbtns, 'tripapproved');
        if ($trip_disapprove) array_push($headgridbtns, 'tripdisapproved');

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
      case 15: //nathina
      case 17: //unihome
      case 20: //proline
      case 28: //xcomp
      case 39: //CBBSI
        if ($release) {
          $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'sjrelease', 'access' => 'release'], 'label' => 'RELEASE'];
        }
        break;
      case 19: //housegem
        $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'viewotherinfo', 'access' => 'view'], 'label' => 'Delivery Details'];
        $tab['multigrid2'] = ['action' => 'tableentry', 'lookupclass' => 'viewsostockinfo', 'label' => 'Truck Scale'];
        break;
      case 43: //mighty
        if ($trip_tab) $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'tripdetails', 'access' => 'trip'], 'label' => 'TRIP'];
        if ($arrived_tab) $tab['customform2'] = ['event' => ['action' => 'customform', 'lookupclass' => 'tripdetails2', 'access' => 'dispatched'], 'label' => 'DISPATCHED'];
        break;
      case 22: //eipi
        $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'tabheadinfo', 'label' => 'ADD FEES', 'checkchanges' => 'tableentry'];
        break;
      case 29: //sbc
        $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'sbcremarks', 'label' => 'SBC REMARKS', 'checkchanges' => 'tableentry'];
        break;
      case 37: // mega crystal
        $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'sjrelease', 'access' => 'release'], 'label' => 'UPDATE INFO'];
        break;
    }

    if ($this->companysetup->getserial($config['params'])) {
      if ($companyid == 28) { //xcomp
        $stockbuttons = ['save', 'delete', 'showbalance'];
      } else {
        $stockbuttons = ['save', 'delete', 'serialout', 'showbalance'];
      }
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

    switch ($companyid) {
      case 10: //afti
        array_push($stockbuttons, 'iteminfo');
        break;
      case 59: //ROOSEVELT
        break;
      default: //main
        array_push($stockbuttons, 'stockinfo');
        break;
      
    }
    
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][$action]['style'] = 'text-align: left; width: 150px;whiteSpace: normal;min-width:150px';
    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Selling Kgs';
    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$markup]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
    }


    switch ($config['params']['companyid']) {
      case 1: //vitaline
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'coldel';
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'input';
        $obj[0]['inventory']['columns'][$itemd]['label'] = 'Item Description';
        $obj[0]['inventory']['columns'][$itemd]['readonly'] = false;

        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$loc]['label'] = 'Lot/Serial#';
        $obj[0]['inventory']['columns'][$expiry]['label'] = 'Expiry/Mfr Date';
        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        break;
      case 19: //housegem
        $obj[0]['inventory']['columns'][$rem]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:300px;max-width:450px;';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'input';

        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'coldel';
        break;
      case 21: // kinggeorge
        $obj[0]['inventory']['columns'][$noprint]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'coldel';
        break;
      case 10: //afti
      case 12: //afti usd
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
        $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'lookup';
        $obj[0]['inventory']['columns'][$serial]['lookupclass'] = 'lookupserialout';
        $obj[0]['inventory']['columns'][$serial]['action'] = 'lookupserialout';
        $obj[0]['inventory']['columns'][$serial]['readonly'] = true;
        $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
        $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
        $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'coldel';
        break;
      case 40: //cdo
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$color]['type'] = 'input';
        $obj[0]['inventory']['columns'][$color]['readonly'] = true;
        $obj[0]['inventory']['columns'][$color]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        //$obj[0]['inventory']['columns'][$serial]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'lookup';
        $obj[0]['inventory']['columns'][$serial]['lookupclass'] = 'lookupserialout';
        $obj[0]['inventory']['columns'][$serial]['action'] = 'lookupserialout';
        $obj[0]['inventory']['columns'][$serial]['readonly'] = true;
        $obj[0]['inventory']['columns'][$serial]['label'] = 'Engine/Chassis#';
        $obj[0]['inventory']['columns'][$serial]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:250px;max-width:2350px;';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$pnpcsr]['readonly'] = true;
        $obj[0]['inventory']['columns'][$pnpcsr]['label'] = 'PNP/CSR#';
        $obj[0]['inventory']['columns'][$pnpcsr]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:250px;max-width:2350px;';

        $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'coldel';
        break;
      case 24: //goodfound
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$loc]['label'] = 'Batch No';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;

        if ($viewfieldsforgate2users == '0') {
          $obj[0]['inventory']['columns'][$isamt]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$markup]['type'] = 'coldel';
        }
        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'coldel';
        break;
      case 60: //transpower
        // $obj[0]['inventory']['columns'][$barcode]['type'] = 'input';
        // $obj[0]['inventory']['columns'][$barcode]['class'] = 'csbarcode sbccsenablealways';
        // $obj[0]['inventory']['columns'][$barcode]['readonly'] = true;
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$barcode]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
        $obj[0]['inventory']['columns'][$disc]['style'] = 'text-align: left; width: 180px;whiteSpace: normal;min-width:180px;max-width:220px;';

        $obj[0]['inventory']['columns'][$itemname]['type'] = 'label';
        $obj[0]['inventory']['columns'][$itemname]['label'] = 'Itemname';
        $obj[0]['inventory']['columns'][$wh]['label'] = 'Warehouse Code';
        $obj[0]['inventory']['columns'][$cost]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$rem]['style'] = 'text-align: left; width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'input';
        $obj[0]['inventory']['columns'][$rem]['label'] = 'Notes';

        $obj[0][$this->gridname]['descriptionrow'] = [];
        break;
      case 59: //roosevelt

        $obj[0]['inventory']['columns'][$barcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$barcode]['style'] = 'text-align: left; width:125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemname]['type'] = 'label';
        $obj[0]['inventory']['columns'][$itemname]['label'] = 'Itemname';
        $obj[0]['inventory']['columns'][$uom]['style'] = 'text-align:left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';

        $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';

        $obj[0]['inventory']['columns'][$rem]['style'] = 'text-align: left;width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'input';
        $obj[0]['inventory']['columns'][$rem]['label'] = 'Notes';

        $obj[0]['inventory']['descriptionrow'] = [];
        $this->modulename = 'SALES INVOICE';
        break;
      default:
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$itemstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$gprofit]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemd]['type'] = 'coldel';

        if (!$isexpiry) {
          switch ($companyid) {
            case 28: //xcomp
              break;
            case 50: //unitech
              $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
              // $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
              break;
            default:
              $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
              break;
          }
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
        }
        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
        break;
    }

    if (!$access['changeamt']) {
      // 3 - isamt
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      // 4 - disc
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    if ($systemtype == 'REALESTATE') {
      $obj[0][$this->gridname]['columns'][$blk]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$lot]['readonly'] = true;
    }


    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $isserial = $this->companysetup->getserial($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    if ($isserial) {
      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingsq'];
      } else {
        $tbuttons = ['poserial', 'pendingso', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
      }
    } elseif ($ispallet) {
      $tbuttons = ['poserial', 'additem', 'saveitem', 'deleteallitem'];
    } else {
      $tbuttons = [];
      switch ($companyid) {
        case 10:
        case 12: //afti, afti usd
          array_push($tbuttons, 'additem', 'saveitem', 'deleteallitem', 'pendingsq');
          break;
        case 60: //transpower
          array_push($tbuttons, 'pendingpo', 'additem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingso');
          break;
        default:
          array_push($tbuttons, 'additem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingso');
          break;
      }
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($isserial) {
      if ($companyid == 10 || $companyid == 12) {
        $obj[4]['addedparams'] = ['yourref'];
      } else {
        $obj[0]['label'] = 'SO Serial';
        $obj[0]['lookupclass'] = 'soserial';
        $obj[0]['action'] = 'soserial';
      }
    } elseif ($ispallet) {
      $obj[0]['label'] = 'SO';
      $obj[0]['lookupclass'] = 'sopallet';
      $obj[0]['action'] = 'sopallet';
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $inv = $this->companysetup->isinvonly($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['docno', 'client', 'clientname'];

    if ($companyid != 10 && $companyid != 12) { //not afti & not afti usd
      array_push($fields, 'address');
    }

    switch ($companyid) {
      case 10: //afti
        array_push($fields, 'dbranchname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer10');
        data_set($col1, 'dbranchname.required', true);
        data_set($col1, 'clientname.type', 'textarea');
        break;

      case 14: //majesty
        array_push($fields, 'dprojectname', 'salestype');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        break;

      case 15: //nathina
        array_push($fields, 'shipto');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'shipto.type', 'ctextarea');
        break;

      case 16: //ati
        array_push($fields, ['sadesc', 'podesc']);
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'client.required', false);
        data_set($col1, 'sadesc.type', 'input');
        data_set($col1, 'podesc.type', 'input');
        break;

      case 21: //kinggeorge
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'client.required', false);
        break;

      case 19: //housegem
        array_push($fields, 'dprojectname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'client.required', false);
        data_set($col1, 'address.type', 'lookup');
        data_set($col1, 'address.lookupclass', 'lookupaddress');
        data_set($col1, 'address.action', 'lookupaddress');
        data_set($col1, 'address.class', 'sbccsreadonly');
        data_set($col1, 'address.addedparams', ['client']);
        break;

      case 24: // goodfound
        array_push($fields, 'dprojectname', ['hauler', 'driver'], ['weightin', 'weightintime']);
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'client.required', false);
        data_set($col1, 'driver.label', 'Driver');
        break;

      case 29: //sbc main
        array_push($fields, 'dprojectname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        break;

      default:
        if ($systemtype == 'REALESTATE') {
          array_push($fields, 'dprojectname', 'phase');
          $col1 = $this->fieldClass->create($fields);
          data_set($col1, 'client.lookupclass', 'customer');
          data_set($col1, 'client.required', false);
          data_set($col1, 'dprojectname.lookupclass', 'project');
          data_set($col1, 'phase.addedparams', ['projectid']);
        } else {
          array_push($fields, 'dprojectname');
          $col1 = $this->fieldClass->create($fields);
          data_set($col1, 'client.lookupclass', 'customer');
        }

        break;
    }

    data_set($col1, 'docno.label', 'Transaction#');
    if ($inv) {
      $fields = [['dateid', 'terms'], 'due', 'dwhname'];
    } else {
      $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname'];
    }

    // COL2

    switch ($companyid) {
      case 10: //afti
        $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname', 'ddeptname'];
        break;
      case 11: //summit
        $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname', 'ms_freight'];
        break;
      case 15: //nathina
        $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname', 'dprojectname', 'mlcp_freight', 'ms_freight'];
        break;
      case 19: //hosegem
        $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname', 'deldate', 'crref'];
        break;
      case 24: //goofound
        array_push($fields, 'statname', ['plateno', 'licenseno'], ['weightout', 'weightouttime']);
        break;
      case 22: //eipi
        $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname', 'shipto'];
        break;
      case 32: //3m
        $fields = [['dateid', 'terms'], 'due', 'salestype', 'dacnoname', 'dwhname'];
        break;
      case 52: //technolab
        $fields = ['invoiceno', ['dateid', 'terms'], 'due', 'dacnoname', 'dwhname'];
        break;
    }

    if ($systemtype == 'REALESTATE') {
      array_push($fields, 'housemodel', 'amenityname');
    }

    if ($companyid == 40) { //cdo
      array_push($fields, 'interestrate');
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AR Account');
    data_set($col2, 'dacnoname.lookupclass', 'AR');
    switch ($companyid) {
      case 10: //afti
        data_set($col2, 'ddeptname.label', 'Department');
        data_set($col2, 'ddeptname.required', true);
        break;
      case 15: //nathina
        data_set($col2, 'ms_freight.label', 'Other Charges');
        break;
      case 19: //housegem
        data_set($col2, 'crref.label', 'Request Order No.');
        break;
      case 22: //eipi
        data_set($col2, 'shipto.label', 'Delivered To');
        break;
      case 24: //goodfound
        data_set($col2, 'statname.required', false);
        break;
      case 32: //3m
        data_set($col2, 'salestype.lookupclass', 'sjtype');
        break;
      case 52: //technolab
        data_set($col2, 'invoiceno.label', 'Invoice No.');
        break;
    }

    if ($systemtype == 'REALESTATE') {
      data_set($col2, 'housemodel.addedparams', ['projectid']);
    }

    data_set($col2, 'statname.label', 'Type');
    data_set($col2, 'statname.lookupclass', 'lookup_sjtype');

    //col3

    if ($inv) {
      $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dagentname'];
    } else {
      $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dagentname'];
    }

    switch ($companyid) {
      case 10: //afti
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], ['dvattype', 'taxdef'], 'dagentname', 'dewt'];
        break;
      case 24: //goodfound
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dagentname', ['batchno', 'cwano'], 'cwatime', ['kilo', 'assignedlane']];
        break;
      case 22: //eipi
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dagentname', 'dewt'];
        break;
      case 52: //technolab
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'agentname'];
        break;
    }
    if ($systemtype == 'REALESTATE') {
      array_push($fields, ['blklot', 'lot'], 'subamenityname');
    }

    switch ($companyid) {
      case 19:
        array_push($fields, 'shipto');
        break;
      case 43: //mighty
        array_push($fields, 'istrip');
        break;
      case 49: //hotmix
        array_push($fields, 'commamt', 'commvat', 'netcomm');
        break;
      case 40: //cdo
        array_push($fields, 'downpayment');
        break;
      case 52:
      case 41: //technolab
        array_push($fields, 'lblpaid');
        break;
      case 59: //roosevelt
        array_push($fields, 'bpo', 'ctnsno', 'isreported');
        break;
      case 60: //transpower
        array_push($fields, 'dewt');
        break;
    }


    $col3 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        data_set($col3, 'yourref.label', 'Customer PO');
        break;
      case 19:
        data_set($col3, 'shipto.label', 'Deliver To');
        break;
      case 24: //goodfound
        data_set($col3, 'yourref.label', 'DR No.#');
        data_set($col3, 'ourref.label', 'SI No.#');
        break;
      case 22: //EIPI
        data_set($col3, 'ourref.label', 'Charge SI #');
        data_set($col3, 'yourref.label', 'Reference No.');
        break;
      case 40: //cdo
        data_set($col3, 'yourref.label', 'SI#');
        break;
      case 59: //roosevelt 
        data_set($col3, 'ourref.label', 'SI#');
        break;
      default:
        data_set($col3, 'yourref.label', 'PO#');
        break;
    }

    if ($systemtype == 'REALESTATE') {
      data_set($col3, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
      data_set($col3, 'subamenityname.addedparams', ['amenityid']);
    }

    $fields = ['rem', 'creditinfo'];
    switch ($companyid) {
      case 10: //afti
        $fields = ['rem', 'creditinfo', ['lbltotal', 'ext'], ['lbltaxes', 'taxesandcharge'], ['lblgrandtotal', 'totalcash']];
        break;
      case 19: //housegem
        $fields = ['rem', 'creditinfo', ['lblgrossprofit', 'grossprofit'], 'forwtinput', 'posted'];
        break;
      case 43: //mighty
        array_push($fields, 'lblapproved');
        break;
    }

    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    if ($this->companysetup->linearapproval($config['params'])) {
      array_push($fields, 'forapproval', 'doneapproved', 'lblapproved');
    }


    $col4 = $this->fieldClass->create($fields);

    if ($companyid == 10) { //afti
      data_set($col4, 'ext.class', 'sbccsreadonly');
      data_set($col4, 'ext.label', '');
      data_set($col4, 'taxesandcharge.label', '');
      data_set($col4, 'taxesandcharge.class', 'sbccsreadonly');
      data_set($col4, 'totalcash.label', '');
    }
    if ($companyid == 43 || $this->companysetup->linearapproval($config['params'])) { //mighty
      data_set($col4, 'lblapproved.type', 'label');
      data_set($col4, 'lblapproved.label', 'APPROVED!');
      data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');
    }

    data_set($col4, 'grossprofit.label', '');
    data_set($col4, 'posted.label', 'Done Loading');


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
    $data[0]['dagentname'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['dacnoname'] = '';
    $data[0]['agent'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['agentname'] = '';

    switch ($params['companyid']) {
      case 36: //rozlab
        $data[0]['tax'] = 12;
        $data[0]['vattype'] = 'VATABLE';
        break;
      case 22:
        $data[0]['vattype'] = 'NON-VATABLE';
        break;
      default:
        $data[0]['vattype'] = 'NON-VATABLE';
        break;
    }

    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    if ($params['companyid'] == 24) { //goodfound
      $data[0]['wh'] = 'WH0000000000002';
    } else {
      $data[0]['wh'] = $this->companysetup->getwh($params);
    }
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['taxdef'] = '0';
    $data[0]['dept'] = '';
    $data[0]['sotrno'] = 0;
    $data[0]['ms_freight'] = '0.00';
    $data[0]['mlcp_freight'] = '';
    $data[0]['shipto'] = '';
    $data[0]['salestype'] = '';
    $data[0]['statid'] = '0';
    $data[0]['statname'] = '';
    $data[0]['deldate'] = date('Y-m-d');
    $data[0]['crref'] = '';

    $data[0]['hauler'] = '';
    $data[0]['driver'] = '';
    $data[0]['plateno'] = '';
    $data[0]['licenseno'] = '';

    $data[0]['batchno'] = '';
    $data[0]['cwano'] = '';

    $data[0]['weightin'] = 0.00;
    $data[0]['weightintime'] = '';

    $data[0]['weightout'] = 0.00;
    $data[0]['weightouttime'] = '';

    $data[0]['cwatime'] = '';
    $data[0]['kilo'] = '0.00';
    $data[0]['assignedlane'] = '';

    $data[0]['sano'] = '0';
    $data[0]['pono'] = '0';
    $data[0]['sadesc'] = '';
    $data[0]['podesc'] = '';

    if ($params['companyid'] == 60) { //transpower
      $data[0]['ewt'] = '';
      $data[0]['dewt'] = '';
      $data[0]['ewtrate'] = 0;
    }
    return $data;
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;

    if ($params['companyid'] == 21) { //kinggeorge
      $newdate = date_create($this->othersClass->getCurrentDate());
      $sjadddate = $this->coreFunctions->getfieldvalue("profile", "yr",  "doc=? and psection=?", ['SYS', 'SJADDDATE']);
      $sjadddate = is_numeric($sjadddate) ? intval($sjadddate) : 1;

      if (date_format($newdate, "D") == 'Sat') {
        $totalDays = 1 + $sjadddate;
      } else {
        $totalDays = $sjadddate;
      }

      date_add($newdate, date_interval_create_from_date_string($totalDays . " days"));
      $data[0]['dateid'] = date_format($newdate, "Y-m-d");
      $data[0]['due'] = date_format($newdate, "Y-m-d");
    } else {
      $data[0]['dateid'] = $this->othersClass->getCurrentDate();
      $data[0]['due'] = $this->othersClass->getCurrentDate();
    }

    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;
    $data[0]['dagentname'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['dacnoname'] = '';
    $data[0]['agent'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['agentname'] = '';

    switch ($params['companyid']) {
      case 36:
      case 49:
      case 59: //rozlab / hotmix//roosevelt
        $data[0]['tax'] = 12;
        $data[0]['vattype'] = 'VATABLE';
        $data[0]['isreported'] = '0';
        break;
      case 60: //transpower
        $data[0]['tax'] = 12;
        $data[0]['vattype'] = 'VATABLE';
        break;
      default:
        $data[0]['tax'] = 0;
        $data[0]['vattype'] = 'NON-VATABLE';
        break;
    }

    if ($params['companyid'] == 49) { //hotmix
      $this->defaultContra = "CA1";
    }

    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    if ($params['companyid'] == 24) { //goodfound
      $data[0]['wh'] = 'WH0000000000002';
    } else {
      $data[0]['wh'] = $this->companysetup->getwh($params);
    }
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['taxdef'] = '0';
    $data[0]['dept'] = '';
    $data[0]['sotrno'] = 0;
    $data[0]['ms_freight'] = '0.00';
    $data[0]['mlcp_freight'] = '';
    $data[0]['shipto'] = '';
    $data[0]['salestype'] = '';
    $data[0]['statid'] = '0';
    $data[0]['statname'] = '';
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['crref'] = '';

    $data[0]['hauler'] = '';
    $data[0]['driver'] = '';
    $data[0]['plateno'] = '';
    $data[0]['licenseno'] = '';

    $data[0]['batchno'] = '';
    $data[0]['cwano'] = '';

    $data[0]['weightin'] = 0.00;
    $data[0]['weightintime'] = '';

    $data[0]['weightout'] = 0.00;
    $data[0]['weightouttime'] = '';

    $data[0]['cwatime'] = '';
    $data[0]['kilo'] = '0.00';
    $data[0]['assignedlane'] = '';

    $data[0]['sano'] = '0';
    $data[0]['pono'] = '0';
    $data[0]['sadesc'] = '';
    $data[0]['podesc'] = '';
    $data[0]['istrip'] = '0';
    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;

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

    $data[0]['interestrate'] = 0;
    $data[0]['downpayment'] = 0.00;

    $data[0]['commamt'] = 0.00;
    $data[0]['commvat'] = 0.00;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $isapproved = $this->othersClass->isapproved($config['params']['trno'], "hcntnuminfo");
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value 
        from " . $this->tablenum . " 
        where doc=? and center=? and left(bref,3) <> 'SJS'
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
    $hideheadergridbtns = [];

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
         ifnull(agent.client,'') as agent,
         ifnull(agent.clientname,'') as agentname,'' as dagentname,
         head.tax,
         head.vattype,
         '' as dvattype,
         warehouse.client as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         date(head.deldate) as deldate,
          head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         head.crref,
         client.groupid,head.creditinfo,ifnull(project.code,'') as projectcode,
         head.ms_freight, num.statid as numstatid,
         head.billid, head.shipid,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, head.branch,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname, head.taxdef, head.billcontactid, head.shipcontactid,head.sotrno,
         head.mlcp_freight,head.salestype,
         head.statid, ifnull(stat.status,'') as statname,
         head.driver, ifnull(hinfo.hauler,'') as hauler, ifnull(hinfo.plateno,'') as plateno, ifnull(hinfo.licenseno,'') as licenseno, ifnull(hinfo.batchno,'') as batchno, 
         ifnull(hinfo.cwano,'') as cwano, ifnull(hinfo.commamt,0) as commamt, ifnull(hinfo.commvat,0) as commvat, ifnull(hinfo.commamt,0)-ifnull(hinfo.commvat,0) as netcomm,

         ifnull(hinfo.cwatime,'') as cwatime, 
         ifnull(hinfo.weightin,'') as weightin, 
         ifnull(hinfo.weightintime,'') as weightintime, 
         ifnull(hinfo.weightout,'') as weightout, 
         ifnull(hinfo.weightouttime,'') as weightouttime, 
         ifnull(hinfo.kilo,0) as kilo,
         ifnull(hinfo.assignedlane,'') as assignedlane,
         head.sano, ifnull(sa.sano,'') as sadesc,
         head.pono,ifnull(po.sano,'') as podesc,
         cast(ifnull(head.istrip,0) as char) as istrip,
         head.ewt,head.ewtrate,'' as dewt,
         hinfo.interestrate,hinfo.downpayment,  head.phaseid, ps.code as phase,  head.modelid, hm.model as housemodel, head.blklotid, 
           bl.blk as blklot,  bl.lot, amen.line as amenityid, amen.description as amenityname, 
           subamen.line as subamenityid, subamen.description as subamenityname, head.isreported,
           head.bpo, head.ctnsno, head.invoiceno
         ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join trxstatus as stat on stat.line = head.statid
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono 

         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid

        where head.trno = ? and num.doc=? and num.center = ? and left(num.bref,3) <> 'SJS'
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join trxstatus as stat on stat.line = head.statid
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono

         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid
        where head.trno = ? and num.doc=? and num.center=? and left(num.bref,3) <> 'SJS' ";

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

      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      $receivedby = $this->coreFunctions->datareader("select receivedby as value from cntnum  where trno=?", [$trno]);

      $lblreceived_stat = $receivedby == "" ? true : false;
      $hideobj = ['lblreceived' => $lblreceived_stat];

      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        if ($head[0]->tax == '12') {
          $sqry = "select sum(ext) as value from (select ext from $this->stock as stock 
                    where stock.trno =? union all select ext from $this->hstock as stock where stock.trno = ?) as a";
          $ext = round($this->coreFunctions->datareader($sqry, [$head[0]->trno, $head[0]->trno]), 2);


          $tax = $charges = 0;
          $charges = $ext * .12;
          $tax = round($ext - $charges, 2);
          $amount = $ext + $charges;
          $taxdef = $head[0]->taxdef;

          if ($taxdef != 0) {
            $charges = $taxdef;
            $amount = $ext + $charges;
          }

          $head[0]->ext = number_format($ext, $this->companysetup->getdecimal('default', $config['params']));
          $head[0]->taxesandcharge = number_format($charges, $this->companysetup->getdecimal('default', $config['params']));
          $head[0]->totalcash = number_format($amount, 2);
        } else {
          $sqry = "select sum(ext) as value from (select ext from $this->stock as stock 
                    where stock.trno =? union all select ext from $this->hstock as stock where stock.trno = ?) as a ";
          $ext = round($this->coreFunctions->datareader($sqry, [$head[0]->trno, $head[0]->trno]), 2);

          $tax = $charges = 0;
          $charges = 0;
          $tax = 0;
          $amount = $ext + $charges;
          $taxdef = $head[0]->taxdef;

          if ($taxdef != 0) {
            $charges = $taxdef;
            $amount = $ext + $charges;
          }

          $head[0]->ext = number_format($ext, $this->companysetup->getdecimal('price', $config['params']));
          $head[0]->taxesandcharge = number_format($charges, $this->companysetup->getdecimal('price', $config['params']));
          $head[0]->totalcash = number_format($amount, 2);
        }
      }

      $hideheadergridbtns = [];
      if ($companyid == 43) { //mighty
        $hideobj = ['lblapproved' => !$isapproved];
        $hideheadergridbtns = ['tripapproved' => $isapproved, 'tripdisapproved' => !$isapproved];
      }
      if ($companyid == 19) { //housegem
        $gpqry = "select sum(ext) as value from (select (case when stock.Amt>0 then ((stock.amt-stock.cost)/" . $head[0]->forex . ") else 0 end) * stock.iss as ext from $this->stock as stock where stock.trno =? 
        union all select (case when stock.Amt>0 then ((stock.amt-stock.cost)/" . $head[0]->forex . ") else 0 end) * stock.iss as ext from $this->hstock as stock where stock.trno = ?) as a ";
        $gpext = round($this->coreFunctions->datareader($gpqry, [$head[0]->trno, $head[0]->trno]), 2);
        $head[0]->grossprofit = number_format($gpext, $this->companysetup->getdecimal('price', $config['params']));

        $hideobj['posted'] = true;
        $hideobj['forwtinput'] = true;

        if ($this->companysetup->getcompanyalias($config['params']) == 'HOUSEGEM') {
          if (!$isposted) {

            $hideobj['forwtinput'] = false;
            switch ($head[0]->numstatid) {
              case 39:
                $hideobj['forwtinput'] = true;
                $hideobj['posted'] = true;
                break;
              case 74:
                $hideobj['forwtinput'] = true;
                $hideobj['posted'] = false;
                break;
            }
          }
        }
      }

      if ($companyid == 52 || $companyid == 41) { //technolab and labsol manila
        $lvlpaid = true;
        if ($isposted) {
          $bal = $this->coreFunctions->datareader("select sum(bal) as value from arledger  where trno=?", [$trno]);
          if (!empty($bal)) {
            $lvlpaid = $bal == 0 ? false : true;
          }
        }
        $hideobj = ['lblpaid' => $lvlpaid];
      }

      if ($companyid == 59) { //roosevelt
        if ($head[0]->isreported) {
          $head[0]->isreported = '1';
        } else {
          $head[0]->isreported = '0';
        }
      }

      // fortesting
      if ($this->companysetup->linearapproval($config['params'])) {
        switch ($head[0]->numstatid) {
          case 10: // forapproval
            $hideobj = ['forapproval' => true, 'doneapproved' => false, 'lblapproved' => true];
            break;
          case 36: // approved
            $hideobj = ['forapproval' => true, 'doneapproved' => true, 'lblapproved' => false];
            break;
          case 0: // draft
            $hideobj = ['forapproval' => false, 'doneapproved' => true, 'lblapproved' => true];
            break;
        }
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
    $info = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }
    if ($companyid == 24) { //goodfound
      array_push($this->fields, 'driver');
    }

    if ($companyid == 59) { //roosevelt
      array_push($this->fields, 'isreported');
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if
      }
    }
    if ($companyid == 19) { //housegem
      if ($data['terms'] == '') {
        $data['due'] = $data['deldate'];
      } else {
        $data['due'] = $this->othersClass->computeterms($data['deldate'], $data['due'], $data['terms']);
      }
    } else {
      if ($data['terms'] == '') {
        $data['due'] = $data['dateid'];
      } else {
        $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($companyid == 24) { //goodfound
      $info = [];
      $info['trno'] = $head['trno'];
      $info['plateno'] = $head['plateno'];
      $info['licenseno'] = $head['licenseno'];
      $info['hauler'] = $head['hauler'];

      $info['batchno'] = $head['batchno'];
      $info['cwano'] = $head['cwano'];

      $info['cwatime'] = $head['cwatime'];
      $info['weightin'] = $head['weightin'];
      $info['weightintime'] = $head['weightintime'];
      $info['weightout'] = $head['weightout'];
      $info['weightouttime'] = $head['weightouttime'];
      $info['kilo'] = $head['kilo'];
      $info['assignedlane'] = $head['assignedlane'];
    }
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->othersClass->getcreditinfo($config, $this->head);
      $this->recomputestock($head, $config);

      switch ($companyid) {
        case 24: //goodfound
          $trno = $head['trno'];
          $info['trno'] = $head['trno'];
          $info['plateno'] = $head['plateno'];
          $info['licenseno'] = $head['licenseno'];
          $info['hauler'] = $head['hauler'];
          $info['batchno'] = $head['batchno'];
          $info['cwano'] = $head['cwano'];
          $info['cwatime'] = $head['cwatime'];
          $info['weightin'] = $head['weightin'];
          $info['weightintime'] = $head['weightintime'];
          $info['weightout'] = $head['weightout'];
          $info['weightouttime'] = $head['weightouttime'];
          $info['assignedlane'] = $head['assignedlane'];

          $weightin = floatval($info['weightin']);
          $weightout = floatval($info['weightout']);
          $qry = "select sum(stock.iss) as value from lastock as stock
          left join item as i on i.itemid=stock.itemid
          where stock.trno =$trno
          and stock.iscomponent=0 and i.fg_isfinishedgood=1
          and i.body not in
          ('MAYON TYPE 1P','MAYON TYPE 1T SUPER','MAYON TYPE 1T PREMIUM','MAYON TYPE 1T BICOL','MAYON PPC','MAYON GREEN')";

          $qty = floatval($this->coreFunctions->datareader($qry));

          $weightin = isset($weightin) ? $weightin : 0;
          $weightout = isset($weightout) ? $weightout : 0;

          if ($qty == 0 && $weightin == 0 && $weightout == 0) {
            $info['kilo'] = 0;
          } else {
            $info['kilo'] = ($weightout - $weightin) / $qty;
          }

          $this->coreFunctions->sbcupdate('cntnuminfo', $info, ['trno' => $head['trno']]);

          break;
        case 40: //cdo
          $info['trno'] = $head['trno'];
          $info['interestrate'] = $head['interestrate'];
          $info['downpayment'] = $head['downpayment'];
          $this->coreFunctions->sbcupdate('cntnuminfo', $info, ['trno' => $head['trno']]);
          break;
        case 49: //hotmix
          $info['trno'] = $head['trno'];
          $info['commvat'] = $head['commvat'];
          $info['commamt'] = $head['commamt'];

          $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
          if ($infotransexist == '') {
            $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          } else {
            $this->coreFunctions->sbcupdate('cntnuminfo', $info, ['trno' => $head['trno']]);
          }

          break;
      }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->othersClass->getcreditinfo($config, $this->head);

      switch ($companyid) {
        case 10: //afti
          $this->autocreatestock($config, $data, $head['sotrno']);
          break;
        case 24: //goodfound
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
        case 15: //nathina
        case 17: //unihome
        case 19: //housegem
        case 20: //proline
        case 28: //xcomp
        case 39: //CBBSI
          $info = [];
          $info['trno'] = $head['trno'];
          $info['plateno'] = $head['plateno'];
          $info['licenseno'] = $head['licenseno'];
          $info['hauler'] = $head['hauler'];
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
        case 40: //cdo
          $info = [];
          $info['trno'] = $head['trno'];
          $info['interestrate'] = $head['interestrate'];
          $info['downpayment'] = $head['downpayment'];
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
        case 43: //mighty
          $info = [];
          $info['trno'] = $head['trno'];
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
        case 49: //hotmix
          $info = [];
          $info['trno'] = $head['trno'];
          $info['commamt'] = $head['commamt'];
          $info['commvat'] = $head['commvat'];
          $this->coreFunctions->sbcinsert('cntnuminfo', $info);
          break;
        case 59: //roosevelt
          $this->coreFunctions->sbcupdate("client", ['lasttrans' => $data['dateid']], ['clientid' => $head['clientid']]);
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
    $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
    $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno], 'trno desc');
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
    $companyid = $config['params']['companyid'];
    if ($companyid == 21) { //Kinggeorge
      $this->logger->sbcwritelog($trno, $config, 'POST', 'Start Posting');
    }
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if (!$this->othersClass->checkserialout($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }

    if ($companyid == 60) { //transpower      
      $negativestock = $this->coreFunctions->datareader("select line as value from lastock where trno=? and isqty<0 limit 1", [$trno], '', true);
      if ($negativestock != 0) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Negative quantity not allowed.'];
      }

      // $islocked = $this->othersClass->islocked($config);
      // if(!$islocked){
      //   //for confirmation pa
      // }
      $stocks = $this->coreFunctions->datareader("select stock.line as value from " . $this->stock . " as stock left join item on item.itemid=stock.itemid where stock.trno=? and stock.amt<item.namt6", [$trno], '', true);
      if ($stocks != 0) {
        return ['status' => false, 'msg' => 'Posting failed, amount less than lowest net amount.'];
      }

      if (!$this->othersClass->checktotalext($trno)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Zero Amount, Not Allowed to Post.'];
      }
    }

    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1']);
      if ($companyid == 10) { //afti
        $checkacct = $this->othersClass->checkcoaacct(['AR1', 'TX2']);
      }

      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      $stock = $this->openstock($trno, $config);
      $checkcosting = $this->othersClass->checkcosting($stock);
      if ($checkcosting != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
      }

      $override = $this->othersClass->checkAccess($config['params']['user'], 1729);

      $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
      $islimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);

      if (floatval($islimit) == 0) {
        switch ($companyid) {
          case 19: //housegem
          case 10: //afti
            break;
          case 21: //kinggeorge
            if ($override == '0') {
              $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
              $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
              $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);
              $crlimitamt = $this->coreFunctions->getfieldvalue("client", "crlimit", "client=?", [$client], '', true);

              if ($cstatus <> 'ACTIVE') {
                $this->logger->sbcwritelog($trno, $config, 'POST', 'Customer Status is not Active.');
                return ['status' => false, 'msg' => 'Posting failed. The customer`s status is not active.'];
              }

              if ($crlimitamt != 0) {
                if (floatval($crline) <= 0) {
                  $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit.');
                  return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
                }
              }
            }
            break;
          default:
            if ($override == '0') {
              $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
              $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
              $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
              $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);

              if ($cstatus <> 'ACTIVE') {
                $this->logger->sbcwritelog($trno, $config, 'POST', 'Customer Status is not Active.');
                return ['status' => false, 'msg' => 'Posting failed. The customer`s status is not active.'];
              }

              //if (floatval($overdue) > 0) {
              if (floatval($crline) < floatval($totalso)) {
                $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit');
                return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
              }
              //}
            }
            break;
        }
      }

      if ($companyid == 19) { //housegem
        if ($this->companysetup->getcompanyalias($config['params']) == 'HOUSEGEM') {
          $statid = $this->coreFunctions->datareader('select statid as value from ' . $this->tablenum . ' where trno=?', [$trno]);
          if ($statid == '') $statid = 0;
          if ($statid != 39) {
            return ['status' => false, 'msg' => 'Posting failed. The warehouse is not yet finished.'];
          }
        }
      }

      if ($companyid == 24) { //goodfound
        $statid = $this->coreFunctions->getfieldvalue($this->head, "statid", "trno=?", [$trno]);
        $pack = $this->coreFunctions->getfieldvalue('cntnuminfo', "packdate", "trno=?", [$trno]);
        $release = $this->coreFunctions->getfieldvalue('cntnuminfo', "releasedate", "trno=?", [$trno]);

        switch ($statid) {
          case 40: //cdo
            if (is_null($pack) == false && is_null($release) == false) {
              $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 42], ['trno' => $trno]);
            } else {
              return ['status' => false, 'msg' => 'Posting failed. Posting of transaction tagged for Pickup can be done in releasing module.'];
            }
            break;
          default:
            if (!empty($release)) {
              return ['status' => false, 'msg' => 'Posting failed. Please tag as released first.'];
            }
            if ($statid == 0) {
              return ['status' => false, 'msg' => 'Posting failed. Type is required.'];
            }

            break;
        }
      }

      if ($companyid == 21) { //Kinggeorge
        $this->logger->sbcwritelog($trno, $config, 'POST', 'Create Distribution');
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        $return = $this->othersClass->posttranstock($config);
        if ($return) {
          $ref = $this->coreFunctions->opentable("select distinct refx from glstock where trno =?", [$trno]);

          if (!empty($ref)) {
            foreach ($ref as $key => $value) {
              $sotrno = $this->coreFunctions->datareader("select sotrno as value from hqshead where trno=?", [$ref[$key]->refx]);
              $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hqsstock where trno=? and iss=(sjqa+voidqty)", [$ref[$key]->refx]);
              if ($status) {
                $this->coreFunctions->execqry("update transnum set statid=9 where trno=" . $sotrno);
              }
            }
          }
        }
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
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $itemname = 'item.itemname,';
    switch ($companyid) {
      case 28: //xcomp
        $itemname = "case when item.itemname like '%misc%' and stockinfo.itemdesc<>'' then stockinfo.itemdesc else item.itemname end as itemname,";
        break;
      case 29: //sbc
      case 61: //bytesized
        $itemname = "if(stockinfo.itemdesc<>'',stockinfo.itemdesc,item.itemname) as itemname,";
        break;
    }

    $markup_field = "stock.Amt";
    if ($companyid == 36) { //ROZLAB
      $markup_field = "stock.cost";
    }

    $serialfield = '';

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $qty_dec = 0;
        $serialfield = ",ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno";
        break;
      case 40: //cdo
        $serialfield = ",ifnull(group_concat(concat(rr.serial,'/',rr.chassis) separator '\\n\\r'),'') as serialno ";
        break;
      case 52: //technolab
      case 23: // labsol cebu
      case 41: //labsol manila
        $serialfield = ",stockinfo.itemdesc ";
        break;
      case 60: //transpower
        $serialfield = ",stock.agentamt, stock.startwire, stock.endwire, stock.porefx, stock.polinex ";
        break;
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
    $itemname
    stock.uom,
    FORMAT(uom.factor*stock.cost,6) as cost,
    stock.kgs,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as iss,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as isqty,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as qty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    stock.void,
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
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,
    round(case when (stock.Amt>0 and stock.iss>0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / ($markup_field * stock.Iss))/head.forex)*100) else 0 end,2) as markup,
    
    stock.rebate,
    round(case when stock.Amt>0 then ((stock.amt-stock.cost)/head.forex) else 0 end,2) as gprofit,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,stock.sgdrate,stock.itemstatus,

     stock.phaseid, ps.code as phasename,  stock.modelid, hm.model as housemodel,stock.blklotid, bl.blk, bl.lot,
     prj.code as project,
     amen.line as amenity, amen.description as amenityname,  subamen.line as subamenity, subamen.description as subamenityname,

    case when stock.noprint=0 then 'false' else 'true' end as noprint,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription
    " . $serialfield . ",ifnull(group_concat(concat('PNP#: ',rr.pnp,' / CSR#: ',rr.csr) separator '\\n\\r'),'') as pnp,stock.color";

    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    $leftjoin = '';
    $hleftjoin = '';
    $stockinfogroup = '';

    switch ($companyid) {
      case 28: //xcomp
      case 29: //sbc
      case 52: //technolab
      case 41: //labsol manila
      case 23: //labsol cebu
      case 61: //bytesized
        $leftjoin = 'left join stockinfo as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line';
        $hleftjoin = 'left join hstockinfo as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line';
        $stockinfogroup = 'stockinfo.itemdesc,';
        break;
      case 60: //transpower
        $stockinfogroup = 'stock.agentamt, stock.startwire, stock.endwire, stock.porefx, stock.polinex, ';
        break;
    }


    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    
    $leftjoin
    where stock.trno =?
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color,

    stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description ,  subamen.line , subamen.description ,item.namt6



    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join $this->hhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    
    $hleftjoin
    where stock.trno =? 
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color,
     stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description ,  subamen.line , subamen.description  ,item.namt6

    order by sortline, line";


    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    $leftjoin = '';
    $stockinfogroup = '';

    switch ($companyid) {
      case 28: //xcomp
      case 29: //sbc
      case 52: //technolab
      case 41: //labsol manila
      case 23: //labsol cebu
      case 61: //bytesized  
        $leftjoin = 'left join stockinfo as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line';
        $stockinfogroup = 'stockinfo.itemdesc,';
        break;
      case 60: //transpower
        $stockinfogroup = 'stock.agentamt, stock.startwire, stock.endwire, stock.porefx, stock.polinex, ';
        break;
    }

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line

     left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    
    $leftjoin
    where stock.trno = ? and stock.line = ? 
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color,
     stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description ,  subamen.line , subamen.description ,item.namt6
    ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        $return =  $this->additem('insert', $config);
        if ($return['status'] == true) {
          $this->othersClass->getcreditinfo($config, $this->head);
        }
        return $return;
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
      case 'getsosummary':
        if ($this->companysetup->getserial($config['params'])) {
          return $this->getsosummaryserial($config);
        } else {
          return $this->getsosummary($config);
        }
        break;
      case 'getsodetails':
        if ($this->companysetup->getserial($config['params'])) {
          return $this->getsodetailsserial($config);
        } else {
          return $this->getsodetails($config);
        }
        break;
      case 'getsqsummary':
        return $this->getsqsummary($config);
        break;
      case 'getsqdetails':
        return $this->getsqdetails($config);
        break;
      case 'refreshso':
        $data = $this->sqlquery->getpendingsodetailsperpallet($config);
        return ['status' => true, 'msg' => 'Refresh Data', 'data' => $data];
        break;
      case 'getserialout':
        return $this->getserialout($config);
        break;
      case 'getposummary':
        return $this->getposummary($config);
        break;
      case 'getpodetails':
        return $this->getpodetails($config);
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

  public function diagram_aftech($config)
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
     left join hqsstock as qtstock on qtstock.refx = s.trno and s.line = qtstock.linex
     left join hqshead as qthead on qthead.trno = qtstock.trno
     left join hsqhead as sohead on sohead.trno = qthead.sotrno
     left join glstock as glstock on glstock.refx = qthead.trno
     where glstock.trno = ?
     group by head.trno,head.docno,head.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    $a = 0;
    if (!empty($t)) {
      $startx = 550;

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

        // quotation
        $qry = "
            select head.docno,left(head.dateid,10) as dateid,
            CAST(concat('Total QS Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hqshead as head 
            left join hqsstock as s on s.trno = head.trno
            left join glstock as glstock on glstock.refx = head.trno
            where glstock.trno = ?
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


        // SO
        $qry = "
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from sqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          left join glstock as glstock on glstock.refx = qthead.trno
          where glstock.trno = ?
          group by head.docno,head.dateid
          union all
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hsqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          left join glstock as glstock on glstock.refx = qthead.trno
          where glstock.trno = ?
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
    left join hsqhead as sohead on sohead.trno = head.sotrno
    left join glstock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join glhead as sjhead on sjhead.trno = sjstock.trno
    left join arledger as ar on ar.trno = sjhead.trno
    where sjhead.trno = ? and sjhead.docno is not null
    group by sjhead.docno, sjhead.dateid, ar.bal, sjhead.trno
    union all 
    select sjhead.docno,
    date(sjhead.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(sum(sjstock.ext),2)) as CHAR) as rem, 
    sjhead.trno
    from hqshead as head
    left join hqsstock as stock on stock.trno = head.trno
    left join hsqhead as sohead on sohead.trno = head.sotrno
    left join lastock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join lahead as sjhead on sjhead.trno = sjstock.trno
    where sjhead.trno = ? and sjhead.docno is not null
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

  public function stockstatusposted($config)
  {
    $tablenum = $this->tablenum;
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'diagram':
        switch ($config['params']['companyid']) {
          case 10: //afti
            return $this->diagram_aftech($config);
            break;
          default:
            return $this->diagram($config);
            break;
        }
        break;
      case 'batchpostsj':
        return $this->batchpostsj($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'SJCR');
        break;
      case 'donetodo':
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'downloadexcel':
        return $this->downloadexcel($config);
        break;
      case 'posted':
        return $this->warehousedone($config);
        break;
      case 'tripapproved':
        return $this->tripapproved($config);
        break;
      case 'tripdisapproved':
        return $this->tripdisapproved($config);
        break;
      case 'forwtinput':
        return $this->forwtinput($config);
        break;
      case 'forapproval':
        return $this->othersClass->forapproval($config, $tablenum);
        break;
      case 'doneapproved':
        return $this->othersClass->approvedsetup($config, $tablenum);
        break;
      case 'uploadexcel':
        return $this->othersClass->uploadexcel($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function forwtinput($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 74], ['trno' => $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate($this->head, ['lockdate' => $this->othersClass->getCurrentTimeStamp(), 'lockuser' => $config['params']['user']], ['trno' => $config['params']['trno']]);
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'FOR WEIGHT INPUT');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  public function warehousedone($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    $qry = "select trno from stockinfo where trno=? and weight2=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Failed to tag done, please input actual weight of all items'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'WAREHOUSE DONE');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  private function downloadexcel($config)
  {
    $trno = $config['params']['trno'];
    $cntnum = $this->coreFunctions->opentable("select docno, ifnull(postdate,'') as postdate from cntnum where trno=?", [$trno]);
    if (empty($cntnum)) {
      return ['status' => false, 'msg' => 'Failed to download, invalid transaction', 'name' => 'dr', 'data' => []];
    }

    $data = $this->coreFunctions->opentable("select item.barcode as `itemcode`, s.uom, s.isqty as `qty`, s.disc, s.isamt as `cost`, s.kgs, s.sortline, s.line from lahead as h left join lastock as s on s.trno=h.trno left join item on item.itemid=s.itemid where h.trno=? 
                                            union all
                                            select item.barcode as `itemcode`, s.uom, s.isqty as `qty`, s.disc, s.isamt as `cost`, s.kgs, s.sortline, s.line from glhead as h left join glstock as s on s.trno=h.trno left join item on item.itemid=s.itemid where h.trno=? 
                                            order by sortline, line", [$trno, $trno]);

    $this->logger->sbcwritelog($trno, $config, 'EXPORT', 'DOWNLOAD EXCEL FILE');
    return ['status' => true, 'msg' => $cntnum[0]->docno . ' is ready to Download', 'name' => 'dr', 'data' => $data];
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

  private function batchpostsj($config)
  {
    $msg = '';
    try {
      $curdate = $this->othersClass->getCurrentDate();
      $sql = "select trno, docno from lahead where doc='SJ' and lockdate is not null and date(dateid)<=?";
      $data = $this->coreFunctions->opentable($sql, [$curdate]);
      foreach ($data as $key => $value) {
        $config['params']['trno'] = $value->trno;
        $result = $this->posttrans($config);
        if (!$result['status']) {
          $msg = $result['msg'];
          goto exithere;
        } else {
          $this->logger->sbcwritelog($value->trno, $config, 'BATCH POST', $value->docno);
        }
      }
    } catch (Exception $ex) {
      $msg = $ex;
    }
    exithere:
    if ($msg = '') {
      $msg = 'Batch posting was finished';
    }
    return ['status' => 'true', 'msg' => $msg];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstockline($config);
    $msg = '';
    if ($isupdate['msg'] != '') {
      $msg = $isupdate['msg'];
    }
    if (!$isupdate['status']) {
      $data[0]->errcolor = 'bg-red-2';

      return ['row' => $data, 'status' => true, 'msg' => $msg];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }

  public function updateitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $update = $this->additem('update', $config);
      if ($msg != '') {
        $msg = $msg . ' ' . $update['msg'];
      } else {
        $msg = $update['msg'];
      }
    }
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
      }
    }

    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function

  public function addallitem($config)
  {
    $companyid = $config['params']['companyid'];
    $msg = '';

    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);

      if ($msg != '') {
        $msg = $msg . ' ' . $row['msg'];
      } else {
        $msg = $row['msg'];
      }

      if ($companyid == 49) { //hotmix
        $qrycmp = "select item.itemid, comp.barcode, comp.itemname, (comp.qty * " . $config['params']['data']['qty'] . ") as qty, 
                  comp.uom, comp.cost, comp.uomfactor as factor, '' as disc, 0 as wh, 0 as amt 
                  from component as comp 
                  left join item on item.barcode=comp.barcode 
                  where comp.itemid = ?";

        $data5 = json_decode(json_encode($this->coreFunctions->opentable($qrycmp, [$config['params']['data']['itemid']])), true);

        foreach ($data5 as $key2 => $data5) {
          $config['params']['data'] = $data5;
          $row2 = $this->additem('insert', $config);

          if ($row2['msg'] != '') {
            $msg = $msg . ' ' . $row2['msg'];
          } else {
            $msg = $row2['msg'];
          }
        }
      }

      if (isset($config['params']['data']['refx'])) {
        if ($config['params']['data']['refx'] != 0) {
          if ($this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $row['row'][0]->trno, 'line' => $row['row'][0]->line]);
            $this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']);
            if ($msg != '') {
              $msg = $msg . '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
            } else {
              $msg = '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
            }
          }
        }
      }
    }

    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $status = true;

    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $status = false;
      }
    }

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
    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom, '' as expiry from item where barcode=?", [$barcode]);
    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $data = $this->getlatestprice($config);

      if (!empty($data)) {
        $item[0]->amt = $data['data'][0]->amt;
        $item[0]->disc = $data['data'][0]->disc;
        $item[0]->uom = $data['data'][0]->uom;
      }
      $config['params']['data'] = json_decode(json_encode($item[0]), true);
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $ispallet = $this->companysetup->getispallet($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];

    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : "";
    $wh = $config['params']['data']['wh'];
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';
    $itemdesc = isset($config['params']['data']['itemdesc']) ? $config['params']['data']['itemdesc'] : '';
    $locid = isset($config['params']['data']['locid']) ? $config['params']['data']['locid'] : 0;
    $palletid = isset($config['params']['data']['palletid']) ? $config['params']['data']['palletid'] : 0;
    $weight = isset($config['params']['data']['weight']) ? $config['params']['data']['weight'] : 0;
    $expiry = '';
    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }

    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }

    $sgdrate = 0;

    $porefx = isset($config['params']['data']['porefx']) ? $config['params']['data']['porefx'] : 0;
    $polinex = isset($config['params']['data']['polinex']) ? $config['params']['data']['polinex'] : 0;
    $refx = isset($config['params']['data']['refx']) ? $config['params']['data']['refx'] : 0;
    $linex = isset($config['params']['data']['linex']) ? $config['params']['data']['linex'] : 0;
    $ref = isset($config['params']['data']['ref']) ? $config['params']['data']['ref'] : '';
    $rebate = isset($config['params']['data']['rebate']) ? $config['params']['data']['rebate'] : 0;
    $projectid = isset($config['params']['data']['projectid']) ? $config['params']['data']['projectid'] : 0;
    $noprint = isset($config['params']['data']['noprint']) ? $config['params']['data']['noprint'] : 'false';
    $rem = isset($config['params']['data']['rem']) ? $config['params']['data']['rem'] : '';
    $poref = isset($config['params']['data']['poref']) ? $config['params']['data']['poref'] : '';
    $podate = isset($config['params']['data']['podate']) ? $config['params']['data']['podate'] : null;

    if ($companyid == 10) { //afti
      $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
    }

    $itemstatus = '';
    if ($companyid == 22) { //EIPI
      if (isset($config['params']['data']['itemstatus'])) {
        $itemstatus = $config['params']['data']['itemstatus'];
      }
    }

    $agentamt = 0;
    if ($companyid == 60) { //transpower
      if (isset($config['params']['data']['agentamt'])) {
        $agentamt = $config['params']['data']['agentamt'];
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
      if ($companyid == 21) { //kinggeorge
        $this->logger->sbcwritelog($trno, $config, 'DEBUG', 'ADD - Line:' . $line . ' itemid:' . $itemid, $setlog ? $this->tablelogs : '');
      }
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];

      if ($companyid == 10) { //afti
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
      if ($companyid == 21) { //kinggeorge
        $this->logger->sbcwritelog($trno, $config, 'DEBUG', 'UPDATE - Line:' . $line . ' itemid:' . $itemid, $setlog ? $this->tablelogs : '');
      }
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $kgs = $this->othersClass->sanitizekeyfield('qty', $kgs);

    if ($systemtype == 'REALESTATE') {
      $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
      $phaseid = $this->coreFunctions->getfieldvalue($this->head, "phaseid", "trno=?", [$trno]);
      $modelid = $this->coreFunctions->getfieldvalue($this->head, "modelid", "trno=?", [$trno]);
      $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      $amenityid = $this->coreFunctions->getfieldvalue($this->head, "amenityid", "trno=?", [$trno]);
      $subamenityid = $this->coreFunctions->getfieldvalue($this->head, "subamenityid", "trno=?", [$trno]);
    }
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv,namt4 from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    $cost = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      if ($companyid == 60) { //transpower
        $cost = $item[0]->namt4;
      }
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));

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

    if ($companyid == 10) { //afti
      if ($disc != "") {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs, 1, 1);
      } else {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
      }
    } else {
      if ($this->companysetup->getisdiscperqty($config['params'])) {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs, 0, 1);
      } else {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
      }
    }

    if (floatval($curtopeso) == 0) {
      $curtopeso = 1;
    }

    $hamt = $computedata['amt'] * $curtopeso;
    if ($companyid == 10) { //afti
      if ($disc != "") {
        $hamt = number_format($computedata['amt'] * $curtopeso, 2, '.', '');
      }
    }
    $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $hamt,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'rem' => $rem,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'locid' => $locid,
      'palletid' => $palletid,
      'rebate' => $rebate,
      'noprint' => $noprint
    ];

    switch ($companyid) {
      case 11: //summit
        $data['rem'] = $rem;
        break;
      case 10: //afti
        $data['projectid'] = $projectid;
        $data['sgdrate'] = $sgdrate;
        break;
      case 22: //EIPI
        $data['itemstatus'] = $itemstatus;
        break;
      case 60: //transpower
        $data['porefx'] = $porefx;
        $data['polinex'] = $polinex;
        $data['cost'] = $cost;
        $data['agentamt'] = $agentamt;
        if ($action == 'insert') {
          $wireitem = $this->coreFunctions->opentable("select startwire, endwire from item where itemid=? and iswireitem=1", [$data['itemid']]);
          if (!empty($wireitem)) {
            $lastitem = $this->coreFunctions->opentable("select trno, startwire, endwire from (select trno, startwire, endwire from lastock where itemid=? union all select trno, startwire, endwire from glstock where itemid=?) as t where startwire>0 and endwire>0 order by trno desc limit 1", [$data['itemid'], $data['itemid']]);
            if (!empty($lastitem)) {
              $range = ($lastitem[0]->endwire - $lastitem[0]->startwire);
              $data['startwire'] = $lastitem[0]->endwire + 1;
              $data['endwire'] = $data['startwire'] + $range;
            } else {
              $data['startwire'] = $wireitem[0]->startwire;
              $data['endwire'] = $wireitem[0]->endwire;
            }
          } else {
            // $data['agentamt'] = $data['startwire'] = $data['endwire'] = 0;
            $data['startwire'] = $data['endwire'] = 0;
          }
        } else {
          $agentamt = isset($config['params']['data']['agentamt']) ? $config['params']['data']['agentamt'] : 0;
          $startwire = isset($config['params']['data']['startwire']) ? $config['params']['data']['startwire'] : 0;
          $endwire = isset($config['params']['data']['endwire']) ? $config['params']['data']['endwire'] : 0;
          $data['agentamt'] = $this->othersClass->sanitizekeyfield('agentamt', $agentamt);
          $data['startwire'] = $this->othersClass->sanitizekeyfield('startwire', $startwire);
          $data['endwire'] = $this->othersClass->sanitizekeyfield('endwire', $endwire);
        }
        break;
    }

    if ($systemtype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }

    //insert item
    if ($action == 'insert') {
      $sjitemlimit = $this->companysetup->getsjitemlimit($config['params']);
      if ($sjitemlimit != 0) {
        $qry = "select ifnull(count(stock.trno),0) as itmcnt from lahead as head
              left join lastock as stock on stock.trno=head.trno
              where head.doc='sj' and head.trno=?";
        $count = $this->coreFunctions->opentable($qry, [$trno]);

        if ($count[0]->itmcnt >= $sjitemlimit) {
          return ['status' => false, 'msg' => 'Item Records Limit Reached(' . $sjitemlimit . 'max)'];
        }
      }

      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      if (isset($config['params']['data']['sortline'])) {
        $data['sortline'] =  $config['params']['data']['sortline'];
      } else {
        $data['sortline'] =  $data['line'];
      }

      switch ($companyid) {
        case 10: //afti
          $data['poref'] = $poref;
          $data['podate'] = $podate;
          break;
      }

      $trno = $this->othersClass->val($trno);
      if ($trno == 0) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ZERO TRNO (SJ)');
        return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $havestock = true;
        $msg = 'Item was successfully added.';

        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            switch ($companyid) {
              case 0: //main
              case 10: //afti
                $stockinfo_data = [
                  'trno' => $trno,
                  'line' => $line,
                  'rem' => $rem
                ];
                $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
                break;
              case 19: //housegem
                $stockinfo_data = [
                  'trno' => $trno,
                  'line' => $line,
                  'weight' => $weight
                ];
                $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
                break;
              case 52: //technolab
              case 41: //labsol manila
              case 23: //labsol cebu
                $stockinfo_data = [
                  'trno' => $trno,
                  'line' => $line,
                  'itemdesc' => $itemdesc
                ];
                $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
                break;
            }
            break;
          case 'AIMSPAYROLL': //XCOMp
            $stockinfo_data = [
              'trno' => $trno,
              'line' => $line,
              'itemdesc' => $itemdesc
            ];
            $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            break;
        }

        if ($companyid == 60) {
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' Cost' . $cost . ' wh:' . $wh . ' Uom:' . $uom . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        } else {
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' Uom:' . $uom . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        }

        if ($isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

            //CHECK BELOW COST
            if ($this->companysetup->checkbelowcost($config['params'])) {
              $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
              if ($belowcost == 1) {
                $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
              } elseif ($belowcost == 2) {
                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
              }
            }
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
          }
        }
        if ($companyid == 10) { //afti
          if ($this->setservedsqitems($refx, $linex) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setservedsqitems($refx, $linex);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than SO Qty.";
          }
        } else if ($companyid == 60) { //transpower
          if ($this->setservedpoitems($porefx, $polinex) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'linex' => $line]);
            $this->setservedpoitems($porefx, $polinex);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than PO Qty.";
          }
        } else {
          if ($this->setserveditems($refx, $linex) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than RR Qty.";
          }
        }

        $this->othersClass->getcreditinfo($config, $this->head);
        $row = $this->openstockline($config);
        if (!$havestock) {
          $row[0]->errcolor = 'bg-red-2';
          $msg = '(' . $item[0]->barcode . ') Out of Stock.';
        }

        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);

      switch ($this->companysetup->getsystemtype($config['params'])) {
        case 'AIMS':
          switch ($companyid) {
            case 52: //technolab
            case 41: //labsol manila
            case 23: //labsol cebu
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'itemdesc' => $itemdesc
              ];
              $checkstockinfo = $this->coreFunctions->getfieldvalue("stockinfo", "trno", "trno=? and line =?", [$trno, $line]);
              if ($checkstockinfo == '') {
                $this->coreFunctions->sbcinsert("stockinfo", $stockinfo_data);
              } else {
                $stockinfo_data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $stockinfo_data['editby'] = $config['params']['user'];
                foreach ($stockinfo_data as $key => $valueinfo) {
                  $stockinfo_data[$key] = $this->othersClass->sanitizekeyfield($key, $stockinfo_data[$key]);
                }
                $this->coreFunctions->sbcupdate("stockinfo", $stockinfo_data, ['trno' => $trno, 'line' => $line]);
              }
              break;
          }
          break;
      }
      if ($isnoninv == 0) {
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

          //CHECK BELOW COST
          if ($this->companysetup->checkbelowcost($config['params'])) {
            $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
            if ($belowcost == 1) {
              $msg = '(' . $item[0]->barcode . ') Is this free if charge? Please check.';
            } elseif ($belowcost == 2) {
              $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
              $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
              $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
              $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
              $return = false;
            }
          }
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          if ($companyid == 10) { //afti
            $this->setservedsqitems($refx, $linex);
          } else {
            $this->setserveditems($refx, $linex);
          }
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Out of Stock.";
        }
      }

      if ($companyid == 10) { //afti
        if ($this->setservedsqitems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedsqitems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
        }
      } else if ($companyid == 60) { //transpower
        if ($porefx != 0) {
          if ($this->setservedpoitems($porefx, $polinex) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setservedpoitems($porefx, $polinex);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty is Greater than PO Qty.";
          }
        } else {
          goto setServed;
        }
      } else {
        setServed:
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
        }
      }

      if ($companyid == 24) { //goodfound
        $weightin = floatval($this->coreFunctions->getfieldvalue('cntnuminfo', 'weightin', 'trno=?', [$trno]));
        $weightout = floatval($this->coreFunctions->getfieldvalue('cntnuminfo', 'weightout', 'trno=?', [$trno]));
        $qry = "select sum(stock.iss) as value from lastock as stock
        left join item as i on i.itemid=stock.itemid
        where stock.trno =$trno
        and stock.iscomponent=0 and i.fg_isfinishedgood=1
        and i.body not in
        ('MAYON TYPE 1P','MAYON TYPE 1T SUPER','MAYON TYPE 1T PREMIUM','MAYON TYPE 1T BICOL','MAYON PPC','MAYON GREEN')";

        $qty = floatval($this->coreFunctions->datareader($qry));

        $weightin = isset($weightin) ? $weightin : 0;
        $weightout = isset($weightout) ? $weightout : 0;
        $kilo = ($weightout - $weightin) / $qty;

        $headinfo = [
          'kilo' => $kilo
        ];

        $this->coreFunctions->sbcupdate('cntnuminfo', $headinfo, ["trno" => $trno]);
      }



      return ['status' => $return, 'msg' => $msg];
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

    $data = $this->coreFunctions->opentable('select refx,linex,porefx,polinex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      switch ($config['params']['companyid']) {
        case 10: //afti
          $this->setservedsqitems($data[$key]->refx, $data[$key]->linex);
          break;
        case 60: //transpower
          $this->setservedpoitems($data[$key]->porefx, $data[$key]->polinex);
          $this->setserveditems($data[$key]->refx, $data[$key]->linex);
          break;
        default:
          $this->setserveditems($data[$key]->refx, $data[$key]->linex);
          break;
      }
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function setservedpoitems($refx, $linex)
  {
    if ($refx == 0) return 1;
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as stock on stock.trno=head.trno where head.doc in ('SJ', 'BO') and stock.porefx=" . $refx . " and stock.polinex=" . $linex . " union all
      select stock." . $this->hqty . " from glhead as head left join glstock as stock on stock.trno=head.trno where head.doc in ('SJ', 'BO') and stock.porefx=" . $refx . " and stock.polinex=" . $linex;
    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2, [], '', true);
    $this->coreFunctions->create_Elog($qry1);
    $result = $this->coreFunctions->execqry("update hpostock set sjqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and qty>sjqa and void=0", [$refx], '', true);
    if ($status == 1) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and sjqa<>0 and void=0", [$refx], '', true);
      if ($status == 1) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx, 'update');
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx, 'update');
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx, 'update');
    }
    return $result;
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

    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and iss>qa and void=0", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and qa<>0 and void=0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx); // partial
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx); // no SJ
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx); //complete
    }

    // $status = $this->coreFunctions->opentable("select ifnull(count(trno),0) as value, sum(qa) as qa from hsostock where trno=? and iss>qa and void=0 group by trno", [$refx]);
    // if (count($status) > 0) {

    //   if ($status[0]->qa > 0) {
    //     $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx); // partial
    //   } else {
    //     $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx); // no SJ
    //   }
    // } else {
    //   $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx); //complete
    // }

    return $result;
  }

  public function setservedsqitems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='SJ' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='SJ' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if (floatval($qty) == 0) {
      $qty = 0;
    }

    $return =  $this->coreFunctions->execqry("update hqsstock set sjqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    $sotrno = $this->coreFunctions->datareader("select sotrno as value from hqshead where trno=?", [$refx]);
    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hqsstock where trno=? and iss>(sjqa+voidqty)", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hqsstock where trno=? and sjqa<>0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $sotrno);
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $sotrno);
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $sotrno);
    }
    return $return;
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
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );

    switch ($config['params']['companyid']) {
      case 10: //afti
        $this->setservedsqitems($data[0]->refx, $data[0]->linex);
        break;
      case 60: //transpower
        //$this->coreFunctions->create_Elog("delete item");
        $this->setservedpoitems($data[0]->porefx, $data[0]->polinex);
        $this->setserveditems($data[0]->refx, $data[0]->linex);
        break;
      default:
        $this->setserveditems($data[0]->refx, $data[0]->linex);
        break;
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    $pricetype = $this->companysetup->getpricetype($config['params']);
    $pricedec = $this->companysetup->getdecimal('price', $config['params']);

    $pricegrp = '';
    $data = [];

    switch ($pricetype) {
      case 'Stockcard':
        goto itempricehere;
        break;

      case 'CustomerGroup':
      case 'CustomerGroupLatest':
        if ($companyid == 59) { //roosevelt
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$barcode]);
          $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);
          $qry = "select 'Customer SKU' as docno, left(now(),10) as dateid, round(sku.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt, round(sku.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt, sku.disc, item.uom, item.itemid from sku left join item on item.itemid=sku.itemid where sku.itemid=" . $itemid . " and sku.clientid=" . $clientid;
          $data = $this->coreFunctions->opentable($qry);
          if (!empty($data)) goto setpricehere;
        }
        $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$client]);

        if ($pricegrp != '') {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $qry = "select 'A' as g,'" . $pricefield['label'] . "' as docno, left(now(),10) as dateid," . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom, itemid,1 as factor from item where barcode=? 
            union all
            select 'Z' as g,docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,itemid,factor from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc,stock.itemid,uom.factor
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as computeramt,
            stock.uom,stock.disc,stock.itemid,uom.factor from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            order by dateid desc limit 5) as tbl order by g,dateid desc";
          $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);


          if ($companyid == 21) { //kinggeorge - compute based on default SO uom
            $datauom = $this->coreFunctions->opentable("select uom, factor from uom where itemid=" . $data[0]->itemid . " and issalesdef=1 and uom.issalesdef=1 limit 1");
            if (!empty($datauom)) {
              $data[0]->uom =  $datauom[0]->uom;
              if ($datauom[0]->factor == 0) {
                $datauom[0]->factor = 1;
              }
              $data[0]->amt =  number_format($data[0]->amt * $datauom[0]->factor, $pricedec);
              $data[0]->famt =  $data[0]->amt;
            }
          }

          if (!empty($data)) {
            goto setpricehere;
          }
        } else {
          if ($pricetype == 'CustomerGroupLatest') {
            goto getCustomerLatestPriceHere;
          } else {
            goto itempricehere;
          }
        }
        break;

      default:
        getCustomerLatestPriceHere:
        $amtfield = 'amt';
        switch ($companyid) {
          case 22: //eipi
            $itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=?', [$barcode]);
            $client = $this->coreFunctions->getfieldvalue('lahead', 'client', 'trno=?', [$trno]);
            $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$client]);
            $amt = $this->coreFunctions->getfieldvalue('sku', 'amt', 'itemid=? and clientid=?', [$itemid, $clientid], '', 1);

            if ($amt != 0) {
              $amtfield = $amt;
            }

            $qry = "select 'SKU' as docno,left(now(),10) as dateid,round(s.amt,2) as amt,round(i.amt,2) as defamt,i.disc,uom
            from sku as s left join item as i on s.itemid = i.itemid and s.clientid  = $clientid where i.barcode =?
            union all
            select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom 
              from(
                select head.docno,head.dateid,
                stock.rrcost as amt,stock.uom,stock.disc
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid = stock.itemid
                where head.doc in ('RR','IS','AJ','TS') and cntnum.center = ?
                and item.barcode = ? 
                and stock.cost <> 0 and cntnum.trno <>?
              union all
                select head.docno,head.dateid,stock.rrcost as amt,
                stock.uom,stock.disc from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join client on client.clientid = head.clientid
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('RR','IS','AJ','TS') and cntnum.center = ?
                and item.barcode = ? 
                and stock.cost <> 0 and cntnum.trno <>?
              order by dateid desc limit 5 ) as tbl order by dateid desc ";
            $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $trno, $center, $barcode, $trno]);
            break;
          default:
            $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
            round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem from(select head.docno,head.dateid,
              stock.isamt as amt,stock.uom,stock.disc,'test' as rem
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid = stock.itemid
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and head.client = ?
              and stock.isamt <> 0 and cntnum.trno <> ?
              UNION ALL
              select head.docno,head.dateid,stock.isamt as computeramt,
              stock.uom,stock.disc,'test' as rem from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join client on client.clientid = head.clientid
              left join cntnum on cntnum.trno=head.trno
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and client.client = ?
              and stock.isamt <> 0 and cntnum.trno <> ?
              order by dateid desc limit 5) as tbl order by dateid desc";

            $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);
            break;
        }

        break;
    }

    if (!empty($data)) {
      // if ($companyid == 21) { //kinggeorge
      //   $qry = "select 'Retail Price' as docno, round((item.amt * if(uom.factor=0,1,uom.factor))," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
      //   round((item.amt * if(uom.factor=0,1,uom.factor))," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,'" . $data[0]->uom . "' as uom 
      //   from item left join uom on uom.itemid=item.itemid and uom.uom='" . $data[0]->uom . "' where item.barcode=?";
      //   $data = $this->coreFunctions->opentable($qry, [$barcode]);
      // }
      if ($companyid == 21) { //kinggeorge
        kinggeorge_defaultprice:
        $defaultsameuom = " and uom.issalesdef=1"; //kinggeorge
        $qry = "select 'Retail Price' as docno, round((item.amt * if(uom.factor=0,1,uom.factor))," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
        round((item.amt * if(uom.factor=0,1,uom.factor))," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom.uom 
        from item left join uom on uom.itemid=item.itemid and uom.issales=1 where item.barcode=? " . $defaultsameuom;
        $this->coreFunctions->LogConsole($qry);
        $data = $this->coreFunctions->opentable($qry, [$barcode]);
        if (empty($data)) {
          $defaultsameuom = '';
          goto kinggeorge_defaultprice;
        }
      }
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    } else {
      itempricehere:

      if ($companyid == 21) { //kinggeorge
        $qry = "select 'Retail Price' as docno, amt,amt as defamt,disc,uom,itemid from item where barcode=?";
        $data = $this->coreFunctions->opentable($qry, [$barcode]);
        goto kinggeorge_defaultprice;
      }


      $qry = "select 'STOCKCARD'  as docno,left(now(),10) as dateid,amt,amt as defamt,disc,uom,'test' as rem,1 as factor from item where barcode=? 
        union all
        select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem,factor from(select head.docno,head.dateid,
        stock.isamt as amt,stock.uom,stock.disc,'test' as rem,uom.factor
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
        where head.doc = 'SJ' and cntnum.center = ?
        and item.barcode = ? and head.client = ?
        and stock.isamt <> 0 and cntnum.trno <> ?
        UNION ALL
        select head.docno,head.dateid,stock.isamt as computeramt,
        stock.uom,stock.disc,'test' as rem,uom.factor from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
        left join client on client.clientid = head.clientid
        left join cntnum on cntnum.trno=head.trno
        where head.doc = 'SJ' and cntnum.center = ?
        and item.barcode = ? and client.client = ?
        and stock.isamt <> 0 and cntnum.trno <> ?
        order by dateid desc limit 5) as tbl";
      $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

      setpricehere:
      $usdprice = 0;
      $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
      $defuom = '';

      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        if (empty($data)) {
          $data[0]->docno = 'UOM';
        }
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
        $this->coreFunctions->LogConsole('def' . $defuom . $data[0]->amt);
        if ($defuom != "") {
          $deffactor = $this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
          $data[0]->uom = $defuom;
          $data[0]->factor = $deffactor;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = number_format($data[0]->amt * $deffactor, 2);
            } else {
              if ($companyid != 32) { //not 3m
                $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
              }
            }
          }
        } else {
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]));
            } else {
              if ($companyid != 32) { //not 3m
                $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]);
              }
            }
          }
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $data[0]->docno = 'UOM';
          $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom." . $pricefield['amt'] . ",0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
        }
      }

      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, $this->companysetup->getdecimal('price', $config['params']));
        }
      }

      if (isset($data[0]->amt)) {
        if (floatval($data[0]->amt) == 0) {
          return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
        } else {
          return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
        }
      } else {
        return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
      }
    }
  } // end function

  public function getsosummaryqry($config)
  {
    $addfield = ",head.ourref";

    switch ($config['params']['companyid']) {
      case 28: //xcomp
        $addfield = ",head.docno as ourref";
        break;
      case 22: //EIPI
        $addfield .= ",stock.fstatus as itemstatus";
        break;
      case 60: //transpower
        $addfield .= ",stock.agentamt";
        break;
      case 29: //sbc
        $addfield .= ",head.tax,head.vattype";
        break;
    }
    return "
        select head.docno,head.client, head.clientname, head.address, ifnull(head.rem,'') as rem, 
        head.cur, head.forex, head.shipto " . $addfield . " , head.yourref, head.terms, 
        ifnull(head.branch,0) as branch,item.itemid,stock.trno,stock.line, item.barcode,
        stock.uom,stock.amt,(stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,stock.weight,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,head.shipto,head.mlcp_freight,
        head.ms_freight,head.agent,head.projectid as hprojectid,wh.client as swh,
        info.driverid,info.helperid,info.checkerid,info.plateno,info.truckid,sinfo.itemdesc,head.sano,head.pono,head.wh,head.salestype,head.due
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as wh on wh.clientid=stock.whid 
        left join hheadinfotrans as info on info.trno=head.trno
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono
        where stock.trno = ? and stock.iss>stock.qa and stock.void=0";
  }

  public function getsosummary($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);
    $companyid = $config['params']['companyid'];
    $this->coreFunctions->LogConsole('FIFO-' . $fifoexpiration);
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $updatehead = 0;

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getsosummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        if ($companyid == 24) { //goodfound
          $overwritedue = $this->othersClass->checkAccess($config['params']['user'], 4219);
          if (!$overwritedue) {
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            if (substr($current_timestamp, 0, 10) > substr($data[0]->due, 0, 10)) {
              return ['row' => $rows, 'status' => false, 'msg' => 'SO past validity, please contact admin.'];
            }
          }
        }
        if ($updatehead == 0) {

          if ($companyid == 29) { //sbc
            $headupdate = [
              'ourref' => $data[0]->ourref,
              'yourref' => $data[0]->yourref,
              'terms' => $data[0]->terms,
              'agent' => $data[0]->agent,
              'rem' => $data[0]->rem,
              'wh' => $data[0]->wh,
              'shipto' => $data[0]->shipto,
              'projectid' => $data[0]->hprojectid,
              'sano' => $data[0]->sano,
              'pono' => $data[0]->pono,
              'tax' => $data[0]->tax,
              'vattype' => $data[0]->vattype

            ];
          } else {
            $headupdate = [
              'ourref' => $data[0]->ourref,
              'yourref' => $data[0]->yourref,
              'terms' => $data[0]->terms,
              'agent' => $data[0]->agent,
              'rem' => $data[0]->rem,
              'wh' => $data[0]->wh,
              'shipto' => $data[0]->shipto,
              'projectid' => $data[0]->hprojectid,
              'sano' => $data[0]->sano,
              'pono' => $data[0]->pono
            ];
          }

          if ($companyid == 24) { //goodfound
            if (substr($data[0]->docno, 0, 2) == 'SO') {
              $headupdate['tax'] = 12;
              $headupdate['vattype'] = 'VATABLE';

              if ($data[0]->salestype == 'Pickup') {
                $headupdate['statid'] = 40;
              }
              if ($data[0]->salestype == 'Deliver') {
                $headupdate['statid'] = 24;
              }
            }
          }

          $updatehead = $this->coreFunctions->sbcupdate($this->head, $headupdate, ["trno" => $trno]);
          if ($updatehead) {
            if ($companyid == 19) { //housegem
              $headinfo = [
                'driverid' => $data[0]->driverid,
                'helperid' => $data[0]->helperid,
                'checkerid' => $data[0]->checkerid,
                'plateno' => $data[0]->plateno,
                'truckid' => $data[0]->truckid
              ];
              $this->coreFunctions->sbcupdate('cntnuminfo', $headinfo, ["trno" => $trno]);
            }
          }
        }

        foreach ($data as $key2 => $value) {

          if ($fifoexpiration) {
            $wh = $data[$key2]->swh;
            $return_result = $this->insertfifoexpiration($config, $value, $wh);
            if (!empty($return_result)) {
              foreach ($return_result as $key => $return_val) {
                array_push($rows, $return_val);
              }
            } else {
              goto defaultsjentryhere;
            }
          } else {
            defaultsjentryhere:
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->isqty;
            $config['params']['data']['wh'] = $data[$key2]->swh;
            $config['params']['data']['loc'] = $data[$key2]->loc;
            $config['params']['data']['expiry'] = $data[$key2]->expiry;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->isamt;
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['kgs'] = $data[$key2]->kgs;
            $config['params']['data']['weight'] = $data[$key2]->weight;
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
            if ($companyid == 22) { //EIPI
              $config['params']['data']['itemstatus'] = $data[$key2]->itemstatus;
            }
            if ($companyid == 60) { //transpower
              $config['params']['data']['agentamt'] = $data[$key2]->agentamt;
            }

            $return = $this->additem('insert', $config);

            if ($msg = '') {
              $msg = $return['msg'];
            } else {
              $msg = $msg . $return['msg'];
            }

            if ($return['status']) {
              if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                $line = $return['row'][0]->line;
                $config['params']['trno'] = $trno;
                $config['params']['line'] = $line;
                $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                $row = $this->openstockline($config);
                $return = ['row' => $row, 'status' => true, 'msg' => $msg];
              }
              array_push($rows, $return['row'][0]);
            }
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  } //end function

  public function insertfifoexpiration($config, $value, $wh, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $return_row = [];

    $sql = "select rrstatus.expiry,rrstatus.loc,rrstatus.whid,ifnull(sum(rrstatus.bal),0) as bal from rrstatus
        left join item on item.itemid = rrstatus.itemid left join client on client.clientid=rrstatus.whid
        where rrstatus.itemid = " . $value->itemid . " and client.client = '" . $wh . "' and rrstatus.bal <> 0 
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

        $config['params']['data']['uom'] = $value->uom;
        $config['params']['data']['itemid'] = $value->itemid;
        $config['params']['trno'] = $trno;
        $config['params']['data']['disc'] = $value->disc;
        $config['params']['data']['qty'] = $qty;
        $config['params']['data']['wh'] = $value->swh;
        $config['params']['data']['loc'] = $loc;
        $config['params']['data']['expiry'] = $expiry;
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $value->trno;
        $config['params']['data']['linex'] = $value->line;
        $config['params']['data']['ref'] = $value->docno;
        $config['params']['data']['amt'] = $value->isamt;
        $config['params']['data']['projectid'] = $value->projectid;
        $config['params']['data']['itemdesc'] = $value->itemdesc;
        $return = $this->additem('insert', $config, $setlog);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setserveditems($value->trno, $value->line) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($$value->trno, $value->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
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

  public function getsosummaryserial($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.whid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $qry = "select serialin.sline as value from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
                where rrstatus.itemid=? and rrstatus.whid=? and serialin.serial=? and serialin.outline=0 ";
          $sline = $this->coreFunctions->datareader($qry, [$data[$key2]->itemid, $data[$key2]->whid, $data[$key2]->loc]);

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';

          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
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
            } else {
              if ($sline != '') {
                $line = $return['row'][0]->line;
                $this->othersClass->insertserialout($sline, $trno, $line, $data[$key2]->loc);
              }
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function

  public function getsodetailsserial($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.whid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $qry = "select serialin.sline as value from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
                where rrstatus.itemid=? and rrstatus.whid=? and serialin.serial=? and serialin.outline=0 ";
          $sline = $this->coreFunctions->datareader($qry, [$data[$key2]->itemid, $data[$key2]->whid, $data[$key2]->loc]);

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';

          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
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
              $return = ['row' => $row, 'status' => true, 'msg' => $return['msg']];
            } else {
              if ($sline != '') {
                $line = $return['row'][0]->line;
                $this->othersClass->insertserialout($sline, $trno, $line, $data[$key2]->loc);
              }
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $return['msg']];
  } //end function

  public function getsodetails($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);

    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';

    $addfield = '';

    switch ($config['params']['companyid']) {
      case 22: //EIPI
        $addfield = ', stock.fstatus as itemstatus';
        break;
      case 60: //transpower
        $addfield .= ",stock.agentamt";
        break;
      case 29: //sbc
        $addfield .= ",head.tax,head.vattype";
        break;
    }



    foreach ($config['params']['rows'] as $key => $value) {

      $qry = "
        select head.docno, head.ourref, head.yourref, head.terms, head.agent, head.shipto, head.projectid as hprojectid,head.rem,item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,wh.client as swh,info.driverid,info.helperid,info.checkerid,info.plateno,stock.weight,sinfo.itemdesc,head.sano,head.pono,head.wh,head.due  $addfield
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as wh on wh.clientid=stock.whid left join hheadinfotrans as info on info.trno=head.trno
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        $updatehead = 0;
        if ($companyid == 24) { //goodfound
          $overwritedue = $this->othersClass->checkAccess($config['params']['user'], 4219);
          if (!$overwritedue) {
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            if (substr($current_timestamp, 0, 10) > substr($data[0]->due, 0, 10)) {
              return ['row' => $rows, 'status' => false, 'msg' => 'SO past validity, please contact admin.'];
            }
          }
        }
        foreach ($data as $key2 => $value) {

          if ($updatehead == 0) {

            if ($companyid == 29) { //sbc
              $headupdate = [
                'ourref' => $data[0]->ourref,
                'yourref' => $data[0]->yourref,
                'terms' => $data[0]->terms,
                'agent' => $data[0]->agent,
                'rem' => $data[0]->rem,
                'wh' => $data[0]->wh,
                'shipto' => $data[0]->shipto,
                'projectid' => $data[0]->hprojectid,
                'sano' => $data[0]->sano,
                'pono' => $data[0]->pono,
                'tax' => $data[0]->tax,
                'vattype' => $data[0]->vattype
              ];
            } else {
              $headupdate = [
                'ourref' => $data[0]->ourref,
                'yourref' => $data[0]->yourref,
                'terms' => $data[0]->terms,
                'agent' => $data[0]->agent,
                'rem' => $data[0]->rem,
                'wh' => $data[0]->wh,
                'shipto' => $data[0]->shipto,
                'projectid' => $data[0]->hprojectid,
                'sano' => $data[0]->sano,
                'pono' => $data[0]->pono
              ];
            }


            if ($companyid == 24) { //goodfound
              if (substr($data[0]->docno, 0, 2) == 'SO') {
                $headupdate['tax'] = 12;
                $headupdate['vattype'] = 'VATABLE';
              }
            }

            $updatehead = $this->coreFunctions->sbcupdate($this->head, $headupdate, ["trno" => $trno]);
            if ($updatehead) {
              if ($companyid == 19) { //housegem
                $headinfo = [
                  'driverid' => $data[0]->driverid,
                  'helperid' => $data[0]->helperid,
                  'checkerid' => $data[0]->checkerid,
                  'plateno' => $data[0]->plateno
                ];
                $this->coreFunctions->sbcupdate('cntnuminfo', $headinfo, ["trno" => $trno]);
              }
            }
          }

          if ($fifoexpiration) {
            $wh = $data[$key2]->swh;
            $return_result = $this->insertfifoexpiration($config, $value, $wh);
            if (!empty($return_result)) {
              foreach ($return_result as $key => $return_val) {
                array_push($rows, $return_val);
              }
            } else {
              goto defaultsjentryhere;
            }
          } else {
            defaultsjentryhere:
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->isqty;
            if ($companyid == 15) { //nathina
              $config['params']['data']['wh'] = $data[$key2]->swh;
            } else {
              $config['params']['data']['wh'] = $wh;
            }
            $config['params']['data']['loc'] = $data[$key2]->loc;
            $config['params']['data']['expiry'] = $data[$key2]->expiry;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->isamt;
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['kgs'] = $data[$key2]->kgs;
            $config['params']['data']['weight'] = $data[$key2]->weight;
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
            if ($companyid == 22) { //EIPI
              $config['params']['data']['itemstatus'] = $data[$key2]->itemstatus;
            }

            if ($companyid == 60) { //transpower
              $config['params']['data']['agentamt'] = $data[$key2]->agentamt;
            }

            $return = $this->additem('insert', $config);
            if ($msg = '') {
              $msg = $return['msg'];
            } else {
              $msg = $msg . $return['msg'];
            }
            if ($return['status']) {
              if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                $line = $return['row'][0]->line;
                $config['params']['trno'] = $trno;
                $config['params']['line'] = $line;
                $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                $row = $this->openstockline($config);
                $return = ['row' => $row, 'status' => true, 'msg' => $msg];
              }
              array_push($rows, $return['row'][0]);
            }
          }
        } // end foreach
      } //end if
    } //end foreach
    switch ($companyid) {
      case 19: //housegem
      case 24: //goodfound
        return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
        break;

      default:
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
        break;
    }
  } //end function

  public function getsqsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, 
      date(head.dateid) as dateid, left(head.due, 10) as podate, head.yourref,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.projectid,stock.sgdrate,stock.sortline
      from hsqhead as so 
      left join hqshead as head on head.sotrno=so.trno 
      left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.voidqty) and stock.void = 0 and stock.trno=? order by stock.line
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
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          $config['params']['data']['poref'] = $data[$key2]->yourref;
          $config['params']['data']['podate'] = $data[$key2]->podate;
          $config['params']['data']['sortline'] = $data[$key2]->sortline;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function getsqdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, 
      date(head.dateid) as dateid, left(head.due, 10) as podate, head.yourref,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.projectid,stock.sgdrate,stock.sortline
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.voidqty) and stock.void = 0 and stock.trno=? and stock.line=?
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          $config['params']['data']['poref'] = $data[$key2]->yourref;
          $config['params']['data']['podate'] = $data[$key2]->podate;
          $config['params']['data']['sortline'] = $data[$key2]->sortline;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function  

  public function createdistribution($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $status = true;
    $totalar = 0;
    $ewt = 0;
    $ewtamt = 0;
    $isvatexsales = $this->companysetup->getvatexsales($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $delcharge = $this->coreFunctions->getfieldvalue($this->head, "ms_freight", "trno=?", [$trno]);

    $commexp = $this->coreFunctions->datareader("select commamt-commvat as value from cntnuminfo where trno=?", [$trno], '', true);

    if ($delcharge == '') {
      $delcharge = 0;
    }
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $fields = '';
    if ($systype == 'REALESTATE') {
      $fields = ',stock.projectid as sprojectid,stock.phaseid,stock.modelid, 
                  stock.blklotid,stock.amenityid,stock.subamenityid';
    }
    if ($companyid == 10) { //afti
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,
        ifnull(a.acno,"") as asset,ifnull(r.acno,"") as revenue,ifnull(e.acnoid,0) as expense,
        stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,stock.projectid,client.rev,
        stock.rebate,head.deptid,head.branch,head.taxdef,head.yourref,head.sotrno,head.deldate,head.ewt,head.ewtrate
        from ' . $this->head . ' as head 
        left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid 
        left join projectmasterfile as p on p.line = stock.projectid left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid
        left join coa as e on e.acnoid = p.expenseid where head.trno=?';
    } else {
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
      item.expense,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate,head.taxdef,head.deldate,head.ewt,head.ewtrate
      ' . $fields . '
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';
    }

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
        $revacct2 = $revacct;

        if ($this->companysetup->getisdiscperqty($config['params'])) {
          $discamt = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
          $disc = $discamt * $stock[$key]->isqty;
        } else {
          $disc = ($stock[$key]->isamt * $stock[$key]->isqty) - ($this->othersClass->discount($stock[$key]->isamt * $stock[$key]->isqty, $stock[$key]->disc));
        }


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
          $revacct2 = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct2 = $stock[$key]->rev;
          }
        }

        if ($companyid == 22) { //eipi
          if ($stock[0]->ewtrate != 0) {
            $ewt = $stock[0]->ewtrate / 100;
          }
        }

        $expense = isset($stock[$key]->expense) ? $stock[$key]->expense : '';

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => number_format($stock[$key]->ext, 2, '.', ''),
          'ar' => $stock[$key]->taxdef == 0 ? number_format($stock[$key]->ext, 2, '.', '') : 0,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct2,
          'expense' => $expense,
          'tax' =>  $stock[$key]->taxdef == 0 ? $tax : 0,
          'discamt' => number_format($disc, 2, '.', ''),
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => number_format($stock[$key]->cost * $stock[$key]->iss, 2, '.', ''),
          'fcost' => number_format($stock[$key]->fcost * $stock[$key]->iss, 2, '.', ''),
          'projectid' => $stock[$key]->projectid,
          'rebate' => $stock[$key]->rebate,
          'deldate' => $stock[$key]->deldate,
          'ewt' => $ewt
        ];

        if ($systype == 'REALESTATE') {
          $params['projectid'] = $stock[$key]->sprojectid;
          $params['phaseid'] = $stock[$key]->phaseid;
          $params['modelid'] = $stock[$key]->modelid;
          $params['blklotid'] = $stock[$key]->blklotid;
          $params['amenityid'] = $stock[$key]->amenityid;
          $params['subamenityid'] = $stock[$key]->subamenityid;
        }
        if ($companyid == 10) { //afti
          $params['branch'] = $stock[$key]->branch;
          $params['deptid'] = $stock[$key]->deptid;
          $params['taxdef'] = $stock[$key]->taxdef;
          $params['poref'] = $stock[$key]->yourref;
          $params['sotrno'] = $stock[$key]->sotrno;
        }
        if ($isvatexsales) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
      }
    }

    //entry ar and vat if with default tax    
    $taxdef = $this->coreFunctions->getfieldvalue($this->head, "taxdef", "trno=?", [$trno]);

    $d = [];
    if ($taxdef != 0 || $delcharge != 0 || $commexp != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
    }

    if ($taxdef != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$d[0]->contra]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => (($totalar + $taxdef) * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $totalar + $taxdef, 'fcr' => 0];
      if ($companyid == 10) { //afti
        $entry['branch'] = $d[0]->branch;
        $entry['deptid'] = $d[0]->deptid;
        $entry['poref'] = $stock[$key]->yourref;
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

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ["TX2"]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'cr' => ($taxdef * $d[0]->forex), 'db' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $taxdef, 'fcr' => 0];
      if ($companyid == 10) { //afti
        $entry['branch'] = $d[0]->branch;
        $entry['deptid'] = $d[0]->deptid;
        $entry['poref'] = $stock[$key]->yourref;
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

    if ($delcharge != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['DC1']);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => 0, 'cr' => $delcharge * $d[0]->forex, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => ($delcharge * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid, 'fcr' => 0];
      if ($companyid == 10) { //afti
        $entry['branch'] = $d[0]->branch;
        $entry['deptid'] = $d[0]->deptid;
        $entry['poref'] = $d[0]->poref;
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

    if ($commexp != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['EXCOM']);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => $commexp * $d[0]->forex, 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => ($commexp * $d[0]->forex) * -1, 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid, 'fcr' => 0, 'projectid' => $params['projectid']];
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
    $companyid = $config['params']['companyid'];
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

    if ($companyid == 22) { //eipi
      if (floatval($params['ewt']) != 0) {
        if (floatval($params['tax']) != 0) {
          $ewtamt = ($params['ext'] - $params['tax']) * $params['ewt'];
        } else {
          $ewtamt = $params['ext'] * $params['ewt'];
        }
      }
    }
    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ar'] - $ewtamt) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : ($params['ar'] - $ewtamt), 'fcr' => 0, 'projectid' => $params['projectid']];
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

      if ($companyid == 19) { //housegem
        if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      if ($params['discamt'] < 0) {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => abs($params['discamt'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
      } else {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
      }
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

      if ($companyid == 19) { //housegem
        if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
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

        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        if ($params['expense'] == '') {
          $cogs = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        } else {
          $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['expense']]);
        }
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
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

        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }


    //rebate vitaline
    if (floatval($params['rebate']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR3']);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => $params['rebate'] * $forex, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['rebate'], 'fdb' => 0, 'projectid' => $params['projectid']];

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

    if ($companyid == 22) { //eipi
      if (floatval($params['ewt']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['ARWT1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => round($ewtamt * $forex, 2), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $ewtamt, 'fcr' => 0, 'projectid' => $params['projectid']];

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
      //sales
      $sales = ($params['ext'] - $params['rebate'] - $params['tax']);
      $sales  = $sales + $params['discamt'];
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
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

        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      // output tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
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

      if ($companyid == 19) { //housegem
        if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sales
      $sales = ($params['ext'] - $params['rebate']);
      $sales = round(($sales + $params['discamt']), 2);
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
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

        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function distributionvatex($params, $config)
  {
    $companyid = $config['params']['companyid'];
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
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['poref'] = $params['poref'];
        $entry['podate'] = $this->coreFunctions->getfieldvalue("hqshead", "due", "trno=?", [$params['sotrno']]);
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
    if ($this->companysetup->getissalesdisc($config['params'])) {
      if (floatval($params['discamt']) != 0) {
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
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


    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
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

        //cogs
        $cogs =  $params['expense'] == 0 ? $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']) : $params['expense'];
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
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

    //sales
    $sales = $params['ext'];
    if ($this->companysetup->getissalesdisc($config['params'])) {
      $sales = round(($sales + $params['discamt']), 2);
    }

    if (floatval($sales) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
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

    // output tax
    if ($params['tax'] != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
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

    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 21: //kinggeorge
        // for lock transaction every generate report
        $isposted = $this->othersClass->isposted2($config['params']['trno'], $this->tablenum);
        if (!$isposted) {
          $config['params']['action'] = 'lock';
          $config['params']['locktype'] = 'AUTO';

          $result = $this->headClass->lockunlock($config);
          if (!$result['status']) {
            return ['status' => false, 'msg' => $result['msg']];
          }
        }
        break;
      case 60: //transpower
        $isposted = $this->othersClass->isposted2($config['params']['trno'], $this->tablenum);
        $islocked = $this->othersClass->islocked($config);
        $stocks = 0;
        $tbl = 'lastock';
        if ($isposted) $tbl = 'glstock';
        if (!$islocked) {
          $stocks = $this->coreFunctions->datareader("select stock.line as value from " . $tbl . " as stock left join item on item.itemid=stock.itemid where stock.trno=? and stock.amt<item.namt6", [$config['params']['trno']], '', true);

          if ($stocks != 0) {
            return ['status' => false, 'msg' => 'Print failed, amount less than lowest net amount.'];
          }
        }


        if (!$isposted) {
          $config['params']['action'] = 'lock';
          $config['params']['locktype'] = 'AUTO';

          $result = $this->headClass->lockunlock($config);
          if (!$result['status']) {
            return ['status' => false, 'msg' => $result['msg']];
          }
        }
        break;
    }

    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $dataparams = $config['params']['dataparams'];


    switch ($companyid) {
      case 10: //afti
        $sjoptions = $config['params']['dataparams']['radiosjafti'];
        $data = app($this->companysetup->getreportpath($config['params']))->report_sj_query($config['params']['dataid']);
        switch ($sjoptions) {
          case 'salesinvoice':
            $str = app($this->companysetup->getreportpath($config['params']))->reportsalesinvoicepdf($config, $data);
            break;
          default:
            $str = app($this->companysetup->getreportpath($config['params']))->reportdeliveryreceiptpdf($config, $data);
            break;
        }
        break;
      case 52: //technolab
      case 61: //bytesized
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        break;

      // case 21: //kinggeorge - backup checking incase print button still display in the module
      //   $config['params']['trno'] = $config['params']['dataid'];
      //   $locked = $this->othersClass->islocked($config);
      //   if ($locked) {
      //     $this->logger->sbcviewreportlog($config, "Trying to print locked DR");
      //     $str = app($this->companysetup->getreportpath($config['params']))->notallowtoprint($config, "Not allowed to print locked DR");
      //   } else {
      //     $this->logger->sbcviewreportlog($config);
      //     $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
      //     $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
      //   }
      //   break;

      case 37: // mega crystal
        $config['params']['trno'] = $config['params']['dataid'];
        $isposted = $this->othersClass->isposted($config);
        if (!$isposted) {
          $this->logger->sbcviewreportlog($config, "Transaction not posted.");
          $str = app($this->companysetup->getreportpath($config['params']))->notallowtoprint($config, "Transaction not posted.");
        } else {
          $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
          $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        }
        break;


      default:
        if ($companyid == 12) { //afti usd
        } else {
          $this->logger->sbcviewreportlog($config);
        }
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        break;
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }

  private function autocreatestock($config, $data2, $trno)
  {
    $wh = $data2['wh'];
    $rows = [];
    $msg = '';
    $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      ((stock.iss-(stock.qa+stock.sjqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end) as isqty,stock.projectid,stock.sgdrate
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa) and stock.void = 0 and stock.trno=? order by stock.line
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      foreach ($data as $key2 => $value) {
        $config['params']['data']['uom'] = $data[$key2]->uom;
        $config['params']['data']['itemid'] = $data[$key2]->itemid;
        $config['params']['trno'] = $config['params']['trno'];
        $config['params']['data']['disc'] = $data[$key2]->disc;
        $config['params']['data']['qty'] = $data[$key2]->isqty;
        $config['params']['data']['ext'] = $data[$key2]->ext;
        $config['params']['data']['wh'] = $wh;
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $data[$key2]->trno;
        $config['params']['data']['linex'] = $data[$key2]->line;
        $config['params']['data']['ref'] = $data[$key2]->docno;
        $config['params']['data']['amt'] = $data[$key2]->isamt;
        $config['params']['data']['projectid'] = $data[$key2]->projectid;
        $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
        $return = $this->additem('insert', $config);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $return['row'][0]->trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $return['row'][0]->trno, 'line' => $line]);
            $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
          array_push($rows, $return['row'][0]);
        }
      }
      return ['row' => $rows, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
    }
  }

  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $companyid = $config['params']['companyid'];
    $deci = $this->companysetup->getdecimal('price', $config['params']);
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = $this->othersClass->sanitizekeyfield('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])));
      if ($companyid == 10) { //afti
        if ($data[$key]->disc != "") {
          $computedata = $this->othersClass->computestock(
            $damt * $head['forex'],
            $data[$key]->disc,
            $dqty,
            $data[$key]->uomfactor,
            0,
            '',
            0,
            1
          );
          $computedata['amt']  = number_format($computedata['amt'], 2, '.', '');
        } else {
          $computedata = $this->othersClass->computestock(
            $damt * $head['forex'],
            $data[$key]->disc,
            $damt,
            $data[$key]->uomfactor,
            0
          );
          $computedata['amt']  = number_format($computedata['amt'], $deci, '.', '');
        }

        $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);

        $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      } else {
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
    }
    return $exec;
  }

  public function getserialout($config)
  {
    $dinsert = [];
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    foreach ($config['params']['rows'] as $key => $value) {
      $dinsert['trno'] = $trno;
      $dinsert['line'] = $config['params']['rows'][$key]['stockline'];
      $dinsert['serial'] = $config['params']['rows'][$key]['serial'];
      $sline = $config['params']['rows'][$key]['sline'];
      if ($companyid == 40) { //cdo
        $dinsert['chassis'] = $config['params']['rows'][$key]['chassis'];
        $dinsert['color'] = $config['params']['rows'][$key]['color'];
        $dinsert['pnp'] = $config['params']['rows'][$key]['pnp'];
        $dinsert['csr'] = $config['params']['rows'][$key]['csr'];
      }
      $outline = $this->coreFunctions->insertGetId('serialout', $dinsert);
      if ($outline != 0) {
        $qry = "update serialin set outline=? where sline=? and outline=0";
        $this->coreFunctions->execqry($qry, 'update', [$outline, $sline]);
      }
      $stockline = $config['params']['rows'][$key]['stockline'];
    }

    $data = $this->openstock($trno, $config);
    return ['status' => true, 'reloadgriddata' => true, 'msg' => 'Serial has been added.', 'griddata' => ['inventory' => $data]];
  } //end function  

  public function getposummaryqry($config)
  {
    return "
        select head.doc,head.docno, head.client, head.clientname, head.address, ifnull(head.rem,'') as hrem, head.cur, head.forex, head.shipto, head.ourref, head.yourref, head.projectid, head.terms,
        item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.cost, (stock.qty-stock.sjqa) as qty,stock.rrcost,stock.ext, head.wh,
        round((stock.qty-stock.sjqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,stock.rem as rem,
        stock.disc,stock.stageid,head.branch,head.billcontactid,head.shipcontactid,head.billid,head.shipid,head.tax,head.vattype,head.yourref,head.deptid,stock.sgdrate,wh.client as swh,stock.loc,
        head.ewt,head.ewtrate,head.wh,hwh.clientid as whid,
        stock.projectid as stock_projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        FROM hpohead as head 
        left join hpostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as wh on wh.clientid=stock.whid left join client as hwh on hwh.client = head.wh
        where stock.trno = ? and stock.qty>stock.sjqa and stock.void=0 ";
  }

  public function getposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $cl = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $rows = [];
    $config['params']['client'] = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
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
          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['porefx'] = $data[$key2]->trno;
          $config['params']['data']['polinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['agentamt'] = 0;
          $config['params']['barcode'] = $data[$key2]->barcode;
          $iamt = $this->coreFunctions->getfieldvalue("item", "amt", "itemid=?", [$data[$key2]->itemid], '', true);
          $config['params']['data']['amt'] = $iamt;

          $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$cl]);

          if ($pricegrp != "") {
            $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
            $iamt = $this->coreFunctions->opentable("select " . $pricefield['amt'] . " as amt, " . $pricefield['disc'] . " as disc, uom, itemid from item where itemid=?", [$data[$key2]->itemid]);
            if (!empty($iamt)) {
              $config['params']['data']['amt'] = $iamt[0]->amt;
              $config['params']['data']['disc'] = $iamt[0]->disc;
            }
          }
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedpoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedpoitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
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
    $config['params']['client'] = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $cl = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $msg = '';

    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.doc,head.docno, head.rem as hrem, item.itemid,stock.trno,stock.rem,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.sjqa) as qty,stock.rrcost,stock.ext,
        round((stock.qty-stock.sjqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.stageid,head.yourref,head.terms,head.cur,head.forex,stock.loc,
        head.vattype,head.tax,head.ourref,head.ewt,head.ewtrate,head.wh,wh.clientid as whid,
        stock.projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        FROM hpohead as head 
        left join hpostock as stock on stock.trno=head.trno 
        left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join client as wh on wh.client = head.wh where stock.trno = ? and stock.line=? and stock.qty>stock.sjqa and stock.void=0";
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
          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['porefx'] = $data[$key2]->trno;
          $config['params']['data']['polinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['agentamt'] = 0;
          $config['params']['barcode'] = $data[$key2]->barcode;
          $iamt = $this->coreFunctions->getfieldvalue("item", "amt", "itemid=?", [$data[$key2]->itemid], '', true);
          $config['params']['data']['amt'] = $iamt;

          $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$cl]);

          if ($pricegrp != "") {
            $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
            $iamt = $this->coreFunctions->opentable("select " . $pricefield['amt'] . " as amt, " . $pricefield['disc'] . " as disc, uom, itemid from item where itemid=?", [$data[$key2]->itemid]);
            if (!empty($iamt)) {
              $config['params']['data']['amt'] = $iamt[0]->amt;
              $config['params']['data']['disc'] = $iamt[0]->disc;
            }
          }
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedpoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedpoitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function
} //end class