<?php

namespace App\Http\Classes\modules\reportlist\motorcycle_reports;

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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class total_delivery_report
{
    public $modulename = 'Total Delivery Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1500'];

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
        $fields = ['radioprint', 'start', 'end', 'stockgrp', 'dwhname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'stockgrp.label', 'Category');
        data_set($col1, 'dwhname.label', 'Destination Warehouse');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'default', 'color' => 'red'],
        ]);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $whid = '';
        $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
        $whname = '';
        $dwhname = '';

        if ($wh != '') {
            $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);
            $whname = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$wh]);
            $dwhname = $wh . '~' . $whname;
        }

        return $this->coreFunctions->opentable("select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '' as groupid,
            '' as stockgrp,
            '" . $wh . "' as wh, 
            '" . $whid . "' as whid, 
            '" . $whname . "' as whname, 
            '" . $dwhname . "' as dwhname");
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $groupid     = $config['params']['dataparams']['groupid'];
        $stockgrp   = $config['params']['dataparams']['stockgrp'];
        $whid     = $config['params']['dataparams']['whid'];
        $whname     = $config['params']['dataparams']['whname'];

        $filter = '';
        if ($stockgrp != '') $filter .= " and stockgrp.stockgrp_id = " . $groupid . " ";
        if ($whname != '') $filter .= " and client.clientid = $whid";

        $query = "select date(head.dateid) as date,ifnull(head.yourref,'') as pono, client.clientname as location, 
        client.client, i.itemname as modelname, brand.brand_desc as brand, head.rem,
        ss.chassis as chassis, ss.serial as serial, ss.pnp as pnpno,
        ss.csr as csrno, stock.isamt as cost, ss.color, stock.isqty, stock.ext                             
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join item as i on i.itemid = stock.itemid
        left join itemcategory as cat on cat.line = i.category
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
        left join model_masterfile as model on model.model_id = i.model
        left join frontend_ebrands as brand on brand.brandid = i.brand
        left join client on client.client = head.client
        left join serialout as ss on ss.trno = stock.trno and ss.line = stock.line
        where head.doc='ST'  and head.dateid between '$start' and '$end' $filter 
        union all
        select date(head.dateid) as date, ifnull(head.yourref, '') as pono, wh.clientname as location, 
        client.client, i.itemname as modelname, brand.brand_desc as brand, head.rem,
        ss.chassis as chassis, ss.serial as serial, ss.pnp as pnpno,
        ss.csr as csrno, stock.isamt as cost, ss.color, stock.isqty, stock.ext                             
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item as i on i.itemid = stock.itemid
        left join itemcategory as cat on cat.line = i.category
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
        left join model_masterfile as model on model.model_id = i.model
        left join client on client.clientid = head.clientid
        left join client as wh on wh.clientid = head.whid
        left join frontend_ebrands as brand on brand.brandid = i.brand
        left join serialout as ss on ss.trno = stock.trno and ss.line = stock.line
        where head.doc='ST'  and stock.tstrno = 0 and head.dateid between '$start' and '$end' $filter
        order by brand,location,date";
        return $query;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $layoutsize = '1500';
        $totalcost = 0;
        $totalext = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {

            $brand = '';
            foreach ($result as $key => $data) {
                if ($brand != $data->brand) {
                    $brand = $data->brand;
                    if ($key > 0) $str .= "<br />";

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->brand, '1500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->date, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->location, '240', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->modelname . ' ' . $data->brand . ',' . $data->color, '240', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->chassis . ' ' . $data->serial, '240', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rem, '240', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pnpno . ' ' . $data->csrno, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->isqty, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->cost, $decimal), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $totalcost += $data->cost;
                $totalext += $data->ext;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '140', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Grand Total', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalcost, $decimal), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $category = $config['params']['dataparams']['stockgrp'];

        $str = '';
        $layoutsize = '1500';

        if (empty($category)) $category = "ALL";

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Delivery Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '750', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category : ' . $category, '750', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1500';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('LOCATION', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MODEL | BRAND | COLOR', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CHASSIS | ENGINE', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', '240', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PNP & CSR', '140', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QUANTITY', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COST', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class