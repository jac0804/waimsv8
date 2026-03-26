<?php

namespace App\Http\Classes\modules\reportlist\pos_reports;

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

class daily_analysis_of_product
{
    public $modulename = 'POS Report';
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

        $companyid = $config['params']['companyid'];
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        // for signatory
        $fields = ['prepared', 'received', 'approved'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'prepared.label', 'Prepared By');
        data_set($col2, 'received.label', 'Received By');
        data_set($col2, 'approved.label', 'Approved By');

        $fields = ['start', 'end'];
        // for date filter
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'start.required', true);
        data_set($col3, 'end.required', true);

        // for filter
        $fields = ['dcentername', 'brand', 'stock_groupname', 'pos_station'];
        $col4 = $this->fieldClass->create($fields);
        // Branch
        data_set($col4, 'dcentername.lookupclass', 'getmultibranch');
        //brand
        data_set($col4, 'brand.lookupclass', 'brand');
        // Group
        data_set($col4, 'lookupgroup_stock.lookupclass', 'stockgrp');
        // Station
        data_set($col4, 'pos_station_lookup.lookupclass', 'pos_station');

        $fields = ['print'];
        $col5 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4, 'col5' => $col5);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      date(now()) as end, 
      '' as center,
      '' as dcentername,
      '' as centername,
      '' as prefix,
      '0' as reporttype,
      '0' as clientid,
      '' as customer,
      '' as pos_station,
      '0' as groupid,
      '' as stock_groupname,
      '' as stationname,
      '' as clientname,
    '0' as brandid,
      '' as brandname,
      '' as prepared,
      '' as received,
    '' as approved
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
        $reporttype = $config['params']['dataparams']['reporttype'];

        if ($companyid == 56) { //MIS
            return $this->daily_sales_analysis_of_product($config);
        }

        if ($companyid == 29) { //sbc
            return $this->daily_sales_analysis_of_product_sbc($config);
        }
    }

    // jan 20 DAILY ANALYSIS OF PRODUCT QRY
    public function daily_sales_analysis_of_product_qry($config)
    {
        $center = $config['params']['dataparams']['center'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $groupid = $config['params']['dataparams']['groupid'];
        $groupName = $config['params']['dataparams']['stock_groupname'];
        $station = $config['params']['dataparams']['pos_station'];
        $brandid = $config['params']['dataparams']['brandid'];


        $filter = '';

        if ($groupid != "0") {
            $filter .= " and item.groupid = $groupid";
        }

        if ($station != "") {
            $filter .= " and cnum.station = '$station'";
        }

        if ($brandid != "0") {
            $filter .= " and item.brand = $brandid";
        }

        if ($center != '') {
            $filter .= " and cnum.center = '$center'";
        }

        $query = "select head.trno, h.doc, h.docno, h.dateid, h.wh, h.branch, branch.clientname as bname,
                (case when item.barcode='*' then 'Discount Trans.' else item.barcode end) as barcode, item.itemname,
                0 as cost, stock.isamt, stock.iss, item.uom, 0 as disc, stock.ext,
                item.cost as icost
                        
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join head as h on h.webtrno = head.trno and h.docno = stock.ref
                join item on item.itemid = stock.itemid
                left join client as wh on wh.wh = head.wh
                left join client as branch on branch.client = h.branch
                left join stockinfo as si on si.trno = stock.trno and si.line = stock.line
                left join cntnum as cnum on cnum.trno = head.trno
                        
                where cnum.bref in ('SJS', 'SRS')  and date(head.dateid) between '$start' and '$end'
                and h.voiddate is null and stock.line <> 9999 $filter
                        
                union all
                        
                select head.trno, h.doc, h.docno, h.dateid, h.wh, h.branch, branch.clientname as bname,
                (case when item.barcode='*' then 'Discount Trans.' else item.barcode end) as barcode, item.itemname,
                0 as cost, stock.isamt, stock.isqty as iss, item.uom,
                (si.lessvat+si.sramt+si.pwdamt+si.soloamt+si.acdisc+si.discamt+si.empdisc+si.vipdisc+si.oddisc+si.smacdisc) as disc,
                round((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cnum.doc = 'CM', -1, 1),2) as ext,
                item.cost as icost
                        
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join head as h on h.webtrno = head.trno and h.docno = stock.ref
                join item on item.itemid = stock.itemid
                left join client as wh on wh.wh = head.whid
                left join client as branch on branch.client = h.branch
                left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
                left join cntnum as cnum on cnum.trno = head.trno
                        
                where cnum.bref in ('SJS', 'SRS')  and date(head.dateid) between '$start' and '$end'
                and h.voiddate is null and stock.iscomponent = 0 and stock.line <> 9999 $filter
                        
                order by trno, docno";
        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $data;
    }

    // jan 20 DAILY ANALYSIS OF PRODUCT HEADER
    public function daily_sales_analysis_of_product_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $station = $config['params']['dataparams']['pos_station'];
        // $data = $this->summarized_salesreport_query($config);

        $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
        $qry2 = "select bn.comptel from branchstation as bn left join client on client.clientid = bn.clientid
         where bn.comptel <> 0 and client.center = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $headerdata2 = $this->coreFunctions->opentable($qry2);

        $gnrtdate = date('m-d-Y H:i:s A');
        // $system = '';
        $srno = '';
        $machineid = '';
        $postrmnl = '';
        // $username = '';

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "9";
        $border = "1px dashed ";

        if ($config['params']['dataparams']['dcentername'] == '') {
            $dcentername = '-';
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata2[0]->comptel, null, null, false, '3px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY ANALYSIS OF PRODUCT SOLD', null, '50', false, $border, '', 'C', 'Times New Roman', $fontsize, 'B', 'blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Branch: ', null, null, false, '', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Covered: <b>' . $start . '</b> to <b>' . $end . '</b>', '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        if ($station == '') {
            $str .= $this->reporter->col('Station: ' . 'ALL STATION', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Station: ' . strtoupper($station), '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Barcode', '220', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('U. Cost', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('S. Price', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Discount', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Qty', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Net Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Cost', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Profit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('% Mark-On', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    // jan 20 DAILY ANALYSIS OF PRODUCT REPORT PLOTTING
    public function daily_sales_analysis_of_product($config)
    {
        $str = '';
        $layoutsize = '1000';
        // $font = $this->companysetup->getrptfont($config['params']);
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->daily_sales_analysis_of_product_header($config);
        $data = $this->daily_sales_analysis_of_product_qry($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $cost = 0;
        $profit = 0;
        $markon = 0;

        $ttlCost = 0;
        $ttlPrice = 0;
        $ttlQty = 0;
        $ttlNet = 0;
        $sumProfit = 0;
        $sumCost = 0;
        $ttlMarkOn = 0;

        for ($i = 0; $i < count($data); $i++) {

            $cost = ($data[$i]['cost'] != 0 && $data[$i]['iss'] != 0) ? $data[$i]['cost'] * $data[$i]['iss'] : 0;
            $profit = ($data[$i]['ext'] != 0) ? $data[$i]['ext'] - $cost : 0;
            $markon = ($data[$i]['ext'] > 0 && $profit != 0) ? round(($profit / $data[$i]['ext']) * 100, 2) : 0;

            // Total Mark On
            $ttlMarkon = ($ttlNet > 0 && $sumProfit != 0) ? round(($sumProfit / $ttlNet) * 100, 2) : 0;


            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['itemname'] . '[' . $data[$i]['docno'] . ']', '220', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['cost'] != 0 ? number_format($data[$i]['cost'], 2) : '-', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['isamt'] != 0 ? number_format($data[$i]['isamt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['disc'] != 0 ? number_format($data[$i]['disc'], 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['iss'] != 0 ? number_format($data[$i]['iss'], 2) : '-', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['ext'] != 0 ? number_format($data[$i]['ext'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($cost != 0 ? number_format($cost, 2) : '-', '130', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($profit != 0 ? number_format($profit, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($markon != 0 ? number_format($markon, 2) . '%' : '-', '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            // $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $ttlCost += $data[$i]['cost'];
            $ttlPrice += $data[$i]['isamt'];
            $ttlQty += $data[$i]['iss'];
            $ttlNet += $data[$i]['ext'];

            $sumProfit += $profit;
            $sumCost += $sumCost;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'TL', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Cost:', '150', null, false, '1px dashed', 'T', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlCost != 0 ? number_format($ttlCost, 2) : '-', '228', null, false, '1px dashed', 'T', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Grand Total Cost:', '150', null, false, '1px dashed', 'T', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($sumCost != 0 ? number_format($sumCost, 2) : '-', '202', null, false, '1px dashed', 'TR', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'L', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Price:', '150', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlPrice != 0 ? number_format($ttlPrice, 2) : '-', '228', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Profit:', '150', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($sumProfit != 0 ? number_format($sumProfit, 2) : '-', '202', null, false, '1px dashed', 'R', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'L', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Quantity:', '150', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlQty != 0 ? number_format($ttlQty, 2) : '-', '228', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Mark-On:', '150', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlMarkon != 0 ? number_format($ttlMarkon, 2) . '%' : '-', '202', null, false, '1px dashed', 'R', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'BL', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Net Amount:', '150', null, false, '1px dashed', 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlNet != 0 ? number_format($ttlNet, 2) : '-', '228', null, false, '1px dashed', 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '202', null, false, '1px dashed', 'BR', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('% Mark On = ', '80', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Net Amount - U Cost', '150', null, false, '1px solid', 'B', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, '1px dashed', '', 'L', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Net Amount', '150', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Received by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($config['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']['received'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function daily_sales_analysis_of_product_header_sbc($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $centername     = $config['params']['dataparams']['centername'];
        $station = $config['params']['dataparams']['pos_station'];
        // $data = $this->summarized_salesreport_query($config);

        $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
        $qry2 = "select bn.comptel from branchstation as bn left join client on client.clientid = bn.clientid
         where bn.comptel <> 0 and client.center = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $headerdata2 = $this->coreFunctions->opentable($qry2);

        $gnrtdate = date('m-d-Y H:i:s A');
        // $system = '';
        $srno = '';
        $machineid = '';
        $postrmnl = '';
        // $username = '';

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "9";
        $border = "1px dashed ";

        if ($config['params']['dataparams']['dcentername'] == '') {
            $dcentername = '-';
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata2[0]->comptel, null, null, false, '3px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY ANALYSIS OF PRODUCT SOLD', null, '50', false, $border, '', 'C', 'Times New Roman', $fontsize, 'B', 'blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($centername == '') {
            $str .= $this->reporter->col('BRANCH: ' . 'ALL BRANCH', '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('BRANCH: ' . strtoupper($centername), '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Covered: <b>' . $start . '</b> to <b>' . $end . '</b>', '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        if ($station == '') {
            $str .= $this->reporter->col('Station: ' . 'ALL STATION', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Station: ' . strtoupper($station), '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Barcode', '220', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('U. Cost', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('S. Price', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Discount', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Qty', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Net Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Cost', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Profit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('% Mark-On', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function daily_sales_analysis_of_product_sbc($config)
    {
        $str = '';
        $layoutsize = '1000';
        // $font = $this->companysetup->getrptfont($config['params']);
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->daily_sales_analysis_of_product_header_sbc($config);
        $data = $this->daily_sales_analysis_of_product_qry($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $cost = 0;
        $profit = 0;
        $markon = 0;

        $ttlCost = 0;
        $ttlPrice = 0;
        $ttlQty = 0;
        $ttlNet = 0;
        $sumProfit = 0;
        $sumCost = 0;
        $ttlMarkOn = 0;

        for ($i = 0; $i < count($data); $i++) {

            $cost = ($data[$i]['cost'] != 0 && $data[$i]['iss'] != 0) ? $data[$i]['cost'] * $data[$i]['iss'] : 0;
            $profit = ($data[$i]['ext'] != 0) ? $data[$i]['ext'] - $cost : 0;
            $markon = ($data[$i]['ext'] > 0 && $profit != 0) ? round(($profit / $data[$i]['ext']) * 100, 2) : 0;

            // Total Mark On
            $ttlMarkon = ($ttlNet > 0 && $sumProfit != 0) ? round(($sumProfit / $ttlNet) * 100, 2) : 0;


            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['itemname'] . '[' . $data[$i]['docno'] . ']', '220', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['cost'] != 0 ? number_format($data[$i]['cost'], 2) : '-', '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['isamt'] != 0 ? number_format($data[$i]['isamt'], 2) : '-', '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['disc'] != 0 ? number_format($data[$i]['disc'], 2) : '-', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['iss'] != 0 ? number_format($data[$i]['iss'], 2) : '-', '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['ext'] != 0 ? number_format($data[$i]['ext'], 2) : '-', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($cost != 0 ? number_format($cost, 2) : '-', '130', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($profit != 0 ? number_format($profit, 2) : '-', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($markon != 0 ? number_format($markon, 2) . '%' : '-', '110', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            // $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $ttlCost += $data[$i]['cost'];
            $ttlPrice += $data[$i]['isamt'];
            $ttlQty += $data[$i]['iss'];
            $ttlNet += $data[$i]['ext'];

            $sumProfit += $profit;
            $sumCost += $sumCost;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'TL', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Cost:', '150', null, false, '1px dashed', 'T', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlCost != 0 ? number_format($ttlCost, 2) : '-', '228', null, false, '1px dashed', 'T', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Grand Total Cost:', '150', null, false, '1px dashed', 'T', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($sumCost != 0 ? number_format($sumCost, 2) : '-', '202', null, false, '1px dashed', 'TR', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'L', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Price:', '150', null, false, '1px dashed', '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlPrice != 0 ? number_format($ttlPrice, 2) : '-', '228', null, false, '1px dashed', '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Profit:', '150', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($sumProfit != 0 ? number_format($sumProfit, 2) : '-', '202', null, false, '1px dashed', 'R', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'L', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Quantity:', '150', null, false, '1px dashed', '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlQty != 0 ? number_format($ttlQty, 2) : '-', '228', null, false, '1px dashed', '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Mark-On:', '150', null, false, '1px dashed', '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlMarkon != 0 ? number_format($ttlMarkon, 2) . '%' : '-', '202', null, false, '1px dashed', 'R', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'BL', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Net Amount:', '150', null, false, '1px dashed', 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlNet != 0 ? number_format($ttlNet, 2) : '-', '228', null, false, '1px dashed', 'B', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '150', null, false, '1px dashed', 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '202', null, false, '1px dashed', 'BR', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('% Mark On = ', '80', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Net Amount - U Cost', '150', null, false, '1px solid', 'B', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, '1px dashed', '', 'L', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Net Amount', '150', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '1px dashed', '', 'C', 'Calibri', $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared by:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Received by:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved by:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']["approved"], '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']["received"], '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class
