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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class sss_phip_hdmf_remittance_report
{
    public $modulename = 'SSS/PHIP/HDMF Remittance Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'tpaygroup'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        // for department lookup
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');

        $fields = ['start', 'end'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        date(now()) as end, 
        0 clientid, '' client, '' as clientname, '' as dclientname,
        0 as divid, '' as divname,
        0 as deptid, '' as deptname,
        0 as sectid, '' as sectname,
        0 as paygroupid, '' as tpaygroup
      ";
        return $this->coreFunctions->opentable($paramstr);
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        return $this->report_default_layout($config);
        break;
    }

    // QUERY
    public function default_qry($config)
    {
        // $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $center = ($config['params']['center']);
        $divname = ($config['params']['dataparams']['divname']);
        $divid = ($config['params']['dataparams']['divid']);
        $deptid = ($config['params']['dataparams']['deptid']);
        $deptname = ($config['params']['dataparams']['deptname']);
        $sectid = ($config['params']['dataparams']['sectid']);
        $sectname = ($config['params']['dataparams']['sectname']);
        $paygroupid = ($config['params']['dataparams']['paygroupid']);
        $tpaygroup = ($config['params']['dataparams']['tpaygroup']);

        $filter = '';
        $leftjoin = '';
        $leftjoin2 = '';
        $leftjoin3 = '';

        if (
            $divname != ''
        ) {
            $leftjoin3  .= " left join division on division.divcode = e.division";
            $leftjoin2 .= " left join division on division.divid = e.division";
            $filter .= " and division.divid = $divid";
        }

        if ($deptname != '') {
            $leftjoin .= " left join department on department.deptcode = e.dept";
            $filter .= " and e.deptid = $deptid";
        }

        if ($sectname != '') {
            $leftjoin .= " left join section on section.sectid = e.sectid";
            $filter .= " and e.sectid = $sectid";
        }

        if ($tpaygroup != '') {
            $filter .= " and e.emptype = $paygroupid";
        }

        $query = "select e.empfirst, e.emplast, LEFT(e.empmiddle,1) as mi, e.empid,
        e.sss  as sssno, e.phic as phicno, e.hdmf as hdmfno,
        sum(case when pa.code = 'PT44' then p.cr else 0 end) as sss,
        sum(case when pa.code = 'PT48' then p.cr else 0 end) as phic,
        sum(case when pa.code = 'PT51' then p.cr else 0 end) as hdmf
        from paytrancurrent as p
        left join employee as e ON e.empid = p.empid
        left join paccount as pa ON pa.line = p.acnoid
        left join batch as b on b.line = p.batchid
        $leftjoin3
        $leftjoin
        where pa.code IN ('PT44','PT48','PT51') 
        and p.dateid between '$start' and '$end'
        $filter
        group by e.empid, e.empfirst, e.emplast, e.empmiddle, e.sss, e.phic, e.hdmf

        union all

        select e.empfirst, e.emplast, left(e.empmiddle,1) as mi, e.empid,
        e.sss  as sssno, e.phic as phicno, e.hdmf as hdmfno,
        sum(case when pa.code = 'PT44' then p.cr else 0 end) as sss,
        sum(case when pa.code = 'PT48' then p.cr else 0 end) as phic,
        sum(case when pa.code = 'PT51' then p.cr else 0 end) as hdmf
        FROM paytranhistory as p
        left join employee as e ON e.empid = p.empid
        left join paccount as pa ON pa.line = p.acnoid
        left join batch as b on b.line = p.batchid
        $leftjoin2
        $leftjoin
        where pa.code in ('PT44','PT48','PT51') 
        and p.dateid between '$start' and '$end'
        $filter
        group by e.empid, e.empfirst, e.emplast, e.empmiddle, e.sss, e.phic, e.hdmf
        ";
        // var_dump($query);
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
    }

    public function report_default_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = ($config['params']['dataparams']['start']);
        $end = ($config['params']['dataparams']['end']);
        $divname = ($config['params']['dataparams']['divname']);
        $deptname = ($config['params']['dataparams']['deptname']);
        $sectname = ($config['params']['dataparams']['sectname']);
        // $type = ($config['params']['dataparams']['type']);

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $start = date('j-F-Y');
        $end = date('j-F-Y');
        $reportdate = date('l, F j, Y');
        $reporttime = date('h:i:s A');

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '20', false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SSS/PHIP/HDMF Remittance Report', null, null, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date From: ' . $start . ' to ' . $end, null, null, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '20', false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($divname != '') {
            $str .= $this->reporter->col('<b>Division: &nbsp&nbsp&nbsp</b>' . strtoupper($divname), 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Division: &nbsp&nbsp&nbsp</b>' . 'ALL DIVISION', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        if ($deptname != '') {
            $str .= $this->reporter->col('<b>Department: &nbsp&nbsp&nbsp</b>' . strtoupper($deptname), 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Department: &nbsp&nbsp&nbsp</b>' . 'ALL DEPARTMENT', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        if ($sectname != '') {
            $str .= $this->reporter->col('<b>Section: &nbsp&nbsp&nbsp</b>' . strtoupper($sectname), 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Section: &nbsp&nbsp&nbsp</b>' . 'ALL SECTION', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($sectname != '') {
            $str .= $this->reporter->col('<b>Pay Group: &nbsp&nbsp&nbsp</b>' . strtoupper($sectname), 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Pay Group: &nbsp&nbsp&nbsp</b>' . 'All PAY GROUP', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '20', false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'B', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'B', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Name', '250', null, false, '2px solid', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SSS No.', '125', null, false, '2px solid', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PHIC No.', '125', null, false, '2px solid', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HDMF No.', '125', null, false, '2px solid', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SSS', '125', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PHIC', '125', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HDMF', '125', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function report_default_layout($config)
    {

        $result = $this->default_qry($config);

        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        // $font = $this->companysetup->getrptfont($config['params']);
        // $font='Courier New';
        $fontsize = "10";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $count = count($result);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // Grand totals
        $grandSss   = 0;
        $grandPhic = 0;
        $grandHdmf = 0;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->report_default_header($config);

        foreach ($result as $data) {
            $fullname = trim($data->emplast . ', ' . $data->empfirst . ' ' . $data->mi . '. ');

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp&nbsp' . $fullname, '250', null, true, '2px dotted', 'B', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
            $str .= $this->reporter->col(!empty($data->sssno)  ? $data->sssno  : '-', '125', null, true, '2px dotted', 'B', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
            $str .= $this->reporter->col(!empty($data->phicno) ? $data->phicno : '-', '125', null, true, '2px dotted', 'B', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
            $str .= $this->reporter->col(!empty($data->hdmfno) ? $data->hdmfno : '-', '125', null, true, '2px dotted', 'B', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
            $str .= $this->reporter->col($data->sss  != 0 ? number_format($data->sss,  2) : '-', '125', null, true, '2px dotted', 'B', 'RT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
            $str .= $this->reporter->col($data->phic != 0 ? number_format($data->phic, 2) : '-', '125', null, true, '2px dotted', 'B', 'RT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
            $str .= $this->reporter->col($data->hdmf != 0 ? number_format($data->hdmf, 2) : '-', '125', null, true, '2px dotted', 'B', 'RT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandSss  += $data->sss;
            $grandPhic += $data->phic;
            $grandHdmf += $data->hdmf;
        }

        // Undefined Employee Gender Row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $grandTotal += $undefined['total'];

        // Grand Total Row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '250', null, false, '', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Grand Total',      '125', null, false, '', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',   '125', null, false, '', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '125', null, false, '', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandSss, '125', null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandPhic,  '125', null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandHdmf, '125', null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport($layoutsize);

        return $str;
    }
}//end class