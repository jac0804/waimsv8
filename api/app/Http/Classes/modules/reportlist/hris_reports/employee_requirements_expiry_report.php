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

class employee_requirements_expiry_report
{
    public $modulename = 'Employee Requirements Expiry Report';
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
        $fields = ['radioprint', 'start', 'end', 'empreq', 'tpaygroup'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'tpaygroup.label', 'Paygroup');

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
        '' as tpaygroup,
        0 as paygroupid,
        '' as empreq,
        '' as reqcode,
        0 as reqline,
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
        $leftjoin = "";
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end   = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $dept   = $config['params']['dataparams']['dept'];
        $tpaygroup = $config['params']['dataparams']['tpaygroup'];
        $paygroupid = $config['params']['dataparams']['paygroupid'];
        $reqcode = $config['params']['dataparams']['reqcode'];
        $reqline = $config['params']['dataparams']['reqline'];
        $empreq = $config['params']['dataparams']['empreq'];

        if ($empreq != "") {
            $filter .= " and e.reqid = '$reqline'";
        }
        if ($tpaygroup != "") {
            $leftjoin = "left join paygroup as p on p.line = emp.paygroup";
            $filter .= " and emp.paygroup = $paygroupid";
        }

        $query = "select e.expiry, er.code, e.reqs, e.irno, c.client as empcode, c.clientname
        from employee as emp
        left join client as c on c.clientid = emp.empid
        left join erequire as e on e.empid = emp.empid
        left join emprequire as er on er.line = e.reqid
        $leftjoin
        where date(e.expiry) between '$start' and '$end' and emp.isactive = 1 and emp.resigned is null $filter
        order by clientname;";

        return $this->coreFunctions->opentable($query);
    }

    public function defaultHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m-d-Y', strtotime($config['params']['dataparams']['start']));
        $end   = date('m-d-Y', strtotime($config['params']['dataparams']['end']));
        $tpaygroup = $config['params']['dataparams']['tpaygroup'];
        $paygroupid = $config['params']['dataparams']['paygroupid'];
        $reqcode = $config['params']['dataparams']['reqcode'];
        $reqline = $config['params']['dataparams']['reqline'];
        $empreq = $config['params']['dataparams']['empreq'];

        $paygroup = "";
        $ereq = "";

        if ($tpaygroup !== "") {
            $paygroup = $tpaygroup;
        }else {
            $paygroup = "";
        }

        if ($empreq !== "") {
            $ereq = $empreq;
        }else {
            $ereq = "";
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
        $str .= $this->reporter->col('EMPLOYEE REQUIREMENTS EXPIRY REPORT', null, null, false, '3px solid', '', 'C', $font, '12', 'B', 'blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From: ' . '<b>' . $start . '</b> to <b>' . $end . '</b>', null, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Requirement: ' . '<b>' . $ereq . '</b>', null, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Paygroup: ' . '<b>' . $paygroup . '</b>', null, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, true, '2px solid', '', 'L', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->col('', null, 20, true, '2px solid', '', 'C', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->col('', null, 20, true, '2px solid', '', 'R', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->col('', null, 20, true, '2px solid', '', 'R', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, true, '2px solid', 'T', 'L', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->col('', null, 20, true, '2px solid', 'T', 'C', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->col('', null, 20, true, '2px solid', 'T', 'R', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->col('', null, 20, true, '2px solid', 'T', 'R', $font, '10', '', '', '', '', '', '', '', '', '#e8e9eb');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EXPIRY', 100, null, false, '3px solid', '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('REQUIREMENT CODE', 160, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('REQUIREMENT', 160, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('NO', 120, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('EMP CODE', 160, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('EMP NAME', 300, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'T', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'T', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'T', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'T', 'R', $font, '10', '', '', '');
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

        $empcount = 0;

        foreach ($result as $row) {

            // check if line limit is reached
            if ($linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->defaultHeader($config);
                $page += $count; // move the next page threshold
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date('m/d/Y', strtotime($row->expiry)), 100, null, false, '3px solid', '', 'CT', $font, '10', '', '', '');
            $str .= $this->reporter->col($row->code, 160, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
            $str .= $this->reporter->col($row->reqs, 160, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
            $str .= $this->reporter->col($row->irno, 120, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
            $str .= $this->reporter->col($row->empcode, 160, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
            $str .= $this->reporter->col($row->clientname, 300, null, false, '3px solid', '', 'LT', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $linecounter++; // increment after each row
            $empcount++;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '3px solid', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NO. OF EMPLOYEES: ' . $empcount, null, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class