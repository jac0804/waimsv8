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

class inventory_balance_per_warehouse_report
{
    public $modulename = 'Inventory Balance Per Warehouse';
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

        $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'radiolayoutformat'];

        if ($companyid == 47) { //kitchenstar
            array_push($fields,'dwhname');
        }

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'start.label', 'Balance as of');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
        data_set($col1, 'luom.action', 'replookupuom');

        if ($companyid == 47) { //kitchenstar
            data_set(
                $col1,
                'radiolayoutformat.options',
                [
                    ['label' => 'Warehouse with balance only', 'value' => '1', 'color' => 'teal'],
                    ['label' => 'All Warehouse', 'value' => '0', 'color' => 'teal'],
                    ['label' => 'Exclude Dummy Warehouse', 'value' => '2', 'color' => 'teal']
                ]
            );
        }

        unset($col1['divsion']['labeldata']);
        unset($col1['class']['labeldata']);
        unset($col1['model']['labeldata']);
        unset($col1['labeldata']['divsion']);
        unset($col1['labeldata']['class']);
        unset($col1['labeldata']['model']);
        data_set($col1, 'divsion.name', 'stockgrp');
        data_set($col1, 'class.name', 'classic');
        data_set($col1, 'model.name', 'modelname');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 
        'default' as print,
        left(now(),10) as start,
        '' as client,
        '' as clientname,
        0 as itemid,
        '' as itemname,
        '' as barcode,
        0 as groupid,
        '' as stockgrp,
        0 as brandid,
        '' as brandname,
        0 as classid,
        '' as classic,
        '' as categoryname,
        '' as subcatname,
        0 as modelid,
        '' as modelname,
        '' as ditemname,
        '' as divsion,
        '' as brand,
        '' as model,
        '' as class,
        '' as category,
        '' as subcat,
        '' as uom,
        0 as partid,
        '' as part,
        '' as partname,
        '' as dwhname,
        '' as wh,
        '' as whname,
        '0' as layoutformat";

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
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->DEFAULT_QUERY_v2($config);
        return $this->coreFunctions->opentable($query);
    }

    public function main_query($config, $fields, $filter_wh)
    {
        $asof         = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $barcode      = $config['params']['dataparams']['barcode'];
        $classname    = $config['params']['dataparams']['classic'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $subcatname   = $config['params']['dataparams']['subcatname'];
        $groupname    = $config['params']['dataparams']['stockgrp'];
        $brandname        = $config['params']['dataparams']['brandname'];
        $modelname    = $config['params']['dataparams']['modelname'];
        $uom          = $config['params']['dataparams']['uom'];
        $companyid    = $config['params']['companyid'];

        $filteritem = "";
       

        if ($uom != "") {
            $filteritem .= " and stock.uom='" . $uom . "'";
        }
        if ($brandname != "") {
            $brandid = $config['params']['dataparams']['brandid'];
            $filteritem .= " and item.brand=" . $brandid;
        }
        if ($modelname != "") {
            $modelid = $config['params']['dataparams']['modelid'];
            $filteritem .= " and item.model=" . $modelid;
        }
        if ($classname != "") {
            $classid = $config['params']['dataparams']['classid'];
            $filteritem .= " and item.class=" . $classid;
        }
        if ($categoryname != "") {
            $category = $config['params']['dataparams']['category'];
            $filteritem .= " and item.category='" . $category . "'";
        }
        if ($subcatname != "") {
            $subcat = $config['params']['dataparams']['subcat'];
            $filteritem .= " and item.subcat='" . $subcat . "'";
        }
        if ($barcode != "") {
            $itemid = $config['params']['dataparams']['itemid'];
            $filteritem .= " and stock.itemid=" . $itemid;
        }
        if ($groupname != "") {
            $groupid = $config['params']['dataparams']['groupid'];
            $filteritem .= " and item.groupid=" . $groupid;
        }

        $addfields = '';
        if ($companyid == 47) { //kitchenstar
            $addfields = ", item.color, item.sizeid";
        }

        $query = "select item.barcode,item.itemname, item.uom $addfields $fields
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid
        left join part_masterfile as partgrp on partgrp.part_id = item.part
        left join iteminfo as iinfo on iinfo.itemid = item.itemid
        where head.dateid<='$asof' 
        and ifnull(item.barcode,'')<>'' and item.isofficesupplies=0 $filter_wh $filteritem 
        group by item.barcode,item.itemname, item.uom $addfields

        UNION ALL

        select item.barcode,item.itemname, item.uom $addfields $fields
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid
        left join iteminfo as iinfo on iinfo.itemid = item.itemid   
        where  head.dateid<='$asof' 
        and ifnull(item.barcode,'')<>'' and item.isofficesupplies=0 $filter_wh $filteritem 
        group by item.barcode,item.itemname, item.uom $addfields";

        return $query;
    }

    public function main_query_v2($config)
    {
        $asof         = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $barcode      = $config['params']['dataparams']['barcode'];
        $classname    = $config['params']['dataparams']['classic'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $subcatname   = $config['params']['dataparams']['subcatname'];
        $groupname    = $config['params']['dataparams']['stockgrp'];
        $brandname        = $config['params']['dataparams']['brandname'];
        $modelname    = $config['params']['dataparams']['modelname'];
        $uom          = $config['params']['dataparams']['uom'];

        $filteritem = "";
        if ($uom != "") {
            $filteritem .= " and stock.uom='" . $uom . "'";
        }
        if ($brandname != "") {
            $brandid = $config['params']['dataparams']['brandid'];
            $filteritem .= " and item.brand=" . $brandid;
        }
        if ($modelname != "") {
            $modelid = $config['params']['dataparams']['modelid'];
            $filteritem .= " and item.model=" . $modelid;
        }
        if ($classname != "") {
            $classid = $config['params']['dataparams']['classid'];
            $filteritem .= " and item.class=" . $classid;
        }
        if ($categoryname != "") {
            $category = $config['params']['dataparams']['category'];
            $filteritem .= " and item.category='" . $category . "'";
        }
        if ($subcatname != "") {
            $subcat = $config['params']['dataparams']['subcat'];
            $filteritem .= " and item.subcat='" . $subcat . "'";
        }
        if ($barcode != "") {
            $itemid = $config['params']['dataparams']['itemid'];
            $filteritem .= " and stock.itemid=" . $itemid;
        }
        if ($groupname != "") {
            $groupid = $config['params']['dataparams']['groupid'];
            $filteritem .= " and item.groupid=" . $groupid;
        }

        $query = "select stock.itemid, stock.whid, sum(stock.qty-iss) as balance 
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where head.dateid<='$asof' and stock.trno is not null $filteritem 
        group by stock.itemid, stock.whid
        
        UNION ALL

        select stock.itemid, stock.whid, sum(stock.qty-iss) as balance 
        from glhead as head 
        left join glstock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where head.dateid<='$asof' and stock.trno is not null $filteritem 
        group by stock.itemid, stock.whid";

        return $query;
    }

    public function get_all_warehouse($config)
    {
        $companyid = $config['params']['companyid'];
        $layoutformat = $config['params']['dataparams']['layoutformat'];
        $limit = "";
        $filter ="";
        $whid         = $config['params']['dataparams']['whid'];
        $wh         = $config['params']['dataparams']['wh'];
        $whname     = $config['params']['dataparams']['whname'];

        if ($whname != "") {
            $filter .= " and clientid='" . $whid . "'";
        }

        switch ($companyid) {
            case 47: //kitchenstar
                if ($layoutformat == 2) { //except dummy warehouse PASIG00004
                    $qry2 = "select client,clientname,clientid from client where iswarehouse=1 and clientid <> 1014 ". $filter ." order by client $limit";
                } else { //layoutformat 0,1 
                    $qry2 = "select client,clientname,clientid from client where iswarehouse=1 ". $filter ." order by client $limit";
                }
                break;
            default: //other company
                $qry2 = "select client,clientname,clientid from client where iswarehouse=1 order by client $limit";
                break;
        }

        return $this->coreFunctions->opentable($qry2);
    }

    public function count_all_warehouse($config, $data)
    {
        $count = 0;
        foreach ($data as $i => $value) {
            $count++;
        }
        return $count;
    }

    public function DEFAULT_QUERY($config)
    {
        $companyid = $config['params']['companyid'];
        $warehouse_count = 0;
        $inner_count = '';
        $outer_count = '';

        $all_warehouse = $this->get_all_warehouse($config);
        $warehouse_count = count($all_warehouse); // $this->count_all_warehouse($config, $all_warehouse);
        $counter = 0;
        $compiled_qry = '';
        $filter_wh = '';

        $layoutformat = $config['params']['dataparams']['layoutformat'];
        $filterbalance = '';

        $addfields = '';
        if ($companyid == 47) { //kitchenstar
            $addfields = ", ib.color, ib.sizeid";
        }

        // Main loop for all warehouse
        for ($i = 0; $i < $warehouse_count; $i++) {

            $inner_count = '';
            $filter_wh = '';
            // loop to compile fields
            foreach ($all_warehouse as $array_index => $array) {
                if ($array_index == $counter) {
                    $inner_count .= ", 
                    sum(stock.qty-stock.iss) as '" . $array->client . "'";
                    $filter_wh .= " and stock.whid= '" . $array->clientid . "'";
                    $outer_count .= ", 
                    sum(ib." . $array->client . ") as '" . $array->client . "'";
                } else {
                    $inner_count .= ", 
                    0 as '" . $array->client . "'";
                }
            }
            $counter++;

            // after fields are compiled per warehouse, compile main qry based on that warehouse
            if ($compiled_qry == '') {
                $compiled_qry .= $this->main_query($config, $inner_count, $filter_wh);
            } else {
                $compiled_qry .= "
                
                union all
                
                " . $this->main_query($config, $inner_count, $filter_wh);
            }
        }

        if ($layoutformat == 1) {
            foreach ($all_warehouse as $array_index => $array) {
                if ($filterbalance == '') {
                    $filterbalance .= "where ib." . $array->client . "  !=0 ";
                } else {
                    $filterbalance .= " or ib." . $array->client . "  !=0 ";
                }
            }
        }

        // Completed Query
        $query = "
            select ib.barcode,ib.itemname,ib.uom $addfields $outer_count from ( 

            $compiled_qry

            ) as ib
            $filterbalance
            group by ib.barcode,ib.itemname,ib.uom $addfields            
            order by ib.barcode
            ";
        return $query;
    }

    public function DEFAULT_QUERY_v2($config)
    {
        $companyid = $config['params']['companyid'];
        $inner_count = '';

        $all_warehouse = $this->get_all_warehouse($config);
        $layoutformat = $config['params']['dataparams']['layoutformat'];

        $filterbalance = '';

        $addfields = '';
        if ($companyid == 47) { //kitchenstar
            $addfields = ", item.color, item.sizeid";
        }

        // Main loop for all warehouse
        foreach ($all_warehouse as $array_index => $array) {
            $inner_count .= ", sum(if(whid=$array->clientid,balance,0)) as '" . $array->client . "'";
        }

        if ($layoutformat == 1) {
            $filterbalance = ' having sum(balance)<>0';
        }

        $query = "
            select item.barcode,item.itemname,item.uom $addfields $inner_count from (
                
                " . $this->main_query_v2($config) . "
                
            ) as ib left join item on item.itemid=ib.itemid
            group by item.barcode,item.itemname,item.uom $addfields         
            $filterbalance
            order by item.barcode
            ";

        return $query;
    }

    private function default_displayHeader($config)
    {
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $asof       = $config['params']['dataparams']['start'];
        $barcode    = $config['params']['dataparams']['barcode'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $subcatname =  $config['params']['dataparams']['subcat'];
        $groupname  = $config['params']['dataparams']['stockgrp'];
        $brandname  = $config['params']['dataparams']['brandname'];
        $modelname  = $config['params']['dataparams']['modelname'];
        $layoutformat = $config['params']['dataparams']['layoutformat'];

        switch ($layoutformat) {
            case 1:
                $layout = 'With Balance';
                break;
            case 2:
                $layout = 'Exclude Dummy Warehouse';
                break;
            default:
                $layout = 'ALL';
                break;
        }

        if ($brandname == '') {
            $brandname = "ALL";
        }
        if ($modelname == '') {
            $modelname = "ALL";
        }

        $str = '';
        $all_warehouse = $this->get_all_warehouse($config);
        $warehouse_count = $this->count_all_warehouse($config, $all_warehouse);
        $layoutsize = 550 + ($warehouse_count * 100);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('INVENTORY BALANCE PER WAREHOUSE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Balance as of : ' . $asof, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        if ($barcode == '') {
            $str .= $this->reporter->col('Items : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        } else {
            $str .= $this->reporter->col('Items : ' . $barcode, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        }

        if ($groupname == '') {
            $str .= $this->reporter->col('Group : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        } else {
            $str .= $this->reporter->col('Group : ' . $groupname, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        }


        $str .= $this->reporter->col('Brand : ' . $brandname, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

        if ($categoryname == '') {
            $str .= $this->reporter->col('Category : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        } else {
            $str .= $this->reporter->col('Category : ' . $categoryname, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        } else {
            $subcatname = $config['params']['dataparams']['subcatname'];
            $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        }

        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Layout : ' . $layout, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $str .= $this->reporter->printline();

        $all_warehouse = $this->get_all_warehouse($config);
        $warehouse_count = $this->count_all_warehouse($config, $all_warehouse);
        $layoutsize = 550 + ($warehouse_count * 100);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BARCODE', '75', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEMNAME', '300', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');

        foreach ($all_warehouse as $array_index => $array) {
            $str .= $this->reporter->col($array->clientname, '100', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->col('TOTAL BALANCE', '100', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $companyid = $config['params']['companyid'];
        $font_size = '10';
        $fontsize11 = 11;

        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $all_warehouse = $this->get_all_warehouse($config);
        $warehouse_count = $this->count_all_warehouse($config, $all_warehouse);
        $layoutsize = 550 + ($warehouse_count * 100); // fixed width based on all other columns and 100 per variable WH

        $wh_string = [];
        // push all warehouse names into array for checking later
        foreach ($all_warehouse as $array_index => $array) {
            array_push($wh_string, $array->client);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $totalbalqty = 0;
        $grandtotal = 0;

        foreach ($result as $key => $data) {
            $totalbalqty = 0;

            switch ($companyid) {
                case 47: //kitchenstar
                    $barcode =  $data->barcode;
                    $itemname = $data->itemname . ' ' . $data->color . ' ' . $data->sizeid;
                    $uom = $data->uom;

                    $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
                    break;
                default:
                    $barcode =  $data->barcode;
                    $itemname = $data->itemname;
                    $uom = $data->uom;

                    $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
                    break;
            }

            $arr_barcode = $this->reporter->fixcolumn([$barcode], '30', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_uom]);

            for ($r = 0; $r < $maxrow; $r++) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col(' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '75', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '300', null, false, '1px solid ', '', 'L', $font, $font_size, '',  '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '75', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
                if ($r == 0) {
                    foreach ($data as $i => $value) {
                        // $i is the field column name in qry
                        // $value is balance
                        if (array_search($i, $wh_string) !== false) {
                            if ($value == 0) {
                                $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                            } else {
                                $str .= $this->reporter->col(number_format($value, 2), '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                            }

                            $totalbalqty += $value;
                        }
                    }
                    if ($totalbalqty == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($totalbalqty, 2), '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                    }
                } else {
                    foreach ($data as $i => $value) {

                        // $i is the field column name in qry
                        // $value is balance
                        if (array_search($i, $wh_string) !== false) {
                            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                        }
                    }
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                }
            }

            $grandtotal += $totalbalqty;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OVER ALL STOCKS: ', '75', null, false, '1px solid ', 'T', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '1px solid ', 'T', 'L', $font, $font_size, '',  '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'L', $font, $font_size, '', '', '');
        // loop all warehouse

        foreach ($all_warehouse as $array_index => $array) {
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, $font_size, '', '', '');
        }

        $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, '1px solid ', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class