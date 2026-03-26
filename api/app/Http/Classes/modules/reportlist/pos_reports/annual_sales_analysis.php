<?php

namespace App\Http\Classes\modules\reportlist\pos_reports;

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
use DateTime;
use DateInterval;
use DatePeriod;

class annual_sales_analysis
{
    public $modulename = 'Annual Sales Analysis';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
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


        // data_set($col1, 'brand.lookupclass', 'brand');

        $fields = ['radioprint', 'year', 'dcentername', 'category', 'class', 'brand'];
        //, 'radioreportitemstatus', 'station_rep'
        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'dbranchname.required', true);
        // data_set($col1, 'station_rep.addedparams', ['branch']);
        // data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'category.lookupclass', 'lookupcategoryitemcategory');
        data_set($col1, 'category.labeldata', 'category');



        // $fields = ['radioprint', 'year', 'dcentername', 'category', 'class'];
        // //, 'radioreportitemstatus', 'station_rep'
        // $col1 = $this->fieldClass->create($fields);
        // // data_set($col1, 'dbranchname.required', true);
        // // data_set($col1, 'station_rep.addedparams', ['branch']);
        // // data_set($col1, 'ddeptname.label', 'Department');
        // data_set($col1, 'category.lookupclass', 'lookupcategoryitemcategory');
        // data_set($col1, 'category.labeldata', 'category');

        // $fields = ['print'];
        // $col2 = $this->fieldClass->create($fields);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


        return $this->coreFunctions->opentable("select 
            'default' as print,
            '' as year,'' as brandid,
            '' as brand,
            '" . $defaultcenter[0]['center'] . "' as center,
            '" . $defaultcenter[0]['centername'] . "' as centername,
            '" . $defaultcenter[0]['dcentername'] . "' as dcentername,

            '' as category, 
            '' as catid, 

            '' as class,
            '' as classic,
            '' as classid");
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

    public function reportDefault($config)
    {
        // QUERY
        $year = $config['params']['dataparams']['year'];

        $centername = $config['params']['dataparams']['centername'];
        $category = $config['params']['dataparams']['category'];
        $classic = $config['params']['dataparams']['classic'];
        $brand = $config['params']['dataparams']['brandid'];


        $filter = '';
        if ($centername != '') {
            $center = $config['params']['dataparams']['center'];
            $filter .= " and num.center=" . $center;
        }

        if ($category != '') {
            $catid = $config['params']['dataparams']['catid'];
            $filter .= " and i.category=" . $catid;
        }

        if ($classic != '') {
            $classid = $config['params']['dataparams']['classid'];
            $filter .= " and i.class=" . $classid;
        }

        if ($brand != '') {
            $brid = $config['params']['dataparams']['brandid'];
            $filter .= " and i.brand=" . $brid;
        }


        $query = "select mon,sum(g.ext) as sales,n from (
                select head.dateid,stock.ext,date_format(head.dateid,'%M') as mon,month(head.dateid) as n
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnum as num on num.trno=head.trno
                left join item as i on i.itemid=stock.itemid
                where num.doc in ('SJ','DR') and year(head.dateid)='$year'  $filter
                union all
                select head.dateid,stock.ext,date_format(head.dateid,'%M') as mon,month(head.dateid) as n
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join cntnum as num on num.trno=head.trno
                left join item as i on i.itemid=stock.itemid
                where num.doc in ('SJ','DR') and year(head.dateid)='$year'  $filter
            ) as g
            group by mon,n
            order by n";



        // select date_format(g.dateid,'%M') as mon,sum(g.ext) as sales from (
        //     select head.dateid,stock.ext
        //     from lahead as head
        //     left join lastock as stock on stock.trno=head.trno
        //     left join cntnum as num on num.trno=head.trno
        //     left join item as i on i.itemid=stock.itemid
        //     where num.doc in ('SJ','DR') and year(head.dateid)='$year' $filter
        //     union all
        //     select head.dateid,stock.ext
        //     from glhead as head
        //     left join glstock as stock on stock.trno=head.trno
        //     left join cntnum as num on num.trno=head.trno
        //     left join item as i on i.itemid=stock.itemid
        //     where num.doc in ('SJ','DR') and year(head.dateid)='$year' $filter
        // ) as g
        // group by mon
        // order by date_format(g.dateid,'%c')";



        return $this->coreFunctions->opentable($query);
    }

    private function defaultHeader($config, $currpage, $totalpages)
    {
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $year = $config['params']['dataparams']['year'];

        $centername = $config['params']['dataparams']['centername'];
        $brand = $config['params']['dataparams']['brandid'];

        $str = '';
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ANNUAL SALES ANALYSIS', '200', null, false, '1px solid ', '', 'C', $font, '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>YEAR:</b>' . $year, null, null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>BRAND:</b> ' . (!empty($brand) ? $brand : 'ALL BRAND'), null, null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>BRAND:</b> ' . $centername, null, null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        return $str;
    }

    private function defaultTableCols($layoutSize, $border, $font, $font_size)
    {
        $str = '';
        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Month', '200', null, false, $border, 'B', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Amount', '200', null, false, $border, 'B', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }

    private function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $layoutsize = $this->reportParams['layoutSize'];
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $page = 30;
        $count = 30;
        $totalpages = ceil(count($result) / $page);
        if (empty($result)) return $this->othersClass->emptydata($config);

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);

        $currpage = 1;
        $str .= $this->defaultHeader($config, $currpage, $totalpages);
        $str .= $this->defaultTableCols($layoutsize, $border, $font, $font_size);


        $total = 0;


        $i = 1;
        foreach ($result as $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('', '180', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->mon, '200', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(isset($data->sales) ? number_format($data->sales, 2) : '-', '200', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();

            $total += $data->sales;

            if ($i == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $currpage++;
                $str .= $this->defaultHeader($config, $currpage, $totalpages);
                $str .= $this->defaultTableCols($layoutsize, $border, $font, $font_size);
                $page += $count;
            }
            $i++;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '180', null, false, '1px solid ', '', 'RT', $font, $font_size - 8, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'R', $font, $font_size - 8, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'RT', $font, $font_size - 8, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'R', $font, $font_size - 8, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'RT', $font, $font_size - 8, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '180', null, false, '1px solid ', '', 'RT', $font, $font_size, 'B', '', '4px');
        $str .= $this->reporter->col('Total: ', '200', null, false, '1px solid ', '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'RT', $font, $font_size, 'B', '', '4px');
        $str .= $this->reporter->col(($total != 0) ? number_format($total, 2) : '-', '200', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'RT', $font, $font_size, 'B', '', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class