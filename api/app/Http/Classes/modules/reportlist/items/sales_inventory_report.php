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

class sales_inventory_report
{
    public $modulename = 'Sales Inventory Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

    public function __construct($params = null)
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass($params);
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'start', 'end', 'dwhname', 'groupid', 'model', 'brandname', 'brandid', 'dclientname', 'radiosort', 'prepared', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'area.readonly', true);
        data_set($col1, 'groupid.readonly', true);
        data_set($col1, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
            ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]);
        data_set($col1, 'radiosortby.options', [
            ['label' => 'Item Code', 'value' => 0, 'color' => 'green'],
            ['label' => 'Item Description', 'value' => 1, 'color' => 'green'],
            ['label' => 'Date', 'value' => 2, 'color' => 'green'],
            ['label' => 'Document #', 'value' => 3, 'color' => 'green'],
            ['label' => 'Customer', 'value' => 4, 'color' => 'green']
        ]);
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dwhname.label', 'Warehouse');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $start = $this->othersClass->getCurrentDate();
        $end = $this->othersClass->getCurrentDate();
        $yearstart = date('Y-01-01', strtotime($start));

        $paramstr = "select
        'default' as print,
        '" . $yearstart . "' as start,
        '" . $end . "' as end,
        '' as dclientname, 
        '' as client, 
        '' as clientname, 
        0 as clientid, 
        '' as wh, '' as whname, 0 as whid, '' as dwhname, 
        '' as groupid, 
        '' as groupname,
        '' as model,
        '' as modelid,
        '' as modelname,
        '' as brandid,
        '' as brandname,
        0 as radiosortby,
        '' as prepared,
        '' as approved
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
        $qry = $this->default_query($config);
        return $this->coreFunctions->opentable($qry);
    }


    public function default_query($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client = $config['params']['dataparams']['client'];
        $clientid = $config['params']['dataparams']['clientid'];
        $groupid = $config['params']['dataparams']['groupid'];
        $groupname = $config['params']['dataparams']['groupname'];
        $model = $config['params']['dataparams']['model'];
        $modelname = $config['params']['dataparams']['modelname'];
        $modelid = $config['params']['dataparams']['modelid'];
        $brandid = $config['params']['dataparams']['brandid'];
        $brandname = $config['params']['dataparams']['brandname'];
        $center = $config['params']['center'];
        $wh = $config['params']['dataparams']['wh'];
        $whid = $config['params']['dataparams']['whid'];
        $sortby = $config['params']['dataparams']['radiosortby'];
        $filter = "";
        $addfield = "";
        $addfield2 = "";
        $grp = "";

        if ($client != '') {
            $filter .= " and c.clientid= $clientid";
        }
        if ($groupname != '') {
            $filter .= " and item.groupid=" . $groupid;
        }
        if ($wh != '') {
            $filter .= " and stock.whid= $whid";
        }
        if ($modelname != '') {
            $filter .= " and item.model = $modelid";
        }
        if ($brandname != '') {
            $filter .= " and item.brand = $brandid";
        }

        switch ($sortby) {
            case 0:
                $orderby = 'barcode, brand, part, itemname';
                break;
            case 1:
                $orderby = 'itemname, brand, part';
                break;
            case 2:
                $orderby = 'dateid, brand, part, itemname';
                break;
            case 3:
                $orderby = 'docno, brand, part, itemname';
                break;
            case 4:
                $orderby = 'clientname, brand, part, itemname';
                break;
            default:
                $orderby = 'barcode, brand, part, itemname';
                break;
        }

        $query = "select barcode, itemname, clientname, cost, docno, dateid , qty, price,
        uom, brand, part,model,sizeid,body,ponum $addfield2
        from (
        select item.barcode, item.itemname, c.clientname, head.docno, date(head.dateid) as dateid, sum(stock.isqty) as qty,
        sum(stock.amt) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand,
        part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum $addfield
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join item on item.itemid=stock.itemid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join part_masterfile as part on part.part_id = item.part
        left join model_masterfile as model on model.model_id = item.model
        left join client as c on c.client=head.client
        left join cntnum as num on num.trno=head.trno
        where head.doc in ('sj', 'cm') and item.barcode is not null and num.center = '". $center ."' and date(head.dateid) between '$start' and '$end' $filter and item.isofficesupplies=0
        group by item.barcode, item.itemname, c.clientname, head.dateid,item.uom, brand.brand_desc, part.part_name,
        model.model_name,item.sizeid,item.body, head.docno,stock.ref $grp
        UNION ALL
        select item.barcode, item.itemname, c.clientname, head.docno, date(head.dateid) as dateid, sum(stock.isqty) as qty,
        sum(stock.amt) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand,
        part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum $addfield
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join part_masterfile as part on part.part_id = item.part
        left join model_masterfile as model on model.model_id = item.model
        left join client as c on c.clientid=head.clientid
        left join cntnum as num on num.trno=head.trno
        where head.doc in ('sj', 'cm') and item.barcode is not null  and num.center = '". $center ."' and head.dateid between '$start' and '$end' $filter and item.isofficesupplies=0
        group by item.barcode, item.itemname, c.clientname, docno, head.dateid,item.uom, brand.brand_desc,
        part.part_name,model.model_name,item.sizeid,item.body,stock.ref $grp) as ip
        order by $orderby";

        return $query;
    }

    private function default_displayHeader($config)
    {
        $border = '2px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $groupname = $config['params']['dataparams']['groupname'];
        $whname = $config['params']['dataparams']['whname'];
        $clientname = $config['params']['dataparams']['clientname'];
        $modelname = $config['params']['dataparams']['modelname'];
        $brandname = $config['params']['dataparams']['brandname'];
        $sortby = $config['params']['dataparams']['radiosortby'];
        $yearstart = date('Y-01-01', strtotime($start));

        $str = '';
        $layoutsize = '1000';
        $sortlabel = '';

        switch ($sortby) {
            case 0:
                $sortlabel = 'Item Code';
                break;
            case 1:
                $sortlabel = 'Item Description';
                break;
            case 2:
                $sortlabel = 'Date';
                break;
            case 3:
                $sortlabel = 'Document #';
                break;
            case 4:
                $sortlabel = 'Customer';
                break;
            default:
                $sortlabel = 'ALL';
                break;
        }

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SALES INVENTORY REPORT', null, null, false, $border, '', 'C', $font, '12', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($yearstart)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'C', $font, '10', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WH: <u>' . ($whname == '' ? 'ALL' : $whname). '</u>' . ' Group: <u>' . ($groupname == '' ? 'ALL' : $groupname) . '</u> Model: <u>' . ($modelname == '' ? 'ALL' : $modelname). '</u> Brand: <u>' . ($brandname == '' ? 'ALL' : $brandname) . '</u>', null, null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer : <u>' . ($clientname == '' ? 'ALL' : $clientname) . '</u> Sorted By: <u>' . $sortlabel . '</u>', null, null, false, '1px solid', '', 'C', $font, $font_size, '', '', $padding, $margin);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('<i>Page</i>', null, null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Item Code', '100', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('SRP', '80', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('Brand', '80', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('DATE', '80', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('Document #', '115', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('Customer', '215', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('QTY', '60', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '115', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '215', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $prepared = $config['params']['dataparams']['prepared'];
        $approved = $config['params']['dataparams']['approved'];
        $border = '2px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '8';
        $result = $this->reportDefault($config);
        $page = 45;

        // if (empty($result)) {
        //     return $this->othersClass->emptydata($config);
        // }

        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        foreach ($result as $key => $data) {

            // row calculation: how many lines will this row take, based on its widest-wrapping column
            $lines = $this->reporter->estimateRowLines([
                [$data->barcode, 100, '6px'],
                [$data->itemname, 200, '6px'],
                [$data->cost == 0 ? '-' : number_format($data->cost, 2), 80, '6px'],
                [$data->brand, 80, '6px'],
                [$data->dateid, 80, '6px'],
                [$data->docno, 115, '6px'],
                [$data->clientname, 215, '6px'],
                [$data->qty == 0 ? '-' : number_format($data->qty, 2), 60, '6px'],
            ], $font_size * 0.5);

            // if this row won't fit on the current page, break first and reprint the header
            if ($this->reporter->linecounter > 0 && ($this->reporter->linecounter + $lines) > $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $this->reporter->linecounter = 0;
            }

            for ($l = 0; $l < $lines; $l++) {
                $this->reporter->addline();
            } // row calculation END

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'LT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->cost == 0 ? '-' : number_format($data->cost, 2), '80', null, false, $border, '', 'RT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->brand, '80', null, false, $border, '', 'LT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'CT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->docno, '115', null, false, $border, '', 'CT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->clientname, '215', null, false, $border, '', 'LT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->qty == 0 ? '-' : number_format($data->qty, 2), '60', null, false, $border, '', 'RT', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // closing line
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared by: <u><b>' . $prepared . '</u></b>', null, null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
        $str .= $this->reporter->col('Approved by: <u><b>' . $approved . '</u></b>', null, null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class