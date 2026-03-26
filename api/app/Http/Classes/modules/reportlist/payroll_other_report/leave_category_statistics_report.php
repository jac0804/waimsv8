<?php

namespace App\Http\Classes\modules\reportlist\payroll_other_report;

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

class leave_category_statistics_report
{
    public $modulename = 'Leave Category Statistics Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
            'default' as print");
    }

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
        $query = "select count(lt.catid) as count,lt.catid,lc.category  from leavetrans as lt
        left join leavecategory as lc ON lc.line = lt.catid 
        where lt.catid <> 0 and lt.status <> 'E' and lc.isinactive = 0 group by lt.catid,lc.category order by lt.catid asc";
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {

        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->col('LEAVE CATEGORY STATISTICS', '500', null, '', $border, '', 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, 12, 'B', '', '');
        $str .= $this->reporter->col('CATEGORY NAME', '300', null, $bgcolors, $border, 'TLR', 'L', $font, 12, 'B', $fontcolor, '');
        $str .= $this->reporter->col('COUNT', '80', null, $bgcolors, $border, 'TR', 'C', $font, 12, 'B', $fontcolor, '');
        $str .= $this->reporter->col('PERCENTAGE', '120', null, $bgcolors, $border, 'TR', 'C', $font, 12, 'B', $fontcolor, '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, 12, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function totalcatused()
    {
        $query = "select count(empid) as totaluse from leavetrans where catid <> 0 and status <> 'E' ";
        return $this->coreFunctions->opentable($query);
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
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $catcount = $this->totalcatused();

        $i = 0;
        $totalpercent = 0;
        $totalcount = 0;
        $percent = 0;
        foreach ($result as $key => $data) {
            $i++;
            $percent = ($data->count / $catcount[0]->totaluse) * 100;
            if (count($result) == $i) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('' . $data->category, '300', null, false, $border, 'TLBR', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('' .  number_format($data->count, 0), '80', null, false, $border, 'TRB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('' .  number_format($percent, 2), '120', null, false, $border, 'TRB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
            } else {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('' . $data->category, '300', null, false, $border, 'LTR', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('' .  number_format($data->count, 0), '80', null, false, $border, 'TRB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('' .  number_format($percent, 2), '120', null, false, $border, 'TR', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
            }
            $totalcount += $data->count;
            $totalpercent += $percent;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', '25', false, $border, '', 'L', $font, $font_size, 'B', '', '25');
        $str .= $this->reporter->col(' TOTAL : ', '300', '25', false, $border, 'LTRB', 'L', $font, $font_size, 'B', '', '25');
        $str .= $this->reporter->col('' .  number_format($totalcount, 0), '80', '25', false, $border, 'TRB', 'C', $font, $font_size, 'B', '', '25');
        $str .= $this->reporter->col('' .  number_format($totalpercent, 0), '120', '25', false, $border, 'TRB', 'C', $font, $font_size, 'B', '', '25');
        $str .= $this->reporter->col('', '150', '25', false, $border, '', 'L', $font, $font_size, 'B', '', '25');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class