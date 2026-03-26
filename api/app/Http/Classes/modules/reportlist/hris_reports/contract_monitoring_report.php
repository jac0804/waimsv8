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

class contract_monitoring_report
{
    public $modulename = 'Contract Monitoring Report';
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
        $fields = ['radioprint', 'dbranchname', 'divrep', 'deptrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.lookupclass', 'lookupdivpayslip');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');
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
            left(now(),10) as `end`,
            '' as dbranchname,
            '' as branchcode,
            '' as branchid,
            '' as divid,
            '' as divname,
            '' as divrep,
            '' as deptid,
            '' as deptname,
            '' as deptrep");
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

        $branch = $config['params']['dataparams']['dbranchname'];
        $branchid = $config['params']['dataparams']['branchid'];
        $division = $config['params']['dataparams']['divrep'];
        $divid = $config['params']['dataparams']['divid'];
        $department = $config['params']['dataparams']['deptrep'];
        $deptid = $config['params']['dataparams']['deptid'];

        if ($branch != "") {
            $filter .= " and emp.branchid=" . $branchid;
        }
        if ($division != "") {
            $filter .= " and emp.divid=" . $divid;
        }
        if ($department != "") {
            $filter .= " and emp.deptid=" . $deptid;
        }

        $query = "select 'P' as stat,client.clientname, date(p.expiration) as expiration, DATEDIFF(now(), emp.hired) as numdays, 
            br.clientname as brname, dv.divname, dept.clientname as deptname, job.jobtitle,
            p.regid, p.empid, date(emp.hired) as hired, p.line, reg.description, reg.sortline
            from regprocess as p 
            left join client on client.clientid=p.empid 
            left join employee as emp on emp.empid=client.clientid
            join regularization as reg on reg.line=p.regid
            left join client as br on br.clientid=emp.branchid
            left join division as dv on dv.divid=emp.divid
            left join client as dept on dept.clientid=emp.deptid
            left join jobthead as job on job.line=emp.jobid
            where p.evaluated is null and DATEDIFF(now(), emp.hired) <= 180 $filter
            union all
            select 'X' as stat,client.clientname, date(p.expiration) as expiration, DATEDIFF(now(), emp.hired) as numdays, 
            br.clientname as brname, dv.divname, dept.clientname as deptname, job.jobtitle,
            p.regid, p.empid, date(emp.hired) as hired, p.line, 'EXPIRED CONTRACT' as description, reg.sortline
            from regprocess as p 
            left join client on client.clientid=p.empid 
            left join employee as emp on emp.empid=client.clientid
            join regularization as reg on reg.line=p.regid
            left join client as br on br.clientid=emp.branchid
            left join division as dv on dv.divid=emp.divid
            left join client as dept on dept.clientid=emp.deptid
            left join jobthead as job on job.line=emp.jobid
            where p.evaluated is null and DATEDIFF(now(), emp.hired) > 180 $filter
            order by stat, sortline, numdays";

        return $this->coreFunctions->opentable($query);
    }

    public function reportLayout($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $result = $this->reportDefault($config);
        $str = '';
        $layoutsize = 1000;
        $fontsize = '10';
        $font = $this->companysetup->getrptfont($config['params']);
        $border = '1px solid';

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CONTRACT MONITORING REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevProcess = '';
        $counter = 1;

        $blnExpired = false;

        $fontcolor = '#000000'; //white
        $bgcolors = '#FFFFFF'; //black

        foreach ($result as $key => $value) {

            if ($value->stat == 'X') {
                $blnExpired = true;

                $fontcolor = '#FFFFFF';
                $bgcolors = '#FF0000';
            }

            if ($prevProcess == '') {
                PrintDescHere:
                $str .= '<br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($value->description, null, null, false, $border, '', '', $font, $fontsize + 4, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('NO', 50, null, $bgcolors, $border, 'LTB', 'C', $font, $fontsize, 'B', $fontcolor, '5px');
                $str .= $this->reporter->col('EMPLOYEE NAME', 400, null, $bgcolors, $border, 'LTB', '', $font, $fontsize, 'B', $fontcolor, '5px');
                $str .= $this->reporter->col('JOB DESCRIPTION', 200, null, $bgcolors, $border, 'LTB', '', $font, $fontsize, 'B', $fontcolor, '5px');
                $str .= $this->reporter->col('HIRED DATE', 100, null, $bgcolors, $border, 'LTBR', 'C', $font, $fontsize, 'B', $fontcolor, '5px');
                $str .= $this->reporter->col('NO OF DAYS', 100, null, $bgcolors, $border, 'TBR', 'C', $font, $fontsize, 'B', $fontcolor, '5px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            } else {
                if ($prevProcess != $value->description) {
                    $counter = 1;
                    goto PrintDescHere;
                }
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($counter . '.', 50, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($value->clientname, 400, null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($value->jobtitle, 200, null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($value->hired, 100, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($value->numdays, 100, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $prevProcess = $value->description;
            $counter += 1;
        }

        if (!$blnExpired) {
            $str .= '<br/><br/>';
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('EXPIRED CONTRACT', null, null, false, $border, '', '', $font, $fontsize + 4, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $fontcolor = '#FFFFFF';
            $bgcolors = '#FF0000';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('NO', 50, null, $bgcolors, $border, 'LTB', 'C', $font, $fontsize, 'B', $fontcolor, '5px');
            $str .= $this->reporter->col('EMPLOYEE NAME', 400, null, $bgcolors, $border, 'LTB', '', $font, $fontsize, 'B', $fontcolor, '5px');
            $str .= $this->reporter->col('JOB DESCRIPTION', 200, null, $bgcolors, $border, 'LTB', '', $font, $fontsize, 'B', $fontcolor, '5px');
            $str .= $this->reporter->col('HIRED DATE', 100, null, $bgcolors, $border, 'LTBR', 'C', $font, $fontsize, 'B', $fontcolor, '5px');
            $str .= $this->reporter->col('NO OF DAYS', 100, null, $bgcolors, $border, 'TBR', 'C', $font, $fontsize, 'B', $fontcolor, '5px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        return $str;
    }
}//end class