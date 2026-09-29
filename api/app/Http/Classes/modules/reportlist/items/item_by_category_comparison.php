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


class item_by_category_comparison
{
    public $modulename = 'Item by Category Comparison';
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
        $fields = ['radioprint', 'start', 'end','categoryname'];
        $col1 = $this->fieldClass->create($fields);
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
        adddate(left(now(),10),-30) as start,
        left(now(),10) as end,
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
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end  = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $category = $config['params']['dataparams']['category'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $filter = '';

        if ($categoryname != '' ){
            $filter .= "and cat.name = '" .$categoryname . "'";
        }

        $query ="
        select  cat.name as category, class.cl_name as class,year(head.dateid) as syear,
        (stock.ext-ifnull(info.lessvat,0)-ifnull(info.sramt,0)-ifnull(info.pwdamt,0)) as ext
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        left join itemcategory as cat on cat.line = item.category
        left join item_class as class on class.cl_id = item.class
        where item.category <> '' head.dateid between  '$start' and '$end' $filter
        union all
        select  cat.name as category, class.cl_name as class,year(head.dateid) as syear,
        (stock.ext-ifnull(info.lessvat,0)-ifnull(info.sramt,0)-ifnull(info.pwdamt,0)) as ext
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        left join itemcategory as cat on cat.line = item.category
        left join item_class as class on class.cl_id = item.class
        where item.category <> '' head.dateid between  '$start' and '$end' $filter
        group by syear, cl_name,cat.name,stock.ext,info.lessvat,info.sramt,info.pwdamt
        order by syear, category, class
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);        
    }

    public function header_layout($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date("Y", strtotime($config['params']['dataparams']['start']));
        $end  = date("Y", strtotime($config['params']['dataparams']['end']));
        $numYear = ($end - $start) + 1;
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid"; 
        $str .= '<br><br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '', '', 'l', $font, '14', 'B', '', '');
        $str .= $this->reporter->col('Print Date : '.date_format(date_create($current_timestamp), 'm/d/Y h:i:s A'), null, null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM SUMMARY COMPARISON', null, null, false, $border, '', 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->pagenumber('Page ', null, null, false, $border, '', 'R', $font, $fontsize, '', 'false', '');
       
       
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<u>CLASS / CATEGORY</u>', '400', '40', false, $border, 'B', 'C', $font, '10', 'B', '', '');
        for ($i = 0; $i < $numYear; $i++) { //year loop
            $str .= $this->reporter->col($start + $i, '120', '40', false, $border, 'TBLR', 'C', $font, '10', 'B', '', '');
        }
        $str .= $this->reporter->col('', '300', '40', false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $start = date("Y", strtotime($config['params']['dataparams']['start']));
        $end  = date("Y", strtotime($config['params']['dataparams']['end']));
        $numYear = ($end - $start) + 1;

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid"; 
        $page = 0;

        if ($numYear > 5) { //if more than 5 rows on years
            $this->reportParams['orientation'] = 'l';
            $page = 28;
        } else {
            $this->reportParams['orientation'] = 'p';
            $page = 40;
        }
        $str .= $this->reporter->beginreport();

        $str .= $this->header_layout($config);
        $query = $this->reportQuery($config);
        $catname = ''; //storing current category
        $catshown = '';
        $grandtotal = [];

        if (empty($query)) {
        return $this->othersClass->emptydata($config);
        }


        $grouped = [];
        foreach ($query as $row) {
            $grouped[$row->category][$row->class][$row->syear] = $row->ext;
        }
        foreach($grouped as $category => $classes){ //category
            $subtotal = [];
            foreach ($classes as $class => $years) { //class

                if($catname != $category){
                    $catname = $catshown = $category;
                }else if ($catname == $category){ //if stored category = current vategory
                    $catshown = '';
                }

                // row calculation
                $lines = $this->reporter->estimateRowLines([
                    [$catshown, 150, '6px'],
                    [$class, 250, '6px'],
                ], $fontsize  * 0.5);
                for ($l = 0; $l < $lines; $l++) {
                    $this->reporter->addline();
                }// row calculation END

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($catshown, '150', 30, false, $border, 'L', 'C', $font, '10', 'B', '', '');
                $str .= $this->reporter->col($class, '250', 30, false, $border, 'BL', 'L', $font, '10', '', '', '');
                for ($i = 0; $i < $numYear; $i++) {//loop on how many years
                    $str .= $this->reporter->col((!isset($years[$start + $i]) || $years[$start + $i] == 0)? '-' :  number_format($years[$start + $i],2), '120', 30, false, $border, 'BLR', 'R', $font, '10', '', '', '');
                    
                    $subtotal[$start + $i] = (isset($subtotal[$start + $i]) ? $subtotal[$start + $i] : 0) + (isset($years[$start + $i]) ? $years[$start + $i] : 0); //sub total accumulation
                    }
                $str .= $this->reporter->col('', '300', 30, false, $border, '', 'L', $font, '12', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter >= $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_layout($config);
                    $this->reporter->linecounter = 0;
                }
            }
            //subtotal
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '150', 30, false, $border, 'LB', 'C', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('CLASS SUBTOTAL:', '250', 30, false, $border, 'BL', 'R', $font, '10', 'B', '', '');
                for ($i = 0; $i < $numYear; $i++) {//loop on how many years
                    $str .= $this->reporter->col( number_format($subtotal[$start + $i],2), '120', 30, false, $border, 'BLR', 'R', $font, '10', 'B', '', '');

                    $grandtotal[$start + $i] = (isset($grandtotal[$start + $i]) ? $grandtotal[$start + $i] : 0) + $subtotal[$start + $i]; //grand total accumulation
                }
                $str .= $this->reporter->col('', '300', 30, false, $border, '', 'L', $font, '12', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $this->reporter->addline();
                if ($this->reporter->linecounter >= $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_layout($config);
                    $this->reporter->linecounter = 0;
                }
        }
        //grand total
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '150', 30, false, $border, 'LB', 'C', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('GRAND TOTAL:', '250', 30, false, $border, 'B', 'R', $font, '10', 'B', '', '');
                for ($i = 0; $i < $numYear; $i++) {//loop on how many years
                    $str .= $this->reporter->col( isset($grandtotal[$start + $i]) ? number_format($grandtotal[$start + $i],2) : '-', '120', 30, false, $border, 'BLR', 'R', $font, '12', 'B', '', '');
                }
                $str .= $this->reporter->col('', '300', 30, false, $border, '', 'L', $font, '11', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
    
        $str .= $this->reporter->endreport();
        return $str;
    }


}