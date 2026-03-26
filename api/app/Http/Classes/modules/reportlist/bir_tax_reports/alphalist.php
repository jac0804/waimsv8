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

class alphalist
{
  public $modulename = 'ALPHALIST';
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
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1100'];

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
    division.divname, division.address as daddress, division.zipcode as dzipcode,division.tin as dtin,e.empid, date(e.hired) as hired from employee as e
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


  private function displayHeader($config)
  {

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $year = intval($config['params']['dataparams']['year']);

    $layoutsize = '1100';
    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BIR FORM 1604CF-SCHEDULE 7.3<br />ALPHALIST OF EMPLOYEES AS OF DECEMBER 31 WITH NO PREVIOUS EMPLOYER WITHIN THIS YEAR<br />AS OF DECEMBER 31, 2021', '400', null, false, '2px solid ', '', 'L', $font, '14', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> TIN: <br /> WITHHOLDING AGENTS NAME:', '170', null, false, '2px solid ', '', 'L', $font, '14', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('GROSS COMPENSATION INCOME', '10', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '65', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('NON-TAXABLE', '60', null, false, '2px solid', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('TAXABLE', '10', null, false, '2px solid', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', $font, '9', '', '', '');

    $str .= $this->reporter->col('YEAR-END ADJUSTMENT', '10', null, false, '2px solid', 'B', 'C', $font, '9', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Seq <br> No.', '10', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Tax <br> Identification <br> Number', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Name of Employees <br> (Last Name, First Name, <br> Middle Name)', '180', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Hired', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('13th Month <br> Pay & Other <br> benefits', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('SSS,GSIS, <br> PHIC, & <br> Pag-ibig <br> Contributions <br> & Union Dues', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Salaries & <br> Other Forms <br> of <br> Compensation', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('13th Month <br> Pay & Other <br> benefits', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Salaries & <br> Other Forms <br> of <br> Compensation', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Amt of <br> Exemption', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Premium Paid <br> on Health <br> and/or <br> Hospital', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Tax Due <br> (Jan-Dec))', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Tax Withheld <br> (Jan-Nov))', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Amt Withheld <br> & paid for in <br> December)', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col('Over Withheld <br> & Tax Employee', '70', null, false, '2px solid ', 'B', 'C', $font, '9', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }


  public function reportDefaultLayout($config)
  {

    $result = $this->reportDefault($config);
    $font = $this->companysetup->getrptfont($config['params']);
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);
    $year = intval($config['params']['dataparams']['year']);
    $i = 0;

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);

    foreach ($result as $key => $data) {
      $i = $i + 1;

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
      $i = number_format($i, 0);

      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($i, '10', null, false, '2px solid ', '', 'C', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->tin, '70', null, false, '2px solid ', '', 'C', $font, '9', '', '', '');
      $fullname = $data->emplast . ", " . $data->empfirst . " " . $data->empmiddle;
      $str .= $this->reporter->col($fullname, '180', null, false, '2px solid ', '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->hired, '70', null, false, '2px solid ', '', 'C', $font, '9', '', '', '');
      $str .= $this->reporter->col($bonusntax == 0 ? '-' : $bonusntax, '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($contribution == 0 ? '-' : $contribution, '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($basicsalary == 0 ? '-' : $basicsalary, '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col('-', '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col('-', '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col('-', '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col('-', '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($taxdue == 0 ? '-' : $taxdue, '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($wht == 0 ? '-' : $wht, '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col('-', '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(($taxdue - $wht) == 0 ? '-' : number_format($taxdue - $wht, 2), '70', null, false, '2px solid ', '', 'R', $font, '9', '', '', '');

      $str .= $this->reporter->endrow();
    } //end foreach
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();


    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class