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

class ot_filling_reports
{
    public $modulename = 'Overtime Filling Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1500'];

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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint',  'start', 'end', 'divname', 'dclientname', 'emploc', 'radioposttype', 'radiosortby'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioposttype.options', [
            ['label' => 'ENTRY', 'value' => 'entry', 'color' => 'red'],
            ['label' => 'APPROVED', 'value' => 'approved', 'color' => 'red']
        ]);
        data_set($col1, 'dateid.label', 'Date');
        data_set($col1, 'dateid.readonly', false);
        data_set($col1, 'divname.type', 'lookup');
        data_set($col1, 'divname.lookupclass', 'lookupempdivision');
        data_set($col1, 'divname.action', 'lookupempdivision');
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');

        data_set($col1, 'emploc.type', 'lookup');
        data_set($col1, 'emploc.lookupclass', 'lookupemplocation');
        data_set($col1, 'emploc.action', 'lookupemplocation');
        // lookupemplocation

        data_set($col1, 'radiosortby.label', 'Paymode');

        data_set($col1, 'radiosortby.options', [
            ['label' => 'Weekly', 'value' => 'W', 'color' => 'red'],
            ['label' => 'Semi-Monthly', 'value' => 'S', 'color' => 'red']
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);



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
                '0' as divid,
                '' as divname,
                '' as division,
                '' as client,
                '' as clientname,
                '' as dclientname,
                'S' as sortby,
                '' as emploc,
                'approved' as posttype"

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
        switch ($config['params']['companyid']) {
            case 44: // stone pro
                $result =  $this->report_stonepro_Layout_ot($config);
                break;
            case 53: // CAMERA SOUND
                $this->reportParams['orientation'] = 'l';
                $result =  $this->report_camera_Layout_ot($config);
                break;
            case 51: // ulitc
                $result =  $this->report_ulitc_Layout_ot($config);
                break;
            default:
                $result = $this->reportDefaultLayout_ot($config);
                break;
        }
        return $result;
    }

    public function reportDefault($config)
    {
        switch ($config['params']['companyid']) {
            case 44: // strone pro
            case 53: // CAMERA SOUND
            case 51: // ulitc
                $query =  $this->ot_advance_QUERY($config);
                break;

            default:
                $query = $this->ot_QUERY($config);
                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    public function ot_QUERY($config)
    {
        $asof2    = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $userid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $status = "";
        switch ($companyid) {
            case '44': //stonepro
            case '53': //camera
                $otapplication = 'otapplicationadv';
                break;
            default:
                $otapplication = 'otapplication';
                break;
        }
        $url = 'App\Http\Classes\modules\payroll\\' . $otapplication;
        $data = app($url)->approvers($config['params']);
        foreach ($data as $key => $value) {
            if (count($data) > 1) {
                $status = " tm.otstatus = 2 and tm.otstatus2 = 2 and";
                break;
            } else {
                if (count($data) == 1) {
                    $status = " tm.otstatus = 2 and ";
                    break;
                }
            }
        }
        $query = "
                        select '' as number, cl.client, cl.clientname,tm.dateid as shiftdate, tm.entryot as hrsfilled,
                        case
                                when tm.otstatus = 0 then 'PENDING'
                                when tm.otstatus = 1 then 'ENTRY'
                                when tm.otstatus = 2 then 'APPROVED'
                                when tm.otstatus = 3 then 'DISAPPROVED'
                              END as status,tm.othrs as hrs, date(tm.actualin)as startdate,
                              date(tm.actualout) as enddate,tm.entryremarks as remarks,
                              time_format(tm.schedout, '%H:%i ') as starttime,
                              time_format(tm.actualout, '%H:%i') as endtime,
                              tm.approvedby_disapprovedby2 as approvedby
                        from timecard as tm
                        left join client as cl on cl.clientid=tm.empid
                        where $status tm.dateid <='$asof2' and (tm.othrs <> 0 or tm.ndiffot <> 0) and tm.entryot<>0
                        order by tm.dateid";
        return $query;
    }
    public function ot_advance_QUERY($config)
    {
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $divid = $config['params']['dataparams']['divid'];
        $client     = $config['params']['dataparams']['client'];
        $paymode     = $config['params']['dataparams']['sortby'];
        $emploc     = $config['params']['dataparams']['emploc'];
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5228);
        $user = $config['params']['user'];
        //emploc
        $userid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $posttype = $config['params']['dataparams']['posttype'];
        $status = "";
        $filter = "";

        if ($paymode != "") {
            $filter .= " and emp.paymode = '$paymode' ";
        }
        if ($emploc != "") {
            $filter .= " and emp.emploc = '$emploc' ";
        }

        switch ($posttype) {
            case 'entry':
                $status .= " ot.otstatus = 1 ";
                break;
            case 'approved':
                $status .= " ot.otstatus = 2 ";
                break;
        }
        $filteremp = "";
        $leftjoin = "";

        $check = $this->othersClass->checkapproversetup($config, $userid, 'OT', 'emp');
        if ($check['filter'] != "") {
            $filteremp .= $check['filter'];
        }
        if ($check['leftjoin'] != "") {
            $leftjoin .= $check['leftjoin'];
        }


        switch ($companyid) {
            case 53: // camera
            case 51: // ulitc
                $query = "select approver.clientname as appname,approver2.clientname as appname2,date(ot.createdate) as dateapp ,cl.clientname,date(ot.scheddate) as scheddate,ot.othrs,
        ot.daytype,date_format(ot.ottimein,'%H:%i %p') as timein,date_format(ot.ottimeout,'%H:%i %p') as timeout,
        ot.rem as reason,ot.apothrs,date(ot.approvedate) as approvedate,date(ot.approvedate2) as approvedate2, ot.approvedby,ot.remarks as appreason,
        ot.disapproved_remarks2 as appreason2,
        case
        when ot.otstatus2 = 1 && ot.submitdate is null then 'ENTRY'
        when ot.otstatus2 = 1 && ot.submitdate is not null then 'FOR APPROVAL'
        when ot.otstatus2 = 2 then 'APPROVED'
        when ot.otstatus2 = 3 then 'DISAPPROVED' 
        end as status2,

        case
        when ot.otstatus = 1 then 'ENTRY'
        when ot.otstatus = 2 then 'APPROVED'
        when ot.otstatus = 3 then 'DISAPPROVED' 
        end as status,
        ot.othrsextra,ot.ndiffothrs,ot.apothrsextra as extrahrs,ot.apndiffothrs as ndiffhrs
	    from otapplication as ot
        left join client as cl on cl.clientid=ot.empid
        left join employee as emp on emp.empid = ot.empid
        left join client as approver on approver.email = ot.approvedby and approver.email <> ''
        left join client as approver2 on approver2.email = ot.approvedby and approver2.email <> ''
        $leftjoin
        where $status $filter and date(ot.scheddate) between '" . $start . "' and '" . $end . "' $filteremp";
                break;

            default:
                $query = "select '' as number, cl.client, cl.clientname,cl.clientid, date(ot.dateid) as shiftdate,
        date(ot.ottimein) as startdate,date(ot.ottimeout) as enddate,
        time(ot.ottimein) as starttime,time(ot.ottimeout) as endtime,
        ot.othrs,ot.apothrs as hrs,ot.apothrsextra as extrahrs,ot.apndiffothrs as ndiffhrs,
        case
        when ot.otstatus2 = 1 && ot.submitdate is null then 'ENTRY'
        when ot.otstatus2 = 1 && ot.submitdate is not null then 'FOR APPROVAL'
        when ot.otstatus2 = 2 then 'APPROVED'
        when ot.otstatus2 = 3 then 'DISAPPROVED' 
        end as status2,        
        case
        when ot.otstatus = 1 && ot.submitdate is null then 'ENTRY'
        when ot.otstatus = 2 then 'APPROVED'
        when ot.otstatus = 3 then 'DISAPPROVED' 
        end as status,

        ot.rem as remarks,approver.clientname as approvedby,approver2.clientname as approvedby2,date(ot.approvedate) as appdate,date(ot.approvedate2) as appdate2
        from otapplication as ot
        left join client as cl on cl.clientid=ot.empid
        left join employee as emp on emp.empid = ot.empid
        left join client as approver on approver.email = ot.approvedby
        left join client as approver2 on approver2.email = ot.approvedby2
        left join timecard as tm on tm.empid = ot.empid and date(ot.dateid) = date(tm.dateid)
        $leftjoin
        where $status $filter and 
        date(ot.dateid) between '" . $start . "' and '" . $end . "' and (ot.apothrs <> 0 or ot.othrs <> 0) $filteremp";
                break;
        }

        return $query;
    }



    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $asof2    = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1500';
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
        $str .= $this->reporter->col('Overtime Filling', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . $asof2, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
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
        $layoutsize = '1500';

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Shift Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Status', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Start Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Start Time', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('End Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('End Time', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hours Filed', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Hours Approved', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Worked OT Hours', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '185', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '165', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout_ot($config)
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
        $layoutsize = '1500';
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

                if ($data->status == 'APPROVED') {
                    $workhrs = $data->hrs;
                    $approvedhrs = round($data->hrs);
                } else {
                    $workhrs = '0';
                    $approvedhrs = '0';
                }

                $str .= $this->reporter->col($data->client, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->shiftdate, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->status, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->startdate, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->starttime, '135', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->enddate, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->endtime, '135', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(round($data->hrsfilled, 2), '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(round($approvedhrs, 2), '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(round($workhrs, 2), '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '185', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->remarks, '165', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '135', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '135', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'CT', $font, $fontsize, '', 'R', '');
        $str .= $this->reporter->col('', '185', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '165', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function tableheader_stonepro($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1900';

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Shift Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Status', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Start Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Start Time', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('End Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('End Time', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hours Filed', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Hours Approved', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Worked OT Hours', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Approved Date (Supervisor)', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved By (Supervisor)', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Approved Date', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '185', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '165', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function report_stonepro_Layout_ot($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9.5";
        $border = "1px solid ";
        $layoutsize = '1900';
        $stime = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25;margin-top:10px;margin-left:10px;');
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader_stonepro($layoutsize, $config);
        $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1500'];
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col($data->client, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->shiftdate, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->status, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->startdate, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->starttime, '135', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->enddate, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->endtime, '135', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->othrs != 0 ? number_format($data->othrs, 2) : '', '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->hrs != 0 ? number_format($data->hrs, 2) : '', '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->extrahrs != 0 ? number_format($data->extrahrs, 2) : '', '70', null, false, $border, '', 'CT', $font, $fontsize, '', 'R', '');

                $str .= $this->reporter->col($data->appdate2, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby2, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->appdate, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '185', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->remarks, '165', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '135', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '135', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'CT', $font, $fontsize, '', 'R', '');
        $str .= $this->reporter->col('', '185', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '165', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function camera_tableheader($layoutsize, $config, $seqcount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $asof2    = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "7";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Overtime Filling', null, null, false, $border, '', '', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . $asof2, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Applied', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '110', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Schedule Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT Hours', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Day Type', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date In /Time In', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Out /Time Out', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reason', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved OT Hours', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved OT > 8 Hours', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Ndiff OT Hours', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        if ($seqcount > 1) {
            $str .= $this->reporter->col('Head Dept. Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date Approved/ Disapproved', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved /Disapproved By', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved Reason', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->col('Hr/Payroll Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/ Disapproved', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved /Disapproved By', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Reason', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function report_camera_Layout_ot($config)
    {
        $result = $this->reportDefault($config);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "7";
        $border = "1px solid ";
        $stime = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OT'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            // $approversetup = explode(',', $approversetup);

            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        $layoutsize = '1800';
        $seqcount = count($approversetup);
        if ($seqcount == 1 || $both) {
            $seqcount = 1;
            $layoutsize = '1400';
        }

        $str .= $this->reporter->beginreport($layoutsize);
        // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50;margin-top:10px;margin-left:15px;');
        $str .= $this->camera_tableheader($layoutsize, $config, $seqcount);

        if (!empty($result)) {
            foreach ($result as $key => $data) {

                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateapp, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->scheddate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->othrs, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->daytype, '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->timein, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->timeout, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->reason, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->apothrs, '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->extrahrs, '75', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ndiffhrs, '75', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                if ($seqcount > 1) {
                    $str .= $this->reporter->col($data->status2, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->approvedate2, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->appname2, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->appreason2, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                }

                $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appreason, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function header_ulitc($config, $layoutsize)
    {


        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $asof2    = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "7";
        $border = "1px solid ";
        $str = '';

        $companyid   = $config['params']['companyid'];
        $divid = $config['params']['dataparams']['divid'];
        $str .= $this->reporter->begintable($layoutsize);
        if ($companyid == 51) { //ulitc
            if ($divid != '0') {

                $qry = "select code,name,address,tel from center where code = '" . $center . "'";
                $headerdata = $this->coreFunctions->opentable($qry);
                $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
                $divname = $config['params']['dataparams']['divname'];
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($reporttimestamp, null, null, false, $border, '', 'L', $font, '9', '', '', '') . '<br />';
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($divname, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
                $str .= $this->reporter->endrow();
            } else {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->letterhead($center, $username, $config);
                $str .= $this->reporter->endrow();
            }
        } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username, $config);
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Overtime Filling', null, null, false, $border, '', '', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . $asof2, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Applied', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '160', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Schedule Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT Hours', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('>8hrs', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Night Diff', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Day Type', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date In /Time In', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Out /Time Out', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reason', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Status', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved OT Hours', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Approved >8hrs', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Night Diff', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->col('Date Approved/ Disapproved', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved /Disapproved By', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Reason', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_ulitc_Layout_ot($config)
    {
        $result = $this->reportDefault($config);
        $username = $config['params']['user'];
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = '7';
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $str = '';
        $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1700'];
        $layoutsize = $this->reportParams['layoutSize'];
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10;margin-top:10px;margin-left:15px;');

        $str .= $this->header_ulitc($config, $layoutsize);
        $daysbal = 0;
        $i = 0;
        $totalday = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateapp, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->scheddate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->othrs, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col($data->othrsextra, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ndiffothrs, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col($data->daytype, '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->timein, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->timeout, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->reason, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col($data->apothrs, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col($data->extrahrs, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ndiffhrs, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col($data->approvedate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->appname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->appreason, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class