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


class journal_voucher
{
  public $modulename = 'Journal Voucher';
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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['start', 'end', 'dcentername'];
    switch ($companyid) {
      case 19: //housegem
        array_push($fields, 'dclientname');
        break;
      case 24: //goodfound
        array_push($fields, 'dacnoname');
        break;
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', 'StartDate');
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.label', 'EndDate');
    data_set($col2, 'end.readonly', false);

    data_set($col2, 'dclientname.label', 'Customer/Supplier');
    data_set($col2, 'dclientname.lookupclass', 'lookupgjclient');

    data_set($col2, 'dacnoname.action', 'lookupcoa');
    data_set($col2, 'dacnoname.lookupclass', 'detail');
    data_set($col2, 'dacnoname.label', 'Account Description');

    $fields = ['radioposttype', 'radioreporttype'];
    $col3 = $this->fieldClass->create($fields);
    data_set(
      $col3,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];

        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable("select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '0' as reporttype,'0' as posttype,'' as contra,'' as acnoname,'' as dacnoname, '' as dclientname, '' as client, '' as clientname,
        '' as contra,
        '' as acnoname,
        '' as dacnoname");

      default:
        return $this->coreFunctions->opentable("select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as center,
        '' as centername,
        '' as dcentername,
        '0' as reporttype,'0' as posttype,'' as contra,'' as acnoname,'' as dacnoname, '' as dclientname, '' as client, '' as clientname");
        break;
    }
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
    $filter = "";
    $center = $filters['params']['dataparams']['center'];
    $companyid = $filters['params']['companyid'];
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $posttype = $filters['params']['dataparams']['posttype'];
    $reporttype = $filters['params']['dataparams']['reporttype'];
    $client = $filters['params']['dataparams']['client'];
    $acno = $filters['params']['dataparams']['contra'];

    if ($acno == "") {
      $acno = "ALL";
    }

    if ($acno != "ALL") {
      $filter .= " and coa.acno='\\" . $acno . "' ";
    }


    if ($center != "") {
      $filter .= " and cntnum.center = '" . $center . "' ";
    } //end if

    if ($client != "") {
      $filter .= " and client.client = '" . $client . "' ";
    }
    switch ($companyid) {
      case 15: //nathina
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $condition = " cntnum.doc in ('MI','IS','AJ','GJ','DS')"; // NATHINA/UNIHOME
        break;
      default:
        $condition = " cntnum.doc = 'GJ' "; // default
        break;
    }

    switch ($posttype) {

      case 0: //posted
        if ($reporttype == 1) { //detailed
          $query = "select 'p' as tr, 'cr' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, detail.ref, detail.db as debit, detail.cr as credit, date(cntnum.postdate) as postdate
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    order by docno,credit";
        } else { //summarized
          $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                    select 'p' as tr, 'cr' as bk,coa.acno, coa.acnoname as description, detail.db as debit, detail.cr as credit
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . ") as x 
                    where description is not null
                    group by acno, description
                    order by credit";
        }
        //end if
        break;
      case 1: //unposted
        if ($reporttype == 1) { //detailed
          $query = "select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, cntnum.center, sum(detail.db) as debit,
                    detail.ref, sum(detail.cr) as credit, date(cntnum.postdate) as postdate
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    group by head.docno, head.rem, head.clientname,head.dateid,
                    coa.acno, coa.acnoname, cntnum.center,detail.ref, cntnum.postdate
                    order by docno,credit";
        } else { //summarized
          $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                    select 'u' as tr, 'cd' as bk,coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    group by coa.acno, coa.acnoname, cntnum.center
                    ) as x where description is not null group by acno, description order by credit";
        } //end if

        break;
      case 2: //all
        if ($reporttype == 1) { //detailed posted and unposted
          $query = " select   tr,  bk, docno, rem, clientname,  dateid,acno, description, ref,  debit, credit, postdate
                    from (
                    select 'p' as tr, 'cr' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, cntnum.center,  sum(detail.db) as debit,
                    detail.ref, sum(detail.cr) as credit, date(cntnum.postdate) as postdate
                    from ((glhead as head
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where  $condition
                    and date(head.dateid)  between '" . $startdate . "' and '" . $enddate . "'  $filter
                    group by head.docno, head.rem, head.clientname,head.dateid,
                    coa.acno, coa.acnoname, cntnum.center,detail.ref, cntnum.postdate

                    union all
                    select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, cntnum.center, sum(detail.db) as debit,
                    detail.ref, sum(detail.cr) as credit, date(cntnum.postdate) as postdate

                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "'  $filter
                    group by head.docno, head.rem, head.clientname,head.dateid,
                    coa.acno, coa.acnoname, cntnum.center,detail.ref, cntnum.postdate) as x
                    order by docno,credit ";
        } else { //summarized unposted and posted
          $query = "select acno, description, sum(debit) as debit, sum(credit) as credit

                from (
                    select 'p' as tr, 'cr' as bk,coa.acno, coa.acnoname as description, detail.db as debit, detail.cr as credit
                    from ((glhead as head
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where $condition
                    and date(head.dateid)  between '" . $startdate . "' and '" . $enddate . "'  $filter

                    union all

                    select 'u' as tr, 'cd' as bk,coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "'  $filter
                    group by coa.acno, coa.acnoname, cntnum.center
                    ) as x where description is not null group by acno, description order by credit";
        }
        break;
    } //end if

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->default_query($config);
    if ($config['params']['dataparams']['reporttype'] == 1) {
      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
        case 41: //labsol paranaque
        case 52: //technolab
          $reportdata =  $this->VITALINE_JOURNAL_VOUCHER_DETAILED($result, $config);
          break;
        case 15: //nathina
        case 17: //unihome
        case 28: //xcomp
        case 39: //CBBSI
          $reportdata =  $this->MSJOY_JOURNAL_VOUCHER_DETAILED($result, $config);
          break;
        default:
          $reportdata =  $this->DEFAULT_JOURNAL_VOUCHER_DETAILED($result, $config);

          break;
      }
    } else {
      switch ($companyid) {
        case 15: //nathina
        case 17: //unihome
        case 28: //xcomp
        case 39: //CBBSI
          $reportdata =  $this->MSJOY_JOURNAL_VOUCHER_SUMMARIZED($result, $config);
          break;
        default:
          $reportdata =  $this->DEFAULT_JOURNAL_VOUCHER_SUMMARIZED($result, $config);

          break;
      }
    }
    return $reportdata;
  }

  private function MSJOY_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $str = '';
    $companyid = $params['params']['companyid'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    if ($reporttype == 1) {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();

      switch ($params['params']['companyid']) {
        case 1: //vitaline
        case 23: // lab sol
          $str .= $this->reporter->col('POSTED DATE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          break;
      }
      $str .= $this->reporter->col('DOCUMENT #', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PAYEE NAME PARTICULARS', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('ACCNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('REFFERENCE #', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
    } else {

      $str .= $this->reporter->begintable('800');

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->endrow();
    }
    return $str;
  }

  private function MSJOY_JOURNAL_VOUCHER_HEADER($params)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';

    $str = '';

    $startdate = $params['params']['dataparams']['start'];
    $enddate = $params['params']['dataparams']['end'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    if ($reporttype == 1) {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br/><br/>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('DETAILED JOURNAL VOUCHER', null, null, '', '1px solid ', '', 'l', $font, '18', 'b', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('SUMMARIZED JOURNAL VOUCHER', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
    }

    return $str;
  }

  private function MSJOY_JOURNAL_VOUCHER_DETAILED($data, $params)
  {
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;

    $str = '';
    $count = 41;
    $page = 40;

    $this->reporter->linecounter = 0;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_JOURNAL_VOUCHER_HEADER($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totalardb = 0;
    $totalrcr = 0;
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";
    $rem = "";
    $db = 0;
    $cr = 0;

    foreach ($data as $key => $data) {
      if ($docno == $data->docno) {
        $docno = "";
        $date = "";
        $cname = "";
        $rem = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $data->docno;
        $date = $data->dateid;
        $cname = $data->clientname;
        $rem = $data->rem;
      }

      $debit = number_format($data->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($data->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($docno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($cname . "<br>" . $rem, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->col($date, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->ref, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;

      $docno = $data->docno;
      $date = $data->dateid;
      $cname = $data->clientname;
      $rem = $data->rem;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();



        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_JOURNAL_VOUCHER_HEADER($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);


        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function MSJOY_JOURNAL_VOUCHER_SUMMARIZED($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;

    $str = '';
    $count = 60;
    $page = 59;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_JOURNAL_VOUCHER_HEADER($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totalardb = 0;
    $totalrcr = 0;
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";
    $db = 0;
    $cr = 0;

    foreach ($data as $key => $data) {
      $debit = number_format($data->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($data->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_JOURNAL_VOUCHER_HEADER($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach


    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, '', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'C', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  private function default_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $str = '';
    $companyid = $params['params']['companyid'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    if ($reporttype == 1) {
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();

      switch ($params['params']['companyid']) {
        case 1: //vitaline
        case 23: //labsol cebu 
          $str .= $this->reporter->col('POSTED DATE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DOCUMENT #', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PAYEE NAME PARTICULARS', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DATE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('ACCNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('REFFERENCE #', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
          break;
        default:
          $str .= $this->reporter->col('DOCUMENT #', 120, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PAYEE NAME PARTICULARS', 250, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DATE', 110, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('ACCNT CODE', 100, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 200, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('REFFERENCE #', 120, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DEBIT', 150, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('CREDIT', 150, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
          break;
      }
    } else {

      $str .= $this->reporter->begintable('800');

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ACCOUNT CODE', 100, null, '', '1px solid ', 'B', 'l', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 400, null, '', '1px solid ', 'B', 'C', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->col('DEBIT', 150, null, '', '1px solid ', 'B', 'r', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->col('CREDIT', 150, null, '', '1px solid ', 'B', 'r', $font, $fontsize + 1, 'b', '', '');
      $str .= $this->reporter->endrow();
    }
    return $str;
  }

  private function DEFAULT_JOURNAL_VOUCHER_HEADER($params)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
     $companyid = $params['params']['companyid'];
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';

    $str = '';

    $startdate = $params['params']['dataparams']['start'];
    $enddate = $params['params']['dataparams']['end'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];

    if ($acnoname == "") {
      $acnoname = "ALL";
    } else {
      $acnoname = $acno . ' - ' . $acnoname;
    }


    $posttype = $params['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:
        $post = 'Posted';
        break;
      case 1:
        $post = 'Unposted';
        break;
      default:
        $post = 'All';
        break;
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    if ($reporttype == 1) {

      
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br/><br/>';

      $str .= $this->reporter->begintable('1200', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('DETAILED JOURNAL VOUCHER', null, null, '', '1px solid ', '', 'l', $font, '18', 'b', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Account: ' . strtoupper($acnoname), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    
      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('SUMMARIZED JOURNAL VOUCHER', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
    }

    return $str;
  }

  private function DEFAULT_JOURNAL_VOUCHER_DETAILED($data, $params)
  {
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;
    $str = '';
    $count = 41;
    $page = 40;

    $this->reporter->linecounter = 0;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totalardb = 0;
    $totalrcr = 0;
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";
    $rem = "";
    $db = 0;
    $cr = 0;

    foreach ($data as $key => $data) {
      if ($docno == $data->docno) {
        $docno = "";
        $date = "";
        $cname = "";
        $rem = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $data->docno;
        $date = $data->dateid;
        $cname = $data->clientname;
        $rem = $data->rem;
      }

      $debit = number_format($data->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($data->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($docno, 120, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($cname . "<br>" . $rem, 250, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->col($date, 110, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->acno, 100, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->description, 200, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->ref, 120, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, 150, null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, 150, null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;

      $docno = $data->docno;
      $date = $data->dateid;
      $cname = $data->clientname;
      $rem = $data->rem;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 120, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', 250, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', 110, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', 100, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', 200, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', 120, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function DEFAULT_JOURNAL_VOUCHER_SUMMARIZED($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;

    $str = '';
    $count = 60;
    $page = 59;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

    $totalardb = 0;
    $totalrcr = 0;
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";
    $db = 0;
    $cr = 0;

    foreach ($data as $key => $data) {
      $debit = number_format($data->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($data->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->acno, 100, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->description, 400, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, 150, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, 150, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach


    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, '', '12', '', '');
    $str .= $this->reporter->col('', 100, null, '', '1px solid ', 'T', 'c', '', $font, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', 400, null, '', '1px solid ', 'T', 'C', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  private function VITALINE_JOURNAL_VOUCHER_DETAILED($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;

    $str = '';
    $count = 25;
    $page = 25;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totalardb = 0;
    $totalrcr = 0;
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $postdate = "";
    $cname = "";
    $db = 0;
    $cr = 0;

    foreach ($data as $key => $data) {
      if ($docno == $data->docno) {
        $docno = "";
        $date = "";
        $postdate = "";
        $cname = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $data->docno;
        $date = $data->dateid;
        $postdate = $data->postdate;
        $cname = $data->clientname;
      }

      $debit = number_format($data->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($data->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($postdate, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($docno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($cname, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($date, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->ref, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;

      $docno = $data->docno;
      $date = $data->dateid;
      $cname = $data->clientname;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);


        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class