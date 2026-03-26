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

class separated_employees_and_status
{
    public $modulename = 'Separated Employees and Status';
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
        $fields = ['radioprint', 'divrep', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');

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
            '' as divid,'' as divcode,
                        '' as divname,'' as divrep,
                        '' as division,  left(adddate(now(),-30),10) as start,
                         left(now(),10) as end");
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
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }

        // $query = "select concat(emp.empfirst, ' ', ifnull(emp.empmiddle, ''), ' ', emp.emplast) as empname,
        //         divs.divname as company,clr.jobtitle as designation,date(clr.dateid) as transdate,
        //         ifnull(clr.resignedtype,'') as statuss
        //         from clearance as clr
        //         left join employee as emp on emp.empid = clr.empid
        //         left join division as divs on divs.divid=emp.divid  where  date(clr.dateid) between '$start' and '$end' $filter
        //         union all

        //         select concat(emp.empfirst, ' ', ifnull(emp.empmiddle, ''), ' ', emp.emplast) as empname,
        //         divs.divname as company,clr.jobtitle as designation,date(clr.dateid) as transdate,
        //         ifnull(clr.resignedtype,'') as statuss
        //         from hclearance as clr
        //         left join employee as emp on emp.empid = clr.empid
        //         left join division as divs on divs.divid=emp.divid where date(clr.dateid) between '$start' and '$end' $filter ";

        $query = "select concat(emp.empfirst, ' ', ifnull(emp.empmiddle, ''), ' ', emp.emplast) as empname,
                divs.divname as company,job.jobtitle as designation,date(emp.resigned) as transdate,
                ifnull(emp.resignedtype,'') as statuss
                from employee as emp left join division as divs on divs.divid=emp.divid left join jobthead as job on job.line=emp.jobid
                where  date(emp.resigned) between '$start' and '$end' $filter";

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
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Separated Employees and Status', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From ' . $start . ' to ' . $end, NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('The following is a list of employees who have separated from the Motormate Group of Companies. ', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME', '200', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
        $str .= $this->reporter->col('COMPANY BRANCH', '200', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
        $str .= $this->reporter->col('DESIGNATION', '200', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
        $str .= $this->reporter->col('TRANS-DATE', '100', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
        $str .= $this->reporter->col('EMP-STATUS', '300', null,  $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B',  $fontcolor, '8px');

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

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col($data->empname, '200', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->company, '200', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->designation, '200', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->transdate, '100', null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->statuss, '300', null, false, $border, 'TLB', 'LT', $font, $font_size, '', '', '');

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
}//end class