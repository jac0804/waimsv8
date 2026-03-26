<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class supplier_price_per_item
{
    public $modulename = 'Supplier Price Per Item';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:2200px;max-width:2200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '2000'];

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

        $fields = ['radioprint', 'start', 'enddate', 'dclientname', 'ditemname'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'dclientname.lookupclass', 'wasupplier');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        left(now(),10) as enddate,0 as itemid, '' as ditemname, '' as barcode,
         0 as clientid,'' as client, '' as clientname,'' as dclientname";

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
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->DEFAULT_QUERY_v2($config); //DEFAULT_QUERY_v2
        return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    }


    public function main_query_v2($config)
    {
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
        $supp  = $config['params']['dataparams']['dclientname'];
        $suppid  = $config['params']['dataparams']['clientid'];
        $item  = $config['params']['dataparams']['ditemname'];
        $itemid  = $config['params']['dataparams']['itemid'];

        $filter = '';

        if ($supp != '') {
            $filter = " and stat.clientid = $suppid ";
        }

        if ($item != '') {
            $filter .= " and stat.itemid = $itemid ";
        }

        $query = "select stat.itemid,stat.clientid as suppid,
                        (select cost from rrstatus as s where s.itemid=stat.itemid and s.clientid=stat.clientid
                                and  s.dateid <= '$end' order by dateid desc limit 1) as price
                from rrstatus as stat
                left join glhead as head on head.trno=stat.trno
                where head.doc='RR' and stat.clientid <> 0 and date(stat.dateid) between '$start' and '$end' $filter
                group by stat.itemid,stat.clientid";

        return $query;
    }

    public function get_all_suppliers($config)
    {
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
        $supp  = $config['params']['dataparams']['dclientname'];
        $suppid  = $config['params']['dataparams']['clientid'];

        $filter_p = '';

        if ($supp != '') {
            $filter_p = " and head.clientid = $suppid ";
        }

        $qry2 = "select client,clientname,clientid 
                from (select supp.client,supp.clientname,head.clientid
                      from glhead as head
                      left join client as supp on supp.clientid=head.clientid
                      where head.doc='RR' and date(head.dateid) between '$start' and '$end' $filter_p ) as k
                where client is not null
                group by client,clientname,clientid
                order by client ";
        return $this->coreFunctions->opentable($qry2);
    }

    public function count_all_suppliers($config, $data)
    {
        $count = 0;
        foreach ($data as $i => $value) {
            $count++;
        }
        return $count;
    }

    public function DEFAULT_QUERY_v2($config)
    {
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
        $inner_count = '';
        $date_count = '';
        $all_suppliers = $this->get_all_suppliers($config);
        $filterbalance = '';

        // Main loop for suppliers
        foreach ($all_suppliers as $array_index => $array) {
            $inner_count .= ",sum(if(suppid=$array->clientid,price,0)) as '" . $array->client . "',
                            ifnull((select date(dateid) from rrstatus as s where s.itemid=ib.itemid 
                                    and s.clientid=$array->clientid
                                    and s.dateid <= '$end' order by date(dateid) desc limit 1),'') as '" . $array->client . '_ladate' . "',
                            (select rrcost from glstock as s left join glhead as h on h.trno=s.trno
                                        where s.itemid=ib.itemid and h.clientid=$array->clientid
                                                and h.dateid <='$end' order by dateid desc limit 1) as '" . $array->client . '_amount' . "',
                            (select s.disc from glstock as s left join glhead as h on h.trno=s.trno
                                where s.itemid=ib.itemid and h.clientid=$array->clientid
                                and h.dateid <= '$end' order by dateid desc limit 1) as '" . $array->client . '_disc' . "'
                                
                            ";
        }


        $query = "
            select item.barcode,item.itemname $inner_count from (
                
                " . $this->main_query_v2($config) . "
                
            ) as ib left join item on item.itemid=ib.itemid
            
            group by item.barcode,item.itemname,ib.itemid
            order by item.barcode
            ";
        return $query;
    }

    private function default_displayHeader($config)
    {
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start       = $config['params']['dataparams']['start'];
        $end       = $config['params']['dataparams']['enddate'];


        $str = '';
        $all_suppliers = $this->get_all_suppliers($config);
        $suppliers_count = $this->count_all_suppliers($config, $all_suppliers);
        $layoutsize = 500 + ($suppliers_count * 400);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('SUPPLIER PRICE PER ITEM', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        // $str .= $this->reporter->printline();

        $all_suppliers = $this->get_all_suppliers($config);
        $suppliers_count = $this->count_all_suppliers($config, $all_suppliers);
        $layoutsize = 500 + ($suppliers_count * 400);


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEMNAME', 500, null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, 'B', '', '');

        foreach ($all_suppliers as $array_index => $array) {
            $str .= $this->reporter->col($array->clientname, '400', null, false, '1px solid ', 'TBR', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 500, null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, 'B', '', '');

        foreach ($all_suppliers as $array_index => $array) {
            $str .= $this->reporter->col('DATE', '100', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('AMOUNT', '100', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('DISC', '100', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('COST' . '&nbsp', '100', null, false, '1px solid ', 'TBR', 'R', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $companyid = $config['params']['companyid'];
        $font_size = '10';
        $fontsize11 = 11;

        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $all_suppliers = $this->get_all_suppliers($config);
        $suppliers_count = $this->count_all_suppliers($config, $all_suppliers);
        $layoutsize = 500 + ($suppliers_count * 400); // fixed width based on all other columns and 100 per variable WH

        $s_string = [];

        foreach ($all_suppliers as $array_index => $array) {
            array_push($s_string, $array->client);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $totalprice = 0;
        $grandtotal = 0;

        $test = 0;

        $columnTotals = [];

        foreach ($result as $key => $data) {

            $itemname = $data['itemname'];
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '70', 0);
            $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname]);

            for ($r = 0; $r < $maxrow; $r++) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col(' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 500, null, false, '1px solid ', 'LR', 'L', $font, $font_size, '',  '', '');

                if ($r == 0) {
                    foreach ($data as $i => $value) {
                        // $i is the field column name in qry
                        // $value is balance

                        if (array_search($i, $s_string) !== false) {

                            if ($value == 0) {
                                $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'R', 'R', $font, $font_size, '', '', '');
                            } else {
                                $str .= $this->reporter->col($data[$i . '_ladate'], '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col(number_format($data[$i . '_amount'], 2), '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($data[$i . '_disc'], '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col(number_format($value, 2) . '&nbsp', '100', null, false, '1px solid ', 'R', 'R', $font, $font_size, '', '', '');
                            }

                            $columnTotals[$i] = $value;
                            $totalprice += $value;
                        }
                    }
                } else {
                    foreach ($data as $i => $value) {

                        // $i is the field column name in qry
                        // $value is balance
                        if (array_search($i, $s_string) !== false) {
                            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'R', 'R', $font, $font_size, '', '', '');
                        }
                    }
                }
                $str .= $this->reporter->endrow();
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', 500, null, false, '1px solid ', 'LRB', 'R', $font, $font_size, '', '', '');
            foreach ($data as $i => $value) {
                if (array_search($i, $s_string) !== false) {
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'RB', 'R', $font, $font_size, '', '', '');
                }
            }
            $str .= $this->reporter->endrow();



            $grandtotal += $totalprice;
        }

        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class