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

class sales_report_yearly_by_sales_date
{
    public $modulename = 'Sales Report Yearly By Sales Date';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:3000px;max-width:3000px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1000'];



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


        $fields = ['radioprint', 'year', 'dclientname', 'region', 'province', 'area', 'groupid', 'dagentname', 'radioposttype', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'year.type', 'lookup');
        data_set($col1, 'year.class', 'csyear sbccsreadonly');
        data_set($col1, 'year.lookupclass', 'lookupyear');
        data_set($col1, 'year.action', 'lookupyear');
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
      '' as year,
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


    public function query_ericco_annual_detailed($config)
    {
        $year = $config['params']['dataparams']['year'];
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

        $months = "
        SUM(CASE WHEN MONTH(sdate1) = 1  THEN amount_val ELSE 0 END) as jan,
        SUM(CASE WHEN MONTH(sdate1) = 2  THEN amount_val ELSE 0 END) as feb,
        SUM(CASE WHEN MONTH(sdate1) = 3  THEN amount_val ELSE 0 END) as mar,
        SUM(CASE WHEN MONTH(sdate1) = 4  THEN amount_val ELSE 0 END) as apr,
        SUM(CASE WHEN MONTH(sdate1) = 5  THEN amount_val ELSE 0 END) as may,
        SUM(CASE WHEN MONTH(sdate1) = 6  THEN amount_val ELSE 0 END) as jun,
        SUM(CASE WHEN MONTH(sdate1) = 7  THEN amount_val ELSE 0 END) as jul,
        SUM(CASE WHEN MONTH(sdate1) = 8  THEN amount_val ELSE 0 END) as aug,
        SUM(CASE WHEN MONTH(sdate1) = 9  THEN amount_val ELSE 0 END) as sep,
        SUM(CASE WHEN MONTH(sdate1) = 10 THEN amount_val ELSE 0 END) as oct,
        SUM(CASE WHEN MONTH(sdate1) = 11 THEN amount_val ELSE 0 END) as nov,
        SUM(CASE WHEN MONTH(sdate1) = 12 THEN amount_val ELSE 0 END) as december,
        SUM(amount_val) as total
    ";

        switch ($posttype) {
            case 0: //Posted
                $query = "
                select groupid, clientname, $months
                from (
                    select client.groupid, head.clientname, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join hsistock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                    union all
                    select client.groupid, head.clientname, head.sdate1,
                        sum(stock.ext) as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join glstock as stock on stock.trno = num2.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                ) as sub
                group by groupid, clientname
            ";
                break;

            case 1: //Unposted
                $query = "
                select groupid, clientname, $months
                from (
                    select client.groupid, head.clientname, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join sistock as stock on stock.trno = head.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                    union all
                    select client.groupid, head.clientname, head.sdate1,
                        sum(stock.ext) as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join lastock as stock on stock.trno = num2.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                ) as sub
                group by groupid, clientname
            ";
                break;

            case 2: //All
                $query = "
                select groupid, clientname, $months
                from (
                    select client.groupid, head.clientname, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join hsistock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                    union all
                    select client.groupid, head.clientname, head.sdate1,
                        sum(stock.ext) as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join glstock as stock on stock.trno = num2.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                    union all
                    select client.groupid, head.clientname, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join sistock as stock on stock.trno = head.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                    union all
                    select client.groupid, head.clientname, head.sdate1,
                        sum(stock.ext) as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join glhead as ghead on ghead.trno = num2.trno
                    left join glstock as stock on stock.trno = ghead.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.clientname, head.sdate1, head.amount
                ) as sub
                group by groupid, clientname
            ";
                // var_dump($query);
                break;
        }

        return $this->coreFunctions->openTable($query);
    }

    public function query_ericco_annual_summary($config)
    {
        $year     = $config['params']['dataparams']['year'];
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

        $months = "
        SUM(CASE WHEN MONTH(sdate1) = 1  THEN amount_val ELSE 0 END) as jan,
        SUM(CASE WHEN MONTH(sdate1) = 2  THEN amount_val ELSE 0 END) as feb,
        SUM(CASE WHEN MONTH(sdate1) = 3  THEN amount_val ELSE 0 END) as mar,
        SUM(CASE WHEN MONTH(sdate1) = 4  THEN amount_val ELSE 0 END) as apr,
        SUM(CASE WHEN MONTH(sdate1) = 5  THEN amount_val ELSE 0 END) as may,
        SUM(CASE WHEN MONTH(sdate1) = 6  THEN amount_val ELSE 0 END) as jun,
        SUM(CASE WHEN MONTH(sdate1) = 7  THEN amount_val ELSE 0 END) as jul,
        SUM(CASE WHEN MONTH(sdate1) = 8  THEN amount_val ELSE 0 END) as aug,
        SUM(CASE WHEN MONTH(sdate1) = 9  THEN amount_val ELSE 0 END) as sep,
        SUM(CASE WHEN MONTH(sdate1) = 10 THEN amount_val ELSE 0 END) as oct,
        SUM(CASE WHEN MONTH(sdate1) = 11 THEN amount_val ELSE 0 END) as nov,
        SUM(CASE WHEN MONTH(sdate1) = 12 THEN amount_val ELSE 0 END) as december,
        SUM(amount_val) as total
    ";

        switch ($posttype) {
            case 0: //Posted
                $query = "
                select groupid, $months
                from (
                    select client.groupid, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join hsistock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.sdate1, head.amount
                    union all
                    select client.groupid, head.sdate1,
                        sum(stock.ext) as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join glstock as stock on stock.trno = num2.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.sdate1, head.amount
                ) as sub
                group by groupid
            ";
                break;

            case 1: //Unposted
                $query = "
                select groupid, $months
                from (
                    select client.groupid, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join sistock as stock on stock.trno = head.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.sdate1, head.amount
                    union all
                    select client.groupid, head.sdate1,
                        sum(stock.ext) as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join lastock as stock on stock.trno = num2.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.sdate1, head.amount
                ) as sub
                group by groupid
            ";
                break;

            case 2: //All
                $query = "
                select groupid, $months
                from (
                    select client.groupid, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join hsistock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.sdate1, head.amount
                    union all
                    select client.groupid, head.sdate1,
                        sum(stock.ext) as amount_val
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join glstock as stock on stock.trno = num2.trno
                    left join client on head.clientid = client.clientid
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.sdate1, head.amount
                    union all
                    select client.groupid, head.sdate1,
                        case when head.amount > 0 then head.amount else sum(stock.ext) end as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join sistock as stock on stock.trno = head.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'ch' $filter
                    group by client.groupid, head.sdate1, head.amount
                    union all
                    select client.groupid, head.sdate1,
                        sum(stock.ext) as amount_val
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join cntnum as num2 on num2.svnum = head.trno
                    left join glhead as ghead on ghead.trno = num2.trno
                    left join glstock as stock on stock.trno = ghead.trno
                    left join client on head.client = client.client
                    where YEAR(head.sdate1) = '$year' and head.doc = 'on' $filter
                    group by client.groupid, head.sdate1, head.amount
                ) as sub
                group by groupid
            ";
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case 0: //Detailed
                $result = $this->layout_annual_detailed($config, $this->query_ericco_annual_detailed($config));
                break;
            case 1: //Summary
                $result = $this->layout_annual_summary($config, $this->query_ericco_annual_summary($config));
                break;
        }


        return $result;
    }

    //ERICCO
    public function header_ericco_annual($config)
    {
        $year           = $config['params']['dataparams']['year'];
        $center         = $config['params']['center'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $type_label = ($reporttype == 0) ? 'DETAILED' : 'SUMMARY';
        $font           = "TAHOMA";
        $layoutsize     = 1500;
        $fontsize_title = "12";
        $fontsize       = "10";
        $border         = "1px solid ";
        $str            = '';
        $months         = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];

        $qry = "select name, address, tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ANNUAL SALES REPORT FOR THE YEAR OF ' . $year . ' - ' . $type_label, null, null, false, $border, '', 'L', $font, $fontsize_title, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($reporttype == 0) {
            $str .= $this->reporter->col('CLIENTNAME', 300, 0, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('GROUP', 300, 0, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        }
        foreach ($months as $m) {
            $str .= $this->reporter->col($m, 90, 0, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('TOTAL', 120, 0, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function layout_annual_detailed($config, $result)
    {
        $year = $config['params']['dataparams']['year'];

        $str = '';
        $layoutsize = 1500;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_ericco_annual($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $grand_total  = 0;
        $months       = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'december'];
        $grand_totals = array_fill_keys($months, 0);


        $grouped = [];
        foreach ($result as $data) {
            $groupid = isset($data->groupid) ? $data->groupid : '';
            $grouped[$groupid][] = $data;
        }
        ksort($grouped);


        foreach ($grouped as $groupid => $rows) {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            if ($groupid == '') {
                $groupid = 'NO GROUP';
            }
            $str .= $this->reporter->col($groupid, 1500, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            foreach ($rows as $data) {
                $clientname = isset($data->clientname) ? $data->clientname : '';
                $row_total  = isset($data->total)      ? $data->total      : 0;
                $grand_total += $row_total;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($clientname, 300, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                foreach ($months as $m) {
                    $val = isset($data->$m) ? $data->$m : 0;
                    $grand_totals[$m] += $val;
                    $display = ($val == 0) ? '-' : number_format($val, 2);  // ← show '-' if zero
                    $str .= $this->reporter->col($display, 90, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                }
                $str .= $this->reporter->col(number_format($row_total, 2), 120, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $str .= '</br>';
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', 300, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        foreach ($months as $m) {
            $display = ($grand_totals[$m] == 0) ? '-' : number_format($grand_totals[$m], 2);
            $str .= $this->reporter->col($display, 90, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        }
        $grand_display = ($grand_total == 0) ? '-' : number_format($grand_total, 2);
        $str .= $this->reporter->col($grand_display, 120, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function layout_annual_summary($config, $result)
    {
        $year = $config['params']['dataparams']['year'];

        $str = '';
        $layoutsize = 1500;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_ericco_annual($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $grand_total  = 0;
        $months       = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'december'];
        $grand_totals = array_fill_keys($months, 0);


        foreach ($result as $data) {
            $groupid   = isset($data->groupid) ? $data->groupid : '';
            $row_total = isset($data->total)   ? $data->total   : 0;
            $grand_total += $row_total;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($groupid, 300, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            foreach ($months as $m) {
                $val = isset($data->$m) ? $data->$m : 0;
                $grand_totals[$m] += $val;
                $display = ($val == 0) ? '-' : number_format($val, 2);
                $str .= $this->reporter->col($display, 90, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            }
            $str .= $this->reporter->col(number_format($row_total, 2), 120, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= '</br>';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', 300, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        foreach ($months as $m) {
            $display = ($grand_totals[$m] == 0) ? '-' : number_format($grand_totals[$m], 2);
            $str .= $this->reporter->col($display, 90, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        }
        $grand_display = ($grand_total == 0) ? '-' : number_format($grand_total, 2);
        $str .= $this->reporter->col($grand_display, 120, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class
