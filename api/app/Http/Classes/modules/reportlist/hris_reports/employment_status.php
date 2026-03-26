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

class employment_status
{
    public $modulename = 'Employment Status';
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
        $fields = ['radioprint', 'divrep', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'red']]);

        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'divrep.label', 'Company');

        $fields = ['dclientname', 'resigned'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col2, 'dclientname.label', 'Employee');

        data_set($col2, 'resigned.type', 'lookup');
        data_set($col2, 'resigned.lookupclass', 'lookupresigned');
        data_set($col2, 'resigned.action', 'lookupresigned');
        data_set($col2, 'resigned.readonly', true);
        data_set($col2, 'resigned.class', 'csresigned sbccsreadonly');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
                'default' as print,
                '0' as clientid,
                '' as client,
                '' as clientname,
                '' as dclientname,
                adddate(left(now(),10),-360) as start,
                    left(now(),10) as `end`,
                    '' as resigned,
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
        return $this->CDOHRIS_Layout($config);
    }
    public function CDOHRIS_QRY($config)
    {
        // QUERY
        $client     = $config['params']['dataparams']['clientid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $divid     = $config['params']['dataparams']['divid'];
        $resigned     = $config['params']['dataparams']['resigned'];

        $filter   = "";

        if ($client != 0) {
            $filter .= " and clr.empid = '$client'";
        }
        if ($resigned != "") {
            $filter .= " and emp.resignedtype = '$resigned'";
        }
        if ($divid != 0) {
            $filter .= " and emp.divid = '$divid'";
        }

        $query = "select concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as employee,
           jt.jobtitle,clr.resignedtype as empstatus,date(clr.dateid) as dateid,branch.clientname as branch
           from clearance as clr 
           left join employee as emp on emp.empid = clr.empid
           left join jobthead as jt on jt.line = emp.jobid
           left join client as branch on branch.clientid = emp.branchid
           where clr.doc = 'HC' and date(clr.dateid) between '$start' and '$end' $filter
           union all
           select concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as employee,
           jt.jobtitle,clr.resignedtype as empstatus,date(clr.dateid) as dateid,branch.clientname as branch
           from hclearance as clr 
           left join employee as emp on emp.empid = clr.empid
           left join jobthead as jt on jt.line = emp.jobid
           left join client as branch on branch.clientid = emp.branchid
           where clr.doc = 'HC' and date(clr.dateid) between '$start' and '$end' $filter 
           order by dateid ";

        return $this->coreFunctions->opentable($query);
    }

    private function CDOHRIS_header($config)
    {

        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = '11';
        $layoutsize = '1000';
        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $division     = $config['params']['dataparams']['division'];
        $companyid   = $config['params']['companyid'];
        $str = '';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $logo = "";
        // if ($companyid == 58) { // cdohris
        //     $logo = "";
        //     switch ($division) {
        //         case '001':
        //             $logo = URL::to('/images/cdohris/logocdo2cycles.jpg');
        //             break;
        //         case '002':
        //             $logo = URL::to('/images/cdohris/logombc.jpg');
        //             break;
        //         case '003':
        //             $logo = URL::to('/images/cdohris/logoridefund.png');
        //             break;
        //     }

        //     if ($logo != "") {
        //         $str .= '<div style="position: relative;">';
        //         $str .= "<div style='position:absolute; margin:-150px 0 0 0'>";

        //         $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mbc" width="200px" height ="200px">', '250', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //         $str .= "</div>";
        //         $str .= "</div>";
        //     }
        // }

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYMENT STATUS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();;
        $str .= $this->reporter->col('EMPLOYEE NAME', '300', null, $bgcolors, $border, 'TBL', 'L', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('COMPANY BRANCH', '200', null, $bgcolors, $border, 'TBL', 'L', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('DESIGNATION', '200', null, $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('TRANS-DATE', '150', null, $bgcolors, $border, 'TBL', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('EMP-STATUS', '150', null, $bgcolors, $border, 'TBLR', 'L', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        return $str;
    }
    public function CDOHRIS_Layout($config)
    {
        $result = $this->CDOHRIS_QRY($config);

        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';
        $count = 6; //2
        $page = 5; //1
        $str = '';
        $layoutsize = '1000';
        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->CDOHRIS_header($config);
        $str .= $this->reporter->begintable($layoutsize);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('' . $data->employee, '300', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $data->branch, '200', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $data->jobtitle, '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $data->dateid, '150', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $data->empstatus, '150', null, false, $border, 'LBR', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();


        return $str;
    }
}//end class