<?php

namespace App\Http\Classes\modules\reportlist\payroll_reports;

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

class allowance_report
{
    public $modulename = 'Allowance Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1000px;max-width:1000px;';
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
        $fields = ['radioprint', 'divrep', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');

        $fields = ['radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreporttype.options', [
            ['label' => 'Employee`s with Allowances', 'value' => '0', 'color' => 'red'],
            ['label' => 'Overall Allowances', 'value' => '1', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 'default' as print, '' as divcode,
                '' as divid,'' as divname,'' as divrep,'' as division,'0' as reporttype,'' as client,
                '' as clientname,'' as dclientname,'0' as clientid");
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
            case '0': // Employee`s with Allowances
                return $this->Emp_Allowance_Layout($config);
                break;

            case '1': // Overall Allowances
                return $this->Overall_Allowance_Layout($config);
                break;
        }
    }

    //QRY SUMMARY
    public function default_query($config)
    {
        // QUERY  
        $divrep     = $config['params']['dataparams']['divrep'];
        $client     = $config['params']['dataparams']['dclientname'];

        $filter   = "";

        if ($divrep != 0) {
            $divid     = $config['params']['dataparams']['divid'];
            $filter .= " and emp.divid = $divid";
        }

        if ($client != '') {
            $clientid     = $config['params']['dataparams']['clientid'];
            $filter .= " and allow.empid = $clientid";
        }


        $query = "select concat(emp.empfirst, ' ', emp.empmiddle, ' ',emp.emplast) as employee,
                        b.clientname as branch,acc.codename as allowance,allow.allowance as amount,
                        date(allow.dateeffect) as dateeffect
                    from allowsetup as allow
                    left join employee as emp on emp.empid=allow.empid
                    left join client as b on b.clientid=emp.branchid
                    left join paccount as acc on acc.line= allow.acnoid
                    where year(dateend) = '9999' and acc.alias='Allowance'  $filter
                    order by employee, acc.codename";

        return $this->coreFunctions->opentable($query);
    }


    //QRY SUMMARY
    public function overall_query($config)
    {
        // QUERY  
        $divrep     = $config['params']['dataparams']['divrep'];
        $client     = $config['params']['dataparams']['dclientname'];

        $filter   = "";

        if ($divrep != 0) {
            $divid     = $config['params']['dataparams']['divid'];
            $filter .= " and emp.divid = $divid";
        }

        if ($client != '') {
            $clientid     = $config['params']['dataparams']['clientid'];
            $filter .= " and allow.empid = $clientid";
        }

        $query = "select  count(allow.empid) as empcount,b.clientname as branch,sum(allow.allowance) as amount
                    from allowsetup as allow
                    left join employee as emp on emp.empid=allow.empid
                    left join client as b on b.clientid=emp.branchid
                    left join paccount as acc on acc.line= allow.acnoid
                    where year(allow.dateend) = '9999' and acc.alias='Allowance'   $filter
                    group by b.clientname
                    order by b.clientname";
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
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $reporttype     = $config['params']['dataparams']['reporttype'];

        $str = '';
        $layoutsize = 1000;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        // $str .= '<br/><br/>';

        if ($reporttype == 0) {
            $type = 'Allowance Report';
        } else {
            $type = 'Overall Allowances';
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($type, null, null, false, $border, '', '', $font, '18', 'B', '', '');
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


        $str .= $this->reporter->endtable();
        switch ($reporttype) {
            case '0': // default
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('NAME', 280, null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('BRANCH', 250, null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('ALLOWANCE', 250, null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('AMOUNT', 100, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('EFFECTIVE DATE', 120, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
            case '1': // overall allowance
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('BRANCH', 300, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('NO. OF EMPLOYEES', 200, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TOTAL AMOUNT', 200, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
        }

        return $str;
    }

    public function Emp_Allowance_Layout($config)
    {
        $result = $this->default_query($config);
        $reporttype     = $config['params']['dataparams']['reporttype'];

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $count = 41;
        $page = 40;
        if ($reporttype == 0) {
            $layoutsize = 1000;
        } else {
            $layoutsize = 500;
        }

        $str = '';
        $total = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '25px;margin-top:5px;');
        $str .= $this->displayHeader($config);

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->employee, 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->branch, 250, null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->allowance, 250, null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->amount == 0 ? '-' : number_format($data->amount, 2), 100, null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->dateeffect, 120, null, false, $border, '', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
            $total += $data->amount;
        }



        $str .= $this->reporter->col('', 200, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 250, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('TOTALAMOUNT :', 250, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($total, 2), 100, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', 120, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();


        return $str;
    }


    public function Overall_Allowance_Layout($config)
    {
        $result = $this->overall_query($config);
        $reporttype     = $config['params']['dataparams']['reporttype'];

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;

        $layoutsize = 1000;


        $str = '';
        $total = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->branch, 300, null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->empcount, 200, null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->amount == 0 ? '-' : number_format($data->amount, 2), 200, null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
            $total += $data->amount;
        }

        $str .= $this->reporter->col('', 300, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT :', 200, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($total, 2), 200, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();


        return $str;
    }
}//end class