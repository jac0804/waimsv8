<?php

namespace App\Http\Classes\modules\reportlist\payroll_portal_reports;

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

class undertime_reports
{
    public $modulename = 'Undertime Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1370'];

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
        $fields = ['radioprint',  'divname', 'dclientname', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.label', 'Date');
        data_set($col1, 'dateid.readonly', false);
        data_set($col1, 'divname.type', 'lookup');
        data_set($col1, 'divname.lookupclass', 'lookupempdivision');
        data_set($col1, 'divname.action', 'lookupempdivision');
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        // data_set($col1, 'radioprint.options', [
        //     ['label' => 'PDF', 'value' => 'default', 'color' => 'red'],
        // ]);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];

        return $this->coreFunctions->opentable(
            "select 
                'default' as print,
                adddate(left(now(), 10),-360) as start,
                left(now(),10) as end,
                0 as divid,
                    '' as client,
                    '' as clientname,
                    '' as dclientname"

        );
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
        $result = $this->reportDefaultLayout_ob($config);
        return $result;
    }

    public function reportDefault($config)
    {

        $query = $this->undertime_query($config);

        return $this->coreFunctions->opentable($query);
    }

    public function undertime_query($config)
    {
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $divid = $config['params']['dataparams']['divid'];
        $client     = $config['params']['dataparams']['client'];
        $userid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $filter = "";
        $status = "";
        if ($divid != 0) {
            $filter .= " and e.divid = " . $divid . "";
        }
        if ($client != "") {
            $filter .= " and client.client = '$client' ";
        }

        $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
        $data = app($url)->approvers($config['params']);
        foreach ($data as $key => $value) {
            if (count($data) > 1) {
                $status = " u.status = 'A' and  u.status2 = 'A' and ";
                break;
            } else {
                if (count($data) == 1) {
                    $status = " u.status = 'A' and ";
                    break;
                }
            }
        }
        $query = "select u.empid,concat(upper(e.emplast), ', ', e.empfirst, ' ', left(e.empmiddle, 1), '.') as employee,cl.client as empcode,u.rem as reason,
date(u.createdate) as createdate,u.dateid,date_format(u.dateid, '%Y-%m-%d %H:%i') as dateid,
date_format(u.dateid, '%H:%i') as time,time(t.schedout) as schedtime,timestampdiff(hour, u.dateid, t.schedout) as hours,
u.approvedby,date(u.approvedate) as approvedate,u.approverem
from undertime as u
left join employee as e on e.empid = u.empid
left join division as d on d.divcode = e.division
left join client as cl on cl.clientid = e.empid
left join timecard as t on t.empid = u.empid and date(t.dateid) = date(u.dateid)
where $status date(u.dateid) between '" . $start . "' and '" . $end . "' $filter";
        return $query;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = $this->reportParams['layoutSize'];
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('UNDERTIME REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range Date : ' . $start . 'to ' . $end, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = $this->reportParams['layoutSize'];

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Field.', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date of Undertime', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Time Covered', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Number of Hours', '130', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reason', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Date', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Remarks', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout_ob($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = $this->reportParams['layoutSize'];
        $stime = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col($data->empcode, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->employee, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->createdate, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dateid, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->time, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->hours, '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->reason, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedate, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approverem, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class