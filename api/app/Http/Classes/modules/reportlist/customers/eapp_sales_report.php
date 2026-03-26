<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class eapp_sales_report
{
  public $modulename = 'Sales Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $fields = ['radioprint', 'month', 'year', 'prepared', 'noted'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'month.required', true);
    data_set($col1, 'year.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $paramstr = "select 'default' as print,month(current_date()) as month, year(current_date()) as year, '' as prepared, '' as noted";
    return $this->coreFunctions->opentable($paramstr);
  }

  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $result = $this->reportDefaultLayout_REPORT($config);
    return $result;
  }

  public function reportDefault($config)
  {
    $month = $config['params']['dataparams']['month'];
    $year = $config['params']['dataparams']['year'];

    $filter = "";
    if ($month != '') $filter .= " and month(head.dateid)='" . $month . "' ";
    if ($year != '') $filter .= " and year(head.dateid)='" . $year . "' ";
   
    $query = "select date(head.dateid) as dateid, head.docno, concat(info.lname, ' ', info.fname, ' ', info.mname) as planholder,
        ifnull((case info.issenior when 1 then pt.amount/1.12 else pt.amount end),0) as amount, '' as catpay,
      (select  cra.dateid  from (select cr.dateid,cr.yourref,cr.trno from lahead as cr
        union all
       select cr.dateid,cr.yourref,cr.trno from glhead as cr ) as cra where cra.yourref=head.docno order by cra.dateid limit 1) as initdate,
      head.terms,
      (select  cra.amount  from (select cr.amount,cr.yourref,cr.trno,cr.dateid from lahead as cr
        union all
       select cr.amount,cr.yourref,cr.trno,cr.dateid from glhead as cr ) as cra where cra.yourref=head.docno order by cra.dateid limit 1) as initamt,
      pt.name as plantype, '' as datereg, '' as begbal, '' as sold, '' as endbal, '' as prevbal, '' as soldval, '' as curbal
      from lahead as head
        left join heahead as ehead on ehead.trno=head.aftrno
        left join client on client.client=ehead.client
        left join heainfo as info on info.trno=head.aftrno
        left join plantype as pt on pt.line=ehead.planid
      where head.doc='CP' " . $filter . "
      group by head.dateid, head.docno, planholder, initdate, initamt, catpay, pt.amount, info.issenior, head.terms, pt.name
      union all
      select date(head.dateid) as dateid, head.docno, concat(info.lname, ' ', info.fname, ' ', info.mname) as planholder,
        ifnull((case info.issenior when 1 then pt.amount/1.12 else pt.amount end),0) as amount, '' as catpay,
      (select  cra.dateid  from (select cr.dateid,cr.yourref,cr.trno from lahead as cr
        union all
       select cr.dateid,cr.yourref,cr.trno from glhead as cr ) as cra where cra.yourref=head.docno order by cra.dateid limit 1) as initdate,
      head.terms,
      (select  cra.amount  from (select cr.amount,cr.yourref,cr.trno,cr.dateid from lahead as cr
        union all
       select cr.amount,cr.yourref,cr.trno,cr.dateid from glhead as cr ) as cra where cra.yourref=head.docno order by cra.dateid limit 1) as initamt,
      pt.name as plantype, '' as datereg, '' as begbal, '' as sold, '' as endbal, '' as prevbal, '' as soldval, '' as curbal
      from glhead as head
        left join heahead as ehead on ehead.trno=head.aftrno
        left join client on client.clientid=ehead.client
        left join cntnum on cntnum.trno=ehead.trno
        left join heainfo as info on info.trno=head.aftrno
        left join plantype as pt on pt.line=ehead.planid
      where head.doc='CP' " . $filter . "
      group by head.dateid, head.docno, planholder, initdate, initamt, catpay, pt.amount, info.issenior, head.terms, pt.name
      order by docno";
    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault2($config)
  {
    $month = $config['params']['dataparams']['month'];
    $year = $config['params']['dataparams']['year'];
    $filter = '';
    if ($month != '') $filter .= " and month(head.dateid)='" . $month . "'";
    if ($year != '') $filter .= " and year(head.dateid)='" . $year . "'";
    $query = "select count(head.trno) as sold, pt.name as plantype, '' as datereg, '' as begbal, '' as endbal, '' as prevbal, '' as soldval, '' as curbal
      from lahead as head
      left join heahead as ehead on ehead.trno=head.aftrno
      left join plantype as pt on pt.line=ehead.planid
        where head.doc='CP' " . $filter . "
      group by pt.name
    union all
    select count(head.trno) as sold, pt.name as plantype, '' as datereg, '' as begbal, '' as endbal, '' as prevbal, '' as soldval, '' as curbal
      from glhead as head
      left join heahead as ehead on ehead.trno=head.aftrno
      left join plantype as pt on pt.line=ehead.planid
        where head.doc='CP' " . $filter . "
      group by pt.name";
    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_query($config)
  {
    return '';
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $month = $config['params']['dataparams']['month'];
    $year = $config['params']['dataparams']['year'];

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Plan Type: Life', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $monthName = date("F", strtotime('00-' . $month . '-01'));
    $str .= $this->reporter->col('For the month of ' . $monthName . ' ' . $year, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 100, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('A. Schedule of Plans/Contracts Sold', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('No.', '75', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Name of Planholder', '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Plan/Contract No.', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Contract Price Per Plan', '75', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date of Issue', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date of Initial Payment', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Initial Payment Made', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category (Fully Paid/Installment', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_displayHeader2($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $month = $config['params']['dataparams']['month'];
    $year = $config['params']['dataparams']['year'];

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 100, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('B. Summary of Plans/Contracts Sold', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Type of Plan /', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date of Product Registration /', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('No. of Registered Plans / Contracts', '300', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount of Registered Plans / Contracts', '300', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Product', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Approval', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Balance Beg.', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sold', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Balance End', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Balance Previous Month (VAT Ex.)', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sold (vAT Ex.)', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Balance Current Month (VAT Ex.)', '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout_REPORT($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault($config);
    $result2 = $this->reportDefault2($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $prepared = $config['params']['dataparams']['prepared'];
    $noted = $config['params']['dataparams']['noted'];
    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $totalamt = 0;
    $totalinitamt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($key + 1, '75', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->planholder, '150', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '75', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->initdate, '100', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data->initamt > 0 ? number_format($data->initamt, 2) : ''), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data->initamt > 0 ? ($data->terms == 'Spot Cash' ? 'Fully Paid' : 'Installment') : ''), '100', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalamt += $data->amount;
      $totalinitamt += $data->initamt;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->col('TOTAL', '75', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '75', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalinitamt, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'LBR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br /><br />';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Prepared by: ' . $prepared, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Noted by: ' . $noted, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->default_displayHeader2($config);

    $total1 = 0;
    $total2 = 0;
    $total3 = 0;
    $total4 = 0;
    $total5 = 0;
    $total6 = 0;

    foreach ($result2 as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->plantype, '100', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->datereg, '100', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->begbal, '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->sold, '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->endbal, '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->prevbal, '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->soldval, '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->curbal, '100', null, false, $border, 'LBR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $total1 += $data->begbal;
      $total2 += $data->sold;
      $total3 += $data->endbal;
      $total4 += $data->prevbal;
      $total5 += $data->soldval;
      $total6 += $data->curbal;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader2($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page += $count;
      }
    }
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($total1, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total2, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total3, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total4, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total5, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total6, 2), '100', null, false, $border, 'LBR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br /><br />';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Prepared by: ' . $prepared, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Noted by: ' . $noted, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class