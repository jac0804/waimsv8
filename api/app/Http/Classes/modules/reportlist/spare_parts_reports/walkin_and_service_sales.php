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

class walkin_and_service_sales
{
    public $modulename = 'WALKIN AND SERVICE SALES';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1800'];

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

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '' as client,
                '' as clientname,
                '' as dclientname,
                0 as whid,
                '' as wh,
                '' as whname,
                '' as dwhname,
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
                select concat(catname,trnxtype) as catname,sum(client) as client,sum(sold) as sold,sum(amount) as amount 
                from (
                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,case ifnull(mode.name,'') when '' then '' else concat('(',mode.name,')') end as trnxtype,
                    sum(stock.iss) as sold,sum(stock.ext) as amount
                    from glstock as stock
                    left join glhead on glhead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.clientid = glhead.clientid
                    left join itemcategory as cat on cat.line = item.category
                    left join client as wh on wh.clientid=stock.whid
                    left join mode_masterfile as mode on mode.line = glhead.trnxtype
                    where glhead.doc = 'CI' and  date(glhead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client,mode.name
                    union all
                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,'RETURN ITEM' as trnxtype,
                    sum(stock.qty)*-1 as sold,sum(stock.ext)*-1 as amount
                    from glstock as stock
                    left join glhead on glhead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.clientid = glhead.clientid
                    left join itemcategory as cat on cat.line = item.category
                    left join client as wh on wh.clientid=stock.whid
                    where glhead.doc = 'CM' and  date(glhead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client
                ) as x 
                group by catname,trnxtype";
                break;
            case 'unposted': // unposted
                $query = "
                select concat(catname,trnxtype) as catname,sum(client) as client,sum(sold) as sold,sum(amount) as amount 
                from (
                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,case ifnull(mode.name,'') when '' then '' else concat('(',mode.name,')') end as trnxtype,
                    sum(stock.iss) as sold,sum(stock.ext) as amount
                    from lastock as stock
                    left join lahead on lahead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.client = lahead.client
                    left join itemcategory as cat on cat.line = item.category
                    left join client as wh on wh.clientid=stock.whid
                    left join mode_masterfile as mode on mode.line = lahead.trnxtype
                    where lahead.doc = 'CI' and  date(lahead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client,mode.name
                    union all 

                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,'RETURN ITEM' as trnxtype,
                    sum(stock.qty)*-1 as sold,sum(stock.ext)*-1 as amount
                    from lastock as stock
                    left join lahead on lahead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.client = lahead.client
                    left join itemcategory as cat on cat.line = item.category
                    left join client as wh on wh.clientid=stock.whid
                    where lahead.doc = 'CM'  and  date(lahead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client
                ) as x 
                group by catname,trnxtype";
                break;
            case 'all': // all
                $query = "
                select concat(catname,trnxtype) as catname,sum(client) as client,sum(sold) as sold,sum(amount) as amount 
                from (
                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,case ifnull(mode.name,'') when '' then '' else concat('(',mode.name,')') end as trnxtype,
                    sum(stock.iss) as sold,sum(stock.ext) as amount
                    from glstock as stock
                    left join glhead on glhead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.clientid = glhead.clientid
                    left join itemcategory as cat on cat.line = item.category
                    left join client as wh on wh.clientid=stock.whid
                    left join mode_masterfile as mode on mode.line = glhead.trnxtype
                    where glhead.doc = 'CI'  and  date(glhead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client,mode.name
                    union all
                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,'RETURN ITEM' as trnxtype,
                    sum(stock.qty)*-1 as sold,sum(stock.ext)*-1 as amount
                    from glstock as stock
                    left join glhead on glhead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.clientid = glhead.clientid
                    left join itemcategory as cat on cat.line = item.category
                    left join client as wh on wh.clientid=stock.whid
                    where glhead.doc = 'CM' and  date(glhead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client

                    union all 

                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,case ifnull(mode.name,'') when '' then '' else concat('(',mode.name,')') end as trnxtype,
                    sum(stock.iss) as sold,sum(stock.ext) as amount
                    from lastock as stock
                    left join lahead on lahead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.client = lahead.client
                    left join itemcategory as cat on cat.line = item.category
                    left join mode_masterfile as mode on mode.line = lahead.trnxtype
                    left join client as wh on wh.clientid=stock.whid
                    where lahead.doc = 'CI'  and  date(lahead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client,mode.name

                    union all 

                    select ifnull(cat.name,'-') as catname,count(cl.client) as client,'RETURN ITEM' as trnxtype,
                    sum(stock.qty)*-1 as sold,sum(stock.ext)*-1 as amount
                    from lastock as stock
                    left join lahead on lahead.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join client as cl on cl.client = lahead.client
                    left join itemcategory as cat on cat.line = item.category
                    left join client as wh on wh.clientid=stock.whid
                    where lahead.doc = 'CM'  and  date(lahead.dateid) between '$start' and '$end' $filter
                    group by item.barcode,cat.name,cl.client
                ) as x 
                group by catname,trnxtype";
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
        $layoutsize = '800';
        $totalcost = 0;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);
        // $txt = '', $w = null, $h = null, $bg = false,  $b = false, $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '', $isamount = 0, $colspan = 0
        $str .= $this->reporter->begintable($layoutsize);
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->catname, '520', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->client, '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->sold, 0), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $totalcost += $data->amount;
            }
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL: ', '700', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalcost, $decimal), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
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
        $layoutsize = '800';

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
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('WH: ' . ($whname == '' ? 'ALL' : $whname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
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
        $layoutsize = '800';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CATEGORY', '520', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER#', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QUANTITY#', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class