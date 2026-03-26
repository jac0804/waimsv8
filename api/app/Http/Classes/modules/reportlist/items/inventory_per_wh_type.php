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

class inventory_per_wh_type
{
    public $modulename = 'Inventory Per Warehouse Type';
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
        $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'whtype', 'whtype2', 'whtype3'];

        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'start.label', 'Balance as of');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
        data_set($col1, 'luom.action', 'replookupuom');
        data_set($col1, 'whtype.label', 'WH Type 1');
        data_set($col1, 'whtype.lookupclass', 'lookupwhtype1');
        data_set($col1, 'whtype2.lookupclass', 'lookupwhtype2');
        data_set($col1, 'whtype3.lookupclass', 'lookupwhtype3');
        data_set($col1, 'whtype.readonly', true);

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
        // NAME NG INPUT YUNG NAKA ALIAS

        return $this->coreFunctions->opentable("select 
                'default' as print,
                left(now(),10) as start,
                '' as client,
                '' as clientname,
                '' as itemname,
                0 as itemid,
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
                '' as uom,
                '' as luom,
                '' as ditemname,
                '' as divsion,
                '' as brand,
                '' as model,
                '' as class,
                '' as category,
                '' as subcat,
                '' as whtype,
                '' as uom,
                '' as whtype2,
                '' as whtype3
                ");
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

        $result = $this->reportDefaultLayout_NONE($config);

        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->DEFAULT_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function DEFAULT_QUERY($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $asof         = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $barcode      = $config['params']['dataparams']['barcode'];
        $classname    = $config['params']['dataparams']['classic'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $subcatname   = $config['params']['dataparams']['subcatname'];
        $groupname    = $config['params']['dataparams']['stockgrp'];
        $brandname    = $config['params']['dataparams']['brandname'];
        $modelname    = $config['params']['dataparams']['modelname'];
        $whtype1      = $config['params']['dataparams']['whtype'];
        $whtype2      = $config['params']['dataparams']['whtype2'];
        $whtype3      = $config['params']['dataparams']['whtype3'];
        $uom          = $config['params']['dataparams']['uom'];

        $order = " order by category,itemname";
        $filter = " ";
        if ($brandname != "") {
            $brandid = $config['params']['dataparams']['brandid'];
            $filter .= " and item.brand=" . $brandid;
        }
        if ($classname != "") {
            $classid = $config['params']['dataparams']['classid'];
            $filter .= " and item.class=" . $classid;
        }
        if ($modelname != "") {
            $modelid = $config['params']['dataparams']['modelid'];
            $filter .= " and item.model=" . $modelid;
        }
        if ($groupname != "") {
            $groupid = $config['params']['dataparams']['groupid'];
            $filter .= " and item.groupid=" . $groupid;
        }
        if ($categoryname != "") {
            $category = $config['params']['dataparams']['category'];
            $filter .= " and item.category='" . $category . "'";
        }
        if ($subcatname != "") {
            $subcat = $config['params']['dataparams']['subcat'];
            $filter .= " and item.subcat='" . $subcat . "'";
        }
        if ($barcode != "") {
            $itemid = $config['params']['dataparams']['itemid'];
            $filter .= " and item.itemid=" . $itemid;
        }
        if ($uom != "") {
            $filter .= " and item.uom='" . $uom . "'";
        }

        $filterwh = '';
        if ($whtype1 != "") {
            $filterwh .= " wh.type='$whtype1'";
        } else {
            $whtype1 = '-';
        }
        if ($whtype2 != "") {
            $filterwh .= ($filterwh != '' ? ' or ' : '') . " wh.type='$whtype2'";
        } else {
            $whtype2 = '-';
        }
        if ($whtype3 != "") {
            $filterwh .= ($filterwh != '' ? ' or ' : '') . " wh.type='$whtype3'";
        } else {
            $whtype3 = '-';
        }

        $filter2 = "";
        if ($filterwh != '') {
            $filter2 .= ' and (' . $filterwh . ')';
        }

        $query = "
        select item.disc, cat.name as category, item.itemid,item.barcode, item.itemname, item.uom, ifnull(partgrp.part_name,'') as part,
        ifnull(sum(ib.qty-ib.iss),0) as balance,sum(whbal1) as whbal1,sum(whbal2) as whbal2,sum(whbal3) as whbal3
        from item left join (
        select stock.itemid, wh.client as swh, wh.clientname as whname, stock.qty, stock.iss, 
        if(wh.type='$whtype1',(stock.qty - stock.iss),0) as whbal1,
        if(wh.type='$whtype2',(stock.qty - stock.iss),0) as whbal2,
        if(wh.type='$whtype3',(stock.qty - stock.iss),0) as whbal3
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        where head.dateid<='$asof' $filter2
        
        UNION ALL

        select stock.itemid, wh.client as swh, wh.clientname as whname, stock.qty, stock.iss,
        if(wh.type='$whtype1',(stock.qty - stock.iss),0) as whbal1,
        if(wh.type='$whtype2',(stock.qty - stock.iss),0) as whbal2,
        if(wh.type='$whtype3',(stock.qty - stock.iss),0) as whbal3
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        where  head.dateid<='$asof' $filter2
        ) as ib on ib.itemid=item.itemid
        left join itemcategory as cat on cat.line = item.category
        left join part_masterfile as partgrp on partgrp.part_id = item.part
        where item.isofficesupplies=0 $filter  
        group by disc, category, item.itemid,barcode, itemname, cat.name, uom, partgrp.part_name" . $order;
        
        return $query;
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
        $whtype1         = $config['params']['dataparams']['whtype'];
        $whtype2         = $config['params']['dataparams']['whtype2'];
        $whtype3         = $config['params']['dataparams']['whtype3'];

        if ($brandname == '') {
            $brandname = "ALL";
        }

        if ($modelname == '') {
            $modelname = "ALL";
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
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('INVENTORY PER WAREHOUSE TYPE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
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
        $str .= $this->reporter->col('', '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

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
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('ITEM CODE', '100', null, false, '1px solid ', 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '450', null, false, '1px solid ', 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('WH Type 1 ' . $whtype1, '90', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('WH Type 2 ' . $whtype2, '90', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('WH Type 3 ' . $whtype3, '90', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '105', null, false, '1px solid ', 'B', 'R', $font, $font_size, 'B', '', '');
        return $str;
    }

    public function reportDefaultLayout_NONE($config)
    {
        $result = $this->reportDefault($config);

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $count = 46;
        $page = 45;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader_NONE($config);

        $totalbalwh1 = 0;
        $totalbalwh2 = 0;
        $totalbalwh3 = 0;
        $part = "";
        $scatgrp = "";
        $totalext = 0;
        $grandtotal = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            if ($data->part != 0 || $data->part != null) {
                if (strtoupper($part) == strtoupper($data->part)) {
                    $part = "";
                } else {
                    $part = strtoupper($data->part);
                }
            } else {
                $part = "";
            }

            if ($data->category != 0 || $data->category != null) {
                if (strtoupper($scatgrp) == strtoupper($data->category)) {
                    $scatgrp = "";
                } else {
                    $scatgrp = strtoupper($data->category);
                }
            } else {
                $scatgrp = "";
            }

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

            $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($scatgrp, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $totalext = $data->whbal1 + $data->whbal2 + $data->whbal3;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemname, '450', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->whbal1 == 0 ? '-' . '&nbsp&nbsp&nbsp' : number_format($data->whbal1, 2) . '&nbsp&nbsp&nbsp', '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->whbal2 == 0 ? '-' . '&nbsp&nbsp&nbsp' : number_format($data->whbal2, 2) . '&nbsp&nbsp&nbsp', '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->whbal3 == 0 ? '-' . '&nbsp&nbsp&nbsp' : number_format($data->whbal3, 2) . '&nbsp&nbsp&nbsp', '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($totalext == 0 ? '-' . '&nbsp&nbsp&nbsp' : number_format($totalext, 2), '105', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $scatgrp = strtoupper($data->category);
            $part = strtoupper($data->part);
            $grandtotal = $grandtotal + $totalext;
            $totalbalwh1 = $totalbalwh1 + $data->whbal1;
            $totalbalwh2 = $totalbalwh2 + $data->whbal2;
            $totalbalwh3 = $totalbalwh3 + $data->whbal3;
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader_NONE($config);
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= '<br/>';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('OVERALL STOCKS :', '450', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '');
        $str .= $this->reporter->col(number_format($totalbalwh1, 2) . '&nbsp', '90', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalbalwh2, 2) . '&nbsp&nbsp', '90', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalbalwh3, 2) . '&nbsp&nbsp', '90', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '105', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class