<?php

namespace App\Http\Classes\modules\reportlist\time_and_attendance_report;

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

class late_frequency_report
{
    public $modulename = 'Late Frequency Report';
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
        $fields = ['radioprint',  'year', 'month'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'year.required', true);
        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        data_set($col1, 'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'blue']]);

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
                 left(now(),4) as year, '0' as bmonth,
                 '' as month");
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
        $year = $config['params']['dataparams']['year'];
        $bmonth = $config['params']['dataparams']['bmonth'];

        $query = "select empname,brname,deptname,
                    sum(case when isnologin = isnologout then 1 else 0 end) as tlwholeday,sum(isnologin) as allnologin,sum(isnologout) as allnologout,
                    sum(remain) as halfdays from (
                    select distinct ifnull(cl.clientname,'')  as empname,
                        br.clientname as brname,dept.clientname as deptname,
                        tm.isnologin,tm.isnologout,tm.dateid,
                        case  when (tm.isnologin = 1 and tm.isnologout = 0) or (tm.isnologin = 0 and tm.isnologout = 1) then 1
                        else 0 end as remain
                    from timecard as tm
                    left join client as cl on cl.clientid=tm.empid
                                        left join employee as emp on emp.empid=tm.empid
                                        left join client as br on br.clientid=emp.branchid
                                        left join client as dept on dept.clientid=emp.deptid
                                        where year(tm.dateid)='" . $year . "' and month(tm.dateid)= '" . $bmonth . "'
                                        and  not (tm.isnologin = 0 and tm.isnologout = 0)

                        ) as t
                    group by empname,brname,deptname
                    having sum(isnologin) >= 2 and sum(isnologout) >= 2";
        return $this->coreFunctions->opentable($query);
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

        $month = $config['params']['dataparams']['month'];
        $year = $config['params']['dataparams']['year'];

        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LIST OF EMPLOYEE WITH MORE THAN 3 ABSENCES FOR THE MONTH OF ' . $month . ', ' . $year, null, null, false, $border, '', '', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NAME', '200', null,  $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('BRANCH', '200', null,  $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('DEPARTMENT', '200', null,  $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('FREQUENCY', '200', null,  $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col("REMARKS", '200', null,  $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('', '200', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('', '200', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('(HALF/WHOLE DAY)', '200', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col("", '200', null,  $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
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

        foreach ($result as $key => $data) {

            if ($data->halfdays <> 0) {
                $ff = $data->tlwholeday + $data->halfdays;
                $remainder = $ff % 2;
                $freq = $ff - $remainder;
                $frequency = $freq . ' days & ' . $remainder . ' halfday';
            } else {
                $frequency = $data->tlwholeday;
            }

            //or kapag lahat ng halfday hindi pinag add
            // if ($data->halfdays <> 0) {
            //     $frequency = $data->tlwholeday . ' days & ' . $data->halfdays . ' halfday\'s';
            // } else {
            //     $frequency = $data->tlwholeday;
            // }

            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->empname, '200', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->brname, '200', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->deptname, '200', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($frequency, '200', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->endtable();

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
        // $str .= $this->reporter->printline();
        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class