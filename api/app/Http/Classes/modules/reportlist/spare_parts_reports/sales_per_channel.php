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

class sales_per_channel
{
    public $modulename = 'Sales Per Channel';
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

    public function createHeadField()
    {
        $fields = [
            'radioprint',
            'start',
            'end', 'dwhname'
        ];

        $col1 = $this->fieldClass->create($fields);

        $fields = ['radioposttype'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
            ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return [
            'col1' => $col1,
            'col2' => $col2,
            'col3' => $col3
        ];
    }

    public function paramsdata()
    {
        return $this->coreFunctions->opentable("
            select 'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '0' as posttype,
            0 as whid,
            '' as wh,
            '' as whname,
            '' as dwhname
        ");
    }

    public function getloaddata()
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
        $wh         = $config['params']['dataparams']['wh'];

        $filter = '';
        if ($wh != "") {
            $whid = $config['params']['dataparams']['whid'];
            $filter = $filter . " and stock.whid=" . $whid;
        }

        $query = ($posttype == '0') ? "" : (($posttype == '1') ? "" : "");

        switch ($posttype) {
            case '0': // posted
                $query = "select channel,sum(amount) as amount from (select  ifnull(mode.name,'-') as channel, stock.ext as amount
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join mode_masterfile as mode on mode.line = head.trnxtype
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CI' and date(head.dateid) between '$start' and '$end'
                $filter
                union all
                select 'RETURN ITEM' as channel, stock.ext*-1 as amount
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CM'  and date(head.dateid) between '$start' and '$end' $filter
                ) as a
                group by channel
                order by channel;";
                break;
            case '1': // unposted
                $query = "select channel,sum(amount) as amount from (select ifnull(mode.name,'-') as channel, stock.ext as amount
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join mode_masterfile as mode on mode.line = head.trnxtype
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CI'  and date(head.dateid) between '$start' and '$end' $filter
                union all
                select 'RETURN ITEM' as channel, stock.ext*-1 as amount
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CM' and date(head.dateid) between '$start' and '$end' $filter
                ) as a
                group by channel
                order by channel;";
                break;
            default: // all 
                $query = "select channel,sum(amount) as amount from (select ifnull(mode.name,'-') as channel, stock.ext as amount
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join mode_masterfile as mode on mode.line = head.trnxtype
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CI'  and date(head.dateid) between '$start' and '$end' $filter
                union all
                select ifnull(mode.name,'-') as channel, stock.ext as amount
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join mode_masterfile as mode on mode.line = head.trnxtype
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CI'  and date(head.dateid) between '$start' and '$end' $filter
                union all
                select 'RETURN ITEM' as channel, stock.ext*-1 as amount
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CM' and date(head.dateid) between '$start' and '$end' $filter
                union all
                select 'RETURN ITEM' as channel, stock.ext*-1 as amount
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join client as wh on wh.clientid = stock.whid
                where head.doc = 'CM'  and date(head.dateid) between '$start' and '$end' $filter
                ) as a
                group by channel
                order by channel;";
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

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $posttype = ($posttype == '0') ? "POSTED" : (($posttype == '1') ? "UNPOSTED" : "ALL");
        $warehouse = $warehouse == '' ? 'ALL' : $warehouse;

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
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse: ' . $warehouse, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CHANNEL', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
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

        $total = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);

        foreach ($result as $key => $data) {

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->channel, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->amount == 0 ? '-' : number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->header_DEFAULT($config);
                $page += $count;
            } //end if

            $total += $data->amount;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL: ', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($total, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class