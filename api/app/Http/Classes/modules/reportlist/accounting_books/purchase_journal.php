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

ini_set('memory_limit', '-1');

class purchase_journal
{
  public $modulename = 'Purchase Journal';
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

    $fields = ['dateid', 'due', 'dclientname', 'dcentername'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    $fields = ['radioreporttype'];
    $col3 = $this->fieldClass->create($fields);

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

        return $this->coreFunctions->opentable(
          "select 'default' as print,
          adddate(left(now(),10),-360) as dateid,
          left(now(),10) as due,
          0 as clientid,
          '' as client,
          '' as clientname,
          '' as dclientname,
          '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
          '0' as reporttype,
          '0' as posttype,
          '' as contra,
          '' as acnoname,
          '' as dacnoname"
        );
        break;
      default:
        return $this->coreFunctions->opentable(
          "select 'default' as print,
          adddate(left(now(),10),-360) as dateid,
          left(now(),10) as due,
          0 as clientid,
          '' as client,
          '' as clientname,
          '' as dclientname,
          '' as center,
          '' as centername,
          '' as dcentername,
          '0' as reporttype,
          '0' as posttype,
          '' as contra,
          '' as acnoname,
          '' as dacnoname"
        );
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
    $client = $filters['params']['dataparams']['client'];
    $center = $filters['params']['dataparams']['center'];
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $reporttype = $filters['params']['dataparams']['reporttype'];
    $companyid = $filters['params']['companyid'];

    switch ($companyid) {
      case 15: //nathina
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $condition = " cntnum.doc in ('SN','DM','AP','PV')";
        break;
      default:
        $condition = " cntnum.doc = 'RR' "; // default
        break;
    }

    $filter = "";
    if ($center != "") {
      $filter .= "and cntnum.center = '" . $center . "'  ";
    } //end if
    if ($client != "") {
      $clientid = $filters['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    } //end if

    $hjcselect = '';
    $hjcselectd = '';

    if ($companyid == 8) { //maxipro
      $hjcselect = " union all select 'p' as tr, 'pj' as bk, coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
      from (glhead as head left join gldetail as detail on detail.trno=head.trno)
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid = head.clientid                                
      left join coa on coa.acnoid=detail.acnoid
      where $condition 
      and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
      group by coa.acno, coa.acnoname ";

      $hjcselectd = " union all select tr, bk, dateid,dateid2, docno, type, clientname, sum(regdb) as regdb, sum(regcr) as regcr, sunaccno, sunacctname, sundb, suncr from (
      select 'p' as tr, 'pj' as bk, left(head.dateid,10) as dateid,head.dateid as dateid2,
      concat(left(head.docno,4),right(head.docno,5)) as docno, 'regular' as type, head.clientname,
      ifnull(case when left(coa.alias,2)='AP' then detail.cr-detail.db else 0 end, 0) as regcr,
      ifnull(case when left(coa.alias,2)='IN' then detail.db-detail.cr else 0 end, 0) as regdb,
      '' as sunaccno, '' as sunacctname, 0 as sundb, 0 as suncr
      from ((hjchead as head left join gldetail as detail on detail.trno=head.trno)
      left join coa on coa.acnoid=detail.acnoid)
      left join client on client.client = head.client
      left join cntnum on cntnum.trno=head.trno
      where  cntnum.doc = 'JC'
      and head.dateid between '" . $startdate . "' and '" . $enddate . "'
      and left(coa.alias, 2) in ('ap','in')) as c
      group by tr, bk, dateid,dateid2, docno, type, clientname,c.sunaccno,c.sunacctname,c.sundb,c.suncr ";
    }

    switch ($reporttype) {
      case 1:
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $query = "select left(head.dateid,10) as dateid,head.dateid as dateid2, concat(left(head.docno,4), right(head.docno,5)) as docno,
            head.clientname, ifnull(sum(stock.ext),0) as amt, head.tax, head.rem, head.invoiceno, left(head.invoicedate,10) as invoicedate
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join cntnum on cntnum.trno=head.trno
            left join gldetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            where cntnum.doc='RR' and head.dateid between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
            and left(coa.alias,2) in ('ap','in') group by head.dateid, docno, head.clientname, amt, head.tax, head.rem, head.invoiceno, invoicedate
            
            UNION ALL

            select left(head.dateid,10) as dateid,head.dateid as dateid2, concat(left(head.docno,4), right(head.docno,5)) as docno,
            head.clientname, ifnull(sum(stock.ext),0) as amt, head.tax, head.rem, head.invoiceno, left(head.invoicedate,10) as invoicedate
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join cntnum on cntnum.trno=head.trno
            left join gldetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            where cntnum.doc='RR' and head.dateid between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
            and left(coa.alias,2) not in ('ap','in') group by head.dateid, docno, head.clientname, amt, head.tax, head.rem, head.invoiceno, invoicedate
            order by dateid2, docno
            ";
            break;
          default:
            $query = "
            select tr, bk, dateid, dateid2, docno, type, clientname, sum(regdb) as regdb, sum(regcr) as regcr, sunaccno, sunacctname, sundb, suncr from (
            select 'p' as tr, 'pj' as bk, left(head.dateid,10) as dateid, head.dateid as dateid2,
            concat(left(head.docno,4),right(head.docno,5)) as docno, 'regular' as type, head.clientname,
            ifnull(case when left(coa.alias,2)='AP' then detail.cr-detail.db else 0 end, 0) as regcr, 
            ifnull(case when left(coa.alias,2)='IN' then detail.db-detail.cr else 0 end, 0) as regdb, 
            '' as sunaccno, '' as sunacctname, 0 as sundb, 0 as suncr
            from ((glhead as head left join gldetail as detail on detail.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
            left join client on client.clientid = head.clientid                                
            left join cntnum on cntnum.trno=head.trno
            where $condition
            and head.dateid between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
            and left(coa.alias, 2) in ('ap','in')) as c
            group by tr, bk, dateid, dateid2, docno, type, clientname,c.sunaccno,c.sunacctname,c.sundb,c.suncr

            UNION ALL

            select 'p2' as tr, 'pj' as bk, left(head.dateid,10) as dateid,  head.dateid as dateid2,
            concat(left(head.docno,4),right(head.docno,5)) as docno, 'sundries' as type, head.clientname,
            0 as regdb, 0 as regcr, ifnull(coa.acno, '') as sunaccno, ifnull(coa.acnoname,'') as sunacctname,
            ifnull(sum(detail.db), 0) as sundb, ifnull(sum(detail.cr), 0) as suncr
            from ((glhead as head left join gldetail as detail on detail.trno=head.trno) left join coa on coa.acnoid=detail.acnoid)
            left join client on client.clientid = head.clientid                                
            left join cntnum on cntnum.trno=head.trno
            where $condition
            and  date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
            and left(coa.alias, 2) not in ('ap','in')
            group by head.dateid, head.docno, coa.acno, coa.acnoname, head.clientname $hjcselectd
            order by docno,type
            ";
            break;
        }
        break;

      case 0:
        $query = "select tr,bk,acno,description,debit,credit from (
        select 'p' as tr, 'pj' as bk, coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
        from (glhead as head left join gldetail as detail on detail.trno=head.trno)
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid = head.clientid                                
        left join coa on coa.acnoid=detail.acnoid
        where $condition 
        and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
        group by coa.acno, coa.acnoname $hjcselect ) as k
        where acno is not null
        group by tr,bk,acno,description,debit,credit
        order by credit
        ";
        break;
    } // end switch
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $result = $this->default_query($config);
    $companyid = $config['params']['companyid'];

    if ($config['params']['dataparams']['reporttype'] == 1) {
      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $reportdata = $this->DEFAULT_PURCHASE_JOURNAL_DETAILED_AFTI($result, $config);
          break;
        case 15: //nathina
        case 17: //unihome
        case 28: //xcomp
        case 39: //CBBSI
          $reportdata = $this->MSJOY_PURCHASE_JOURNAL_DETAILED($result, $config);
          break;
        default:
          $reportdata =  $this->DEFAULT_PURCHASE_JOURNAL_DETAILED($result, $config);
          break;
      }
    } else {
      switch ($companyid) {
        case 15: //nathina
        case 17: //unihome
        case 28: //xcomp
        case 39: //CBBSI
          $reportdata =  $this->MSJOY_PURCHASE_JOURNAL_SUMMARIZED($result, $config);
          break;
        default:
          $reportdata =  $this->DEFAULT_PURCHASE_JOURNAL_SUMMARIZED($result, $config);
          break;
      }
    }

    return $reportdata;
  }

  private function MSJOY_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $str = '';
    $fontsize10 = 10;
    $reporttype = $params['params']['dataparams']['reporttype'];
    if ($reporttype == 1) {
      $str .= $this->reporter->printline();
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '100', null, false, '1px solid ', 'B', 'L', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('SUPPLIER NAME', '200', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('INVENTORY DEBIT', '75', null, false, '1px solid ', 'B', 'L', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('A/P CREDIT', '50px', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', '200', null, false, '1px solid ', 'B', 'L', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ACCOUNT CODE', 100, null, false, '1px solid ', 'B', 'L', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 500, null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', 100, null, false, '1px solid ', 'B', 'R', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', 100, null, false, '1px solid ', 'B', 'R', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    } //end if
    return $str;
  }

  private function MSJOY_HEADER($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = 10;
    $str = '';
    $client = $params['params']['dataparams']['client'];
    $center = $params['params']['dataparams']['center'];
    $centername = $params['params']['dataparams']['centername'];
    $startdate = $params['params']['dataparams']['dateid'];
    $enddate = $params['params']['dataparams']['due'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

    if ($reporttype == 1) {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center1, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DETAILED PURCHASE JOURNAL', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

      if ($client == '') {
        $str .= $this->reporter->col('Supplier :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Supplier :' . $client, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } //end if

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      if ($center == '') {
        $str .= $this->reporter->col('Center :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Center :' . $center . ' - ' . $centername, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      }
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center1, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= '<br><br>';
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUMMARIZED PURCHASE JOURNAL', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

      if ($client == '') {
        $str .= $this->reporter->col('Supplier :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Supplier :' . $client, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } //end if

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      if ($center == '') {
        $str .= $this->reporter->col('Center :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Center :' . $center . ' - ' . $centername, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      }
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
    } //end if

    return $str;
  } //end fn

  private function MSJOY_DETAIL($params, $field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($field1, '75', null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field2, '100', null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field3, '175', null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field4, '75', null, '', $border, '', 'r', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field5, '75', null, '', $border, '', 'r', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field6, '150', null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field7, '75', null, '', $border, '', 'r', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field8, '75', null, '', $border, '', 'r', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function MSJOY_PURCHASE_JOURNAL_SUMMARIZED($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = 10;
    $fontsize11 = 11;
    $str = '';
    $count = 60;
    $page = 59;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_HEADER($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

    $totalsdb = 0;
    $totalscr = 0;

    foreach ($data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $cr = number_format($value->credit, 2);

      if ($cr == 0) {
        $cr = '-';
      } //end if

      $db = number_format($value->debit, 2);

      if ($db == 0) {
        $db = '-';
      } //end if

      $str .= $this->reporter->col($value->acno, 100, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, 500, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($db, 100, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($cr, 100, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      $totalsdb = $totalsdb + $value->debit;
      $totalscr = $totalscr + $value->credit;
      $str .= $this->reporter->endrow();


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_HEADER($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('GRAND TOTAL :', 100, null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', 500, null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(NUMBER_FORMAT($totalsdb, 2), 100, null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(NUMBER_FORMAT($totalsdb, 2), 100, null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function MSJOY_PURCHASE_JOURNAL_DETAILED($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = 10;
    $fontsize12 = 12;
    $count = 51;
    $page = 50;
    $str = '';

    $this->reporter->linecounter = 0;

    if (empty($data)) return $this->othersClass->emptydata($params);

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_HEADER($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

    $totalrdb = 0;
    $totalrcr = 0;
    $totalsdb = 0;
    $totalscr = 0;
    $docno = "";
    $clname = "";
    $date = "";

    foreach ($data as $key => $value) {
      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $clname = "";
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

          $str .= $this->reporter->endrow();
        }
        $docno = $value->docno;
        $date = $value->dateid;
        $clname = $value->clientname;
      }

      $suncr = number_format($value->suncr, 2);

      if ($suncr == 0) {
        $suncr = '-';
      } //end if

      $sundb = number_format($value->sundb, 2);

      if ($sundb == 0) {
        $sundb = '-';
      } //end if

      $regcr = number_format($value->regcr, 2);

      if ($regcr == 0) {
        $regcr = '-';
      } //end if

      $regdb = number_format($value->regdb, 2);

      if ($regdb == 0) {
        $regdb = '-';
      } //endif

      $str .= $this->MSJOY_DETAIL($params, $date, $docno, $clname, $regdb, $regcr, $value->sunacctname, $sundb, $suncr);

      $totalrdb = $totalrdb + $value->regdb;
      $totalrcr = $totalrcr + $value->regcr;
      $totalsdb = $totalsdb + $value->sundb;
      $totalscr = $totalscr + $value->suncr;

      $docno = $value->docno;
      $date = $value->dateid;
      $clname = $value->clientname;


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_HEADER($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('TOTAL :', '225', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalrdb, 2), '75', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalrcr, 2), '75', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsdb, 2), '75', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalscr, 2), '75', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


  private function default_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $str = '';
    $reporttype = $params['params']['dataparams']['reporttype'];
    if ($reporttype == 1) {
      switch ($params['params']['companyid']) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->begintable('1200');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '100', null, false, '1px solid', 'LTRB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Invoice', '100', null, false, '1px solid', 'LTRB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Supplier', '350', null, false, '1px solid', 'LTRB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Remarks', '350', null, false, '1px solid', 'LTRB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Net Amount', '100', null, false, '1px solid', 'LTRB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Vat Amount', '100', null, false, '1px solid', 'LTRB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Grand Total', '100', null, false, '1px solid', 'LTRB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          break;
        default:
          $str .= $this->reporter->begintable('1200');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '110', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DOC #', '120', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('SUPPLIER NAME', '200', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('INVENTORY DEBIT', '135', null, false, '1px solid ', 'TB', 'RT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('A/P CREDIT', '135', null, false, '1px solid ', 'TB', 'RT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('ACCNT DESCRIPTION', '200', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DEBIT', '150', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('CREDIT', '150', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          break;
      }
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ACCOUNT CODE', 100, null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 400, null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', 150, null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', 150, null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } //end if
    return $str;
  }

  private function GENERATE_DEFAULT_HEADER($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = 10;
    $str = '';
    $client = $params['params']['dataparams']['client'];
    $center = $params['params']['dataparams']['center'];
    $centername = $params['params']['dataparams']['centername'];
    $startdate = $params['params']['dataparams']['dateid'];
    $enddate = $params['params']['dataparams']['due'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];
    if ($reporttype == 1) {
          $str .= $this->reporter->begintable('1200');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->letterhead($center1, $username, $params);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
    

      $str .= '<br>';

      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DETAILED PURCHASE JOURNAL', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

      if ($client == '') {
        $str .= $this->reporter->col('Supplier :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Supplier :' . $client, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } //end if

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      if ($center == '') {
        $str .= $this->reporter->col('Center :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Center :' . $center . ' - ' . $centername, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      }
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      
          $str .= $this->reporter->begintable('800');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->letterhead($center1, $username, $params);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

      $str .= '<br>';
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUMMARIZED PURCHASE JOURNAL', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      if ($client == '') {
        $str .= $this->reporter->col('Supplier :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Supplier :' . $client, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } //end if
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      if ($center == '') {
        $str .= $this->reporter->col('Center :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col('Center :' . $center . ' - ' . $centername, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      }
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
    } //end if

    return $str;
  } //end fn

  private function GENERATE_DEFAULT_DETAIL($params, $field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($field1, '110', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field2, '120', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field3, '200', null, '', $border, '', 'LT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field4, '135', null, '', $border, '', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field5, '135', null, '', $border, '', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field6, '200', null, '', $border, '', 'LT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field7, '150', null, '', $border, '', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($field8, '150', null, '', $border, '', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function DEFAULT_PURCHASE_JOURNAL_DETAILED($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = 10;
    $fontsize12 = 12;
    $count = 51;
    $page = 50;
    $str = '';

    $this->reporter->linecounter = 0;

    if (empty($data)) return $this->othersClass->emptydata($params);

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize10, $params);

    $totalrdb = 0;
    $totalrcr = 0;
    $totalsdb = 0;
    $totalscr = 0;
    $docno = "";
    $clname = "";
    $date = "";

    foreach ($data as $key => $value) {
      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $clname = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', 110, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 120, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 200, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 135, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 135, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 200, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 150, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 150, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $value->docno;
        $date = $value->dateid;
        $clname = $value->clientname;
      }

      $suncr = number_format($value->suncr, 2);

      if ($suncr == 0) {
        $suncr = '-';
      } //end if

      $sundb = number_format($value->sundb, 2);

      if ($sundb == 0) {
        $sundb = '-';
      } //end if

      $regcr = number_format($value->regcr, 2);

      if ($regcr == 0) {
        $regcr = '-';
      } //end if

      $regdb = number_format($value->regdb, 2);

      if ($regdb == 0) {
        $regdb = '-';
      } //endif

      $str .= $this->GENERATE_DEFAULT_DETAIL($params, $date, $docno, $clname, $regdb, $regcr, $value->sunacctname, $sundb, $suncr);

      $totalrdb = $totalrdb + $value->regdb;
      $totalrcr = $totalrcr + $value->regcr;
      $totalsdb = $totalsdb + $value->sundb;
      $totalscr = $totalscr + $value->suncr;

      $docno = $value->docno;
      $date = $value->dateid;
      $clname = $value->clientname;


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->GENERATE_DEFAULT_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->begintable('1200');
        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, false, '1px solid ', 'T', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid ', 'T', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('TOTAL :', '200', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalrdb, 2), '135', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalrcr, 2), '135', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsdb, 2), '150', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalscr, 2), '150', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function DEFAULT_PURCHASE_JOURNAL_SUMMARIZED($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = 10;
    $fontsize11 = 11;
    $str = '';
    $count = 60;
    $page = 59;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    $totalsdb = 0;
    $totalscr = 0;

    foreach ($data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $cr = number_format($value->credit, 2);

      if ($cr == 0) {
        $cr = '-';
      } //end if

      $db = number_format($value->debit, 2);

      if ($db == 0) {
        $db = '-';
      } //end if

      $str .= $this->reporter->col($value->acno, 100, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, 400, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($db, 150, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($cr, 150, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $totalsdb = $totalsdb + $value->debit;
      $totalscr = $totalscr + $value->credit;
      $str .= $this->reporter->endrow();


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->GENERATE_DEFAULT_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', 100, null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', 400, null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(NUMBER_FORMAT($totalsdb, 2), 150, null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(NUMBER_FORMAT($totalsdb, 2), 150, null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn


  private function DEFAULT_PURCHASE_JOURNAL_DETAILED_AFTI($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;
    $str = '';

    $totalamt = 0;
    $totalnetamt = 0;
    $totalvatamt = 0;

    $this->reporter->linecounter = 0;

    if (empty($data)) return $this->othersClass->emptydata($params);

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

    foreach ($data as $key => $value) {
      $amt = $value->amt;
      $netamt = 0;
      $vatamt = 0;
      if ($value->tax > 0) {
        $netamt = $amt / 1.12;
        $vatamt = $netamt * 0.12;
      } else {
        $netamt = $amt;
      }
      $invoice = substr($value->docno, 0, 2) . '' . substr($value->docno, -5);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->dateid, '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($invoice, '100', null, '', $border, 'LTRB', 'C', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->clientname, '350', null, '', $border, 'LTRB', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->invoiceno . ' ' . $value->invoicedate, '350', null, '', $border, 'LTRB', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($netamt == 0 ? '-' : number_format($netamt, 2), '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($vatamt == 0 ? '-' : number_format($vatamt, 2), '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($amt == 0 ? '-' : number_format($amt, 2), '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->endrow();

      $totalamt += $amt;
      $totalnetamt += $netamt;
      $totalvatamt += $vatamt;
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('TOTAL :', '350', null, false, '1px solid ', 'LTRB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '350', null, false, '1px solid ', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetamt, 2), '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalvatamt, 2), '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class