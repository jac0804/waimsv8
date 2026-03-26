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

class employee_movement_report
{
    public $modulename = 'Employee Movement Report';
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
        $fields = ['radioprint', 'divrep', 'year', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Newly Hired Employees', 'value' => '0', 'color' => 'red'],
            ['label' => 'Resigned Employees', 'value' => '1', 'color' => 'red']
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
             left(now(),4) as year,
            '0' as reporttype,
              '' as divid,'' as divcode,
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
        $str = $this->reportLayout1($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function reportDefault($config)
    {
        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $year = $config['params']['dataparams']['year'];

        if ($divrep != '') {
            $filter .= " and emp.divid = $divid";
        }

        if ($reporttype == 0) {
            $date = 'emp.hired';
            $filter .= " and emp.isactive=1 and emp.resigned is null and year(emp.hired)= '$year'";
        } else {
            $date = 'emp.resigned';
            $filter .= " and emp.resigned is not null and year(emp.resigned)= '$year'";
        }

        $query = "select company,department, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
                        sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                        sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                    from (select d.divname as company,dept.clientname as department,emp.empid,$date,
                                sum(case when month($date)=1 then 1 else 0 end) as mojan,
                                sum(case when month($date)=2 then 1 else 0 end) as mofeb,
                                sum(case when month($date)=3 then 1 else 0 end) as momar,
                                sum(case when month($date)=4 then 1 else 0 end) as moapr,
                                sum(case when month($date)=5 then 1 else 0 end) as momay,
                                sum(case when month($date)=6 then 1 else 0 end) as mojun,
                                sum(case when month($date)=7 then 1 else 0 end) as mojul,
                                sum(case when month($date)=8 then 1 else 0 end) as moaug,
                                sum(case when month($date)=9 then 1 else 0 end) as mosep,
                                sum(case when month($date)=10 then 1 else 0 end) as mooct,
                                sum(case when month($date)=11 then 1 else 0 end) as monov,
                                sum(case when month($date)=12 then 1 else 0 end) as modec
                        from employee as emp
                        left join division as d on d.divid=emp.divid
                        left join client as dept on dept.clientid=emp.deptid
                        where 1=1 $filter
                        group by d.divname,dept.clientname,emp.empid,$date) as a
                    group by company,department
                    order by company,department";
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
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str = '';
        $label = '';

        $layoutsize = 1800;

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        if ($reporttype == 0) {
            $label = 'Newly Hired Employees';
        } else {
            $label = 'Resigned Employees';
        }

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Movement Report - ' . $label, '500', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
        $str .= $this->reporter->col('Company', '310', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('Department', '310', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('JAN', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('FEB', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('MAR', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('APR', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('MAY', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('JUN', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('JUL', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('AUG', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('SEP', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('OCT', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('NOV', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('DEC', '90', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('TOTAL', '100', '', $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
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
        $company = '';
        $layoutsize = 1800;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->headerlayout1($config);
        $ab = 0;
        $totalmojan = $totalmofeb = $totalmomar = $totalmoapr = $totalmomay = $totalmojun = $totalmojul = $totalmoaug = $totalmosep = $totalmooct = $totalmonov = $totalmodec = $amt = $totalamt = 0;

        //brand
        $subjan = $subfeb = $submar = $subapr = $submay = $subjun = $subjul = $subaug = $subsep = $suboct = $subnov = $subdec = $subamt = 0;
        //part
        $gsubjan = $gsubfeb = $gsubmar = $gsubapr = $gsubmay = $gsubjun = $gsubjul = $gsubaug = $gsubsep = $gsuboct = $gsubnov = $gsubdec = $gsubamt = 0;


        foreach ($result as $key => $data) {
            $mojan = number_format($data->mojan, $ab);
            $mofeb = number_format($data->mofeb, $ab);
            $momar = number_format($data->momar, $ab);
            $moapr = number_format($data->moapr, $ab);
            $momay = number_format($data->momay, $ab);
            $mojun = number_format($data->mojun, $ab);
            $mojul = number_format($data->mojul, $ab);
            $moaug = number_format($data->moaug, $ab);
            $mosep = number_format($data->mosep, $ab);
            $mooct = number_format($data->mooct, $ab);
            $monov = number_format($data->monov, $ab);
            $modec = number_format($data->modec, $ab);

            if ($mojan == 0) $mojan = '';
            if ($mofeb == 0) $mofeb = '';
            if ($momar == 0) $momar = '';
            if ($moapr == 0) $moapr = '';
            if ($momay == 0) $momay = '';
            if ($mojun == 0) $mojun = '';
            if ($mojul == 0) $mojul = '';
            if ($moaug == 0) $moaug = '';
            if ($mosep == 0) $mosep = '';
            if ($mooct == 0) $mooct = '';
            if ($monov == 0) $monov = '';
            if ($modec == 0) $modec = '';

            $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            // $str .= $this->reporter->addline();

            if ($company != $data->company) {
                $str .= $this->reporter->col($data->company, '310', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->department, '310', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($mojan, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mofeb, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($momar, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($moapr, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($momay, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mojun, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mojul, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($moaug, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mosep, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mooct, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($monov, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($modec, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col(number_format($amt, $ab), '100', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
            } else {
                $str .= $this->reporter->col('', '310', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->department, '310', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($mojan, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mofeb, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($momar, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($moapr, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($momay, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mojun, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mojul, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($moaug, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mosep, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($mooct, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($monov, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col($modec, '90', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
                $str .= $this->reporter->col(number_format($amt, $ab), '100', null, false, $border, 'TLRB', 'C', $font, $font_size, '', '', '0 2px 0 0', '');
            }

            $company = $data->company;

            $subjan += $data->mojan;
            $subfeb += $data->mofeb;
            $submar += $data->momar;
            $subapr += $data->moapr;
            $submay += $data->momay;
            $subjun += $data->mojun;
            $subjul += $data->mojul;
            $subaug += $data->moaug;
            $subsep += $data->mosep;
            $suboct += $data->mooct;
            $subnov += $data->monov;
            $subdec += $data->modec;
            $subamt = $subamt + $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;


            $gsubjan += $data->mojan;
            $gsubfeb += $data->mofeb;
            $gsubmar += $data->momar;
            $gsubapr += $data->moapr;
            $gsubmay += $data->momay;
            $gsubjun += $data->mojun;
            $gsubjul += $data->mojul;
            $gsubaug += $data->moaug;
            $gsubsep += $data->mosep;
            $gsuboct += $data->mooct;
            $gsubnov += $data->monov;
            $gsubdec += $data->modec;
            $gsubamt = $gsubamt + $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

            $totalmojan += $data->mojan;
            $totalmofeb += $data->mofeb;
            $totalmomar += $data->momar;
            $totalmoapr += $data->moapr;
            $totalmomay += $data->momay;
            $totalmojun += $data->mojun;
            $totalmojul += $data->mojul;
            $totalmoaug += $data->moaug;
            $totalmosep += $data->mosep;
            $totalmooct += $data->mooct;
            $totalmonov += $data->monov;
            $totalmodec += $data->modec;
            $totalamt += $amt;


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

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', '310', null, false, $border, 'TLRB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '310', null, false, $border, 'TLRB', 'L', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col($totalmojan == 0 ? '-' : number_format($totalmojan, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmofeb == 0 ? '-' : number_format($totalmofeb, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmomar == 0 ? '-' : number_format($totalmomar, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmoapr == 0 ? '-' : number_format($totalmoapr, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmomay == 0 ? '-' : number_format($totalmomay, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmojun == 0 ? '-' : number_format($totalmojun, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmojul == 0 ? '-' : number_format($totalmojul, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmoaug == 0 ? '-' : number_format($totalmoaug, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmosep == 0 ? '-' : number_format($totalmosep, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmooct == 0 ? '-' : number_format($totalmooct, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmonov == 0 ? '-' : number_format($totalmonov, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmodec == 0 ? '-' : number_format($totalmodec, $ab), '90', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalamt == 0 ? '-' : number_format($totalamt, $ab), '100', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B', '0 2px 0 0', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class