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

class sell_out_per_item
{
    public $modulename = 'SELL OUT PER ITEM';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1000px;max-width:1000px;';
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
        $fields = ['radioprint', 'start', 'end', 'dwhname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'default', 'color' => 'red'],
        ]);

        
        $fields = ['radioposttype'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => 'posted', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => 'unposted', 'color' => 'teal'],
            ['label' => 'All', 'value' => 'all', 'color' => 'teal']
        ]);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return ['col1' => $col1,'col2' => $col2,'col3' => $col3];
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '' as client,
                0 as whid,
                '' as dwhname,
                '' as wh,
                '' as whname,
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
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $wh       = $config['params']['dataparams']['wh'];
        $posttype  = $config['params']['dataparams']['posttype'];
        $filter = '';
        if ($wh != "") {
            $whid = $config['params']['dataparams']['whid'];
            $filter = $filter . " and stock.whid=" . $whid;
        }

        switch ($posttype) {
            case 'posted': // posted
                $query = "
                select barcode,partno,partname,catname,body, sum(sold) as sold ,sum(amount) as  amount 
                from  (
                    select item.barcode,item.partno,ifnull(part.part_name,'') as partname, ifnull(cat.name,'') as catname,item.body,
                    sum(stock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as sold,sum(stock.ext) as amount
                    from glstock as stock
                    left join glhead on glhead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join part_masterfile as part on part.part_id = item.part
                    left join itemcategory as cat on cat.line = item.category
                    where glhead.doc = 'CI'  and date(glhead.dateid)  between '$start' and '$end' $filter
                    group by item.barcode,item.partno,part.part_name,cat.name,item.body
                    
                ) as x 
                group by  barcode,partno,partname,catname,body";
            break;
            case 'unposted': // unposted
                $query = "
                select barcode,partno,partname,catname,body, sum(sold) as sold ,sum(amount) as  amount 
                from  (
                    
                    select item.barcode,item.partno,ifnull(part.part_name,'') as partname, ifnull(cat.name,'') as catname,item.body,
                    sum(stock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as sold,sum(stock.ext) as amount
                    from lastock as stock
                    left join lahead on lahead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join part_masterfile as part on part.part_id = item.part
                    left join itemcategory as cat on cat.line = item.category
                    where lahead.doc = 'CI'   and date(lahead.dateid)  between '$start' and '$end' $filter
                    group by item.barcode,item.partno,part.part_name,cat.name,item.body 
                ) as x 
                group by  barcode,partno,partname,catname,body";
            break;
            case 'all': // all
                $query = "
                select barcode,partno,partname,catname,body, sum(sold) as sold ,sum(amount) as  amount 
                from  (
                    select item.barcode,item.partno,ifnull(part.part_name,'') as partname, ifnull(cat.name,'') as catname,item.body,
                    sum(stock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as sold,sum(stock.ext) as amount
                    from glstock as stock
                    left join glhead on glhead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as wh on wh.clientid=stock.whid
                    left join part_masterfile as part on part.part_id = item.part
                    left join itemcategory as cat on cat.line = item.category
                    where glhead.doc = 'CI'  and date(glhead.dateid)  between '$start' and '$end' $filter
                    group by item.barcode,item.partno,part.part_name,cat.name,item.body
                    union all 
                    select item.barcode,item.partno,ifnull(part.part_name,'') as partname, ifnull(cat.name,'') as catname,item.body,
                    sum(stock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as sold,sum(stock.ext) as amount
                    from lastock as stock
                    left join lahead on lahead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as wh on wh.clientid=stock.whid
                    left join part_masterfile as part on part.part_id = item.part
                    left join itemcategory as cat on cat.line = item.category
                    where lahead.doc = 'CI'   and date(lahead.dateid)  between '$start' and '$end' $filter
                    group by item.barcode,item.partno,part.part_name,cat.name,item.body 
                ) as x 
                group by  barcode,partno,partname,catname,body";
            break;
        }

        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $this->reporter->linecounter = 0;

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1000';
        $totalcost = 0;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->partno, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->body, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->partname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->catname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->sold, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totalcost += $data->amount;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL: ', '100', null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalcost, $decimal), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
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
        $whname     = $config['params']['dataparams']['whname'];
        $posttype  = $config['params']['dataparams']['posttype'];
        
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

        $str = '';
        $layoutsize = '1000';

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
        $str .= $this->reporter->col('' . $this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('WH: ' . ($whname == '' ? 'ALL' : $whname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BARCODE#', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PART NUMBER#', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUPERCEEDING#', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PART NAME', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CATEGORY', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SOLD', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class