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

class reason_of_leaving_the_company
{
    public $modulename = 'Reason of Leaving the Company';
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
        $fields = ['radioprint', 'divrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
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
              '' as division");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportLayout($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function reportDefault($config)
    {
        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }

        $query = "select resignedtype,count(empid) as totalemp 
                  from (select emp.divid,d.divname as company,emp.resignedtype,emp.empid
                        from employee as emp
                        left join division as d on d.divid=emp.divid
                        where emp.resignedtype <> '' $filter) as a
                  group by resignedtype order by resignedtype";

        return $this->coreFunctions->opentable($query);
    }

    private function headerlayout($config)
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
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Reason of Leaving the Company', '500', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
        $str .= $this->reporter->col('Reason of Leaving', '450', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        $str .= $this->reporter->col('No. of Employee', '150', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportLayout($config)
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
        $str .= $this->headerlayout($config);

        foreach ($result as $key => $data) {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->resignedtype, '400', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->totalemp, '150', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->headerlayout($config);
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