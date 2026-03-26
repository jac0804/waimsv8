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

class monthly_summary_of_creditable_withholding_tax
{
  public $modulename = 'Monthly Summary of Creditable Withholding Tax';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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

    $fields = ['dateid', 'due', 'dcentername'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'ddeptname.label', 'Department');
        break;
      default:
        $col2 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $paramstr = "select 'default' as print,
      adddate(left(now(),10),-360) as dateid,left(now(),10) as due,
      '' as center,
      '' as centername,
      '' as dcentername, 
      0 as deptid, '' as ddeptname, '' as dept, '' as deptname";

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
    $companyid = $filters['params']['companyid'];
    $startdate  = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $enddate    = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center     = $filters['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    if ($center != "") {
      $filter .= " and cnt.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $filters['params']['dataparams']['ddeptname'];
      if ($deptname != "") {
        $deptid = $filters['params']['dataparams']['deptid'];
        $filter1 .= " and h.deptid=" . $deptid;
      }
    }

    $qry = "
      select * from (
        select h.docno, date(h.dateid) as dateid, c.clientname as customer, c.client, c.tin, c.addr, sum(d.db) as db, sum(d.cr-d.db) as purchases, ewt.rate, ewt.code
        from lahead as h
        left join ladetail as d on h.trno=d.trno
        left join client as c on c.client=h.client
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        where (d.isvewt = 1 OR d.isewt = 1 or coa.alias in ('WT2','ARWT') ) and cnt.doc='CR'  and ewt.rate is not null
        and date(h.dateid) between '$startdate' and '$enddate' $filter $filter1
        group by h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code
        union all 
        select h.docno, date(h.dateid) as dateid, c.clientname as customer, c.client, c.tin, c.addr, sum(d.db) as db, sum(d.cr-d.db) as purchases, ewt.rate, ewt.code
        from glhead as h
        left join gldetail as d on h.trno=d.trno
        left join client as c on c.clientid=h.clientid
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        where  (d.isvewt = 1 OR d.isewt = 1 or coa.alias in ('WT2','ARWT') )  and cnt.doc='CR'  and ewt.rate is not null
        and date(h.dateid) between '$startdate' and '$enddate' $filter $filter1
        group by h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code ) as tbl
      order by code, client, docno";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  }

  public function reportplotting($config)
  {

    $result = $this->default_query($config);

    $reportdata =  $this->DEFAULT_EWT_LAYOUT($result, $config);

    return $reportdata;
  }

  private function DEFAULT_EWT_HEADER($params)
  {
    $username   = $params['params']['user'];
    $ccenter   = $params['params']['center'];
    $companyid = $params['params']['companyid'];

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $params['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $params['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($ccenter, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Monthly Summary of Creditable Withholding Tax', null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period of ' . date('m/d/y', strtotime($start)) . ' - ' . date('m/d/y', strtotime($end)), null, null, false, $border, '', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
      $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '950', null, false, $border, '', '', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
      $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Doc #', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Customer', '175', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('ATC', '100', '', false, $border, 'TB', 'C', $font, $fontsize, 'b', '', '', '');
        $str .= $this->reporter->col('Tax Base', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('CWT%', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('CWT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '', '600');
        $str .= $this->reporter->col('Customer', '175', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Address', '175', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Doc #', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('ATC', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('CWT%', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('CWT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_EWT_LAYOUT($data, $params)
  {
    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $col = array(
      array('100', '', false, $border, '', 'l', $font, '',  '', '', '', ''),
      array('125', '', false, $border, '', 'l', $font, '',  '', '', '', ''),
      array('175', '', false, $border, '', 'l', $font, '',  '', '', '', ''),
      array('125', '', false, $border, '', 'l', $font, '',  '', '', '', ''),
      array('125', '', false, $border, '', 'l', $font, '',  '', '', '', ''),
      array('100', '', false, $border, '', 'r', $font, '',  '', '', '', ''),
      array('100', '', false, $border, '', 'r', $font, '',  '', '', '', ''),
      array('100', '', false, $border, '', 'r', $font, '',  '', '', '', ''),
    );
    $group = $str = '';
    $a = $b = $c = $totala = $totalb = $totalc = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    $str .= $this->reporter->beginreport('1000');

    #header here
    $str .= $this->DEFAULT_EWT_HEADER($params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable('1000');
    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      if (($group == '' || ($group != $data_->code && $data_->code != ''))) {
        if ($data_->code == '') {
          $group = 'NO DATE';
        } else {
          #subtotal here
          $str .= $this->DEFAULT_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);
          #subtotal end
          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          $group = $data_->code;
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
        if ($c == 0) {
          $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '');
        } else {
          $str .= $this->reporter->col('Sub Total: ' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '');
        } #endif
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $cwtpercentage = $data_->rate / 100;
      $cwt = $data_->purchases * $cwtpercentage;
      if ($cwtpercentage != 0) {
        $taxbase = $data_->db / $cwtpercentage;
      } else {
        $taxbase = $data_->db;
      }

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->customer, '175', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->code, '100', '', false, $border, '', 'c', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($taxbase, $decimal_currency), '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->rate . '%', '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->customer, '175', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->addr, '175', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($taxbase, $decimal_currency), '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->rate . '%', '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
          break;
      }


      $str .= $this->reporter->col(number_format($data_->db, $decimal_currency), '100', '1', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->endrow();

      $dateid = $data_->dateid;
      $a += $taxbase;
      $b += $data_->rate;
      $c += $data_->db;
      $totala = $totala + $taxbase;
      $totalb = $totalb + $data_->rate;
      $totalc = $totalc + $data_->db;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $str .= $this->DEFAULT_EWT_HEADER($params);
        #header end
        $str .= $this->reporter->begintable('1000');
        $page += $count;
      } # end if


      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($data_->docno == '') {
          $group = 'NO DATE';
        } else {
          #subtotal here
          $str .= $this->DEFAULT_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);
          #subtotal end

          $str .= $this->reporter->addline();

          $a = 0;
          $b = 0;
          $c = 0;
          $group = $data_->docno;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
      } # end if
      $str .= $this->reporter->endrow();
    } # end for loop


    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize, 'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_EWT_SUBTOTAL($a, $b, $c, $companyid, $params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->startrow();
    if ($c == 0) {
      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '');
          break;
      }
    } else {
      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col('SUBTOTAL', '100', '', false, '1px dashed', 'T', 'l', $font, $fontsize,  'b', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          break;
      }
      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '');
          break;
      }
    } #end if

    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class