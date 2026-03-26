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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use DateTime;
use DateInterval;
use DatePeriod;


// not yet done
class bir_e_sales
{
    public $modulename = 'BIR E-Sales';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'month', 'year', 'dcentername'];
        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'dbranchname.required', true);
        data_set($col1, 'year.required', true);
        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        // data_set($col1, 'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'blue']]);
        data_set($col1, 'radioprint.options', [
            // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        return $this->coreFunctions->opentable("select 
        'default' as print,
        left(now(),4) as year, '' as bmonth,
        '' as month,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername");
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
        $str = $this->report_default_layout($config);

        return $str;
    }


    public function reportDefault($config)
    {
        // QUERY
        $year = $config['params']['dataparams']['year'];
        $bmonth = $config['params']['dataparams']['bmonth'];
        $center = $config['params']['dataparams']['center'];
        $printtype = $config['params']['dataparams']['print'];

        $filter = "";
        if ($center != "") {
            $filter .= " and cntnum.center = '" . $center . "'  ";
        }

        // $query = "select min,cash,charge,branch,tin, sum(isamt) as amt,
        //          sum(iss) as iss, sum(nvat) as nvat,sum(vatex) as vatex,sum(vatamt) as vatamt,sum(lessvat) as lessvat,tax
        //          from (
        //             select brs.min,br.clientname as branch,brs.tin,  stock.isamt,
        //             case when cntnum.doc = 'CM' then stock.qty - 1 else stock.iss end as iss,
        //             si.nvat,  si.vatex,si.vatamt, si.lessvat,head.tax,
        //             (select cx.docno from head as cx where cx.cash <> 0 and cx.station=cntnum.station order by cx.dateid desc limit 1) as cash,
        //         (select cx.docno from head as cx where (cx.cheque <> 0 or cx.card <> 0) and cx.station=cntnum.station order by cx.dateid desc limit 1) as charge
        //         from glhead as head
        //         left join glstock as stock on stock.trno = head.trno
        //         left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
        //         left join cntnum on cntnum.trno = head.trno
        //         left join client as br on br.clientid = head.branch
        //         left join branchstation as brs on brs.clientid = br.clientid
        //         left join head as cx on cx.station = cntnum.station
        //         where left(cntnum.bref,3) in ('SJS','SRS')  and year(head.dateid)= '$year' and month(head.dateid)='$bmonth' $filter) as dxc
        //         group by min,cash, charge, branch, tin, tax";

        // $query = "select brs.min, dxc.cash, dxc.charge, br.clientname AS branch, brs.tin,
        //          SUM(isamt) AS amt, SUM(iss) AS iss, SUM(nvat) AS nvat,
        //          SUM(vatex) AS vatex, SUM(vatamt) AS vatamt, SUM(lessvat) AS lessvat, dxc.tax
        //    FROM ( SELECT  SUM(stock.isamt) AS isamt,
        //                  CASE WHEN cntnum.doc = 'CM' THEN SUM(stock.qty) - 1 ELSE SUM(stock.iss) END AS iss,
        //                  SUM(si.nvat) AS nvat,  SUM(si.vatex) AS vatex, SUM(si.vatamt) AS vatamt,
        //                  SUM(si.lessvat) AS lessvat, head.tax,
        //                  (SELECT cx.docno
        //                   FROM head AS cx
        //                   WHERE cx.cash <> 0 AND cx.station = cntnum.station
        //                   ORDER BY cx.dateid DESC LIMIT 1) AS cash,
        //                  (SELECT cx.docno
        //                   FROM head AS cx
        //                   WHERE (cx.cheque <> 0 OR cx.card <> 0) AND cx.station = cntnum.station
        //                   ORDER BY cx.dateid DESC LIMIT 1) AS charge,head.branch
        //          FROM glhead AS head
        //          LEFT JOIN glstock AS stock ON stock.trno = head.trno
        //          LEFT JOIN hstockinfo AS si ON si.trno = stock.trno AND si.line = stock.line
        //          LEFT JOIN cntnum ON cntnum.trno = head.trno
        //          LEFT JOIN head AS cx ON cx.station = cntnum.station
        //          WHERE LEFT(cntnum.bref, 3) IN ('SJS', 'SRS')
        //            AND YEAR(head.dateid) = '2025'
        //            AND MONTH(head.dateid) = '07'
        //         group by cntnum.doc,head.tax,cntnum.station,head.branch) AS dxc

        //    LEFT JOIN client AS br ON br.clientid =  dxc.branch
        //    LEFT JOIN branchstation AS brs ON brs.clientid = br.clientid
        //    GROUP BY brs.min, cash, charge, br.clientname, brs.tin, dxc.tax";
        switch ($printtype) {
            case 'default':
                $query = "select br.client as brcode, brs.station, brs.min, br.clientname as branch, brs.tin, sum(ext) as amt, 0.0 as iss, sum(nvat) as nvat,
                    sum(vatex) as vatex, sum(nvat+vatamt) as vatamt, sum(lessvat) as lessvat, 0.0 as tax,
                    (select docno from head where doc='BP' and bref='SI' and cash<>0 and year(dateid) = '$year' and month(dateid) = '$bmonth' and head.station=brs.station and head.branch = br.client order by docno desc limit 1) as cashor,
                    (select docno from head where doc='BP' and bref='SI' and cash=0 and year(dateid) = '$year' and month(dateid)= '$bmonth' and head.station=brs.station and head.branch = br.client order by docno desc limit 1) as cardor

                    from (

                    select head.branch, cntnum.station, head.docno, item.barcode, item.itemname,
                    sum(case cntnum.doc when 'CM' then (stock.ext-si.sramt-si.pwdamt-si.lessvat)*-1 else (case item.barcode when '*' then (stock.ext-si.sramt-si.pwdamt-si.lessvat)*-1 else (stock.ext-si.sramt-si.pwdamt-si.lessvat) end) end) as ext,
                    sum(case cntnum.doc when 'CM' then si.pwdamt*-1 else (case item.barcode when '*' then si.pwdamt*-1 else si.pwdamt end) end) as pwdamt,
                    sum(case cntnum.doc when 'CM' then si.sramt*-1 else (case item.barcode when '*' then si.sramt*-1 else si.sramt end) end) as sramt,
                    sum(case cntnum.doc when 'CM' then si.lessvat*-1 else (case item.barcode when '*' then si.lessvat*-1 else si.lessvat end) end) as lessvat,
                    sum(si.nvat) as nvat, sum(si.vatamt) as vatamt, sum(si.vatex) as vatex

                    from glhead as head left join glstock as stock on stock.trno = head.trno left join cntnum on cntnum.trno = head.trno
                    left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line left join item on item.itemid = stock.itemid

                    where left(cntnum.bref, 3) IN ('SJS', 'SRS') and year(head.dateid)= '$year' and month(head.dateid) = '$bmonth' $filter

                    group by head.branch, cntnum.station, head.docno, item.barcode, item.itemname

                    ) as dxc
                    left join client as br on br.clientid =  dxc.branch
                    left join branchstation as brs on brs.clientid = br.clientid and brs.station = dxc.station

                    group by br.client, brs.station, brs.min, br.clientname, brs.tin";
                break;
            case 'CSV':
                $query = "select   brs.tin as `TIN`,  br.clientname as `BRANCH`, brs.min as `MIN`,
                     (select docno from head where doc='BP' and bref='SI' and cash<>0 and year(dateid) = '$year' and month(dateid) = '$bmonth' and head.station=brs.station and head.branch = br.client order by docno desc limit 1) as `CASH`,
                    (select docno from head where doc='BP' and bref='SI' and cash=0 and year(dateid) = '$year' and month(dateid)= '$bmonth' and head.station=brs.station and head.branch = br.client order by docno desc limit 1) as `CARD`,
                    sum(nvat+vatamt) as  `VATABLE_SALES`, 0 as `VAT_ZERO_RATED_SALES`,
                    sum(vatex) as `VAT_EXEMPT_SALES`,
                    0 as  `SALES_SUBJECT_TO_OTHER_PERCENTAGE_TAXES`,
                    0 as `TOTAL`,
                    brs.station
                  
                    from (

                    select head.branch, cntnum.station, head.docno, item.barcode, item.itemname,
                    sum(case cntnum.doc when 'CM' then (stock.ext-si.sramt-si.pwdamt-si.lessvat)*-1 else (case item.barcode when '*' then (stock.ext-si.sramt-si.pwdamt-si.lessvat)*-1 else (stock.ext-si.sramt-si.pwdamt-si.lessvat) end) end) as ext,
                    sum(case cntnum.doc when 'CM' then si.pwdamt*-1 else (case item.barcode when '*' then si.pwdamt*-1 else si.pwdamt end) end) as pwdamt,
                    sum(case cntnum.doc when 'CM' then si.sramt*-1 else (case item.barcode when '*' then si.sramt*-1 else si.sramt end) end) as sramt,
                    sum(case cntnum.doc when 'CM' then si.lessvat*-1 else (case item.barcode when '*' then si.lessvat*-1 else si.lessvat end) end) as lessvat,
                    sum(si.nvat) as nvat, sum(si.vatamt) as vatamt, sum(si.vatex) as vatex

                    from glhead as head left join glstock as stock on stock.trno = head.trno left join cntnum on cntnum.trno = head.trno
                    left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line left join item on item.itemid = stock.itemid

                    where left(cntnum.bref, 3) IN ('SJS', 'SRS') and year(head.dateid)= '$year' and month(head.dateid) = '$bmonth' $filter

                    group by head.branch, cntnum.station, head.docno, item.barcode, item.itemname

                    ) as dxc
                    left join client as br on br.clientid =  dxc.branch
                    left join branchstation as brs on brs.clientid = br.clientid and brs.station = dxc.station

                    group by br.client, brs.station, brs.min, br.clientname, brs.tin";

                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    private function default_header($config)
    {
        $center = $config['params']['center'];
        $year = $config['params']['dataparams']['year'];
        $bmonth = $config['params']['dataparams']['bmonth'];
        $str = '';
        $layoutsize = '1200';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $qry = "select name from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        // var_dump($year);//2025

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name, '1200', null, false, $border, '', 'C', $font, '25', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Monthly Sales Report', '1200', null, false, $border, '', 'C', $font, '20', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $monthName = date("F", mktime(0, 0, 0, $bmonth, 1));

        // $year = date("F", mktime(0, 0, 0, 8, 1, $year));

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('For the Month of ' . $monthName . ' ' . $year, '1200', null, false, $border, '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Last SI# : ', '120', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '980', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Vatable', '135', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Vat Zero', '135', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Vat Exempt', '135', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sales Subject to', '135', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '140', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Branch', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Min', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cash', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Charge', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sales', '135', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rated Sales', '135', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sales', '135', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('other percentage taxes', '135', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        return $str;
    }

    public function report_default_layout($config)
    {
        $result = $this->reportDefault($config);

        $this->reporter->linecounter = 0;
        $count = 35;
        $page = 35;
        $layoutsize = '1200';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->default_header($config);

        $gtotal = 0;
        $ctotal = 0;
        for ($i = 0; $i < count($result); $i++) {
            $zerorated = 0;
            $sub = 0;
            $vatamt = isset($result[$i]->vatamt) ? $result[$i]->vatamt : 0;
            $vatexx = isset($result[$i]->vatex) ? $result[$i]->vatex : 0;
            $total = $vatamt + $vatexx + $zerorated + $sub;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(isset($result[$i]->tin) ? $result[$i]->tin : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($result[$i]->branch) ? $result[$i]->branch : '', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($result[$i]->min) ? $result[$i]->min : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($result[$i]->cashor) ? $result[$i]->cashor : '', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($result[$i]->cardor) ? $result[$i]->cardor : '', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($vatamt, 2), '135', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($zerorated, 2), '135', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($vatexx, 2), '135', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($sub, 2), '135', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '140', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->endrow();
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
            $ctotal += $total;
        }

        $gtotal += $ctotal;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(' ', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL : ', '135', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gtotal, 2), '140', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();


        return $str;
    }

    public function reportdatacsv($config)
    {

        $data = $this->reportDefault($config);
        foreach ($data as $row => $value) {
            $tlhere = $value->VATABLE_SALES + $value->VAT_EXEMPT_SALES + $value->VAT_ZERO_RATED_SALES +  $value->SALES_SUBJECT_TO_OTHER_PERCENTAGE_TAXES;
            $value->TOTAL = number_format($tlhere, 2);
            $value->VATABLE_SALES = number_format($value->VATABLE_SALES, 2);
            $value->VAT_ZERO_RATED_SALES = number_format($value->VAT_ZERO_RATED_SALES, 2);
            $value->VAT_EXEMPT_SALES = number_format($value->VAT_EXEMPT_SALES, 2);
            $value->SALES_SUBJECT_TO_OTHER_PERCENTAGE_TAXES = number_format($value->SALES_SUBJECT_TO_OTHER_PERCENTAGE_TAXES, 2);
            unset($value->station);
        }
        $status =  true;
        $msg = 'Generating CSV successfully';
        if (empty($data)) {
            $status =  false;
            $msg = 'No data Found';
        }
        return ['status' => $status, 'msg' => $msg, 'data' => $data, 'params' => $this->reportParams, 'name' => 'BirESales'];
    }
}//end class