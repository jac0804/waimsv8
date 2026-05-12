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

class end_of_contract_report
{
    public $modulename = 'End of Contract Report';
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
        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep','start', 'end'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.label', 'Department');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $currentDate = $this->othersClass->getCurrentDate();
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate('" . $currentDate . "', -360) as start,
        ' " . $currentDate . " ' as end,
        '' as dept,
        0 as deptid,
        '' as deptname,
        0 as divid,
        '' as dovision,
        '' as divname,
        0 as sectid,
        '' as sectname");
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


    public function defaultqry($config)
    {
        $filter   = "";
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end   = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $dept   = $config['params']['dataparams']['dept'];
        $deptid   = $config['params']['dataparams']['deptid'];
        $deptname = $config['params']['dataparams']['deptname'];
        $divid    = $config['params']['dataparams']['divid'];
        $divname  = $config['params']['dataparams']['divname'];
        $sectid   = $config['params']['dataparams']['sectid'];
        $sectname = $config['params']['dataparams']['sectname'];

        if ($divname != '') {
            $filter = " and e.divid = '$divid'";
        }
        if ($deptname != "") {
            $filter .= " and e.deptid = $deptid";
        }
        if ($sectname != "") {
            $filter .= " and e.sectid = $sectid";
        }

        $query = "select concat(e.emplast, ', ', e.empfirst, ' ', left(e.empmiddle, 1), '.') as employee, c.descr, c.datefrom, c.dateto
        from contracts as c
        left join employee as e on e.empid = c.empid
        left join division as d on d.divcode = e.division
        left join section as s on s.sectcode = e.orgsection
        left join department as dept on dept.deptcode = e.dept
        where date(c.datefrom) >= '$start' and date(c.dateto) <= '$end' $filter";

        return $this->coreFunctions->opentable($query);
    }

    public function defaultHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m-d-Y', strtotime($config['params']['dataparams']['start']));
        $end   = date('m-d-Y', strtotime($config['params']['dataparams']['end']));
        $dept   = $config['params']['dataparams']['dept'];
        $deptid   = $config['params']['dataparams']['deptid'];
        $deptname = $config['params']['dataparams']['deptname'];
        $divid    = $config['params']['dataparams']['divid'];
        $divname  = $config['params']['dataparams']['divname'];
        $sectid   = $config['params']['dataparams']['sectid'];
        $sectname = $config['params']['dataparams']['sectname'];

        $department = "";
        $division = "";
        $section = "";

        if ($deptname !== "") {
            $department = $deptname;
        }else {
            $department = "All Departments";
        }

        if ($divname !== "") {
            $division = $divname;
        }else {
            $division = "All Company";
        }

        if ($sectname !== "") {
            $section = $sectname;
        }else {
            $section = "All Sections";
        }

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "12";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, $border, '', 'LB', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('END OF CONTRACT REPORT', null, null, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Division: ' . '<b>' . $division . '</b>' . ' ' . 'Department: ' . '<b>' . $department . '</b>' . ' ' . 'Section: ' . '<b>' . $section . '</b>', null, null, false, '3px solid', '', 'CT', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From: ' . '<b>' . $start . '</b> to <b>' . $end . '</b>', null, 30, false, '3px solid', '', 'CT', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 25, false, '2px solid', 'T', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, 25, false, '2px solid', 'T', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, 25, false, '2px solid', 'T', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, 25, false, '2px solid', 'T', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME', 300, null, false, '3px solid', 'T', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('DESCRIPTION', 300, null, false, '3px solid', 'T', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('START DATE', 200, null, false, '3px solid', 'T', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('END DATE', 200, null, false, '3px solid', 'T', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '2px dashed', 'T', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '2px dashed', 'T', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '2px dashed', 'T', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '2px dashed', 'T', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportLayout($config)
    {
        $result = $this->defaultqry($config);
        $border = '1px solid';
        $font = 'Tahoma';
        $fontsize = 10;
        $count = 55;
        $page = 55;
        $str = '';
        $linecounter = 0; // add this

        $layoutsize = 1000;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->defaultHeader($config);

        foreach ($result as $row) {

            // check if line limit is reached
            if ($linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->defaultHeader($config);
                $page += $count; // move the next page threshold
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($row->employee), 300, null, false, '1px solid', '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col($row->descr, 300, null, false, '1px solid', '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($row->datefrom)), 200, null, false, '1px solid', '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($row->dateto)), 200, null, false, '1px solid', '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $linecounter++; // increment after each row
        }

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class