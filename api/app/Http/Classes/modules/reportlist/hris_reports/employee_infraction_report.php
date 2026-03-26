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

class employee_infraction_report
{
  public $modulename = 'Employee Infraction Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'L', 'format' => 'legal', 'layoutSize' => '1200'];

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
    $fields = ['radioprint', 'divrep', 'deptrep','year'];

    $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
      data_set($col1, 'divrep.label', 'Company');
      data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptrep.label', 'Department');

      data_set($col1, 'year.lookupclass', 'year');
      data_set($col1, 'year.label', 'Year');
    

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    '' as deptrep,
    '' as year
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
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    return $this->reportDefaultLayout($config);
  }

    public function reportDefault($config)
  {
    // QUERY
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $year1       = $config['params']['dataparams']['year'];

    $filter   = "";


    if ($divid != 0) {
      $filter .= " and e.divid = $divid";
    }
    if ($deptid != 0) {
      $filter .= " and dept.clientid = $deptid";
    }
    if ($year1 != 0) {
      $filter .= " and year(dp.dateid) = $year1";
    }

    $query = "select dept.clientname,(case when MONTH(dp.dateid)=01 then COUNT(dp.trno) ELSE '-' END) AS moJan,
             (case when MONTH(dp.dateid)=02 then COUNT(dp.trno) ELSE '-' END) AS moFeb,
             (case when MONTH(dp.dateid)=03 then COUNT(dp.trno) ELSE '-' END) AS moMar,
             (case when MONTH(dp.dateid)=04 then COUNT(dp.trno) ELSE '-' END) AS moApr,
             (case when MONTH(dp.dateid)=05 then COUNT(dp.trno) ELSE '-' END) AS moMay,
             (case when MONTH(dp.dateid)=06 then COUNT(dp.trno) ELSE '-' END) AS moJun,
             (case when MONTH(dp.dateid)=07 then COUNT(dp.trno) ELSE '-' END) AS moJuly,
             (case when MONTH(dp.dateid)=08 then COUNT(dp.trno) ELSE '-' END) AS moAug,
             (case when MONTH(dp.dateid)=09 then COUNT(dp.trno) ELSE '-' END) AS moSep,
             (case when MONTH(dp.dateid)=10 then COUNT(dp.trno) ELSE '-' END) AS moOct,
             (case when MONTH(dp.dateid)=11 then COUNT(dp.trno) ELSE '-' END) AS moNov,
             (case when MONTH(dp.dateid)=12 then COUNT(dp.trno) ELSE '-' END) AS moDec

             FROM disciplinary AS dp
             LEFT JOIN employee AS e ON e.empid = dp.empid
             LEFT JOIN division AS d ON d.divid=e.divid
             LEFT JOIN client AS dept ON e.deptid = dept.clientid AND dept.isdepartment = 1 
             where ''='' $filter
             GROUP BY dp.dateid,dept.clientname,dept.clientname
             UNION ALL
             SELECT dept.clientname,(case when MONTH(dp.dateid)=01 then COUNT(dp.trno) ELSE '-' END) AS moJan,
             (case when MONTH(dp.dateid)=02 then COUNT(dp.trno) ELSE '-' END) AS moFeb,
             (case when MONTH(dp.dateid)=03 then COUNT(dp.trno) ELSE '-' END) AS moMar,
             (case when MONTH(dp.dateid)=04 then COUNT(dp.trno) ELSE '-' END) AS moApr,
             (case when MONTH(dp.dateid)=05 then COUNT(dp.trno) ELSE '-' END) AS moMay,
             (case when MONTH(dp.dateid)=06 then COUNT(dp.trno) ELSE '-' END) AS moJun,
             (case when MONTH(dp.dateid)=07 then COUNT(dp.trno) ELSE '-' END) AS moJuly,
             (case when MONTH(dp.dateid)=08 then COUNT(dp.trno) ELSE '-' END) AS moAug,
             (case when MONTH(dp.dateid)=09 then COUNT(dp.trno) ELSE '-' END) AS moSep,
             (case when MONTH(dp.dateid)=10 then COUNT(dp.trno) ELSE '-' END) AS moOct,
             (case when MONTH(dp.dateid)=11 then COUNT(dp.trno) ELSE '-' END) AS moNov,
             (case when MONTH(dp.dateid)=12 then COUNT(dp.trno) ELSE '-' END) AS moDec

             from hdisciplinary as dp
             LEFT JOIN employee AS e ON e.empid = dp.empid
             LEFT JOIN division AS d ON d.divid=e.divid
             LEFT JOIN client AS dept ON e.deptid = dept.clientid AND dept.isdepartment = 1
             where ''='' $filter
             GROUP BY dp.dateid,dept.clientname,dept.clientname
             ORDER BY clientname";

    return $this->coreFunctions->opentable($query);
  }

    public function displayHeader($config)
  {
    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = 10;
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $year1       = $config['params']['dataparams']['year'];
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black
    $layoutsize=1400;

    $str = '';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
   
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->col('Employee Infraction Report',100, null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Count of DISCIPLINARY ACTION',100, null, false, $border, '', 'L', $font, '15', 'B', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year '.$year1,100, null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable(($this->reportParams['layoutSize']));
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT',225, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('January',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('February',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('March',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('April',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('May',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('June',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('July',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('August',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('September',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('October',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('November',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('December',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('Grand Total',75, null, $bgcolors, $border, 'TRL', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

    public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = 10;
    $count = 55;
    $page = 55;
    $str = '';
    $border2 = '1px solid';
    $totalJan=0;
    $totalFeb=0;
    $totalMar=0;
    $totalApr=0;
    $totalMay=0;
    $totalJun=0;
    $totalJuly=0;
    $totalAug=0;
    $totalSep=0;
    $totalOct=0;
    $totalNov=0;
    $totalDec=0;
    $total=0;
    $layoutsize=1400;
    

    if (empty($result)) {
     return $this->othersClass->emptydata($config);
    }
      $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '40px;margin-top:5px;');

      $str .= $this->displayHeader($config);

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);

     foreach ($result as $key => $data) {
      $totalmonth=0;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->clientname,225, null, false, $border, 'TRBL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->moJan,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->moFeb,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');  
      $str .= $this->reporter->col($data->moMar,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');  
      $str .= $this->reporter->col($data->moApr,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', ''); 
      $str .= $this->reporter->col($data->moMay,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->moJun,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', ''); 
      $str .= $this->reporter->col($data->moJuly,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');  
      $str .= $this->reporter->col($data->moAug,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');  
      $str .= $this->reporter->col($data->moSep,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', ''); 
      $str .= $this->reporter->col($data->moOct,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->moNov,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->moDec,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');
      $totalmonth+=($data->moJan+$data->moFeb+$data->moMar+$data->moApr+$data->moMay+$data->moJun+$data->moJuly+$data->moAug+$data->moSep+$data->moOct+$data->moNov+$data->moDec);
      $str .= $this->reporter->col($totalmonth,75, null, false, $border, 'TRB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
  

      $totalJan+=($data->moJan);
      $totalFeb+=($data->moFeb); 
      $totalMar+=($data->moMar); 
      $totalApr+=($data->moApr); 
      $totalMay+=($data->moMay); 
      $totalJun+=($data->moJun); 
      $totalJuly+=($data->moJuly); 
      $totalAug+=($data->moAug); 
      $totalOct+=($data->moOct); 
      $totalNov+=($data->moNov); 
      $totalDec+=($data->moDec);  
      $total+=($data->moJan+$data->moFeb+$data->moMar+$data->moApr+$data->moMay+$data->moJun+$data->moJuly+$data->moAug+$data->moSep+$data->moOct+$data->moNov+$data->moDec);

      
    }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Grand Total',225, null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalJan == 0 ? '-' :$totalJan,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalFeb == 0 ? '-' :$totalFeb,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalMar == 0 ? '-' :$totalMar,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalApr == 0 ? '-' :$totalApr,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalMay == 0 ? '-' :$totalMay,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalJun == 0 ? '-' :$totalJun,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalJuly == 0 ? '-' :$totalJuly,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalAug == 0 ? '-' :$totalAug,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalSep == 0 ? '-' :$totalSep,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalOct == 0 ? '-' :$totalOct,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalNov == 0 ? '-' :$totalNov,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($totalDec == 0 ? '-' :$totalDec,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($total == 0 ? '-' :$total,75, null, false, $border, 'BR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();

   //insight
    $months = [
    'January'   => $totalJan,
    'February'  => $totalFeb,
    'March'     => $totalMar,
    'April'     => $totalApr,
    'May'       => $totalMay,
    'June'      => $totalJun,
    'July'      => $totalJuly,
    'August'    => $totalAug,
    'September' => $totalSep,
    'October'   => $totalOct,
    'November'  => $totalNov,
    'December'  => $totalDec,
    ];
    $year1       = $config['params']['dataparams']['year'];
    $monthsWithValue = array_filter($months, function ($val) {
    return $val > 0;
    });

    $monthNames = array_keys($monthsWithValue);

    $startMonth = reset($monthNames); 
    $endMonth   = end($monthNames); 
    $period = '<b>'.$startMonth.'</b> to <b>'.$endMonth.'</b> '.$year1;
      
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('•Key Insight',225, null, false, $border, '', 'L', $font, '15', 'B', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable(600);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('🡢Total case:',103, null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($total.' disciplinary actions from '.$period.'.',497, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;

  }
  


}//end class