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

class inventory_balance_per_engine
{
    public $modulename = 'Inventory Balance Per Engine';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1250'];

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
        $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'subcatname', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'start.label', 'Balance as of');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
        data_set($col1, 'luom.action', 'replookupuom');

        unset($col1['divsion']['labeldata']);
        unset($col1['model']['labeldata']);
        unset($col1['class']['labeldata']);
        unset($col1['labeldata']['divsion']);
        unset($col1['labeldata']['model']);
        unset($col1['labeldata']['class']);
        data_set($col1, 'divsion.name', 'stockgrp');
        data_set($col1, 'model.name', 'modelname');
        data_set($col1, 'class.name', 'classic');

        $fields = ['radiorepitemstock'];
        $col2 = $this->fieldClass->create($fields);
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
            '' as wh,
            '' as whname,
            '(0,1)' as itemtype,
            '(0,1)' as itemstock,
            'none' as amountformat,
            '' as ditemname,
            '' as divsion,
            '' as brand,
            '' as model,
            '' as class,
            '' as category,
            '' as subcat,
            '' as dwhname,
            '' as uom,
            0 as partid,
            '' as part,
            '' as partname,
            0 as whid";
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
        ini_set('max_execution_time', -1);
        return $this->reportDefaultLayout($config);
    }

    public function reportDefault($config)
    {
        $query = $this->DEFAULT_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }

    public function DEFAULT_QUERY($config)
    {
        $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $barcode    = $config['params']['dataparams']['barcode'];
        $classname    = $config['params']['dataparams']['classic'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $groupname    = $config['params']['dataparams']['stockgrp'];
        $brandname    = $config['params']['dataparams']['brandname'];
        $modelname    = $config['params']['dataparams']['modelname'];
        $wh         = $config['params']['dataparams']['wh'];
        $itemstock  = $config['params']['dataparams']['itemstock'];
        $itemtype   = $config['params']['dataparams']['itemtype'];
        $uom = $config['params']['dataparams']['uom'];

        $order = " order by category,itemname";
        $filter = " and item.isimport in $itemtype";
        $filteritem = "";

        if ($brandname != "") {
            $brandid = $config['params']['dataparams']['brandid'];
            $filteritem .= " and ib.brand=" . $brandid;
        }
        if ($modelname != "") {
            $modelid = $config['params']['dataparams']['modelid'];
            $filteritem .= " and ib.model=" . $modelid;
        }
        if ($classname != "") {
            $classid = $config['params']['dataparams']['classid'];
            $filteritem .= " and ib.class=" . $classid;
        }
        if ($categoryname != "") {
            $category = $config['params']['dataparams']['category'];
            $filteritem .= " and ib.category='" . $category . "'";
        }
        if ($subcatname != "") {
            $subcat = $config['params']['dataparams']['subcat'];
            $filteritem .= " and ib.subcatline='" . $subcat . "'";
        }
        if ($barcode != "") {
            $itemid = $config['params']['dataparams']['itemid'];
            $filteritem .= " and ib.itemid=" . $itemid;
        }
        if ($groupname != "") {
            $groupid = $config['params']['dataparams']['groupid'];
            $filteritem .= " and ib.groupid=" . $groupid;
        }
        if ($wh != "") {
            $whid = $config['params']['dataparams']['whid'];
            $filter .= " and stock.whid=" . $whid;
        }
        if ($uom != '') {
            $filter .= " and uom.uom='" . $uom . "'";
        }

        $query = "
        select disc,category,itemid,barcode,itemname,
            part,uom,sum(qty-iss) as balance,cost,amt,catid,subcatline,class,groupid,model,brand
            from (
            select item.disc, cat.name as category,item.itemid,item.barcode, item.itemname,partgrp.part_name as part,item.uom,
                stock.qty,stock.iss,0 as cost, item.amt,item.category as catid,subcat.line as subcatline,item.class,item.groupid,item.model,item.brand
                from item
                left join glstock as stock on stock.itemid = item.itemid
                left join glhead as head on head.trno = stock.trno
                left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
                left join model_masterfile as modelgrp on modelgrp.model_id = item.model
                left join part_masterfile as partgrp on partgrp.part_id = item.part
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                left join iteminfo as iinfo on iinfo.itemid = item.itemid
                left join uom on uom.itemid = item.itemid  
                left join frontend_ebrands as fbrand on fbrand.brandid = item.brand
            where head.doc in ('sj','mj','rr') and  head.dateid <= '$asof' and ifnull(item.barcode,'' )<> '' $filter  and item.isimport in (0,1)  and item.isofficesupplies = 0

            UNION ALL

            select item.disc, cat.name as category,item.itemid,item.barcode, item.itemname,partgrp.part_name as part,uom.uom,
                stock.qty,stock.iss,0 as cost, item.amt,item.category as catid,subcat.line as subcatline,item.class,item.groupid,item.model,item.brand
                from item
                left join lastock as stock on stock.itemid = item.itemid
                left join lahead as head on head.trno = stock.trno
                left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
                left join model_masterfile as modelgrp on modelgrp.model_id = item.model
                left join part_masterfile as partgrp on partgrp.part_id = item.part
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                left join iteminfo as iinfo on iinfo.itemid = item.itemid
                left join uom on uom.itemid = item.itemid 
                left join frontend_ebrands as fbrand on fbrand.brandid = item.brand
            where head.doc in ('sj','mj','rr') and head.dateid <= '$asof' and ifnull(item.barcode,'' )<> ''   $filter  and item.isimport in (0,1)  and item.isofficesupplies = 0
            ) as ib where ib.catid = '6' " . $filteritem . "  group by disc,category,itemid,barcode,itemname,subcatline,class,groupid,model,brand,
        part,uom,cost,amt,catid having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;

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
        $classname  = $config['params']['dataparams']['classic'];
        $subcatname =  $config['params']['dataparams']['subcat'];
        $groupname  = $config['params']['dataparams']['stockgrp'];
        $brandname  = $config['params']['dataparams']['brandname'];
        $modelname  = $config['params']['dataparams']['modelname'];
        $whname     = $config['params']['dataparams']['whname'];
        $itemstock  = $config['params']['dataparams']['itemstock'];
        $itemtype   = $config['params']['dataparams']['itemtype'];

        if ($brandname == '') {
            $brandname = "ALL";
        }

        if ($modelname == '') {
            $modelname = "ALL";
        }

        if ($whname == '') {
            $whname = "ALL";
        }

        $str = '';
        $layoutsize = '1500';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('INVENTORY BALANCE PER ENGINE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        if ($barcode == '') {
            $str .= $this->reporter->col('Items : ALL', '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Items : ' . $barcode, '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }


        if ($groupname == '') {
            $str .= $this->reporter->col('Group : ALL', '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Group : ' . $groupname, '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }


        $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        if ($classname == '') {
            $str .= $this->reporter->col('Class : ALL', '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Class : ' . $classname, '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        switch ($itemtype) {
            case '(1)':
                $itemtype = 'Import';
                break;
            case '(0)':
                $itemtype = 'Local';
                break;
            case '(0,1)':
                $itemtype = 'Both';
                break;
        }
        $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        switch ($itemstock) {
            case '(1)':
                $itemstock = 'With Balance';
                break;
            case '(0)':
                $itemstock = 'Without Balance';
                break;
            case '(0,1)':
                $itemstock = 'None';
                break;
        }

        $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL', '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }

        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '120', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('COLOR', '152', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ENGINE#', '152', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('CHASISS#', '152', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('PNP#', '152', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('CSR#', '152', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '70', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $fontsize11 = 11;

        $count = 51;
        $page = 50;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1250';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $totalbalqty = 0;
        $part = "";
        $scatgrp = "";
        $totalext = 0;
        $grandtotal = 0;

        $multiheader = true;
        foreach ($result as $key => $data) {

            $balance = number_format($data->balance, 2);
            if ($balance == 0) {
                $balance = '-';
            }
            $isamt = number_format($data->amt, 2);
            if ($isamt == 0) {
                $isamt = '-';
            }

            $discounted = $this->othersClass->Discount($data->amt, $data->disc);
            //aa



            if ($data->category != 0 || $data->category != null) {
                if (strtoupper($scatgrp) == strtoupper($data->category)) {
                    $scatgrp = "";
                } else {
                    $scatgrp = strtoupper($data->category);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col($scatgrp, '120', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');

                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

                    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {
                $scatgrp = "";
            }
            if ($data->part != 0 || $data->part != null) {
                if (strtoupper($part) == strtoupper($data->part)) {
                    $part = "";
                } else {
                    $part = strtoupper($data->part);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col($part, '270', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');

                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

                    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {
                $part = "";
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col($data->barcode, '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

            $totalext = $data->balance * $discounted;

            if ($totalext == 0) {
                $totalext = '-';
            } else {
                $totalext = number_format($totalext, 2);
            }

            $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $query = "  select 
            concat(ifnull(sin.serial ,'')) as serial,
            concat(ifnull(sin.color ,'')) as color,
            concat(ifnull(sin.chassis ,'')) as chassis,
            concat(ifnull(sin.pnp ,'')) as pnp,
            concat(ifnull(sin.csr ,'')) as csr
            from glstock as stock
            left join serialin as sin on sin.trno = stock.trno and sin.line = stock.line
            where stock.itemid = $data->itemid and sin.outline =0
            union all
              select 
            concat(ifnull(sin.serial ,'')) as serial,
            concat(ifnull(sin.color ,'')) as color,
            concat(ifnull(sin.chassis ,'')) as chassis,
            concat(ifnull(sin.pnp ,'')) as pnp,
            concat(ifnull(sin.csr ,'')) as csr
            from lastock as stock
            left join serialin as sin on sin.trno = stock.trno and sin.line = stock.line
            where stock.itemid = $data->itemid and sin.outline =0";

            $serial = $this->coreFunctions->opentable($query);
            foreach ($serial as $key => $value) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

                $str .= $this->reporter->col($serial[$key]->color, '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($serial[$key]->serial, '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($serial[$key]->chassis, '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($serial[$key]->pnp, '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($serial[$key]->csr, '152', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

                $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $scatgrp = strtoupper($data->category);
            $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
            $part = $data->part;
            $grandtotal = $grandtotal + ($data->balance * $discounted);
            $totalbalqty = $totalbalqty + $data->balance;

            if ($multiheader) {
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$allowfirstpage) {
                        $str .= $this->default_displayHeader($config);
                    }
                    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
                    $page = $page + $count;
                }
            }
        }

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= '<br/>';
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '25', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('OVERALL STOCKS :', '650', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '40', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();
        return $str;
    }

    private function default_displayHeader_LATEST_COST($config)
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
        $whname     = $config['params']['dataparams']['whname'];
        $itemstock  = $config['params']['dataparams']['itemstock'];
        $itemtype   = $config['params']['dataparams']['itemtype'];

        if ($brandname == '') {
            $brandname = "ALL";
        }

        if ($modelname == '') {
            $modelname = "ALL";
        }

        if ($whname == '') {
            $whname = "ALL";
        }

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('INVENTORY BALANCE PER ENGINE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        if ($barcode == '') {
            $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }


        if ($groupname == '') {
            $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }


        $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');


        if ($categoryname == '') {
            $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }


        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '', '');

        $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

        switch ($itemtype) {
            case '(1)':
                $itemtype = 'Import';
                break;
            case '(0)':
                $itemtype = 'Local';
                break;
            case '(0,1)':
                $itemtype = 'Both';
                break;
        }
        $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');


        switch ($itemstock) {
            case '(1)':
                $itemstock = 'With Balance';
                break;
            case '(0)':
                $itemstock = 'Without Balance';
                break;
            case '(0,1)':
                $itemstock = 'None';
                break;
        }

        $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }

        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        return $str;
    }

    private function default_latest_cost_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '120', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '460', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '40', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('COST', '80', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '');

        return $str;
    }

    public function reportDefaultLayout_LATEST_COST($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $fontsize11 = 11;
        $companyid = $config['params']['companyid'];
        $wh = $config['params']['dataparams']['wh'];

        if ($wh == '') {
            $wh = 'ALL';
        }

        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader_LATEST_COST($config);

        $str .= $this->default_latest_cost_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $totalbalqty = 0;
        $part = "";
        $scatgrp = "";
        $totalext = 0;
        $grandtotal = 0;

        $multiheader = true;

        foreach ($result as $key => $data) {

            $balance = number_format($data->balance, 2);
            if ($balance == 0) {
                $balance = '-';
            }

            $cost = $this->coreFunctions->datareader("select cost as value from rrstatus where itemid=" . $data->itemid . " order by dateid desc limit 1", [], '', true);
            if ($cost == 0) {
                $cost = '-';
            }
            //not majesty, unihome & goodfound
            if ($companyid != 14 && $companyid != 17 && $companyid != 24) {
                if ($data->part != 0 || $data->part != null) {
                    if (strtoupper($part) == strtoupper($data->part)) {
                        $part = "";
                    } else {
                        $part = strtoupper($data->part);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->addline();
                        $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
                        $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();
                    }
                } else {
                    $part = "";
                }

                if ($data->category != 0 || $data->category != null) {
                    if (strtoupper($scatgrp) == strtoupper($data->category)) {
                        $scatgrp = "";
                    } else {
                        $scatgrp = strtoupper($data->category);
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->addline();
                        $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                        $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }
                } else {
                    $scatgrp = "";
                }
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->barcode, '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemname, '460', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

            $totalext = $data->balance * $data->cost;
            if ($totalext == 0) {
                $totalext = '-';
            } else {
                $totalext = number_format($totalext, 2);
            }

            $str .= $this->reporter->col($data->uom, '40', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($cost, '80', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($totalext, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            $scatgrp = strtoupper($data->category);
            $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
            $part = $data->part;
            $totalbalqty = $totalbalqty + $data->balance;
            $grandtotal = $grandtotal + ($data->balance * $data->cost);

            if ($multiheader) {

                if ($this->reporter->linecounter >= $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$allowfirstpage) {
                        $str .= $this->default_displayHeader_LATEST_COST($config);
                    }
                    $str .= $this->default_latest_cost_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
                    $page = $page + $count;
                }
            }
        }

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= '<br/>';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }

    private function default_displayHeader_NONE($config)
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
        $whname     = $config['params']['dataparams']['whname'];
        $itemstock  = $config['params']['dataparams']['itemstock'];
        $itemtype   = $config['params']['dataparams']['itemtype'];

        if ($brandname == '') {
            $brandname = "ALL";
        }

        if ($modelname == '') {
            $modelname = "ALL";
        }

        if ($whname == '') {
            $whname = "ALL";
        }


        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('INVENTORY BALANCE PER ENGINE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        if ($barcode == '') {
            $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }

        if ($groupname == '') {
            $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }


        $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');


        if ($categoryname == '') {
            $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }


        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '', '');

        $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

        switch ($itemtype) {
            case '(1)':
                $itemtype = 'Import';
                break;
            case '(0)':
                $itemtype = 'Local';
                break;
            case '(0,1)':
                $itemtype = 'Both';
                break;
        }
        $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');


        switch ($itemstock) {
            case '(1)':
                $itemstock = 'With Balance';
                break;
            case '(0)':
                $itemstock = 'Without Balance';
                break;
            case '(0,1)':
                $itemstock = 'None';
                break;
        }
        $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');



        $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');


        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $subcatname = $config['params']['dataparams']['subcatname'];
            $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        return $str;
    }

    private function default_none_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $itemstock  = $config['params']['dataparams']['itemstock'];

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '120', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '420', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '60', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '');
        if ($itemstock != '(0,1)') {
            $str .= $this->reporter->col('SRP', '100', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '');
        }
        $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '');
        return $str;
    }

    public function reportDefaultLayout_NONE($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $fontsize11 = 11;

        $itemstock  = $config['params']['dataparams']['itemstock'];

        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader_NONE($config);
        $str .= $this->default_none_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $totalbalqty = 0;
        $part = "";
        $scatgrp = "";
        $igrp = "";
        $totalext = 0;
        $grandtotal = 0;

        $multiheader = true;

        foreach ($result as $key => $data) {

            $balance = number_format($data->balance, 2);
            if ($balance == 0) {
                $balance = '-';
            }
            if (isset($data->amt)) {
                $isamt = number_format($data->amt, 2);
                if ($isamt == 0) {
                    $isamt = '-';
                }
            } else {
                $isamt = '-';
                $data->amt = 0;
            }

            if ($data->part != 0 || $data->part != null) {
                if (strtoupper($part) == strtoupper($data->part)) {
                    $part = "";
                } else {
                    $part = strtoupper($data->part);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                }
            } else {
                $part = "";
            }

            if ($data->category != 0 || $data->category != null) {
                if (strtoupper($scatgrp) == strtoupper($data->category)) {
                    $scatgrp = "";
                } else {
                    $scatgrp = strtoupper($data->category);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {
                $scatgrp = "";
            }


            $totalext = $data->balance * $data->amt;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            //aa

            $str .= $this->reporter->col($data->partno == '' ? '-' : $data->partno, '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemname, '420', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

            $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            if ($itemstock != '(0,1)') {
                $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            }
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $scatgrp = strtoupper($data->category);
            $part = strtoupper($data->part);

            $grandtotal = $grandtotal + $totalext;
            $totalbalqty = $totalbalqty + $data->balance;

            if ($multiheader) {
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->reporter->begintable($layoutsize);
                    $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$allowfirstpage) {
                        $str .= $this->default_displayHeader_NONE($config);
                    }
                    $str .= $this->default_none_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
                    $page = $page + $count;
                }
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= '<br/>';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');



        $str .= $this->reporter->col('OVERALL STOCKS :', '375', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');

        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');



        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function report_default_avecost($config)
    {
        $result = $this->reportDefaultAveCost($config);

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $companyid = $config['params']['companyid'];
        $wh = $config['params']['dataparams']['wh'];

        if ($wh == '') {
            $wh = 'ALL';
        }

        $count = 55;
        $page = 55;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        //aaa
        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->kinggeorge_header($config);

        $totalbalqty = 0;
        $part = "";
        $scatgrp = "";
        $totalext = 0;
        $grandtotal = 0;

        $multiheader = true;
        switch ($companyid) {
            case 14: //majesty
                $multiheader = false;
                break;
        }
        $str .= $this->reporter->begintable($layoutsize);
        foreach ($result as $key => $data) {

            $balance = number_format($data->balance, 2);
            if ($balance == 0) {
                $balance = '-';
            }

            $cost = number_format($data->cost, 2);
            if ($cost == 0) {
                $cost = '-';
            }

            if ($data->part != 0 || $data->part != null) {
                if (strtoupper($part) == strtoupper($data->part)) {
                    $part = "";
                } else {
                    $part = strtoupper($data->part);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                }
            } else {
                $part = "";
            }

            if ($data->category != 0 || $data->category != null) {
                if (strtoupper($scatgrp) == strtoupper($data->category)) {
                    $scatgrp = "";
                } else {
                    $scatgrp = strtoupper($data->category);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {
                $scatgrp = "";
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->barcode, '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemname, '520', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $totalext = $data->balance * $data->cost;

            if ($totalext == 0) {
                $totalext = '-';
            } else {
                $totalext = number_format($totalext, 2);
            }

            $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($cost, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($totalext, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->endtable();
            $scatgrp = strtoupper($data->category);
            $part = $data->part;
            $totalbalqty = $totalbalqty + $data->balance;
            $grandtotal = $grandtotal + ($data->balance * $data->cost);
            //oks
            if ($multiheader) {
                if ($this->reporter->linecounter >= $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                    if ($allowfirstpage) {
                        $str .= $this->kinggeorge_header($config);
                    }

                    $page = $page + $count;
                }
            }
        }

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');

        $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');


        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }

    private function getLatestCost($itemid)
    {
        $qry = "select ifnull(cost,0) as value from rrstatus where itemid= ? order by dateid desc limit 1";
        return $this->coreFunctions->datareader($qry, [$itemid]);
    }

    private function serialquery($trno, $line)
    {
        $query = "select ifnull(concat(rr.serial,', '),'') as serialno
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
        where head.trno='$trno' and stock.line = '$line' and rr.outline = 0 
        union all
        select ifnull(concat(rr.serial,', '),'') as serialno
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
        where head.trno='$trno' and stock.line = '$line' and rr.outline = 0 
        order by serialno";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    private function serialquery2($itemid)
    {
        $query = "select ifnull(concat(rr.serial,', '),'') as serialno
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
        where stock.itemid='$itemid' and rr.outline = 0 
        union all
        select ifnull(concat(rr.serial,', '),'') as serialno
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
        where stock.itemid='$itemid' and rr.outline = 0
        order by serialno";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    private function kinggeorge_header($config)
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
        $whname     = $config['params']['dataparams']['whname'];
        $itemstock  = $config['params']['dataparams']['itemstock'];
        $itemtype   = $config['params']['dataparams']['itemtype'];

        if ($brandname == '') {
            $brandname = "ALL";
        }

        if ($modelname == '') {
            $modelname = "ALL";
        }

        if ($whname == '') {
            $whname = "ALL";
        }

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('INVENTORY BALANCE PER ENGINE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        if ($barcode == '') {
            $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }

        if ($groupname == '') {
            $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }
        $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        if ($categoryname == '') {
            $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

        switch ($itemtype) {
            case '(1)':
                $itemtype = 'Import';
                break;
            case '(0)':
                $itemtype = 'Local';
                break;
            case '(0,1)':
                $itemtype = 'Both';
                break;
        }
        $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');


        switch ($itemstock) {
            case '(1)':
                $itemstock = 'With Balance';
                break;
            case '(0)':
                $itemstock = 'Without Balance';
                break;
            case '(0,1)':
                $itemstock = 'None';
                break;
        }
        $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        } else {
            $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
        }

        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');


        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '120', null, false, '1px solid ', 'B', 'L', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '520', null, false, '1px solid ', 'B', 'L', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '60', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('UNIT COST', '100', null, false, '1px solid ', 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('TOTAL COST', '100', null, false, '1px solid ', 'B', 'R', $font, $font_size, 'B', '', '', '');

        $str .= $this->reporter->endrow();
        return $str;
    }
}//end class