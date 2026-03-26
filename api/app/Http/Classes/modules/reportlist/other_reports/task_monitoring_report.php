<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class task_monitoring_report
{
    public $modulename = 'Task Monitoring Report';
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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'username', 'dystatus', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'username.lookupclass', 'lookupusers');

        // status options
        data_set($col1, 'radiostatus.options', [
            ['label' => 'Open', 'value' => '0', 'color' => 'blue'],
            ['label' => 'Pending', 'value' => '1', 'color' => 'blue'],
            ['label' => 'For Checking', 'value' => '2', 'color' => 'blue'],
            ['label' => 'Completed', 'value' => '3', 'color' => 'blue'],
            ['label' => 'Cancelled', 'value' => '4', 'color' => 'blue'],
            ['label' => 'All', 'value' => '5', 'color' => 'blue']
        ]);

        // group by options
        data_set($col1, 'radioreporttype.name', 'groupby');
        data_set($col1, 'radioreporttype.label', 'Group by:');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Group by users', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Group by customer', 'value' => '1', 'color' => 'orange']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $currentDate = $this->othersClass->getCurrentDate();
        $paramstr = "select 
        'default' as print,
        adddate('" . $currentDate . "', -30) as start,
        ' " . $currentDate . " ' as end, 
        0 clientid, '' client, '' as clientname, '' as dclientname,
        0 userid, '' as username,
        '0' groupby, '0' statid, '' as dystatus,
        '0' as groupby
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
        $groupby = $config['params']['dataparams']['groupby'];
        switch ($groupby) {
            case 0:
                return $this->taskmonitoring_grp_by_users($config);
                break;
            case 1:
                return $this->taskmonitoring_grp_by_customer($config);
                break;
        }
    }

    // QUERY
    public function tm_qry($config)
    {
        // $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        // $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $start = ($config['params']['dataparams']['start']);
        $end = ($config['params']['dataparams']['end']);
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $userid = ($config['params']['dataparams']['userid']);
        $username = ($config['params']['dataparams']['username']);
        $status = ($config['params']['dataparams']['dystatus']);

        $filter = '';
        $groupby = '';
        $statusFilter = '';

        if ($clientname != '') {
            $filter .= " and customer.clientid = '$clientid'";
        }

        if (
            $username != ''
        ) {
            $filter .= " and users.clientid = '$userid'";
        }

        // Filter main tasks that have at least one daily task with the selected status
        switch ($status) {
            case 'On-Going':
                $statusid = 0;
                break;
            case 'Completed':
                $statusid = 1;
                break;
            case 'Undone':
                $statusid = 2;
                break;
            case 'Neglect':
                $statusid = 4;
                break;
            case 'Cancelled':
                $statusid = 5;
                break;
            case 'Return':
                $statusid = 6;
                break;
            default:
                $statusid = '';
                break;
        }

        if ($statusid !== '') {
            $statusFilter = " and (tmd.line, tmd.trno) in (
            select taskline, tasktrno from hdailytask where statid = $statusid
            union select taskline, tasktrno from dailytask where statid = $statusid
            )";
        }

        switch ($config['params']['dataparams']['groupby']) {
            case 1:
                $groupby .= ' order by customer.clientname, date(tmd.encodeddate)';
                break;
            default:
                $groupby .= ' order by users.clientname, date(tmd.encodeddate)';
                break;
        }

        $query = "select 'main_task' as record_type, tmd.userid, users.clientname as users, customer.clientname as customer, customer.clientid,
                    date(tmh.createdate) as date, date(tmd.encodeddate) as ddate, tmd.title as task, tmd.startdate, tmd.fcheckingdate as enddate, '' as dtask, 
                    '' as createdate, '' as donedate, 
                    tmd.line, tmd.trno, 0 as taskline, 0 as tasktrno,
                    tmd.status as main_status, '' as daily_status,
                    '' as isprev, '' as ischecker
                
            from tmhead as tmh
            left join tmdetail as tmd on tmd.trno = tmh.trno
            left join client as customer on customer.clientid = tmh.clientid
            left join client as users on users.clientid = tmd.userid
            where users.clientname is not null and users.clientname != ''
            and date(tmd.encodeddate) between '$start' and '$end'
            $filter
            $statusFilter 
            $groupby";
        return $this->coreFunctions->opentable($query);
    }

    public function daily_tasks_qry($config)
    {
        $start = ($config['params']['dataparams']['start']);
        $end = ($config['params']['dataparams']['end']);
        $userid = ($config['params']['dataparams']['userid']);
        $username = ($config['params']['dataparams']['username']);
        $status = ($config['params']['dataparams']['dystatus']);

        $filter = '';
        $userTaskFilter = '';

        switch ($status) {
            case 'On-Going':
                $filter .= " and dtask.statid = 0";
                break;
            case 'Completed':
                $filter .= " and dtask.statid = 1";
                break;
            case 'Undone':
                $filter .= " and dtask.statid = 2";
                break;
            case 'Neglect':
                $filter .= " and dtask.statid = 4";
                break;
            case 'Cancelled':
                $filter .= " and dtask.statid = 5";
                break;
            case 'Return':
                $filter .= " and dtask.statid = 6";
                break;
        }

        if ($username != '') {
            $userTaskFilter = " and tasktrno in (
            select distinct tasktrno from hdailytask where userid = '$userid'
            union
            select distinct tasktrno from dailytask where userid = '$userid'
        )";
        }

        // Match the same groupby ordering as tm_qry
        // $groupby = '';
        // switch ($config['params']['dataparams']['groupby']) {
        //     case 1:
        //         $groupby = ' order by date(createdate), createdate';
        //         break;
        //     default:
        //         $groupby = ' order by date(createdate), createdate';
        //         break;
        // }

        $query = "select * from (
        select dtask.userid, users.clientname as users, customer.clientname as customer, dtask.isprev, dtask.ischecker,
               dtask.createdate, dtask.donedate, dtask.rem as dtask_comment,
               dtask.statid as daily_status,
               dtask.taskline, dtask.tasktrno, dtask.reseller,
               tmd.encodeddate
        from hdailytask as dtask
        left join client as users on users.clientid = dtask.userid
        left join client as customer on customer.clientid = dtask.clientid
        left join tmdetail as tmd on tmd.line = dtask.taskline and tmd.trno = dtask.tasktrno
        where users.clientname is not null and users.clientname != '' 
        and date(dtask.createdate) between '$start' and '$end' 
        and dtask.taskline <> 0 and dtask.tasktrno <> 0
        $userTaskFilter
        $filter

        union all

        select dtask.userid, users.clientname as users, customer.clientname as customer, dtask.isprev, dtask.ischecker,
               dtask.createdate, dtask.donedate, dtask.rem as dtask_comment,
               dtask.statid as daily_status,
               dtask.taskline, dtask.tasktrno, dtask.reseller,
               tmd.encodeddate
        from dailytask as dtask
        left join client as users on users.clientid = dtask.userid
        left join client as customer on customer.clientid = dtask.clientid
        left join tmdetail as tmd on tmd.line = dtask.taskline and tmd.trno = dtask.tasktrno
        where users.clientname is not null and users.clientname != ''
        and date(dtask.createdate) between '$start' and '$end' 
        and dtask.taskline <> 0 and dtask.tasktrno <> 0
        $userTaskFilter
        $filter
    ) as combineddtask
    order by tasktrno, taskline, createdate";
        return $this->coreFunctions->opentable($query);
    }

    public function get_task_comments($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "
            select prem.createdate, prem.rem as cmnt, prem.createby, client.clientname, client.clientid,
                   prem.tmline, prem.tmtrno
            from headprrem as prem
            left join client on client.email = prem.createby
            where date(prem.createdate) between '$start' and '$end'
            order by prem.tmtrno, prem.tmline, prem.createdate desc
            ";
        return $this->coreFunctions->opentable($query);
    }

    // customer registration
    public function task_monitoring_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
        $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));
        // $dcentername     = $config['params']['dataparams']['dcentername'];
        $clientname = ($config['params']['dataparams']['clientname']);
        $str = '';
        $layoutsize = '1200';
        $font = "Tahoma";
        $fontsize = "11";
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
        $str .= $this->reporter->col('TASK MONITORING REPORT', null, null, false, '3px solid', '', 'c', $font, '12', 'B', 'blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('<i>Date Covered: ' . $start . ' to ' . $end . '</i>', null, null, false, '3px solid', '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        return $str;
    }

    public function taskmonitoring_grp_by_users($config)
    {
        $str = '';
        $layoutsize = '1200';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "2px solid";
        $this->reporter->linecounter = 0;

        $groupby = $config['params']['dataparams']['groupby'];

        // Get main tasks
        $mainTasks = $this->tm_qry($config);

        // Get daily tasks
        $dailyTasks = $this->daily_tasks_qry($config);

        // Get comments from headprrem
        $comments = $this->get_task_comments($config);

        if (empty($mainTasks)) {
            return $this->othersClass->emptydata($config);
        }

        // Pagination settings
        $linesPerPage = 30;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->task_monitoring_header($config);

        $currentUserId = '';
        $rowCount = 0;
        $reporttype = 'GROUP BY USERS';

        switch ($groupby) {
            case 1:
                $reporttype = 'GROUP BY CUSTOMERS';
                break;
            default:
                $reporttype = 'GROUP BY USERS';
                break;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FORMAT: ' . $reporttype, $layoutsize, null, false, $border, '', 'LB', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, $border, '', 'LB', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // Simple loop through main tasks
        foreach ($mainTasks as $data) {
            $rowCount++;

            // Page break
            // if ($rowCount > $linesPerPage) {
            //     $str .= $this->reporter->endreport();
            //     $str .= $this->reporter->beginreport($layoutsize);
            //     $str .= $this->task_monitoring_header($config);
            //     $rowCount = 1;
            // }

            // User header
            if ($currentUserId != $data->userid) {
                $currentUserId = $data->userid;
                $mainTaskCounter = 0;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('USER: ' . $data->users, $layoutsize, null, false, $border, 'B', 'L', $font, '12', 'B');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // Main table headers
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('NO.', '20', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('DATE', '110', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('STATUS', '120', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('CUSTOMER', '250', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('TASK', '380', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('START', '160', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('END', '160', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
            $mainTaskCounter++;

            // Format main task
            $formattedDate = !empty($data->ddate) ? date('m/d/Y', strtotime($data->ddate)) : '-';
            $startDate = !empty($data->startdate) ? date('m/d/Y h:i A', strtotime($data->startdate)) : '-';
            $endDate = !empty($data->enddate) ? date('m/d/Y h:i A', strtotime($data->enddate)) : '-';

            // Main task status
            $status = $data->main_status;
            if ($status == 0) $statLabel = 'Draft';
            elseif ($status == 1) $statLabel = 'Open';
            elseif ($status == 2) $statLabel = 'Pending';
            elseif ($status == 3) $statLabel = 'Ongoing';
            elseif ($status == 4) $statLabel = 'For Checking';
            elseif ($status == 5) $statLabel = 'Completed';
            else $statLabel = '';

            // pagkuha ng reseller sa dtask table for this main task
            $resellerName = '';
            foreach ($dailyTasks as $dtask) {
                if ($dtask->taskline == $data->line && $dtask->tasktrno == $data->trno && !empty($dtask->reseller)) {
                    $resellerName = $dtask->reseller;
                    break;
                }
            }

            $customerDisplay = $data->customer;
            if (!empty($resellerName)) {
                $customerDisplay .= '/' . $resellerName;
            }

            // Main task row
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($mainTaskCounter . '.', '20', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($formattedDate, '110', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($statLabel, '120', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($customerDisplay, '250', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->task, '380', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($startDate, '160', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($endDate, '160', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // Find and display daily tasks for this main task
            $foundDaily = false;
            $totalDailyHours = 0;
            $dailyTaskCounter = 0;

            foreach ($dailyTasks as $dtask) {
                if ($dtask->taskline == $data->line && $dtask->tasktrno == $data->trno) {

                    if (!$foundDaily) {
                        // Daily task headers
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '20', null, true, '2px dotted', 'LT', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('DATE', '110', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('STATUS', '100', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('USER', '180', null, true, '2px dotted', 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('COMMENT', '240', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('START', '160', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('END', '160', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('HOURS', '130', null, true, '2px dotted', 'TR', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        $foundDaily = true;
                    }

                    $dailyTaskCounter++;

                    // Format daily task
                    $taskDate = !empty($dtask->createdate) ? date('m/d/Y', strtotime($dtask->createdate)) : '-';
                    $createdTime = !empty($dtask->createdate) ? date('m/d/Y h:i A', strtotime($dtask->createdate)) : '-';
                    $doneTime = !empty($dtask->donedate) ? date('m/d/Y h:i A', strtotime($dtask->donedate)) : '-';

                    $createdTimestamp = !empty($dtask->createdate) ? strtotime($dtask->createdate) : 0;
                    $doneTimestamp = !empty($dtask->donedate) ? strtotime($dtask->donedate) : 0;
                    $hoursDifference = ($createdTimestamp && $doneTimestamp) ? round(($doneTimestamp - $createdTimestamp) / 3600, 2) : 0;
                    $totalDailyHours += $hoursDifference;
                    $displayHours = $hoursDifference ? $hoursDifference . ' hrs' : '-';

                    // daily task status
                    $statid = $dtask->daily_status;
                    if ($statid == 0) $statLabel2 = 'On-going';
                    elseif ($statid == 1) $statLabel2 = 'Completed';
                    elseif ($statid == 2) $statLabel2 = 'Undone';
                    elseif ($statid == 4) $statLabel2 = 'Neglect';
                    elseif ($statid == 5) $statLabel2 = 'Cancelled';
                    elseif ($statid == 6) $statLabel2 = 'Return';
                    else $statLabel2 = '';

                    if ($dtask->ischecker == 1) {
                        $statLabel2 = $statLabel2 . ' / Checker';
                    }

                    if (
                        $dtask->isprev == 1 && $dtask->daily_status == 0
                    ) {
                        $checker = ($dtask->ischecker == 1) ? ' / Checker' : '';
                        $displayLabel = ' On-Going (Continuation)' . $checker;
                    } else {
                        $displayLabel = $statLabel2;
                    }

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', '20', false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '20', '20', false, '2px dotted', 'L', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '110', '20', false, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '100', '20', false, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '180', '20', false, '2px dotted', '', 'L', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '240', '20', false, '2px dotted', '', 'L', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '160', '20', false, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '160', '20', false, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '130', '20', false, '2px dotted', 'R', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '50', '20', false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    // Daily task row
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('&nbsp' . $dailyTaskCounter . '.', '20', null, true, '2px dotted', 'L', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('&nbsp&nbsp' . $taskDate, '110', null, false, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($displayLabel, '100', null, false, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($dtask->users, '180', null, false, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '240', null, false, '2px dotted', '', 'L', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($createdTime, '160', null, false, '2px dotted', '', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($doneTime, '160', null, false, '2px dotted', '', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($displayHours . '&nbsp&nbsp', '130', null, false, '2px dotted', 'R', 'RT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    // Find and display comments for this daily task
                    $foundComment = false;
                    $matchingComment = null;

                    // For each daily task, find comments that were created AFTER this task was completed
                    // but BEFORE the next task of the same type was started
                    $dtaskDoned = !empty($dtask->donedate) ? strtotime($dtask->donedate) : 0;

                    if ($dtaskDoned > 0) {
                        // Find the next daily task for the same main task (if any)
                        $nextTaskStart = PHP_INT_MAX;
                        foreach ($dailyTasks as $nextTask) {
                            if ($nextTask->taskline == $dtask->taskline && $nextTask->tasktrno == $dtask->tasktrno) {
                                $nextStart = strtotime($nextTask->createdate);
                                if ($nextStart > $dtaskDoned && $nextStart < $nextTaskStart) {
                                    $nextTaskStart = $nextStart;
                                }
                            }
                        }

                        // Find the most recent comment created after this task was completed
                        // but before the next task started
                        $closestComment = null;
                        $closestTimeDiff = PHP_INT_MAX;

                        foreach ($comments as $comment) {
                            if ($comment->tmline == $dtask->taskline && $comment->tmtrno == $dtask->tasktrno) {
                                $commentTime = strtotime($comment->createdate);

                                // Comment should be after task completion
                                if ($commentTime >= $dtaskDoned) {
                                    // And before the next task starts (if there is a next task)
                                    if ($commentTime <= $nextTaskStart) {
                                        // Find the comment closest to the completion time
                                        $timeDiff = $commentTime - $dtaskDoned;
                                        if ($timeDiff < $closestTimeDiff) {
                                            $closestTimeDiff = $timeDiff;
                                            $closestComment = $comment;
                                        }
                                    }
                                }
                            }
                        }

                        $matchingComment = $closestComment;
                    }

                    // Display the matching comment if found
                    if ($matchingComment) {
                        if (!$foundComment) {
                            $foundComment = true;
                        }

                        // Format comment date
                        $commentDate = !empty($matchingComment->createdate) ? date('m/d/Y', strtotime($matchingComment->createdate)) : '-';

                        // Comment label
                        $commentLabel = ($dtask->ischecker == 1) ? 'Comment / Checker' : 'Comment';

                        // Comment row
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '20', null, false, '2px dotted', 'L', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('&nbsp&nbsp' . $commentDate, '110', null, false, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col($commentLabel, '100', null, false, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col($matchingComment->clientname, '180', null, false, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col($matchingComment->cmnt, '240', null, false, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '160', null, false, '2px dotted', '', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '160', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '130', null, false, '2px dotted', 'R', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }
                }
            }

            // Show total daily hours if there were daily tasks
            if ($foundDaily && $totalDailyHours > 0) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '20', null, false, '2px dotted', 'BL', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '110', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '100', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '180', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('TOTAL DAILY HOURS:', '240', null, false, '2px dotted', 'B', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col($totalDailyHours, '130', null, false, '2px dotted', 'BR', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            } else if ($foundDaily && $totalDailyHours == 0) {
                // Handle case where daily tasks exist but have 0 hours
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '20', null, false, '2px dotted', 'BL', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '110', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '100', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '180', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('TOTAL DAILY HOURS:', '240', null, false, '2px dotted', 'B', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, false, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('0.00 hrs', '130', null, false, '2px dotted', 'BR', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '50', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
            $str .= '<br></br>';
        }

        // Footer
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '290', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '420', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function taskmonitoring_grp_by_customer($config)
    {
        $str = '';
        $layoutsize = '1200';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "2px solid";
        $this->reporter->linecounter = 0;

        $groupby = $config['params']['dataparams']['groupby'];

        // Get main tasks
        $mainTasks = $this->tm_qry($config);

        // Get daily tasks
        $dailyTasks = $this->daily_tasks_qry($config);

        // Get comments from headprrem
        $comments = $this->get_task_comments($config);

        if (empty($mainTasks)) {
            return $this->othersClass->emptydata($config);
        }

        // Pagination settings
        $linesPerPage = 30;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->task_monitoring_header($config);

        $currentCustomerId = '';
        $rowCount = 0;
        $reporttype = '';

        switch ($groupby) {
            case 1:
                $reporttype = 'GROUP BY CUSTOMERS';
                break;
            default:
                $reporttype = 'GROUP BY USERS';
                break;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FORMAT: ' . $reporttype, $layoutsize, null, false, $border, '', 'LB', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, $border, '', 'LB', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // Simple loop through main tasks
        foreach ($mainTasks as $data) {
            $rowCount++;

            // Page break
            // if ($rowCount > $linesPerPage) {
            //     $str .= $this->reporter->endreport();
            //     $str .= $this->reporter->beginreport($layoutsize);
            //     $str .= $this->task_monitoring_header($config);
            //     $rowCount = 1;
            // }

            // Get reseller information from daily tasks for this customer
            $resellerNames = [];
            foreach ($dailyTasks as $dtask) {
                // Check if this daily task belongs to any main task of this customer
                // We need to find if this daily task is associated with this customer through its main task
                foreach ($mainTasks as $mainTask) {
                    if (
                        $mainTask->clientid == $data->clientid &&
                        $dtask->taskline == $mainTask->line &&
                        $dtask->tasktrno == $mainTask->trno &&
                        !empty($dtask->reseller)
                    ) {
                        $resellerNames[$dtask->reseller] = true;
                    }
                }
            }

            // Format customer display with resellers if available
            $customerDisplay = $data->customer;
            if (!empty($resellerNames)) {
                $resellerList = implode(', ', array_keys($resellerNames));
                $customerDisplay .= '/' . $resellerList;
            }

            // Customer header
            if ($currentCustomerId != $data->clientid) {
                $currentCustomerId = $data->clientid;
                $mainTaskCounter = 0;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CUSTOMER: ' . $customerDisplay, $layoutsize, null, false, $border, 'B', 'L', $font, '12', 'B');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // Main table headers - SWAPPED: Now showing USER first, then CUSTOMER (or just showing USER)
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('NO.', '20', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('DATE', '110', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('STATUS', '120', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('USER', '250', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('TASK', '380', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('START', '160', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('END', '160', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
            $mainTaskCounter++;

            // Format main task
            $formattedDate = !empty($data->ddate) ? date('m/d/Y', strtotime($data->ddate)) : '-';
            $startDate = !empty($data->startdate) ? date('m/d/Y h:i A', strtotime($data->startdate)) : '-';
            $endDate = !empty($data->enddate) ? date('m/d/Y h:i A', strtotime($data->enddate)) : '-';

            // Main task status
            $status = $data->main_status;
            if ($status == 0) $statLabel = 'Draft';
            elseif ($status == 1) $statLabel = 'Open';
            elseif ($status == 2) $statLabel = 'Pending';
            elseif ($status == 3) $statLabel = 'Ongoing';
            elseif ($status == 4) $statLabel = 'For Checking';
            elseif ($status == 5) $statLabel = 'Completed';
            else $statLabel = '';

            // Main task row - SWAPPED: Now showing USER in the customer column position
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($mainTaskCounter, '20', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($formattedDate, '110', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($statLabel, '120', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->users, '250', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->task, '380', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($startDate, '160', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($endDate, '160', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // Find and display daily tasks for this main task
            $foundDaily = false;
            $totalDailyHours = 0;
            $dailyTaskCounter = 0;

            foreach ($dailyTasks as $dtask) {
                if ($dtask->taskline == $data->line && $dtask->tasktrno == $data->trno) {

                    if (!$foundDaily) {
                        // Daily task headers (unchanged)
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '20', null, true, '2px dotted', 'LT', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('DATE', '110', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('STATUS', '100', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('USER', '180', null, true, '2px dotted', 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('COMMENT', '240', null, true, '2px dotted', 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('START', '160', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('END', '160', null, true, '2px dotted', 'T', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('HOURS', '130', null, true, '2px dotted', 'TR', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        $foundDaily = true;
                    }

                    $dailyTaskCounter++;

                    // Format daily task
                    $taskDate = !empty($dtask->createdate) ? date('m/d/Y', strtotime($dtask->createdate)) : '-';
                    $createdTime = !empty($dtask->createdate) ? date('m/d/Y h:i A', strtotime($dtask->createdate)) : '-';
                    $doneTime = !empty($dtask->donedate) ? date('m/d/Y h:i A', strtotime($dtask->donedate)) : '-';

                    $createdTimestamp = !empty($dtask->createdate) ? strtotime($dtask->createdate) : 0;
                    $doneTimestamp = !empty($dtask->donedate) ? strtotime($dtask->donedate) : 0;
                    $hoursDifference = ($createdTimestamp && $doneTimestamp) ? round(($doneTimestamp - $createdTimestamp) / 3600, 2) : 0;
                    $totalDailyHours += $hoursDifference;
                    $displayHours = $hoursDifference ? $hoursDifference . ' hrs' : '-';

                    // daily task status
                    $statid = $dtask->daily_status;
                    if ($statid == 0) $statLabel2 = 'On-going';
                    elseif ($statid == 1) $statLabel2 = 'Completed';
                    elseif ($statid == 2) $statLabel2 = 'Undone';
                    elseif ($statid == 4) $statLabel2 = 'Neglect';
                    elseif ($statid == 5) $statLabel2 = 'Cancelled';
                    elseif ($statid == 6) $statLabel2 = 'Return';
                    else $statLabel2 = '';

                    if ($dtask->ischecker == 1) {
                        $statLabel2 = $statLabel2 . ' / Checker';
                    }

                    if ($dtask->isprev == 1 && $dtask->daily_status == 0) {
                        $checker = ($dtask->ischecker == 1) ? ' / Checker' : '';
                        $displayLabel = ' On-Going (Continuation)' . $checker;
                    } else {
                        $displayLabel = $statLabel2;
                    }

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', '20', true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '20', '20', true, '2px dotted', 'L', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '110', '20', true, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '100', '20', true, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '180', '20', true, '2px dotted', '', 'L', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '240', '20', true, '2px dotted', '', 'L', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '160', '20', true, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '160', '20', true, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '130', '20', true, '2px dotted', 'R', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '50', '20', true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    // Daily task row
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('&nbsp' . $dailyTaskCounter . '.', '20', null, true, '2px dotted', 'L', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('&nbsp&nbsp' . $taskDate, '110', null, true, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($displayLabel, '100', null, true, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($dtask->users, '180', null, true, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '240', null, true, '2px dotted', '', 'L', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($createdTime, '160', null, true, '2px dotted', '', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($doneTime, '160', null, true, '2px dotted', '', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col($displayHours . '&nbsp&nbsp', '130', null, true, '2px dotted', 'R', 'RT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    // Find and display comments for this daily task (unchanged logic)
                    $foundComment = false;
                    $matchingComment = null;

                    // For each daily task, find comments that were created AFTER this task was completed
                    // but BEFORE the next task of the same type was started
                    $dtaskDoned = !empty($dtask->donedate) ? strtotime($dtask->donedate) : 0;

                    if ($dtaskDoned > 0) {
                        // Find the next daily task for the same main task (if any)
                        $nextTaskStart = PHP_INT_MAX;
                        foreach ($dailyTasks as $nextTask) {
                            if ($nextTask->taskline == $dtask->taskline && $nextTask->tasktrno == $dtask->tasktrno) {
                                $nextStart = strtotime($nextTask->createdate);
                                if ($nextStart > $dtaskDoned && $nextStart < $nextTaskStart) {
                                    $nextTaskStart = $nextStart;
                                }
                            }
                        }

                        // Find the most recent comment created after this task was completed
                        // but before the next task started
                        $closestComment = null;
                        $closestTimeDiff = PHP_INT_MAX;

                        foreach ($comments as $comment) {
                            if ($comment->tmline == $dtask->taskline && $comment->tmtrno == $dtask->tasktrno) {
                                $commentTime = strtotime($comment->createdate);

                                // Comment should be after task completion
                                if ($commentTime >= $dtaskDoned) {
                                    // And before the next task starts (if there is a next task)
                                    if ($commentTime <= $nextTaskStart) {
                                        // Find the comment closest to the completion time
                                        $timeDiff = $commentTime - $dtaskDoned;
                                        if ($timeDiff < $closestTimeDiff) {
                                            $closestTimeDiff = $timeDiff;
                                            $closestComment = $comment;
                                        }
                                    }
                                }
                            }
                        }

                        $matchingComment = $closestComment;
                    }

                    // Display the matching comment if found
                    if ($matchingComment) {
                        if (!$foundComment) {
                            $foundComment = true;
                        }

                        // Format comment date
                        $commentDate = !empty($matchingComment->createdate) ? date('m/d/Y', strtotime($matchingComment->createdate)) : '-';

                        // Comment label
                        $commentLabel = ($dtask->ischecker == 1) ? 'Comment / Checker' : 'Comment';

                        // Comment row
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '20', null, true, '2px dotted', 'L', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('&nbsp&nbsp' . $commentDate, '110', null, true, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col($commentLabel, '100', null, true, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col($matchingComment->clientname, '180', null, true, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col($matchingComment->cmnt, '240', null, true, '2px dotted', '', 'LT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '160', null, true, '2px dotted', '', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '160', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '130', null, true, '2px dotted', 'R', 'CT', $font, $fontsize, '', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }
                }
            }

            // Show total daily hours if there were daily tasks
            if ($foundDaily && $totalDailyHours > 0) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '20', null, true, '2px dotted', 'BL', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '110', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '100', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '180', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('TOTAL DAILY HOURS:', '240', null, true, '2px dotted', 'B', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col($totalDailyHours, '130', null, true, '2px dotted', 'BR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            } else if ($foundDaily && $totalDailyHours == 0) {
                // Handle case where daily tasks exist but have 0 hours
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '20', null, true, '2px dotted', 'BL', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '110', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '100', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '180', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('TOTAL DAILY HOURS:', '240', null, true, '2px dotted', 'B', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '160', null, true, '2px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('0.00 hrs', '130', null, true, '2px dotted', 'BR', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->col('', '50', null, true, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#757575');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
            $str .= '<br></br>';
        }

        // Footer
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '290', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '420', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class