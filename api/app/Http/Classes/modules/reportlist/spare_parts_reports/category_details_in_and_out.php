<?php

namespace App\Http\Classes\modules\reportlist\spare_parts_reports;

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

class category_details_in_and_out
{
    public $modulename = 'Category Details In and Out';
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
        $fields = [
            'radioprint',
            'start',
            'end',
            'dwhname',
            'itemcategoryname'
        ];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'itemcategoryname.name', 'categoryname');
        data_set($col1, 'itemcategoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'itemcategoryname.lookupclass', 'lookupcategoryitemstockcard');
        data_set($col1, 'itemcategoryname.class', 'cscscategocsryname sbccsreadonly');

        $fields = ['radioposttype'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => 'posted', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => 'unposted', 'color' => 'teal'],
            ['label' => 'All', 'value' => 'all', 'color' => 'teal']
        ]);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("
            select 'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            0 as whid,
            '' as wh,
            '' as whname,
            '' as dwhname,
            '' as category, 
            '' as categoryname,
            'all' as posttype
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
        return [
            'status' => true,
            'msg' => 'Generating report successfully.',
            'report' => $str,
            'params' => $this->reportParams
        ];
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
        $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype  = $config['params']['dataparams']['posttype'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $wh       = $config['params']['dataparams']['wh'];
        $posttype  = $config['params']['dataparams']['posttype'];

        $filter = '';
        if ($categoryname != '') {
            $category  = $config['params']['dataparams']['category'];
            $filter .= " and item.category = '$category'";
        }
        if ($wh != "") {
            $whid = $config['params']['dataparams']['whid'];
            $filter = $filter . " and stock.whid=" . $whid;
        }

        switch ($posttype) {
            case 'posted': // posted
                $query = "select stockno, itemname as part_name, category, partno,  sum(rrqty) as rrqty, sum(isqty) as isqty, sum(amount) as amount
                from (select 'p' as tr, head.docno, head.doc, head.dateid, item.barcode as stockno, item.itemname, ifnull(cat.name, '') as category, wh.client, ifnull(item.partno, '') as partno, ifnull(p.part_name, '') as part_name, stock.rrqty, stock.isqty, stock.ext as amount
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid=stock.itemid
                left join client as wh on wh.clientid = stock.whid
                left join itemcategory as cat on cat.line = item.category
                left join part_masterfile as p on p.part_id = item.part
                where item.barcode <> '' and item.itemname <> '' and cat.name <> 'MC UNIT'  and date(head.dateid) between '$start' and '$end' $filter) as x
                group by stockno, itemname,category, partno, part_name
                order by stockno";
                break;
            case 'unposted': // unposted
                $query = "select stockno, itemname as part_name, category, partno,  sum(rrqty) as rrqty, sum(isqty) as isqty, sum(amount) as amount
                from (select 'u' as tr, head.docno, head.doc, head.dateid, item.barcode as stockno, item.itemname, ifnull(cat.name, '') as category, wh.client, ifnull(item.partno, '') as partno, ifnull(p.part_name, '') as part_name, stock.rrqty, stock.isqty, stock.ext as amount
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join item on item.itemid=stock.itemid
                left join client as wh on wh.clientid = stock.whid
                left join itemcategory as cat on cat.line = item.category
                left join part_masterfile as p on p.part_id = item.part
                where item.barcode <> '' and item.itemname <> '' and cat.name <> 'MC UNIT'  and date(head.dateid) between '$start' and '$end' $filter) as x
                group by stockno, itemname,category, partno, part_name
                order by stockno";
                break;
            case 'all': // all
                $query = "
                select x.stockno, x.itemname as part_name, x.category, x.partno, sum(x.rrqty) as rrqty,  sum(x.isqty) as isqty, sum(x.amount) as amount
                from (
                    select 'p' as tr, head.docno, head.doc, head.dateid, item.barcode as stockno, item.itemname, ifnull(cat.name, '') as category, wh.client, 
                    ifnull(item.partno, '') as partno,  stock.rrqty, stock.isqty, stock.ext as amount
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid=stock.itemid
                    left join client as wh on wh.clientid = stock.whid
                    left join itemcategory as cat on cat.line = item.category
                    left join part_masterfile as p on p.part_id = item.part
                    where  item.barcode <> '' and item.itemname <> '' and cat.name <> 'MC UNIT' and date(head.dateid) between '$start' and '$end' $filter
                    union all
                    select 'u' as tr, head.docno, head.doc, head.dateid, item.barcode as stockno, item.itemname, ifnull(cat.name, '') as category, 
                    wh.client, ifnull(item.partno, '') as partno,  stock.rrqty, stock.isqty, stock.ext as amount
                    from lahead as head
                    left join lastock as stock on stock.trno = head.trno
                    left join item on item.itemid=stock.itemid
                    left join client as wh on wh.clientid = stock.whid
                    left join itemcategory as cat on cat.line = item.category
                    left join part_masterfile as p on p.part_id = item.part
                    where item.barcode <> '' and item.itemname <> '' and cat.name <> 'MC UNIT'  and date(head.dateid) between '$start' and '$end' $filter
                ) as x
                group by x.stockno, x.itemname, x.category, x.partno
                order by x.stockno";
                break;
        }

        return $query;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype  = $config['params']['dataparams']['posttype'];
        $warehouse  = $config['params']['dataparams']['whname'];
        $category  = $config['params']['dataparams']['categoryname'];

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        switch ($posttype) {
            case 'posted':
                $posttype = "POSTED";
                break;
            case 'unposted':
                $posttype = "UNPOSTED";
                break;
            default:
                $posttype = "ALL";
                break;
        }

        $warehouse = $warehouse == '' ? 'ALL' : $warehouse;
        $category = $category == '' ? 'ALL' : $category;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '', '', 0, '', 0, 2) . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse: ' . $warehouse, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category: ' . $category, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STOCK #', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PART NUMBER', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PART NAME', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('IN', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OUT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $count = 38;
        $page = 40;

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);

        $totalin = 0;
        $totalout = 0;
        $totalamt = 0;

        foreach ($result as $key => $data) {

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->stockno == '' ? '-' : $data->stockno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->partno == '' ? '-' : $data->partno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->part_name == '' ? '-' : $data->part_name, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->rrqty == 0 ? '-' : number_format($data->rrqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->isqty == 0 ? '-' : number_format($data->isqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->header_DEFAULT($config);
                $page += $count;
            } //end if

            $totalin += $data->rrqty;
            $totalout += $data->isqty;
            $totalamt += $data->amount;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL: ', '300', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalin, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalout, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class