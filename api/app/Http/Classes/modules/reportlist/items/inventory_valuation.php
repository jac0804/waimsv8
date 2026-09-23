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

class inventory_valuation
{
    public $modulename = 'Inventory Valuation with Cost';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:800px;max-width:800px;';
    public $directprint = false;

    // orientations: portrait=p, landscape=l
    // formats: letter, a4, legal
    // layoutsize: reportWidth
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
        // $fields = ['radioprint', 'radiooption'];
        $fields = ['radioprint', 'start'];
        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'radiooption.options', array(
        //     ['label' => 'Default', 'value' => 0, 'color' => 'green'],
        //     // ['label' => 'Item Brand', 'value' => 1, 'color' => 'green'],
        //     // ['label' => 'Item Category', 'value' => 2, 'color' => 'green'],
        //     // ['label' => 'Car Brand', 'value' => 3, 'color' => 'green'],
        //     // ['label' => 'No Price', 'value' => 4, 'color' => 'green'],
        //     // ['label' => 'No Price/With Picture', 'value' => 5, 'color' => 'green'],
        //     // ['label' => 'With Price/With Picture', 'value' => 6, 'color' => 'green'],
        // ));

        $fields = ['ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'wh'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'divsion.label', 'Group');
        data_set($col2, 'luom.action', 'replookupuom');
        data_set($col2, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col2, 'subcatname.action', 'lookupsubcatitemstockcard');

        unset($col2['divsion']['labeldata']);
        unset($col2['class']['labeldata']);
        unset($col2['model']['labeldata']);
        unset($col2['labeldata']['divsion']);
        unset($col2['labeldata']['class']);
        unset($col2['labeldata']['model']);
        data_set($col2, 'divsion.name', 'stockgrp');
        data_set($col2, 'class.name', 'classic');
        data_set($col2, 'model.name', 'modelname');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $startdate = date('Y-m-d', strtotime($this->othersClass->getCurrentTimeStamp()));
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select
        'default' as print,
        0 as poption,
        '" . $startdate . "' as start,
        left(now(),10) as end,
        '' as infratype,
        '' as category,
        '' as categoryname,
        0 as catid,
        '' as groupid,
        '' as stockgrp,
        '' as divsion,
        '' as wh, 
        '' as whid,
        '' as whname,
        0 as itemid,
        '' as itemname,
        '' as barcode,
        '' as ditemname,
        '' as uom,
        0 as brandid,
        '' as brandname,
        '' as brand,
        0 as modelid,
        '' as modelname,
        '' as model,
        0 as classid,
        '' as classic,
        '' as class,
        '' as subcatname,
        '' as subcat
        ");
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config);
    }

    // {
    //     $companyid = $config['params']['companyid'];
    //     $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    //     $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    //     $poption = $config['params']['dataparams']['poption'];
    //     $groupid = $config['params']['dataparams']['groupid'];
    //     $category = $config['params']['dataparams']['catid'];
    //     $whid = $config['params']['dataparams']['whid'];
    //     $whname = $config['params']['dataparams']['whname'];
    //     $categoryname = $config['params']['dataparams']['category'];
    //     $stockgrp = $config['params']['dataparams']['stockgrp'];

    //     $filter = '';
    //     $leftjoin = '';
    //     $orderby = '';
    //     $query = '';

    //     if ($stockgrp != "") {
    //         $filter .= " and stockgrp.stockgrp_id =$groupid";
    //         $leftjoin .= " left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid";
    //     }
    //     if ($whname != "") {
    //         $filter .= " and stock.whid = $whid";
    //     }
    //     if ($categoryname != "") {
    //         $filter .= " and item.category=$category";
    //         $leftjoin .= " left join itemcategory as cat on cat.line = item.category";
    //     }

    //     switch ($poption) {
    //         case 1:
    //             $orderby = "order by brand";
    //             break;
    //         case 2:
    //             $orderby = "order by category";
    //             break;
    //         case 3:
    //             $orderby = "order by cb.brand";
    //             break;
    //         case 4:
    //         case 5:
    //         case 6:
    //             // picture-based price list: only show items that actually have a picture
    //             // $filter = " and i.picture is not null and i.picture <> '' ";
    //             $orderby = "order by partno";
    //             break;
    //     }


    //     $query = "select item.barcode,item.itemname, item.uom, sum(stock.rrcost) as value, round(max(stat.bal),2) as bal,
    //             max(stat.cost) as cost, stock.loc
    //             from lahead as head
    //             left join lastock as stock on stock.trno=head.trno
    //             left join client as wh on wh.clientid=stock.whid
    //             left join item on item.itemid=stock.itemid
    //             left join part_masterfile as partgrp on partgrp.part_id = item.part
    //             left join rrstatus as stat on stat.itemid = stock.itemid
    //             $leftjoin
    //             where head.dateid<= '$start'
    //             and ifnull(item.barcode,'')<>'' $filter
    //             group by item.barcode,item.itemname, item.uom, stock.loc

    //             union all

    //             select item.barcode,item.itemname, item.uom, sum(stock.rrcost) as value, round(max(stat.bal),2) as bal,
    //             max(stat.cost) as cost, stock.loc
    //             from glhead as head
    //             left join glstock as stock on stock.trno=head.trno
    //             left join client as wh on wh.clientid=stock.whid
    //             left join item on item.itemid=stock.itemid
    //             left join part_masterfile as partgrp on partgrp.part_id = item.part
    //             left join rrstatus as stat on stat.itemid = stock.itemid
    //             $leftjoin
    //             where  head.dateid<= '$start'
    //             and ifnull(item.barcode,'')<>'' $filter
    //             group by item.barcode,item.itemname, item.uom, stock.loc";
    //     // var_dump($query);
    //     return $this->coreFunctions->opentable($query);
    // }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $poption = $config['params']['dataparams']['poption'];
        $groupid = $config['params']['dataparams']['groupid'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $category = $config['params']['dataparams']['category'];
        $wh = $config['params']['dataparams']['wh'];
        $whid = $config['params']['dataparams']['whid'];
        $whname = $config['params']['dataparams']['whname'];
        $categoryname = $config['params']['dataparams']['category'];
        $stockgrp = $config['params']['dataparams']['stockgrp'];
        $barcode = $config['params']['dataparams']['barcode'];
        $itemid = $config['params']['dataparams']['itemid'];
        $subcat = $config['params']['dataparams']['subcat'];
        $subcatname   = $config['params']['dataparams']['subcatname'];
        $brandname        = $config['params']['dataparams']['brandname'];
        $brandid = $config['params']['dataparams']['brandid'];
        $modelname    = $config['params']['dataparams']['modelname'];
        $modelid = $config['params']['dataparams']['modelid'];
        $uom          = $config['params']['dataparams']['uom'];
        $classid = $config['params']['dataparams']['classid'];
        $classname    = $config['params']['dataparams']['classic'];

        $filter = '';
        $leftjoin = '';
        $orderby = '';

        if ($stockgrp != "") {
            $filter .= " and stockgrp.stockgrp_id = $groupid";
            $leftjoin .= " left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid";
        }
        if ($wh != "") {
            $filter .= " and stock.whid = $whid";
        }
        if ($barcode != "") {
            $filter .= " and stock.itemid = $itemid";
        }
        if ($categoryname != "") {
            $filter .= " and item.category = '" . $category . "'";
            $leftjoin .= " left join itemcategory as cat on cat.line = item.category";
        }
        if ($uom != "") {
            $filter .= " and stock.uom='" . $uom . "'";
        }
        if ($brandname != "") {
            $filter .= " and item.brand=" . $brandid;
        }
        if ($modelname != "") {
            $filter .= " and item.model=" . $modelid;
        }
        if ($classname != "") {
            $filter .= " and item.class=" . $classid;
        }
        if ($subcatname != "") {
            $filter .= " and item.subcat='" . $subcat . "'";
        }

        switch ($poption) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                $orderby = "order by barcode";
                break;
        }

        $query = "
        select barcode, itemname, uom, whid, loc,
        sum(value) as value,
        sum(bal)   as bal,
        max(cost)  as cost
        from (
        select item.barcode,
        item.itemname,
        item.uom,
        stock.whid, stock.loc,
        sum(stock.rrcost) as value,
        sum(stock.qty - stock.iss) as bal,
        max(stock.cost) as cost
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join client  as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join part_masterfile as partgrp on partgrp.part_id = item.part
        $leftjoin
        where head.dateid <= '$start'
        and item.barcode is not null
        and item.barcode <> ''
        $filter
        group by item.barcode, item.itemname, item.uom, stock.whid, stock.loc
        union all
        select item.barcode,
        item.itemname,
        item.uom,
        stock.whid, stock.loc,
        sum(stock.rrcost) as value,
        sum(stock.qty - stock.iss) as bal,
        max(stock.cost) as cost
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join client  as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join part_masterfile as partgrp on partgrp.part_id = item.part
        $leftjoin
        where head.dateid <= '$start'
        and item.barcode is not null
        and item.barcode <> ''
        $filter
        group by item.barcode, item.itemname, item.uom, stock.whid, stock.loc) as v
        group by barcode, itemname, uom, whid, loc
        $orderby";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $poption = $config['params']['dataparams']['poption'];
        $start = date("M d, Y", strtotime($config['params']['dataparams']['start']));
        $end = date("M-d-Y", strtotime($config['params']['dataparams']['end']));
        $printDate = date("m/d/y");
        $printTime = date("g:i:s A");

        $grouplbl = '';
        $catlabl = '';
        $whlbl = '';
        $itemnamelbl = '';
        $groupname = $config['params']['dataparams']['stockgrp'];
        $catname = $config['params']['dataparams']['category'];
        $whname = $config['params']['dataparams']['whname'];
        $wh = $config['params']['dataparams']['wh'];
        $itemname = $config['params']['dataparams']['itemname'];

        $str = '';
        $layoutsize = '1000';
        $font = 'Times New Roman';
        $fontsize = "10";
        $fontsize2 = "9";
        $border = "1px solid ";
        $groupby = "";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        // $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($reporttimestamp, '800', null, false, '', '', 'L', $font, $fontsize);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        if ($groupname != '') {
            $grouplbl = $groupname;
        } else {
            $grouplbl = " All Groups";
        }

        if ($wh != '') {
            $whlbl = $whname;
        } else {
            $whlbl = " All Warehouse";
        }

        if ($catname != '') {
            $catlabl = $catname;
        } else {
            $catlabl = " All Category";
        }
        if ($itemname != '') {
            $itemnamelbl = $itemname;
        } else {
            $itemnamelbl = " All Stock";
        }

        switch ($poption) {
            case 1:
                $groupby = "ITEM BRAND";
                break;
            case 2:
                $groupby = "ITEM CATEGORY";
                break;
            case 3:
                $groupby = "CAR BRAND";
                break;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('as of' . ' ' . $start, null, null, false, '10px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>Group: </b> <u>' . $grouplbl . '</u> <b>Category: </b> <u>' . $catlabl . ' ' . '</u> <b>Size:</b> <u>' . ' All Size</u>', null, null, false, '10px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>WH: </b> <u>' . $whlbl . '</u> <b>Include: </b><u>' . $itemnamelbl .' </u> <b>Stocks:</b> <u>' . 'All Stocks </u>', null, null, false, '10px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<i>Print Date : ' . $printDate . '</i>', '220', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '470');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '3px solid', 'B', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // 120, 320, 100, 120, 100, 120, 120
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Product Code', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Product Name', '320', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('U O M', '100', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Stocks', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Lot#', '100', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Latest Cost', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('Value', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '310', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '110', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize2 = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];
        $poption = $config['params']['dataparams']['poption'];

        $result = $this->data_query($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $rowCount = 50;
        $page = 55;
        $this->reporter->linecounter = 0;
        $currentLabel = '';
        $grpLabel = '';
        $price = 0;
        $value = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        // $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '125px;margin-top:5px;');
        $str .= $this->displayHeader($config);

        foreach ($result as $key => $data) {
            $lines = $this->reporter->estimateRowLines([
                [$data->barcode, 120, ''],
                [$data->itemname, 310, ''],
                [$data->uom, 90, ''],
                [$data->bal, 110, ''],
                [$data->loc, 90, ''],
                [number_format($data->cost, 2), 110, ''],
                [number_format($data->value, 2), 110, ''],
            ], $fontsize2);

            // Break only when the row genuinely won't fit.
            if ($this->reporter->linecounter + $lines > $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $this->reporter->linecounter = 0;
            }

            for ($l = 0; $l < $lines; $l++) {
                $str .= $this->reporter->addline();
            }

            $value = ($data->bal * $data->cost);

            // 120, 320, 100, 120, 100, 120, 120
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->barcode, '120', null, false, '2px solid', '', 'CT', $font, $fontsize2, '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, '', '', '');
            $str .= $this->reporter->col($data->itemname, '310', null, false, '2px solid', '', 'LT', $font, $fontsize2, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->uom, '90', null, false, '2px solid', '', 'CT', $font, $fontsize2, '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->bal == 0 ? '-' : number_format($data->bal, 0), '110', null, false, '2px solid', '', 'CT', $font, $fontsize2, '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->loc, '90', null, false, '2px solid', '', 'CT', $font, $fontsize2, '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, '', '', '');
            $str .= $this->reporter->col($data->cost == 0 ? '-' : number_format($data->cost, 2), '110', null, false, '2px solid', '', 'RT', $font, $fontsize2, '', '', '');
            $str .= $this->reporter->col('  ', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, '', '', '');
            $str .= $this->reporter->col($value == 0 ? '-' : number_format($value, 2), '110', null, false, '2px solid', '', 'RT', $font, $fontsize2, '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '2px solid', '', 'RT', $font, $fontsize2, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // error_log(sprintf(
            //     'key=%d barcode=%s lines=%d linecounter=%d page=%d',
            //     $key, $data->barcode, $lines, $this->reporter->linecounter, $page
            // ));


            $item = $data->barcode;
            // $subtotal = $subtotal + $data->price;
            // $amt = $amt + $data->price;
            // if ($this->reporter->linecounter >= $page) {
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->displayHeader($config);
            //     // $page = $page + $rowCount;
            //     $this->reporter->linecounter = 0; 
            // }
        }

        $str .= $this->reporter->endreport();
        return $str;
    }
} // end class
