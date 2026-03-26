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

class request_for_training_and_development
{
  public $modulename = 'Request for Training and Development';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1380px;max-width:1380px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1200'];

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
    $fields = ['radioprint', 'dclientname', 'traintype'];
    $col1 = $this->fieldClass->create($fields);

    if ($config['params']['companyid'] == 58) { //cdo
      data_set($col1, 'divrep.label', 'Company');
    }
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
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
          '' as client,
          '' as clientname,
          '' as dclientname,
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
    $client     = $config['params']['dataparams']['client'];
    $deptid     = $config['params']['dataparams']['type'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";
    $filter1   = "";

    if ($client != "") {
      $filter .= " and emp.client = '$client'";
    }
    if ($deptid != "") {
      $filter1 .= " and head.type = '$deptid'";
    }

    $query = "SELECT '' as client, dept.client as dept, head.trno, head.docno,
  head.empid, date(head.dateid) as dateid, head.empname,
  head.jobtitle, head.type, head.title,
  head.venue, date(head.date1) as date1, date(head.date2) as date2,
  head.purpose, head.budget,head.deptid,
  emp.client as empcode,
  emp.clientname as empname
  from traindev as head
  left join client as emp on emp.clientid=head.empid
  left join client as dept on dept.clientid=head.deptid
  where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
  union all
  SELECT '' as client, dept.client as dept, head.trno, head.docno,
  head.empid, date(head.dateid) as dateid, head.empname,
  head.jobtitle, head.type, head.title,
  head.venue, date(head.date1) as date1, date(head.date2) as date2,
  head.purpose, head.budget,head.deptid,
  emp.client as empcode,
  emp.clientname as empname
  from htraindev as head
  left join client as emp on emp.clientid=head.empid
  left join client as dept on dept.clientid=head.deptid
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
    $str .= $this->reporter->col('REQUEST FOR TRAINING AND DEVELOPMENT REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '130', null,  $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('DATE', '80', null,  $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('EMPLOYEE', '150', null,  $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('TRAINING TYPE', '180', null,  $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('TITLE', '150', null,  $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('VENUE', '100', null,  $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('DATE FROM', '80', null,  $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('DATE TO', '80', null,  $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('PURPOSE', '170', null,  $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('BUDGET', '80', null,  $bgcolors, $border, 'RTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
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
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      if ($chkemp != $data->client) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '130', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '170', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
      };

      $str .= $this->reporter->col($data->docno, '130', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, '150', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->type, '180', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->title, '150', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->venue, '100', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->date1, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->date2, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->purpose, '170', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->budget, 2), '80', null, false, $border, 'LBR', 'RT', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      $chkemp = $data->client;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '170', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class