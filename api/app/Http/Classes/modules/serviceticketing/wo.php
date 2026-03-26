<?php

namespace App\Http\Classes\modules\serviceticketing;

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

class wo
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'WORK ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
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
  private $stockselect;
  private $fields = ['trno', 'docno', 'client', 'address', 'dateid', 'terms', 'contra', 'vattype', 'due', 'yourref', 'ourref', 'forex', 'cur', 'wh', 'projectid', 'deptid'];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
  private $otherfields = ['trno', 'rem2', 'rem3'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AR1';
  public $showfilterlabel = [
    ['val' => 'pending', 'label' => 'Pending Application', 'color' => 'primary'],
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'forquotation', 'label' => 'For Quotation', 'color' => 'primary'],
    ['val' => 'cancelled', 'label' => 'Cancelled', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'success']
  ];
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
    $this->reporter = new SBCPDF;
  } // end function

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4940,
      'edit' => 4941,
      'new' => 4942,
      'save' => 4943,
      'delete' => 4945,
      'print' => 4946,
      'lock' => 4947,
      'unlock' => 4948,
      'changeamt' => 4949,
      'post' => 4950,
      'unpost' => 4951,
      'additem' => 4952,
      'edititem' => 4953,
      'deleteitem' => 4954
    );

    return $attrib;
  } // end function

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $listpostedby = 5;
    $listcreateby = 6;
    $listeditedby = 7;
    $listviewby = 8;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:120px;';
    $cols[1]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $cols[$liststatus]['name'] = 'statuscolor';
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  } // end function

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = 'TA';
    $center = $config['params']['center'];
    $limit = "limit 50";

    $condition = '';
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'client.clientname', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $status = "stat.status";
    $statuscolor = "'red'";
    switch ($itemfilter) {
      case 'pending':
        $status = "'PENDING'";
        $condition .= " and head.ista=1 and num.postdate is null and num.statid=95";
        break;
      case 'forquotation':
        $status = "'FOR QUOTATION'";
        $condition .= " and num.postdate is null and num.statid=96";
        break;
      case 'cancelled':
        $status = "'CANCELLED'";
        $condition .= " and num.postdate is null and num.statid=97";
        break;
      case 'approved':
        $status = "'APPROVED'";
        $condition .= " and num.postdate is null and num.statid=36";
        break;
      case 'draft':
        $status = "'DRAFT'";
        $condition .= " and head.ista=0 and num.postdate is null";
        break;
      case 'posted':
        $condition .= " and num.postdate is not null";
        $statuscolor = "'grey'";
        break;
    }

    $qry = "select head.trno,head.docno,client.clientname,left(head.dateid,10) as dateid, 
            head.createby,head.editby,head.viewby,num.postedby," . $status . " as status, " . $statuscolor . " as statuscolor
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join client on client.client=head.client 
            left join trxstatus as stat on stat.line=num.statid
            where head.doc=? and num.center=? and convert(head.dateid,date)>=? and convert(head.dateid,date)<=? " . $condition . "  " . $filtersearch . "
            union all
            select head.trno,head.docno,client.clientname,left(head.dateid,10) as dateid, 
            head.createby,head.editby,head.viewby,num.postedby," . $status . " as status, " . $statuscolor . " as statuscolor
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join client on client.clientid=head.clientid 
            left join trxstatus as stat on stat.line=num.statid
            where head.doc=? and num.center=? and convert(head.dateid,date)>=? and convert(head.dateid,date)<=? " . $condition . "  " . $filtersearch . "
            order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  } // end function

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
      'toggledown'
    );

    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
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
    $viewfieldsforgate2users = $this->othersClass->checkAccess($config['params']['user'], 2509);
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $action = 0;
    $itemdesc = 1;
    $itemdesc2 = 2;
    $isqty = 3;
    $uom = 4;
    $isamt = 5;
    $disc = 6;
    $ext = 7;
    $cost = 8;
    $markup = 9;
    $wh = 10;
    $whname =  11;
    $ref = 12;
    $loc = 13;
    $expiry = 14;
    $rem = 15;
    $itemname = 16;
    $stock_projectname = 17;
    $noprint = 18;
    $barcode = 19;

    $headgridbtns = ['viewdistribution', 'viewitemstockinfo'];
    $column = ['action', 'itemdescription', 'itemdesc', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'cost', 'markup',  'wh', 'whname', 'ref', 'loc', 'expiry', 'rem', 'itemname', 'stock_projectname', 'noprint', 'barcode'];
    $sortcolumn = ['action', 'itemdescription', 'itemdesc', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'cost', 'markup',  'wh', 'whname', 'ref', 'loc', 'expiry', 'rem', 'itemname', 'stock_projectname', 'noprint', 'barcode'];
    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

    if ($iskgs) {
      $computefield['kgs'] = 'kgs';
    }

    $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $sortcolumn, 'computefield' => $computefield, 'headgridbtns' => $headgridbtns]];

    $stockbuttons = ['save', 'delete', 'showbalance'];

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$markup]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$itemdesc2]['type'] = 'input';
    $obj[0]['inventory']['columns'][$itemdesc2]['readonly'] = 'false';
    $obj[0]['inventory']['columns'][$itemdesc2]['label'] = 'Other Description';
    $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
    }
    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';

    if (!$access['changeamt']) {
      // 3 - isamt
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      // 4 - disc
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  } // end funtion

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  } // end funtion

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  } // end funtion

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address', 'ddeptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'clientname.readonly', true);
    data_set($col1, 'clientname.class', 'cs sbccsreadonly');
    data_set($col1, 'ddeptname.label', 'Department');
    data_set($col1, 'ddeptname.required', true);

    $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dprojectname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'rem2.label', 'Item Desctiption');

    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dwhname'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['rem2', 'rem3', 'forquotation', ['doneapproved', 'cancel']];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem2.label', 'Item Desctiption');
    data_set($col4, 'rem3.label', 'Remarks');
    data_set($col4, 'forquotation.label', 'For Quotation');

    data_set($col4, 'forquotation.confirm', true);
    data_set($col4, 'forquotation.confirmlabel', 'Tag as for Quotation?');

    data_set($col4, 'doneapproved.confirm', true);
    data_set($col4, 'doneapproved.confirmlabel', 'Approved this document?');

    data_set($col4, 'cancel.style', 'width:100%');
    data_set($col4, 'cancel.access', 'save');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  } // end funtion

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['address'] = '';

    $data[0]['client'] = '';
    $data[0]['clientname'] = '';

    $data[0]['terms'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;

    $data[0]['dacnoname'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['dvattype'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);

    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';

    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';

    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';

    $data[0]['rem2'] = '';
    $data[0]['rem3'] = '';
    $data[0]['ista'] = 1;

    return $data;
  } // end funtion

  public function loadheaddata($config)
  {
    $doc = 'TA';
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
    $statid = $this->othersClass->getstatid($config);
    $table = $this->head;
    $tablenum = $this->tablenum;

    $qryselect = "select num.center, client.client, head.trno, head.docno, head.terms, head.cur, head.forex, head.yourref, head.ourref, 
                  head.contra, coa.acnoname, '' as dacnoname, left(head.dateid,10) as dateid, left(head.due,10) as due,
                  head.address, client.clientname, head.forex, head.cur, head.tax, head.vattype, '' as dvattype, warehouse.client as wh, 
                  warehouse.clientname as whname, '' as dwhname, head.projectid, ifnull(project.name,'') as projectname, '' as dprojectname, 
                  ifnull(project.code,'') as projectcode, info.rem2, info.rem3, date_format(head.createdate,'%Y-%m-%d') as createdate, head.ista,
                  head.deptid, ifnull(d.client,'') as dept, ifnull(d.clientname,'') as deptname, '' as ddeptname ";

    $qry =        $qryselect . " from $table as head
                  left join $tablenum as num on num.trno = head.trno
                  left join client on client.client = head.client
                  left join coa on coa.acno = head.contra
                  left join client as warehouse on warehouse.client = head.wh
                  left join projectmasterfile as project on project.line=head.projectid 
                  left join cntnuminfo as info on info.trno=head.trno
                  left join client as d on d.clientid=head.deptid
                  where head.trno = ? and num.center = ?
                  UNION ALL
                  " . $qryselect . " from " . $this->hhead . " as head
                  left join $tablenum as num on num.trno = head.trno
                  left join client on client.clientid = head.clientid
                  left join coa on coa.acno = head.contra
                  left join client as warehouse on warehouse.clientid = head.whid
                  left join projectmasterfile as project on project.line=head.projectid
                  left join hcntnuminfo as info on info.trno=head.trno
                  left join client as d on d.clientid=head.deptid
                  where head.trno = ? and num.center = ?";

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

      $hideobj = [];
      $hideobj['forquotation'] = true;
      $hideobj['doneapproved'] = true;
      $hideobj['cancel'] = true;
      $breadcrumbs = [];


      switch ($statid) {
        case 95:
          $hideobj['forquotation'] = false;
          array_push($breadcrumbs, ['label' => 'Submitted', 'icon' => 'fact_check']);
          break;

        case 96:
          $hideobj['doneapproved'] = false;
          $hideobj['cancel'] = false;
          array_push($breadcrumbs, ['label' => 'Submitted', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'For Quotation', 'icon' => 'fact_check']);
          break;

        case 36:
          array_push($breadcrumbs, ['label' => 'Submitted', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'For Quotation', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'Approved', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'For Posting', 'icon' => 'fact_check']);
          break;

        case 97:
          array_push($breadcrumbs, ['label' => 'Submitted', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'For Quotation', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'Cancelled', 'icon' => 'fact_check']);
          break;

        case 12:
          array_push($breadcrumbs, ['label' => 'Submitted', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'For Quotation', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'Approved', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'For Posting', 'icon' => 'fact_check']);
          array_push($breadcrumbs, ['label' => 'Posted', 'icon' => 'fact_check']);
          break;
      }

      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj, 'breadcrumbsbottom' => $breadcrumbs];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  } // end function

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'forquotation':
        return $this->forquotation($config);
        break;
      case 'doneapproved':
        return $this->doneapproved($config);
        break;
      case 'cancel':
        return $this->cancel($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function forquotation($config)
  {
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 96], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'For Quotation');
      return ['status' => true, 'msg' => 'Tagged for quotation.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag'];
    }
  }

  public function doneapproved($config)
  {
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 36], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Approved');
      return ['status' => true, 'msg' => 'Successfully approved.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag'];
    }
  }

  public function cancel($config)
  {
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 97], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Cancelled');
      return ['status' => true, 'msg' => 'Successfully Cancelled', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $data = [];
    $dataother = [];

    if ($isupdate) {
      unset($this->fields[1]);
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

    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataother[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
        } //end if
      }
    }

    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = 'TA';
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataother);
    } else {
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataother, ['trno' => $head['trno']]);
    }
  } // end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];

    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if (!$this->othersClass->checkserialout($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }


    $stock = $this->coreFunctions->datareader("select count(trno) as value from " . $this->stock . " where trno=?", [$config['params']['trno']], '', true);
    if ($stock == 0) {
      return ['status' => false, 'msg' => 'Unable to post, Please add items first...'];
    }

    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1']);

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
        if ($override == '0') {
          $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
          $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
          $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
          $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);

          if ($cstatus <> 'ACTIVE') {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'Customer Status is not Active');
            return ['status' => false, 'msg' => 'Posting failed. Customer status is not Active.'];
          }

          if (floatval($crline) < floatval($totalso)) {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit');
            return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
          }
          //}
        }
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        return $this->othersClass->posttranstock($config);
      }
    }
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
    if ($delcharge == '') {
      $delcharge = 0;
    }
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $fields = '';

    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
      item.expense,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate,head.taxdef,head.deldate,head.ewt,head.ewtrate
      ' . $fields . '
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';

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
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct,
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

    if ($delcharge != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['DC1']);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => 0, 'cr' => $delcharge * $d[0]->forex, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => ($delcharge * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid, 'fcr' => 0];

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

    //rebate vitaline
    if (floatval($params['rebate']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR3']);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => $params['rebate'] * $forex, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['rebate'], 'fdb' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      //sales
      $sales = ($params['ext'] - $params['rebate'] - $params['tax']);
      $sales  = $sales + $params['discamt'];
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
      $sales = ($params['ext'] - $params['rebate']);
      $sales = round(($sales + $params['discamt']), 2);
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

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $result = $this->othersClass->unposttranstock($config);
    $this->coreFunctions->execqry('update cntnum set statid=36 where trno=' . $trno);

    return $result;
  } //end function

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
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function openstock($trno, $config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $leftjoin = '';
    $hleftjoin = '';
    $stockinfogroup = '';

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
    left join stockinfo as info on info.trno = stock.trno and info.line = stock.line
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
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc, stock.isqty,info.itemdesc 

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
    left join hstockinfo as info on info.trno = stock.trno and info.line = stock.line
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
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc, stock.isqty,info.itemdesc
    order by sortline, line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

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

    switch ($pricetype) {
      case 'Stockcard':
        goto itempricehere;
        break;

      case 'CustomerGroup':
      case 'CustomerGroupLatest':
        $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$client]);
        if ($pricegrp != '') {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $this->coreFunctions->LogConsole($pricefield);

          $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'TA' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as computeramt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'TA' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            order by dateid desc limit 5) as tbl order by dateid desc";
          //latest trans
          $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

          if (empty($data)) {
            $this->coreFunctions->LogConsole("Empty");
            $qry = "select '" . $pricefield['label'] . "' as docno, left(now(),10) as dateid," . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom from item where barcode=? 
              union all
              select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
                stock.isamt as amt,stock.uom,stock.disc
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid = stock.itemid
                where head.doc = 'TA' and cntnum.center = ?
                and item.barcode = ? and head.client = ?
                and stock.isamt <> 0 and cntnum.trno <> ?
                UNION ALL
                select head.docno,head.dateid,stock.isamt as computeramt,
                stock.uom,stock.disc from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join client on client.clientid = head.clientid
                left join cntnum on cntnum.trno=head.trno
                where head.doc = 'TA' and cntnum.center = ?
                and item.barcode = ? and client.client = ?
                and stock.isamt <> 0 and cntnum.trno <> ?
                order by dateid desc limit 5) as tbl order by dateid desc";
            $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);
          }



          if (!empty($data)) {
            goto setpricehere;
          }
        } else {
          if ($pricetype == 'CustomerGroupLatest') {
            goto getCustomerLatestPriceHere;
          } else {
            goto setpricehere;
          }
        }
        break;

      default:
        getCustomerLatestPriceHere:
        $amtfield = 'amt';
        $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
          round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc,'test' as rem
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'TA' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as computeramt,
            stock.uom,stock.disc,'test' as rem from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'TA' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            order by dateid desc limit 5) as tbl order by dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);
        break;
    }

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    } else {
      itempricehere:
      $qry = "select 'STOCKCARD'  as docno,left(now(),10) as dateid,amt,amt as defamt,disc,uom,'test' as rem from item where barcode=? 
            union all
            select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc,'test' as rem
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'TA' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as computeramt,
            stock.uom,stock.disc,'test' as rem from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'TA' and cntnum.center = ?
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
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            } else {
              $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
            }
          }
        } else {
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]));
            } else {
              $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]);
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
      default:
        return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ') TA'];
        break;
    }
  }

  public function additem($action, $config, $setlog = false)
  {
    $ispallet = $this->companysetup->getispallet($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];

    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';
    $itemdesc = isset($config['params']['data']['itemdesc']) ? $config['params']['data']['itemdesc'] : '';
    $locid = isset($config['params']['data']['locid']) ? $config['params']['data']['locid'] : 0;
    $palletid = isset($config['params']['data']['palletid']) ? $config['params']['data']['palletid'] : 0;
    $weight = isset($config['params']['data']['weight']) ? $config['params']['data']['weight'] : 0;
    $expiry = '';
    $info = [];
    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }


    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }

    $rebate = 0;
    $refx = 0;
    $linex = 0;
    $ref = '';
    $projectid = 0;
    $sgdrate = 0;
    $noprint = 'false';
    $rem = '';
    $poref = '';
    $podate = null;

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['rebate'])) {
      $rebate = $config['params']['data']['rebate'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $projectid = $config['params']['data']['projectid'];
    }

    if (isset($config['params']['data']['noprint'])) {
      $noprint = $config['params']['data']['noprint'];
    }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['podate'])) {
      $podate = $config['params']['data']['podate'];
    }

    $itemstatus = '';
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


    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv,item.foramt from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    $floorprice = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $floorprice = $item[0]->foramt;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    if ($this->companysetup->getisdiscperqty($config['params'])) {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs, 0, 1);
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
    }

    if (floatval($curtopeso) == 0) {
      $curtopeso = 1;
    }

    $hamt = $computedata['amt'] * $curtopeso;
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

    if ($itemdesc != '') {
      $info = [
        'trno' => $trno,
        'line' => $line,
        'itemdesc' => $itemdesc
      ];
    }


    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if (!empty($info)) {
      foreach ($info as $key2 => $v) {
        $info[$key2] = $this->othersClass->sanitizekeyfield($key2, $info[$key2]);
      }
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
              where head.doc='ta' and head.trno=?";
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

      $trno = $this->othersClass->val($trno);
      if ($trno == 0) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ZERO TRNO (TA)');
        return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $havestock = true;
        $msg = 'Item was successfully added.';

        if (!empty($info)) {
          $this->coreFunctions->sbcinsert("stockinfo", $info);
        }


        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' Uom:' . $uom . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        if ($isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], 'TA', $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], 'TA', $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

            //CHECK BELOW floor price
            $override = $this->othersClass->checkAccess($config['params']['user'], 1736);
            if ($override != 1) {
              if ($this->companysetup->checkbelowcost($config['params'])) {
                $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
                if ($amt == 0) {
                  $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
                } elseif ($amt < $floorprice) {
                  $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW FLOOR PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                  $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                  $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW FLOOR PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                  $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's FLOOR PRICE!!!";
                } elseif ($belowcost == 2) {
                  $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'CHECK PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                  $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                  $this->logger->sbcwritelog($trno, $config, 'STOCK', 'CHECK PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                  $msg = "(" . $item[0]->barcode . ") You can't issue this item/s, please check Price!!!";
                }
              }
            }
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
          }
        }
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than RR Qty.";
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
      if (!empty($info)) {
        $exist = $this->coreFunctions->getfieldvalue("stockinfo", "trno", "trno=? and line =?", [$trno, $line]);
        if (floatval($exist) != 0) {
          $this->coreFunctions->sbcupdate("stockinfo", $info, ['trno' => $trno, 'line' => $line]);
        } else {
          $this->coreFunctions->sbcinsert("stockinfo", $info);
        }
      }
      if ($isnoninv == 0) {
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], 'TA', $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], 'TA', $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

          //CHECK BELOW COST
          $override = $this->othersClass->checkAccess($config['params']['user'], 1736);
          if ($override != 1) {
            if ($this->companysetup->checkbelowcost($config['params'])) {
              $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
              if ($amt == 0) {
                $msg = '(' . $item[0]->barcode . ') Is this free if charge? Please check.';
              } elseif ($amt < $floorprice) {
                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW FLOOR PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW FLOOR PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s, please check Price!!!";
                $return = false;
              } elseif ($belowcost == 2) {
                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'CHECK PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'CHECK PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s,please check Price!!!";
                $return = false;
              }
            }
          }
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->setserveditems($refx, $linex);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Out of Stock.";
        }
      }

      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
        $return = false;
        $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
      }

      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc in ('TA','BO') and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc in ('TA','BO') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

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
  } // end function

  public function openstockline($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    $leftjoin = '';
    $stockinfogroup = '';


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
    left join stockinfo as info on info.trno = stock.trno and info.line = stock.line
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
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc, stock.isqty,info.itemdesc
    ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  private function getstockselect($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $itemname = 'item.itemname,';
    $serialfield = '';

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
    uom.factor*stock.cost as cost,
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
    round(case when (stock.Amt>0 and stock.iss>0 and stock.Cost>0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / (stock.Amt * stock.Iss))/head.forex)*100) else 0 end,2) markup,stock.rebate,
    round(case when stock.Amt>0 then ((stock.amt-stock.cost)/head.forex) else 0 end,2) as gprofit,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,

    case when stock.noprint=0 then 'false' else 'true' end as noprint,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,'')) as itemdescription,info.itemdesc";
    return $sqlselect;
  }

  public function addallitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);
      if ($msg != '') {
        $msg = $msg . ' ' . $row['msg'];
      } else {
        $msg = $row['msg'];
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
  } //end function

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

    $this->setserveditems($data[0]->refx, $data[0]->linex);

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
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

    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  } // end function

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
  } //end function

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
} //end class
