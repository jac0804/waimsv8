<?php

namespace App\Http\Classes\modules\reportlist\pos_reports;


use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\SBCPDF;


class monthly_sales_analysis_report
{
    public $modulename = 'Monthly Sales Analysis Report';
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

        $fields = ['radioprint', 'month', 'year',  'stockgrp', 'brand', 'pos_station'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'month.label', 'Month');
        data_set($col1, 'dcentername.lookupclass', 'getmultibranch');
        data_set($col1, 'dcentername.class', 'cscsdcentername sbccsreadonly');

        // data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        // data_set($col1, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
        // data_set($col1, 'categoryname.class', 'cscscategocsryname sbccsreadonly');

        // created jan 22
        data_set($col1, 'lookupgroup_stock.lookupclass', 'stockgrp');
        //Brand
        data_set($col1, 'brand.lookupclass', 'brand');
        // Station
        data_set($col1, 'pos_station_lookup.lookupclass', 'pos_station');

        $fields = ['radioreporttype', 'print'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreporttype.options', [
            ['label' => 'Standard', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Monthly Sales', 'value' => '1', 'color' => 'orange'],
            ['label' => 'BIR', 'value' => '2', 'color' => 'orange']
        ]);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr = "select 
      'default' as print,
      '" . $defaultcenter[0]['center'] . "' as center,
      '" . $defaultcenter[0]['centername'] . "' as centername,
      '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
      '' as category,
      '' as categoryname,
      left(now(),4) as year,
      '' as classname,
      '' as class,
      '' as pos_station,
      '0' as groupid,
      '' as stock_groupname,
      '0' as brandid,
      '' as brandname,
      date_format(now(),'%m') as month,
      '' as pos_station,
      '0' as reporttype";
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
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case '0':
                if ($config['params']['companyid'] == 29) { //sbc
                    return $this->monthly_analysis_sales_layout_sbc($config);
                }
                return $this->monthly_analysis_sales_layout($config);
                break;

            case '1':
                if ($config['params']['companyid'] == 29) { //sbc
                    return $this->monthly_sales_layout_sbc($config);
                }
                return $this->monthly_sales_layout($config);
                break;

            case '2':
                if ($config['params']['companyid'] == 29) { //sbc
                    return $this->monthly_analysis_BIR_layout_sbc($config);
                }
                return $this->monthly_analysis_BIR_layout($config);
                break;
        }
    }

    public function monthly_analysis_sales_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $month = $config['params']['dataparams']['month'];
        $year = $config['params']['dataparams']['year'];
        $station = $config['params']['dataparams']['pos_station'];
        $brandid = $config['params']['dataparams']['brandid'];
        $groupid = $config['params']['dataparams']['groupid'];
        $centername = $config['params']['dataparams']['centername'];

        $filter   = "";

        if ($station != "") {
            $filter .= " and cntnum.station = '$station'";
        }
        if ($brandid != "0") {
            $filter .= " and item.brand = $brandid";
        }
        if ($groupid != "0") {
            $filter .= " and item.groupid = $groupid";
        }


        $query = "select center.name as branch,item.barcode,item.itemname,item.brand,
        ifnull(stockgrp.stockgrp_name,'') as stockgrp,
        round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
        left join item on item.itemid=stock.itemid
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
        left join client as supplier on supplier.client=item.supp
        left join cntnum on cntnum.trno=head.trno
        left join center on center.code=cntnum.center
	    where cntnum.bref in ('SJS','SRS') and month(head.dateid) = $month and year(head.dateid) = $year
        and cntnum.center = '$center' $filter
	    group by cntnum.doc,item.barcode,item.itemname,item.brand,center.name,stockgrp.stockgrp_name";
        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $data;
    }
    public function monthly_analysis_sales_BIR_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $month = $config['params']['dataparams']['month'];
        $year = $config['params']['dataparams']['year'];
        // $station = $config['params']['dataparams']['pos_station'];
        // $brandid = $config['params']['dataparams']['brandid'];
        // $groupid = $config['params']['dataparams']['groupid'];

        // $filter   = "";

        // if ($station != "") {
        //     $filter .= " and cntnum.station = '$station'";
        // }
        // if ($brandid != "0") {
        //     $filter .= " and item.brand = $brandid";
        // }
        // if ($groupid != "0") {
        //     $filter .= " and item.groupid = $groupid";
        // }

        $query = "
        select d.center,a.dateid,min(d.firstdoc) AS firstdoc,MAX(d.lastdoc) AS lastdoc,COUNT(d.firstdoc) as totalor,
        (case when DATE(d.hddateid)<date(d.jdateid) then sum(d.amt) else 0 end ) as  beg,
        (case when date(d.hddateid) = DATE(d.jdateid) then (SUM(d.amt)+SUM(d.disc)+SUM(d.discsr)+SUM(d.pwdamt)) else 0 end ) as   endbal,
        d.nvat,d.vatex,0 as zero,d.vatamt,d.discsr,d.disc,d.amt,d.sc,d.pwdamt from (
        select last_day(concat($year,'-',$month,'-01')) - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as dateid
        from (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a 
        cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union  select 6 union all SELECT 7 union all select 8 union  all          select 9) as b 
        cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
        ) a
        left join (
		select date(hd.dateid) as hddateid ,date(j.dateid) as jdateid,j.nvat,j.vatex,
		j.vatamt,j.discsr,j.disc,j.amt,j.sc,j.pwdamt,num.center,hd.docno as firstdoc,hd.docno as lastdoc 
        from journal as j 
		left join head as hd on date(hd.dateid) = date(j.dateid)
		left join cntnum as num on num.trno = hd.webtrno
		where hd.bref = 'SI' and num.center = '" . $center . "') AS d ON d.jdateid = a.dateid
  
        where year(a.dateid)='" . $year . "' and month(a.dateid)='" . $month . "'
        group by a.dateid,d.vatex,d.nvat,d.vatamt,d.discsr,d.disc,d.amt,d.sc,d.disc,.d.pwdamt,d.hddateid,d.jdateid,d.center
        order by a.dateid;";


        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        return $data;
    }

    // created january 22 (Monthly Sales Qry)
    public function monthly_sales_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $month = $config['params']['dataparams']['month'];
        $year = $config['params']['dataparams']['year'];
        $station = $config['params']['dataparams']['pos_station'];
        $brandid = $config['params']['dataparams']['brandid'];
        $groupid = $config['params']['dataparams']['groupid'];

        $filter   = "";

        if ($station != "") {
            $filter .= " and cntnum.station = '$station'";
        }
        if ($brandid != "0") {
            $filter .= " and item.brand = $brandid";
        }
        if ($groupid != "0") {
            $filter .= " and item.groupid = $groupid";
        }

        $query = "select cashier, dateid, day, sum(ext) as ext, sum(net) as net, sum(total) as total, sum(vatamt) as vatamt,
        sum(nvat) as nvat,  count(distinct s.trno) as transcount
        from (select h.trno, h.openby as cashier, h.dateid, date_format(h.dateid,'%W') as day,
        round(sum((stock.ext - si.lessvat - si.vatex - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext,
        sum(si.vatex) as net,
        sum(stock.isamt * stock.isqty) as total, sum(si.vatamt) as vatamt, sum(si.nvat) as nvat

        from lahead
        left join lastock stock on stock.trno = lahead.trno
        left join head as h on h.webtrno = lahead.trno and h.docno = stock.ref
        left join cntnum on cntnum.trno = lahead.trno
        left join client on client.client = lahead.client
        join item on item.itemid = stock.itemid
        join stockinfo as si on si.trno = stock.trno and si.line=stock.line
        where cntnum.bref in ('SJS','SRS') and cntnum.center='$center' and month(h.dateid)=$month and year(h.dateid)=$year
        $filter

        group by h.dateid, h.station, h.openby, h.trno

        union all

        select h.trno, h.openby as cashier, h.dateid, date_format(h.dateid,'%W') as day,
        round(sum((stock.ext - si.lessvat - si.vatex - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext,
        sum(si.vatex) as net,
        sum(stock.isamt * stock.isqty) as total, sum(si.vatamt) as vatamt, sum(si.nvat) as nvat

        from glhead
        left join glstock stock on glhead.trno=stock.trno
        left join head as h on h.webtrno = glhead.trno and h.docno = stock.ref
        left join cntnum on cntnum.trno = glhead.trno
        left join client on client.clientid = glhead.clientid
        join item on item.itemid = stock.itemid
        join hstockinfo si on stock.trno = si.trno and si.line = stock.line
        where cntnum.bref in ('SJS','SRS') and cntnum.center='$center' and month(h.dateid)=$month and year(h.dateid)=$year
        $filter

        group by h.dateid, h.station, h.openby, h.trno
        ) s
        group by s.dateid, s.cashier, s.day
        order by s.dateid, s.cashier";
        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        return $data;
    }

    private function monthly_analysis_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $year     = $config['params']['dataparams']['year'];
        $month     = $config['params']['dataparams']['month'];
        $brand     = $config['params']['dataparams']['brandname'];
        $station = $config['params']['dataparams']['pos_station'];
        $brandid = $config['params']['dataparams']['brandid'];
        $groupid = $config['params']['dataparams']['groupid'];
        $group = $config['params']['dataparams']['stock_groupname'];
        $str = '';
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $fontcolor = "#150485";
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
        $str .=  $this->reporter->col($this->reporter->pagenumber('Page'), '300', null, false, '1px solid ', '', 'R', $font, '11', '', '');
        $str .=  $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '360', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Phone: ' . strtoupper($headerdata[0]->tel), '280', null, false, $border, '', 'L', $font, '10', '', '', ''); //Fax: ( )
        $str .= $this->reporter->col('', '360', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MONTHLY SALES ANALYSIS', '800', null, false, $border, '', 'C', $font, '14', 'B', $fontcolor, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $monthname = date('F', strtotime("$year-$month-01"));

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '390', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Month: ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . strtoupper($monthname), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Year: ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . ($year != '' ? $year : "-"), '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '390', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '380', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        if ($groupid == '0') {
            $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('GROUP:  ' . strtoupper($group), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        }
        if ($brandid == '0') {
            $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        }
        if ($station == '') {
            $str .= $this->reporter->col('STATION:  ALL STATION', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('STATION:  ' . strtoupper($station), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('', '380', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Branch Name', '245', null, false, $border, 'B', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Barcode', '195', null, false, $border, 'B', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Item Description', '395', null, false, $border, 'B', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Amount', '150', null, false, $border, 'B', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function monthly_analysis_sales_layout($config)
    {

        $this->reporter->linecounter = 0;
        $count = 35;
        $page = 35;
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $data = $this->monthly_analysis_sales_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->monthly_analysis_header($config);
        $str .= $this->reporter->begintable($layoutsize);
        $totalamt = 0;
        for ($i = 0; $i < count($data); $i++) {

            // If next row would exceed page, break BEFORE printing it
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->monthly_analysis_header($config);
                $str .= $this->reporter->begintable($layoutsize);
                $page += $count;
            }

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['branch'], '245', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['barcode'], '195', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['itemname'], '395', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();

            $totalamt += $data[$i]['ext'];
        }


        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '245', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '195', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total:', '395', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalamt, 2), '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '245', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '195', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '395', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function BIR_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $year     = $config['params']['dataparams']['year'];
        $month     = $config['params']['dataparams']['month'];
        $str = '';
        $layoutsize = '1500';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $fontcolor = "#150485";
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($reporttimestamp, '1200', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
        $str .=  $this->reporter->col($this->reporter->pagenumber('Page'), '200', null, false, '1px solid ', '', 'R', $font, '11', '', '');
        $str .=  $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '600', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Phone: ' . strtoupper($headerdata[0]->tel), '300', null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '600', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $monthname = date('F', strtotime("$year-$month-01"));
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Monthly Sales Report', null, null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('For the Month of ' . $monthname . ' ' . $year, null, null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Accmulating OR', '350', null, false, $border, 'BTL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Accmulating Sales', '300', null, false, $border, 'BTL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Z-Read', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Vatable', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('VAT-Exempt', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Zero Rated', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('VAT 12%', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Discount', '200', null, false, $border, 'BTL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Net Sales', '100', null, false, $border, 'TLR', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Day', '50', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');

        $str .= $this->reporter->col('BEG', '125', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('END', '125', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');

        $str .= $this->reporter->col('BEG', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('END', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');

        $str .= $this->reporter->col('Counter', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Sales', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Sales', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Sales', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('S.C./ PWD', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Regular', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br/>';
        return $str;
    }

    public function monthly_analysis_BIR_layout($config)
    {
        $this->reporter->linecounter = 0;
        $count = 35;
        $page = 35;
        $layoutsize = '1500';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['month'];
        $this->reportParams['orientation'] = 'l';

        $data = $this->monthly_analysis_sales_BIR_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->BIR_header($config);
        $str .= $this->reporter->begintable($layoutsize);

        $j = 0;
        $z = 0;
        $totalor = 0;
        $zcounter = 0;

        $z_read = false;


        $totalvals = 0;
        $totalvatex = 0;
        $totalzread = 0;
        $totalvat = 0;
        $totaldiscsr = 0;
        $totalregular = 0;
        $totalnsales = 0;

        for ($i = 0; $i < count($data); $i++) {
            $j++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($j, '50', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
            // OR
            $str .= $this->reporter->col($data[$i]['firstdoc'], '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['lastdoc'], '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['totalor'], '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            // SALES
            if ($data[$i]['firstdoc'] != '') {
                $zcounter++;
                $z_read = true;
            } else {
                $z_read = false;
            }
            $totalsale = $data[$i]['beg'] + $data[$i]['endbal'];
            $str .= $this->reporter->col(number_format($data[$i]['beg'], 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['endbal'], 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($totalsale, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            //

            $str .= $this->reporter->col('' . ($z_read ? $zcounter : $z), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['nvat'], 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['vatex'], 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('0', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col(number_format($data[$i]['vatamt'], 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['discsr'], 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['disc'], 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            $netsales = $data[$i]['amt'] - $data[$i]['vatamt'];
            $str .= $this->reporter->col(number_format($netsales, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->endrow();


            $totalvals += $data[$i]['nvat'];
            $totalvatex += $data[$i]['vatex'];

            $totalvat += $data[$i]['vatamt'];
            $totaldiscsr += $data[$i]['discsr'];
            $totalregular += $data[$i]['disc'];

            $totalnsales += $netsales;
        }

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
        // OR
        $str .= $this->reporter->col('', '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        // SALES

        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        //
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalvals, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalvatex, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalzread, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col(number_format($totalvat, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totaldiscsr, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalregular, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalnsales, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    // created january 22 (Monthly Sales header)
    private function monthly_sales_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $year     = $config['params']['dataparams']['year'];
        $month     = $config['params']['dataparams']['month'];
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";
        $fontcolor = "#150485";
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $qry2 = "select bn.comptel from branchstation as bn left join client on client.clientid = bn.clientid
         where bn.comptel <> 0 and client.center = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $headerdata2 = $this->coreFunctions->opentable($qry2);
        $gnrtDate = date('m-d-Y');
        $gnrtTime      = date('h:i:s A');


        $str .= $this->reporter->begintable($layoutsize);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
        $str .=  $this->reporter->col($this->reporter->pagenumber('Page'), '300', null, false, '1px solid ', '', 'R', $font, '11', '', '');
        $str .=  $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata2[0]->comptel, null, null, false, '3px solid', '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Monthly Sales Report', '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Report Date:' . $gnrtDate, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->col('For the Month: ' . $month . ',' . $year, '700', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Report Date: ' . $gnrtTime, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $monthname = date('F', strtotime("$year-$month-01"));

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Day', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '750', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cashier', '100', null, false, $border, 'BL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sales<br> w/ VAT', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sales<br> w/0 VAT', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Vat Output', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Net of Vat', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Avg. Run', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Running <br>Total', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Trans.<br> Count', '100', null, false, $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    // created january 22 (Monthly Sales data)
    public function monthly_sales_layout($config)
    {

        $this->reporter->linecounter = 0;
        $count = 35;
        $page = 35;
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $data = $this->monthly_sales_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->monthly_sales_header($config);

        $totalamt = 0;
        $avgTotal = 0;
        $avgCount = 0;
        $runningTotal = 0;

        $currentDate = null;
        $dateExt = 0;
        $dateNet = 0;
        $dateTotal = 0;
        $dateVat = 0;
        $dateNvat = 0;

        $dateRowCount = 0; // number of cashier rows for that date
        $dateTransSum = 0; // sum of transcount per cashier row (for avg)

        $runningTotal = 0;
        $totalamt = 0;

        // for grand total
        // GRAND TOTALS
        $grandExt   = 0;
        $grandNet   = 0;
        $grandTotal = 0;
        $grandVat   = 0;
        $grandNvat  = 0;

        $grandRowCount  = 0;
        $grandTransSum  = 0;
        $grandAvgTotal  = 0;
        $grandAvgCount  = 0;

        $printSubTotal = function () use (
            &$str,
            &$dateExt,
            &$dateNet,
            &$dateTotal,
            &$dateVat,
            &$dateNvat,
            &$dateRowCount,
            &$dateTransSum,
            &$avgTotal,
            &$avgCount,
            $layoutsize,
            $border,
            $font,
            $fontsize
        ) {
            if ($dateRowCount <= 0) return;

            $avgRun = ($avgCount > 0) ? ($avgTotal / $avgCount) : 0;
            $avgTrans = ($dateRowCount > 0) ? ($dateTransSum / $dateRowCount) : 0;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col('Sub Total:', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($dateExt != 0 ? number_format($dateExt, 2) : '-', '140', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateNet != 0 ? number_format($dateNet, 2) : '-', '140', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateTotal != 0 ? number_format($dateTotal, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateVat != 0 ? number_format($dateVat, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateNvat != 0 ? number_format($dateNvat, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($avgCount > 0 ? number_format($avgRun, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateRowCount > 0 ? number_format($avgTrans, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        };

        for ($i = 0; $i < count($data); $i++) {
            // If next row would exceed page, break BEFORE printing it
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->monthly_sales_header($config);
                $str .= $this->reporter->begintable($layoutsize);
                $page += $count;
            }

            if ($currentDate !== $data[$i]['dateid']) {

                // print subtotal for previous date (not on first date)
                if (
                    $currentDate !== null
                ) {
                    $printSubTotal();

                    // reset per-date totals after printing
                    $dateExt = $dateNet = $dateTotal = $dateVat = $dateNvat = 0;
                    $dateRowCount = 0;
                    $dateTransSum = 0;
                    $avgTotal = 0;
                    $avgCount = 0;
                }

                // print date header
                $str .= $this->reporter->begintable();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]['day'], '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '750', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $currentDate = $data[$i]['dateid'];
            }

            // per-date subtotal
            $dateExt   += (float)$data[$i]['ext'];
            $dateNet   += (float)$data[$i]['net'];
            $dateTotal += (float)$data[$i]['total'];
            $dateVat   += (float)$data[$i]['vatamt'];
            $dateNvat  += (float)$data[$i]['nvat'];

            $dateRowCount++;
            $dateTransSum += (float)$data[$i]['transcount'];

            if ($data[$i]['ext'] != 0) {
                $avgTotal += $data[$i]['ext'];
                $avgCount++;
            }

            $average = ($avgCount > 0) ? ($avgTotal / $avgCount) : 0;

            $runningTotal += $data[$i]['ext'];
            $totalamt += $data[$i]['ext'];

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['cashier'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['ext'] != 0 ? number_format($data[$i]['ext'], 2) : '-', '140', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['net'] != 0 ? number_format($data[$i]['net'], 2) : '-', '140', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['total'] != 0 ? number_format($data[$i]['total'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['vatamt'] != 0 ? number_format($data[$i]['vatamt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['nvat'] != 0 ? number_format($data[$i]['nvat'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($avgCount > 0 ? number_format($average, 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($runningTotal != 0 ? number_format($runningTotal, 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['transcount'] != 0 ? number_format($data[$i]['transcount'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandExt   += (float)$data[$i]['ext'];
            $grandNet   += (float)$data[$i]['net'];
            $grandTotal += (float)$data[$i]['total'];
            $grandVat   += (float)$data[$i]['vatamt'];
            $grandNvat  += (float)$data[$i]['nvat'];

            $grandRowCount++;
            $grandTransSum += (float)$data[$i]['transcount'];

            if ($data[$i]['ext'] != 0) {
                $grandAvgTotal += (float)$data[$i]['ext'];
                $grandAvgCount++;
            }
        }
        // print the last subtotal after ther loop
        $printSubTotal();

        $grandAvgRun   = ($grandAvgCount > 0) ? ($grandAvgTotal / $grandAvgCount) : 0;
        $grandAvgTrans = ($grandRowCount > 0) ? ($grandTransSum / $grandRowCount) : 0;
        // for grandtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', '10', false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total:', '100', '10', false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandExt != 0 ? number_format($grandExt, 2) : '-', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandNet != 0 ? number_format($grandNet, 2) : '-', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandTotal != 0 ? number_format($grandTotal, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandVat != 0 ? number_format($grandVat, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandNvat != 0 ? number_format($grandNvat, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Average:', '90', '10', false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandAvgTrans != 0 ? number_format($grandAvgTrans, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'R', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', '10', false, $border, 'BL', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'BR', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }

    public function monthly_analysis_sales_header_sbc($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $year     = $config['params']['dataparams']['year'];
        $month     = $config['params']['dataparams']['month'];
        $brand     = $config['params']['dataparams']['brandname'];
        $station = $config['params']['dataparams']['pos_station'];
        $brandid = $config['params']['dataparams']['brandid'];
        $groupid = $config['params']['dataparams']['groupid'];
        $group = $config['params']['dataparams']['stock_groupname'];
        $str = '';
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $fontcolor = "#150485";
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
        $str .=  $this->reporter->col($this->reporter->pagenumber('Page'), '300', null, false, '1px solid ', '', 'R', $font, '11', '', '');
        $str .=  $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '360', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Phone: ' . strtoupper($headerdata[0]->tel), '280', null, false, $border, '', 'L', $font, '10', '', '', ''); //Fax: ( )
        $str .= $this->reporter->col('', '360', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MONTHLY SALES ANALYSIS', '800', null, false, $border, '', 'C', $font, '14', 'B', $fontcolor, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $monthname = date('F', strtotime("$year-$month-01"));

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '390', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Month: ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . strtoupper($monthname), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Year: ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . ($year != '' ? $year : "-"), '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '390', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '380', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        if ($groupid == '0') {
            $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('GROUP:  ' . strtoupper($group), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        }
        if ($brandid == '0') {
            $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        }
        if ($station == '') {
            $str .= $this->reporter->col('STATION:  ALL STATION', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('STATION:  ' . strtoupper($station), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('', '380', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Barcode', '350', null, false, $border, 'B', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Item Description', '490', null, false, $border, 'B', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Amount', '150', null, false, $border, 'B', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function monthly_analysis_sales_layout_sbc($config)
    {
        $this->reporter->linecounter = 0;
        $count = 35;
        $page = 35;
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $data = $this->monthly_analysis_sales_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->monthly_analysis_sales_header_sbc($config);
        $str .= $this->reporter->begintable($layoutsize);
        $totalamt = 0;
        for ($i = 0; $i < count($data); $i++) {

            // If next row would exceed page, break BEFORE printing it
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->monthly_analysis_sales_header_sbc($config);
                $str .= $this->reporter->begintable($layoutsize);
                $page += $count;
            }

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['barcode'], '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['itemname'], '490', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();

            $totalamt += $data[$i]['ext'];
        }


        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total:', '490', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalamt, 2), '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '490', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function monthly_sales_header_sbc($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $year     = $config['params']['dataparams']['year'];
        $month     = $config['params']['dataparams']['month'];
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";
        $fontcolor = "#150485";
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $qry2 = "select bn.comptel from branchstation as bn left join client on client.clientid = bn.clientid
         where bn.comptel <> 0 and client.center = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $headerdata2 = $this->coreFunctions->opentable($qry2);
        $gnrtDate = date('m-d-Y');
        $gnrtTime      = date('h:i:s A');


        $str .= $this->reporter->begintable($layoutsize);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
        $str .=  $this->reporter->col($this->reporter->pagenumber('Page'), '300', null, false, '1px solid ', '', 'R', $font, '11', '', '');
        $str .=  $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata2[0]->comptel, null, null, false, '3px solid', '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Monthly Sales Report', '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Report Date:' . $gnrtDate, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->col('For the Month: ' . $month . ',' . $year, '700', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Report Date: ' . $gnrtTime, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $monthname = date('F', strtotime("$year-$month-01"));

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Day', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '750', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cashier', '100', null, false, $border, 'BL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sales<br> w/ VAT', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sales<br> w/0 VAT', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Vat Output', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Net of Vat', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Avg. Run', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Running <br>Total', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Trans.<br> Count', '100', null, false, $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function monthly_sales_layout_sbc($config)
    {
        $this->reporter->linecounter = 0;
        $count = 35;
        $page = 35;
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $data = $this->monthly_sales_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->monthly_sales_header_sbc($config);

        $totalamt = 0;
        $avgTotal = 0;
        $avgCount = 0;
        $runningTotal = 0;

        $currentDate = null;
        $dateExt = 0;
        $dateNet = 0;
        $dateTotal = 0;
        $dateVat = 0;
        $dateNvat = 0;

        $dateRowCount = 0; // number of cashier rows for that date
        $dateTransSum = 0; // sum of transcount per cashier row (for avg)

        $runningTotal = 0;
        $totalamt = 0;

        // for grand total
        // GRAND TOTALS
        $grandExt   = 0;
        $grandNet   = 0;
        $grandTotal = 0;
        $grandVat   = 0;
        $grandNvat  = 0;

        $grandRowCount  = 0;
        $grandTransSum  = 0;
        $grandAvgTotal  = 0;
        $grandAvgCount  = 0;

        $printSubTotal = function () use (
            &$str,
            &$dateExt,
            &$dateNet,
            &$dateTotal,
            &$dateVat,
            &$dateNvat,
            &$dateRowCount,
            &$dateTransSum,
            &$avgTotal,
            &$avgCount,
            $layoutsize,
            $border,
            $font,
            $fontsize
        ) {
            if ($dateRowCount <= 0) return;

            $avgRun = ($avgCount > 0) ? ($avgTotal / $avgCount) : 0;
            $avgTrans = ($dateRowCount > 0) ? ($dateTransSum / $dateRowCount) : 0;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col('Sub Total:', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($dateExt != 0 ? number_format($dateExt, 2) : '-', '140', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateNet != 0 ? number_format($dateNet, 2) : '-', '140', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateTotal != 0 ? number_format($dateTotal, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateVat != 0 ? number_format($dateVat, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateNvat != 0 ? number_format($dateNvat, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($avgCount > 0 ? number_format($avgRun, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateRowCount > 0 ? number_format($avgTrans, 2) : '-', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        };

        for ($i = 0; $i < count($data); $i++) {
            // If next row would exceed page, break BEFORE printing it
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->monthly_sales_header_sbc($config);
                $str .= $this->reporter->begintable($layoutsize);
                $page += $count;
            }

            if ($currentDate !== $data[$i]['dateid']) {

                // print subtotal for previous date (not on first date)
                if (
                    $currentDate !== null
                ) {
                    $printSubTotal();

                    // reset per-date totals after printing
                    $dateExt = $dateNet = $dateTotal = $dateVat = $dateNvat = 0;
                    $dateRowCount = 0;
                    $dateTransSum = 0;
                    $avgTotal = 0;
                    $avgCount = 0;
                }

                // print date header
                $str .= $this->reporter->begintable();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]['day'], '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '750', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $currentDate = $data[$i]['dateid'];
            }

            // per-date subtotal
            $dateExt   += (float)$data[$i]['ext'];
            $dateNet   += (float)$data[$i]['net'];
            $dateTotal += (float)$data[$i]['total'];
            $dateVat   += (float)$data[$i]['vatamt'];
            $dateNvat  += (float)$data[$i]['nvat'];

            $dateRowCount++;
            $dateTransSum += (float)$data[$i]['transcount'];

            if ($data[$i]['ext'] != 0) {
                $avgTotal += $data[$i]['ext'];
                $avgCount++;
            }

            $average = ($avgCount > 0) ? ($avgTotal / $avgCount) : 0;

            $runningTotal += $data[$i]['ext'];
            $totalamt += $data[$i]['ext'];

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['cashier'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['ext'] != 0 ? number_format($data[$i]['ext'], 2) : '-', '140', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['net'] != 0 ? number_format($data[$i]['net'], 2) : '-', '140', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['total'] != 0 ? number_format($data[$i]['total'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['vatamt'] != 0 ? number_format($data[$i]['vatamt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['nvat'] != 0 ? number_format($data[$i]['nvat'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($avgCount > 0 ? number_format($average, 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($runningTotal != 0 ? number_format($runningTotal, 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['transcount'] != 0 ? number_format($data[$i]['transcount'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandExt   += (float)$data[$i]['ext'];
            $grandNet   += (float)$data[$i]['net'];
            $grandTotal += (float)$data[$i]['total'];
            $grandVat   += (float)$data[$i]['vatamt'];
            $grandNvat  += (float)$data[$i]['nvat'];

            $grandRowCount++;
            $grandTransSum += (float)$data[$i]['transcount'];

            if ($data[$i]['ext'] != 0) {
                $grandAvgTotal += (float)$data[$i]['ext'];
                $grandAvgCount++;
            }
        }
        // print the last subtotal after ther loop
        $printSubTotal();

        $grandAvgRun   = ($grandAvgCount > 0) ? ($grandAvgTotal / $grandAvgCount) : 0;
        $grandAvgTrans = ($grandRowCount > 0) ? ($grandTransSum / $grandRowCount) : 0;
        // for grandtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', '10', false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total:', '100', '10', false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandExt != 0 ? number_format($grandExt, 2) : '-', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandNet != 0 ? number_format($grandNet, 2) : '-', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandTotal != 0 ? number_format($grandTotal, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandVat != 0 ? number_format($grandVat, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandNvat != 0 ? number_format($grandNvat, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Average:', '90', '10', false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($grandAvgTrans != 0 ? number_format($grandAvgTrans, 2) : '-', '90', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'R', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', '10', false, $border, 'BL', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', '10', false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', '10', false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', '10', false, $border, 'BR', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }

    public function monthly_analysis_BIR_header_sbc($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $year     = $config['params']['dataparams']['year'];
        $month     = $config['params']['dataparams']['month'];
        $str = '';
        $layoutsize = '1500';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $fontcolor = "#150485";
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($reporttimestamp, '1200', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
        $str .=  $this->reporter->col($this->reporter->pagenumber('Page'), '200', null, false, '1px solid ', '', 'R', $font, '11', '', '');
        $str .=  $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '600', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Phone: ' . strtoupper($headerdata[0]->tel), '300', null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '600', null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $monthname = date('F', strtotime("$year-$month-01"));
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Monthly Sales Report', null, null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('For the Month of ' . $monthname . ' ' . $year, null, null, false, $border, '', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Accmulating OR', '350', null, false, $border, 'BTL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Accmulating Sales', '300', null, false, $border, 'BTL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Z-Read', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Vatable', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('VAT-Exempt', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Zero Rated', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('VAT 12%', '100', null, false, $border, 'TL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Discount', '200', null, false, $border, 'BTL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Net Sales', '100', null, false, $border, 'TLR', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Day', '50', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');

        $str .= $this->reporter->col('BEG', '125', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('END', '125', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');

        $str .= $this->reporter->col('BEG', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('END', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');

        $str .= $this->reporter->col('Counter', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Sales', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Sales', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Sales', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('S.C./ PWD', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Regular', '100', null, false, $border, 'BL', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br/>';
        return $str;
    }
    public function monthly_analysis_BIR_layout_sbc($config)
    {
        $this->reporter->linecounter = 0;
        $count = 35;
        $page = 35;
        $layoutsize = '1500';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['month'];
        $this->reportParams['orientation'] = 'l';

        $data = $this->monthly_analysis_sales_BIR_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->BIR_header($config);
        $str .= $this->reporter->begintable($layoutsize);

        $j = 0;
        $z = 0;
        $totalor = 0;
        $zcounter = 0;

        $z_read = false;

        $totalbeg = 0;
        $totalend = 0;
        $grandtotal = 0;
        $totalvals = 0;
        $totalvatex = 0;
        $totalzread = 0;
        $totalvat = 0;
        $totaldiscsr = 0;
        $totalregular = 0;
        $totalnsales = 0;

        for ($i = 0; $i < count($data); $i++) {
            $j++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($j, '50', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
            // OR
            $str .= $this->reporter->col($data[$i]['firstdoc'], '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['lastdoc'], '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['totalor'], '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
            // SALES
            if ($data[$i]['firstdoc'] != '') {
                $zcounter++;
                $z_read = true;
            } else {
                $z_read = false;
            }
            $totalsale = $data[$i]['beg'] + $data[$i]['endbal'];
            $str .= $this->reporter->col(number_format($data[$i]['beg'], 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['endbal'], 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($totalsale, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            //

            $str .= $this->reporter->col('' . ($z_read ? $zcounter : $z), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['nvat'], 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['vatex'], 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('0', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col(number_format($data[$i]['vatamt'], 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['discsr'], 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['disc'], 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, '', '', '');
            $netsales = $data[$i]['amt'] - $data[$i]['vatamt'];
            $str .= $this->reporter->col(number_format($netsales, 2), '100', null, false, $border, 'LBR', 'RT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->endrow();


            $totalvals += $data[$i]['nvat'];
            $totalvatex += $data[$i]['vatex'];

            $totalvat += $data[$i]['vatamt'];
            $totaldiscsr += $data[$i]['discsr'];
            $totalregular += $data[$i]['disc'];
            $totalbeg += $data[$i]['beg'];
            $totalend += $data[$i]['endbal'];
            $grandtotal += $totalsale;

            $totalnsales += $netsales;
        }

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
        // OR
        $str .= $this->reporter->col('', '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        // SALES

        $str .= $this->reporter->col(number_format($totalbeg, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalend, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        //
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalvals, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalvatex, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalzread, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col(number_format($totalvat, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaldiscsr, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalregular, 2), '100', null, false, $border, 'LB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalnsales, 2), '100', null, false, $border, 'LBR', 'RT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class
