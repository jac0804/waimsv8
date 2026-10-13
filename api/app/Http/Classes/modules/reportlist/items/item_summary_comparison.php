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

class item_summary_comparison
{
    public $modulename = 'Item Summary Comparison';
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
        $fields = ['radioprint', 'year', 'month', 'categoryname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'year.required', true);
        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
        'default' as print,
        left(now(),4) as year, 
        '0' as bmonth,
        '' as month,
        '' as category, 
        '' as categoryname
        ");
    }

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

    public function reportQuery($config)
    {
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['bmonth'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $filter = '';

        if ($categoryname != '') {
            $filter .= "where cat.name = '" . $categoryname . "'";
        }

        $query = "
        select ifnull(cat.name, 'No Category') as name, ifnull(cl.cl_name, 'No Class') as cl_name,l.qty, l.uom, item.itemname, l.last3, l.lastyear
        from (
        select itemid, uom, 
        sum(case when year(dateid) = $year and month(dateid) = $month then qty else 0 end) as qty,
        sum(case when dateid >= date_sub('$year-$month-01', interval 2 month) and dateid < date_add('$year-$month-01', interval 1 month) then ext else 0 end)
        /
        sum(case when dateid >= date_sub('$year-$month-01', interval 2 month) and dateid < date_add('$year-$month-01', interval 1 month) then qty else 0 end)
        as last3,
        sum(case when dateid >= date_sub('$year-$month-01', interval 12 month) and dateid < date_sub('$year-$month-01', interval 11 month) then ext else 0 end) as lastyear
        from (
            select s.itemid,s.isqty as qty, s.uom, s.ext, h.dateid from lahead as h
            join lastock as s on s.trno = h.trno
            where h.doc = 'SJ'
            and h.dateid >= date_sub('$year-$month-01', interval 12 month)
            and h.dateid < date_add('$year-$month-01', interval 1 month)
            union all
            select s.itemid,s.qty as qty, s.uom, s.ext, h.dateid from glhead as h
            join glstock as s on s.trno = h.trno
            where h.doc = 'SJ'
            and h.dateid >= date_sub('$year-$month-01', interval 12 month)
            and h.dateid < date_add('$year-$month-01', interval 1 month)
        ) as t
        group by itemid, uom
        ) as l
        left join item on item.itemid = l.itemid
        left join item_class as cl on cl.cl_id = item.class
        left join itemcategory as cat on cat.line = item.category $filter
        order by cat.name, cl.cl_name, item.itemname
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    public function header_layout($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['month'];

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "2px solid";
        $str .= '<br><br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM SUMMARY COMPARISON REPORT', null, null, false, $border, '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date : ' . date_format(date_create($current_timestamp), 'm/d/Y h:i:s A'), null, null, false, '', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->pagenumber('Page ', null, null, false, $border, '', 'R', $font, $fontsize, '', 'false', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('QTY', '100', '60', false, $border, 'TB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '5', '60', false, $border, 'T', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('UOM', '100', '60', false, $border, 'TB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '5', '60', false, $border, 'T', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('ITEM <br> DESCRIPTION', '400', '60', false, $border, 'TB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '5', '60', false, $border, 'T', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('LAST 3 MONTHS<br/>AVERAGE<br/>' . $year, '120', '60', false, $border, 'TB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '5', '60', false, $border, 'T', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('LAST YEAR<br>' . $month . '<br>' . ($year - 1), '120', '60', false, $border, 'TB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";
        $page = 45;
        $count = 0;

        $str .= $this->reporter->beginreport();
        $str .= $this->header_layout($config);
        $query = $this->reportQuery($config);

        $storedCat = '';
        $storedCls = '';

        if (empty($query)) {
            return $this->othersClass->emptydata($config);
        }

        foreach ($query as $data) {
            if ($count >= $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->header_layout($config);
                $count = 0;
                $storedCat = '';
                $storedCls = '';
            }
            //Category
            if ($data->name != $storedCat) { //new cat
                $storedCat = $data->name;
                $storedCls = '';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('<u>' . strtoupper($data->name) . '</u>', '100', '10', false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $count++;
            }

            if ($data->cl_name != $storedCls) { //new class
                $storedCls = $data->cl_name;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . strtoupper($data->cl_name), '100', '10', false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $count++;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->qty == 0 ? '-' : number_format($data->qty, 2), '100', '10', false, $border, '', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '5', '10', false, $border, '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->uom, '100', '10', false, $border, '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '5', '10', false, $border, '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->itemname, '400', '10', false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '5', '10', false, $border, '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->last3 == 0 ? '-' : number_format($data->last3, 2), '120', '10', false, $border, '', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '5', '10', false, $border, '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->lastyear == 0 ? '-' : number_format($data->lastyear, 2), '120', '10', false, $border, '', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $count++;
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}
