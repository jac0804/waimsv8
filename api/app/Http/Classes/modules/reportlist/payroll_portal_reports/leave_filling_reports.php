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

class leave_filling_reports
{
    public $modulename = 'Leave Filling Reports';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    // orientations: portrait=p, landscape=l
    // formats: letter, a4, legal
    // layoutsize: reportWidth
    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1700'];

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
        $fields = ['radioprint',  'divname', 'dclientname', 'start', 'end'];


        if ($companyid == 51 || $companyid == 53) { //camera,ulitc
            array_push($fields, 'radioposttype');
        }

        $col1 = $this->fieldClass->create($fields);

        if ($companyid == 51 || $companyid == 53) { //camera,ulitc
            data_set($col1, 'radioposttype.options', [
                ['label' => 'ENTRY', 'value' => 'entry', 'color' => 'red'],
                ['label' => 'APPROVED', 'value' => 'approved', 'color' => 'red']
            ]);
        }

        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'divname.type', 'lookup');
        data_set($col1, 'divname.lookupclass', 'lookupempdivision');
        data_set($col1, 'divname.action', 'lookupempdivision');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    'approved' as posttype,
    '0' as divid,
    '' as divname,
    '' as division,
    '' as client,
    '' as clientname,
    '' as dclientname
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
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $username = $config['params']['user'];

        switch ($companyid) {
            case 53: //camera
                return $this->report_camera_Layout($config);
                break;
            case 51: //ulitc
                return $this->report_ulitc_Layout($config);
                break;
            case 44: //stone
                return $this->report_stonepro_Layout($config);
                break;


            default:
                return $this->reportDefaultLayout($config);
                break;
        }
    }

    public function reportDefault($config)
    {
        $companyid = $config['params']['companyid'];
        $client     = $config['params']['dataparams']['client'];
        $datestart = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $dateend = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $divid = $config['params']['dataparams']['divid'];
        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $adminid = $config['params']['adminid'];
        $posttype     = $config['params']['dataparams']['posttype'];

        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5228);
        $user = $config['params']['user'];

        $filter   = "";
        $status = "";

        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
        }


        $filteremp = "";
        $leftjoin = "";

        switch ($posttype) {
            case 'entry':
                $status = " lt.status = 'E' and ";
                break;
            case 'approved':
                $status = " lt.status = 'A' and ";
                break;
        }


        $check = $this->othersClass->checkapproversetup($config, $adminid, 'LEAVE', 'e');
        if ($check['filter'] != "") {
            $filteremp .= $check['filter'];
        }
        if ($check['leftjoin'] != "") {
            $leftjoin .= $check['leftjoin'];
        }

        switch ($companyid) {
            case 53: // camera
            case 51: // ulitc
                $query = "
                select date(lt.dateid) as dateid,ls.docno,cl.clientname as empname,ls.days,lt.adays,ls.empid,ls.acnoid,ls.trno,date(lt.effectivity) as effectivity,
                app.clientname as appname,app2.clientname as appname2,lt.remarks,
                date(lt.date_approved_disapproved) as fdate,date(lt.date_approved_disapproved2) as sdate, 
                (case when lt.status2 = 'E' then 'ENTRY' 
                when lt.status2 = 'A' then 'APPROVED'
                else 'DISAPPROVED' end) as status2,
                (case when lt.status = 'E' then 'ENTRY' 
                when lt.status = 'A' then 'APPROVED'
                else 'DISAPPROVED' end) as status,
                lt.disapproved_remarks2 as reason2,lt.disapproved_remarks as reason,b.batch,lt.fillingtype,p.codename

                from leavetrans as lt
                left join leavesetup as ls on lt.trno = ls.trno
                left join employee as e on e.empid=ls.empid
                left join paccount as p on p.line=ls.acnoid
                left join client as cl on cl.clientid=e.empid
                left join client as app on app.email = lt.approvedby_disapprovedby and app.email <> ''
                left join client as app2 on app2.email = lt.approvedby_disapprovedby2 and app2.email <> ''
                left join batch as b on b.line=lt.batchid
                $leftjoin
                where $status $filter date(lt.effectivity) between  '" . $datestart . "' and '" . $dateend . "' $filteremp
                group by ls.docno,ls.trno,cl.clientname,ls.days,ls.empid,ls.acnoid,lt.effectivity,
                lt.dateid,app.clientname,app2.clientname,lt.remarks,lt.status2,lt.status,
                lt.date_approved_disapproved,lt.date_approved_disapproved2,
                lt.disapproved_remarks2,lt.disapproved_remarks,b.batch,lt.fillingtype,p.codename,lt.adays order by lt.effectivity";
                break;

            default:
                $query = "select ls.docno,cl.client,ls.days,ls.empid,ls.acnoid,ls.trno,lt.effectivity
                from leavetrans as lt
                left join leavesetup as ls on lt.trno = ls.trno
                left join employee as e on e.empid=ls.empid
                left join paccount as p on p.line=ls.acnoid
                left join client as cl on cl.clientid=e.empid
                $leftjoin
                where $status date(lt.date_approved_disapproved) is not null $filter and date(lt.effectivity) between '" . $datestart . "' and '" . $dateend . "' $filteremp
                group by ls.docno,ls.trno,client,ls.days,ls.empid,ls.acnoid,lt.effectivity order by lt.effectivity";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config, $layoutsize, $seqcount)
    {

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $padding = '';
        $margin = '';

        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $datestart = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $dateend = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $companyid = $config['params']['companyid'];
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        if ($client == '') {
            $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '5px');
        } else {
            $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '5px');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . strtoupper($datestart) . ' to ' . strtoupper($dateend), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '5px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Doc #', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('leave Date', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Day Type', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Employee Code', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Full Name', '190', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Leave', '60', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Quantity', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


        if ($companyid == 44) { // stonepro
            $str .= $this->reporter->col('Paid', '50', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        }
        $str .= $this->reporter->col('Balance', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Reason', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Creation Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');

        if ($seqcount > 1) {
            $str .= $this->reporter->col('First Status', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approval Date', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approved By', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approver Remarks', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        }

        $str .= $this->reporter->col('Last Status', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approval Date', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approver Remarks', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $username = $config['params']['user'];
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");


        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
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

        $layoutsize = 2150;
        $seqcount = count($approversetup);
        if (count($approversetup) == 1 || $both) {
            $seqcount = 1;
            $layoutsize = 1700;
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $layoutsize, $seqcount);
        $daysbal = 0;
        $i = 0;
        $totalday = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $qry = "
    select ls.docno,date(lt.effectivity) as effectivity,p.code as daytype,client.client,
    date(lt.dateid) as dateapplied,concat(e.emplast,', ',e.empfirst,' ',left(e.empmiddle,1),case when e.empmiddle <> '' then '.' else '' end) as empname,lt.adays,ls.days,
    p.alias as lev,lt.adays as quantity,e.empid,
    case when p.alias in('AA') then 'False' else 'True' end as paid,
    case
    when lt.status = 'A' then 'APPROVED'
    when lt.status = 'E' then 'ENTRY'
    when lt.status = 'O' then 'ON-HOLD'
    when lt.status = 'P' then 'PROCESSED'
    end as status,
    case
    when lt.status2 = 'A' then 'APPROVED'
    when lt.status2 = 'E' then 'ENTRY'
    when lt.status2 = 'O' then 'ON-HOLD'
    when lt.status2 = 'P' then 'PROCESSED'
    end as status2,
    ls.bal,lt.remarks,
    date(ls.dateid) as createdate,
    approver.clientname as approvedby,
    approver2.clientname as approvedby2,
    date(lt.date_approved_disapproved) as date_approved,
    date(lt.date_approved_disapproved2) as date_approved2,
    lt.disapproved_remarks,lt.disapproved_remarks2
    from leavetrans as lt
    left join leavesetup as ls on lt.trno = ls.trno
    left join employee as e on e.empid=ls.empid
    left join client as approver on approver.email = lt.approvedby_disapprovedby and approver.email <> ''
    left join client as approver2 on approver2.email = lt.approvedby_disapprovedby2 and approver2.email <> ''
    left join batch as b on b.line=lt.batchid
    left join paccount as p on p.line=ls.acnoid
    left join paytrancurrent as ptrans on ptrans.acnoid = p.line
    left join client on client.clientid=e.empid
    where lt.status = 'A' and ls.acnoid = ? and ls.docno = ? and ls.empid = ?
    group by ls.docno,lt.effectivity,p.code,client.client,lt.dateid,e.emplast,e.empfirst,e.empmiddle,lt.adays,ls.days,
    p.alias,e.empid,ptrans.batchid,lt.status,lt.status2,ls.bal,lt.remarks,ls.dateid,lt.date_approved_disapproved,lt.date_approved_disapproved2,lt.disapproved_remarks,lt.disapproved_remarks2,approver.clientname,approver2.clientname
    order by lt.effectivity";
            $data2 = $this->coreFunctions->opentable($qry, [$data->acnoid, $data->docno, $data->empid]);
            foreach ($data2 as $key2 => $value) {

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($value->docno, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->effectivity, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->daytype, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->client, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->empname, '190', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->lev, '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($value->quantity, 4), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');

                $str .= $this->reporter->col($value->paid, '50', null, false, $border, '', 'C', $font, $font_size, '', '', '');

                if ($i == 0) {
                    $daysbal =  $data->days - $value->quantity;
                    $i++;
                } else {
                    $daysbal -= $value->quantity;
                    if ($totalday == $value->days) {
                        $i = 0;
                    } else {
                        $i++;
                    }
                }
                $str .= $this->reporter->col(number_format(($daysbal), 4), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $data->days = $daysbal;
                $totalday += $value->adays;

                // $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->remarks, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->createdate, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

                if ($seqcount > 1) {
                    $str .= $this->reporter->col($value->status2, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($value->date_approved2, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($value->approvedby2, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($value->disapproved_remarks2, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                }

                $str .= $this->reporter->col($value->status, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->date_approved, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->approvedby, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->disapproved_remarks, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
            }
            $daysbal = 0;
            $totalday = 0;
            $i = 0;
            $str .= $this->reporter->endtable();
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $layoutsize, $seqcount);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
    public function computeremainhours($adays, $days)
    {
        return    $days -= $adays;
    }
    public function camera_header($config, $layoutsize)
    {
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $padding = '';
        $margin = '';
        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $datestart = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $dateend = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        if ($client == '') {
            $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '8', '', '', '', '5px');
        } else {
            $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '8', '', '', '', '5px');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . strtoupper($datestart) . ' to ' . strtoupper($dateend), NULL, null, false, $border, '', 'L', $font, '8', '', '', '', '5px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Applied', '90', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Effectivity', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Days', '40', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Reason', '135', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('First Status', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Approved/ Disapproved By', '115', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/ Disapproved', '115', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approver Reason', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Last Status', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved/ Disapproved By', '130', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/ Disapproved', '115', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Supervisor Reason', '135', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Batch', '85', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_camera_Layout($config)
    {
        $result = $this->reportDefault($config);
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $count = 55;
        $page = 55;
        $str = '';

        $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1400'];
        $layoutsize = '1400';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");


        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
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

        $layoutsize = 2150;
        $seqcount = count($approversetup);
        if (count($approversetup) == 1 || $both) {
            $seqcount = 1;
            $layoutsize = 1700;
        }
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25;margin-top:10px;margin-left:10px;');
        $str .= $this->camera_header($config, $layoutsize, $seqcount);
        $daysbal = 0;
        $i = 0;
        $totalday = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->empname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->effectivity, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->days, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->remarks, '135', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->status2, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appname2, '115', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->sdate, '115', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->reason2, '85', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->status, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appname, '130', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->fdate, '115', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->reason, '135', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->batch, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->camera_header($config, $layoutsize, $seqcount);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
    public function ulitc_header($config, $layoutsize)
    {
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $padding = '';
        $margin = '';
        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $datestart = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $dateend = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

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
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        if ($client == '') {
            $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '8', '', '', '', '5px');
        } else {
            $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '8', '', '', '', '5px');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . strtoupper($datestart) . ' to ' . strtoupper($dateend), NULL, null, false, $border, '', 'L', $font, '8', '', '', '', '5px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Applied', '90', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Effectivity', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Leave Day', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Type', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Days', '40', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Reason', '135', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');


        // $str .= $this->reporter->col('First Status', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        // $str .= $this->reporter->col('Approved/ Disapproved By', '115', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('Date Approved/ Disapproved', '115', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('Approver Reason', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');


        $str .= $this->reporter->col('Last Status', '85', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved/ Disapproved By', '130', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/ Disapproved', '115', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Supervisor Reason', '135', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Batch', '85', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_ulitc_Layout($config)
    {
        $result = $this->reportDefault($config);
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $count = 55;
        $page = 55;
        $str = '';

        $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1600'];
        $layoutsize = '1600';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25;margin-top:10px;margin-left:10px;');
        $str .= $this->ulitc_header($config, $layoutsize);
        $daysbal = 0;
        $i = 0;
        $totalday = 0;
        $day = "";
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->empname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->effectivity, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');

            if ($data->adays == 1) {
                $day = "WHOLE DAY";
            } else {
                $day = "HALFDAY";
            }

            $str .= $this->reporter->col($day, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->codename, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

            $str .= $this->reporter->col($data->days, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->remarks, '135', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col($data->status2, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col($data->appname2, '115', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col($data->sdate, '115', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col($data->reason2, '85', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->status, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appname, '130', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->fdate, '115', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->reason, '135', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->batch, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->camera_header($config, $layoutsize);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
    private function displayHeader_stonepro($config, $layoutsize, $seqcount)
    {

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $datestart = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $dateend = date('Y-m-d', strtotime($config['params']['dataparams']['end']));


        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        if ($client == '') {
            $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '5px');
        } else {
            $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '5px');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . strtoupper($datestart) . ' to ' . strtoupper($dateend), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '5px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Doc #', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('leave Date', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Day Type', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Employee Code', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Full Name', '190', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Leave', '60', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Quantity', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


        // $str .= $this->reporter->col('Paid', '50', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Balance', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Reason', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Creation Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        if ($seqcount > 1) {
            $str .= $this->reporter->col('Status (Supervisor) ', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approval Date (Supervisor)', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approved By (Supervisor)', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approver Remarks (Supervisor)', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        }

        $str .= $this->reporter->col('Status', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approval Date', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approver Remarks', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_stonepro_Layout($config)
    {
        $result = $this->reportDefault($config);

        $username = $config['params']['user'];
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $str = '';
        // $layoutsize = 1920;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");


        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
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

        $layoutsize = 2100;
        $seqcount = count($approversetup);
        if ($seqcount == 1 || $both) {
            $seqcount = 1;
            $layoutsize = 1650;
        }
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25;margin-top:10px;margin-left:10px;');
        $str .= $this->displayHeader_stonepro($config, $layoutsize, $seqcount);
        $daysbal = 0;
        $i = 0;
        $totalday = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $qry = "
    select ls.docno,date(lt.effectivity) as effectivity,p.code as daytype,client.client,
    date(lt.dateid) as dateapplied,concat(e.emplast,', ',e.empfirst,' ',left(e.empmiddle,1),case when e.empmiddle <> '' then '.' else '' end) as empname,lt.adays,ls.days,
    p.alias as lev,lt.adays as quantity,e.empid,
    case when p.alias in('AA') then 'False' else 'True' end as paid,
    case
    when lt.status = 'A' then 'APPROVED'
    when lt.status = 'E' then 'ENTRY'
    when lt.status = 'O' then 'ON-HOLD'
    when lt.status = 'P' then 'PROCESSED'
    end as status,
    case
    when lt.status2 = 'A' then 'APPROVED'
    when lt.status2 = 'E' then 'ENTRY'
    when lt.status2 = 'O' then 'ON-HOLD'
    when lt.status2 = 'P' then 'PROCESSED'
    end as status2,
    ls.bal,lt.remarks,
    date(ls.dateid) as createdate,
    approver.clientname as approvedby,
    approver2.clientname as approvedby2,
    date(lt.date_approved_disapproved) as date_approved,
    date(lt.date_approved_disapproved2) as date_approved2,
    lt.disapproved_remarks as reason,lt.disapproved_remarks2 as reason2
    from leavetrans as lt
    left join leavesetup as ls on lt.trno = ls.trno
    left join employee as e on e.empid=ls.empid
    left join client as approver on approver.email = lt.approvedby_disapprovedby and approver.email <> ''
    left join client as approver2 on approver2.email = lt.approvedby_disapprovedby2 and approver2.email <> ''

    left join batch as b on b.line=lt.batchid
    left join paccount as p on p.line=ls.acnoid
    left join paytrancurrent as ptrans on ptrans.acnoid = p.line
    left join client on client.clientid=e.empid
    where lt.status = 'A' and ls.acnoid = ? and ls.docno = ? and ls.empid = ?
    group by ls.docno,lt.effectivity,p.code,client.client,lt.dateid,e.emplast,e.empfirst,e.empmiddle,lt.adays,ls.days,
    p.alias,e.empid,ptrans.batchid,lt.status,lt.status2,ls.bal,lt.remarks,ls.dateid,lt.date_approved_disapproved,lt.date_approved_disapproved2,lt.disapproved_remarks,lt.disapproved_remarks2,approver.clientname,approver2.clientname
    order by lt.effectivity";
            $data2 = $this->coreFunctions->opentable($qry, [$data->acnoid, $data->docno, $data->empid]);
            foreach ($data2 as $key2 => $value) {

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($value->docno, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->effectivity, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->daytype, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->client, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->empname, '190', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->lev, '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($value->quantity, 2), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');

                // $str .= $this->reporter->col($value->paid, '50', null, false, $border, '', 'L', $font, $font_size, '', '', '');

                if ($i == 0) {
                    $daysbal =  $data->days - $value->quantity;
                    $i++;
                } else {
                    $daysbal -= $value->quantity;
                    if ($totalday == $value->days) {
                        $i = 0;
                    } else {
                        $i++;
                    }
                }
                $str .= $this->reporter->col(number_format(($daysbal), 2), '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $data->days = $daysbal;
                $totalday += $value->adays;

                // $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->remarks, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->createdate, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

                if ($seqcount > 1) {
                    $str .= $this->reporter->col($value->status, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($value->date_approved2, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($value->approvedby2, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($value->reason2, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                }

                $str .= $this->reporter->col($value->status, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->date_approved, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->approvedby, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->reason, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
            }
            $daysbal = 0;
            $totalday = 0;
            $i = 0;
            $str .= $this->reporter->endtable();
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $layoutsize, $seqcount);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class