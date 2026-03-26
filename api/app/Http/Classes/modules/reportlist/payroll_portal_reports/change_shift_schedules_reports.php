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

class change_shift_schedules_reports
{
    public $modulename = 'Change Shift Schedules Report';
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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'divname', 'dclientname',  'start', 'end', 'radioposttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'radiostatus.label', 'Change Shift Status');
        data_set($col1, 'divname.type', 'lookup');
        data_set($col1, 'divname.lookupclass', 'lookupempdivision');
        data_set($col1, 'divname.action', 'lookupempdivision');
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'radiostatus.options', [
            ['label' => 'Entry', 'value' => '0', 'color' => 'green'],
            ['label' => 'Approved', 'value' => '1', 'color' => 'green'],
            ['label' => 'Disapproved', 'value' => '2', 'color' => 'red'],
        ]);
        data_set($col1, 'radioposttype.options', [
            ['label' => 'ENTRY', 'value' => 'entry', 'color' => 'red'],
            ['label' => 'APPROVED', 'value' => 'approved', 'color' => 'red']
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
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      'approved' as posttype,
      0 as divid,
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
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        switch ($companyid) {
            case 53: //camera
            case 51: //ulitc
                return  $this->camera_layout($config);
                break;
            case 44: //stonepro
                return $this->stonepro_Layout($config);
                break;

            default:
                return $this->reportDefaultLayout($config);
                break;
        }
    }

    public function reportDefault($config)
    {
        $companyid = $config['params']['companyid'];
        $client = $config['params']['dataparams']['client'];
        $datestart = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $dateend = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $posttype = $config['params']['dataparams']['posttype'];
        $divid = $config['params']['dataparams']['divid'];
        $adminid = $config['params']['adminid'];
        $filter   = "";


        switch ($posttype) {
            case 'entry':
                $status = " csapp.status = 0 and ";
                break;
            case 'approved':
                $status = " csapp.status = 1 and ";
                break;
        }

        $filteremp = "";
        $leftjoin = "";

        $check = $this->othersClass->checkapproversetup($config, $adminid, 'CHANGESHIFT', 'emp');
        if ($check['filter'] != "") {
            $filteremp .= $check['filter'];
        }
        if ($check['leftjoin'] != "") {
            $leftjoin .= $check['leftjoin'];
        }

        $query = "
        select  cl.client as empcode,
        concat(emp.emplast,', ',emp.empfirst,' ',left(emp.empmiddle,1),case when emp.empmiddle <> '' then '.' else '' end) as empname,
        date(csapp.dateid) as dateid,
        DATE_FORMAT(csapp.dateid, '%W') as day,
        concat(DATE_FORMAT(csapp.schedin, '%h:%i %p'),' - ',DATE_FORMAT(csapp.schedout, '%h:%i %p')) as nschedule,
        date(csapp.dateid) as dateid,
        time(csapp.schedin) as schedin,
        time(csapp.schedout) as schedout,
        tcard.daytype,
        csapp.rem,ifnull(app.clientname,app2.clientname) as appname,ifnull(app2.clientname,disapp2.clientname) as appname2,csapp.disapproved_remarks as apprem,csapp.disapproved_remarks2 as apprem2,
        ifnull(date(csapp.approveddate2),date(csapp.disapproveddate2)) as appdate2,
        ifnull(date(csapp.approveddate),date(csapp.disapproveddate)) as appdate,
       (case when csapp.status = 0 then 'Entry'
              when csapp.status = 1 then 'Approved'
              when csapp.status = 2 then 'Disapproved'
              else '' end
        ) as status,
        (case when csapp.status2 = 0 then 'Entry'
              when csapp.status2 = 1 then 'Approved'
              when csapp.status2 = 2 then 'Disapproved'
              else '' end
        ) as status2,
        csapp.approvedby, date(csapp.createdate) as createdate,csapp.daytype as dtype
        from changeshiftapp as csapp
        left join employee as emp on emp.empid = csapp.empid
        left join client as cl on cl.clientid = emp.empid

        left join client as app on app.email = csapp.approvedby and app.email <> ''
        left join client as app2 on app2.email = csapp.approvedby2 and app2.email <> ''

        left join client as disapp on disapp.email = csapp.disapprovedby and disapp.email <> ''
        left join client as disapp2 on disapp2.email = csapp.disapprovedby2 and disapp2.email <> ''

        left join timecard as tcard on tcard.empid = csapp.empid and tcard.dateid = csapp.dateid
        $leftjoin
        where $status $filter date(csapp.dateid) between '" . $datestart . "' and '" . $dateend . "' $filteremp ";

        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config, $layoutSize, $seqcount)
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

        $str = '';
        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();

        if ($client == '') {
            $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '');
        } else {
            $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . strtoupper($datestart) . ' to ' . strtoupper($dateend), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Formal Name', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Shift Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Weekday', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('New Daily Schedule', '140', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('New Day Type', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');

        if ($seqcount > 1) {
            $str .= $this->reporter->col('First Status', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approved By', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Remarks', '180', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        }

        $str .= $this->reporter->col('Last Status', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '180', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $str = '';


        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
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
        $layoutSize = 1170;
        $seqcount = 1;
        if (count($approversetup)) {
            $seqcount = count($approversetup);
            $layoutSize = 1580;
        }

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutSize);
        $str .= $this->displayHeader($config, $layoutSize, $seqcount);
        $daysbal = 0;
        $i = 0;
        $totalday = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutSize);
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col($data->empcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->empname, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->day, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->nschedule, '140', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->daytype, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

            if ($seqcount > 1) {
                $str .= $this->reporter->col($data->status2, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->appname2, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->apprem2, '180', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            }

            $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->apprem, '180', null, false, $border, '', 'L', $font, $font_size, '', '', '');


            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config, $layoutSize, $seqcount);
            $page = $page + $count;
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
    public function computeremainhours($adays, $days)
    {
        return    $days -= $adays;
    }

    public function camera_header($config, $layoutSize, $seqcount)
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
        $companyid     = $config['params']['companyid'];
        $str = '';
        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();

        if ($client == '') {
            $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . strtoupper($datestart) . ' to ' . strtoupper($dateend), NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $labelsup = 'First ';
        $labelapprover = 'Last';
        if ($companyid == 53) { //camera
            $labelsup = 'Head Dept. ';
            $labelapprover = 'Hr/Payroll';
        }

        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Applied', '110', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '130', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Schedule Date', '115', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Day Type', '75', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Schedule', '120', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Reason', '110', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');

        if ($seqcount > 1) {
            $str .= $this->reporter->col($labelsup . ' Status', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Date Approved/ Disapproved', '125', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approved/ Disapproved By', '125', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Approved Reason', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        }

        $str .= $this->reporter->col($labelapprover . ' Status', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/ Disapproved', '125', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved/ Disapproved By', '125', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved Reason', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function camera_layout($config)
    {

        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '8';
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
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

        $seqcount = count($approversetup);
        $layoutSize = '1600';
        if (count($approversetup) == 1 || $both) {
            $layoutSize = '1130';
            $seqcount = 1;
        }

        $this->reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => $layoutSize];

        // $str .= $this->reporter->beginreport($layoutSize);
        $str .= $this->reporter->beginreport($layoutSize, null, false, false, '', '', '', '', '', '', '', '50;margin-top:10px;margin-left:10px;');

        $daysbal = 0;
        $i = 0;
        $totalday = 0;

        $str .= $this->camera_header($config, $layoutSize, $seqcount);
        $str .= $this->reporter->begintable($layoutSize);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->createdate, '110', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->empname, '130', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->dateid, '115', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->dtype, '75', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->nschedule, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->rem, '110', null, false, $border, '', 'L', $font, $font_size, '', '', '');


            if ($seqcount > 1) {
                $str .= $this->reporter->col($data->status2, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->appdate2, '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->appname2, '125', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->apprem2, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            }

            $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appdate, '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appname, '125', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->apprem, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');


            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->page_break();
            $str .= $this->camera_header($config, $layoutSize, $seqcount);
            $page = $page + $count;
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
    private function displayHeader_stonepro($config)
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
        $layoutsize = 1400;

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
            $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '');
        } else {
            $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date : ' . strtoupper($datestart) . ' to ' . strtoupper($dateend), NULL, null, false, $border, '', 'L', $font, '11', '', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Formal Name', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Shift Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Weekday', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('New Daily Schedule', '140', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Status (Supervisor)', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved Date (Supervisor)', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved By (Supervisor)', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Status', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved Date', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '180', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function stonepro_Layout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $str = '';
        $layoutsize = 1400;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_stonepro($config);
        $daysbal = 0;
        $i = 0;
        $totalday = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col($data->empcode, '100', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->empname, '120', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->day, '100', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->nschedule, '140', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->status2, '100', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appdate2, '100', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appname2, '150', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appdate, '80', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->appname, '150', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->rem, '180', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config);
            $page = $page + $count;
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class