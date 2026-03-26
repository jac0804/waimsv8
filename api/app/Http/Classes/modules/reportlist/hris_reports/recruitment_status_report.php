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

class recruitment_status_report
{
    public $modulename = 'Recruitment Status Report';
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
        $fields = ['radioprint', 'deptrep', 'start', 'end', 'prepared'];
        $col1 = $this->fieldClass->create($fields);
        data_set(
            $col1,
            'radioprint.options',
            [['label' => 'Default', 'value' => 'default', 'color' => 'red']]
        );

        // data_set($col1, 'start.required', true);
        data_set($col1, 'deptrep.label', 'Department');

        $fields = [];
        $col2 = $this->fieldClass->create($fields);



        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
                'default' as print,
                 adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '' as dept,
                '' as deptname,
                '0' as deptid,
                '' as prepared
                
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
        return $this->one_sky_layout($config);
    }
    public function one_sky_query($config)
    {
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $deptname = $config['params']['dataparams']['deptname'];
        $dept = $config['params']['dataparams']['dept'];
        $filter = "";
        if ($deptname != "") {
            $filter .= " and req.dept = '" . $dept . "' ";
        }

        $query = "select IFNULL(sum(req.headcount-req.qa),0) as openpo,IFNULL(sum(req.qa),0) as filled from hpersonreq as req
        where date(dateid) between '" . $start . "' and '" . $end . "' $filter";
        return $this->coreFunctions->opentable($query);
    }
    public function onesky_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = 'Century Gothic';
        $font_size = '9';
        $deptname = '';
        $prepared = '';
        if ($config['params']['dataparams']['deptname'] != "") {
            $deptname = $config['params']['dataparams']['deptname'];
        }
        if ($config['params']['dataparams']['prepared'] != "") {
            $prepared = $config['params']['dataparams']['prepared'];
        }

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $layoutsize = 1000;
        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Recruitment Status Report', null, null, $border, $border, '', 'C', $font, '18', 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable(750);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('Department: ', 100, null, false, $border, '', 'L', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col($deptname, 400, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('Report Period: ', 100, null, false, $border, '', 'L', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col($start . ' - ' . $end, 400, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('Prepared By : ', 100, null, false, $border, '', 'L', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col($prepared, 400, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('Recruitment Category', 200, null, $bgcolors, $border, 'BLT', 'L', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('No. of Position ', 150, null, $bgcolors, $border, 'BLT', 'C', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('Percentage', 150, null, $bgcolors, $border, 'BLTR', 'C', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '11', 'B', '5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function one_sky_layout($config)
    {
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = 'Century Gothic';
        $font_size = '9';
        $layoutsize = 1000;
        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $str = '';
        $data = $this->one_sky_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->onesky_header($config);

        $open = 0;
        $filled = 0;

        $total = 0;
        $totalavg = 0;
        foreach ($data as $key => $value) {
            $open += number_format($value->openpo, 0);
            $filled += number_format($value->filled, 0);
            $total += ($filled + $open);
        }
        $openpr = 0;
        $filledpr = 0;
        if ($open != 0) {
            $openpr = ($open / $total) * 100;
        }
        if ($filled != 0) {
            $filledpr = ($filled / $total) * 100;
        }

        $totalavg = $filledpr + $openpr;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('Open Position', 200, null, false, $border, 'BL', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col($open, 150, null, false, $border, 'BL', 'C', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($openpr, 0) . '%', 150, null, false, $border, 'BRL', 'C', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('Filled Position', 200, null, false, $border, 'BL', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col($filled, 150, null, false, $border, 'BL', 'C', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($filledpr, 0) . '%', 150, null, false, $border, 'BRL', 'C', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('Total Position', 200, null, false, $border, 'BL', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col(number_format($total, 0), 150, null, false, $border, 'BL', 'C', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col(number_format($totalavg, 0) . '%', 150, null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class