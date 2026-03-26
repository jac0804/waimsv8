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
use BcMath\Number;
use Illuminate\Support\Facades\URL;

class length_of_service
{
    public $modulename = 'Length of Service';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
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
        $companyid = $config['params']['companyid'];
        // $companyid = 58;
        // $fields = ['radioprint', 'dclientname','year'];
        // if ($companyid == 58) { // cdohris 
            $fields = ['radioprint', 'dclientname','ddivname', 'year'];
        // }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        // if ($companyid == 58) { // cdohris 
            data_set($col1, 'ddivname.label', 'Company');
        // }
        data_set($col1, 'year.required', false);
        data_set($col1, 'year.label', 'Year of service');
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable("select 
                'default' as print,
                0 as clientid,
                '' as client,
                '' as clientname,
                '' as dclientname,
                '' as year,
                '' as year2,
                '' as divname,
                '0' as divid,
                '' as division
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
        $companyid = $config['params']['companyid'];
        // $companyid     = 58;
        switch ($companyid) {
            case 58: // cdohris

                return $this->report_cdohris_Layout($config);
                break;

            default:
                // $this->reportNewStandardLayout($config);
                return $this->reportDefaultLayout($config);
                break;
        }
    }

    public function reportDefault($config)
    {
        // QUERY
        $employee     = $config['params']['dataparams']['dclientname'];
        $empid     = $config['params']['dataparams']['clientid'];
        $companyid     = $config['params']['companyid'];
        // $companyid     = 58;
        // $divid     = $config['params']['dataparams']['divid'];
        
        $divname = isset($config['params']['dataparams']['divname']) ? $config['params']['dataparams']['divname'] : '';
        $year     = $config['params']['dataparams']['year'];

        $filter   = "";
        $join   = "";
        $fields = "";
        if ($employee != "") {
            $filter .= " and e.empid = '$empid'";
        }
        switch ($companyid) {
            case '58':
                if ($divname != '') {
                    $filter .= " and di.divname =  '$divname'";
                }
                if ($year != '') {
                    $filter .= " and timestampdiff(year,date(hired), curdate()) =  $year ";
                }
            
                $query = "
                    select concat(emplast,', ',empfirst,' ',empmiddle) as employee,date(hired) as hired,
                    TIMESTAMPDIFF(YEAR, hired, CURDATE()) as lenservice , di.divname as division,jt.jobtitle 
                    from employee as e
                    left join division as di on di.divid = e.divid
                    left join jobthead as jt on jt.line = e.jobid
                    where isactive=1 and resigned is null $filter
                    order by hired";
                break;
            
            default:
                if ($year != '') {
                    $filter .= "  and year(now())-year(e.hired) = '$year'";
                }
                $query = "
                    select TIMESTAMPDIFF(year,e.bday, now()) as age,c.client as empcode,year(now())-year(e.hired) as year,
                    concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as name,
                    e.hired,e.regular,e.resigned,e.division,dept.deptname,e.jobtitle as position,r.basicrate as salary
                    from employee as e 
                    left join department as dept on dept.deptcode=e.dept
                    LEFT JOIN division  as dv ON dv.divcode=e.division
                    LEFT JOIN section as sec ON sec.sectcode=e.orgsection
                    left join client as c on c.clientid=e.empid
                    left join ratesetup as r on r.empid=c.clientid and r.dateend='9999-12-31 00:00:00'
                    where e.regular is not null  and e.resigned is null  $filter
                ";
                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    
    private function Cdohris_display_Header($config)
    {

        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = 11;

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $employee     = $config['params']['dataparams']['dclientname'];
        $division     = $config['params']['dataparams']['division'];
        $companyid   = $config['params']['companyid'];

        if ($employee == '') {
            $employee = "ALL";
        }

        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LENGTH OF SERVICE', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->col('EMPLOYEE: ' . $employee, NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME', '300', null,  $bgcolors, $border, 'TLB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('COMPANY', '200', null,  $bgcolors, $border, 'TLB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('DESIGNATION', '200', null,  $bgcolors, $border, 'TLB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('HIRED', '150', null,  $bgcolors, $border, 'TLB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col("SPAN OF SERVICE", '150', null,  $bgcolors, $border, 'TLBR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function report_cdohris_Layout($config)
    {

        $result = $this->reportDefault($config);

        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = 11;
        $count = 55;
        $page = 55;
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->Cdohris_display_Header($config);
        $chkemp = "";
        $olddocno = "";
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        foreach ($result as $key => $data) {

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->employee, '300', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->division, '200', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->jobtitle, '200', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->hired, '150', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->lenservice, '150', null, false, $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->Cdohris_display_Header($config);
            //     $page = $page + $count;
            // }
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    
    private function displayHeader($config)
    {

        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = 11;

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $employee     = $config['params']['dataparams']['dclientname'];
        $year     = $config['params']['dataparams']['year'];

        if ($employee == '') {
            $employee = "ALL";
        }

        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LENGTH OF SERVICE', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('List of Employees within ' . $year.' Years of Employment ', NULL, null, false, $border, '', 'L', $font, '10', '', 'I', '', '');
        $str .= $this->reporter->endrow();
        
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('EMPLOYEE: ' . $employee, NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->col('', '250', null, $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NAME', '300', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('POSITION', '200', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col("AGE", '150', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col("SALARY", '200', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col("YEAR", '150', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = 11;
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
        
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col($data->name, '300', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->position, '200', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->age, '150', null, false, $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->salary,2), '200', null, false, $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->year, '150', null, false, $border, 'LBR', 'CT', $font, $font_size, '', '', '');

            
            
            $str .= $this->reporter->endrow();

            // $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                
                $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
                
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endtable();
        // $str .= $this->reporter->printline();
        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }





    // old default

    // public function createHeadField($config)
    // {
    //     $companyid = $config['params']['companyid'];
    //     $fields = ['radioprint', 'dclientname'];

    //     if ($companyid == 58) { // cdohris 
    //         array_push($fields, 'ddivname', 'year');
    //     }
    //     $col1 = $this->fieldClass->create($fields);
    //     data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    //     data_set($col1, 'dclientname.label', 'Employee');
    //     if ($companyid == 58) { // cdohris 
    //         data_set($col1, 'year.required', false);
    //         data_set($col1, 'year.label', 'Year of service');
    //         data_set($col1, 'ddivname.label', 'Company');
    //     }
    //     $fields = ['print'];
    //     $col2 = $this->fieldClass->create($fields);

    //     return array('col1' => $col1, 'col2' => $col2);
    // }

    // public function paramsdata($config)
    // {
    //     // NAME NG INPUT YUNG NAKA ALIAS
    //     $center = $config['params']['center'];
    //     $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    //     return $this->coreFunctions->opentable("select 
    //             'default' as print,
    //             0 as clientid,
    //             '' as client,
    //             '' as clientname,
    //             '' as dclientname,
    //             '' as year,
    //             '' as divname,
    //             '0' as divid,
    //             '' as division
    //             ");
    // }

    
    // public function reportDefault($config)
    // {
    //     // QUERY
    //     $employee     = $config['params']['dataparams']['dclientname'];
    //     $empid     = $config['params']['dataparams']['clientid'];
    //     $companyid     = $config['params']['companyid'];
    //     $divid     = $config['params']['dataparams']['divid'];
    //     $year     = $config['params']['dataparams']['year'];

    //     $filter   = "";
    //     $join   = "";
    //     $fields = "";

    //     if ($employee != "") {
    //         $filter .= " and empid = '$empid'";
    //     }

    //     if ($companyid == 58) { // cdohris
    //         $join = " left join division as di on di.divid = employee.divid
    //         left join jobthead as jt on jt.line = employee.jobid";
    //         $fields = ", di.divname as division,jt.jobtitle ";
    //         if ($divid != 0) {
    //             $filter .= " and employee.divid =  $divid ";
    //         }
    //         if ($year != '') {
    //             $filter .= " and timestampdiff(year,date(hired), curdate()) =  $year ";
    //         }
    //     }

    //     $query = "select concat(emplast,', ',empfirst,' ',empmiddle) as employee,date(hired) as hired,
    //                     TIMESTAMPDIFF(YEAR, hired, CURDATE()) as lenservice $fields
    //                 from employee 
    //                 $join 
    //                 where isactive=1 and resigned is null $filter
    //                 order by hired";

    //     return $this->coreFunctions->opentable($query);
    // }



    // private function displayHeader($config)
    // {

    //     $border = '1px solid';
    //     $font = 'Century Gothic';
    //     $font_size = 11;

    //     $fontcolor = '#FFFFFF'; //white
    //     $bgcolors = '#000000'; //black
    //     $center     = $config['params']['center'];
    //     $username   = $config['params']['user'];
    //     $employee     = $config['params']['dataparams']['dclientname'];

    //     if ($employee == '') {
    //         $employee = "ALL";
    //     }

    //     $str = '';

    //     $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->letterhead($center, $username, $config);
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .= '<br/><br/>';

    //     $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('LENGTH OF SERVICE', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    //     $str .= $this->reporter->startrow();

    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->col('EMPLOYEE: ' . $employee, NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    //     $str .= $this->reporter->endtable();

    //     // $str .= $this->reporter->col('', '250', null, $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
    //     $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('EMPLOYEE NAME', '300', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    //     $str .= $this->reporter->col('HIRED DATE', '150', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    //     $str .= $this->reporter->col("LENGTH OF SERVICE (YEAR)", '250', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //     return $str;
    // }

    // public function reportDefaultLayout($config)
    // {
    //     $result = $this->reportDefault($config);
    //     $border = '1px solid #C0C0C0 !important';
    //     $font = 'Century Gothic';
    //     $font_size = 11;
    //     $count = 55;
    //     $page = 55;
    //     $str = '';

    //     if (empty($result)) {
    //         return $this->othersClass->emptydata($config);
    //     }

    //     $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    //     $str .= $this->displayHeader($config);
    //     $chkemp = "";
    //     $olddocno = "";

    //     foreach ($result as $key => $data) {
    //         $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    //         $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->addline();

    //         $str .= $this->reporter->col($data->employee, '300', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
    //         $str .= $this->reporter->col($data->hired, '150', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
    //         $str .= $this->reporter->col($data->lenservice, '250', null, false, $border, 'LBR', 'CT', $font, $font_size, '', '', '');
    //         $str .= $this->reporter->endrow();

    //         $str .= $this->reporter->endtable();

    //         if ($this->reporter->linecounter == $page) {
    //             $str .= $this->reporter->endtable();
    //             $str .= $this->reporter->page_break();
    //             $str .= $this->displayHeader($config);
    //             $str .= $this->reporter->endrow();
    //             $str .= $this->reporter->endtable();
    //             $page = $page + $count;
    //         }
    //     }
    //     $str .= $this->reporter->endtable();
    //     // $str .= $this->reporter->printline();
    //     // $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->endreport();

    //     return $str;
    // }

}//end class