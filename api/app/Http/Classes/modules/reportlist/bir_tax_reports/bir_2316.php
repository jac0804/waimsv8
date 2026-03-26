<?php

namespace App\Http\Classes\modules\reportlist\bir_tax_reports;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Illuminate\Support\Facades\URL;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class bir_2316
{
  public $modulename = 'BIR 2316';
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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep', 'sectrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
    data_set($col1, 'sectrep.label', 'Section');


    $fields = ['year'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'year.required', true);

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
    '' as deptid,
    '' as deptname,
    '' as sectid,
    '' as sectname,
    '' as divrep,
    '' as deptrep,
    '' as sectrep,
    left(now(),4) as year
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
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $sectid     = $config['params']['dataparams']['sectid'];

    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and cl.client = '$client'";
    }
    if ($divid != "") {
      $filter1 .= " and e.divid = $divid";
    }
    if ($deptid != "") {
      $filter2 .= " and e.deptid = $deptid";
    }
    if ($sectid != "") {
      $filter3 .= " and e.sectid = $sectid";
    }
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "
    select cl.client as empcode, e.emplast, e.empfirst, e.empmiddle, e.address, e.city, e.zipcode, e.tin, date(e.bday) as bday,e.telno,
    division.divname, division.address as daddress, division.zipcode as dzipcode,division.tin as dtin,e.empid from employee as e
    left join client as cl on cl.clientid = e.empid
    left join division on division.divid=e.divid
    where isactive=1 and e.level in $emplvl $filter $filter1 $filter2 $filter3 
  ";


    return $this->coreFunctions->opentable($query);
  }

  private function getbasicsalary($empid, $year)
  {
    return $this->coreFunctions->datareader("select ifnull(sum(a.db-a.cr),0) as value from (select p.db,p.cr,p.empid from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid where pa.istax=1 and year(dateid)='" . $year . "'  union all select p.db,p.cr,p.empid from paytranhistory as p left join paccount as pa on pa.line=p.acnoid where  pa.istax=1 and year(dateid)='" . $year . "' ) as a where a.empid=" . $empid . "");
  }

  private function getbonus($empid, $year)
  {
    return $this->coreFunctions->datareader("select ifnull(sum(a.db-a.cr)/12,0) as value from (select p.db,p.cr,p.empid from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid where (pa.alias in ('BSA','ABSENT','LATE','UNDERTIME') or pa.codename like '%leave%') and year(dateid)='" . $year . "'  union all select p.db,p.cr,p.empid from paytranhistory as p left join paccount as pa on pa.line=p.acnoid where (pa.alias in ('BSA','ABSENT','LATE','UNDERTIME') or pa.codename like '%leave%') and year(dateid)='" . $year . "' ) as a where a.empid=" . $empid . "");
  }

  private function getcontribution($empid, $year)
  {
    return $this->coreFunctions->datareader("select ifnull(sum(a.cr-a.db),0) as value from (select p.db,p.cr,p.empid from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid where pa.alias in ('YSE','YME','YPE') and year(dateid)='" . $year . "'  union all select p.db,p.cr,p.empid from paytranhistory as p left join paccount as pa on pa.line=p.acnoid where pa.alias in ('YSE','YME','YPE') and year(dateid)='" . $year . "' ) as a where a.empid=" . $empid . "");
  }

  private function getwht($empid, $year)
  {
    return $this->coreFunctions->datareader("select ifnull(sum(a.cr-a.db),0) as value from (select p.db,p.cr,p.empid from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid where pa.alias in ('YWT') and year(dateid)='" . $year . "'  union all select p.db,p.cr,p.empid from paytranhistory as p left join paccount as pa on pa.line=p.acnoid where pa.alias in ('YWT') and year(dateid)='" . $year . "' ) as a where a.empid=" . $empid . "");
  }
  private function gettaxdue($gross)
  {
    $taxdue = 0.0;
    $range = 0.0;
    $percentage = 0.0;
    $amt = 0.0;
    $qry = "";

    $qry = " select range1,amt,percentage from annualtax where " . $gross . " between range1 and range2";
    $pap = $this->coreFunctions->opentable($qry);

    if (!empty($data)) {
      foreach ($pap as $key => $data) {
        $range = $data->range1;
        $amt = $data->amt;
        $percentage = $data->percentage;
      }
      $taxdue = $gross - $range;
      $taxdue = $taxdue * $percentage;
      $taxdue = $taxdue + $amt;
    } else {
      $taxdue = 0.0;
    }

    return $taxdue;
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

    $birlogo = URL::to('/images/reports/birlogo.png');
    $birblogo = URL::to('/images/reports/birbarcode.png');
    $year = intval($config['params']['dataparams']['year']);
    $count = 55;
    $page = 55;
    $layoutsize = '800';
    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);

    foreach ($result as $key => $data) {


      //Basic Salary    
      $basicsalary = 0.0;
      $basicsalary = $this->getbasicsalary($data->empid, $year);

      //Bonus
      $bonusntax = 0.0;
      $bonusntax = $this->getbonus($data->empid, $year);

      //Contribution
      $contribution = 0.0;
      $contribution = $this->getcontribution($data->empid, $year);

      //Withholding Tax
      $wht = 0.0;
      $wht = $this->getwht($data->empid, $year);

      //Total #36
      $total36 = 0.0;
      $total36 = $basicsalary + $bonusntax - $contribution;

      //Total #20
      $total20 = 0.0;
      $total20 = $total36;

      //Total #50
      $total50 = 0.0;

      //Gross Income
      $grossincome = $total36 + $total50;

      //Total #21
      $total21 = 0.0;
      $total21 = $grossincome - $total20;

      //Total #22
      $total22 = 0.0;

      //Total #23
      $total23 = 0.0;
      $total23 = $total21 + $total22;

      //Total #26
      $total26 = 0.0;
      $total26 = $wht;

      //Taxdue
      $taxdue = 0.0;
      $taxdue = $this->gettaxdue($grossincome);


      //To Number Format
      $basicsalary = number_format($basicsalary, 2);
      $bonusntax = number_format($bonusntax, 2);
      $contribution = number_format($contribution, 2);
      $grossincome = number_format($grossincome, 2);
      $total36 = number_format($total36, 2);
      $total20 = number_format($total20, 2);
      $wht = number_format($wht, 2);
      $total21 = number_format($total21, 2);
      $total23 = number_format($total23, 2);
      $total26 = number_format($total26, 2);
      $taxdue = number_format($taxdue, 2);

      //1st row
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, '2px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="60px" height ="60px">', '10', null, false, '2px solid ', '', 'R', $font, '15', 'B', '', '');
      $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue', '60', null, false, '2px solid ', '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '90', null, false, '2px solid ', '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col(
        "<small>BIR Form No.</small>
      <br>
      <p style='font-size:32px; font-weight: bold; margin: 0px;'>2316</p>
      <small>January 2018 (ENCS)</small>
      ",
        '135',
        null,
        false,
        '2px solid ',
        'LRTB',
        'L',
        $font,
        '10',
        '',
        '',
        ''
      );
      $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
      $str .= $this->reporter->col('Certificate of Compensation <br> Payment/Tax Withheld <br>
      <small>For Compensation Payment With or Without Tax Withheld</small>', '450', null, false, '2px solid ', 'RTB', 'C', $font, '16', 'B', '', '');

      $str .= $this->reporter->col('<img src ="' . $birblogo . '" alt="BIR" width="200px" height ="50px">', '130', null, false, '2px solid ', 'TB', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '5', null, false, '2px solid ', 'RTB', 'L', $font, '10', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"', '100', null, false, '2px solid ', 'LRTB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      //2nd row blank
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('1.', '50', null, false, '2px solid ', 'BLT', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col('For the Year <br> (YYYY)', '100', null, false, '2px solid ', 'TB', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='text-align:center;' value='" . $year . "'>", '100', null, false, '2px solid', 'TB', 'C', $font, '13', '', '', '');
      $str .= $this->reporter->col('', '124', null, false, '2px solid ', 'BRT', 'C', $font, '10', '', '', '');

      $str .= $this->reporter->col('2.', '50', null, false, '2px solid ', 'BT', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col('For the Period <br> &nbsp&nbsp&nbsp From (MM/DD)', '100', null, false, '2px solid ', 'BT', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col("<br> <input readonly type='text' style='width: 40px ;' value='0'><input readonly type='text' style='width: 40px;' value='1'>", '100', null, false, '2px solid ', 'BT', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col("<br> <input readonly type='text' style='width: 40px;' value='1'><input readonly type='text' style='width: 40px;' value='2'>", '150', null, false, '2px solid ', 'BRT', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Part I - Employee Information', '400', null, false, '2px solid ', 'LBR', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('Part IV-B Details of Compensation Income and Tax Withheld from Present Employer', '400', null, false, '2px solid ', 'RB', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('3. TIN', '150', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $tin = $data->tin;
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $tin . "' >", '250', null, false, '2px solid ', 'B', 'L', $font, '9', '', '', '3px');
      $str .= $this->reporter->col('A. NON-TAXABLE/EXEMPT COMPENSATION INCOME', '400', null, false, '2px solid ', 'LR', 'L', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $fullname = $data->emplast . ", " . $data->empfirst . " " . $data->empmiddle;
      $str .= $this->reporter->col("4. Employee's Name (Last Name, First Name, Middle Name) <br> <input readonly type='text' style='width: 100%;' value='" . $fullname . "'>", '300', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("5. RDO Code <br> <input readonly type='text' style='width: 100%;'>", '100', null, false, '2px solid ', 'B', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('27. Basic Salary(including the exempt P250,000 & I Statutory Minimum Wage of the MWE', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("Amount <br> <input readonly type='text' style='width: 100%; text-align: right;' value='" . $basicsalary . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $address = $data->address;
      $str .= $this->reporter->col("6. Registered Address <br> <input readonly type='text' style='width: 100%;' value='" . $address . "'>", '300', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $zipcode = $data->zipcode;
      $str .= $this->reporter->col("6A. Zip Code <br> <input readonly type='text' style='width: 100%;' value='" . $zipcode . "'>", '100', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('28. Holiday Pay (MWE)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;''>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("6B. Local Home Address <br> <input readonly type='text' style='width: 100%;'>", '300', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("6C. Zip Code <br> <input readonly type='text' style='width: 100%;'>", '100', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('29. Overtime Pay (MWE)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("6D. Foreign Address <br> <input readonly type='text' style='width: 100%;'>", '300', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("6E. Zip Code <br> <input readonly type='text' style='width: 100%;'>", '100', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('30. Night Shift Differential (MWE)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $bday = $data->bday;
      $str .= $this->reporter->col("7. Date of Birth (MM/DD/YYY) <br> <input readonly type='text' style='width: 100%;' value='" . $bday . "'>", '200', null, false, '2px solid ', 'LRT', 'L', $font, '9', '', '', '');
      $telno = $data->telno;
      $str .= $this->reporter->col("8. Telephone Number <br> <input readonly type='text' style='width: 100%;' value='" . $telno . "'>", '200', null, false, '2px solid ', 'T', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('31. Hazard Pay (MWE)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("9. Statutory Minimum Wage rate per day'", '250', null, false, '2px solid ', 'LT', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '150', null, false, '2px solid ', 'T', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('32. 13 Month Pay and Other Benefits (maximum of P90,000)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value=" . $bonusntax . ">", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("10. Statutory Minimum Wage rate per Month'", '250', null, false, '2px solid ', 'LT', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '150', null, false, '2px solid ', 'T', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('33. De minimis Benefits', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("11. <input readonly type='text' style='width: 25px;'>", '50', null, false, '2px solid ', 'LBT', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("Minimum Wage Earner whose compensation is exempt from withholding tax and not subject to income tax", '350', null, false, '2px solid ', 'TB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('34. SSS, GSIS, PHIC & Pag-ibig Contributions and Union Dues (Employee Share Only)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $contribution . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Part II - Employer Information (Present)', '400', null, false, '2px solid ', 'LBR', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('35. Salaries & Other Forms of Compensation', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('12. Taxpayer', '150', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $dtin = $data->dtin;
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $dtin . "'>", '250', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
      $str .= $this->reporter->col('36. Total Non-Taxable/Exempt Compensation Income <br> (Sum of Items 27 to 35)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $total36 . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $divname = $data->divname;
      $str .= $this->reporter->col("13. Employer's Name <br> <input readonly type='text' style='width: 100%;' value='" . $divname . "'>", '400', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('B. TAXABLE COMPENSATION INCOME REGULAR', '400', null, false, '2px solid ', 'LR', 'L', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $daddress = $data->daddress;
      $str .= $this->reporter->col("14. Registered Address <br> <input readonly type='text' style='width: 100%;' value='" . $daddress . "'>", '300', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $dzipcode = $data->dzipcode;
      $str .= $this->reporter->col("14A. Zip Code <br> <input readonly type='text' style='width: 100%;' value='" . $dzipcode . "'>", '100', null, false, '2px solid ', 'B', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('37. Basic Salary', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("15. Registered Address", '150', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 20px;' value='//'> Main Employer", '125', null, false, '2px solid ', 'B', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 20px;'> Secondary Employer", '125', null, false, '2px solid ', 'B', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('38. Representation', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Part III - Employee Information (Previous)', '400', null, false, '2px solid ', 'LBR', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('39. Transportation', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('16. TIN', '150', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;'>", '250', null, false, '2px solid ', 'B', 'L', $font, '9', '', '', '3px');
      $str .= $this->reporter->col('40. Cost of Living Allowance (COLA)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("17. Employer's Name <br> <input readonly type='text' style='width: 100%;'>", '400', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col('41. Fixed Housing Allowance', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("18. Registered Address <br> <input readonly type='text' style='width: 100%;'>", '300', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("18A. Zip Code <br> <input readonly type='text' style='width: 100%;'>", '100', null, false, '2px solid ', 'B', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("42. Others (Specify) <br>
      &nbsp&nbsp&nbsp 41A. <input readonly type='text' style='width: 150px;'> <br>
      &nbsp&nbsp&nbsp 42B. <input readonly type='text' style='width: 150px; margin-left:1px;'>", '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<br><input readonly type='text' style='width: 100%; text-align: right;'> <br> <input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Part IV - Summary', '400', null, false, '2px solid ', 'LBR', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp SUPPLEMENTARY', '400', null, false, '2px solid ', 'R', 'L', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('19. Gross Compensation Income from Present Employer (Sum of Items 36 and 50)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $grossincome . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('43. Commission', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('20. Less: Total Non-Taxable/Exempt Compensation Income from Present Employer (From Item 36)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $total20 . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('44. Profit Sharing', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('21. Taxable Compensation Income from Present Employer (Item 19 Less Item 20) (From Item 50)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $total21 . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("45. Fees Including Director's Fees", '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('22. Add: Taxable Compensation Income from Previous Employee, if applicable', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("46. Taxable 13th Month Pay Benefits", '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('23. Gross Taxable Compensation Income (Sum of Items 21 and 22)', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $total23 . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("47. Hazard Pay", '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('24. Tax Due', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $taxdue . "'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("48. Overtime Pay", '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('25. Amount of Taxes Withheld <br>
      &nbsp&nbsp&nbsp 25A. Present Employer <br>
      &nbsp&nbsp&nbsp 25B. Previous Employer', '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<br><input readonly type='text' style='width: 100%; text-align: right;' value='" . $wht . "'> <br> <input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("49. Others (Specify) <br>
      &nbsp&nbsp&nbsp 49A. <input readonly type='text' style='width: 150px;'> <br>
      &nbsp&nbsp&nbsp 49B. <input readonly type='text' style='width: 150px; margin-left:1px;'>", '200', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<br><input readonly type='text' style='width: 100%; text-align: right;'> <br> <input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'R', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('26. Total Amount of Taxex Withheld as adjusted (Sum of Items 25A and 25B)', '200', null, false, '2px solid ', 'LB', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;' value='" . $total26 . "'>", '200', null, false, '2px solid ', 'RB', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("50. Total Taxable Compensation Income (Sum of Items 37 and 49B)", '200', null, false, '2px solid ', 'BL', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align: right;'>", '200', null, false, '2px solid ', 'RB', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp I/We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by as, and to the best of my/our knowledge and belief, is true and correct pursant to the provisions of the National Internal Revenue Code, as amended and the regulations issued under authority therof. Further, I/We give my/our consent to the processing of my/our information as contemplated under the *Data Privacy Act of 2019(R.A No. 10173) for legitimate and lawful purposes.", '800', null, false, '2px solid ', 'LBR', 'L', $font, '9', '', '', '10px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp 51. ', '50', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<br> <input readonly type='text' style='width: 300px; border-top: none; border-left: none; border-right: none;'> <br> Present Employer/Authorized Agent Signature Over Printed Name", '350', null, false, '2px solid ', '', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("Date Signed", '100', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 30px;'><input readonly type='text' style='width: 30px;'><input readonly type='text' style='width: 50px;'>", '300', null, false, '2px solid ', 'R', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp', '400', null, false, '2px solid ', 'LR', 'L', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'L', 'L', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('CONFORME: ', '380', null, false, '2px solid ', 'R', 'L', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp 52. ', '50', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<br> <input readonly type='text' style='width: 300px; border-top: none; border-left: none; border-right: none;'> <br> Employee Signature Over Printed Name", '350', null, false, '2px solid ', '', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("Date Signed", '100', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 30px;'><input readonly type='text' style='width: 30px;'><input readonly type='text' style='width: 50px;'>", '300', null, false, '2px solid ', 'R', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp CTC/Valid ID. N of &nbsp&nbsp&nbsp&nbsp Employee', '100', null, false, '2px solid ', 'L', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;'>", '100', null, false, '2px solid ', '', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Place of Issue', '100', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;'>", '100', null, false, '2px solid ', '', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->col("Date Signed", '100', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col("<input readonly type='text' style='width: 30px;'><input readonly type='text' style='width: 30px;'><input readonly type='text' style='width: 50px;'>", '300', null, false, '2px solid ', 'R', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('To be accomplished under substituted filling', '800', null, false, '2px solid ', 'LBTR', 'C', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("<div style='text-align: left; margin-top: -50px;'>&nbsp&nbsp&nbsp&nbsp&nbsp I declare, under the penalties of parjury, that the information herin stated are reported under BIR Form No. 1604C which has been filed with the Bureau of Internal Revenue.</div> <br> <br> <br>
      53. <input readonly type='text' style='width: 300px; border-top: none; border-left: none; border-right: none;'> <br> Present Employer/Authorized Agent Signature Over Printed Name <br>
      (Head of Accounting/Human Resource or Authorized Representative)", '200', null, false, '2px solid ', 'LB', 'C', $font, '9', '', '', '10px;');
      $str .= $this->reporter->col("<div style='text-align:left;''>&nbsp&nbsp&nbsp&nbsp&nbsp I declare, under the penalties of perjury that I am qualified under substituted filling of Income Tax Return(BIR Form No. 1700), since I received purely compensation income from only one employer in the Philippines for the calendar year, that taxes have been correctly withheld by my employer (tax due equals tax withheld); that the BIR Form No. 1604-C filed by my employer to the BIR shall constitute as my income tax return; and that BIR Form No. 2316 shall serve the same purpose as BIR Form No. 1700 has been filed pursuant to the provisions of Revenue Regulations (RR) No. 3-2002, as amended.</div> <br> <br> <br>
      54. <input readonly type='text' style='width: 300px; border-top: none; border-left: none; border-right: none;'> <br> Employee Signature Over Printed Name", '200', null, false, '2px solid ', 'BR', 'C', $font, '9', '', '', '10px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('*NOTE: The BIR Data Privacy is in the BIR website (www.bir.gov.ph)', '800', null, false, '2px solid ', '', 'L', $font, '9', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->page_break();
      $str .= $this->reporter->endtable();
    } //end foreach


    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class