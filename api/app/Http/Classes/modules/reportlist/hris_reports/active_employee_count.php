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
use Illuminate\Support\Facades\URL;

class active_employee_count
{
    public $modulename = 'Employee Count - Active';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $fields = [];

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
        $fields = ['radioprint', 'divrep', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'By Company and Department', 'value' => '0', 'color' => 'red'],
            ['label' => 'By Company and Age', 'value' => '1', 'color' => 'red']
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
            '0' as reporttype,
              '0' as divid,'' as divcode,
              '' as divname,'' as divrep,
              '' as division");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0':
                $str = $this->reportLayout1($config);
                break;
            case '1':
                $str = $this->reportLayout2($config);
                break;
        }
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function reportDefault($config)
    {
        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }


        switch ($reporttype) {
            case '0':
                $query = "select company,department,count(empid) as totalemp 
                        from (select emp.divid,emp.deptid,d.divname as company,dept.clientname as department,
                                    emp.empid
                            from employee as emp
                            left join division as d on d.divid=emp.divid
                            left join client as dept on dept.clientid=emp.deptid
                            where emp.isactive=1 $filter) as a
                        group by company,department 
                        order by company,department";
                break;
            case '1':
                $query = "select company,age,count(empid) as totalemp 
                        from (select emp.divid,d.divname as company,emp.empid,emp.bday,
                                    TIMESTAMPDIFF(YEAR, emp.bday, CURDATE()) AS age
                                from employee as emp
                                left join division as d on d.divid=emp.divid
                                where emp.isactive=1) as a
                        group by company,age
                        order by company,age";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function totalemp($config)
    {
        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }
        $query = "select count(empid) as total_employees from ( select emp.empid
                from division as divi
                left join employee as emp on emp.divid = divi.divid 
                where isactive=1 $filter) as k";
        return $this->coreFunctions->opentable($query);
    }


    private function headerlayout1($config)
    {

        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = '11';
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $division   = $config['params']['dataparams']['division'];
        $divname     = $config['params']['dataparams']['divname'];
        $str = '';

        $layoutsize = 1000;

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $datar = $this->totalemp($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Count - Active', '500', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if (empty($divname)) {
            $divname = 'ALL';
        }
        $str .= $this->reporter->col('Company: ' . $divname, '500', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if (!empty($datar)) {
            $str .= $this->reporter->col('Active Employee Count : ' . number_format($datar[0]->total_employees, 0) . ' Employees', '500', null, false, $border, '', 'L', $font, '12', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Company Name', '450', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('Department', '400', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('No. of Employee', '150', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportLayout1($config)
    {
        $result = $this->reportDefault($config);
        // $gen_res = $this->genration($config);
        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '11';
        $count = 55;
        $page = 55;
        $str = '';

        $layoutsize = 1000;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->headerlayout1($config);

        $company = '';

        foreach ($result as $key => $data) {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            if ($company != $data->company) {
                $str .= $this->reporter->col($data->company, '450', null, '', $border, 'LT', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->department, '400', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->totalemp, '150', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            } else {
                $str .= $this->reporter->col('', '450', null, '', $border, 'LR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->department, '400', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->totalemp, '150', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            }

            $company = $data->company;


            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->headerlayout1($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
        }


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '450', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '400', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    private function headerlayout2($config)
    {

        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = '11';
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $division   = $config['params']['dataparams']['division'];
        $divname     = $config['params']['dataparams']['divname'];
        $str = '';

        $layoutsize = 1000;

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $datar = $this->totalemp($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Count - Active', '500', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if (empty($divname)) {
            $divname = 'ALL';
        }
        $str .= $this->reporter->col('Company: ' . $divname, '500', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if (!empty($datar)) {
            $str .= $this->reporter->col('Active Employee Count : ' . number_format($datar[0]->total_employees, 0) . ' Employees', '500', null, false, $border, '', 'L', $font, '12', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Company Name', '450', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('Age', '400', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('No. of Employee', '150', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportLayout2($config)
    {
        $result = $this->reportDefault($config);
        // $gen_res = $this->genration($config);
        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '11';
        $count = 55;
        $page = 55;
        $str = '';

        $layoutsize = 1000;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->headerlayout2($config);

        $company = '';

        foreach ($result as $key => $data) {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            if ($company != $data->company) {
                $str .= $this->reporter->col($data->company, '450', null, '', $border, 'LT', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->age . ' years old', '400', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->totalemp, '150', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            } else {
                $str .= $this->reporter->col('', '450', null, '', $border, 'LR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->age . ' years old', '400', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->totalemp, '150', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            }

            $company = $data->company;


            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col('', '450', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '400', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '150', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->headerlayout2($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
        }


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '450', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '400', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, '', $border, 'T', 'LT', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class