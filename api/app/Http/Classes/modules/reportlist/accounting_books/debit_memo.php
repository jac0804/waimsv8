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

class debit_memo
{
  public $modulename = 'Debit Memo';
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

    return $this->coreFunctions->opentable("select 'default' as print,adddate(left(now(),10),-360) as dateid,left(now(),10) as due,'' as center,'' as centername,'' as dcentername,'0' as reporttype,'0' as posttype,'' as contra,'' as acnoname,'' as dacnoname");
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
        $query = "select 'p' as tr, 'cr' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, detail.ref, detail.db as debit, detail.cr as credit
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    where cntnum.doc = 'GD'
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    order by docno,credit";
      } else {
        $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                    select 'p' as tr, 'cr' as bk,coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    where cntnum.doc = 'GD'
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . " group by coa.acno,coa.acnoname
                    ) as x group by acno, description order by credit";
      } //end if
    } else {
      if ($reporttype == 1) {
        $query = "select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, cntnum.center, sum(detail.db) as debit,
                    detail.ref, sum(detail.cr) as credit
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where cntnum.doc = 'GD'
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    group by head.docno, head.clientname,head.dateid,coa.acno, coa.acnoname, cntnum.center, head.rem,detail.ref
                    order by head.docno,detail.cr";
      } else {
        $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                    select 'u' as tr, 'cd' as bk, coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where cntnum.doc = 'GD'
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    group by coa.acno, coa.acnoname, cntnum.center
                    ) as x group by acno, description order by credit";
      } //end if
    } //end if
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $result = $this->default_query($config);
    if ($config['params']['dataparams']['reporttype'] == 1) {
      $reportdata =  $this->DEFAULT_DEBIT_MEMO_DETAILED($result, $config);
    } else {
      $reportdata =  $this->DEFAULT_DEBIT_MEMO_SUMMARIZED($result, $config);
    }
    return $reportdata;
  }

  private function DEFAULT_HEADER($params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $fontsize10 = '10';

    $str = '';
    $center = $params['params']['dataparams']['center'];
    $startdate = $params['params']['dataparams']['dateid'];
    $enddate = $params['params']['dataparams']['due'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    $companyid = $params['params']['companyid'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    $qry = "select name,address,tel from center where code = '" . $center1 . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    if ($reporttype == 1) {
      $str .= $this->reporter->beginreport();
      if ($companyid == 3) { //conti
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center1 . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      } else {
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      $str .= '<br/><br/>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('DETAILED DEBIT MEMO', null, null, '', '1px solid ', '', 'l', $font, '18', 'b', '', '');
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
      $str .= $this->reporter->col('DOCUMENT #', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('PAYEE NAME PARTICULARS', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DATE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('REFFERENCE #', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
    } else {
      if ($companyid == 3) { //conti
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center1 . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      } else {
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('SUMMARIZED DEBIT MEMO', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
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

  private function DEFAULT_DEBIT_MEMO_DETAILED($data, $params)
  {
    $font = 'Century Gothic';
    $fontsize10 = '10';

    $str = '';
    $count = 25;
    $page = 25;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER($params);

    // $totalardb = 0;
    // $totalrcr = 0;
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";
    // $db = 0;
    // $cr = 0;

    foreach ($data as $key => $data) {
      if ($docno == $data->docno) {
        $docno = "";
        $date = "";
        $cname = "";
      } else {
        $docno = $data->docno;
        $date = $data->dateid;
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

        $str .= $this->DEFAULT_HEADER($params);

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

  private function DEFAULT_DEBIT_MEMO_SUMMARIZED($data, $params)
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
    $str .= $this->DEFAULT_HEADER($params);

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
        $str .= $this->DEFAULT_HEADER($params);
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
}//end class