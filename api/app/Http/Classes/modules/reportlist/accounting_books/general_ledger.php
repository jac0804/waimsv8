<?php

namespace App\Http\Classes\modules\reportlist\accounting_books;

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

class general_ledger
{
  public $modulename = 'General Ledger';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['prepared', 'approved', 'dateid', 'enddate', 'dacnoname'];
    array_push($fields, 'dclientname', 'dcentername', 'costcenter', 'ddeptname');
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'ddeptname.label', 'Department');
    data_set($col2, 'costcenter.label', 'Item Group');
    data_set($col2, 'dclientname.lookupclass', 'lookupgjclient');
    data_set($col2, 'dclientname.label', 'Customer/Supplier');

    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'dacnoname.action', 'lookupcoa');
    data_set($col2, 'dacnoname.lookupclass', 'detail');
    data_set($col2, 'dacnoname.label', 'Account Description');

    $fields = ['radioposttype'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'radioposttype.options', array(
      ['label' => 'Posted Transaction', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Unposted Transaction', 'value' => '1', 'color' => 'orange'],
      ['label' => 'All Transaction', 'value' => '2', 'color' => 'orange'],
    ));
    data_set($col3, 'dclientname.lookupclass', 'lookupgjclient');
    data_set($col3, 'dclientname.label', 'Customer/Supplier');

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as dateid,
      left(now(),10) as enddate,
      '' as contra,
      '' as acnoname,
      '' as dacnoname,
      0 as clientid,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '' as prepared,
      '' as approved,
      '0' as posttype,
      '' as center,
      '' as centername,
      '' as dcentername,
      '' as code,
      '' as name,
      '' as costcenter,
      0 as costcenterid,
      0 as deptid,
      '' as ddeptname,
      '' as dept,
      '' as deptname";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function default_query($filters)
  {
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));

    $center = $filters['params']['dataparams']['center'];
    $costcenter = isset($filters['params']['dataparams']['costcenter']) ? $filters['params']['dataparams']['costcenter'] : "";
    $costcenterid = isset($filters['params']['dataparams']['costcenterid']) ? $filters['params']['dataparams']['costcenterid'] : 0;
    $acno = $filters['params']['dataparams']['contra'];
    $client = $filters['params']['dataparams']['client'];

    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(detail.cr-detail.db,2)),0) ';
        break;
      default:
        $field = ' ifnull(sum(round(detail.db-detail.cr,2)),0) ';
        break;
    }
    //myconstant

    $filter = "";
    if ($acno != "ALL") {
      $filter .= " and coa.acno='\\" . $acno . "' ";
    }
    if ($costcenter != "") {
      $filter .= " and head.projectid = '" . $costcenterid . "' ";
    }
    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($client != "") {
      $filter .= " and client.client='" . $client . "' ";
    }
    $deptid = $filters['params']['dataparams']['ddeptname'];
    if ($deptid == "") {
      $dept = "";
    } else {
      $dept = $filters['params']['dataparams']['deptid'];
    }
    if ($deptid != "") {
      $filter .= " and head.deptid = $dept";
    }

    switch ($isposted) {
      case 0: // posted
        $query = "select a.dateid,a.docno,a.client,client.clientname,a.ref,
        a.checkno,case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,head.dateid as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $end . "'  " . $filter . "
        group by  head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr) as a
        left join coa on a.acno=coa.acno left join client on client.client = a.client  
        order by  acno,dateid,docno";
        break;
      case 1: // unposted
        $query = "select a.dateid,a.docno,a.client,client.clientname,a.ref,
        a.checkno,case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail
        from (select head.trno as trno,head.doc as doc,head.docno as docno,head.dateid as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
        coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,
        detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr 
        from ((lahead as head 
        left join ladetail as detail on ((head.trno = detail.trno)))
        left join coa on ((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno=head.trno 
        left join client on client.client=head.client
        where date(head.dateid) < '" . $end . "' " . $filter . "
        group by head.trno ,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem ,detail.line,
        coa.acno,coa.acnoname ,detail.db,detail.cr,coa.alias,detail.ref,detail.client,
        detail.rem,detail.checkno,coa.acnoid) as a
        left join coa on a.acno=coa.acno left join client on client.client = a.client 
        order by  acno,dateid,docno";
        break;
      case 2: // all
        $query = "select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,head.dateid as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) between '" . $start . "' and '" . $end . "'  " . $filter . " 
        group by  head.trno,head.doc,head.docno,head.dateid,client.client,
        head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr) as a
        left join coa on a.acno=coa.acno 

        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail
        from (select head.trno as trno,head.doc as doc,head.docno as docno,head.dateid as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
        coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,detail.rem as drem,
        detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr 
        from ((lahead as head left join ladetail as detail on ((head.trno = detail.trno)))
        left join coa on ((coa.acnoid = detail.acnoid)))left join cntnum on cntnum.trno=head.trno 
        left join client on client.client=head.client
        where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by head.trno ,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem ,detail.line,
        coa.acno,coa.acnoname ,detail.db,detail.cr,
        coa.alias,detail.ref,detail.client,detail.rem,
        detail.checkno,coa.acnoid) as a
        left join coa on a.acno=coa.acno 
        order by acno,dateid,docno";
        break;
    } // end switch

    $result = $this->coreFunctions->opentable($query);
    $bal = 0;
    foreach ($result as $key => $value) {
      if ($key == 0) {
        $bal = $value->begbal;
      } else {
        switch ($cat) {
          case 'L':
          case 'R':
          case 'C':
          case 'O':
            $bal = $bal + ($value->cr - $value->db);
            break;
          default:
            $bal = $bal + ($value->db - $value->cr);
            break;
        } // end switch
        $value->begbal = $bal;
      }
    } // end foreah
    return $result;
  }


  public function reportplotting($config)
  {

    $result = $this->default_query($config);
    $reportdata =  $this->DEFAULT_GENERAL_LEDGER_LAYOUT($config, $result);

    return $reportdata;
  }

  private function headerlabel($params)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    $center = $params['params']['dataparams']['center'];
    $costcenter = isset($params['params']['dataparams']['costcenter']) ? $params['params']['dataparams']['costcenter'] : "";

    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];

    $center1 = $params['params']['center'];
    $username = $params['params']['user'];


    if ($acnoname == "") {
      $acnoname = "ALL";
    } else {
      $acnoname = $acno . ' - ' . $acnoname;
    }

    switch ($isposted) {
      case 0:
        $isposted = 'posted';
        break;

      case 1:
        $isposted = 'unposted';
        break;

      case 2:
        $isposted = 'ALL';
        break;
    }

    if ($center == "") {
      $center = "ALL";
    }

    $dept   = $params['params']['dataparams']['ddeptname'];
    if ($costcenter != "") {
      $costcenter = $params['params']['dataparams']['name'];
    } else {
      $costcenter = "ALL";
    }

    if ($dept != "") {
      $deptname = $params['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('GENERAL LEDGER', 300, null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        break;
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('Center : ' . $center, null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('Project : ' . $costcenter, null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        break;
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($isposted), null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Accounts: ' . strtoupper($acnoname), null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date', '100', null, false, '1px solid', 'B', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('Reference No.', '150', null, false, '1px solid', 'B', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('Description', '350', null, false, '1px solid', 'B', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function default_subtotal($db, $cr, $bal)
  {
    $str = '';
    $fontsize = 9;
    $font = "Century Gothic";

    $col3 = array(
      array('60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
    );
    $value2 = array('', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
    $str .= $this->reporter->row($col3, $value2);

    return $str;
  } // end fn

  private function DEFAULT_GENERAL_LEDGER_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    $center = $params['params']['dataparams']['center'];
    $costcenter = isset($params['params']['dataparams']['costcenter']) ? $params['params']['dataparams']['costcenter'] : "";
    $costcenterid = isset($params['params']['dataparams']['costcenterid']) ? $params['params']['dataparams']['costcenterid'] : 0;
    $acno = $params['params']['dataparams']['contra'];
    $client = $params['params']['dataparams']['client'];

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);
    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(cr-db,2)),0) ';
        break;

      default:
        $field = ' ifnull(sum(round(db-cr,2)),0) ';
        break;
    }

    $filter = "";
    if ($acno != "ALL") {
      $filter .= " and coa.acno='\\" . $acno . "'";
    }

    if ($costcenter != "") {
      $filter .= " and head.projectid = '" . $costcenterid . "'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "'";
    }

    if ($client != "") {
      $filter .= " and client.client='" . $client . "' ";
    }

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';

    $fontsize = 9;
    $font = "Century Gothic";

    $col = array(
      array('60', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
    );

    $col2 = array(
      array('60', null, false, '1px solid', '', 'C', 'Century Gothic', '9', 'B', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', 'B', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
    );

    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params);

    $totaldb = 0;
    $totalcr = 0;
    $db = 0;
    $cr = 0;
    $bal = 0;
    $acno = '';
    $acno2 = '';

    if (!empty($data)) {
      foreach ($data as $key => $data_) {

        if ($acno2 != $data_->acno) { // account groupings
          if ($acno2 != '') { // subtotal for accounts
            $str .= $this->default_subtotal($db, $cr, $bal);
            $db = 0;
            $cr = 0;
            $bal = 0;
          }

          $value2 = array($data_->acno . '     -', $data_->acnoname, '', '', '', '', '');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->row($col2, $value2);
          $str .= $this->reporter->endrow();

          $query = $this->generalledger_query($field, $start, $end, $data_->acno, $isposted, $filter);
          $data1 = $this->coreFunctions->opentable($query);
          $result = json_decode(json_encode($data1), true);

          $chkqry = $this->begbal_chkqry($field, $start, $end, $data_->acno, $isposted, $filter);
          $bdat = $this->coreFunctions->opentable($chkqry);
          $bdata = json_decode(json_encode($bdat), true);

          $bal = 0;
          for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['docno'] == 'Beginning Balance') {
              $bal = $result[$i]['begbal'];
            } else {
              switch ($cat) {
                case 'L':
                case 'R':
                case 'C':
                case 'O':
                  $bal += ($result[$i]['cr'] - $result[$i]['db']);
                  break;
                default:
                  $bal += ($result[$i]['db'] - $result[$i]['cr']);
                  break;
              } // end switch
              $result[$i]['begbal'] = $bal;
            }
          } // end for loop

          if (empty($bdata) || $bdat[0]->begbal == 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->endrow();
          }

          for ($i = 0; $i < count($result); $i++) {
            $str .= $this->reporter->addline();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($result[$i]['dateid'], '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');

            $str .= $this->reporter->col($result[$i]['docno'], '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col($result[$i]['rem'], '120', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col(number_format($result[$i]['db'], 2), '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col(number_format($result[$i]['cr'], 2), '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->col(number_format($result[$i]['begbal'], 2), '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
            $str .= $this->reporter->endrow();

            $totaldb += $result[$i]['db'];
            $totalcr += $result[$i]['cr'];
            $db += $result[$i]['db'];
            $cr += $result[$i]['cr'];
            $bal = $result[$i]['begbal'];

            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->printline();
              $str .= $this->reporter->page_break();
              $str .= $this->headerlabel($params);
              $page = $page + $count;
            } // end if
          } // end foreach loop

          $acno2 = $data_->acno;
        }
      }
    } else {

      $center = $params['params']['dataparams']['center'];
      $costcenter = isset($params['params']['dataparams']['costcenter']) ? $params['params']['dataparams']['costcenter'] : "";
      $costcenterid = isset($params['params']['dataparams']['costcenterid']) ? $params['params']['dataparams']['costcenterid'] : 0;
      $acno = $params['params']['dataparams']['contra'];

      if ($acno == "") {
        $acno = "ALL";
      }

      $filter = "";
      if ($costcenter != "") {
        $filter .= " and head.projectid = '" . $costcenterid . "'";
      }
      if ($center != "") {
        $filter .= " and cntnum.center='" . $center . "'";
      }
      if ($client != "") {
        $filter .= " and client.client='" . $client . "' ";
      }
      if ($acno != "ALL") {
        $filter .= " and coa.acno='\\" . $acno . "'";
      }

      $query = $this->general_query($field, $start, $end, $acno, $isposted, $filter);
      $data1 = $this->coreFunctions->opentable($query);
      $result = json_decode(json_encode($data1), true);

      $chkqry = $this->begbal1_chkqry($field, $start, $end, $acno, $isposted, $filter);
      $bdat = $this->coreFunctions->opentable($chkqry);
      $bdata = json_decode(json_encode($bdat), true);

      $bal = 0;
      for ($i = 0; $i < count($result); $i++) {
        if ($result[$i]['docno'] == 'Beginning Balance') {
          $bal = $result[$i]['begbal'];
        } else {
          switch ($cat) {
            case 'L':
            case 'R':
            case 'C':
            case 'O':
              $bal += ($result[$i]['cr'] - $result[$i]['db']);
              break;
            default:
              $bal += ($result[$i]['db'] - $result[$i]['cr']);
              break;
          } // end switch
          $result[$i]['begbal'] = $bal;
        }
      } // end for loop


      if (empty($bdata)) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        if ($companyid == 3) { //conti
          $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        }
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        if ($companyid == 3) { //conti
          $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '4px');
        }
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->col(number_format($bdata[0]['begbal'], 2), '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '4px');
        $str .= $this->reporter->endrow();
        $bal = $bdata[0]['begbal'];
      }
    }

    $str .= $this->default_subtotal($db, $cr, $bal);

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function begbal_chkqry($field, $start, $end, $acno, $isposted, $filter)
  {
    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,
        '' as clientname,'' as  ref,'' as checkno,
        '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,
        coa.detail, coa.alias, null as postdate
        from ( 
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 

        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 

        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a
        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias";
        break;
      case 1: // unposted 
        $query = "
        select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem, '' as acno,
        '' as acnoname,0 as db,0 as cr,$field as begbal, '' as detail, null as alias, null as postdate
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr from
        ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a
        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null 
        order by  acno,dateid,docno
        ";
        break;
      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,
        '' as clientname,'' as ref,'' as checkno,
        '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,
        coa.detail, coa.alias, null as postdate
        from ( 
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 

        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 

        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a
        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias

        UNION ALL

        select null as dateid,'Beginning Balance' as docno,'' as client,
        '' as clientname,'' as ref,'' as checkno,
        '' as rem, '' as acno,'' as acnoname,0 as db,0 as cr,$field as begbal, 
        '' as detail, null as alias, null as postdate
        from ( 
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 

        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 

        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a

        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null 
        order by  acno,dateid,docno";
        break;
    } // end switch
    return $query;
  }

  private function begbal1_chkqry($field, $start, $end, $acno, $isposted, $filter)
  {
    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr, 
        ifnull(sum(round(db-cr,2)),0)  as begbal
        from (
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr

        from ((((glhead as head
        left join gldetail as detail on((head.trno = detail.trno)))
        left join coa on((coa.acnoid = detail.acnoid)))
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid)))
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' " . $filter . "

        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,
        head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a

        left join coa on a.acno=coa.acno
        left join client on client.client = a.client
        where coa.acno is not null
        group by coa.acno,coa.acnoname,coa.detail, coa.alias";
        break;
      case 1: // unposted 
        $query = "select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr,$field as begbal
        from ( 
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 

        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 

        where date(head.dateid) < '" . $start . "' " . $filter . " 

        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a
        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null";
        break;
      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr,
        ifnull(sum(round(db-cr,2)),0)  as begbal
        from (
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr

        from ((((glhead as head
        left join gldetail as detail on((head.trno = detail.trno)))
        left join coa on((coa.acnoid = detail.acnoid)))
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid)))
        left join cntnum on cntnum.trno=head.trno

        where date(head.dateid) < '" . $start . "' " . $filter . "
        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,
        head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a
        left join coa on a.acno=coa.acno
        left join client on client.client = a.client
        where coa.acno is not null
        group by coa.acno,coa.acnoname,coa.detail, coa.alias

        UNION ALL

        select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr,$field as begbal
        from ( 
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 

        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 

        where date(head.dateid) < '" . $start . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid

        ) as a
        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null";
        break;
    } // end switch

    return $query;
  }

  private function generalledger_query($field, $start, $end, $acno, $isposted, $filter)
  {
    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
        '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate,projname
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr, proj.name as projname
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line = head.projectid
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,
        coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,proj.name) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias, a.projname
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,a.projname
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line= head.projectid
        where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,coa.acno,coa.acnoname,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,proj.name) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null
        order by  acno,dateid,docno";
        break;
      case 1: // unposted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,
        '' as ref,'' as checkno,'' as rem, '' as acno,'' as acnoname,0 as db,0 as cr,
        $field as begbal, '' as detail, null as alias, null as postdate,projname
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname
        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line = head.projectid
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,proj.name) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias, a.projname
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, a.alias, date(postdate) as postdate,a.projname
        from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,
        detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname
        from ((lahead as head 
        left join ladetail as detail on ((head.trno = detail.trno)))
        left join coa on ((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line =head.projectid
        left join client on client.client=head.client
        where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,
        coa.acno,coa.acnoname,
        coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem ,
        detail.checkno ,coa.acnoid,proj.name) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        order by  acno,dateid,docno";
        break;
      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
        '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        
        UNION ALL

        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,
        head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line, coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate
        from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,detail.rem as drem,
        detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr 
        from ((lahead as head 
        left join ladetail as detail on ((head.trno = detail.trno)))
        left join coa on ((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno=head.trno 
        left join client on client.client=head.client
        where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr ,
        coa.alias,detail.ref ,detail.client,detail.rem ,
        detail.checkno ,coa.acnoid, detail.postdate) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        order by  acno,dateid,docno";
        break;
    } // end switch
    return $query;
  } // end fn



  private function general_query($field, $start, $end, $acno, $isposted, $filter)
  {
    switch ($isposted) {
      case 0: // posted 
        $query = "
        select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
        '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate,projname
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line = head.projectid
        where date(head.dateid) < '" . $start . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,proj.name) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias,a.projname
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,a.projname
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line = head.projectid
        where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line, coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,proj.name
        ) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null
        order by  acno,dateid,docno";
        break;
      case 1: // unposted 
        $query = "
        select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem, '' as acno,
        '' as acnoname,0 as db,0 as cr,$field as begbal, '' as detail, null as alias, null as postdate,projname
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname from
        ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line = head.projectid
        where date(head.dateid) < '" . $start . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid,proj.name) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias, a.projname
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, a.alias, date(postdate) as postdate, a.projname
        from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,detail.rem as drem,
        detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname
        from ((lahead as head 
        left join ladetail as detail on ((head.trno = detail.trno)))
        left join coa on ((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno=head.trno 
        left join projectmasterfile as proj on proj.line= head.projectid
        where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr ,
        coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem ,
        detail.checkno ,coa.acnoid,proj.name) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        order by acno,dateid,docno";
        break;
      case 2: // all 
        $query = "
        select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
        '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr from
        ((((glhead as head left join gldetail as detail on((head.trno = detail.trno))) left join coa
        on((coa.acnoid = detail.acnoid))) left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        
        UNION ALL

        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr from
        ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno 
        where date(head.dateid) < '" . $start . "' " . $filter . " group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a
        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null 
        group by coa.acno,coa.acnoname,coa.detail, coa.alias
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr from
        ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line, coa.acno,coa.acnoname,detail.db,detail.cr,
        coa.alias,detail.ref,detail.postdate,dclient.client,
        detail.rem,detail.checkno,coa.acnoid
        ) as a
        left join coa on a.acno=coa.acno 
        where coa.acno is not null 
        
        UNION ALL

        select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,'(',a.ref,')') end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate
        from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
        coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,detail.rem as drem,
        detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr 
        from ((lahead as head 
        left join ladetail as detail on ((head.trno = detail.trno)))
        left join coa on ((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno=head.trno 
        left join client on client.client=head.client
        where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        group by head.trno,head.doc,head.docno,head.dateid,
        client.client,head.clientname,head.rem,detail.line,
        coa.acno,coa.acnoname,detail.db,detail.cr ,
        coa.alias,detail.ref ,detail.client,detail.rem ,
        detail.checkno ,coa.acnoid, detail.postdate) as a
        left join coa on a.acno=coa.acno 
        left join client on client.client = a.client
        where coa.acno is not null 
        order by acno,dateid,docno";
        break;
    } // end switch

    return $query;
  } // end fn


}//end class