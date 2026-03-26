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

class fixed_asset_card_list
{
    public $modulename = 'Fixed Asset Card List';
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
        $fields = ['radioprint', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'categoryname', 'subcatname'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'brandid.name', 'brandid');
        data_set($col1, 'ditemname.lookupclass', 'lookupitemfi');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

        unset($col1['divsion']['labeldata']);
        unset($col1['class']['labeldata']);
        unset($col1['labeldata']['divsion']);
        unset($col1['labeldata']['class']);
        data_set($col1, 'divsion.name', 'stockgrp');
        data_set($col1, 'class.name', 'classic');

        $fields = ['radioreportitemtype', 'radioreportitemstatus'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 'default' as print,
        0 as itemid,           
        '' as itemname,
        '' as barcode, 
        0 as groupid,
        '' as stockgrp, 
        0 as brandid, 
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
        '(0,1)' as itemtype, 
        '(0,1)' as itemstatus,                
        '' as class, '0' as sortby, 'amt' as itemsort,
        0 as clientid,               
        '' as client, 
        '' as clientname, 
        '' as dclientname";

        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
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
        $query = $this->DEFAULT_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    // QUERY
    public function DEFAULT_QUERY($config)
    {
        $barcode    = $config['params']['dataparams']['barcode'];
        $groupname  = $config['params']['dataparams']['stockgrp'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $brandname  = $config['params']['dataparams']['brandname'];
        $classname  = $config['params']['dataparams']['classic'];
        $itemtype   = $config['params']['dataparams']['itemtype'];
        $itemstatus = $config['params']['dataparams']['itemstatus'];

        $filter = "";
        if ($barcode != "") {
            $itemid = $config['params']['dataparams']['itemid'];
            $filter .= " and item.itemid=" . $itemid;
        }
        if ($groupname != "") {
            $groupid = $config['params']['dataparams']['groupid'];
            $filter .= " and item.groupid=" . $groupid;
        }
        if ($brandname != "") {
            $brandid = $config['params']['dataparams']['brandid'];
            $filter .= " and item.brand=" . $brandid;
        }
        if ($classname != "") {
            $classid = $config['params']['dataparams']['classid'];
            $filter .= " and item.class=" . $classid;
        }
        if ($categoryname != "") {
            $category = $config['params']['dataparams']['category'];
            $filter .= " and item.category='" . $category."'";
        }
        if ($subcatname != "") {
            $subcat = $config['params']['dataparams']['subcat'];
            $filter .= " and item.subcat='" . $subcat."'";
        }

        $order = " order by ifnull(parts.part_name,''),brand,itemname";

        $query = "select sizeid as size,current_timestamp as print_date, 0 as sort, barcode, itemname,
                        ifnull(stockgrp.stockgrp_name,'') as groupid, frontend_ebrands.brand_desc as brand,
                        ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,
                        body,class,supplier,cost, amt as price, item.isinactive,ifnull(itclass.cl_name,'') as cl_name,
                        category,subcat.name 
                from item 
                left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
                left join part_masterfile as parts on parts.part_id = item.part
                left join model_masterfile as mm on mm.model_id=item.model
                left join item_class as itclass on item.class = itclass.cl_id
                left join frontend_ebrands on frontend_ebrands.brandid = item.brand
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where item.isfa =1 and item.isinactive in $itemstatus and 
                      item.isimport in $itemtype $filter 
                $order";

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
        $itemtype   = $config['params']['dataparams']['itemtype'];
        $itemstatus = $config['params']['dataparams']['itemstatus'];

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

        if ($itemtype == '(0)') {
            $itemtype = 'Local';
        } elseif ($itemtype == '(1)') {
            $itemtype = 'Import';
        } else {
            $itemtype = 'Both';
        }

        if ($itemstatus == '(0)') {
            $itemstatus = 'Active';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'Inactive';
        } else {
            $itemstatus = 'Both';
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

        $str .= $this->reporter->col('ITEM LISTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Group :' . $rgroup, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Brand :' . $rbrand, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Class :' . $rclass, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
        if ($categoryname == '') {
            $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
        } else {
            $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
        }

        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
        } else {
            $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
        }

        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        return $str;
    }

    private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GROUP / CATEGORY', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PRICE', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
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

        $part = "";
        $brand = "";
        foreach ($result as $key => $data) {
            if (strtoupper($part) == strtoupper($data->part)) {
                $part = "";
            } else {
                $part = $data->part;
            } //end if

            if (strtoupper($brand) == strtoupper($data->brand)) {
                $brand = "";
            } else {
                $brand = strtoupper($data->brand);
            } //end if

            $price = number_format($data->price, 2);
            if ($price == 0) {
                $price = '-';
            } //end if

            if ($part != "") {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($part, '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '400', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
            }
            if ($brand != "") {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($brand, '150', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
                $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $font_size, 'Bi', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            if ($data->isinactive) {
                $isinactive = 'INACTIVE';
            } else {
                $isinactive = 'ACTIVE';
            } //end if

            $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->groupid, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($price, '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($isinactive, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

            $brand = strtoupper($data->brand);
            $part = $data->part;

            $str .= $this->reporter->endrow();

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

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class