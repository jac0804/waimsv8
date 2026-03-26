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

class loan_summary_report
{
    public $modulename = 'Loan Summary Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => 1200];

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
        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Division');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');

        $fields = ['dloantype'];
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
    '' as code,
    '' as codename,
    '' as dloantype,
    '' as codename,
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    '' as deptrep,
    '' as sectrep,
    '' as sectname,
    '' as sectid,
    '' as month,
    '' as year
    ");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        return $this->reportDefaultLayout($config);
    }

    public function reportDefault($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $code = $config['params']['dataparams']['code'];
        $divid      = $config['params']['dataparams']['divid'];
        $deptid     = $config['params']['dataparams']['deptid'];
        $sectid     = $config['params']['dataparams']['sectid'];
        $emplvl = $this->othersClass->checksecuritylevel($config);
        $month = intval($config['params']['dataparams']['month']);
        $year = intval($config['params']['dataparams']['year']);

        $filter = '';
        if ($code != '') $filter = "and pa.code = '$code'";
        if ($deptid != 0) $filter .= " and emp.deptid = $deptid";
        if ($divid != 0) $filter .= " and emp.divid = $divid";
        if ($sectid != 0) $filter .= " and emp.sectid = $sectid";

        $query = "select pa.code, pa.codename, concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle) as empname,ss.dateid,
            ss.docno, ss.amt,ss.balance, ss.effdate,client.client
            from standardsetup as ss
            left join client on client.clientid = ss.empid
            left join employee as emp on ss.empid = emp.empid
            left join paccount as pa on pa.line = ss.acnoid
            where emp.level in $emplvl $filter
            order by pa.code, ss.docno";
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $loantype = $config['params']['dataparams']['codename'];
        $divid      = $config['params']['dataparams']['divname'];
        $deptid     = $config['params']['dataparams']['deptname'];
        $sectid     = $config['params']['dataparams']['sectname'];

        $str = '';
        $layoutsize = '1000';
        $border = '1px solid';
        $font = 'Tahoma';
        $font_size = '10';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOAN SUMMARY REPORT', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';


        //loan type 
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($loantype != '') {
            $str .= $this->reporter->col('Loan Type: ' . $loantype, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Loan Type: All LOAN', '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        }
        if ($divid != '') {
            $str .= $this->reporter->col('Division: ' . $divid, '300', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Division: All DIVISION', '300', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        }
        if ($deptid != '') {
            $str .= $this->reporter->col('Department: ' . $deptid, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Department: All DEPARTMENT', '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        }
        if ($sectid != '') {
            $str .= $this->reporter->col('Section: ' . $sectid, '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Section: All SECTION', '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Code', '120', null, false, $border, 'TBL', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '180', null, false, $border, 'LTB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Doc No.', '120', null, false, $border, 'LTB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Date', '70', null, false, $border, 'LTB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Effectivity of Deduction', '130', null, false, $border, 'LTB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Loan Type', '100', null, false, $border, 'LTB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Beggining Balance', '140', null, false, $border, 'LTB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Running Balance', '140', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $emplvl = $this->othersClass->checksecuritylevel($config);
        $code = $config['params']['dataparams']['code'];
        $divid = $config['params']['dataparams']['divid'];
        $deptid = $config['params']['dataparams']['deptid'];
        $sectid = $config['params']['dataparams']['sectid'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "9";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->displayHeader($config);


        foreach ($result as $data) {

            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->client, '120', null, false, $border, 'BL', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data->empname, '180', null, false, $border, 'BL', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data->docno, '120', null, false, $border, 'BL', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($data->dateid)), '70', null, false, $border, 'BL', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($data->effdate)), '130', null, false, $border, 'BL', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data->codename, '100', null, false, $border, 'BL', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(number_format($data->amt, 2), '140', null, false, $border, 'BL', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(number_format($data->balance, 2), '140', null, false, $border, 'BLR', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $str .= $this->reporter->endrow();
                $page = $page + $count;
            }
        }

        return $str;
    }
}
