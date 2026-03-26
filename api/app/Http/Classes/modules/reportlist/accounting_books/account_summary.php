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

class account_summary
{
  public $modulename = 'Account Summary';
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
    data_set($col2, 'dateid.required', true);
    data_set($col2, 'due.required', true);

    $fields = ['radioposttype'];
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
    $center = $filters['params']['dataparams']['center'];
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $posttype = $filters['params']['dataparams']['posttype'];

    $filter = "";
    if ($center != "") {
      $filter .= " and cnt.center = '" . $center . "'  ";
    } //end if

    switch ($posttype) {
      case '0': // posted
        $query = "
          select coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join coa as coa on coa.acnoid = detail.acnoid
          left join cntnum as cnt on cnt.trno = head.trno
          where head.doc = 'CR' and left(coa.alias, 2) NOT IN ('CA', 'CB', 'CR') and
          date(head.dateid) between '$startdate' and '$enddate' " . $filter . "
          group by coa.acnoname, coa.acno
        ";
        break;
      case '1': // unposted
        # code...
        break;
    }

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $result = $this->default_query($config);
    $reportdata =  $this->DEFAULT_SALES_JOURNAL_SUMMARIZED($result, $config);
    return $reportdata;
  }

  private function GENERATE_DEFAULT_HEADER($params)
  {
    $str = '';
    $center = $params['params']['dataparams']['center'];
    $startdate = $params['params']['dataparams']['dateid'];
    $enddate = $params['params']['dataparams']['due'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', 'Century Gothic', '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT SUMMARY', null, null, '', '1px solid ', '', 'l', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    if ($center != '') {
      $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Center: ' . 'ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  } //end fn

  private function DEFAULT_SALES_JOURNAL_SUMMARIZED($data, $params)
  {
    $str = '';
    $count = 60;
    $page = 59;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    // $totalardb = 0;
    // $totalrcr = 0;
    $totaldb = 0;
    $totalcr = 0;
    // $docno = "";
    // $date = "";
    // $cname = "";
    // $db = 0;
    // $cr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, 2);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, 2);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $totaldb += $value->debit;
      $totalcr += $value->credit;

      $str .= $this->reporter->col($value->acno, null, null, '', '1px solid ', '', 'l', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', '1px solid ', '', 'l', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->GENERATE_DEFAULT_HEADER($params);
        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', 'Century Gothic', 'B', '10', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', 'Century Gothic', 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'BT', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), null, null, '', '1px solid ', 'BT', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), null, null, '', '1px solid ', 'BT', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

}//end class