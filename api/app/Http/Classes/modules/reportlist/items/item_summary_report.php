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

class item_summary_report
{
    public $modulename = 'Item Summary Report';
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
        $fields = ['radioprint', 'start', 'end', 'categoryname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'categoryname.lookupclass', 'lookupcategory_stock');
        data_set($col1, 'categoryname.label', 'Item Category');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $companyid = $config['params']['companyid'];
        $paramstr = "select 
        'default' as print,
        date(now()) as start,
        left(now(),10) as end,
        '' as categoryname,
        '' as category
        ";
        return $this->coreFunctions->opentable($paramstr);
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
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = $this->othersClass->sbcdateformat($config['params']['dataparams']['start']);
        $end = $this->othersClass->sbcdateformat($config['params']['dataparams']['end']);

        $filter = '';

        $query = "select category, class, itemname, uom,sum(isqty) as qty,
        min(amount) as low_price, max(amount) as high_price,
        sum(sales) as total_sales, sum(sales) / nullif(sum(isqty), 0) as avg_price from (
        select cat.name as category, cls.cl_name as class, item.itemname, stock.uom as uom,
        stock.ext as sales, stock.amt as amount, stock.isqty as isqty
        from lahead as head
        left join lastock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join item_class as cls on cls.cl_id = item.class
        left join itemcategory as cat on cat.line = item.category
        where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
        union all
        select cat.name as category, cls.cl_name as class, item.itemname, stock.uom as uom,
        stock.ext as sales, stock.isamt as amount, stock.qty as isqty
        from glhead as head
        left join glstock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join item_class as cls on cls.cl_id = item.class
        left join itemcategory as cat on cat.line = item.category
        where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
        ) as a
        GROUP BY category, class, itemname, uom
        ORDER BY category, class, itemname";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('d-M-Y', strtotime($config['params']['dataparams']['start']));
        $end = date('d-M-Y', strtotime($config['params']['dataparams']['end']));
        $printDate = date("m/d/Y  g:i:s A");

        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = '11';
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM SUMMARY FOR ' . $start . ' TO ' . $end, '1000', null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date : ' . $printDate, '400', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '400');
        $str .= $this->reporter->pagenumber('Page', '200', null, false, '', '', 'R', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('QTY', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('UOM', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '340', null, false, $border, 'TB', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('SALES', '140', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('LOW PRICE', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('HIGH PRICE', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('AVG. PRICE', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = '10';
        $border = "1px solid ";
        $dashedBorder = "1px dashed ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $limitPerPage = 40;
        $rowCount = 0;
        $lastcategory = null;
        $lastclass = null;

        $classSubtotal = 0;
        $categorySubtotal = 0;
        $grandTotal = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        foreach ($result as $data) {

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $lastcategory = null;
                $lastclass = null;
            }

            if ($lastclass !== null && $data->class !== $lastclass) {
                $str .= $this->printSubtotalRow($layoutsize, $font, $dashedBorder, '    ' . strtoupper($lastclass) . ' - Sub Total :', $classSubtotal);
                $classSubtotal = 0;
            }

            if ($lastcategory !== null && $data->category !== $lastcategory) {
                $str .= $this->printSubtotalRow($layoutsize, $font, $border, strtoupper($lastcategory), $categorySubtotal, true);
                $categorySubtotal = 0;
            }

            if ($data->category !== $lastcategory) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(strtoupper($data->category), '200', null, false, $border, '', 'L', $font, '11', 'B', '', '');
                $str .= $this->reporter->col('', '800');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $lastcategory = $data->category;
                $lastclass = null;
            }

            if ($data->class !== $lastclass) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '40');
                $str .= $this->reporter->col(strtoupper($data->class), '200', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '760');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $lastclass = $data->class;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(number_format($data->qty, 2), '80', null, false, '', '', 'R', $font, $fontsize);
            $str .= $this->reporter->col($data->uom, '80', null, false, '', '', 'C', $font, $fontsize);
            $str .= $this->reporter->col($data->itemname, '340', null, false, '', '', 'L', $font, $fontsize);
            $str .= $this->reporter->col(number_format($data->total_sales, 2), '140', null, false, '', '', 'R', $font, $fontsize);
            $str .= $this->reporter->col(number_format($data->low_price, 2), '120', null, false, '', '', 'R', $font, $fontsize);
            $str .= $this->reporter->col(number_format($data->high_price, 2), '120', null, false, '', '', 'R', $font, $fontsize);
            $str .= $this->reporter->col($data->avg_price === null ? '-' : number_format($data->avg_price, 2), '120', null, false, '', '', 'R', $font, $fontsize);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $classSubtotal += $data->total_sales;
            $categorySubtotal += $data->total_sales;
            $grandTotal += $data->total_sales;

            $rowCount++;
        }

        // flush trailing groups
        $str .= $this->printSubtotalRow($layoutsize, $font, $dashedBorder, '    ' . strtoupper($lastclass) . ' - Sub Total :', $classSubtotal);
        $str .= $this->printSubtotalRow($layoutsize, $font, $border, strtoupper($lastcategory), $categorySubtotal, true);

        $str .= '<br/>';
        $str .= $this->printSubtotalRow($layoutsize, $font, $border, 'GRAND TOTAL :', $grandTotal, false, true);

        $str .= $this->reporter->endreport();
        return $str;
    }

    private function printSubtotalRow($layoutsize, $font, $lineBorder, $label, $amount, $underlineLabel = false, $bold = true)
    {
        $labelText = $underlineLabel ? '<u>' . $label . '</u> - Sub Total :' : $label;

        $str = $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($labelText, '500', null, false, '', '', 'R', $font, '10', $bold ? 'B' : '');
        $str .= $this->reporter->col(number_format($amount, 2), '140', null, false, $lineBorder, 'T', 'R', $font, '10', 'B');
        $str .= $this->reporter->col('', '360');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

}// end class