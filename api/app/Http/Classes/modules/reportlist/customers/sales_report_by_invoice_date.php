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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class sales_report_by_invoice_date
{
    public $modulename = 'Sales Report By Invoice Date';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:3000px;max-width:3000px;';
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
        $companyid = $config['params']['companyid'];

       
                $fields = ['radioprint', 'start', 'end', 'dclientname', 'radioposttype'];
                $col1 = $this->fieldClass->create($fields);

                data_set($col1, 'start.required', true);
                data_set($col1, 'end.required', true);
                data_set($col1, 'dclientname.label', 'Customer');
                data_set($col1, 'dclientname.lookupclass', 'rcustomer');

                data_set(
                    $col1,
                    'radioposttype.options',
                    [
                        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                        ['label' => 'All', 'value' => '2', 'color' => 'teal']
                    ]
                );

                $fields = ['print'];
                $col2 = $this->fieldClass->create($fields);
          
          

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as dwhname,
      '' as wh,
      '' as whname,
      '' as dwhref,
      '' as whref,
      '0'  as reporttype,
      '' as whnameref,
      '' as dclientname,
      '0' as clientid,
      '' as client,
      '' as clientname,
      '0' as posttype";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];

        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];
        
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function query_ericco($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype = $config['params']['dataparams']['posttype'];
        $clientid = $config['params']['dataparams']['clientid'];
        $client = $config['params']['dataparams']['client'];
        $filter = "";

        if ($client != '' && $clientid != 0) {
            $filter .= " and client.clientid='$clientid'";
        }

        switch ($posttype) {
            case 0: //Posted
                $query = " 
        select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname as clientname2, client.registername as clientname,
            case when head.amount > 0 then head.amount
            else sum(stock.ext) end as amount,
            head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join hsistock as stock on stock.trno = head.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'  $filter
            group by right(head.docno, 5), head.clientname, head.dateid, address, trno, client.tin, vattype, head.amount,client.registername
            union all
            select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname as clientname2, client.registername as clientname,
            sum(stock.ext) as amount , head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glstock as stock on stock.trno = num2.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
            group by right(head.docno, 5), head.clientname,client.registername, head.dateid, address, trno, client.tin, vattype
            order by docno
        ";
                break;

            case 1: //Unposted
                $query = "
        select
          'Unposted' as status,
          right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
          head.clientname as clientname2, client.registername as clientname,
          case when head.amount > 0 then head.amount
          else sum(stock.ext) end as amount,
          head.address, head.trno, head.vattype
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join sistock as stock on stock.trno = head.trno
          left join client on head.client = client.client
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'   $filter
          group by right(head.docno, 5), head.clientname,client.registername, head.dateid, address, trno, client.tin, vattype, head.amount
          union all
        select
          'Unposted' as status,
          right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
         head.clientname as clientname2, client.registername as clientname,
          sum(stock.ext) as amount, head.address, head.trno, head.vattype
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join cntnum as num2 on num2.svnum = head.trno
          left join glhead as ghead on ghead.trno = num2.trno
          left join glstock as stock on stock.trno = ghead.trno
          left join client on head.client = client.client
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
          group by right(head.docno, 5),head.clientname,client.registername, head.dateid, address, trno, client.tin, vattype
          order by docno
        ";
                break;
            case 2: //All
                $query = "
          select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname as clientname2, client.registername as clientname,
            case when head.amount > 0 then head.amount
            else sum(stock.ext) end as amount,
            head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join hsistock as stock on stock.trno = head.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'  $filter
            group by right(head.docno, 5), head.clientname,client.registername, head.dateid, address, trno, client.tin, vattype, head.amount
            union all
            select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname as clientname2, client.registername as clientname,
            sum(stock.ext) as amount , head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glstock as stock on stock.trno = num2.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
            group by right(head.docno, 5), head.clientname,client.registername, head.dateid, address, trno, client.tin, vattype
            union all
            select
          'Unposted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname as clientname2, client.registername as clientname,
            case when head.amount > 0 then head.amount
            else sum(stock.ext) end as amount,
            head.address, head.trno, head.vattype
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join sistock as stock on stock.trno = head.trno
            left join client on head.client = client.client
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'   $filter
            group by right(head.docno, 5), head.clientname,client.registername, head.dateid, address, trno, client.tin, vattype, head.amount
            union all
            select
          'Unposted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname as clientname2, client.registername as clientname,
            sum(stock.ext) as amount, head.address, head.trno, head.vattype
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glhead as ghead on ghead.trno = num2.trno
            left join glstock as stock on stock.trno = ghead.trno
            left join client on head.client = client.client
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
            group by right(head.docno, 5), head.clientname,client.registername, head.dateid, address, trno, client.tin, vattype
            order by docno
          ";
                break;
        }
        // var_dump($query);

        return $this->coreFunctions->openTable($query);
    }

    public function reportDefault($config)
    {
        $companyid = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];


        $data = $this->query_ericco($config);
             
        return $data;
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $result = $this->layout_ericco($config, $this->query_ericco($config));

        return $result;
    }

    //ERICCO
    public function header_ericco($config)
    {
        $start = date("F Y", strtotime($config['params']['dataparams']['start']));
        $end = date("F Y", strtotime($config['params']['dataparams']['end']));
        $center     = $config['params']['center'];
        $font = "TAHOMA";
        $layoutsize = 1400;
        $fontsize_title = "12";
        $fontsize = "10";
        $border = "1px solid ";
        $str = '';

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUSIDIARY SALES JOURNAL', null, null, false, $border, '', 'L', $font, $fontsize_title, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($start == $end) {
            $str .= $this->reporter->col('FOR THE MONTH OF  ' . $start, null, null, false, $border, '', 'L', $font, $fontsize_title, '', '', '');
        } else {
            $str .= $this->reporter->col('FOR THE MONTHS OF  ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $fontsize_title, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INVOICE DATE', 150, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NAME OF CUSTOMERS', 400, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('INVOICE NO.', 100, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('VAT REG NO.', 120, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ADDRESS', 650, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('INVOICE AMOUNT', 130, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('VAT 12%', 130, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('VATABLE SALES', 130, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function layout_ericco($config, $result)
    {
        $start = date("F Y", strtotime($config['params']['dataparams']['start']));
        $end = date("F Y", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = 1480;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_ericco($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9";
        $border = "1px solid ";
        $totalamount = 0;
        $totalvat = 0;
        $totalvatSales = 0;
        $maxRows = 23;
        $rowCount = 0;
        $months = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        if ($start == $end) {
            $months = $start;
        } else {
            $months = $start . ' to ' . $end;
        }

        //extra space
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 150, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 400, 20, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 100, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 120, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 650, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        foreach ($result as $key => $data) {

            if ($rowCount > $maxRows) {
                $str .= $this->reporter->page_break();
                $str .= "</br></br>";
                $rowCount = 0;
            }
            $vat = $data->amount - ($data->amount / 1.12);

            if ($data->vattype == 'VATABLE') {
                $vat = $data->amount - ($data->amount / 1.12);
            } else {
                $vat = 0;
            }



            if ($data->vattype == 'VATABLE') {
                $vatSales = ($data->amount / 1.12);
            } else {
                $vatSales = $data->amount;
            }


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(isset($data->dateid) ? date('d-M-y', strtotime($data->dateid)) : '', 150, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($data->clientname) ? $data->clientname : '', 400, null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($data->docno) ? $data->docno : '', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($data->tin) ? $data->tin : '', 120, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(isset($data->address) ? $data->address : '', 650, null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format(isset($data->amount) ? $data->amount : 0, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format(isset($vat) ? $vat : 0, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format(isset($vatSales) ? $vatSales : 0, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $rowCount++;
            $totalamount += $data->amount;
            $totalvat += $vat;
            $totalvatSales += $vatSales;
        } //space
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 150, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 400, 20, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 100, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 120, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 650, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 150, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total ' . $months, 400, null, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 120, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 650, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalamount, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalvat, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalvatSales, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 150, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 400, null, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 120, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 650, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class
