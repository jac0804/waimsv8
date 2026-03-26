<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class daily_sales_collection_report
{
  public $modulename = 'Daily Sales Collection Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $fields = ['radioprint', 'start'];
    if ($companyid == '60') { // transpower
      array_push($fields, 'end');
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'Date');

    $fields = ['radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    // data_set(
    //   $col2,
    //   'radioposttype.options',
    //   [
    //     ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
    //     ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
    //     ['label' => 'All', 'value' => '2', 'color' => 'teal']
    //   ]
    // );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 
    'default' as print,
    adddate(left(now(), 10),-360) as start,
    left(now(),10) as end,
    '1' as reporttype";
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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];
    if ($reporttype == 0) { //summarized
      return $this->reportDefaultSummaryLayout($config);
    }
    return $this->reportDefaultDetailedLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    // $center      = $config['params']['dataparams']['center'];
    $center = '';
    $companyid = $config['params']['companyid'];
    if ($center == '') {
      $center = $config['params']['center'];
    }
    $query = $this->defaultQuery_posted($config);
    return $this->coreFunctions->opentable($query);
  }

  public function defaultQuery_unposted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";
    $date_rage = " and date(head.dateid)='" . $start . "'";
    if ($companyid == 60) { // transpower
      $date_rage = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    }
    if ($center != '') {
      $filter .= " and cntnum.center = '$center' ";
    }

    $query = "select 'sales' as tmpfield, head.dateid, abs(sum(detail.db-detail.cr)) as amount
      from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
      where left(coa.alias,2)='SA' $date_rage $filter
      group by head.dateid
      union all
      select 'expenses' as tmpfield, head.dateid, sum(detail.db-detail.cr) as amount
      from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
      where coa.cat='e' $date_rage $filter
      group by head.dateid
      order by tmpfield, dateid";
    return $query;
  }


  public function defaultQuery_posted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";

    $date_rage = " and date(head.dateid)='" . $start . "'";
    if ($companyid == 60) { // transpower
      $date_rage = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    }
    if ($center != '') {
      $filter .= " and cntnum.center = '$center' ";
    }
    // old 
    // $query = "select 'sales' as tmpfield, head.dateid, abs(sum(detail.db-detail.cr)) as amount
    //   from glhead as head
    //     left join gldetail as detail on detail.trno=head.trno
    //     left join coa on coa.acnoid=detail.acnoid
    //     left join cntnum on cntnum.trno=head.trno
    //   where left(coa.alias,2)='SA' $date_rage $filter
    //   group by head.dateid
    //   union all
    //   select 'expenses' as tmpfield, head.dateid, sum(detail.db-detail.cr) as amount
    //   from glhead as head
    //     left join gldetail as detail on detail.trno=head.trno
    //     left join coa on coa.acnoid=detail.acnoid
    //     left join cntnum on cntnum.trno=head.trno
    //   where coa.cat='e' $date_rage $filter
    //   group by head.dateid
    //   order by tmpfield, dateid";

    // new 2026-02-11
    $query = "select SUM(amount) as amount,DATE(dateid) as dateid,tmpfield FROM (
                select  'sales' as tmpfield, head.docno,head.dateid, head.clientname,  sum(detail.db-detail.cr) as amount,
                case when left(coa.alias,2)='CA' then 'CASH PAYMENT TRANSACTIONS'
                when left(coa.alias,2)='CR' then 'CHEQUE PAYMENT TRANSACTIONS'
                when left(coa.alias,2)='CB' then 'BTB PAYMENT TRANSACTIONS' end as acnoname,
                coa.alias,detail.checkno

                from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
                where left(coa.alias,2) in ('CA', 'CR', 'CB') and head.doc = 'CR'
				        $date_rage $filter
                group by head.dateid,head.docno, head.clientname, coa.alias,detail.checkno,head.docno
              
                union all
                select 'expenses' as tmpfield, head.docno, head.dateid, head.clientname, sum(detail.cr) as amount,coa.acnoname,coa.alias,detail.checkno
                from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('GD','GC','CV') and left(coa.alias,2)='CA' and detail.cr>0 
                $date_rage $filter
                group by head.dateid, head.docno, head.clientname,coa.acnoname,detail.checkno,coa.alias
               order by acnoname ) AS v GROUP BY dateid,tmpfield order by dateid";
    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['center'];
    $posttype    = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";

    $date_rage = " and date(head.dateid)='" . $start . "'";
    if ($companyid == 60) { // transpower
      $date_rage = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    }
    if ($center != '') {
      $filter .= " and cntnum.center = '$center' ";
    }

    $query = "select 'sales' as tmpfield, head.dateid, abs(sum(detail.db-detail.cr)) as amount
      from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
      where left(coa.alias,2)='SA' $date_rage $filter
      group by head.dateid
      union all
      select 'expenses' as tmpfield, head.dateid, sum(detail.db-detail.cr) as amount
      from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
      where coa.cat='e' $date_rage $filter
      group by head.dateid
      union all
      select 'sales' as tmpfield, head.dateid, abs(sum(detail.db-detail.cr)) as amount
      from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
      where left(coa.alias,2)='SA' $date_rage $filter
      group by head.dateid
      union all
      select 'expenses' as tmpfield, head.dateid, sum(detail.db-detail.cr) as amount
      from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
      where coa.cat='e' $date_rage $filter
      group by head.dateid
      order by tmpfield, dateid";
    return $query;
  }

  public function detailedQuery($config, $type, $trno)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center      = $config['params']['center'];
    $companyid      = $config['params']['companyid'];
    $filter = "";
    $date_rage = " and date(head.dateid)='" . $start . "'";
    if ($companyid == 60) { // transpower
      $date_rage = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    }
    if ($center != '') {
      $filter .= " and cntnum.center = '$center' ";
    }
    $query = "";

    //left(coa.alias,2) in ('CA', 'CR', 'CB')
    switch ($type) {
      case 1:
        $query = "
              select head.trno, head.clientname, head.rem, sum(detail.db-detail.cr) as amount,
                case when left(coa.alias,2)='CA' then 'CASH PAYMENT TRANSACTIONS'
                when coa.alias = 'CR1' then 'CHEQUE PAYMENT TRANSACTIONS'
                when left(coa.alias,2)='CB' and head.doc = 'DS' then 'CASH DEPOSIT TRANSACTIONS'
                when left(coa.alias,2)='CB' or left(coa.alias,3) = 'CRB' then 'BTB PAYMENT TRANSACTIONS' end as acnoname,
                case when left(coa.alias,2)='CA' then 1
                when left(coa.alias,2)='CR' then 2
                when left(coa.alias,2)='CB' then 3 end as groupid,coa.alias,detail.checkno,

                (select gldetail.ref from gldetail 
					      left join coa on coa.acnoid = gldetail.acnoid  
					      where gldetail.trno = head.trno and left(coa.alias,2) in ('AR') LIMIT 1) as reff

              from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
              where head.doc = 'CR' and (left(coa.alias,2) = 'CA' or coa.alias = 'CR1' or left(coa.alias,2) = 'CB' or left(coa.alias,3) = 'CRB') $date_rage $filter
              group by head.trno, head.clientname, head.rem, coa.alias,detail.checkno,head.doc
              order by acnoname,reff";
        break;
      case 2:
        $query = "
              select head.trno, detail.ref, detail2.db as amount,if(detail.cr <> 0,detail.cr,detail2.cr) as cr,coa.alias
              from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join arledger as detail2 on detail2.trno=detail.refx and detail2.line=detail.linex
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
              where head.trno = $trno and left(coa.alias,2) in ('AR') and detail.refx>0 and head.doc = 'CR' $date_rage $filter
              order by ref";
        break;
      case 3: // CASH DETAIL TRANSACTIONS
        $query = "
              select head.rem,date(detail.postdate) as postdate, sum(detail.cr-detail.db) as db
              from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
              where left(alias,2) in ('CA') and head.doc = 'DS' $date_rage $filter
              group by date(detail.postdate),head.rem";
        break;
      default:
        $query = "
              select head.docno, head.dateid, head.clientname, head.rem, sum(detail.cr) as amount,coa.alias
              from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
              where head.doc in ('GD','GC','CV') and left(coa.alias,2)='CA' and detail.cr>0 $date_rage $filter
              group by head.dateid, head.docno, head.clientname, head.rem,coa.alias
              order by dateid";
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function defaultDetailed_Header_layout($config, $title)
  {
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $rundate = date('Y-m-d h:i A', strtotime($this->othersClass->getCurrentTimeStamp()));
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid ';
    $layoutsize = '1000';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($title, $layoutsize, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 60) { //transpower
      $str .= $this->reporter->col('Date Range: ' . $start . ' - ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Date: ' . $start, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Run Date: ' . $rundate, 940, null, false, $border, '', '', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INV. NO.', 130, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', 250, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', 250, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES AMOUNT', 110, null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 10, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CHEQUE DETAILS', 150, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PAID AMOUNT', 100, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function defaultSummary_Header_layout($config, $title)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $rundate = date('Y-m-d h:i A', strtotime($this->othersClass->getCurrentTimeStamp()));
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = '800';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Daily Sales Collection Report - Summary', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 60) { //transpower
      $str .= $this->reporter->col('Date Range: ' . $start . ' - ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Date: ' . $start, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    }
    // $str .= $this->reporter->col('User: ' . $username, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Run Date: ' . $rundate, '500', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultDetailedLayout($config)
  {
    $data1 = $this->detailedQuery($config, 1, 0);
    $data4 = $this->detailedQuery($config, 4, 0);
    $data3 = $this->detailedQuery($config, 3, 0);
    $username = $config['params']['user'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid ';
    $layoutsize = '1000';
    if (empty($data1)) return $this->othersClass->emptydata($config);
    $count = 0;
    $page = 55;
    $this->reporter->linecounter = 0;
    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->defaultDetailed_Header_layout($config, 'DAILY SALES COLLECTION REPORT');
    $count = 0;
    $acnoname = $ref = $checkno = '';
    $amount = $db = $totalamount = $totalexpense = $gtotal = $totalcash = 0;
    $totals = $totals2 = [];

    foreach ($data1 as $dkey => $d1) {
      $ref = $checkno = '';
      $amount = $db = 0;

      if ($acnoname != $d1->acnoname) {
        if ($acnoname != '') {

          $totals[] = ['account' => $acnoname, 'total' => $totalamount];
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('TOTAL ' . $acnoname, 500, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalamount, 2), 300, null, false, '1px dashed', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $totalamount = 0;

        $acnoname = $d1->acnoname;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($d1->acnoname, null, null, false, $border, 'B', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      if ($count >= $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->defaultDetailed_Header_layout($config, "DOCUMENT SERIES SALES JOURNAL REPORT");
        $count = 0;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($d1->acnoname, null, null, false, $border, 'B', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      $drem = "";
      $concat_si = '';
      $customer_len = strlen($d1->clientname);

      $data2 = $this->detailedQuery($config, 2, $d1->trno);
      $con = false;
      foreach ($data2 as $d2key => $d2) {
        $ref = $d2->ref;

        $pos = strcspn($d2->ref, '0123456789');
        $letters = substr($d2->ref, 0, $pos);
        $numbers = (int) substr($d2->ref, $pos);
        if ($drem != '') {
          $drem .= ', ';
          $con = true;
        }
        $drem .=  $letters . '' . $numbers . '(' . number_format($d2->cr, 2) . ')';

        $amount = $d2->amount;
      }
      if ($con) {
        $concat_si = $drem;
      }
      $checkno = $d1->checkno;
      $checkno_len = strlen($checkno);
      $db = number_format($d1->amount, 2);
      $totalamount += $d1->amount;
      $concat_si_len = strlen($concat_si);
      $length = 0;
      $lenperline = 30;
      if ($customer_len > $concat_si_len) {
        $length = $customer_len;
      } else {
        $lenperline = 27;
        $length = $concat_si_len;
      }
      if ($checkno_len > $length) {
        $lenperline = 18;
        $length = $checkno_len;
      }

      if ($length >= $lenperline) {
        $length = ceil($length / $lenperline);
        $count += $length;
      } else {
        $count++;
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($ref, 130, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($d1->clientname, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($concat_si, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($amount, 2), 110, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 10, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($checkno, 150, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($db, 100, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $totals[] = ['account' => $acnoname, 'total' => $totalamount];
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL ' . $acnoname, 500, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamount, 2), 300, null, false, '1px dashed', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $totalcashdeposit = 0;
    if (!empty($data3)) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CASH DEPOSIT TRANSACTIONS', null, null, false, $border, 'B', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $count += 1;

      foreach ($data3 as $d3key => $d3) {
        $totalcashdeposit += $d3->db;
        $count++;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 130, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($d3->postdate, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('CASH DEPOSIT ' . $d3->rem, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 150, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($d3->db, 2), 100, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('TOTAL CASH DEPOSIT TRANSACTIONS: ', 500, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalcashdeposit, 2), 300, null, false, '1px dashed', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      if ($count >= $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->defaultDetailed_Header_layout($config, "DOCUMENT SERIES SALES JOURNAL REPORT");
        $count = 0;
      }
    }




    if (!empty($data4)) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('EXPENSE TRANSACTIONS', null, null, false, $border, 'B', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable($layoutsize);
      $count += 1;
      foreach ($data4 as $d4) {
        $totalexpense += $d4->amount;
        $count++;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($d4->docno, 130, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($d4->clientname, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($d4->rem, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('-', 120, null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 150, null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('(' . number_format($d4->amount, 2) . ')', 100, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('TOTAL EXPENSE TRANSACTIONS: ', 500, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('(' . number_format($totalexpense, 2) . ')', 300, null, false, '1px dashed', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      if (($count) + 6 >= $page) { //+6 for cash and sales summary
        $str .= $this->reporter->page_break();
        $str .= $this->defaultDetailed_Header_layout($config, "DOCUMENT SERIES SALES JOURNAL REPORT");
        $count = 0;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES SUMMARY', 350, null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 50, null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('CASH SUMMARY', 350, null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    usort($totals, function ($a, $b) {
      return strcmp($b['account'], $a['account']);
    });
    $totalchecksale = 0;
    $totalbtbpayment = 0;
    foreach ($totals as $tkey => $t) {
      $gtotal += $t['total'];

      switch ($t['account']) {
        case 'CASH PAYMENT TRANSACTIONS':
          $totalcash += $t['total'];
          break;
        case 'CHEQUE PAYMENT TRANSACTIONS':
          $totalchecksale += $t['total'];
          break;
        case 'BTB PAYMENT TRANSACTIONS':
          $totalbtbpayment += $t['total'];
          break;
        case 'Cash For Deposit':
          $totalcash += $t['total'];
          break;
      }
    }
    $expectedcash = ($totalcash - $totalexpense);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL CASH SALES:', 200, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalcash, 2), 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 50, null, false, $border, '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('TOTAL CASH SALES:', 200, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalcash, 2), 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL CHEQUE SALES:', 200, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalchecksale, 2), 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 50, null, false, $border, '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('TOTAL EXPENSES:', 200, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('(' . number_format(($totalexpense), 2) . ')', 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL BTB SALES:', 200, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalbtbpayment, 2), 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 50, null, false, $border, '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('TOTAL EXPECTED CASH:', 200, null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format(($totalcash - $totalexpense), 2), 150, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL CHEQUE REFUNDS:', 200, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 150, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 50, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL CASH DEPOSIT:', 200, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format(($totalcashdeposit), 2), 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('END OF DAY TOTAL SALES:', 200, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gtotal, 2), 150, null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 50, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('CASH OVER/SHORT:', 200, null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $cash_short = round($expectedcash - $totalcashdeposit, 2);
    // $this->coreFunctions->LogConsole($cash_short . ' - ' . $expectedcash . '-' . $totalcashdeposit);
    $str .= $this->reporter->col($cash_short != 0 ? number_format($cash_short, 2) : '-', 150, null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function reportDefaultSummaryLayout($config)
  {
    // PRINT LAYOUT

    $result     = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = '800';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // $reporttype = $config['params']['dataparams']['reporttype'];
    $count = 56;
    $page = 55;
    $this->reporter->linecounter = 0;
    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->defaultSummary_Header_layout($config, "DAILY SALES COLLECTION REPORT");

    $sales = $expenses = [];
    foreach ($result as $key => $data) {
      if ($data->tmpfield == 'sales') {
        array_push($sales, $data);
      } else {
        array_push($expenses, $data);
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL CASH SALES REPORT', 390, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->col('', 10, null, false, $border, 'TR', '', $font, $fontsize, '', '', '10px');
    $str .= $this->reporter->col('', 10, null, false, $border, 'T', '', $font, $fontsize, '', '', '10px');
    $str .= $this->reporter->col('TOTAL CASH SALES REPORT', 390, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', 150, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->col('TOTAL SALES', 240, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->col('', 10, null, false, $border, '', '', $font, $fontsize, '', '', '10px');
    $str .= $this->reporter->col('', 10, null, false, $border, 'L', '', $font, $fontsize, '', '', '10px');
    $str .= $this->reporter->col('DATE', 150, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '10px');
    $str .= $this->reporter->col('TOTAL EXPENSE', 240, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->endrow();

    $scount = count($sales);
    $ecount = count($expenses);
    $totalsales = $totalexpense = 0;
    $max = max($scount, $ecount);
    foreach (range(0, $max) as $t) {
      $totalsales += isset($sales[$t]) ? $sales[$t]->amount : 0;
      $totalexpense += isset($expenses[$t]) ? $expenses[$t]->amount : 0;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(isset($sales[$t]) ? $sales[$t]->dateid : '', 150, null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(isset($sales[$t]) ? number_format($sales[$t]->amount, 2) : '', 240, null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col('', 10, null, false, $border, '', '', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col('', 10, null, false, $border, 'L', '', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(isset($expenses[$t]) ? $expenses[$t]->dateid : '', 150, null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(isset($expenses[$t]) ? number_format($expenses[$t]->amount, 2) : '', 240, null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL:', 150, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalsales, 2), 240, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', 10, null, false, $border, 'T', '', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', 10, null, false, $border, 'T', '', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('TOTAL:', 150, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalexpense, 2), 240, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
}//end class