<?php

namespace App\Http\Classes\modules\reportlist\sss_reports;

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

class sss_remittance_report
{
  public $modulename = 'SSS Remittance Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['month', 'year'];
    $col2 = $this->fieldClass->create($fields);

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
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    month(now()) as month,
    left(now(),4) as year,
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

    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $department     = $config['params']['dataparams']['deptname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);

    $filter   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    
    if ($department != '') {
        if ($deptid != 0) {
            $filter .= " and emp.deptid = $deptid";
        }}
    
    if ($divname != '') {
      if ($config['params']['companyid'] == 58) { //cdo
        $filter .= " and emp.contricompid = $divid";
      } else {
        $filter .= " and d.divname = '$divname'";
      }
    }

    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select e.clientname,e.client,emp.sss, sum(p.ee) as ee,sum(p.er) as er,sum(p.ec) as ec,sum(p.cr) as cr from
          (select empid,cr as ee,0 as er,0 as ec,0 as cr from paytrancurrent as pt left join paccount as pa on pa.line=pt.acnoid
          where pa.alias = 'YSE' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          union all
          select empid,0 as ee,cr as er,0 as ec,0 as cr from paytrancurrent as pt left join paccount as pa on pa.line=pt.acnoid
          where pa.alias = 'YSR' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          union all
          select empid,cr as ee,0 as er,0 as ec,0 as cr from paytranhistory as pt left join paccount as pa on pa.line=pt.acnoid
          where pa.alias = 'YSE' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          union all
          select empid,0 as ee,cr as er,0 as ec,0 as cr from paytranhistory as pt left join paccount as pa on pa.line=pt.acnoid
          where pa.alias = 'YSR' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          union all
          select empid,0 as ee,0 as er,cr as ec,0 as cr from paytrancurrent as pt left join paccount as pa on pa.line=pt.acnoid
          where pa.alias = 'YER' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          union all
          select empid,0 as ee,0 as er,cr as ec,0 as cr from paytranhistory as pt left join paccount as pa on pa.line=pt.acnoid
          where pa.alias = 'YER' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          union all
          SELECT vtran.empid,0 as ee,0 as er,0 as ec,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS cr FROM paytrancurrent as vtran LEFT JOIN paccount ON paccount.line=vtran.acnoid WHERE
          month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          AND paccount.istax =1
          group by vtran.empid
          union all
          SELECT vtran.empid,0 as ee,0 as er,0 as ec,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS cr FROM paytranhistory as vtran LEFT JOIN paccount ON paccount.line=vtran.acnoid WHERE
          month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
          AND paccount.istax=1
          group by vtran.empid) as p
          left join client as e on e.clientid = p.empid
          left join employee as emp on emp.empid=p.empid
          left join client as dept on dept.clientid = emp.deptid
          left join division as d on d.divid = emp.divid
          where e.client <>'' and emp.level in $emplvl $filter
          group by e.clientname,e.client,emp.sss";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SSS REMITTANCE REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    if ($client == '') {
      $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    if ($divid == 0) {
      $str .= $this->reporter->col('COMPANY : ALL COMPANY', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $monthNum = $month;
    $monthname = date("F", mktime(0, 0, 0, $monthNum, 10));
    $str .= $this->reporter->col('For the Month of ' . strtoupper($monthname) . ' and Year ' . strtoupper($year), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYER', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('S S S #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('G R O S S  P A Y', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('S S S - E E', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('S S S - E R', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('S S S - E C', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('T O T A L', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $dateid = date('Y-m-d');
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sss, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->cr, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->ee, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->er, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->ec, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $Tot = $Tot + $data->ee + $data->er + $data->ec;
      $str .= $this->reporter->col(number_format($Tot, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $Grandtot = $Grandtot + $Tot;
      $Tot = 0;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($Grandtot, 2), '200px', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
}
