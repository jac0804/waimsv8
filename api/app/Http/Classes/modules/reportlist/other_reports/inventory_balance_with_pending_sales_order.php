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

class inventory_balance_with_pending_sales_order
{
    public $modulename = 'Inventory Balance with Pending Sales and  Purchase Order';
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
        $fields = ['radioprint',  'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'start.label', 'Balance as of');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
        data_set($col1, 'luom.action', 'replookupuom');


        $fields = ['radioreportitemtype', 'radiorepitemstock'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
            'default' as print,
            left(now(),10) as start,
            '' as client,
            '' as clientname,
            '' as itemname,
            '' as barcode,
            '' as groupid,
            '' as stockgrp,
            '' as brandid,
            '' as brandname,
            '' as classid,
            '' as classic,
            '' as categoryid,
            '' as categoryname,
            '' as subcatname,
            '' as modelid,
            '' as modelname,
            '' as wh,
            '' as whname,
            '(0,1)' as itemtype,
            '(0,1)' as itemstock,
            '' as ditemname,
            '' as divsion,
            '' as brand,
            '' as model,
            '' as class,
            '' as category,
            '' as subcat,
            '' as dwhname,
            '' as uom,
            '' as partid,
            '' as part,
            '' as partname,
            '' as whid
            ";
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
        return $this->reportDefaultLayout($config);
    }

    public function reportDefault($config)
    {
        // QUERY
        // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        // $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        // $query = " select i.barcode,i.itemname,i.uom,(ps.qty-ps.qa) as pobalance,po.docno as pendingpo,
        //          (select group_concat(so.docno) as docno from hsohead as so
        //          left join hsostock as sos on sos.trno=so.trno
        //          left join transnum as num on num.trno=so.trno
        //          where sos.itemid=i.itemid and sos.iss>sos.qa and sos.void=0 and num.postdate is not null) as pendingso,
        //             rrstat.bal as finalbal,i.itemid,rrst.trno,rrstat.trno, rrst.line,rrstat.line,ps.qty,ps.qa as served
        //         from hpohead as po
        //         left join hpostock as ps on ps.trno=po.trno
        //         left join transnum as num on num.trno=po.trno
        //         left join item as i on i.itemid=ps.itemid
        //         left join glstock as rrst on rrst.refx=ps.trno and rrst.linex=ps.line
        //         left join glhead as rrhead on rrhead.trno=rrst.trno
        //         left join rrstatus as rrstat on rrstat.trno=rrst.trno and rrstat.line=rrst.line
        //         where ps.qty>ps.qa and ps.void=0 and num.postdate is not null 
        //             and date(po.dateid) between '$start' and '$end'";

        $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));

        $barcode    = $config['params']['dataparams']['barcode'];
        $classid    = $config['params']['dataparams']['classid'];

        $category  = $config['params']['dataparams']['category'];
        $subcatname =  $config['params']['dataparams']['subcat'];
        $groupid    = $config['params']['dataparams']['groupid'];

        $brand    = $config['params']['dataparams']['brand'];
        $modelid    = $config['params']['dataparams']['modelid'];
        $wh         = $config['params']['dataparams']['wh'];
        $whid         = $config['params']['dataparams']['whid'];

        $itemstock  = $config['params']['dataparams']['itemstock'];
        $itemtype   = $config['params']['dataparams']['itemtype'];
        $uom = $config['params']['dataparams']['uom'];
        $order = " order by category,itemname";


        $filter = " and item.isimport in $itemtype";
        $filter1 = "";
        $filteritem = "";

        if ($brand != "") {
            $filteritem = $filteritem . " and item.brand='$brand'";
        }

        if ($modelid != "") {
            $filteritem = $filteritem . " and item.model='$modelid'";
        }

        if ($classid != "") {
            $filteritem = $filteritem . " and item.class='$classid'";
        }

        if ($category != "") {
            $filteritem = $filteritem . " and item.category='$category'";
        }

        if ($subcatname != "") {
            $filteritem = $filteritem . " and item.subcat='$subcatname'";
        }

        if ($barcode != "") {
            $filteritem = $filteritem . " and item.barcode='$barcode'";
        }
        if ($uom != '') {
            $filteritem = $filteritem . " and item.uom='$uom'";
        }


        $filteritem = $filteritem . " and item.groupid='$groupid'";


        if ($wh != "") {
            $filter = $filter . " and stock.whid='$whid'";
        }

        $query = "select ib.itemid, item.barcode,  item.itemname as itemname,ib.uom,
                    sum(ib.qty - ib.iss) as balance,
                                 (select sum(pos.qty) as pobal from hpohead as po
                                    left join hpostock as pos on pos.trno=po.trno
                                    left join transnum as num on num.trno=po.trno
                                    where pos.itemid=item.itemid and pos.qty>pos.qa and pos.void=0 and num.postdate is not null) as pendingpo,

                                    (select sum(sos.iss) as sobal from hsohead as so
                                    left join hsostock as sos on sos.trno=so.trno
                                    left join transnum as num on num.trno=so.trno
                                    where sos.itemid=item.itemid and sos.iss>sos.qa and sos.void=0 and num.postdate is not null) as pendingso

                    from (
                    select stock.itemid,   item.uom,
                    sum( stock.qty) as qty,
                    sum( stock.iss) as iss
                    from lahead as head
                    left join lastock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    where head.dateid <= '$asof'   $filter $filter1 $filteritem     and item.isofficesupplies = 0
                    group by stock.itemid,   item.uom
                    union all
                    select stock.itemid,  item.uom,
                    sum( stock.qty) as qty,
                    sum( stock.iss) as iss
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    where head.dateid <= '$asof'    $filter $filter1 $filteritem     and item.isofficesupplies = 0
                    group by stock.itemid, item.uom ) as ib
                    left join item on item.itemid = ib.itemid
                    group by ib.itemid, barcode, itemname,ib.uom,item.itemid
                    having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $barcode    = $config['params']['dataparams']['barcode'];
        $itemname   = $config['params']['dataparams']['itemname'];
        $classid    = $config['params']['dataparams']['classid'];
        $classname  = $config['params']['dataparams']['classic'];
        $categoryid = $config['params']['dataparams']['categoryid'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $subcatname =  $config['params']['dataparams']['subcat'];
        $groupid    = $config['params']['dataparams']['groupid'];
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
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        // $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Inventory Balance with Pending Sales Order and Pending Purchase Order', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        if ($barcode == '') {
            $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        } else {
            $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        }


        if ($groupname == '') {
            $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        } else {
            $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        }


        $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

        if ($categoryname == '') {
            $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        } else {
            $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
        $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


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
        $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


        $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        } else {
            $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();













        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('Balance as of: ', '100', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col($asof, '900', null, false, $border, '', 'L', $font, $font_size, 'B', '', ''); //150
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '300', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('UOM', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('PENDING PURCHASE ORDER', '150', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('PENDING SALES ORDER', '150', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FINAL BALANCE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $count = 25;
        $page = 25;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        foreach ($result as $key => $data) {
            $fin = $data->balance + $data->pendingpo;
            $finalbal = $fin - $data->pendingso;
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->barcode, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->itemname, '300', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->uom, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');

            $data->balance != 0 ?  $strbalance = number_format($data->balance, 2) : $strbalance = '-';
            $data->pendingpo != 0 ?  $strpendingpo = number_format($data->pendingpo, 2) : $strpendingpo = '-';
            $data->pendingso != 0 ?  $strpendingso = number_format($data->pendingso, 2) : $strpendingso = '-';
            $finalbal != 0 ?  $strfinalbal = number_format($finalbal, 2) : $strfinalbal = '-';

            $str .= $this->reporter->col($strbalance, '100', '', false, $border, '', 'RT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($strpendingpo, '150', '', false, $border, '', 'RT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($strpendingso, '150', '', false, $border, '', 'RT', $font, $font_size,  '', '', '', '', 0, 'max-width:240px;overflow-wrap: break-word;');
            $str .= $this->reporter->col($strfinalbal, '100', '', false, $border, '', 'RT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->endrow();
            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->displayHeader($config, $layoutsize);
            //     $page += $count;
            // }
        } //end foreach

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class