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

class item_list_with_cost
{
    public $modulename = 'Item List With Cost';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1000px;max-width:1000px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
        switch ($companyid) {
            case 39: //cbbsi
                $fields = ['radioprint', 'itemname', 'divsion', 'brandname', 'brandid', 'class', 'categoryname', 'subcatname'];
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'divsion.label', 'Group');
                data_set($col1, 'itemname.label', 'Item Code');
                data_set($col1, 'itemname.readonly', false);
                data_set($col1, 'itemname.name', 'barcode');
                data_set($col1, 'brandid.name', 'brandid');
                data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

                unset($col1['class']['labeldata']);
                unset($col1['divsion']['labeldata']);
                unset($col1['labeldata']['class']);
                unset($col1['labeldata']['divsion']);
                data_set($col1, 'class.name', 'classic');
                data_set($col1, 'divsion.name', 'stockgrp');

                $fields = ['print'];
                $col2 = $this->fieldClass->create($fields);
                break;
        }
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
            'default' as print,
            '' as itemname,
            '' as barcode,
            0 as groupid,
            '' as stockgrp,
            0 as brandid,
            '' as categoryid,
            '' as categoryname,
            '' as brandname,
            '' as brand,
            0 as classid,
            '' as classic,
            '' as ditemname,
            '' as divsion,
            '' as category,
            '' as subcatname,
            '' as subcat,
            '' as class,
            '0' as sortby,
            'amt' as itemsort";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $result = $this->reportDefault($config);
        $str = $this->reportplotting($config, $result);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config, $result)
    {
        $result = $this->reportDefaultLayout($config, $result);
        return $result;
    }

    public function reportDefault($config)
    {
        switch ($config['params']['companyid']) {
            case 39: // cbbsi
                $query = $this->DEFAULT_QUERY($config);
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function DEFAULT_QUERY($config)
    {
        $barcode    = $config['params']['dataparams']['barcode'];
        $groupname  = $config['params']['dataparams']['stockgrp'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $brandname  = $config['params']['dataparams']['brandname'];
        $classname  = $config['params']['dataparams']['classic'];

        $filter = "";
        if ($barcode != "") {
            $filter = $filter . " and left(item.barcode," . strlen($barcode) . ") like '$barcode%'";
        }
        if ($groupname != "") {
            $groupid = $config['params']['dataparams']['groupid'];
            $filter = $filter . " and item.groupid=" . $groupid;
        }
        if ($brandname != "") {
            $brandid = $config['params']['dataparams']['brandid'];
            $filter = $filter . " and item.brand=" . $brandid;
        }
        if ($classname != "") {
            $classid = $config['params']['dataparams']['classid'];
            $filter = $filter . " and item.class=" . $classid;
        }
        if ($categoryname != "") {
            $category = $config['params']['dataparams']['category'];
            $filter = $filter . " and item.category='$category'";
        }
        if ($subcatname != "") {
            $subcat = $config['params']['dataparams']['subcat'];
            $filter = $filter . " and item.subcat='$subcat'";
        }

        $order = " order by groupid,brand,itemname";
        $query = "select current_timestamp as print_date, item.barcode, item.itemname,
            ifnull(stockgrp.stockgrp_name,'') as groupid, frontend_ebrands.brand_desc as brand,
            item.class, amt as price, item.isinactive,ifnull(itclass.cl_name,'') as cl_name,
            ifnull(cat.name,'') as catname,ifnull(subcat.name,'')as subcatname,item.uom as unit ,item.amt9 as cost,item.amt as retail,item.amt2 as ws,item.famt as priceA,item.amt4 as priceB
            from item 
            left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
            left join item_class as itclass on item.class = itclass.cl_id
            left join frontend_ebrands on frontend_ebrands.brandid = item.brand
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
            where item.barcode <> '' $filter
            $order";

        $this->coreFunctions->LogConsole($query);
        return $query;
    }

    private function default_displayHeader($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $barcode    = $config['params']['dataparams']['barcode'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $groupname  = $config['params']['dataparams']['stockgrp'];
        $brandname  = $config['params']['dataparams']['brandname'];
        $classname  = $config['params']['dataparams']['classic'];

        if ($barcode == '') {
            $ritem = ' All';
        } else {
            $ritem = $barcode;
        }
        if ($groupname == '') {
            $rgroup = ' All';
        } else {
            $rgroup = $groupname;
        }
        if ($brandname == '') {
            $rbrand = ' All';
        } else {
            $rbrand = $brandname;
        }
        if ($classname == '') {
            $rclass = ' All';
        } else {
            $rclass = $classname;
        }
        if ($categoryname == '') {
            $catname = 'ALL';
        } else {
            $catname = $categoryname;
        }
        if ($subcatname == '') {
            $subcat = 'ALL';
        } else {
            $subcat = $subcatname;
        }

        $str = '';
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col(strtoupper($this->modulename), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Item :' . $ritem, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Group :' . $rgroup, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Brand :' . $rbrand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Class :' . $rclass, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Category : ' . $catname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
        $str .= $this->reporter->col('Sub-Category : ' . $subcat, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);

        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        return $str;
    }

    private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $font_size = '10';

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '250', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('COST', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('RETAIL', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('WS', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('PRICE A', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('PRICE B', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config, $result)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $fontsize11 = 11;
        $this->reporter->linecounter = 0;
        $companyid = $config['params']['companyid'];

        $count = 61;
        $page = 60;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $group = "";
        foreach ($result as $key => $data) {
            if (strtoupper($group) == strtoupper($data->groupid)) {
                $group = "";
            } else {
                $group = strtoupper($data->groupid);
            } //end if

            if ($companyid != 28) { //not xcomp
                if ($group != "") {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($group, '150', null, false, $border, '', 'L', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '250', null, false, $border, '', 'C', $font, $font_size, 'Bi', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                }
            }
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->unit, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->cost, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->retail, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->ws, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->priceA, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->priceB, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

            $group = strtoupper($data->groupid);
            $str .= $this->reporter->endrow();

            if ($companyid != 28) { //not xcomp
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();

                    $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$allowfirstpage) {
                        $str .= $this->default_displayHeader($config);
                    }
                    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
                    $str .= $this->reporter->addline();
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}
