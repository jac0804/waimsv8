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

class daily_sales_summary
{
    public $modulename = 'Daily Sales Summary';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

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
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['radioposttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
            ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '0' as posttype");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function reportplotting($config)
    {

        return $this->report_SUMMARIZED_Layout($config);
    }

    public function reportDefault($config)
    {

        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
        $posttype   = $config['params']['dataparams']['posttype'];
        switch ($posttype) {
            case '0': //posted
                $query = "  select  count(distinct head.docno) as docno, date(head.dateid) as dateid,if(head.ctnsno='','',head.ctnsno) as carton,
                  sum(stock.ext) as totalsales,if(cat.cat_name='' or cat.cat_name is null,'No Category',cat.cat_name) as catname
                  from glhead as head
                  left join glstock as stock on stock.trno = head.trno
                  left join client as cl on cl.clientid=head.clientid
                  left join category_masterfile as cat on cl.category = cat.cat_id
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
                  group by head.docno,date(head.dateid),head.ctnsno,cat.cat_name  order by head.dateid,catname";

                break;
            case '1': // unposted
                $query = " select count(distinct head.docno) as docno, date(head.dateid) as dateid,if(head.ctnsno='','',head.ctnsno) as carton,
                  sum(stock.ext) as totalsales, if(cat.cat_name='' or cat.cat_name is null,'No Category',cat.cat_name) as catname
                  from lahead as head
                  left join lastock as stock on stock.trno = head.trno
                  left join client as cl on cl.client=head.client
                  left join category_masterfile as cat on cl.category = cat.cat_id
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
                  group by date(head.dateid),head.ctnsno, cat.cat_name  order by head.dateid,catname";
                break;
            case '2': //all
                $query = "
                  select count(distinct docno) as docno,dateid,carton,sum(totalsales) as totalsales,catname from (
                  select head.docno, date(head.dateid) as dateid,if(head.ctnsno='','',head.ctnsno) as carton,
                  sum(stock.ext) as totalsales, if(cat.cat_name='' or cat.cat_name is null,'No Category',cat.cat_name) as catname
                  from lahead as head
                  left join lastock as stock on stock.trno = head.trno
                  left join client as cl on cl.client=head.client
                  left join category_masterfile as cat on cl.category = cat.cat_id
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
                  group by  head.docno,date(head.dateid),head.ctnsno,cat.cat_name

                  union all 
                  
                  select  head.docno, date(head.dateid) as dateid,if(head.ctnsno='','',head.ctnsno) as carton,
                  sum(stock.ext) as totalsales, if(cat.cat_name='' or cat.cat_name is null,'No Category',cat.cat_name) as catname
                  from glhead as head
                  left join glstock as stock on stock.trno = head.trno
                  left join client as cl on cl.clientid=head.clientid
                  left join category_masterfile as cat on cl.category = cat.cat_id
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
                  group by head.docno,date(head.dateid),head.ctnsno,cat.cat_name
                  order by date(dateid),catname) as xy
                  group by dateid,carton,catname order by dateid,catname";
                break;
        }
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config, $layoutsize)
    {

        $font = $this->companysetup->getrptfont($config['params']);

        $center   = $config['params']['center'];
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype   = $config['params']['dataparams']['posttype'];

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font_size = '12';
        $str = '';


        switch ($posttype) {
            case '0': // posted
                $posttype = "Posted";
                break;
            case '1': // unposted
                $posttype = "Unposted";
                break;
            case '2': // all
                $posttype = "All Transaction";
                break;
        }

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



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Daily Sales Summarized', null, null, false, '1px solid ', 'C', 'C', $font, '13', 'B');
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

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Transaction: ' . $posttype, null, null, false, '1px solid ', '', 'L', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '250', null, false, '1px solid ', 'BTL', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('TOTAL INVOICES', '225', null, false, '1px solid ', 'BTL', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('TOTAL CTNS', '225', null, false, '1px solid ', 'BTL', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('SALES AMOUNT', '300', null, false, '1px solid ', 'BTLR', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        return $str;
    }


    public function report_SUMMARIZED_Layout_org($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';

        $result = $this->reportDefault($config);

        $count = 33;
        $page = 34;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';


        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $layoutsize);

        foreach ($result as $key => $data) {
            $carton = (int) ($data->carton != '' ? preg_replace('/\D+/', '', $data->carton) : 0);
            $catname = $data->catname;

            $docno = $data->docno;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateid, '250', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->docno, '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($carton != 0 ? number_format($carton, 2) : '', '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col(number_format($data->totalsales, 2), '300', null, false,  $border, 'LR', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('CUSTOMER TYPE', '250', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('TOTAL INVOICES', '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('TOTAL CTNS', '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('SALES AMOUNT', '300', null, false,  $border, 'LR', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '250', null, false,  $border, 'T',  'L', $font, '', '', '',  '');
                $str .= $this->reporter->col('', '225', null, false,  $border, 'T',  'L', $font, '', '', '', '');
                $str .= $this->reporter->col('', '225', null, false,  $border, 'T',  'L', $font, '', '', '',  '');
                $str .= $this->reporter->col('', '300', null, false,  $border, 'T',  'L', $font, '', '', '',  '');
                $str .= $this->reporter->endrow();



                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $layoutsize);
                $page = $page + $count;
            }
        }

        /// NEW ROW HERE 
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '250', null, false,  '', '',  'L', $font, '6', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  '', '',  'L', $font, '6', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  '', '',  'L', $font, '6', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '300', null, false,  '', '',  'L', $font, '6', '', '',  '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER TYPE', '250', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('TOTAL INVOICES', '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('TOTAL CTNS', '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('SALES AMOUNT', '300', null, false,  $border, 'LR', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', '250', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '225', null, false,  $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '300', null, false,  $border, 'LR', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function report_SUMMARIZED_Layout($config)
    {

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';

        $result = $this->reportDefault($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $count = 40; // bilang ng lines bago mag page break
        $page = $count;
        $this->reporter->linecounter = 0;

        // Totals per date
        $totalSales = 0;
        $totalCartons = 0;
        $totalInvoices = 0;

        // Grand totals (pang dulo)
        $grandSales = 0;
        $grandCartons = 0;
        $grandInvoices = 0;

        // Per-page / per-date summary by customer type
        $page_summary = [];
        $pagetl_invoice = 0;
        $pagetl_ctns = 0;
        $pagetl_sales = 0;

        $prevDate = null;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $layoutsize);

        foreach ($result as $key => $data) {

            $currentDate = $data->dateid;
            $catname = $data->catname ?: 'No Category';
            $sales = (float)$data->totalsales;
            $docno = $data->docno;
            $carton = (int) ($data->carton != '' ? preg_replace('/\D+/', '', $data->carton) : 0);

            if ($prevDate != null && $currentDate != $prevDate) {

                //  i-print muna ang subtotal ng nakaraang date 
                $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($prevDate, '250', null, false, $border, 'L', 'C', $font, $font_size);
                $str .= $this->reporter->col(number_format($totalInvoices, 0), '225', null, false, $border, 'L', 'C', $font, $font_size);
                $str .= $this->reporter->col(number_format($totalCartons, 2), '225', null, false, $border, 'L', 'C', $font, $font_size);
                $str .= $this->reporter->col(number_format($totalSales, 2), '300', null, false, $border, 'LR', 'C', $font, $font_size);
                $str .= $this->reporter->endrow();

                //  idagdag sa grand total 
                $grandSales += $totalSales;
                $grandCartons += $totalCartons;
                $grandInvoices += $totalInvoices;

                // reset ng total
                $totalSales = 0;
                $totalCartons = 0;
                $totalInvoices = 0;
            }

            // current na data 
            $totalSales += $sales;
            $totalCartons += $carton;
            $totalInvoices += $docno;

            if ($this->reporter->linecounter >= $page) {

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('&nbsp;', '250', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
                $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
                $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
                $str .= $this->reporter->col('&nbsp;', '300', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                //  print page summary (CUSTOMER TYPE) 
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CUSTOMER TYPE', '250', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
                $str .= $this->reporter->col('TOTAL INVOICES', '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
                $str .= $this->reporter->col('TOTAL CTNS', '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
                $str .= $this->reporter->col('SALES AMOUNT', '300', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B');
                $str .= $this->reporter->endrow();

                foreach ($page_summary as $ctype => $sum) {

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($ctype, '250', null, false, $border, 'L', 'C', $font, $font_size);
                    $str .= $this->reporter->col(number_format($sum['inv'], 0), '225', null, false, $border, 'L', 'C', $font, $font_size);
                    $str .= $this->reporter->col(number_format($sum['ctns'], 2), '225', null, false, $border, 'L', 'C', $font, $font_size);
                    $str .= $this->reporter->col(number_format($sum['sales'], 2), '300', null, false, $border, 'LR', 'C', $font, $font_size);
                    $str .= $this->reporter->endrow();

                    $pagetl_invoice += $sum['inv'];
                    $pagetl_ctns += $sum['ctns'];
                    $pagetl_sales += $sum['sales'];
                }

                $str .= $this->reporter->endtable();
                $str .= $this->footer($config, $pagetl_invoice, $pagetl_ctns, $pagetl_sales);
                // // reset ng page summary para sa susunod na page 
                $page_summary = [];
                $pagetl_invoice = 0;
                $pagetl_ctns = 0;
                $pagetl_sales = 0;
                // page break next page na 
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $layoutsize);
                $page += $count;
            }

            //check muna ng page bago mag proceed dito para hindi pumasok sa ibang page ang data 
            if (!isset($page_summary[$catname])) {
                $page_summary[$catname] = ['inv' => 0, 'ctns' => 0, 'sales' => 0];
            }
            // var_dump($page_summary);
            $page_summary[$catname]['inv'] += $docno;
            $page_summary[$catname]['ctns'] += $carton;
            $page_summary[$catname]['sales'] += $sales;

            $prevDate = $currentDate;
        }

        // huling date
        if ($prevDate != null) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($prevDate, '250', null, false, $border, 'L', 'C', $font, $font_size);
            $str .= $this->reporter->col(number_format($totalInvoices, 0), '225', null, false, $border, 'L', 'C', $font, $font_size);
            $str .= $this->reporter->col(number_format($totalCartons, 2), '225', null, false, $border, 'L', 'C', $font, $font_size);
            $str .= $this->reporter->col(number_format($totalSales, 2), '300', null, false, $border, 'LR', 'C', $font, $font_size);
            $str .= $this->reporter->endrow();

            // idagdag sa grand total
            $grandSales += $totalSales;
            $grandCartons += $totalCartons;
            $grandInvoices += $totalInvoices;
        }

        // fianl grand total
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '250', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '300', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER TYPE', '250', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->col('TOTAL INVOICES', '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->col('TOTAL CTNS', '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->col('SALES AMOUNT', '300', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->endrow();

        foreach ($page_summary as $ctype => $sum) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($ctype, '250', null, false, $border, 'L', 'C', $font, $font_size);
            $str .= $this->reporter->col(number_format($sum['inv'], 0), '225', null, false, $border, 'L', 'C', $font, $font_size);
            $str .= $this->reporter->col(number_format($sum['ctns'], 2), '225', null, false, $border, 'L', 'C', $font, $font_size);
            $str .= $this->reporter->col(number_format($sum['sales'], 2), '300', null, false, $border, 'LR', 'C', $font, $font_size);
            $str .= $this->reporter->endrow();
            $pagetl_invoice += $sum['inv'];
            $pagetl_ctns += $sum['ctns'];
            $pagetl_sales += $sum['sales'];
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '250', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '300', null, false,  $border, 'T',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAGE TOTAL', '250', null, false, $border, 'TBL', 'C', $font, $font_size, 'B', '', '2px', '');
        $str .= $this->reporter->col(number_format($pagetl_invoice, 0), '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '2px', '');
        $str .= $this->reporter->col(number_format($pagetl_ctns, 2), '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '2px', '');
        $str .= $this->reporter->col(number_format($pagetl_sales, 2), '300', null, false, $border, 'LTRB', 'C', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '250', null, false,  $border, '',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, '',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, '',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '300', null, false,  $border, '',  'L', $font, '4', '', '',  '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', '250', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->col(number_format($grandInvoices, 0), '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->col(number_format($grandCartons, 2), '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->col(number_format($grandSales, 2), '300', null, false, $border, 'TLRB', 'C', $font, $font_size, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '250', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '300', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();

        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('m/d/Y h:i:s a'); //2025-09-25 16:46:32 pm
        $str .= $this->reporter->col($formattedDate, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(' ', '225', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(' ', '225', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->pagenumber('Page', '300', null, '', $border, '', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }

    public function footer($config, $data1, $data2, $data3)
    {
        $border = '1px solid';
        // $data1= totalinvoice $data2=totalctns $data3=salesamt

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '250', null, false,  $border, 'T',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  $border, 'T',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '300', null, false,  $border, 'T',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '10', null, false, $border, 'TLB', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('PAGE TOTAL', '250', null, false, $border, 'TBL', 'C', $font, $font_size, 'B', '', '2px', '');
        $str .= $this->reporter->col(number_format($data1, 0), '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '2px', '');
        $str .= $this->reporter->col(number_format($data2, 2), '225', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '2px', '');
        $str .= $this->reporter->col(number_format($data3, 2), '300', null, false, $border, 'LTRB', 'C', $font, $font_size, 'B', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '250', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '225', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->col('&nbsp;', '300', null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();

        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('m/d/Y h:i:s a'); //2025-09-25 16:46:32 pm
        $str .= $this->reporter->col($formattedDate, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(' ', '225', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(' ', '225', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->pagenumber('Page', '300', null, '', $border, '', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= $this->reporter->endreport();

        return $str;
    }
}//end class