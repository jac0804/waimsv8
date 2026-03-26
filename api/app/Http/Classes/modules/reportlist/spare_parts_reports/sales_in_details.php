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

class sales_in_details
{
    public $modulename = 'Sales In Details';
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
            'dclientname',
            'dcentername',
            'dwhname'
        ];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        $fields = [
            'userfilter',
            'radioposttype',
            'radiosorting'
        ];
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

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center, name as centername, concat(code, '~', name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable("
            select 'default' as print, 
            adddate(left(now(), 10), -360) as start, 
            left(now(), 10) as end, 
            '0' as posttype, 
            'ASC' as sorting,
            0 as whid,
            '' as wh,
            '' as whname,
            '' as dwhname,
            0 as clientid, 
            '' as client,
            '' as clientname, 
            '' as dclientname,
            '0' as userfilter,
            '" . $defaultcenter[0]['center'] . "' as center,
            '" . $defaultcenter[0]['centername'] . "' as centername,
            '" . $defaultcenter[0]['dcentername'] . "' as dcentername");
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
        $client    = $config['params']['dataparams']['client'];
        $sorting   = $config['params']['dataparams']['sorting'];
        $center    = $config['params']['dataparams']['center'];
        $wh        = $config['params']['dataparams']['wh'];
        $userfilter = $config['params']['dataparams']['userfilter'];
        $user      = $config['params']['user'];

        $filter = '';
        if ($center != '') {
            $filter .= " and cntnum.center = '$center' ";
        }
        if ($client != '') {
            $clientid = $config['params']['dataparams']['clientid'];
            $filter .= " and client.clientid=" . $clientid;
        }
        if ($wh != '') {
            $whid = $config['params']['dataparams']['whid'];
            $filter .= " and stock.whid=" . $whid;
        }
        if ($userfilter != '1') {
            $filter .= " and head.createby = '$user'";
        }

        $sorting = $sorting == 'ASC' ? 'ASC' : 'DESC';

        switch ($posttype) {
            case '0': // posted
                $query = "select 'p' as tr, head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, sum(stock.iss) as qty, sum(stock.cost) as cost, sum(stock.isamt) as srp, stock.disc, sum(stock.ext) as amount, wh.client, head.createby,head.rem2
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join cntnum on cntnum.trno = head.trno
                left join client on client.clientid = head.clientid
                left join client as wh on wh.clientid = head.whid 
                left join client as ag on ag.clientid = head.agentid
                where head.doc = 'CI' and date(head.dateid) between '$start' and '$end' $filter
                group by head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, stock.disc, wh.client, head.createby,head.rem2
                order by docno $sorting;";
                break;
            case '1': // unposted
                $query = "select 'u' as tr, head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, sum(stock.iss) as qty, sum(stock.cost) as cost, sum(stock.isamt) as srp, stock.disc, sum(stock.ext) as amount, wh.client, head.createby,head.rem2
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join cntnum on cntnum.trno = head.trno
                left join client on client.client = head.client
                left join item on item.itemid = stock.itemid
                left join client as wh on wh.client = head.wh
                left join client as ag on ag.client = head.agent
                where head.doc = 'CI' and date(head.dateid) between '$start' and '$end' $filter
                group by head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, stock.disc, wh.client, head.createby,head.rem2
                order by docno $sorting;";
                break;
            default: // all 
                $query = "select 'u' as tr, head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, sum(stock.iss) as qty, sum(stock.cost) as cost, sum(stock.isamt) as srp, stock.disc, sum(stock.ext) as amount, wh.client, head.createby,head.rem2
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join cntnum on cntnum.trno = head.trno
                left join client on client.client = head.client
                left join item on item.itemid = stock.itemid
                left join client as wh on wh.client = head.wh
                left join client as ag on ag.client = head.agent
                where head.doc = 'CI' and date(head.dateid) between '$start' and '$end' $filter
                group by head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, stock.disc, wh.client, head.createby,head.rem2
                union all
                select 'p' as tr, head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, sum(stock.iss) as qty, sum(stock.cost) as cost, sum(stock.isamt) as srp, stock.disc, sum(stock.ext) as amount, wh.client, head.createby,head.rem2
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join cntnum on cntnum.trno = head.trno
                left join client on client.clientid = head.clientid
                left join client as wh on wh.clientid = head.whid 
                left join client as ag on ag.clientid = head.agentid
                where head.doc = 'CI' and date(head.dateid) between '$start' and '$end' $filter
                group by head.dateid, head.docno, head.clientname, head.ourref, item.partno, item.itemname, stock.disc, wh.client, head.createby,head.rem2
                order by docno $sorting";
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
        $warehouse  = $config['params']['dataparams']['wh'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $userfilter = $config['params']['dataparams']['userfilter'];

        $str = '';
        $layoutsize = '1100';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $posttype = $posttype == '0' ? 'POSTED' : ($posttype == '1' ? 'UNPOSTED' : 'ALL');
        $sorting = $sorting == 'ASC' ? 'Ascending' : 'Descending';
        $wr = $warehouse == '' ? 'ALL' : $warehouse;
        $userfilter = $userfilter != '0' ? 'ALL' : $username;

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
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('User: ' . $userfilter, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse: ' . $wr, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TRANSACTION CODE/CUSTOMER/CSI', '350', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        //$str .= $this->reporter->col('MECHANIC', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PART NUMBER', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PART NAME', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COST', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SRP', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DISCOUNT %', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $count = 38;
        $page = 40;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1100';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $total = 0;
        $totalamt = 0;

        foreach ($result as $key => $data) {

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno . ' / ' . $data->clientname . (!empty($data->ourref) ?  ' / ' . $data->ourref  : ''), '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->partno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->qty, 0), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->cost != 0 ? number_format($data->cost, 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->srp, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->disc != '' ? $data->disc : '-', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->addline();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->header_DEFAULT($config);
                $page += $count;
            } //end if

            $total += $data->amount;
            $totalamt += $data->amount;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL: ', '900', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class