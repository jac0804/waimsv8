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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class out_of_stock_sales_journal_pos
{
    public $modulename = 'Out of Stock Sales Journal POS';
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
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'print'];

        $col1 = $this->fieldClass->create($fields);
        data_set(
            $col1,
            'radioprint.options',
            [
                ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
                ['label' => 'Excel Raw Data', 'value' => 'excel', 'color' => 'red'],
            ]
        );
        return array('col1' => $col1);
    }
    public function paramsdata($config)
    {
        $companyid       = $config['params']['companyid'];
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr =
            "select 
        'default' as print,
         adddate(left(now(),10),-360) as start,
         left(now(),10) as end,
           '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername";
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
        $radioprint        = $config['params']['dataparams']['print'];
        $data = $this->default_query($config);

        if ($radioprint == 'default') {
            return  $this->report_default_layout($config, $data);
        } else {
            return  $this->excelrawdata_layout($config, $data);
        }
    }
    public function default_query($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }

        $query = "select head.docno,date(head.dateid) as dateid,
        item.barcode,item.itemname,item.itemid,head.trno,wh.client as warehouse
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join client as wh on wh.clientid = stock.whid
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        where head.doc = 'SJ' and left(num.bref,3) = 'SJS' and stock.isqty2 <> 0 and stock.trno is not null and 
        date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
        group by item.barcode,item.itemname,head.dateid,item.itemid,head.trno,head.docno,wh.client
        order by item.itemname,item.barcode ";

        return $this->coreFunctions->opentable($query);
    }
    public function displayitem($trno, $itemid)
    {
        $query = "select stock.itemid,stock.trno,sum(stock.original_qty) as posqty,sum(stock.isqty2) as oosqty,count(stock.trno) as transc, sum((stock.isamt * stock.isqty2)-(info.discamt+info.lessvat+info.pwdamt+info.sramt)) as amt from lastock as stock
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        where stock.trno =? and stock.itemid =? group by stock.trno,stock.itemid";
        return $this->coreFunctions->opentable($query, [$trno, $itemid]);
    }
    public function default_header($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $font_size = '10';
        $count = 50;
        $page = 55;
        $layoutsize = 1200;
        $str = '';
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $font = $this->companysetup->getrptfont($config['params']);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OUT OF STOCK SALES JOURNAL POS', null, null, false, $border, '', 'L', $font, '14', 'B', '', '') . '</br>';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From: ' . $start . ' to ' . $end, null, null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DocNo', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Barcode', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Item Description', '360', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Total Amount', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('POS Qty', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Out of Stock Qty', '120', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function report_default_layout($config, $data)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $count = 50;
        $page = 55;
        $layoutsize = 1200;
        $str = '';
        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config);
        $totaloosqty = 0;
        $totlamt = 0;
        $gtotlamt = 0;
        $grandtotal = 0;
        $str .= $this->reporter->begintable($layoutsize);
        $barcode = '';
        $i = 0;
        foreach ($data as $key => $value) {

            $displayitem = $this->displayitem($data[$key]->trno, $data[$key]->itemid);

            if ($i != 0) {
                if ($barcode == "" || $barcode != $data[$key]->barcode) {
                    $barcode = $data[$key]->barcode;
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '360', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('Sub Total: ', '100', null, false, $border, '', 'R', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col(number_format($totlamt, 2), '100', null, false, $border, 'TB', 'R', $font, '10', 'B', '', '1px');
                    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'R', $font, '10', 'B', '', '1px');
                    $str .= $this->reporter->col(number_format($totaloosqty, 2), '120', null, false, $border, 'TB', 'R', $font, '10', 'B', '', '1px');
                    $str .= $this->reporter->endrow();
                    $totaloosqty = 0;
                    $totlamt = 0;

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '360', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, '10', '', '', '1px');
                    $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, '10', '', '', '1px');
                    $str .= $this->reporter->endrow();
                }
            } else {
                $barcode = $data[$key]->barcode;
            }
            $i++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$key]->docno, '200', null, false, $border, '', 'C', $font, '10', '', '', '1px');
            $str .= $this->reporter->col($data[$key]->dateid, '100', null, false, $border, '', 'C', $font, '10', '', '', '1px');
            $str .= $this->reporter->col($data[$key]->barcode, '150', null, false, $border, '', 'L', $font, '10', '', '', '1px');
            $str .= $this->reporter->col($data[$key]->itemname, '360', null, false, $border, '', 'L', $font, '10', '', '', '1px');
            $str .= $this->reporter->col($data[$key]->warehouse, '100', null, false, $border, '', 'L', $font, '10', '', '', '1px');
            $str .= $this->reporter->col(number_format($displayitem[0]->amt, 2), '100', null, false, $border, '', 'R', $font, '10', '', '', '1px');
            $str .= $this->reporter->col(number_format($displayitem[0]->posqty, 2), '70', null, false, $border, '', 'R', $font, '10', '', '', '1px');
            $str .= $this->reporter->col(number_format($displayitem[0]->oosqty, 2), '120', null, false, $border, '', 'R', $font, '10', '', '', '1px');
            $str .= $this->reporter->endrow();
            $totaloosqty +=  $displayitem[0]->oosqty;
            $grandtotal += $displayitem[0]->oosqty;

            $totlamt +=  $displayitem[0]->amt;
            $gtotlamt +=  $displayitem[0]->amt;


            // last sub
            if ($i == count($data)) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, '10', '', '', '1px');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                $str .= $this->reporter->col('', '360', null, false, $border, '', 'L', $font, '10', '', '', '1px');
                $str .= $this->reporter->col('Sub Total: ', '100', null, false, $border, '', 'R', $font, '10', '', '', '1px');
                $str .= $this->reporter->col(number_format($totlamt, 2), '100', null, false, $border, 'TB', 'R', $font, '10', 'B', '', '1px');
                $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'R', $font, '10', 'B', '', '1px');
                $str .= $this->reporter->col(number_format($totaloosqty, 2), '120', null, false, $border, 'TB', 'R', $font, '10', 'B', '', '1px');
                $str .= $this->reporter->endrow();
            }
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total: ', '200', null, false, $border, 'T', 'L', $font, '10', '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '10', '', '', '1px');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, '10', '', '', '1px');
        $str .= $this->reporter->col('', '360', null, false, $border, 'T', 'L', $font, '10', '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '10', '', '', '1px');
        $str .= $this->reporter->col(number_format($gtotlamt, 2), '100', null, false, $border, 'T', 'R', $font, '10', 'B', '', '1px');
        $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, '10', 'B', '', '1px');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '120', null, false, $border, 'T', 'R', $font, '10', 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    public function excelrawdata_header($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $font_size = '10';
        $count = 50;
        $page = 55;
        $layoutsize = '1200';
        $str = '';
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $font = $this->companysetup->getrptfont($config['params']);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DocNo', '200', null, false, $border, '', 'C', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Date', '100', null, false, $border, '', 'C', $font, $font_size, 'B', '', '3px');

        $str .= '<td style="mso-number-format:\@;font-family: Century Gothic; font-size: 13px;width:150px;font-weight: bold;">Barcode</td>';


        $str .= $this->reporter->col('Item Description', '360', null, false, $border, '', 'L', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Warehouse', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Total Amount', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('POS Qty', '70', null, false, $border, '', 'R', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->col('Out of Stock Qty', '120', null, false, $border, '', 'R', $font, $font_size, 'B', '', '3px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function excelrawdata_layout($config, $data)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $count = 50;
        $page = 55;
        $layoutsize = '1200';
        $str = '';
        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->excelrawdata_header($config);
        $totaloosqty = 0;
        $totlamt = 0;
        $gtotlamt = 0;
        $grandtotal = 0;
        $str .= $this->reporter->begintable($layoutsize);
        $barcode = '';
        $i = 0;
        foreach ($data as $key => $value) {

            $displayitem = $this->displayitem($data[$key]->trno, $data[$key]->itemid);

            $barcode = $data[$key]->barcode;
            $string_number = "" . $barcode;

            $i++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$key]->docno, '200', null, false, $border, '', 'C', $font, '10', '', '', '1px');
            $str .= $this->reporter->col($data[$key]->dateid, '100', null, false, $border, '', 'C', $font, '10', '', '', '1px');

            $str .= '<td style="mso-number-format:\@;font-family: Century Gothic; font-size: 13px;width:150px">' . $barcode . '</td>';
            // $str .= $this->reporter->col('<td style="mso-number-format:\@;font-family: Century Gothic; font-size: 13px;width:150px">' . $barcode . '</td>', '100', null, false, $border, '', 'L', $font, '10', '', '', '1px');


            $str .= $this->reporter->col($data[$key]->itemname, '360', null, false, $border, '', 'L', $font, '10', '', '', '1px');
            $str .= $this->reporter->col($data[$key]->warehouse, '100', null, false, $border, '', 'L', $font, '10', '', '', '1px');
            $str .= $this->reporter->col(number_format($displayitem[0]->amt, 2), '100', null, false, $border, '', 'R', $font, '10', '', '', '1px');
            $str .= $this->reporter->col(number_format($displayitem[0]->posqty, 2), '70', null, false, $border, '', 'R', $font, '10', '', '', '1px');
            $str .= $this->reporter->col(number_format($displayitem[0]->oosqty, 2), '120', null, false, $border, '', 'R', $font, '10', '', '', '1px');
            $str .= $this->reporter->endrow();
            $totaloosqty +=  $displayitem[0]->oosqty;
            $grandtotal += $displayitem[0]->oosqty;

            $totlamt +=  $displayitem[0]->amt;
            $gtotlamt +=  $displayitem[0]->amt;
        }

        return $str;
    }
}
