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

class general_journal
{
  public $modulename = 'General Journal';
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

    $fields = ['dateid', 'due', 'dcentername'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    $fields = ['radioposttype', 'radioreporttype'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("
    select 'default' as print,
    adddate(left(now(),10),-360) as dateid,
    left(now(),10) as due,
    '' as center,
    '' as centername,
    '' as dcentername,
    '0' as reporttype,
    '0' as posttype,
    '' as contra,
    '' as acnoname,
    '' as dacnoname");
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
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $posttype = $filters['params']['dataparams']['posttype'];
    $reporttype = $filters['params']['dataparams']['reporttype'];

    if ($center != "") {
      $filter .= " and cntnum.center = '" . $center . "' ";
    } //end if

    if ($posttype == 0) {
      if ($reporttype == 1) {
        $query = "select 'p' as tr, 'cr' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,head.dateid as dateid2,
                   coa.acno, coa.acnoname as description, detail.ref,
                  detail.db as debit, detail.cr as credit, date(cntnum.postdate) as postdate
                  from ((glhead as head
                  left join gldetail as detail on detail.trno=head.trno)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  where cntnum.doc = 'GJ'
                  and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                  order by dateid2,docno";
      } else {
        $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                  select 'p' as tr, 'cr' as bk,coa.acno, coa.acnoname as description, detail.db as debit, detail.cr as credit
                  from ((glhead as head 
                  left join gldetail as detail on detail.trno=head.trno)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  where cntnum.doc = 'GJ'
                  and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . ") as x group by acno, description
                  order by credit";
      } //end if
    } else {
      if ($reporttype == 1) {
        $query = "select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,head.dateid as dateid2,
                  coa.acno, 
                  coa.acnoname as description, cntnum.center, sum(detail.db) as debit,
                  detail.ref, sum(detail.cr) as credit, date(cntnum.postdate) as postdate
                  from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                  left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                  left join client on client.client=head.client
                  where cntnum.doc = 'GJ'
                  and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                  group by head.docno, head.rem, head.clientname,head.dateid,
                  coa.acno, coa.acnoname, cntnum.center,detail.ref, cntnum.postdate
                  order by dateid2,docno";
      } else {
        $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                  select 'u' as tr, 'cd' as bk,coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
                  from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                  left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                  left join client on client.client=head.client
                  where cntnum.doc = 'GJ'
                  and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                  group by coa.acno, coa.acnoname, cntnum.center
                  ) as x group by acno, description order by  description";
      } //end if
    } //end if
    $this->coreFunctions->LogConsole($query);
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $result = $this->default_query($config);
    $companyid = $config['params']['companyid'];
    if ($config['params']['dataparams']['reporttype'] == 1) { // detailed
      if ($companyid == 10) { //afti
        $reportdata =  $this->AFTI_JOURNAL_VOUCHER_DETAILED($result, $config);
      } else {
        $reportdata =  $this->DEFAULT_JOURNAL_VOUCHER_DETAILED($result, $config);
      }
    } else { // summarized
      $reportdata =  $this->DEFAULT_JOURNAL_VOUCHER_SUMMARIZED($result, $config);
    }
    return $reportdata;
  }

  private function DEFAULT_JOURNAL_VOUCHER_HEADER($params)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $font = 'Century Gothic';
    $fontsize10 = '10';

    $str = '';
    $startdate = $params['params']['dataparams']['dateid'];
    $enddate = $params['params']['dataparams']['due'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    if ($reporttype == 1) {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br/><br/>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DETAILED GENERAL JOURNAL', null, null, '', '1px solid ', '', 'l', $font, '18', 'b', '', '');
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



      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();

      switch ($params['params']['companyid']) {
        case 1: //vitaline
          $str .= $this->reporter->col('POSTED DATE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
          break;
        case 10: //afti
          $str .= $this->reporter->col('DATE', 100, null, '', '1px solid', 'TBRL', 'C', $font, $fontsize10, 'B', '', '');
          break;
      }
      $str .= $this->reporter->col('REFERENCE #', 100, null, '', '1px solid ', 'TBRL', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT', 200, null, '', '1px solid ', 'TBRL', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', 100, null, '', '1px solid ', 'TBRL', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', 100, null, '', '1px solid ', 'TBRL', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DETAILS', 400, null, '', '1px solid ', 'TBRL', 'C', $font, $fontsize10, 'B', '', '');
    } else {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUMMARIZED GENERAL JOURNAL', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
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

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->endrow();
    }

    return $str;
  }

  private function DEFAULT_JOURNAL_VOUCHER_DETAILED($data, $params)
  {
    $font = 'Century Gothic';
    $fontsize10 = '10';
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

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $rem = "";

    foreach ($data as $key => $data) {
      $docno = $data->docno;
      $date = date('m/d/Y', strtotime($data->dateid));;
      $rem = $data->rem;

      $debit = number_format($data->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($data->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      if ($rem != "") {
        $rem = '<br>' . $rem;
      }
      if ($docno != "") {
        $docno = '<br>' . $docno;
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 10) { //afti
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->col($date, null, null, '', '1px solid', 'TBRL', 'LM', $font, $fontsize10, '', '', '');
        } else {
          $str .= $this->reporter->col($date, null, null, '', '1px solid', 'TRL', 'LM', $font, $fontsize10, '', '', '');
        }
      }
      $str .= $this->reporter->col($docno, null, null, '', '1px solid ', 'TBRL', 'LM', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->description, null, null, '', '1px solid ', 'TBRL', 'LM', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', 'TBRL', 'RM', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', 'TBRL', 'RM', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($rem, null, null, '', '1px solid ', 'TBRL', 'LT', $font, $fontsize10, '', '', '');

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);

        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
  private function AFTI_JOURNAL_VOUCHER_DETAILED($data, $params)
  {
    $font = 'Century Gothic';
    $fontsize10 = '10';
    $str = '';
    $page = 40;

    $this->reporter->linecounter = 0;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $rem = "";

    foreach ($data as $key => $data) {
      $docno = $data->docno;
      $date = date('m/d/Y', strtotime($data->dateid));;
      $rem = $data->rem;

      $debit = number_format($data->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($data->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      if ($data->ref != "") {
        $ref = '<br>' . $data->ref;
      }

      if ($rem != "") {
        $rem = '<br>' . $rem;
      }
      if ($docno != "") {
        $docno = '<br>' . $docno;
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 10) { //afti
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->col($date, null, null, '', '1px solid', 'TBRL', 'LM', $font, $fontsize10, '', '', '');
        } else {
          $str .= $this->reporter->col($date, null, null, '', '1px solid', 'TRL', 'LM', $font, $fontsize10, '', '', '');
        }
      }
      $str .= $this->reporter->col($docno, null, null, '', '1px solid ', 'TBRL', 'LM', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data->description, null, null, '', '1px solid ', 'TBRL', 'LM', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', 'TBRL', 'RM', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', 'TBRL', 'RM', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($rem, null, null, '', '1px solid ', 'TBRL', 'L', $font, $fontsize10, '', '', '');

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;

      $str .= $this->reporter->endrow();
    } // end foreach

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col('SUB TOTAL: ', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function DEFAULT_JOURNAL_VOUCHER_SUMMARIZED($data, $params)
  {
    $font = 'Century Gothic';
    $fontsize10 = '10';
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

    $totaldb = 0;
    $totalcr = 0;

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
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', '', 0, '', 1);

      $totaldb = $totaldb + $data->debit;
      $totalcr = $totalcr + $data->credit;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach


    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, '', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', $font, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'C', $font, $fontsize10, 'b', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function VITALINE_JOURNAL_VOUCHER_DETAILED($data, $params)
  {
    $font = 'Century Gothic';
    $fontsize10 = '10';
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
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $postdate = "";
    $cname = "";

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

        $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);

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