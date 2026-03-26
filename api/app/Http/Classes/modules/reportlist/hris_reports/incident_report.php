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

class incident_report
{
  public $modulename = 'Incident Report';
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
    $fields = ['radioprint', 'dclientname', 'dcentername'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Persons Involved');

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
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as client,
    '' as center,
    '' as centername,
    '' as clientname,
    '' as dclientname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
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
    $center     = $config['params']['dataparams']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";

    if ($client != "") {
      $filter .= " and demp.client = '$client'";
    }
    if ($center != '') {
      $filter .= " and h.center='$center'";
    }

    $query = "select head.trno,head.docno,date(head.dateid) as dateid,demp.clientname as dempname,  
    djt.jobtitle as djobtitle,head.idescription,date(head.idate) as idate,time(head.idate) as itime,
    head.iplace,head.idetails,head.icomments
    from incidenthead as head
    left join incidentdtail as detail on detail.trno = head.trno        
    left join jobthead as djt on djt.line = detail.jobid
    left join client as demp on demp.clientid = detail.empid
    left join hrisnum as h on h.trno = head.trno
    where head.dateid between '" . $start . "' and '" . $end . "' $filter  
    union all
    select head.trno,head.docno,date(head.dateid) as dateid,demp.clientname as dempname,
    djt.jobtitle as djobtitle,head.idescription,date(head.idate) as idate,time(head.idate) as itime,
    head.iplace,head.idetails,head.icomments
    from hincidenthead as head
    left join hincidentdtail as detail on detail.trno = head.trno       
    left join jobthead as djt on djt.line = detail.jobid
    left join client as demp on demp.clientid = detail.empid
    left join hrisnum as h on h.trno = head.trno
    where head.dateid between '" . $start . "' and '" . $end . "' $filter
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
    $str .= $this->reporter->col('INCIDENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DATE', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col("PERSONS INVOLVED", '120', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('JOB TITLE', '130', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('INCIDENT DESC.', '180', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('INCIDENT DATE', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('TIME', '100', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('PLACE', '100', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DETAILS', '165', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('COMMENTS', '165', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
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

      $str .= $this->reporter->col($data->docno, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dempname, '120', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->djobtitle, '130', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->idescription, '180', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->idate, '80', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itime, '100', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->iplace, '100', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->idetails, '165', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '', '', 0, 'max-width:150px;overflow-wrap: break-word;');
      $str .= $this->reporter->col($data->icomments, '165', null, false, $border, 'LBR', 'LT', $font, $font_size, '', '', '', '', 0, 'max-width:150px;overflow-wrap: break-word;');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();

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