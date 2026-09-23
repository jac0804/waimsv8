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
use Illuminate\Support\Facades\URL;

class sales_markup_customer_difference_report
{
    public $modulename = 'Sales Markup Customer Difference Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1500px;max-width:1500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1500'];

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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'ditemname', 'agentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.type', 'date');
        data_set($col1, 'end.type', 'date');
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'ditemname.label', 'Item');
        data_set($col1, 'agentname.label', 'Sales Agent');

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
        left(now(),10) as end,
        '' as client,
        '' as clientname,
        '' as dclientname,
        0 as itemid,
        '' as itemname,
        '' as agent,
        '' as agentid,
        '' as agentname

     ");
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportplotting($config)
    {
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid   = $config['params']['companyid'];
        $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $agentname   = $config['params']['dataparams']['agentname'];
        $clientname   = $config['params']['dataparams']['clientname'];
        $itemname   = $config['params']['dataparams']['itemname'];
        $itemid   = $config['params']['dataparams']['itemid'];
        $agentid     = $config['params']['dataparams']['agentid'];

        $filter = '';

        if ($clientname != "") {
            $filter .= " and client.clientname = '$clientname'";
        }

        if ($agentname != "") {
            $filter .= " and agent.clientid = '$agentid'";
        }

        if ($itemname != "") {
            $filter .= "and stock.itemid = '$itemid'";
        }

        $query = "select concat(left(head.docno,3),'-',right(head.docno,3)) as inv,concat(cntnum.bref,'-',cntnum.seq) as invoiceno,
            concat(left(client.client,3),'-',right(client.client,3)) as customer_id,head.clientname,agent.clientname as agent,
            concat(left(item.barcode,3),'-',right(item.barcode,3)) as item_code,item.itemname,stock.isqty,stock.ext as netsales,
            (stock.iss*stock.cost) as totalcost,ifnull(stock.consignpr - stock.custdisc, 0) as actual_markup,
            item.markup as standard_markup,ifnull(stock.ext - (stock.consignpr - stock.custdisc),0) as difference, 0 as exeption_flag
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid = head.agentid
            left join item on item.itemid = stock.itemid
            left join cntnum as cntnum on cntnum.trno=head.trno
            where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' $filter
            union all
            select concat(left(head.docno,3),'-',right(head.docno,3)) as inv,concat(cntnum.bref,'-',cntnum.seq) as invoiceno,
            concat(left(client.client,3),'-',right(client.client,3)) as customer_id,head.clientname,agent.clientname as agent,
            concat(left(item.barcode,3),'-',right(item.barcode,3)) as item_code,item.itemname,stock.isqty as qty ,stock.ext as netsales,
            (stock.iss*stock.cost) as totalcost,ifnull(stock.consignpr - stock.custdisc, 0) as actual_markup,
            item.markup as standard_markup,ifnull(stock.ext - (stock.consignpr - stock.custdisc),0) as difference, 0 as exeption_flag
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client = head.agent
            left join item on item.itemid = stock.itemid
            left join cntnum as cntnum on cntnum.trno=head.trno
            where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' $filter 
            order by clientname";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $start      = date("F j, Y", strtotime($config['params']['dataparams']['start']));
        $end        = date("F j, Y", strtotime($config['params']['dataparams']['end']));
        $clientname = $config['params']['dataparams']['clientname'];
        $itemname   = $config['params']['dataparams']['itemname'];
        $agentname   = $config['params']['dataparams']['agentname'];
        $printDate  = date("l, F j, Y");
        $printTime  = date("g:i:s A");

        $str = '';
        $layoutsize = '1500';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, $layoutsize, '30', false, '', '', 'C', $font, '16', 'B', '', '#FFFFFF');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer :', '80', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($clientname == '' ? 'ALL CUSTOMERS' : strtoupper($clientname), '370', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('Sales Agent :', '80', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($agentname == '' ? 'ALL AGENTS' : strtoupper($agentname), '300', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('Item :', '70', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($itemname == '' ? 'ALL ITEMS' : strtoupper($itemname), '300', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range:', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($start . ' to ' . $end, '400', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->pagenumber('Page', '', null, false, '', '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Invoice No.', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Customer ID', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Customer Name', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Sales Agent', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Item Code',  '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Item Description', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Qty', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Net Sales', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Total Cost', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Actual Markup %', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Standard Markup %', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Difference',  '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->col('Exception Flag', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '#FFFFFF');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '1500';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $limitPerPage = 17;
        $rowCount = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:5px');
        $str .= $this->displayHeader($config, count($result));

        $str .= $this->reporter->begintable($layoutsize);

        foreach ($result as $data) {

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, count($result));
                $str .= $this->reporter->begintable($layoutsize);
            }

            // Skip rows with no meaningful data 
            if (trim($data->itemname) == '' && trim($data->clientname) == '') {
                continue;
            }

            $exception = ($data->exeption_flag == 1) ? 'EXCEPTION' : '-';

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col((empty($data->invoiceno) ? '-' : $data->invoiceno), '70',  null, false, $border, '', 'CT', $font, $fontsize);
            $str .= $this->reporter->col($data->customer_id, '70',  null, false, $border, '', 'CT', $font, $fontsize);
            $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col($data->agent, '140', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col($data->item_code, '70',  null, false, $border, '', 'CT', $font, $fontsize);
            $str .= $this->reporter->col($data->itemname, '140', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col((empty($data->isqty) ? '-' : number_format($data->isqty, 0)), '50', null, false, $border, '', 'CT', $font, $fontsize);
            $str .= $this->reporter->col((empty($data->netsales) ? '-' : number_format($data->netsales, 2)), '80', null, false, $border, '', 'RT', $font, $fontsize);
            $str .= $this->reporter->col((empty($data->totalcost) ? '-' : number_format($data->totalcost, 2)), '80', null, false, $border, '', 'RT', $font, $fontsize);
            $str .= $this->reporter->col((empty($data->actual_markup) ? '-' : number_format($data->actual_markup, 2)), '100', null, false, $border, '', 'RT', $font, $fontsize);
            $str .= $this->reporter->col((empty($data->standard_markup) ? '-' : number_format($data->standard_markup, 2)), '90', null, false, $border, '', 'RT', $font, $fontsize);
            $str .= $this->reporter->col((empty($data->difference) ? '-' : number_format($data->difference, 2)), '70', null, false, $border, '', 'RT', $font, $fontsize);
            $str .= $this->reporter->col($exception, '90', null, false, $border, '', 'C', $font, $fontsize, ($exception == 'EXCEPTION' ? 'LT' : ''));
            $str .= $this->reporter->endrow();

            $rowCount++;
        }
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class