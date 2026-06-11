<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class sales_report_monthly_by_sales_date
{
    public $modulename = 'Sales Report Monthly By Sales Date';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:3000px;max-width:3000px;';
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
        $companyid = $config['params']['companyid'];


        $fields = ['radioprint', 'start', 'end', 'dclientname', 'region', 'province', 'area', 'groupid', 'dagentname', 'radioposttype', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dclientname.lookupclass', 'rcustomer');
        data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
        data_set($col1, 'groupid.action', 'lookupclientgroupledger');
        data_set($col1, 'groupid.class', 'csgroup');
        data_set($col1, 'groupid.readonly', false);

        data_set(
            $col1,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );

        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Detailed', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Summary', 'value' => '1', 'color' => 'teal'],
            ]
        );


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);



        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as dwhname,
      '' as wh,
      '' as whname,
      '' as dwhref,
      '' as whref,
      '0'  as reporttype,
      '' as whnameref,
      '' as dclientname,
      '0' as clientid,
      '' as client,
      '' as clientname,
       '' as area,
    '' as region,
    '' as province,
    '' as dagentname,
    '' as agentname,
    '' as groupid,
    '' as agent,
    '0' as agentid,
      '0' as posttype";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];


        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function query_ericco_monthly($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype = $config['params']['dataparams']['posttype'];
        $clientid = $config['params']['dataparams']['clientid'];
        $client = $config['params']['dataparams']['client'];
        $area     = $config['params']['dataparams']['area'];
        $region   = $config['params']['dataparams']['region'];
        $province = $config['params']['dataparams']['province'];
        $group = $config['params']['dataparams']['groupid'];
        $agentid = $config['params']['dataparams']['agentid'];
        $agent = $config['params']['dataparams']['agent'];
        $filter = "";
        $filter2 = "";

        if ($client != '' && $clientid != 0) {
            $filter .= " and client.clientid='$clientid'";
        }

        if ($area != "") {
            $filter .= " and client.area = '$area'";
        }
        if ($region != "") {
            $filter .= " and client.region = '$region'";
        }
        if ($province != "") {
            $filter .= " and client.province = '$province'";
        }

        if ($group != "") {
            $filter .= " and client.groupid = '$group'";
        }

        if ($agent != "") {
            $filter .= " and client.agent = '$agent'";
        }




        switch ($posttype) {
            case 0: //Posted
                $query = " 
    select  month, groupid, clientname, sum(amount) as amount from (
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname,
        case when head.amount > 0 then head.amount
        else sum(stock.ext) end as amount
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join hsistock as stock on stock.trno = head.trno
        left join client on head.clientid = client.clientid
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'ch' $filter
        group by month, client.groupid, clientname, head.amount
        union all
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname,
        sum(stock.ext) as amount
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join cntnum as num2 on num2.svnum = head.trno
        left join glstock as stock on stock.trno = num2.trno
        left join client on head.clientid = client.clientid
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'on' $filter
        group by month, client.groupid, clientname

        ) as x 
         group by   month, groupid, clientname
    ";
                // var_dump($query);
                break;

            case 1: //Unposted
                $query = "
    select  month, groupid, clientname, sum(amount) as amount from (
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname,
        case when head.amount > 0 then head.amount
        else sum(stock.ext) end as amount
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join sistock as stock on stock.trno = head.trno
        left join client on head.client = client.client
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'ch' $filter
        group by month, client.groupid, clientname, head.amount
        union all
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname,
        sum(stock.ext) as amount
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join cntnum as num2 on num2.svnum = head.trno
        left join lastock as stock on stock.trno = num2.trno
        left join client on head.client = client.client
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'on' $filter
        group by month, client.groupid, clientname

        ) as x 
         group by  month, groupid, clientname
    ";
                break;

            case 2: //All
                $query = "

    select  month, groupid, clientname, sum(amount) as amount from (
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname, 
        case when head.amount > 0 then head.amount
        else sum(stock.ext) end as amount
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join hsistock as stock on stock.trno = head.trno
        left join client on head.clientid = client.clientid
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'ch'  $filter
        group by month, client.groupid, clientname, head.amount
        union all
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname, 
        sum(stock.ext) as amount 
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join cntnum as num2 on num2.svnum = head.trno
        left join glstock as stock on stock.trno = num2.trno
        left join client on head.clientid = client.clientid
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'on'   $filter
        group by month, client.groupid, clientname
        union all
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname, 
        case when head.amount > 0 then head.amount
        else sum(stock.ext) end as amount
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join sistock as stock on stock.trno = head.trno
        left join client on head.client = client.client
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'ch'   $filter
        group by month, client.groupid, clientname, head.amount
        union all
        select
        DATE_FORMAT(head.sdate1, '%Y-%m') as month,
        client.groupid,
        head.clientname, 
        sum(stock.ext) as amount
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join cntnum as num2 on num2.svnum = head.trno
        left join glhead as ghead on ghead.trno = num2.trno
        left join glstock as stock on stock.trno = ghead.trno
        left join client on head.client = client.client
        where date(head.sdate1) between '$start' and '$end'  and head.doc = 'on'   $filter
        group by month, client.groupid, clientname

        ) as x 
         group by   month, groupid, clientname
      ";
                // var_dump($query);
                break;
        }

        return $this->coreFunctions->openTable($query);
    }

    public function query_ericco_monthly_summary($config)
    {
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype = $config['params']['dataparams']['posttype'];
        $clientid = $config['params']['dataparams']['clientid'];
        $client   = $config['params']['dataparams']['client'];
        $area     = $config['params']['dataparams']['area'];
        $region   = $config['params']['dataparams']['region'];
        $province = $config['params']['dataparams']['province'];
        $group    = $config['params']['dataparams']['groupid'];
        $agentid  = $config['params']['dataparams']['agentid'];
        $agent    = $config['params']['dataparams']['agent'];
        $filter   = "";
        $filter2  = "";

        if ($client != '' && $clientid != 0) {
            $filter .= " and client.clientid='$clientid'";
        }
        if ($area != "") {
            $filter .= " and client.area = '$area'";
        }
        if ($region != "") {
            $filter .= " and client.region = '$region'";
        }
        if ($province != "") {
            $filter .= " and client.province = '$province'";
        }
        if ($group != "") {
            $filter .= " and client.groupid = '$group'";
        }
        if ($agent != "") {
            $filter .= " and client.agent = '$agent'";
        }

        switch ($posttype) {
            case 0: //Posted
                $query = "
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    case when head.amount > 0 then head.amount else sum(stock.ext) end as amount
                from glhead as head
                left join cntnum as num on num.trno = head.trno
                left join hsistock as stock on stock.trno = head.trno
                left join client on head.clientid = client.clientid
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'ch' $filter
                group by month, client.groupid, head.amount
                union all
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    sum(stock.ext) as amount
                from glhead as head
                left join cntnum as num on num.trno = head.trno
                left join cntnum as num2 on num2.svnum = head.trno
                left join glstock as stock on stock.trno = num2.trno
                left join client on head.clientid = client.clientid
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'on' $filter
                group by month, client.groupid
            ";
                // var_dump($query);
                break;

            case 1: //Unposted
                $query = "
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    case when head.amount > 0 then head.amount else sum(stock.ext) end as amount
                from lahead as head
                left join cntnum as num on num.trno = head.trno
                left join sistock as stock on stock.trno = head.trno
                left join client on head.client = client.client
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'ch' $filter
                group by month, client.groupid, head.amount
                union all
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    sum(stock.ext) as amount
                from lahead as head
                left join cntnum as num on num.trno = head.trno
                left join cntnum as num2 on num2.svnum = head.trno
                left join lastock as stock on stock.trno = num2.trno
                left join client on head.client = client.client
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'on' $filter
                group by month, client.groupid
            ";
                break;

            case 2: //All
                $query = "
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    case when head.amount > 0 then head.amount else sum(stock.ext) end as amount
                from glhead as head
                left join cntnum as num on num.trno = head.trno
                left join hsistock as stock on stock.trno = head.trno
                left join client on head.clientid = client.clientid
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'ch' $filter
                group by month, client.groupid, head.amount
                union all
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    sum(stock.ext) as amount
                from glhead as head
                left join cntnum as num on num.trno = head.trno
                left join cntnum as num2 on num2.svnum = head.trno
                left join glstock as stock on stock.trno = num2.trno
                left join client on head.clientid = client.clientid
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'on' $filter
                group by month, client.groupid
                union all
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    case when head.amount > 0 then head.amount else sum(stock.ext) end as amount
                from lahead as head
                left join cntnum as num on num.trno = head.trno
                left join sistock as stock on stock.trno = head.trno
                left join client on head.client = client.client
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'ch' $filter
                group by month, client.groupid, head.amount
                union all
                select
                    DATE_FORMAT(head.sdate1, '%Y-%m') as month,
                    client.groupid,
                    sum(stock.ext) as amount
                from lahead as head
                left join cntnum as num on num.trno = head.trno
                left join cntnum as num2 on num2.svnum = head.trno
                left join glhead as ghead on ghead.trno = num2.trno
                left join glstock as stock on stock.trno = ghead.trno
                left join client on head.client = client.client
                where date(head.sdate1) between '$start' and '$end' and head.doc = 'on' $filter
                group by month, client.groupid
            ";
                // var_dump($query);
                break;
        }

        return $this->coreFunctions->openTable($query);
    }



    public function reportDefault($config)
    {
        $companyid  = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $data = $this->reportplotting($config);

        return $data;
    }

    public function reportplotting($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case 0: //Detailed
                $result = $this->layout_monthly($config, $this->query_ericco_monthly($config));
                break;
            case 1: //Summary
                $result = $this->layout_monthly_summary($config, $this->query_ericco_monthly_summary($config));
                break;
        }

        return $result;
    }

    //ERICCO
    public function header_ericco($config)
    {
        $start = date("F Y", strtotime($config['params']['dataparams']['start']));
        $end = date("F Y", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $type_label = ($reporttype == 0) ? 'DETAILED' : 'SUMMARY';
        $center     = $config['params']['center'];
        $font = "TAHOMA";
        $layoutsize = 1000;
        $fontsize_title = "12";
        $fontsize = "10";
        $border = "1px solid ";
        $str = '';

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $title = ($start === $end)
            ? 'SALES REPORT FOR THE MONTH OF ' . strtoupper($start) . ' - ' . $type_label
            : 'SALES REPORT FOR THE MONTH OF ' . strtoupper($start) . ' TO ' . strtoupper($end) . ' - ' . $type_label;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($title, null, null, false, $border, '', 'L', $font, $fontsize_title, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($reporttype == 1) {
            $str .= $this->reporter->col('GROUP', 450, 0, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('CLIENTNAME', 450, 0, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('AMOUNT', 150, 0, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function layout_monthly($config, $result)
    {
        $start = date("F Y", strtotime($config['params']['dataparams']['start']));
        $end   = date("F Y", strtotime($config['params']['dataparams']['end']));


        $str = '';
        $layoutsize = 1000;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_ericco($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize    = "9";
        $groupidsize = "10";
        $border      = "1px solid ";
        $totalamount = 0;

        $grouped = [];
        foreach ($result as $data) {
            $month   = isset($data->month)   ? $data->month   : '';
            $groupid = isset($data->groupid) ? $data->groupid : '';
            $grouped[$month][$groupid][] = $data;
        }

        ksort($grouped);

        foreach ($grouped as $month => $groups) {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date('F Y', strtotime($month . '-01')), 600, null, false, $border, '', 'L', $font, $groupidsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            ksort($groups);

            foreach ($groups as $groupid => $rows) {


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                if ($groupid == '') {
                    $groupid = 'No Group';
                }
                $str .= $this->reporter->col($groupid, 600, null, false, $border, '', 'L', $font, $groupidsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                foreach ($rows as $data) {
                    $amount = isset($data->amount) ? $data->amount : 0;
                    $totalamount += $amount;

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(isset($data->clientname) ? $data->clientname : '', 450, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col(number_format($amount, 2), 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= '</br>';
            }
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL:', 450, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamount, 2), 150, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function layout_monthly_summary($config, $result)
    {
        $str = '';
        $layoutsize = 1000;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_ericco($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $totalamount = 0;

        // Group by groupid only
        $grouped = [];
        foreach ($result as $data) {
            $groupid = isset($data->groupid) ? $data->groupid : '';
            $grouped[$groupid][] = $data;
        }

        ksort($grouped);

        foreach ($grouped as $groupid => $rows) {

            // Sum amount per groupid
            $group_amount = 0;
            foreach ($rows as $row) {
                $group_amount += isset($row->amount) ? $row->amount : 0;
            }
            $totalamount += $group_amount;

            // Groupid row with amount
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            if ($groupid == '') {
                $groupid = 'No Group';
            }
            $str .= $this->reporter->col($groupid, 450, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($group_amount, 2), 150, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // Grand total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', 450, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamount, 2), 150, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class
