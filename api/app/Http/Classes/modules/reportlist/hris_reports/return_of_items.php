<?php

namespace App\Http\Classes\modules\reportlist\hris_reports;

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

class return_of_items
{
  public $modulename = 'Return of Items';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'dclientname', 'deptrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['start', 'end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
  'default' as print,
  '' as client,
  '' as clientname,
  '' as dclientname,
  '' as deptid,
  '' as deptname,
  adddate(left(now(),10),-360) as start,
    left(now(),10) as `end`,
  '' as deptrep
    ");
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

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";
    $filter1   = "";

    if ($client != "") {
      $filter .= " and em.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and d.clientid = $deptid";
    }

    $query = "SELECT head.trno,'' as client,head.docno,head.empid,em.client as empcode,em.clientname as empname,
  head.deptid,d.client as dept,date(head.dateid) as dateid,head.jobtitle,head.rem,detail.itemname,
  ifnull(detail.amt,0) as amt,detail.rem,d.clientname as deptname
  from returnitemhead as head
  left join client as em on em.clientid=head.empid
  left join client as d on d.clientid=head.deptid
  left join returnitemdetail as detail on detail.trno=head.trno
  where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
  union all
  SELECT head.trno,'' as client,head.docno,head.empid,em.client as empcode,em.clientname as empname,
  head.deptid,d.client as dept,date(head.dateid) as dateid,head.jobtitle,head.rem,detail.itemname,
  ifnull(detail.amt,0) as amt,detail.rem,d.clientname as deptname
  from hreturnitemhead as head
  left join client as em on em.clientid=head.empid
  left join client as d on d.clientid=head.deptid
  left join hreturnitemdetail as detail on detail.trno=head.trno
  where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
  order by docno ";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '10';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RETURN OF ITEMS REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '120', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DATE', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('EMPLOYEE', '200', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DEPARTMENT', '180', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('ITEMS', '160', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('AMOUNT', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('REMARKS', '180', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';
    $count = 55;
    $page = 55;
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);
    $chkemp = "";
    $olddocno = "";
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $str .= $this->reporter->col($data->docno, '120', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, '200', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->deptname, '180', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '160', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2) . '&nbsp&nbsp&nbsp', '80', null, false, $border, 'LB', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->rem, '180', null, false, $border, 'LBR', 'LT', $font, $font_size, '', '', '');


      $str .= $this->reporter->endrow();


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class