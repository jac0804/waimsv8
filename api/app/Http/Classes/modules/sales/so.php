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
use Exception;

class so
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SALES ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $statlogs = 'transnum_stat';
  public $head = 'sohead';
  public $hhead = 'hsohead';
  public $stock = 'sostock';
  public $hstock = 'hsostock';

  public $infohead = 'headinfotrans';
  public $hinfohead = 'hheadinfotrans';
  public $infostock = 'stockinfotrans';
  public $hinfostock = 'hstockinfotrans';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = [
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
    'agent',
    'creditinfo',
    'projectid',
    'ms_freight',
    'mlcp_freight',
    'shipto',
    'sano',
    'pono',
    'salestype',
    'statid',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid',
    'tax',
    'vattype'
  ];

  private $infoheadfields = ['trnxtype', 'approvalreason'];

  public $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
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
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 152,
      'edit' => 153,
      'new' => 154,
      'save' => 155,
      // 'change' => 156, remove change doc
      'delete' => 157,
      'print' => 158,
      'lock' => 159,
      'unlock' => 160,
      'changeamt' => 161,
      'crlimit' => 162,
      'changedisc' => 4037,
      'post' => 163,
      'unpost' => 164,
      'additem' => 805,
      'edititem' => 806,
      'deleteitem' => 807,
      'postnoncash' => 2995,
      'whinfo' => 3889,
      'approved' => 3890,
      'revision' => 3891
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 29) {
      $getcols = ['action', 'liststatus', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'rem',  'terms', 'shipto', 'yourref', 'ourref', 'ext', 'lockdate', 'listpostedby', 'postdate', 'listcreateby', 'createdate', 'listeditby', 'listviewby'];
    } else {
      $getcols = ['action', 'liststatus', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'terms', 'shipto', 'yourref', 'ourref', 'rem', 'ext', 'lockdate', 'listpostedby', 'postdate', 'listcreateby', 'createdate', 'listeditby', 'listviewby'];
    }


    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];

    if (!$this->companysetup->linearapproval($config['params'])) {
      unset($this->showfilterlabel[1]);
      unset($this->showfilterlabel[2]);
    }
    switch ($companyid) {
      case 19: //housegem
        $this->showfilterlabel = [
          ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
          ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
          ['val' => 'locked', 'label' => 'Approved', 'color' => 'primary'],
          ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
          ['val' => 'pending', 'label' => 'Pending', 'color' => 'primary'],
          ['val' => 'complete', 'label' => 'Complete', 'color' => 'primary'],
          ['val' => 'all', 'label' => 'All', 'color' => 'primary']
        ];
        break;
      case 59: //roosevelt
        $this->showfilterlabel = [
          ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
          ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
          ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
          ['val' => 'pending', 'label' => 'Pending', 'color' => 'primary'],
          ['val' => 'complete', 'label' => 'Complete', 'color' => 'primary'],
          ['val' => 'all', 'label' => 'All', 'color' => 'primary']
        ];
        break;
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$liststatus]['name'] = 'statuscolor';

    switch ($config['params']['companyid']) {
      case 19: //housegem
      case 22: //eipi
        $cols[$yourref]['label'] = 'PO #';
        $cols[$ext]['style'] = 'text-align:right; width:100px;whiteSpace: normal;min-width:100px;';
        break;
      case 28: //xcomp
        $cols[$ext]['label'] = 'Total Amount';
        $cols[$ext]['align'] = 'text-right';
        break;
      case 47: //kstar
        $cols[$rem]['type'] = 'coldel';
        break;
      case 29: //sbc
        $cols[$rem]['type'] = 'label';
        $cols[$rem]['style'] = 'width:320px;whiteSpace: normal;min-width:320px;';
        break;
      default:
        $cols[$ext]['type'] = 'coldel';
        $cols[$rem]['type'] = 'coldel';
        break;
    }

    if ($companyid != 22) { //not eipi
      $cols[$shipto]['type'] = 'coldel';
    }

    if ($companyid != 21) { //not kinggeorge
      $cols[$terms]['type'] = 'coldel';
    }

    if ($config['params']['companyid'] == 19) { //housegem
      $cols[$liststatus]['type'] = 'coldel';
      $cols[$postdate]['type'] = 'input';
      $cols[$postdate]['label'] = 'Post Date';
      $cols[$lockdate]['label'] = 'Approved Date';
    } else {
      $cols[$lblstatus]['type'] = 'coldel';
      $cols[$postdate]['type'] = 'coldel';
      $cols[$createdate]['type'] = 'coldel';
      $cols[$lockdate]['type'] = 'coldel';
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = [];
    $col1 = [];
    $allownew = $this->othersClass->checkAccess($config['params']['user'], 154);
    if ($allownew == '1') $fields = ['pickpo'];
    if ($config['params']['companyid'] == 39) { //cbbsi
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'pickpo.label', 'pick quotation');
      data_set($col1, 'pickpo.lookupclass', 'pendingqtsummaryshortcut');
      data_set($col1, 'pickpo.action', 'pendingqtsummary');
      data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick Quotation?');
      data_set($col1, 'pickpo.addedparams', ['docno', 'selectprefix']);
    }

    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
  }


  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $laext = '';
    $glext = '';

    $join = '';
    $hjoin = '';
    $addparams = '';

    $lfield = '';
    $gfield = '';
    $ljoin = '';
    $gjoin = '';
    $group = '';

    $user = $config['params']['user'];
    $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);

    $ustatus = "'Pending'";
    if ($companyid == 19) { //housegem
      $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5347);

      $orderby = "order by docno desc, dateid desc";
      $dateid = "left(head.dateid,10) as dateid";
      $status = "stat.status";
      $ustatus = "'Unposted'";

      if ($viewaccess == '0') {
        $condition .= " and head.createby = '" . $user . "'";
      }
    } elseif ($companyid == 47) { //kitchenstar
      $orderby = "order by docno desc";
    } elseif ($companyid == 59) { //roosevelt
      $status = "stat.status";
      $orderby = "order by dateid desc, docno desc";
    } else {
      $orderby = "order by dateid desc, docno desc";
    }


    $searchfilter = $config['params']['search'];
    $usrfilter = "";
    $lscolor = "'red'";
    $lstatus = " case ifnull(head.lockdate,'') when '' then  'DRAFT' else 'LOCKED' end";
    if ($this->companysetup->linearapproval($config['params'])) {
      $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : $itemfilter;

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
      $usrfilter = " and num.appuser='" . $config['params']['user'] . "' ";
      $lstatus = " case when head.lockdate is not null then 'LOCKED' when num.postdate is null and head.lockdate is null and num.statid=10 then 'FOR APPROVAL' 
                   when num.postdate is null and num.statid=36 then 'APPROVED' else 'DRAFT' end";
      $lscolor = " case when num.postdate is null and num.statid=36 or num.statid=10 then 'grey' when head.lockdate is not null then 'green' else 'red' end ";
    }

    $leftjoin = "";
    $leftjoin_posted = "";
    switch ($itemfilter) {
      case 'draft':
        $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=0';
        if ($companyid == 19) { //housegem
          $condition .= ' and num.postdate is null and head.lockdate is null and num.statid in (0,16)';
          $ustatus = "ifnull(stat.status,'Unposted')";
        }
        break;

      case 'pending':
        $leftjoin = ' left join sostock as stock on stock.trno=head.trno';
        $leftjoin_posted = ' left join hsostock as stock on stock.trno=head.trno';
        $condition .= ' and stock.iss>stock.qa and stock.void=0 and num.postdate is not null ';
        break;

      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        $ustatus = "'Locked'";
        if ($companyid == 19) { //housegem
          $ustatus = "'Approved'";
          $condition .= ' and num.statid=36';
        }
        break;

      case 'forapproval':
        $ustatus = "'For Approval'";
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=10 $usrfilter ";
        break;

      case 'approved':
        $ustatus = "'Approved'";
        $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=36';
        break;

      case 'doneloading':
        $ustatus = "'Done Loading'";
        $condition .= ' and num.postdate is null and head.lockdate is not null and num.statid=39';
        break;

      case 'complete':
        $condition .= ' and num.statid = 7 ';
        break;

      case 'posted':
        $condition .= ' and num.postdate is not null ';
        break;
    }

    $limit = "limit 150";

    // replace multisearch
    if (isset($searchfilter)) {
      if ($searchfilter != '') {
        $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.rem'];

        if ($searchfilter != "") {
          $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
        }
      }
    }

    switch ($companyid) {
      case 28: //xcomp
        $lfield = ',format(sum(stock.ext),2) as ext,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar,head.rem';
        $gfield = ',format(sum(stock.ext),2) as ext,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar,head.rem';
        $ljoin = 'left join ' . $this->stock . ' as stock on stock.trno=head.trno';
        $gjoin = 'left join ' . $this->hstock . ' as stock on stock.trno=head.trno';
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,head.createdate,num.postdate,
        head.yourref, head.ourref,head.rem,head.lockdate,head.shipto';
        break;
      case 47: //kstar
        $lfield = ", (select FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") 
        from sostock as stock where stock.trno= head.trno) as ext";
        $gfield = ",(select FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") 
        from hsostock as stock where stock.trno= head.trno) as ext";

        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,head.createdate,num.postdate,
        head.yourref, head.ourref,head.rem,head.lockdate';
        break;
      // case 22: //eipi
      //   $lfield = ',head.shipto';
      //   $gfield = ',head.shipto';
      //   break;
      case 29: //sbc
        $lfield = ',head.rem';
        $gfield = ',head.rem';
        break;
    }

    // $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 
    //               " . $lstatus . " as status,head.createby,head.editby,head.viewby,
    //               num.postedby,head.createdate,num.postdate,
    //               head.yourref, head.ourref,case ifnull(head.lockdate,'') when '' 
    //               then $lscolor else 'green' end as statuscolor,head.shipto,head.terms  $lfield
    //         from " . $this->head . " as head 
    //         left join " . $this->tablenum . " as num on num.trno=head.trno 
    //         $ljoin
    //         where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? 
    //               and CONVERT(head.dateid,DATE)<=? " . $condition . " 
    //         $group
    //         union all
    //         select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
    //                head.createby,head.editby,head.viewby, num.postedby,head.createdate,num.postdate,
    //                head.yourref, head.ourref,'grey' as statuscolor,head.shipto,head.terms $gfield
    //         from " . $this->hhead . " as head 
    //         left join " . $this->tablenum . " as num on num.trno=head.trno 
    //         $gjoin
    //         where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? 
    //               and CONVERT(head.dateid,DATE)<=? " . $condition . " 
    //         $group
    //         $orderby " . $limit;

    $companyid = $config['params']['companyid'];

    // if ($config['params']['companyid'] == 19) { //housegem
    switch ($companyid) {
      case 19: //housegem
        $laext = ", (select FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") 
       from sostock as stock where stock.trno= head.trno) as ext";
        $glext = ", (select FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") 
       from hsostock as stock where stock.trno= head.trno) as ext";

        $qry = "select head.trno,head.docno,head.clientname,$dateid," . $ustatus . " as stat,head.createby,head.editby,head.viewby,num.postedby, num.postdate,head.yourref, head.ourref,head.createdate,head.lockdate
       $laext
       from " . $this->head . " as head 
       left join " . $this->tablenum . " as num on num.trno=head.trno 
       left join trxstatus as stat on stat.line=num.statid 
       " . $leftjoin . "
       " . $join . "
       where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . "
       group by head.trno, head.docno, head.clientname, head.dateid, stat.status,head.createby, head.editby, head.viewby, num.postedby,num.postdate, head.createdate,head.lockdate,head.yourref, head.ourref,stat.line,num.statid
       union all
       select head.trno,head.docno,head.clientname,$dateid," . $status . " as stat,head.createby,head.editby,head.viewby, num.postedby, num.postdate,head.yourref, head.ourref,head.createdate,head.lockdate 
       $glext
       from " . $this->hhead . " as head 
       left join " . $this->tablenum . " as num on num.trno=head.trno 
       left join trxstatus as stat on stat.line=num.statid 
       " . $leftjoin_posted . "
       " . $hjoin . "
       where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . "
       group by head.trno, head.docno, head.clientname, head.dateid, stat.status, head.createby, head.editby, head.viewby, num.postedby, num.postdate, head.createdate,head.lockdate,head.yourref, head.ourref,stat.line,num.statid
      $orderby $limit";
        break;

      case 59: //roosevelt
        $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 
                  " . $lstatus . " as status,head.createby,head.editby,head.viewby,
                  num.postedby,head.createdate,num.postdate,
                  head.yourref, head.ourref,case ifnull(head.lockdate,'') when '' 
                  then $lscolor else 'green' end as statuscolor,head.shipto,head.terms  $lfield
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
             left join trxstatus as stat on stat.line=num.statid 
              " . $leftjoin . "
            $ljoin
            where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " 
            $group
            union all
            select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid," . $status . " as status,
                   head.createby,head.editby,head.viewby, num.postedby,head.createdate,num.postdate,
                   head.yourref, head.ourref,'grey' as statuscolor,head.shipto,head.terms $gfield
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
             left join trxstatus as stat on stat.line=num.statid 
               " . $leftjoin_posted . "
            $gjoin
            where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " 
            $group
            $orderby " . $limit;
        break;

      default:
        $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 
                  " . $lstatus . " as status,head.createby,head.editby,head.viewby,
                  num.postedby,head.createdate,num.postdate,
                  head.yourref, head.ourref,case ifnull(head.lockdate,'') when '' 
                  then $lscolor else 'green' end as statuscolor,head.shipto,head.terms  $lfield
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            $ljoin
            where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " 
            $group
            union all
            select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
                   head.createby,head.editby,head.viewby, num.postedby,head.createdate,num.postdate,
                   head.yourref, head.ourref,'grey' as statuscolor,head.shipto,head.terms $gfield
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            $gjoin
            where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " 
            $group
            $orderby " . $limit;
        break;
    }

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


    switch ($config['params']['companyid']) {
      case 19: //housegem
        $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Retail Request Order', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
        break;
      case 56: //homeworks
        $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
        $buttons['others']['items']['downloadexcel'] = ['label' => 'Download SO Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
        break;
    }

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'so', 'title' => 'SO_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }


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

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['creditinfo'];
    $col1 = $this->fieldClass->create($fields);
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    $so_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3593);
    $whinfo = $this->othersClass->checkAccess($config['params']['user'], 3889);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $changedisc = $this->othersClass->checkAccess($config['params']['user'], 4037);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451); //kinggeorge

    switch ($companyid) {
      case 47: //kitchenstar
        $column = ['action', 'isqty', 'uom', 'kgs', 'weight', 'isamt', 'disc', 'ext', 'fstatus', 'itemdesc', 'wh', 'rem', 'loc', 'qa', 'roqa', 'void', 'ref', 'itemname', 'noprint', 'barcode'];
        $sortcolumn = ['action',  'itemdesc', 'isqty', 'uom', 'kgs', 'weight', 'isamt', 'disc', 'ext', 'fstatus', 'wh', 'rem', 'loc', 'qa', 'roqa', 'void', 'ref', 'itemname', 'noprint', 'barcode'];
        break;
      case 59: //roosevelt
        $column = ['action', 'barcode', 'isqty', 'uom', 'itemname', 'kgs', 'weight', 'isamt', 'disc', 'ext', 'fstatus', 'wh', 'rem', 'loc', 'qa', 'void', 'ref'];
        $sortcolumn = ['action', 'barcode', 'isqty', 'uom', 'itemname', 'kgs', 'weight', 'isamt', 'disc', 'ext', 'fstatus', 'wh', 'rem', 'loc', 'qa', 'void', 'ref'];
        break;
      default:
        $column = ['action', 'isqty', 'uom', 'kgs', 'weight', 'isamt', 'disc', 'agentamt', 'ext', 'fstatus', 'wh', 'rem', 'loc', 'qa', 'roqa', 'void', 'ref', 'itemname', 'noprint', 'barcode'];
        $sortcolumn = ['action', 'isqty', 'uom', 'kgs', 'weight', 'isamt', 'disc', 'agentamt', 'ext', 'fstatus', 'wh', 'rem', 'loc', 'qa', 'roqa', 'void', 'ref', 'itemname', 'noprint', 'barcode'];
        break;
    }

    switch ($systemtype) {
      case 'REALESTATE':
        $project = 18;
        $phasename = 19;
        $housemodel = 20;
        $blk = 21;
        $lot = 22;
        $amenityname = 23;
        $subamenityname = 24;
        array_push($column, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        array_push($sortcolumn, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram'];

    if ($so_btnvoid_access == 0) {
      unset($headgridbtns[0]);
    }
    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

    if ($iskgs) {
      $computefield['kgs'] = 'kgs';
    }

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    foreach ($sortcolumn as $key => $value) {
      $$value = $key;
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ],
      'multiinput1' => ['inputcolumn' => ['col1' => $col1], 'label' => 'CREDIT INFO'],

    ];
    if ($companyid == 19) { //housegem
      if ($whinfo) {
        $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'viewotherinfo', 'access' => 'view'], 'label' => 'Delivery Details'];
      }
      $tab['remtab'] = ['action' => 'vehiclescheduling', 'lookupclass' => 'tabrem', 'label' => 'REVISION REMARKS HISTORY'];
    } else if ($companyid == 39) { //cbbsi
      $tab['customform'] = ['action' => 'customform', 'lookupclass' => '', 'label' => 'Dispatch', 'event' => ['label' => 'Dispatch', 'icon' => 'batch_prediction', 'class' => 'btndispatchinfo', 'lookupclass' => 'viewdispatchinfo', 'action' => 'customform', 'access' => 'view']];
    }

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

    // $stockbuttons = ['save', 'delete', 'showbalance'];

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }
    switch ($systemtype) {
      case 'AIMS':
        switch ($companyid) {
          case 0: //main
          case 15: //nathina
          case 28: //xcomp
            array_push($stockbuttons, 'stockinfo');
            break;
        }
        break;

      case 'AIMSPAYROLL': //XCOMP
        array_push($stockbuttons, 'stockinfo');
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Selling Kgs';
    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    switch ($companyid) {
      case 47: //kitchenstar
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'input';
        $obj[0]['inventory']['columns'][$itemdesc]['label'] = 'Item Description';
        $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = false;
        $obj[0]['inventory']['columns'][$weight]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$roqa]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$agentamt]['type'] = 'coldel';
        break;
      case 24: //goodfound
        $obj[0]['inventory']['columns'][$qa]['label'] = 'Served Qty';
        $obj[0]['inventory']['columns'][$weight]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$roqa]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$noprint]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$agentamt]['type'] = 'coldel';
        break;
      case 2: //MIS
        $obj[0]['inventory']['columns'][$ref]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
        $obj[0]['inventory']['columns'][$weight]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$roqa]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$noprint]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$agentamt]['type'] = 'coldel';
        break;
      case 19: //housegem
        $obj[0]['inventory']['columns'][$rem]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:300px;max-width:450px;';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$isqty]['style'] = 'text-align: right; width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0]['inventory']['columns'][$weight]['label'] = 'Estimated Weight';
        $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$noprint]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$agentamt]['type'] = 'coldel';
        break;
      case 59: //roosevelt
        $obj[0]['inventory']['columns'][$rem]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:300px;max-width:450px;';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'textarea';

        $obj[0]['inventory']['columns'][$weight]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemname]['type'] = 'label';
        $obj[0]['inventory']['columns'][$itemname]['label'] = 'Itemname';

        $obj[0]['inventory']['columns'][$barcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$barcode]['style'] = 'text-align: left; width: 125px;whiteSpace: normal;min-width:125px;max-width:125px;';

        $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
        $obj[0]['inventory']['descriptionrow'] = [];
        $this->modulename = 'ORDER FORM';
        break;
      default:
        $obj[0]['inventory']['columns'][$weight]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$roqa]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$noprint]['type'] = 'coldel';

        if ($companyid == 59) { //roosevelt
          $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
          $this->modulename = 'ORDER FORM';
        }

        if ($companyid != 60) {
          $obj[0]['inventory']['columns'][$agentamt]['type'] = 'coldel';
        }

        break;
    }

    if ($iscreateversion) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
    } else {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ref]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
    }

    if ($companyid != 59) { //roosevelt
      $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
      $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    }

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      if ($companyid != 21) { //not kinggeorge
        $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
      }
    }

    if (!$access['changedisc']) { //based on attribute set in left_menu
      switch ($companyid) {
        case 21: //kingg
        case 28: //xcomp
        case 36: //rozlab
          $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
      }
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
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    if ($iscreateversion) {
      $tbuttons = ['pendingqt', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    } else {
      switch ($companyid) {
        case 3: //conti
          $tbuttons = ['pendingqt', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
          break;
        case 11: //summit
          $tbuttons = ['eggitems', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
          break;
        case 39: //cbbsi
          $tbuttons = ['pendingqt', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
          break;
        default:
          $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
          break;
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $override = $this->othersClass->checkAccess($config['params']['user'], 1729);
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4850);
    // col 1
    $fields = ['docno', 'client', 'clientname', 'address'];
    switch ($companyid) {
      case 15: //nathina
        array_push($fields, 'shipto');
        break;
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');

    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'shipto.type', 'ctextarea');

    if ($companyid == 19) { //housegem
      data_set($col1, 'address.type', 'lookup');
      data_set($col1, 'address.lookupclass', 'lookupaddress');
      data_set($col1, 'address.action', 'lookupaddress');
      data_set($col1, 'address.class', 'sbccsreadonly');
      data_set($col1, 'address.addedparams', ['client']);
    }
    if ($companyid == 39) { //cbbsi
      data_set($col1, 'address.type', 'ctextarea');
      data_set($col1, 'address.maxlength', 150);
    }

    // col 2
    $fields = [['dateid', 'terms'], 'due', 'dwhname', 'dagentname'];
    switch ($companyid) {
      case 15: //nathina
        array_push($fields, 'mlcp_freight', 'ms_freight');
        break;
      case 19: //housegem
        array_push($fields, 'tmpref');
        break;
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'ms_freight.label', 'Other Charges');
    switch ($companyid) {
      case 24: //goodfound
        data_set($col2, 'due.label', 'Validity');
        break;
      case 19: //housegem
        data_set($col2, 'tmpref.label', 'SO Duplicate Ref.');
        data_set($col2, 'tmpref.readonly', true);
        data_set($col2, 'tmpref.class', 'sbccsreadonly');
        break;
      case 40: //cdo
        if ($noeditdate) {
          data_set($col2, 'dateid.class', 'sbccsreadonly');
        }
        break;
    }

    // col 3
    switch ($companyid) {
      case 22: //eipi
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'shipto'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'yourref.label', 'PO #');
        data_set($col3, 'shipto.label', 'Delivered To');
        break;

      case 19: //housegem
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'shipto'];
        if ($this->companysetup->getcompanyname($config['params'] == 'HOUSEGEM')) {
          array_push($fields, ['totalestweight', 'totalactualweight']);
        }
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'yourref.label', 'PO #');
        data_set($col3, 'shipto.type', 'lookup');
        data_set($col3, 'shipto.action', 'lookupwshipping');
        data_set($col3, 'shipto.label', 'Delivered To');

        break;

      case 21: //kinggeorge
        $fields = [['yourref', 'ourref'], ['cur', 'forex']];
        $col3 = $this->fieldClass->create($fields);
        break;

      case 16: //ati
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], ['sadesc', 'podesc']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'podesc.lookupclass', 'lookuppodesc');
        break;

      case 24: //goodfound
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'statname'];
        $col3 = $this->fieldClass->create($fields);
        break;
      case 39: //cbbsi
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'trnxtype'];
        $col3 = $this->fieldClass->create($fields);
        break;
      case 59:
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'shipto'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'shipto.type', 'input');
        data_set($col3, 'shipto.label', 'Delivered To');
        break;
      case 29:
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'dvattype'];
        $col3 = $this->fieldClass->create($fields);
        break;
      default:
        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname'];
        if ($systemtype == 'REALESTATE') {
          $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'rem'];
        }
        $col3 = $this->fieldClass->create($fields);
        break;
    }

    if ($companyid == 24) { //goodfound
      data_set($col3, 'statname.label', 'Type');
      data_set($col3, 'statname.lookupclass', 'lookup_sjtype');
    }
    if ($companyid == 22) { //eipi
      data_set($col3, 'ourref.label', 'Charge SI #');
    }

    if ($companyid == 47) { //kitchenstar
      data_set($col3, 'yourref.label', 'PO #');
    }

    // col 4
    $fields = ['rem'];
    switch ($systemtype) {
      case 'MANUFACTURING':
        array_push($fields, 'sotype');
        break;
      case 'REALESTATE':
        $fields = ['dprojectname', 'phase', 'housemodel', ['blklot', 'lot'], 'amenityname', 'subamenityname'];
        break;
    }

    if ($this->companysetup->getistodo($config['params'])) array_push($fields, 'donetodo');

    switch ($companyid) {
      case 19: //housegem
        array_push($fields, 'forapproval', 'doneapproved', 'forrevision', 'duplicatedoc');
        break;
      case 39: //cbbsi
        array_push($fields, 'approvalreason', 'rem2');
        break;

      default:
        if ($this->companysetup->linearapproval($config['params'])) {
          array_push($fields, 'forapproval', 'doneapproved', 'lblapproved');
        }
        break;
    }

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.required', false);
    if ($companyid == 39) { //cbbsi
      if ($override == '0') {
        data_set($col4, 'approvalreason.class', 'sbccsreadonly');
      }
      data_set($col4, 'rem2.label', 'Disapproval Reason');
      data_set($col4, 'rem2.class', 'sbccsreadonly');
    }

    data_set($col4, 'lblapproved.type', 'label');
    data_set($col4, 'lblapproved.label', 'APPROVED!');
    data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');

    if ($systemtype == 'REALESTATE') {
      data_set($col4, 'dprojectname.lookupclass', 'project');
      data_set($col4, 'phase.addedparams', ['projectid']);
      data_set($col4, 'housemodel.addedparams', ['projectid']);
      data_set($col4, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
      data_set($col4, 'subamenityname.addedparams', ['amenityid']);
    }

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
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
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';
    $data[0]['dagentname'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['address'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['rem2'] = '';

    if ($params['companyid'] == 24) { //goodfound
      $data[0]['wh'] = 'WH0000000000002';
    } else {
      $data[0]['wh'] = $this->companysetup->getwh($params);
    }
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;

    if ($params['companyid'] == 39) { //CBBSI
      $data[0]['projectcode'] = '';
      $data[0]['projectid'] = 0;
      $data[0]['projectname'] = '';

      $data[0]['projectcode'] = $this->companysetup->getproject($params);
      if ($data[0]['projectcode'] != '') {
        $projid = $this->coreFunctions->datareader("select ifnull(line,0) as value from projectmasterfile where code='" . $data[0]['projectcode'] . "'");
        $projname = $this->coreFunctions->datareader("select ifnull(name,'') as value from projectmasterfile where code='" . $data[0]['projectcode'] . "'");
        $data[0]['projectid'] = $projid;
        $data[0]['projectname'] = $projname;
      }
    } else {
      $data[0]['projectcode'] = '';
      $data[0]['projectid'] = 0;
      $data[0]['projectname'] = '';
    }
    $data[0]['ms_freight'] = '0.00';
    $data[0]['mlcp_freight'] = '';
    $data[0]['sotype'] = 0;
    $data[0]['isro'] = '0';

    $data[0]['sano'] = '0';
    $data[0]['pono'] = '0';
    $data[0]['sadesc'] = '';
    $data[0]['podesc'] = '';
    $data[0]['salestype'] = '';
    $data[0]['statid'] = '0';

    $data[0]['trnxtype'] = '';
    $data[0]['approvalreason'] = '';

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

    $data[0]['dvattype'] = '';
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    return $data;
  }

  public function openhead($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $tablenum = $this->tablenum;

    $table = $this->head;
    $htable = $this->hhead;
    $info = $this->infohead;
    $hinfo = $this->hinfohead;

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
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address,  head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.createby,
         head.rem,
         ifnull(head.agent, '') as agent, 
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, 
         client.groupid,head.creditinfo,
         head.sano, ifnull(sa.sano,'') as sadesc,
         head.pono,ifnull(po.sano,'') as podesc,
         head.projectid,ifnull(project.code,'') as projectcode,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,head.ms_freight,head.mlcp_freight,num.statid as numstatid,cast(ifnull(info.isro,0) as char) as isro, head.sotype, head.salestype,
         head.statid, ifnull(stat.status,'') as statname,ifnull(info.trnxtype,'') as trnxtype,ifnull(info.approvalreason,'') as approvalreason,ifnull(info.rem2,'') as rem2,
             head.phaseid, ps.code as phase,  head.modelid, hm.model as housemodel, head.blklotid, info.driverid, info.checkerid, info.truckid, info.helperid, info.plateno,
            bl.blk as blklot,  bl.lot, amen.line as amenityid, amen.description as amenityname, 
            subamen.line as subamenityid, subamen.description as subamenityname, info.tmpref,
             head.tax,
             head.vattype,
             '' as dvattype";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid
        left join $info as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono
        left join trxstatus as stat on stat.line = head.statid 

        left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid
    
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid
        left join $hinfo as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono  
        left join trxstatus as stat on stat.line = head.statid 
         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid
        where head.trno = ? and num.center=? ";
    return
      $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $tablenum = $this->tablenum;
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

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);

    $head = $this->openhead($config);

    if (!empty($head)) {

      if ($config['params']['companyid'] == 19) { //housegem
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5347);
        if ($viewaccess == '0') {
          if ($head[0]->createby != $config['params']['user']) {
            $head[0]->trno = 0;
            $head[0]->docno = '';
            return ['status' => false, 'isnew' => false, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Sales Order was created by another user.'];
          }
        }
      }


      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }


      if ($config['params']['companyid'] == 19) { //housegem
        $estqry = "select sum(totalestweight) as value from (select stock.weight * stock.isqty as totalestweight from $this->stock as stock where stock.trno =?
      union all select stock.weight * stock.isqty as totalestweight from $this->hstock as stock where stock.trno = ?) as a";
        $gptotalestweight = round($this->coreFunctions->datareader($estqry, [$head[0]->trno, $head[0]->trno]), 2);
        $head[0]->totalestweight = number_format($gptotalestweight, $this->companysetup->getdecimal('price', $config['params']));

        $actqry = "select sum(totalactualweight) as value from (select stock.weight2 * stock.isqty as totalactualweight from $this->stock as stock where stock.trno =?
      union all select stock.weight2 * stock.isqty as totalactualweight from $this->hstock as stock where stock.trno = ?) as a";
        $gptotalactualweight = round($this->coreFunctions->datareader($actqry, [$head[0]->trno, $head[0]->trno]), 2);
        $head[0]->totalactualweight = number_format($gptotalactualweight, $this->companysetup->getdecimal('price', $config['params']));
      }

      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }

      if ($config['params']['companyid'] == 19) { //housegem
        $allowduplicate = $this->othersClass->checkAccess($config['params']['user'], 4849);
        $hideobj['duplicatedoc'] = !$allowduplicate;

        if ($this->companysetup->getcompanyname($config['params'] == 'HOUSEGEM')) {
          if ($isposted) {
            $hideobj['forapproval'] = true;
            $hideobj['doneapproved'] = true;
            $hideobj['forrevision'] = true;
          } else {
            $hideobj['forapproval'] = false;
            $hideobj['doneapproved'] = true;
            $hideobj['forrevision'] = true;
            switch ($head[0]->numstatid) {
              case 10:
                $hideobj['forapproval'] = true;
                $hideobj['doneapproved'] = false;
                $hideobj['forrevision'] = false;
                break;
              case 16:
                $hideobj['forapproval'] = false;
                $hideobj['doneapproved'] = true;
                $hideobj['forrevision'] = true;
                break;
              case 36:
                $hideobj['forapproval'] = true;
                $hideobj['doneapproved'] = true;
                $hideobj['forrevision'] = false;
                break;
              case 39:
                $hideobj['forapproval'] = true;
                $hideobj['doneapproved'] = true;
                $hideobj['forrevision'] = true;
                break;
            }
          }
        } else {
          $hideobj['forapproval'] = true;
          $hideobj['doneapproved'] = true;
          $hideobj['forrevision'] = true;
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
        'hideobj' => $hideobj
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $data = [];
    $info = [];
    $datahere = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    if ($companyid == 0) { //main
      array_push($this->fields, 'sotype');
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if    
      }
    }

    if ($companyid != 24) { //not goodfound
      if ($data['terms'] == '') {
        $data['due'] =  $data['dateid'];
      } else {
        $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $info = [];
    $info['trno'] = $head['trno'];

    if ($companyid == 39) { //cbbsi
      $info['trnxtype'] = $head['trnxtype'];
      $info['approvalreason'] = $head['approvalreason'];
      $info['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $info['editby'] = $config['params']['user'];
    }

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->othersClass->getcreditinfo($config, $this->head);

      if ($companyid == 39) { //cbbsi
        $exist = $this->coreFunctions->getfieldvalue('headinfotrans', 'trno', 'trno=?', [$head['trno']]);
        if ($exist != 0) {
          $this->coreFunctions->sbcupdate('headinfotrans', $info, ['trno' => $head['trno']]);
        } else {
          $this->coreFunctions->sbcinsert('headinfotrans', $info);
        }
      }

      if ($companyid == 19) { //housegem
        $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$head['client']]);
        $address = isset($data['shipto']) ? trim($data['shipto']) : '';
        $delto = $this->coreFunctions->getfieldvalue("billingaddr", "addr", "addr=?", [$address], '', true);
        if (!$delto) {
          $datahere['addr'] = $address;
          $datahere['clientid'] = $clientid;
          $datahere['isshipping'] = 1;
          $this->coreFunctions->sbcinsert('billingaddr', $datahere);
        }
      }
    } else {

      if (isset($data['agent'])) {
        $inactive = $this->coreFunctions->getfieldvalue("client", "isinactive", "client=?", [$data['agent']], '', true);
        if ($inactive == 1) {
          $data['agent'] = '';
        }
      }

      if ($companyid == 19) { //housegem
        $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$head['client']]);
        $address = isset($data['shipto']) ? trim($data['shipto']) : '';
        $delto = $this->coreFunctions->getfieldvalue("billingaddr", "addr", "addr=?", [$address], '', true);
        if (!$delto) {
          $datahere['addr'] = $address;
          $datahere['clientid'] = $clientid;
          $datahere['isshipping'] = 1;
          $this->coreFunctions->sbcinsert('billingaddr', $datahere);
        }
      }
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->othersClass->getcreditinfo($config, $this->head);

      switch ($companyid) {
        case 19: //housegem
        case 39: //cbbsi
          $this->coreFunctions->sbcinsert('headinfotrans', $info);
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
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
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
    $companyid = $config['params']['companyid'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $crlimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);

    if (floatval($crlimit) == 0) {
      $override = $this->othersClass->checkAccess($config['params']['user'], 1729);
      $cashterms = $this->coreFunctions->getfieldvalue($this->head, "terms", "trno=?", [$trno]);

      $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
      $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
      $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
      $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);

      switch ($companyid) {
        case 39: //cbbsi
          $this->coreFunctions->LogConsole('override:' . $override);
          $approve = true;
          $disrem = '';

          if ($cashterms != 'CASH') {
            if (floatval($crline) < floatval($totalso)) {
              $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Above Credit Line (' . $crline . ')');
              $approve = false;
              if ($disrem <> '') {
                $disrem = $disrem . "/ Above Credit Line (" . $crline . ")";
              } else {
                $disrem = "Above Credit Line (" . $crline . ")";
              }
            } else {
              if (floatval($overdue) <> 0) {
                $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, has overdue accounts of P ' . number_format($overdue, 2));
                $approve = false;
                if ($disrem <> '') {
                  $disrem = $disrem . "/ Has overdue accounts of P " . number_format($overdue, 2);
                } else {
                  $disrem = "Has overdue accounts of P " . number_format($overdue, 2);
                }
              }
            }
          }

          if ($cstatus <> 'ACTIVE') {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Status is not Active');
            $approve = false;
            if ($disrem <> '') {
              $disrem = $disrem . "/ Customer Status is not Active";
            } else {
              $disrem = "Customer Status is not Active";
            }
          }

          $belowcost = $this->coreFunctions->datareader("select ifnull(s.trno,0) as value from " . $this->stock . " as s left join item as i on i.itemid = s.itemid where i.isnoninv =0 and s.amt<i.amt9 and s.trno = ? limit 1", [$trno], '', true);
          if ($belowcost <> 0) {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Price is below Cost');
            $approve = false;
            if ($disrem <> '') {
              $disrem = $disrem . "/ Price is below Cost";
            } else {
              $disrem = "Price is below Cost";
            }
          }

          $belowcost = $this->coreFunctions->datareader("select s.trno as value from " . $this->stock . " as s 
              left join item as i on i.itemid = s.itemid
              left join uom on uom.uom = s.uom and uom.itemid = i.itemid where i.isnoninv =0 and s.amt<(i.amt7-((s.isamt*uom.factor)-s.amt))  and s.trno = ?  limit 1", [$trno], '', true);

          if ($belowcost <> 0) {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Price is below Price(E)');
            $approve = false;
            if ($disrem <> '') {
              $disrem = $disrem . "/ Price is below Price(E)";
            } else {
              $disrem = "Price is below Price(E)";
            }
          }

          $zerocost = $this->coreFunctions->datareader("select s.trno as value from " . $this->stock . " as s left join item as i on i.itemid = s.itemid where i.isnoninv =0 and i.amt9 =0  and s.trno = ? limit 1", [$trno]);
          if ($belowcost <> 0) {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Zero Cost item exist.');
            $approve = false;
            if ($disrem <> '') {
              $disrem = $disrem . "/ Zero Cost item exist";
            } else {
              $disrem = "Zero Cost item exist";
            }
          }

          if ($disrem <> '') {
            $this->coreFunctions->execqry("update " . $this->infohead . " set rem2 = '" . $disrem . "' where trno = " . $trno);
          }

          if ($override == '0') {
            if ($approve == false) {
              return ['status' => false, 'msg' => 'Posting failed. Due to SO disapproval, transaction cannot be posted.'];
            }
          } else {
            $reason = $this->coreFunctions->datareader("select approvalreason as value from " . $this->infohead . " where trno = " . $trno);
            if ($approve == false && $reason == '') {
              return ['status' => false, 'msg' => 'Posting failed. Please provide a reason for approval.'];
            }
          }
          break;
        case 19: //housegem retail not yet done
          $this->coreFunctions->LogConsole('override:' . $override);
          if ($override == '0') {
            $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);
            $iscis = $this->coreFunctions->getfieldvalue("client", "iscis", "client=?", [$client]);
            $crlimitamt = $this->coreFunctions->getfieldvalue("client", "crlimit", "client=?", [$client]);

            if ($iscis == "0") {
              return ['status' => false, 'msg' => 'Posting failed. Incomplete CIS.'];
            }

            if ($crlimitamt == '') {
              $crlimitamt = 0;
            }

            $pendingso = $this->coreFunctions->datareader("
                    select sum(hsostock.ext) as value from hsostock left join hsohead on hsohead.trno = hsostock.trno 
                    where hsohead.client = ? and hsohead.trno<>? and hsostock.qa<>hsostock.iss and hsostock.void=0", [$client, $trno]);
            if ($pendingso == '') {
              $pendingso = 0;
            }
            $this->coreFunctions->LogConsole('pending so:' . $pendingso);

            $ar = $this->coreFunctions->datareader("
                  select ifnull(sum(bal),0) as value from (
                  select sum(case when db<>0 then bal else bal * -1 end) as bal from arledger where bal<>0 and clientid=?
                  union all
                  select sum(ext) from lahead as h left join lastock as s on s.trno=h.trno where h.doc='SJ' and h.client=?) as sj", [$clientid, $client]);
            if ($ar == '') {
              $ar = 0;
            }
            $this->coreFunctions->LogConsole('AR:' . $ar);

            $unpostedcr = $this->coreFunctions->datareader("select ifnull(sum(d.cr-d.db),0) as value from lahead as h left join ladetail as d on d.trno=h.trno where h.doc='CR' and h.client=? and d.isar=1", [$client]);
            if ($unpostedcr == '') {
              $unpostedcr = 0;
            }
            $this->coreFunctions->LogConsole('unposted:' . $unpostedcr);

            $unpostedcrtxt = '';
            if ($unpostedcr != 0) {
              $unpostedcrtxt = ' - Unposted CR: ' . number_format($unpostedcr, 2);
            }

            if ($crlimitamt == 0) {
              $undeposited = $this->coreFunctions->datareader("
                    select ifnull(sum(db),0) as value from (
                    select d.db from lahead as h left join ladetail as d on d.trno=h.trno left join coa on coa.acnoid=d.acnoid where h.doc='CR' and h.client=? and left(coa.alias,2)='CR'
                    union all
                    select d.db from crledger as d left join glhead as h on h.trno=d.trno left join gldetail as gl on gl.trno=d.trno and gl.line=d.line where d.depodate is null and h.clientid=?
                    union all
                    select d.db from caledger as d left join glhead as h on h.trno=d.trno left join gldetail as gl on gl.trno=d.trno and gl.line=d.line where d.depodate is null and h.clientid=?) as cr", [$client, $clientid, $clientid]);

              if ($undeposited == '') {
                $undeposited = 0;
              }
              $this->coreFunctions->LogConsole('undeposited:' . $undeposited);

              if (($pendingso + $ar + $undeposited + $unpostedcr) != 0) {
                return ['status' => false, 'msg' => 'Posting failed. Please check pending SO: ' . number_format($pendingso, 2) . ' - AR: ' . number_format($ar, 2) . $unpostedcrtxt  . ' - Undeposited: ' . number_format($undeposited, 2)];
              }
            } else {

              $undeposited = $this->coreFunctions->datareader("
                    select ifnull(sum(db),0) as value from (
                    select d.db from lahead as h left join ladetail as d on d.trno=h.trno left join coa on coa.acnoid=d.acnoid where h.doc='CR' and h.client=? and left(coa.alias,2)='CR' and d.isexcept=0
                    union all
                    select d.db from crledger as d left join glhead as h on h.trno=d.trno left join gldetail as gl on gl.trno=d.trno and gl.line=d.line where d.depodate is null and h.clientid=? and gl.isexcept=0
                    union all
                    select d.db from caledger as d left join glhead as h on h.trno=d.trno left join gldetail as gl on gl.trno=d.trno and gl.line=d.line where d.depodate is null and h.clientid=? and gl.isexcept=0) as cr", [$client, $clientid, $clientid]);

              if ($undeposited == '') {
                $undeposited = 0;
              }
              $this->coreFunctions->LogConsole('undeposited:' . $undeposited);

              $crlimitbal = $crlimitamt - ($pendingso + $ar + $undeposited + $unpostedcr + $totalso);
              if ($crlimitbal < 0) {
                return ['status' => false, 'msg' => 'Posting failed. Please check pending SO: ' . number_format($pendingso, 2) . ' - AR: ' . number_format($ar, 2) . $unpostedcrtxt  . ' - Undeposited: ' . number_format($undeposited, 2) . ' - Available CR Limit: ' . $crlimitbal];
              } else {
              }
            }
          }
          break;

        case 21: //kinggeorge
          break;

        default:
          if ($override == '0') {
            if ($cstatus <> 'ACTIVE') {
              $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Customer not active');
              return ['status' => false, 'msg' => 'Posting failed. Due to SO disapproval, transaction cannot be posted. Customer is not active.'];
            }

            if (floatval($crline) < floatval($totalso)) {
              $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Above Credit limit');
              return ['status' => false, 'msg' => 'Posting failed. Due to SO disapproval, transaction cannot be posted. Above credit limit.'];
            }
          }
          break;
      }
    }

    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }

    if ($companyid == 17) { //unihome
      $qry2 = "select terms from " . $this->head . " where trno=? limit 1";
      $iscash = $this->coreFunctions->opentable($qry2, [$trno]);
      $postnoncash = $this->othersClass->checkAccess($config['params']['user'], 2995);


      if ($iscash[0]->terms != 'CASH' && $iscash[0]->terms != 'COD') {
        if (!$postnoncash) {
          return ['status' => false, 'msg' => 'Posting failed. Non-cash is not permitted; cannot post.'];
        }
      }
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $addfield = "";
    $addfieldfilter = "";
    $addsfield = "";

    switch ($companyid) {
      case 0: //main
        $addfield = ",sotype";
        $addfieldfilter = ",head.sotype";
        $addsfield = ",pdqa";
        break;
      case 24: //GFC
        $addfield = ",salestype";
        $addfieldfilter = ",head.salestype";
        break;
      case 22: //EIPI
        $addsfield = ",fstatus";
        break;
    }


    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,creditinfo,crline,overdue, projectid,mlcp_freight,ms_freight,sano,pono,statid,
       phaseid,modelid,blklotid,amenityid,subamenityid,tax,vattype " . $addfield . ")
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.creditinfo,head.crline,head.overdue, head.projectid, 
      head.mlcp_freight,head.ms_freight,head.sano,head.pono,head.statid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid,head.tax,head.vattype " . $addfieldfilter . "
      FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock

      if (!$this->othersClass->postingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,expiry,disc,iss,void,isamt,amt,isqty,ext,kgs,
        encodeddate,encodedby,editdate,editby,refx,linex,rem,ref,weight,weight2,projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint" . $addsfield . ")
        SELECT trno, line, itemid, uom,whid,loc,expiry,disc, iss,void,isamt,amt, isqty, ext,kgs,
        encodeddate, encodedby,editdate,editby,refx,linex,rem,ref,weight,weight2,projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint " . $addsfield . " FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
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
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }

    if ($companyid == 19) {
      $qry = "select trno from " . $this->hstock . " where trno=? and roqa>0";
      $data = $this->coreFunctions->opentable($qry, [$trno]);
      if (!empty($data)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already served in RO'];
      }
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $addfield = "";
    $addfieldfilter = "";
    $addsfield = "";

    switch ($companyid) {
      case 0: //main
        $addfield = ",sotype";
        $addfieldfilter = ",head.sotype";
        $addsfield = ",pdqa";
        break;
      case 24: //GFC
        $addfield = ",salestype";
        $addfieldfilter = ",head.salestype";
        break;
      case 22: //eipi
        $addsfield = ",fstatus";
        break;
    }


    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,creditinfo,crline,overdue,agent, projectid,mlcp_freight,ms_freight,sano,pono,statid,
    phaseid,modelid,blklotid,amenityid,subamenityid,tax,vattype " . $addfield . ")
    select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.creditinfo,head.crline,head.overdue,head.agent,
    head.projectid,head.mlcp_freight,head.ms_freight,head.sano,head.pono,head.statid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid,head.tax,head.vattype " . $addfieldfilter . "
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,expiry,disc,
      amt,iss,void,isamt,isqty,ext,kgs,rem,encodeddate,encodedby,editdate,editby,refx,linex,ref,weight,weight2, projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint " . $addsfield . ")
      select trno, line, itemid, uom,whid,loc,expiry,disc,amt, iss,void, isamt, isqty,
      ext,kgs,ifnull(rem,''), encodeddate,encodedby, editdate, editby,refx,linex,ref,weight,weight2, projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint" . $addsfield . "
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        if ($companyid != 39) { //not cbbsi
          $this->coreFunctions->execqry("update headinfotrans set approvalreason ='' where trno=?", 'update', [$trno]);
        }
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  public function getposummaryqry($config)
  {
    return "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemid,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,head.client, head.clientname, 
              client.addr as address, client.terms, head.rem, 'P' as cur, 1 as forex,head.ourref,stock.uom,
              FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,head.agent
              from hqthead as head
              right join hqtstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client on client.client = head.client
              left join client as wh on wh.clientid=stock.whid
              where head.trno = ? and stock.iss>stock.qa
              and stock.void = 0 ";
  }

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $itemname = 'item.itemname,';
    $itemdesc = '';

    if ($companyid == 28) { //xcomp
      $itemname = "case when item.itemname like '%misc%' and stockinfo.itemdesc <>'' then stockinfo.itemdesc else item.itemname end as itemname,";
    }

    if ($companyid == 47) //kitchenstar
    {
      $itemdesc = ",ifnull(stockinfo.itemdesc, '') as itemdesc";
    }

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    stock.sortline,
    item.barcode, 
    $itemname
    stock.uom, 
    stock.kgs,
    stock.iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    round((stock.iss-stock.roqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as roqa
    " . $itemdesc . ",
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,stock.expiry,
    item.brand,
    stock.rem, stock.refx,stock.linex,stock.ref,
    ifnull(uom.factor,1) as uomfactor,stock.weight,stock.fstatus,

    stock.phaseid, ps.code as phasename,  stock.modelid, hm.model as housemodel,stock.blklotid, bl.blk, bl.lot,
    stock.projectid, proj.code as project,
     amen.line as amenity, amen.description as amenityname,  subamen.line as subamenity, subamen.description as subamenityname,

    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    case when stock.noprint=0 then 'false' else 'true' end as noprint";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];

    $leftjoin = '';
    $hleftjoin = '';
    if ($companyid == 28 || $companyid == 47) { //xcomp and kitchenstar
      $leftjoin = 'left join stockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line';
      $hleftjoin = 'left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line';
    }

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid 
    
    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    
    $leftjoin
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    $hleftjoin
    where stock.trno =? order by sortline,line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];

    $leftjoin = '';

    if ($companyid == 28 || $companyid == 47) { //xcomp and kitchenstar
      $leftjoin = 'left join stockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line';
    }


    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    $leftjoin
    where stock.trno = ? and  stock.line = ?  ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
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
      case 'getqtdetails':
        return $this->getqtdetails($config);
        break;
      case 'getqtsummary':
        return $this->getqtsummary($config);
        break;
      case 'geteggitems':
        return $this->geteggitems($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    $tablenum = $this->tablenum;
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
      case 'forapproval':
        if ($this->companysetup->linearapproval($config['params'])) {
          return $this->othersClass->forapproval($config, $tablenum);
        } else {
          return $this->forapproval($config);
        }
        break;
      case 'doneapproved':
        if ($this->companysetup->linearapproval($config['params'])) {
          return $this->othersClass->approvedsetup($config, $tablenum);
        } else {
          return $this->doneapproved($config);
        }
        break;
      case 'duplicatedoc':
        if (!isset($config['params']['row']['trno'])) {
          $config['params']['row']['trno'] = $config['params']['trno'];
        }
        return $this->othersClass->duplicateTransaction($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function doneapproved($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 36], ['trno' => $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate($this->head, ['lockuser' => $config['params']['user'], 'lockdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $config['params']['trno']]);
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'APPROVED!');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  public function forapproval($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 10], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for approval'];
    }
  }

  public function uploadexcel($config)
  {
    $rawdata = $config['params']['data'];
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['dataparams']['trno'];
    $msg = '';
    $status = true;
    $companylist = [56]; // cdohris
    if ($trno == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
    }

    foreach ($rawdata as $key => $value) {
      try {
        if (isset($rawdata[$key]['itemcode'])) {
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key]['itemcode'] . "'");
          if ($itemid == '') {
            $status = false;
            $msg .= 'Failed to upload. ' . $rawdata[$key]['itemcode'] . ' doesn`t exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Item code is required. ';
          continue;
        }

        if (in_array($companyid, $companylist)) {
          $config['params']['barcode'] = $rawdata[$key]['itemcode'];
          $config['params']['client'] = '';
          goto checking;
        }

        if (isset($rawdata[$key]['driver'])) {
          $driverid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['driver'] . "'");
          if ($driverid == '') {
            $status = false;
            $msg .= 'Failed to upload. Driver ' . $rawdata[$key]['driver'] . ' doesn`t exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Driver is required. ';
          continue;
        }

        if (isset($rawdata[$key]['helper'])) {
          $helperid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['helper'] . "'");
          if ($helperid == '') {
            $status = false;
            $msg .= 'Failed to upload. Helper ' . $rawdata[$key]['helper'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Helper is required. ';
          continue;
        }

        if (isset($rawdata[$key]['truck'])) {
          $truckid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['truck'] . "'");
          if ($truckid == '') {
            $status = false;
            $msg .= 'Failed to upload. Helter ' . $rawdata[$key]['truck'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Truck is required. ';
          continue;
        }

        $others = [
          'driverid' => $driverid,
          'helperid' => $helperid,
          'truckid' => $truckid,
          'plateno' => $rawdata[$key]['plateno'],
          'isro' => 1
        ];
        $this->coreFunctions->sbcupdate('headinfotrans', $others, ['trno' => $trno]);
        checking:
        $config['params']['trno'] = $trno;
        $config['params']['data']['ref'] = isset($rawdata[$key]['ref']);
        $config['params']['data']['uom'] = $rawdata[$key]['uom'];
        $config['params']['data']['itemid'] = $itemid;
        $config['params']['data']['qty'] = $rawdata[$key]['qty'];
        $config['params']['data']['wh'] =  $this->coreFunctions->getfieldvalue($this->head, "wh", "trno = ?", [$trno]);
        $config['params']['data']['amt'] = isset($rawdata[$key]['cost']) ? $rawdata[$key]['cost'] : $rawdata[$key]['amt'];
        $config['params']['data']['loc'] = isset($rawdata[$key]['location']) ? $rawdata[$key]['location'] : "";
        $config['params']['data']['disc'] = isset($rawdata[$key]['disc']) ? $rawdata[$key]['disc'] : "";

        if (isset($rawdata[$key]['kgs'])) {
          $config['params']['data']['kgs'] = $rawdata[$key]['kgs'];
        }
        if (isset($rawdata[$key]['weight'])) {
          $config['params']['data']['weight'] = $rawdata[$key]['weight'];
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

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so 
     left join hsostock as s on s.trno = so.trno
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
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  public function addallitem($config)
  {
    $msg = '';
    $status = true;
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result  = $this->additem('insert', $config);
      if (!$result['status']) {
        $msg .= ' ' . $result['msg'];
        $status = false;
      }
    }

    $data = $this->openstock($config['params']['trno'], $config);
    if ($msg == '') {
      $msg  = 'Successfully saved.';
    }
    return ['inventory' => $data, 'status' => $status, 'msg' => $msg];
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
    // var_dump($config['params']);
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : "";
    $wh = isset($config['params']['data']['wh']) ? $config['params']['data']['wh'] : "";
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : "";
    $void = 'false';
    $rem = '';
    $ref = '';
    $expiry = '';
    $refx = 0;
    $linex = 0;
    $noprint = 'false';

    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }

    $weight = isset($config['params']['data']['weight']) ? $config['params']['data']['weight'] : 0;
    $itemdesc = isset($config['params']['data']['itemdesc']) ? $config['params']['data']['itemdesc'] : '';

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
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
    if (isset($config['params']['data']['noprint'])) {
      $noprint = $config['params']['data']['noprint'];
    }

    $fstatus = '';
    if ($companyid == 22) { //EIPI
      if (isset($config['params']['data']['fstatus'])) {
        $fstatus = $config['params']['data']['fstatus'];
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
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];

      $config['params']['line'] = $line;
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

    if ($companyid == 22) { //eipi
      $client = $this->coreFunctions->getfieldvalue('sohead', "client", "trno=?", [$trno]);
      $clientid = $this->coreFunctions->getfieldvalue('client', "clientid", "client=?", [$client]);
      $sku = $this->coreFunctions->getfieldvalue('sku', "sku", "itemid=? and clientid=?", [$itemid, $clientid]);
    }

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,tqty from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      if ($companyid == 19) { //housegem
        $weight = $item[0]->tqty;
      }
    }

    if ($companyid == 15) { //nathina
      if ($action == 'insert') {
        $groupid = $this->coreFunctions->datareader("select client.category as value from " . $this->head . " as h left join client on client.client=h.client where h.trno=?", [$trno]);
        $pricebracket = $this->coreFunctions->datareader("select `name` as value from qtybracket where ? between minimum and maximum", [$qty]);
        if ($pricebracket != "") {
          $amt = $this->coreFunctions->getfieldvalue("pricebracket", strtolower($pricebracket), "itemid=? and groupid=?", [$itemid, $groupid]);
        } else {
          $amt = 0;
        }
      }
    }

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    if ($this->companysetup->getisdiscperqty($config['params'])) {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', $kgs, 0, 1);
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', $kgs);
    }

    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

    if (floatval($forex) == 0) {
      $forex = 1;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isamt' => $amt,
      'amt' => number_format($computedata['amt'] * $forex, $this->companysetup->getdecimal('price', $config['params']), '.', ''),
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'void' => $void,
      'uom' => $uom,
      'rem' => $rem,
      'refx' => $refx,
      'linex' => $linex,
      'expiry' => $expiry,
      'ref' => $ref,
      'weight' => $weight,
      'fstatus' => $fstatus,
      'noprint' => $noprint
    ];
    if ($systemtype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }
    if ($companyid == 22) { //eipi
      $data['fstatus'] = $sku;
    }

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
      $data['sortline'] =  $data['line'];

      $errormsg = '';
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            $stockinfo_data = [];
            switch ($companyid) {
              case 47: //kitchenstar
                $stockinfo_data = [
                  'trno' => $trno,
                  'line' => $line,
                  'itemdesc' => $itemdesc
                ];
                break;
              case 0: //main
                $stockinfo_data = [
                  'trno' => $trno,
                  'line' => $line,
                  'rem' => $rem
                ];
                break;
            }
            $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' uom:' . $uom, $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        $this->othersClass->getcreditinfo($config, $this->head);
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") SO Qty is Greater than Quotation Qty.";
        }
        return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
      } else {
        $errormsg = $this->coreFunctions->errmsg;
        return ['status' => false, 'msg' => 'Add item Failed for ' . $item[0]->barcode . ' - ' . $errormsg];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';

      // $this->coreFunctions->execqry("update " . $this->stock . " set itemid='" . $data['itemid'] . "', isamt='" . $data['isamt'] . "', amt='" . $data['amt'] . "', isqty='" . $data['isqty'] . "', iss='" . $data['iss'] . "', ext='" . $data['ext'] . "', kgs='" . $data['kgs'] . "', disc='" . $data['disc'] . "', whid='" . $data['whid'] . "', loc='" . $data['loc'] . "', void='" . $data['void'] . "', uom='" . $data['uom'] . "', rem='" . $data['rem'] . "', refx='" . $data['refx'] . "', linex='" . $data['linex'] . "', expiry='" . $data['expiry'] . "', ref='" . $data['ref'] . "', fstatus='" . $data['fstatus'] . "',noprint='" . $data['noprint'] . "' where trno='" . $data['trno'] . "' and line='" . $data['line'] . "'", 'update');
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      switch ($this->companysetup->getsystemtype($config['params'])) {
        case 'AIMS':
          if ($companyid == 47) { //kitchenstar
            $stockinfo_data = [
              'trno' => $trno,
              'line' => $line,
              'itemdesc' => $itemdesc
            ];
            $checkstockinfo = $this->coreFunctions->getfieldvalue("stockinfotrans", "trno", "trno=? and line =?", [$trno, $line]);
            if ($checkstockinfo == '') {
              $this->coreFunctions->sbcinsert("stockinfotrans", $stockinfo_data);
            } else {
              $stockinfo_data['editdate'] = $this->othersClass->getCurrentTimeStamp();
              $stockinfo_data['editby'] = $config['params']['user'];
              foreach ($stockinfo_data as $key => $valueinfo) {
                $stockinfo_data[$key] = $this->othersClass->sanitizekeyfield($key, $stockinfo_data[$key]);
              }
              $this->coreFunctions->sbcupdate("stockinfotrans", $stockinfo_data, ['trno' => $trno, 'line' => $line]);
            }
          }
          break;
      }

      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $return = false;
        $msg = "(" . $item[0]->barcode . ") SO Qty is Greater than Quotation Qty.";
      }
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0 ', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->sbcupdate("headinfotrans", ['isro' => 0], ['trno' => $trno]);

    foreach ($data as $key => $value) {
      if (floatval($data[$key]->refx) != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
    }
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
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
    if (floatval($data[0]->refx) !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
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
    $pricegrp = '';
    $data = [];

    $pricedec = $this->companysetup->getdecimal('price', $config['params']);

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
          if ($companyid == 47) { //kstar
            $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom from(select head.docno,head.dateid,
              stock.isamt as amt,stock.uom,stock.disc
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid = stock.itemid
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and head.client = ?
              and stock.isamt <> 0
              UNION ALL
              select head.docno,head.dateid,stock.isamt as amt,
              stock.uom,stock.disc from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join client on client.clientid = head.clientid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and client.client = ?
              and stock.isamt <> 0
              order by dateid desc limit 5) as tbl order by dateid desc limit 5";
            $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
            if (empty($data)) {
              $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
              $data = $this->coreFunctions->opentable("select '" . $pricefield['label'] . "' as docno, " . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom from item where barcode=?", [$barcode]);
              if (!empty($data)) {
                goto setpricehere;
              }
            }
          } else {
            $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
            $data = $this->coreFunctions->opentable("select '" . $pricefield['label'] . "' as docno, " . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom, itemid from item where barcode=?", [$barcode]);
            if ($companyid == 21) { //kinggeorge - compute based on default SO uom
              $datauom = $this->coreFunctions->opentable("select uom, factor from uom where itemid=" . $data[0]->itemid . " and issalesdef=1 limit 1");
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
        switch ($companyid) {
          case 22: //eipi
            $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom 
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
              order by dateid desc limit 5 ) as tbl order by dateid desc limit 1";
            $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
            break;
          case 60: //transpower
            $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom from(
              select '' as docno,left(now(),10) as dateid,round(namt5,2) as amt,'' as disc,uom
              from item where barcode = ?
              union all
              select head.docno,head.dateid,
              stock.isamt as amt,stock.uom,stock.disc
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid = stock.itemid
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and head.client = ?
              and stock.isamt <> 0
              UNION ALL
              select head.docno,head.dateid,stock.isamt as amt,
              stock.uom,stock.disc from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join client on client.clientid = head.clientid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and client.client = ?
              and stock.isamt <> 0
              order by dateid desc limit 5) as tbl order by dateid desc ";
            $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $center, $barcode, $client]);
            break;
          default:
            $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom from(select head.docno,head.dateid,
              stock.isamt as amt,stock.uom,stock.disc
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid = stock.itemid
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and head.client = ?
              and stock.isamt <> 0
              UNION ALL
              select head.docno,head.dateid,stock.isamt as amt,
              stock.uom,stock.disc from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join client on client.clientid = head.clientid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc = 'SJ' and cntnum.center = ?
              and item.barcode = ? and client.client = ?
              and stock.isamt <> 0
              order by dateid desc limit 5) as tbl order by dateid desc limit 1";
            $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
            break;
        }

        break;
    }

    $defaultsameuom = " and uom.issalesdef=1"; //kinggeorge
    if (!empty($data)) {
      if ($companyid == 21) { //kinggeorge
        kinggeorge_defaultprice:
        $qry = "select 'Retail Price' as docno, round((item.amt * if(uom.factor=0,1,uom.factor))," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
        round((item.amt * if(uom.factor=0,1,uom.factor))," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,'" . $data[0]->uom . "' as uom 
        from item left join uom on uom.itemid=item.itemid and uom.uom='" . $data[0]->uom . "' and uom.issales=1 where item.barcode=? " . $defaultsameuom;
        $this->coreFunctions->LogConsole($qry);
        $data = $this->coreFunctions->opentable($qry, [$barcode]);
        if (empty($data)) {
          $defaultsameuom = '';
          goto kinggeorge_defaultprice;
        }
      }
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      if ($companyid == 15) { //NATHINA
        $trno = $config['params']['trno'];
        $qry = "select 'PRICE LIST' as docno, ifnull((select b.r from sohead as head left join client on client.client=head.client left join pricebracket as b on b.groupid=client.category where head.trno=? and b.itemid=item.itemid),0) as amt,
        ifnull((select b.r from sohead as head left join client on client.client=head.client left join pricebracket as b on b.groupid=client.category where head.trno=? and b.itemid=item.itemid),0) as defamt,
            disc,uom from item where barcode=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno, $barcode]);
      } else {
        itempricehere:
        $qry = "select 'Retail Price' as docno, amt,amt as defamt,disc,uom from item where barcode=?";
        $data = $this->coreFunctions->opentable($qry, [$barcode]);
        if ($companyid == 21) { //kinggeorge
          $defaultsameuom = '';
          goto kinggeorge_defaultprice;
        }
      }

      setpricehere:
      $usdprice = 0;
      $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
      $defuom = '';

      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $data[0]->docno = 'UOM';
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
        if ($defuom != "") {
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if ($data[0]->amt != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            } else {
              $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
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

  public function getqtsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
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
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
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
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function getqtdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
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
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
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
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function


  public function geteggitemsqry($config, $itemid)
  {
    return "select i.itemid,i.barcode,i.itemname,i.uom,i.disc
            from item as i
            left join itemcategory as cat on i.category= cat.line
            where cat.name = 'Egg' and i.itemid = " . $itemid . " ";
  }

  public function geteggitems($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $itemid = $config['params']['rows'][$key]['itemid'];
      $qry = $this->geteggitemsqry($config, $itemid);
      $data = $this->coreFunctions->opentable($qry);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = '';
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['ref'] = '';
          $config['params']['data']['amt'] = '';
          $config['params']['data']['stageid'] = '';

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hqtstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  // reports 

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
    if ($companyid == 10 || $companyid != 12) { //afti, not afti usd
    } else {
      $this->logger->sbcviewreportlog($config);
    }

    switch ($companyid) {
      case 39: //cbbsi
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        break;
      case 40: //cdo
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        break;
      case 22: //eipi
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        break;
    }

    if ($companyid == 19) { //housegem
      $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config, $config['params']['dataid']); // need to pass params
    } else {
      $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    }

    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
