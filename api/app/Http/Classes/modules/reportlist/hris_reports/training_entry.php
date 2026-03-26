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

class training_entry
{
  public $modulename = 'Training Entry';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1640px;max-width:1640px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1450'];

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
    $fields = ['radioprint', 'traintype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'traintype.lookupclass', 'lookuptrainingtype');
    data_set($col1, 'traintype.label', 'Training Type');

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
  '' as type,
  adddate(left(now(),10),-360) as start,
    left(now(),10) as `end`
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
    $deptid     = $config['params']['dataparams']['type'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";

    if ($deptid != "") {
      $filter .= " and head.ttype = '$deptid'";
    }

    $query = "select trno,docno,dateid,ttype,title,venue,tdate1,tdate2,speaker,amt,cost,attendees,remarks,notes
  from(SELECT head.trno,head.docno,date(head.dateid) as dateid,head.ttype,head.title,
  head.venue,date(head.tdate1) as tdate1,date(head.tdate2) as tdate2,head.speaker,head.amt,head.cost,head.attendees,
  head.remarks, head.remarks as notes
  from traininghead as head
  left join trainingdetail as detail on head.trno = detail.trno
  left join client as emp on emp.clientid = detail.empid
  where head.dateid between '" . $start . "' and '" . $end . "' $filter 
  union all
  SELECT head.trno,head.docno,date(head.dateid) as dateid,head.ttype,head.title,
  head.venue,date(head.tdate1) as tdate1,date(head.tdate2) as tdate2,head.speaker,head.amt,head.cost,head.attendees,
  head.remarks, head.remarks as notes
  from htraininghead as head
  left join htrainingdetail as detail on head.trno = detail.trno
  left join client as emp on emp.clientid = detail.empid
  where head.dateid between '" . $start . "' and '" . $end . "' $filter) as tb
  group by trno,docno,dateid,ttype,title,venue,tdate1,tdate2,speaker,amt,cost,attendees,remarks,notes
  order by docno";

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
    $str .= $this->reporter->col('TRAINING ENTRY REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    // $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '120', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DATE', '80', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('TRAINING TYPE', '150', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('TITLE', '150', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('VENUE', '110', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DATE FROM', '80', null,  $bgcolors, $border, 'TBL', 'L', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DATE TO', '80', null,  $bgcolors, $border, 'TBL', 'L', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('SPEAKER/FACILITATORS', '180', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('BUDGET PER EMPLOYEE', '100', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('TOTAL COST', '100', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('ATTENDEES', '150', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('REMARKS', '150', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
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

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '120', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->ttype, '150', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->title, '150', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->venue, '110', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tdate1, '80', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tdate2, '80', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->speaker, '180', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2) . '&nbsp&nbsp&nbsp', '100', null, false, $border, 'TLB', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2) . '&nbsp&nbsp&nbsp', '100', null, false, $border, 'TLB', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->attendees, '150', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->remarks, '150', null, false, $border, 'TLBR', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $dtrno = $data->trno;
      $qry = "SELECT ifnull(stock.trno,0) as trno,ifnull(stock.line,0) as line,
    stock.empname as empname, stock.notes as notes from trainingdetail as stock where stock.trno = " . $dtrno . " 
    union all
    SELECT ifnull(stock.trno,0) as trno,ifnull(stock.line,0) as line,
    stock.empname as empname, stock.notes as notes from htrainingdetail as stock where stock.trno= " . $dtrno . " ";

      $data1      = $this->coreFunctions->opentable($qry);
      $i = 0;
      foreach ($data1 as $key => $data2) {
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '100', null, false, $border, 'L', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', '', $font, $font_size, '', '', '');

        if ($i == 0) {
          $str .= $this->reporter->col('List of Attendees:', '150', null, false, $border, '', '', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $font_size, '', '', '');
        }
        $i = $i + 1;

        $str .= $this->reporter->col($data2->empname, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'R', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

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
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1450', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class