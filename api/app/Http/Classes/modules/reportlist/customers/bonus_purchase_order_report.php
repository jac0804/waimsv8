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


class bonus_purchase_order_report
{
    public $modulename = 'Bonus Purchase Order Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
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
        $fields = ['radioprint', 'radiodatetype','year', 'dclientname', 'area', 'dagentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radiodatetype.label', 'Coverage Period');

        data_set($col1, 'radiodatetype.options', array(
            ['label' => 'January to June', 'value' => '0', 'color' => 'orange'],
            ['label' => 'July to December', 'value' => '1', 'color' => 'orange']
        ));
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
            '0' as transdate,
            '" . date('Y') . "' as year,
            '' as client,
            '' as clientname,
            '' as dclientname,
            '' as agentid,
            '' as agentname,
            '' as dagentname,
            '' as agent,
            '' as area"
            ;

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
        $agent    = $config['params']['dataparams']['agent'];
        $client    = $config['params']['dataparams']['client'];
        $area = $config['params']['dataparams']['area'];
        $dateType = $config['params']['dataparams']['transdate'];
        $year = $config['params']['dataparams']['year'];
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

        $start = '';
        $end = '';

        if ($dateType == '0') {
            $start = $year . '-01-01';
            $end   = $year . '-06-30';
        } else {
            $start = $year . '-07-01';
            $end   = $year . '-12-31';
        }

        $query = " select clientname,area,province,sum(amount) as amount
                    from (
                    select cl.clientname, if(cl.area='', 'No Area',cl.area) as area, if(cl.province='', 'No Province',cl.province) as province,  sum(stock.ext) as amount
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join client as cl on cl.clientid=head.clientid
                    left join client as agent on agent.clientid=head.agentid
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'   $filter
                    group by cl.clientname, area, province

                    union all

                    select cl.clientname, if(cl.area='', 'No Area',cl.area) as area, if(cl.province='', 'No Province',cl.province) as province,  sum(stock.ext) as amount
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join client as cl on cl.client=head.client
                    left join client as agent on agent.client=head.agent
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'   $filter
                    group by cl.clientname, area, province ) as s
                    group by clientname,area, province order by area";
        // var_dump($query);
        return $query;
    }

    private function default_displayHeader($config)
    {
        $border = '1px solid';
        $font = 'CALIBRI';
        $font_size = '14';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $dateType = $config['params']['dataparams']['transdate'];
        $year = $config['params']['dataparams']['year'];

        $str = '';
        $layoutsize = '1100';

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->letterhead($center, $username, $config);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

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

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BONUS PURCHASE ORDER REPORT', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

         if ($dateType == '0') {
           $str .= $this->reporter->col('From 01/01/'. $year. ' to 06/30/'. $year, null, null, '', $border, '', 'C', $font, '12', '', '', '');
        } else {
            $str .= $this->reporter->col('From 07/01/'. $year . ' to 12/31/'. $year, null, null, '', $border, '', 'C', $font, '12', '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('C U S T O M E R', '700', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('CARTONS', '200', '', '', $border, 'LTBR', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br>';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '100', null, false,  '', '',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '700', null, false,  '', '',  'L', $font, '4', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $border = '1px solid';
        $font = 'CALIBRI';
        $font_size = '14';
        $result = $this->reportDefault($config);
        $count = 38;
        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $areaTotalPrinted = false;

        $str = '';
        $layoutsize = '1100';
        $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '25px;margin-top:0px;margin-left:50px;margin-right:35px;');
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $areas = '';
        $tlcarton = 0;
        $grand_cr = 0;

        // $page = 2;
        // $count = 2;

        foreach ($result as $key => $data) {

            // Kung bagong area na (ibang value ng $data->area)
            if ($areas != $data->area) {

                // I-print muna ang area total ng previous area (kung meron)
                if ($areas != ''  && !$areaTotalPrinted) {
                    $this->reporter->linecounter++;
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', null, false, $border, 'TLB', 'L', $font, $font_size, 'B', '',  '5px');
                    $str .= $this->reporter->col('AREA TOTAL', '750', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '5px');
                    $str .= $this->reporter->col(number_format($tlcarton, 2), '200', null, false, $border, 'TLBR', 'R', $font, $font_size, 'B', '', '5px');
                    $str .= $this->reporter->endrow();
                    $this->reporter->linecounter++;

                    // add area totals to grand total
                    $grand_cr += $tlcarton;

                    // // reset area totals
                    $tlcarton = 0;

                    //space bago magheader
                    $str .= $this->reporter->startrow();
                    $this->reporter->linecounter += 0.39;
                    $str .= $this->reporter->col('&nbsp;', '100', null, false,  '', '',  'L', $font, '4', '', '',  '');
                    $str .= $this->reporter->col('&nbsp;', '700', null, false,  '', '',  'L', $font, '4', '', '',  '');
                    $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '4', '', '',  '');
                    $str .= $this->reporter->endrow();
                }

                $areaTotalPrinted = false;

                // I-print ang bagong area header
                $areas = $data->area;
                $province = $data->province;
                $this->reporter->linecounter++;
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('>>', '100', null, false, $border, 'TLB', 'C', $font, $font_size + 1, 'B', '', '5px');
                $str .= $this->reporter->col(''.strtoupper($areas).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.strtoupper($province), '700', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '5px');
                $str .= $this->reporter->col('', '200', null, false, $border, 'TBR', 'L', $font, $font_size + 1, 'B', '',  '5px');
                $str .= $this->reporter->endrow();
                // $str .= $this->reporter->endtable();
            }

            //kukunin yung number lang at gagawing int kc varchar sa database
            $carton = isset($data->amount) ? ($data->amount / 1000) : 0;

            // I-print ang bawat client row sa loob ng area
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false,  $border, 'TL', 'L', $font, $font_size, '', '',  '2px');
            $str .= $this->reporter->col($data->clientname, '700', null, false,  $border, 'T', 'L', $font, $font_size, '', '',  '2px');
            $str .= $this->reporter->col(number_format($carton, 2), '200', null, false,  $border, 'TLR', 'R', $font, $font_size, '', '',  '2px');
            $str .= $this->reporter->endrow();

            $this->countline(['clientname' => $data->clientname], ['clientname' => 60]);

            // accumulate area totals
            $tlcarton += $carton;

            $nextclientname = isset($result[$key + 1]) ? $result[$key + 1]->clientname : '';
            $upcoming = $this->countline(['clientname' => $nextclientname], ['clientname' => 60], false);

            $nextareacheck = isset($result[$key + 1]) ? $result[$key + 1]->area : null;
            $areatransitionlines = 0;
            if ($nextareacheck !== null && $nextareacheck != $areas) {
                $areatransitionlines = 1 + 0.39 + 1;
            }

            if ($this->reporter->linecounter + $upcoming + $areatransitionlines >= $count) {

                $nextarea = isset($result[$key + 1]) ? $result[$key + 1]->area : null; //if the next area is the same as the current
                $isLastOfArea = ($nextarea != $areas);
                if ($isLastOfArea) {
                    $this->reporter->linecounter++;
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', null, false, $border, 'TLB', 'L', $font, $font_size, 'B', '',  '5px');
                    $str .= $this->reporter->col('AREA TOTAL', '750', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '5px');
                    $str .= $this->reporter->col(number_format($tlcarton, 2), '200', null, false, $border, 'TLBR', 'R', $font, $font_size, 'B', '', '5px');
                    $str .= $this->reporter->endrow();
                    $this->reporter->linecounter++;

                    // add area totals to grand total
                    $grand_cr += $tlcarton;

                    // // reset area totals
                    $tlcarton = 0;

                    //space bago magheader
                    $str .= $this->reporter->startrow();
                    $this->reporter->linecounter += 0.39;
                    $str .= $this->reporter->col('&nbsp;', '100', null, false,  '', '',  'L', $font, '4', '', '',  '');
                    $str .= $this->reporter->col('&nbsp;', '700', null, false,  '', '',  'L', $font, '4', '', '',  '');
                    $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '4', '', '',  '');
                    $str .= $this->reporter->endrow();
                    $areaTotalPrinted = true;
                }

                //timestamp
                $printeddate = $this->othersClass->getCurrentTimeStamp();
                $datetime = new DateTime($printeddate);
                $formattedDate = $datetime->format('m/d/Y h:i:s a'); 
                $str .= $this->reporter->begintable('1100');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col( $formattedDate, '700', null, true, $border, '', 'L', $font, 13, '', '',  '5px');
                $str .= $this->reporter->pagenumber('Page ', '200', null, true, $border, '', 'R', $font, 13, '', '',  '5px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $this->pagelinecount = 0;
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // reset line counter
                $this->reporter->linecounter = 0;

                // print new header
                $str .= $this->default_displayHeader($config);
                $str .= $this->reporter->begintable('1100');

                // reprint current agent name
                if (!$isLastOfArea) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '100', null, false, $border, 'TBL', 'L', $font, $font_size + 1, 'B', '', '5px');
                $str .= $this->reporter->col(strtoupper($areas).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.strtoupper($province), '700', null, false, $border, 'TB', 'L', $font, $font_size + 1, 'B', '', '5px');
                $str .= $this->reporter->col('&nbsp;', '200', null, false, $border, 'TBR', 'C', $font, $font_size + 1, 'B', '', '5px');
                $str .= $this->reporter->endrow();
                $this->reporter->linecounter++;
                }
            }
        }

        // Pag natapos ang loop, i-print ang last area total
        if ($areas != '' && !$areaTotalPrinted) {
            $this->reporter->linecounter++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, true, $border, 'TLB', 'L', $font, $font_size, 'B', '', '5px');
            $str .= $this->reporter->col('AREA TOTAL', '700', null, true, $border, 'TB', 'L', $font, $font_size, 'B', '', '5px');
            $str .= $this->reporter->col(number_format($tlcarton, 2), '200', null, true, $border, 'TLBR', 'R', $font, $font_size, 'B',  '', '5px');
            $str .= $this->reporter->endrow();

            // add last area to grand total
            $grand_cr += $tlcarton;
        }

        $str .= $this->reporter->startrow();
        $this->reporter->linecounter += 0.5;
        $str .= $this->reporter->col('&nbsp;', '100', null, false,  '', '',  'L', $font, $font_size, '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '700', null, false,  '', '',  'L', $font, $font_size, '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, $font_size, '', '',  '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, true, $border, 'TLB', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('GRAND TOTAL', '700', null, true, $border, 'TB', 'L', $font, $font_size, 'B', '',  '5px');
        $str .= $this->reporter->col(number_format($grand_cr, 2), '200', null, true, $border, 'TLBR', 'R', $font, $font_size, 'B', '',  '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('m/d/Y h:i:s a'); 
        $str .= $this->reporter->begintable('1100');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col( $formattedDate, '700', null, true, $border, '', 'L', $font, 13, '', '',  '5px');
        $str .= $this->reporter->pagenumber('Page ', '200', null, true, $border, '', 'R', $font, 13, '', '',  '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public $pagelinecount = 0;
    function countline($col = [], $len = [], $commit = true)
    {
        if (!empty($col)) {
            $arr = [];
            foreach ($col as $key => $txt) {
                $collen = isset($len[$key]) ? $len[$key] : 0;
                if ($collen > 0) {
                    array_push($arr, $this->reporter->fixcolumn([$txt], $collen, 0));  
                }
            }
            $lines = $this->othersClass->getmaxcolumn($arr);  //whichever column wrapped into the most lines
            if ($commit) { //used by $upcoming to check if the next row will exceed the page limit
                $this->reporter->linecounter = $this->reporter->linecounter + $lines;
                $this->pagelinecount = $this->pagelinecount + $lines;
            }
            return $lines;
        } else {
            if ($commit) {
                $this->reporter->linecounter++;
                $this->pagelinecount++;
            }
            return 1;
        }
    }
}//end class