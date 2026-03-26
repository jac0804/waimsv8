<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class summary_vendor_report
{
    public $modulename = 'Summary Vendor For Sir Jon';
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
        $fields = ['radioprint', 'start', 'end', 'dbranchname', 'station_rep', 'ddeptname', 'dclientname', 'channel'];
        //, 'radioreportitemstatus', 'station_rep'
        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'dbranchname.required', true);
        data_set($col1, 'station_rep.addedparams', ['branch']);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'channel.lookupclass', 'repchannellookup');
        data_set($col1, 'dclientname.lookupclass', 'wasupplier');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
            'default' as print,
            adddate(left(now(),10),-360) as start,   
            left(now(),10) as end ,
            '(0,1)' as itemstatus,
            '0' as reporttype,

            '' as dbranchname,
            0 as  branchid,
            '' as branchcode,
            '' as branchname,

            '' as station_rep,
            '' as stationline,
            0 as stationid,
            '' as stationname,

            0 as deptid,
            '' as ddeptname, 
            '' as dept,
            '' as deptname,

            '' as client,
            0 as clientid,
            '' as clientname,
            '' as dclientname,

            '' as channel");
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
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        $branchname = $config['params']['dataparams']['branchname'];
        $stationname = $config['params']['dataparams']['stationname'];
        $deptname = $config['params']['dataparams']['ddeptname'];
        $channel = $config['params']['dataparams']['channel'];
        $client = $config['params']['dataparams']['client'];

        $filter = '';
        if ($client != '') {
            $clientid = $config['params']['dataparams']['clientid'];
            $filter .= " and supp.clientid=" . $clientid;
        }
        if ($deptname != '') {
            $deptid = $config['params']['dataparams']['deptid'];
            $filter .= " and head.deptid=" . $deptid;
        }
        if ($branchname != '') {
            $branchid = $config['params']['dataparams']['branchid'];
            $filter .= " and head.branch=" . $branchid;
        }
        if ($stationname != '') {
            $stationid = $config['params']['dataparams']['stationline'];
            $filter .= " and num.station=" . $stationid;
        }
        if ($channel != '') {
            $filter .= " and info.channel='" . $channel . "'";
        }

        $query = "select supcode, supname, sum(tlsrp) as tlsrp, ifnull(sum(netpay), 0) as netpay, ifnull(sum(gm), 0) as gm
        from (
            select supp.client as supcode, supp.clientname as supname, sum(stock.ext) as tlsrp, 
            sum(sinfo.comap + sinfo.cardcharge + sinfo.comap2) as netpay, sum(sinfo.comap / stock.ext) as gm
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client as supp on supp.clientid = item.supplier
            left join cntnum as num on num.trno = head.trno
            left join stockinfo as sinfo on sinfo.trno = head.trno and sinfo.line = stock.line
            where date(head.dateid) between '" . $start . "' and '" . $end . "' and item.isinactive in " . $itemstatus . " and item.supplier <> 0 " . $filter . "
            group by supp.client , supp.clientname

            UNION ALL

            select supp.client as supcode, supp.clientname as supname, sum(stock.ext) as tlsrp, 
            sum(sinfo.comap + sinfo.cardcharge + sinfo.comap2) as netpay, sum(sinfo.comap / stock.ext) as gm
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client as supp on supp.clientid = item.supplier
            left join cntnum as num on num.trno = head.trno
            left join hstockinfo as sinfo on sinfo.trno = head.trno and sinfo.line = stock.line
            where date(head.dateid) between '" . $start . "' and '" . $end . "' and item.isinactive in " . $itemstatus . " and item.supplier <> 0 " . $filter . "
            group by supp.client , supp.clientname) as t
        group by supcode, supname
        order by supname;";

        // var_dump($query);

        return $this->coreFunctions->opentable($query);
    }

    private function defaultHeader($config, $currpage, $totalpages)
    {
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start = date("F d, Y", strtotime($config['params']['dataparams']['start']));
        $end = date("F d, Y", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARY VENDOR REPORT', '200', null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From: ' . '<u>' . $start . '</u>' . ' to ' . '<u>' . $end . '</u>', null, null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '') . '<br />';
        $str .= $this->reporter->col('Page ' . $currpage . ' of ' . $totalpages, null, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    private function defaultTableCols($layoutSize, $border, $font, $font_size)
    {
        $str = '';
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutSize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER CODE', '140', null, false, $border, 'TB', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('SUPPLIER NAME', '300', null, false, $border, 'TB', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL SRP', '120', null, false, $border, 'TB', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('NET PAYABLE', '120', null, false, $border, 'TB', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('GM', '120', null, false, $border, 'TB', 'CT', $font, $font_size, 'B', '', '');
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

        $totaltotaltlsrp = 0;
        $totaltotalnetpay = 0;
        $totaltotalgm = 0;

        $i = 1;
        foreach ($result as $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->supcode, '140', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->supname, '300', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->tlsrp != 0) ? number_format($data->tlsrp, 2) : '-', '120', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->netpay != 0) ? number_format($data->netpay, 2) : '-', '120', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->gm != 0) ? number_format($data->gm, 2) : '-', '120', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            $totaltotaltlsrp += $data->tlsrp;
            $totaltotalnetpay += $data->netpay;
            $totaltotalgm += $data->gm;

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
        $str .= $this->reporter->col('', '300', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, 'B', '', '4px');
        $str .= $this->reporter->col('TOTAL: ', '140', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($totaltotaltlsrp != 0) ? number_format($totaltotaltlsrp, 2) : '-', '120', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($totaltotalnetpay != 0) ? number_format($totaltotalnetpay, 2) : '-', '120', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($totaltotalgm != 0) ? number_format($totaltotalgm, 2) : '-', '120', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class