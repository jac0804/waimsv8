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

class monthly_turnover_rate
{
    public $modulename = 'Monthly Turn-over Rate';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1640px;max-width:1640px;';
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
        $fields = ['radioprint', 'divrep', 'year'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');
        data_set($col1, 'traintype.lookupclass', 'lookuptrainingtype');
        data_set($col1, 'traintype.label', 'Training Type');
        data_set($col1, 'year.required', true);

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            '0' as divid,'' as divcode,
                        '' as divname,'' as divrep,
                        '' as division, left(now(),4) as year");
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


    public function totalemp($config)
    {
        $year = $config['params']['dataparams']['year'];
        $query = "select count(empid) as totalemp from employee where year(hired) <='" . $year . "'";
        return $this->coreFunctions->opentable($query);
    }

    public function month($config)
    {
        $months = []; // dito natin ilalagay ang mga buwan
        // loop mula 1 (january) hanggang 12 (december)
        for ($i = 1; $i <= 12; $i++) {
            // kunin ang pangalan ng buwan at gawing uppercase gamit ang strtoupper()
            $months[] = strtoupper(date('F', mktime(0, 0, 0, $i, 1)));
        }
        return $months;
    }


    public function reportDefault($config)
    {
        // QUERY
        $year = $config['params']['dataparams']['year'];
        // $query = "select count(clr.trno) as total, month(clr.dateid) as month from hclearance as clr where year(clr.dateid) <='" . $year . "'
        //           group by month(clr.dateid)";
        $query = "select count(clr.empid) as total, month(clr.resigned) as month from employee as clr where year(clr.resigned) <='" . $year . "' group by month(clr.resigned)";
        return $this->coreFunctions->opentable($query);
    }



    public function reportDefaultLayouts($config)
    {
        $result = $this->reportDefault($config);
        $month = $this->month($config);


        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';
        $count = 3;
        $page = 3;
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayHeader($config);

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col($month, '250', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', ''); // Array to string conversion

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
        $str .= $this->reporter->endreport();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $totalemployee = $this->totalemp($config);
        $month = $this->month($config);

        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayHeader($config);

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();


        //ang ginawa ko total na ng 2025 yung nilagay ko sa total employee tapos niminus ko na lang kada nagkakroon ng nagreresign sa bawat month
        $runningTotalEmp  = $totalemployee[0]->totalemp;
        foreach ($month as $index => $m) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($m, '250', null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');
            // month number
            $intmonth = $index + 1;

            $totalseparated = 0;
            foreach ($result as $tlseparated) {
                if ($tlseparated->month == $intmonth) {
                    $totalseparated = $tlseparated->total;
                    break;
                }
            }
            $totalremaining = $runningTotalEmp - $totalseparated;
            if ($totalseparated != 0) {
                $turnov = round(($totalseparated / $totalremaining) * 100, 2);
                $turnover = $turnov . ' %';
            } else {
                $turnover = '';
            }
            $str .= $this->reporter->col($totalremaining, '250', null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($totalseparated, '250', null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($turnover, '250', null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();

            $runningTotalEmp = $totalremaining;
        }


        $str .= $this->reporter->endrow();



        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
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
        $companyid   = $config['params']['companyid'];
        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $label = 'Monthly Turn-over Rate';

        if ($companyid == 62) { //onesky
            $label = 'Employee Turn-over Rate';
        }

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($label, null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->col('NO. OF', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->col('SEPARATED', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->col('TURNOVER', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MONTH', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->col('EMPLOYEES', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->col('EMPLOYEES', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->col('RATE', '250', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class