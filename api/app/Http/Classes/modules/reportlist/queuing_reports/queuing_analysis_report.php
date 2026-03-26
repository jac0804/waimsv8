<?php

namespace App\Http\Classes\modules\reportlist\queuing_reports;

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
use Illuminate\Support\Facades\URL;

class queuing_analysis_report
{
    public $modulename = 'Queuing Analysis Report';
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
        $fields = ['radioprint', 'start', 'end', 'username'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'username.lookupclass', 'lookupusers2');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
      'default' as print,
      '" . $this->othersClass->getCurrentDate() . "' as start,
      '" . $this->othersClass->getCurrentDate() . "' as end,
      '' as dclientname,
      '' as username,
      '" . $center . "' as center,
      '" . $dcenter[0]->dcentername . "' as dcentername,
      '" . $dcenter[0]->name . "' as centername,
      '' as prefix
      ";
        return $this->coreFunctions->opentable($paramstr);
    }


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

        return $this->reportDefault_Layout($config);
    }


    public function query_ticket_stat($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }


        $query = "select 
       sum(totaltickets) as totaltickets,
       sum(totalserved) as totalserved,
       sum(totalcancelled) as totalcancelled,
       sum(totalpwd) as totalpwd,
       sum(totalregular) as totalregular
       from (
           select 
           sum(case when cs.isdone = 1 then 1 else 0 end) + sum(case when cs.iscancel = 1 then 1 else 0 end) as totaltickets,
           sum(case when cs.isdone = 1 then 1 else 0 end) as totalserved,
           sum(case when cs.iscancel = 1 then 1 else 0 end) as totalcancelled,
           sum(case when cs.ispwd = 1 then 1 else 0 end) as totalpwd,
           sum(case when cs.ispwd = 0 then 1 else 0 end) as totalregular
           from currentservice as cs
           left join reqcategory as rc on rc.line = cs.serviceline
           where date(cs.dateid) between '$start' and '$end' $filter

           union all

           select 
           sum(case when cs.isdone = 1 then 1 else 0 end) + sum(case when cs.iscancel = 1 then 1 else 0 end) as totaltickets,
           sum(case when cs.isdone = 1 then 1 else 0 end) as totalserved,
           sum(case when cs.iscancel = 1 then 1 else 0 end) as totalcancelled,
           sum(case when cs.ispwd = 1 then 1 else 0 end) as totalpwd,
           sum(case when cs.ispwd = 0 then 1 else 0 end) as totalregular
           from hcurrentservice as cs
           left join reqcategory as rc on rc.line = cs.serviceline
           where date(cs.dateid) between '$start' and '$end' $filter
       ) as x
       ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    public function query_service_breakdown($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }


        $query2 = "select service,
       sum(totalserved) as totalserved,
       sum(totalcancelled) as totalcancelled,
       sum(avgwait)  as avgwait
        from (
            select rc.code as service,
                sum(case when cs.isdone = 1 then 1 else 0 end) as totalserved,
                sum(case when cs.iscancel = 1 then 1 else 0 end) as totalcancelled,
                case when cs.isdone = 1 then ifnull(timestampdiff(minute, cs.dateid, cs.startdate), 0) else 0 end as avgwait
            from currentservice as cs
            left join reqcategory as rc on rc.line = cs.serviceline
            where date(cs.dateid) between '$start' and '$end' $filter
            group by rc.code, cs.dateid, cs.startdate, cs.isdone

            union all

            select rc.code as service,
                sum(case when cs.isdone = 1 then 1 else 0 end) as totalserved,
                sum(case when cs.iscancel = 1 then 1 else 0 end) as totalcancelled,
                case when cs.isdone = 1 then ifnull(timestampdiff(minute, cs.dateid, cs.startdate), 0) else 0 end as avgwait
            from hcurrentservice as cs
            left join reqcategory as rc on rc.line = cs.serviceline
            where date(cs.dateid) between '$start' and '$end' $filter
            group by rc.code, cs.dateid, cs.startdate, cs.isdone
        ) as x

        group by service
        order by service";
        // var_dump($query2);
        return $this->coreFunctions->opentable($query2);
    }

    public function query_user_performance($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }


        $query3 = "select users, sum(served) as served, sum(total) as total
            from (
                select users, case when isdone = 1 then 1 else 0 end as served,
               case when isdone = 1 then ifnull(timestampdiff(minute, startdate, enddate), 0) else 0 end as total
                from currentservice
                where date(dateid) between '$start' and '$end'  $filter 
                group by users,startdate, enddate, isdone

                union all

                select users, case when isdone = 1 then 1 else 0 end as served,
                case when isdone = 1 then ifnull(timestampdiff(minute, startdate, enddate), 0) else 0 end as total
                from hcurrentservice
                where date(dateid) between '$start' and '$end'   $filter
                group by users,startdate, enddate, isdone
            ) as x
            group by users
            order by users";
        // var_dump($query3);
        return $this->coreFunctions->opentable($query3);
    }

    public function query_cancel_ticket($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }


        $query4 = "select code, sum(total) as total from (
        select rc.code, sum(case when iscancel = 1 then 1 else 0 end) as total
        from currentservice as cs
        left join reqcategory as rc on rc.line = cs.serviceline
        where iscancel = 1 and date(cs.dateid) between '$start' and '$end' $filter
        group by rc.code
        union all

        select rc.code, sum(case when iscancel = 1 then 1 else 0 end) as total
        from hcurrentservice as cs
        left join reqcategory as rc on rc.line = cs.serviceline
        where iscancel = 1 and date(cs.dateid) between '$start' and '$end' $filter
        group by rc.code

        ) as x
        group by code
        order by code";
        // var_dump($query4);
        return $this->coreFunctions->opentable($query4);
    }

    public function query_peak_hour($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }

        $query5 = "select 'Highest Traffic' as metric,
       max(round(tickets / ifnull(total_hours, 0))) as tickets_per_hour
        from (
            select hr,
                sum(tickets) as tickets,
                sum(total_hours) as total_hours
            from (
                select hour(cs.dateid) as hr,
                    count(line) as tickets,
                    sum(timestampdiff(hour, cs.startdate, cs.enddate)) as total_hours
                from currentservice as cs
                where (cs.isdone = 1 ) and date(cs.dateid) between '$start' and '$end' $filter
                group by hour(cs.dateid)

                union all

                select hour(cs.dateid) as hr,
                    count(line) as tickets,
                    sum(timestampdiff(hour, cs.startdate, cs.enddate)) as total_hours
                from hcurrentservice as cs
                where (cs.isdone = 1 ) and date(cs.dateid) between '$start' and '$end' $filter
                group by hour(cs.dateid)
            ) as raw
            group by hr
        ) as combined

        union all

        select 'Lowest Traffic' as metric,
            min(round(tickets / ifnull(total_hours, 0))) as tickets_per_hour
        from (
            select hr,
                sum(tickets) as tickets,
                sum(total_hours) as total_hours
            from (
                select hour(cs.dateid) as hr,
                    count(line) as tickets,
                    sum(timestampdiff(hour, cs.startdate, cs.enddate)) as total_hours
                from currentservice as cs
                where (cs.isdone = 1 ) and date(cs.dateid) between '$start' and '$end' $filter
                group by hour(cs.dateid)

                union all

                select hour(cs.dateid) as hr,
                    count(line) as tickets,
                    sum(timestampdiff(hour, cs.startdate, cs.enddate)) as total_hours
                from hcurrentservice as cs
                where (cs.isdone = 1 ) and date(cs.dateid) between '$start' and '$end' $filter
                group by hour(cs.dateid)
            ) as raw
            group by hr
        ) as x";

        return $this->coreFunctions->opentable($query5);
    }

    public function query_peak_traffic_time_slot($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }

        $query6 = "
        select timeslot, tickets_generated
        from (
            select timeslot, 
                concat(tickets_per_hour, ' (Highest)') as tickets_generated,
                1 as sortorder
            from (
                select concat(m, ':00 - ', m + 1, ':00') as timeslot,
                    sum(tickets) as tickets_per_hour
                from (
                    select DATE_FORMAT(cs.dateid, '%H') as m, count(line) as tickets
                    from currentservice as cs
                    where cs.isdone = 1
                    and date(cs.dateid) between '$start' and '$end' $filter
                    group by DATE_FORMAT(cs.dateid, '%H')
                    union all
                    select DATE_FORMAT(cs.dateid, '%H') as m, count(line) as tickets
                    from hcurrentservice as cs
                    where cs.isdone = 1
                    and date(cs.dateid) between '$start' and '$end' $filter
                    group by DATE_FORMAT(cs.dateid, '%H')
                ) as T
                group by m
                order by tickets_per_hour desc
                limit 1
            ) as highest

            union all

            select timeslot,
                concat(tickets_per_hour, ' (Lowest)') as tickets_generated,
                2 as sortorder
            from (
                select concat(m, ':00 - ', m + 1, ':00') as timeslot,
                    sum(tickets) as tickets_per_hour
                from (
                    select DATE_FORMAT(cs.dateid, '%H') as m, count(line) as tickets
                    from currentservice as cs
                    where cs.isdone = 1
                    and date(cs.dateid) between '$start' and '$end' $filter
                    group by DATE_FORMAT(cs.dateid, '%H')
                    union all
                    select DATE_FORMAT(cs.dateid, '%H') as m, count(line) as tickets
                    from hcurrentservice as cs
                    where cs.isdone = 1
                    and date(cs.dateid) between '$start' and '$end' $filter
                    group by DATE_FORMAT(cs.dateid, '%H')
                ) as T
                group by m
                order by tickets_per_hour asc
                limit 1
            ) as lowest

            union all

            select 'Other Hours' as timeslot,
                concat(min(tickets_per_hour), ' - ', max(tickets_per_hour), ' (Range)') as tickets_generated,
                3 as sortorder
            from (
                select m, sum(tickets) as tickets_per_hour
                from (
                    select DATE_FORMAT(cs.dateid, '%H') as m, count(line) as tickets
                    from currentservice as cs
                    where cs.isdone = 1
                    and date(cs.dateid) between '$start' and '$end' $filter
                    group by DATE_FORMAT(cs.dateid, '%H')
                    union all
                    select DATE_FORMAT(cs.dateid, '%H') as m, count(line) as tickets
                    from hcurrentservice as cs
                    where cs.isdone = 1
                    and date(cs.dateid) between '$start' and '$end' $filter
                    group by DATE_FORMAT(cs.dateid, '%H')
                ) as T
                group by m
                having sum(tickets) != (select max(s) from (
                        select DATE_FORMAT(cs.dateid, '%H') as m, sum(case when cs.isdone = 1 then 1 else 0 end) as s
                        from currentservice as cs
                        where date(cs.dateid) between '$start' and '$end' $filter
                        group by DATE_FORMAT(cs.dateid, '%H')
                        union all
                        select DATE_FORMAT(cs.dateid, '%H') as m, sum(case when cs.isdone = 1 then 1 else 0 end) as s
                        from hcurrentservice as cs
                        where date(cs.dateid) between '$start' and '$end' $filter
                        group by DATE_FORMAT(cs.dateid, '%H')
                    ) as mx)
                and sum(tickets) != (select min(s) from (
                        select DATE_FORMAT(cs.dateid, '%H') as m, sum(case when cs.isdone = 1 then 1 else 0 end) as s
                        from currentservice as cs
                        where date(cs.dateid) between '$start' and '$end' $filter
                        group by DATE_FORMAT(cs.dateid, '%H')
                        union all
                        select DATE_FORMAT(cs.dateid, '%H') as m, sum(case when cs.isdone = 1 then 1 else 0 end) as s
                        from hcurrentservice as cs
                        where date(cs.dateid) between '$start' and '$end' $filter
                        group by DATE_FORMAT(cs.dateid, '%H')
                    ) as mn)
            ) as others
        ) as final
        order by sortorder
        ";

        return $this->coreFunctions->opentable($query6);
    }


    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
        $end = date('m/d/Y', strtotime($config['params']['dataparams']['end']));
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);


        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Queuing Analysis Report', null, null, false, null, null, 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('From: ' . $start . ' to ' . $end, null, null, false, '3px solid', '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $str = '';
        $layoutsize = 1000;
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->query_ticket_stat($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Ticket Statistics', '1000', '50', '#DED1D1FF', '2px solid', '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Metric', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Value', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $row = $result[0];
        $metrics = ['Total Tickets', 'Served Tickets', 'Cancelled Tickets', 'Regular Customer', 'Priority Customer'];
        $values  = [$row->totaltickets, $row->totalserved, $row->totalcancelled, $row->totalregular, $row->totalpwd];

        foreach ($metrics as $index => $metric) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($metric, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($values[$index], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, true, '1px solid', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';

        //Service Breakdown
        $result2 = $this->query_service_breakdown($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Service Breakdown', '1000', '50', '#DED1D1FF', '2px solid', '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Service', '250', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Served', '250', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cancelled', '250', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AvgWait(mins)', '250', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $served = 0;
        $cancelled = 0;
        $avgtotal = 0;
        $avg = 0;
        $countservice = 0;
        foreach ($result2 as $row2) {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row2->service, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row2->totalserved, '250', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row2->totalcancelled, '250', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row2->avgwait, '250', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $served += $row2->totalserved;
            $cancelled += $row2->totalcancelled;
            $avgtotal += $row2->avgwait;
            if ($row2->avgwait != 0) {
                $countservice++;
            }
        }
        //total row 
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '10', '', $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($served, '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($cancelled, '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($countservice > 0 ? round($avgtotal / $countservice, 0) . '(avg)' : '0(avg)', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';

        //User Performance
        $result3 = $this->query_user_performance($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('User Performance', '1000', '50', '#DED1D1FF', '2px solid', '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('User', '334', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Ticket Served', '333', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Avg Service Time(mins)', '333', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $totalserved = 0;
        $totalavg = 0;
        $avg_user = 0;
        $countavg = 0;
        foreach ($result3 as $row3) {
            $avg_user = ($row3->served > 0) ? round($row3->total / $row3->served, 0) : 0;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row3->users, '334', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row3->served, '333', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($avg_user, '333', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalserved += $row3->served;
            $totalavg += $avg_user;
            if ($avg_user != 0) {
                $countavg++;
            }
        }

        //total row 
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '10', '', $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '334', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalserved, '333', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(round($totalavg / $countavg, 0) . '(avg)', '333', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';


        //Cancel Ticket Per Service 
        $result4 = $this->query_cancel_ticket($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cancel Ticket Per Service Type', '1000', '50', '#DED1D1FF', '2px solid', '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Service', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cancel Tickets', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $totalcancelled = 0;
        foreach ($result4 as $row4) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row4->code, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row4->total, '500', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalcancelled += $row4->total;
        }
        //total row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '10', '', $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '500', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalcancelled, '500', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';

        //Peal Hour Analysis
        $result5 = $this->query_peak_hour($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Peak Hour Analysis', '1000', '50', '#DED1D1FF', '2px solid', '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Metric', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Tickets/Hour', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        foreach ($result5 as $row5) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row5->metric, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row5->tickets_per_hour, '500', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, true, '1px solid', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';


        //Peak Traffic Time Slot
        $result6 = $this->query_peak_traffic_time_slot($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Peak Traffic Time Slot', '1000', '50', '#DED1D1FF', '2px solid', '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time Slot (Hour)', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Tickets Generated', '500', null, '#F2E9E9FF', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        foreach ($result6 as $row6) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row6->timeslot, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row6->tickets_generated, '500', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, true, '1px solid', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';


        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class
