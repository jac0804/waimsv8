<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class brand_sales_summary_per_size
{
    public $modulename = 'Brand Sales Summary per Size';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

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
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function reportplotting($config)
    {

        return $this->report_SUMMARIZED_Layout($config);
    }

    public function reportDefault($config)
    {

        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
        $query = "select brandname,sum(netsales) as netsales
                    from (
                    select  if(brand.brand_desc is null or brand.brand_desc = '', 'No Brand',brand.brand_desc) as brandname,
                            sum(stock.ext) as netsales
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item on item.itemid = stock.itemid
                    left join frontend_ebrands as brand on brand.brandid = item.brand
                    where head.doc='sj'  and date(head.dateid) between '$start' and '$end'  
                    group by brand.brand_desc

                    union all

                    select if(brand.brand_desc is null or brand.brand_desc = '', 'No Brand',brand.brand_desc) as brandname,
                            sum(stock.ext) as netsales
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item on item.itemid = stock.itemid
                    left join frontend_ebrands as brand on brand.brandid = item.brand
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'  
                    group by brand.brand_desc) as s
                    group by brandname order by brandname";

        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }



    public function uomlist($config)
    {

        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
        $query = "select brandname,sum(netsales) as netsales,uom
                    from (
                    select  if(brand.brand_desc is null or brand.brand_desc = '', 'No Brand',brand.brand_desc) as brandname,
                            sum(stock.ext) as netsales, item.uom
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item on item.itemid = stock.itemid
                    left join frontend_ebrands as brand on brand.brandid = item.brand
                    where head.doc='sj'  and date(head.dateid) between '$start' and '$end'  
                    group by brand.brand_desc,item.uom

                    union all

                    select if(brand.brand_desc is null or brand.brand_desc = '', 'No Brand',brand.brand_desc) as brandname,
                            sum(stock.ext) as netsales,item.uom
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item on item.itemid = stock.itemid
                    left join frontend_ebrands as brand on brand.brandid = item.brand
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'  
                    group by brand.brand_desc,item.uom) as s
                    group by brandname,uom order by brandname";

        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    public function count_all_oumlist($config, $data)
    {
        $count = 0;
        foreach ($data as $i => $value) {
            $count++;
        }
        return $count;
    }

    private function displayHeader($config, $layoutsize)
    {

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '12';
        $center   = $config['params']['center'];
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $oumnamelist = $this->uomlist($config);

        $oumlookup = [];
        foreach ($oumnamelist as $array_index => $array) {
            $lookupKey = $array->brandname . '_' . $array->netsales;
            $oumlookup[$lookupKey][$array->uom] = $array->uom;
        }


        $unique_uoms = [];
        foreach ($oumnamelist as $row) {
            $unique_uoms[strtoupper(trim($row->uom))] = true;
        }
        $unique_uoms = array_keys($unique_uoms); //unique uom
        //  pass the unique oum
        $uom_count = $this->count_all_oumlist($config, $unique_uoms);
        $layoutsize = 250 + ($uom_count * 100);


        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Brand Sales Summary per Size', null, null, false, '1px solid ', 'C', 'C', $font, '13', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

        $startdate = $start;
        $startt = new DateTime($startdate);
        $start = $startt->format('m/d/Y');

        $enddate = $end;
        $endd = new DateTime($enddate);
        $end = $endd->format('m/d/Y');

        $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BRAND DESCRIPTION', '250', null, false, '1px solid ', 'BT', 'C', $font, $font_size, 'B', '', '5px');

        foreach ($unique_uoms as $uom) {
            $str .= $this->reporter->col(strtoupper($uom), '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '5px');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'C', $font, '4', 'B', '', '');

        foreach ($unique_uoms as $uom) {
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, '4', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        return $str;
    }



    public function report_SUMMARIZED_Layout($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $result = $this->uomlist($config);
        $count = 45;
        $page = 45;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // grouped data and unique UOM list 
        $grouped = [];
        $unique_uoms = [];

        foreach ($result as $row) {
            $brand = $row->brandname;
            $uom   = strtoupper(trim($row->uom));
            $netsales = $row->netsales;

            // collect unique uoms
            $unique_uoms[$uom] = true;

            // group by brand + uom
            if (!isset($grouped[$brand])) {
                $grouped[$brand] = [];
            }
            if (!isset($grouped[$brand][$uom])) {
                $grouped[$brand][$uom] = 0;
            }
            $grouped[$brand][$uom] += $netsales;
        }

        // convert uoms to ordered array
        $unique_uoms = array_keys($unique_uoms);

        $uom_count = $this->count_all_oumlist($config, $unique_uoms);
        $layoutsize = 250 + ($uom_count * 100);


        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $layoutsize);
      
        $grandTotals = array_fill_keys($unique_uoms, 0);
        $pageTotals  = array_fill_keys($unique_uoms, 0);

        // Secon loop: render grouped rows 
        foreach ($grouped as $brand => $uomValues) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            // brand name isang beses lang ipiprint
            $str .= $this->reporter->col($brand, '250', null, false, '1px dotted ', '', 'L', $font, $font_size, '', '', '', '');

            // print per-UOM values
            foreach ($unique_uoms as $uom) {
                $val = isset($uomValues[$uom]) ? number_format($uomValues[$uom], 2) : '';
                $num = isset($uomValues[$uom]) ? $uomValues[$uom] : 0;
                $pageTotals[$uom]  += $num;
                $grandTotals[$uom] += $num;

                $str .= $this->reporter->col($val, '100', null, false, '1px dotted ', '', 'R', $font, $font_size, '', '', '', '');
            }


            $str .= $this->reporter->endrow();

            // handle page break
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'L', $font, '4', '', '', '', '');
                foreach ($unique_uoms as $uom) {
                    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '4', '', '', '', '');
                }
                $str .= $this->reporter->endtable();
                $str .= $this->footer($config, $pageTotals, $unique_uoms, $layoutsize);
                // $grandTotals[$uom] = 0;
                $pageTotals = array_fill_keys($unique_uoms, 0);

                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $layoutsize);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', '250', null, false, '1px solid ', 'T', 'L', $font, '12', 'B', '', '', '');

        foreach ($unique_uoms as $uom) {
            $val = $grandTotals[$uom] != 0 ? number_format($grandTotals[$uom], 2) : '';
            $str .= $this->reporter->col($val, '100', null, false, '1px solid ', 'T', 'R', $font, '12', 'B', '', '', '');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', $layoutsize, null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();

        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('m/d/Y h:i:s a'); //2025-09-25 16:46:32 pm
        $str .= $this->reporter->col($formattedDate, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $lay = $layoutsize - 250;
        $str .= $this->reporter->pagenumber('Page', $lay, null, '', $border, '', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }


    public function footer($config, $data, $unique, $layoutsize)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAGE TOTAL', '250', null, false, '1px solid ', 'T', 'L', $font, '12', 'B', '', '', '');
        foreach ($unique as $uom) {
            $val = $data[$uom] != 0 ? number_format($data[$uom], 2) : '';
            $str .= $this->reporter->col($val, '100', null, false, '1px solid ', 'T', 'R', $font, '12', 'B', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', $layoutsize, null, false,  '', '',  'L', $font, '2', '', '',  '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();

        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('m/d/Y h:i:s a'); //2025-09-25 16:46:32 pm
        $str .= $this->reporter->col($formattedDate, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $lay = $layoutsize - 250;
        $str .= $this->reporter->pagenumber('Page', $lay, null, '', $border, '', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= $this->reporter->endreport();

        return $str;
    }
}//end class