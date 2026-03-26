<?php

namespace App\Http\Classes\modules\reportlist\payroll_other_report;

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

class employee_13th_month_pay
{
  public $modulename = 'Employee 13th Month Pay';
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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep'];

    if ($companyid == 53) {
      unset($fields[1]);
      unset($fields[2]);
      unset($fields[3]);
    }
    $col1 = $this->fieldClass->create($fields);
    if ($companyid != 53) {
      data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
      data_set($col1, 'dclientname.label', 'Employee');
      data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
      data_set($col1, 'divrep.label', 'Company');
      data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptrep.label', 'Department');
    }

    $fields = ['start', 'end', 'radioreporttype'];
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
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '0' as reporttype,
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

    $reporttype     = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARY
        return $this->RT_SUMMARY_Layout($config);
        break;

      case '1': // DETAIL
        return $this->RT_DETAIL_Layout($config);
        break;
    }
  }
  public function RT_query($config)
  {
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($companyid) {
      case '53': //camera
        return $this->M13_month_camera_query($config);
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARY
            return $this->RT_SUMMARY_QRY($config);
            break;

          case '1': // DETAIL
            return $this->RT_DETAIL_QRY($config);
            break;
        }
        break;
    }
  }

  //QRY SUMMARY
  public function RT_SUMMARY_QRY($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $dividname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";
    $filter1   = "";
    $filter2   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptname != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }


    if ($dividname != "") {
      $filter2 .= " and emp.divid = $divid";
    }

    $emplvl = $this->othersClass->checksecuritylevel($config);


    $query = "select e.clientname,e.client, sum(p.amt) as amt from
  (
    SELECT vtran.empid,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS amt 
    FROM paytrancurrent as vtran 
    LEFT JOIN paccount ON paccount.line=vtran.acnoid 
    WHERE dateid between '" . $start . "' and '" . $end . "'
    AND paccount.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','SIL','ML','PL','BL','ADJUSTMENT')
    group by vtran.empid
    union all
    SELECT vtran.empid,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS amt 
    FROM paytranhistory as vtran 
    LEFT JOIN paccount ON paccount.line=vtran.acnoid 
    WHERE dateid between '" . $start . "' and '" . $end . "'
    AND paccount.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','SIL','ML','PL','BL','ADJUSTMENT')
    group by vtran.empid
  ) as p
  left join client as e on e.clientid = p.empid
  left join employee as emp on emp.empid=p.empid
  where e.client <>'' and emp.level in $emplvl $filter $filter1 $filter2
  group by e.clientname,e.client";
    return $this->coreFunctions->opentable($query);
  }

  public function RT_DETAIL_QRY($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $dividname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";
    $filter1   = "";
    $filter2   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptname != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }


    if ($dividname != "") {
      $filter2 .= " and emp.divid = $divid";
    }

    $emplvl = $this->othersClass->checksecuritylevel($config);


    $query = "select e.clientname,e.client,p.start,p.end,concat(left(e.client,3),right(e.client,4),'   ',e.clientname) as empname, sum(p.amt) as amt from
  (
    SELECT vtran.empid,date(b.startdate) as start,date(b.enddate) as end,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS amt 
    FROM paytrancurrent as vtran 
    LEFT JOIN paccount ON paccount.line=vtran.acnoid 
    left join batch as b on b.line=vtran.batchid
    WHERE vtran.dateid between '" . $start . "' and '" . $end . "'
    AND paccount.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','SIL','ML','PL','BL','ADJUSTMENT')
    group by vtran.empid,b.startdate,b.enddate
    union all
    SELECT vtran.empid,date(b.startdate) as start,date(b.enddate) as end,IFNULL(SUM(vtran.db), 0) - IFNULL(SUM(vtran.cr), 0) AS amt 
    FROM paytranhistory as vtran 
    LEFT JOIN paccount ON paccount.line=vtran.acnoid 
    left join batch as b on b.line=vtran.batchid
    WHERE vtran.dateid between '" . $start . "' and '" . $end . "'
    AND paccount.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','SIL','ML','PL','BL','ADJUSTMENT')
    group by vtran.empid,b.startdate,b.enddate
  ) as p
  left join client as e on e.clientid = p.empid
  left join employee as emp on emp.empid=p.empid
  where e.client <>'' and emp.level in $emplvl $filter $filter1 $filter2
  group by e.clientname,e.client,p.start,p.end";
    return $this->coreFunctions->opentable($query);
  }
  public function M13_month_camera_query($config)
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $user   = $config['params']['user'];
    $empid = $this->coreFunctions->datareader("select clientid as value from client where email =? ", [$user]);
    $query = "
   select empid,start,end,clientname,clientname as empname,sum(amt) as amt,client from (
    select vtran.empid,date(b.startdate) as start,date(b.enddate) as end,sum(vtran.db - vtran.cr) as amt,cl.clientname,cl.client
	 from paytrancurrent as vtran
    left join batch as b on b.line=vtran.batchid
    left join paccount on paccount.line=vtran.acnoid 
    left join employee as emp on emp.empid = vtran.empid
    left join client as cl on cl.clientid = vtran.empid
    where vtran.dateid between '" . $start . "' and '" . $end . "' and emp.empid = '$empid' 
    and case when emp.is13th = 1 then paccount.code in ('PT108','PT109','PT102','PT103','PT104','PT105','PT89','PT90','PT91','PT86','PT87','PT88','PT85','PT9','PT8','PT7','PT58','PT57','PT5','PT6','PT31','PT114','PT115','PT116','PT4','PT121')
    else paccount.code in ('PT108','PT109','PT102','PT103','PT104','PT105','PT89','PT90','PT91','PT86','PT87','PT88','PT85','PT9','PT8','PT7','PT58','PT57','PT5','PT6','PT114','PT115','PT116','PT121') end 
    group by cl.clientname,cl.client,vtran.empid,b.startdate,b.enddate
    union all 
    select vtran.empid,date(b.startdate) as bstart,date(b.enddate) as bend,sum(vtran.db - vtran.cr) as amt,cl.clientname,cl.client
	 from paytranhistory as vtran
    left join batch as b on b.line=vtran.batchid
    left join paccount on paccount.line=vtran.acnoid
    left join employee as emp on emp.empid = vtran.empid
    left join client as cl on cl.clientid = vtran.empid
    where vtran.dateid between '" . $start . "' and '" . $end . "' and emp.empid = '$empid' 
    and case when emp.is13th = 1 then paccount.code in ('PT108','PT109','PT102','PT103','PT104','PT105','PT89','PT90','PT91','PT86','PT87','PT88','PT85','PT9','PT8','PT7','PT58','PT57','PT5','PT6','PT31','PT114','PT115','PT116','PT4','PT121')
    else paccount.code in ('PT108','PT109','PT102','PT103','PT104','PT105','PT89','PT90','PT91','PT86','PT87','PT88','PT85','PT9','PT8','PT7','PT58','PT57','PT5','PT6','PT114','PT115','PT116','PT121') end 
    group by cl.clientname,cl.client,vtran.empid,b.startdate,b.enddate 
   ) as m 
    group by clientname,client,empid,start,end";

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
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype     = $config['params']['dataparams']['reporttype'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    if ($reporttype == 0) {
      $type = '(SUMMARY)';
    } else {
      $type = '(DETAILED)';
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('13TH MONTH PAY REPORT' . $type, null, null, false, $border, '', '', $font, '18', 'B', '', '');
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


    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endtable();
    switch ($reporttype) {
      case '0': // SUMAMRY
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('T O T A L &nbsp P A Y', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('1 3 T H &nbsp M O N T H', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
      case '1': // DETAIL
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('13TH MONTH', '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
    }

    return $str;
  }

  private function display_camera_Header($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $divname     = $config['params']['dataparams']['divname'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype     = $config['params']['dataparams']['reporttype'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    if ($reporttype == 0) {
      $type = '(SUMMARY)';
    } else {
      $type = '(DETAILED)';
    }

    $query = "select divi.divname,dept.clientname as deptname,sect.sectname from client 
    left join employee as emp on emp.empid = client.clientid
    left join client as dept on dept.clientid = emp.deptid 
    left join division as divi on divi.divid = emp.divid 
    left join section as sect on sect.sectid = emp.sectid
    where client.email = '$username'";
    $data = $this->coreFunctions->opentable($query);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('13TH MONTH PAY REPORT' . $type, null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMPANY : ' . strtoupper($data[0]->divname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($data[0]->deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();
    switch ($reporttype) {
      case '0': // SUMAMRY
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('T O T A L &nbsp P A Y', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('1 3 T H &nbsp M O N T H', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
      case '1': // DETAIL
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('13TH MONTH', '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
    }

    return $str;
  }

  public function RT_SUMMARY_Layout($config)
  {

    $result = $this->RT_query($config);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $companyid = $config['params']['companyid'];
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

    if ($companyid == 53) { //camera
      $str .= $this->display_camera_Header($config);
    } else {
      $str .= $this->displayHeader($config);
    }


    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->amt == 0 ? '-' : number_format($data->amt, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $bonus = $data->amt / 12;
      $str .= $this->reporter->col($bonus == 0 ? '-' : number_format($bonus, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      $Grandtot = $Grandtot + $bonus;

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
    $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($Grandtot, 2), '200px', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }

  public function RT_DETAIL_Layout($config)
  {
    $result = $this->RT_query($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $companyid = $config['params']['companyid'];
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 45;
    $page = 45;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = $GrandBonus = 0;


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    if ($companyid == 53) { //camera
      $str .= $this->display_camera_Header($config);
    } else {
      $str .= $this->displayHeader($config);
    }

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->empname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->start, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->end, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->amt == 0 ? '-' : number_format($data->amt, 2), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $bonus = $data->amt / 12;
      // $str .= $this->reporter->col('','150',null,false,$border,'','',$font,$font_size,'','','');
      $str .= $this->reporter->col($bonus == 0 ? '-' : number_format($bonus, 2), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      $Grandtot = $Grandtot + $data->amt;
      $GrandBonus = $GrandBonus + $bonus;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }



    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($Grandtot, 2), '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($GrandBonus, 2), '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class