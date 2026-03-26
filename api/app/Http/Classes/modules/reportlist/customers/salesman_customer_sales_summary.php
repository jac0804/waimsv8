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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use DateTime;


class salesman_customer_sales_summary
{
    public $modulename = 'Salesman Customer Sales Summary';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'area', 'dagentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dclientname.lookupclass', 'rcustomer');
        data_set($col1, 'dagentname.label', 'Salesman');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1,  'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
            'default' as print,
            adddate(left(now(),10),-30) as start,
            left(now(),10) as end,
            '' as client,
            '' as clientname,
            '' as dclientname,
            '' as agentid,
            '' as agentname,
            '' as dagentname,
            '' as agent,'' as area ";

        return $this->coreFunctions->opentable($paramstr);
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_query($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $agent    = $config['params']['dataparams']['agent'];
        $client    = $config['params']['dataparams']['client'];
        $area = $config['params']['dataparams']['area'];
        $filter = "";
        if ($agent != "") {
            $filter .= " and agent.client='$agent'";
        }

        if ($client != "") {
            $filter .= " and cl.client='$client'";
        }

        if ($area != "") {
            $filter .= " and cl.area='" . $area . "'";
        }

        $query = "select clientname,area,sum(netsales) as netsales, sum(backtotal) as backtotal

                    from (
                    select cl.clientname, if(cl.area='', 'No Area',cl.area) as area, sum(stock.ext) as netsales,
                           0 as backtotal
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join client as cl on cl.clientid=head.clientid
                    left join client as agent on agent.clientid=head.agentid
                    where head.doc='sj'  and date(head.dateid) between '$start' and '$end'   $filter
                    group by cl.clientname,  cl.area

                    union all

                    select cl.clientname, if(cl.area='', 'No Area',cl.area) as area, sum(stock.ext) as netsales,
                            0 as backtotal
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join client as cl on cl.client=head.client
                    left join client as agent on agent.client=head.agent
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'  $filter
                    group by cl.clientname, cl.area) as s
                    group by clientname,area order by area";
        // var_dump($query);
        return $query;
    }

    private function default_displayHeader($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '12';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();


        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sales Summary Report', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

        $startdate = $start;
        $startt = new DateTime($startdate);
        $start = $startt->format('m/d/Y');

        $enddate = $end;
        $endd = new DateTime($enddate);
        $end = $endd->format('m/d/Y');

        $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('PARTICULAR', '395', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('NET SALES', '195', '', '', $border, 'LTB', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '5', '', '', $border, 'TB', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('BACK TOTAL', '195', '', '', $border, 'LTB', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '5', '', '', $border, 'TB', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('SALES W/ BACK', '195', '', '', $border, 'LTB', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '5', '', '', $border, 'TBR', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br>';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '395', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';


        $result = $this->reportDefault($config);

        $count = 30;
        $page = 34;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $areas = '';
        $area_net = 0;
        $area_back = 0;
        $grand_net = 0;
        $grand_back = 0;

        // $page = 2;
        // $count = 2;

        foreach ($result as $key => $data) {

            // Kung bagong area na (ibang value ng $data->area)
            if ($areas != $data->area) {

                // I-print muna ang area total ng previous area (kung meron)
                if ($areas != '') {

                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '5', null, false, $border, 'TLB', 'L', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->col('AREA TOTAL', '395', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->col(number_format($area_net, 2), '195', null, false, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->col(number_format($area_back, 2), '195', null, false, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->col(number_format($area_net + $area_back, 2), '195', null, false, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->col('', '5', null, false, $border, 'TBR', 'R', $font, $font_size, 'B', '', '', '5px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->addline();

                    // add area totals to grand total
                    $grand_net += $area_net;
                    $grand_back += $area_back;

                    // reset area totals
                    $area_net = 0;
                    $area_back = 0;



                    //space bago magheader
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->col('&nbsp;', '395', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
                    $str .= $this->reporter->endrow();
                }


                // I-print ang bagong area header
                $areas = $data->area;
                $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '5', null, false, $border, 'TLB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col(">> " . strtoupper($areas), '395', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '195', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '195', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '195', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '5', null, false, $border, 'TBR', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->endrow();
                // $str .= $this->reporter->endtable();
            }

            // I-print ang bawat client row sa loob ng area
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '5', null, false,  $border, 'L', 'L', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->col($data->clientname, '395', null, false,  $border, '', 'L', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->col(number_format($data->netsales, 2), '195', null, false,  $border, 'L', 'R', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->col('', '5', null, false,  $border, '', 'L', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->col(number_format($data->backtotal, 2), '195', null, false,  $border, 'L', 'R', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->col('', '5', null, false,  $border, '', 'L', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->col(number_format($data->netsales + $data->backtotal, 2), '195', null, false,  $border, 'L', 'R', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->col('', '5', null, false,  $border, 'R', 'L', $font, $font_size, '', '', '', '2px');
            $str .= $this->reporter->endrow();

            // accumulate area totals
            $area_net += $data->netsales;
            $area_back += $data->backtotal;

            $this->reporter->linecounter++;

            // pagination  (optional)
            // if ($this->reporter->linecounter == $page) {
            //     // $str .= $this->reporter->endtable();

            //     $str .= $this->reporter->page_break();
            //     $str .= $this->default_displayHeader($config);
            //      $str .= $this->reporter->begintable($layoutsize);
            //     $page = $page + $count;
            // }

            if ($this->reporter->linecounter >= $count) {

                // close current page
                // $str .= $this->reporter->endrow();
                // $str .= $this->reporter->endtable();
                // $str .= $this->reporter->begintable('1000');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('&nbsp;', '5', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->col('&nbsp;', '395', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->col('&nbsp;', '195', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->col('&nbsp;', '5', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->col('&nbsp;', '195', null, false, $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->col('&nbsp;', '5', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->col('&nbsp;', '195', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->col('&nbsp;', '5', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // reset line counter
                $this->reporter->linecounter = 0;

                // print new header
                $str .= $this->default_displayHeader($config);
                $str .= $this->reporter->begintable($layoutsize);

                // reprint current agent name
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '5', null, false, $border, 'TLB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col(">> " . strtoupper($areas), '395', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '195', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '195', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '195', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->col('', '5', null, false, $border, 'TBR', 'L', $font, $font_size + 1, 'B', '', '', '5px');
                $str .= $this->reporter->endrow();
            }
        }

        // Pag natapos ang loop, i-print ang last area total
        if ($areas != '') {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col('', '5', null, false, $border, 'TLB', 'L', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->col('AREA TOTAL', '395', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->col(number_format($area_net, 2), '195', null, false, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->col(number_format($area_back, 2), '195', null, false, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->col(number_format($area_net + $area_back, 2), '195', null, false, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->col('', '5', null, false, $border, 'TBR', 'R', $font, $font_size, 'B', '', '', '5px');
            $str .= $this->reporter->endrow();

            // add last area to grand total
            $grand_net += $area_net;
            $grand_back += $area_back;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '395', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '195', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '5', null, false,  '', '',  'L', $font, '3', '', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, true, $border, 'TLB', 'L', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->col('GRAND TOTAL', '395', null, true, $border, 'TB', 'L', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->col(number_format($grand_net, 2), '195', null, true, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '5', null, true, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->col(number_format($grand_back, 2), '195', null, true, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '5', null, true, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->col(number_format($grand_net + $grand_back, 2), '195', null, true, $border, 'TLB', 'R', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '5', null, true, $border, 'TBR', 'R', $font, $font_size, 'B', '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class