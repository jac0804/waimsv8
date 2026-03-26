<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class inventory_report
{
    public $modulename = 'Inventory Report';
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
        $fields = ['radioprint', 'start', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.label', 'Balance as of');

        $fields = ['radiolayoutformat'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radiolayoutformat.label', 'Excluded Warehouse');
        data_set(
            $col2,
            'radiolayoutformat.options',
            [
                ['label' => 'Exclude Dummy Warehouse', 'value' => '1', 'color' => 'orange'],
                ['label' => 'None', 'value' => '0', 'color' => 'orange']
            ]
        );

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 
            'default' as print,
            left(now(),10) as start,
            '' as client,
            '' as clientname,
            '' as wh,
            '' as whname,
            '' as dwhname,
            0 as whid,
            '1' as layoutformat";

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
        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');

        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_Query($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_Query($config)
    {
        $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $wh         = $config['params']['dataparams']['wh'];
        $whid       = $config['params']['dataparams']['whid'];
        $excwh      = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '';

        $filter     = "";
        $filter1   = "";
        if ($wh != "") {
            $filter .= ' and stock.whid=' . $whid;
            $filter1 = " and whid = " . $whid;
        }

        $filterexcwh = "";
        $filterexcwh1 = "";
        if ($excwh) {
            $filterexcwh = " and stock.whid <> 1014";
            $filterexcwh1 = " and whid <> 1014";
        }

        $query = "select itemid, barcode, itemname, sizeid, color, oqty as qtyperpack, tqty as qtypercase, sum(qty - iss) stockonhand, amt as sellingprice, minimum, maximum,
                (select cost / forex from rrstatus where itemid = ib.itemid " . $filter1 . $filterexcwh1 . " order by dateid desc limit 1) as rmbcost,
                (select cost from rrstatus where itemid = ib.itemid " . $filter1 . $filterexcwh1 . " order by dateid desc limit 1) as phpcost,uom
                from (select item.itemid, item.barcode, item.itemname, item.sizeid, item.color, item.oqty, item.tqty, stock.qty, stock.iss, item.amt, item.minimum, item.maximum,item.uom
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join client as wh on wh.clientid = stock.whid
                where head.dateid <= '" . $asof . "' and ifnull(item.barcode, '') <> '' " . $filter . $filterexcwh . "
                union all
                select item.itemid, item.barcode, item.itemname, item.sizeid, item.color, item.oqty, item.tqty, stock.qty, stock.iss, item.amt, item.minimum, item.maximum,item.uom
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join client as wh on wh.clientid = stock.whid
                where head.dateid <= '" . $asof . "' and ifnull(item.barcode, '') <> '' " . $filter . $filterexcwh . ") as ib
                group by itemid, barcode, itemname, sizeid, color, oqty, tqty, amt, minimum, maximum,uom
                order by itemname";

        return $query;
    }

    private function default_displayHeader($config)
    {
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = '10';
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $asof     = $config['params']['dataparams']['start'];
        $whname   = $config['params']['dataparams']['whname'];

        if ($whname == '') {
            $whname = "ALL";
        }

        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('INVENTORY REPORT', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As of date : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function default_header_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->printline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM NAME', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('SIZE', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('COLOR', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('QTY PER PACK', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('QTY PER CASE', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('RMB COST', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('PHP COST', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('SELLING PRICE', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('STOCKS ON HAND', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('MIN', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('MAX', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = '10';
        $count = 51;
        $page = 50;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->default_header_cols($this->reportParams['layoutSize'], $border, $font, $fontsize, $config);

        $multiheader = true;

        foreach ($result as $key => $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->itemname, '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->sizeid, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->color, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->qtyperpack, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->qtypercase, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->rmbcost, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->phpcost, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->sellingprice, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->stockonhand, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->minimum, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->maximum, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();

            if ($multiheader) {
                if ($this->reporter->linecounter >= $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$allowfirstpage)  $str .= $this->default_displayHeader($config);
                    $str .= $this->default_header_cols($this->reportParams['layoutSize'], $border, $font, $fontsize, $config);
                    $page = $page + $count;
                }
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= '<br/>';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class