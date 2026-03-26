<?php

namespace App\Http\Classes\modules\reportlist\philhealth_reports;

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

class philhealth_remittance_report
{
  public $modulename = 'Philhealth Remittance Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '700'];

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
    '' as clientid,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as divid,
    '' as divname,
    '' as division,
    '' as divrep,
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
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $client = $config['params']['dataparams']['client'];
    $divid = $config['params']['dataparams']['divid'];
    $divname = $config['params']['dataparams']['divname'];
    $deptname = $config['params']['dataparams']['deptname'];
    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);

    $filter   = "";

    if ($client != "") {
      $filter .= " and e.client= '$client'";
    }
    if ($deptname != '') {
      $filter .= " and dept.deptname= '$deptname'";
    }
    if ($divname != '') {
      if ($config['params']['companyid'] == 58) { //cdo
        $filter .= " and emp.contricompid=" . $divid;
      } else {
        $filter .= " and d.divname=  '$divname' ";
      }
    }


    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select e.clientname,e.client,emp.phic, sum(p.ee) as ee,sum(p.er) as er,sum(p.ec) as ec,sum(p.cr) as cr from
            (select empid,cr as ee,0 as er,0 as ec,0 as cr from paytrancurrent as pt left join paccount as pa on pa.line=pt.acnoid
            where pa.alias ='YME' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
            
            UNION ALL

            select empid,0 as ee,cr as er,0 as ec,0 as cr from paytrancurrent as pt left join paccount as pa on pa.line=pt.acnoid
            where pa.alias ='YMR' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
            
            UNION ALL

            select empid,cr as ee,0 as er,0 as ec,0 as cr from paytranhistory as pt left join paccount as pa on pa.line=pt.acnoid
            where pa.alias ='YME' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
            
            UNION ALL

            select empid,0 as ee,cr as er,0 as ec,0 as cr from paytranhistory as pt left join paccount as pa on pa.line=pt.acnoid
            where pa.alias ='YMR' and month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
            
            UNION ALL

            SELECT vtran.empid,0 as ee,0 as er,0 as ec,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS cr FROM paytrancurrent as vtran LEFT JOIN paccount ON paccount.line=vtran.acnoid WHERE
            month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
            AND paccount.istax =1
            group by vtran.empid
            
            UNION ALL

            SELECT vtran.empid,0 as ee,0 as er,0 as ec,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS cr FROM paytranhistory as vtran LEFT JOIN paccount ON paccount.line=vtran.acnoid WHERE
            month(dateid)= '" . $month . "' and year(dateid)= '" . $year . "'
            AND paccount.istax=1
            group by vtran.empid) as p
            left join client as e on e.clientid = p.empid
            left join employee as emp on emp.empid=p.empid
            left join division as d on d.divid = emp.divid
            left join client as deptcl on deptcl.clientid = emp.deptid
            left join department as dept on dept.deptname = deptcl.clientname
            where e.client <>'' and emp.level in $emplvl $filter 
            group by e.clientname,e.client,emp.phic";
            // var_dump($query);

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

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
    $layoutsize = 1000;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PHILHEALTH REMITTANCE REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->col('For the Month of ' . strtoupper($monthname) . ' and Year ' . strtoupper($year), NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE' . '&nbsp', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYER' . '&nbsp', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '120', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '310', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('P H I C #', '130', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('G R O S S  P A Y', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('P H I C - E E', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('P H I C  - E R', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('T O T A L', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $count = 45; 
    $page = 45;

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    $layoutsize = 1000;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '120', null, false, $border, '', 'T', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '310', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->phic, '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->cr, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->ee, 2), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->er, 2), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $Tot = $Tot + $data->ee + $data->er;
      $str .= $this->reporter->col(number_format($Tot, 2), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');


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
    $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($Grandtot, 2), '200px', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class