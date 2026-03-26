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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class reseller_status_summary_report
{
    public $modulename = 'Reseller Status Summary Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
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
        $fields = ['radioprint', 'start', 'end', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Reseller Summary', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Detail Per Customer', 'value' => '1', 'color' => 'orange']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        date(now()) as end, 
        '" . $center . "' as center,
        '" . $dcenter[0]->dcentername . "' as dcentername,
        '" . $dcenter[0]->name . "' as centername,
        '' as prefix,
        '0' as reporttype
      ";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0':
                return $this->reseller_summary_layout($config);
                break;
            case '1':
                return $this->detail_per_customer_layout($config);
                break;
        }
    }

    public function reseller_summary_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date("m-d-Y", strtotime($config['params']['dataparams']['start']));
        $end = date("m-d-Y", strtotime($config['params']['dataparams']['end']));
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        // $clientname = ($config['params']['dataparams']['clientname']);
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        // $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($headerdata[0]->tel, null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        if ($reporttype == '0') {
            $str .= $this->reporter->col('RESELLER STATUS SUMMARY REPORT', null, null, false, '3px solid', '', 'C', $font, '16', 'B', '', '');
        } else {
            $str .= $this->reporter->col('RESELLER STATUS SUMMARY REPORT<br>DETAIL PER CUSTOMER', null, null, false, '3px solid', '', 'C', $font, '16', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date: ' . $start . ' to ' . $end, null, null, false, '1px dotted', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= '<br></br>';

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NO.', '50', null, false, '1px dotted', 'BTL', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('RESELLER/ BRANCH/ CENTER', '350', null, false, '1px dotted', 'BTL', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('SALES', '200', null, false, '1px dotted', 'BTL', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('RECEIVABLES', '200', null, false, '1px dotted', 'BTL', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('COLLECTION', '200', null, false, '1px dotted', 'BTLR', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reseller_summary_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "12";
        $this->reporter->linecounter = 0;

        $salesResult      = $this->detail_customer_summary_qry($config);
        $allCollections   = $this->detail_customer_collection_qry($config);

        if (empty($salesResult)) {
            return $this->othersClass->emptydata($config);
        }

        // Build collection map keyed by sales_trno
        $collectionMap = array();
        foreach ($allCollections as $col) {
            if ((float)$col->collection != 0) {
                $collectionMap[$col->sales_trno][] = $col;
            }
        }

        // Aggregate per reseller
        $summary = array();
        foreach ($salesResult as $row) {
            $key = $row->center . '|' . $row->reseller;
            if (!isset($summary[$key])) {
                $summary[$key] = array(
                    'center'      => $row->center,
                    'reseller'    => $row->reseller,
                    'sales'       => 0,
                    'receivables' => 0,
                    'collection'  => 0,
                );
            }
            $summary[$key]['sales']       += $row->sales;
            $summary[$key]['receivables'] += $row->receivables;

            if (isset($collectionMap[$row->trno])) {
                foreach ($collectionMap[$row->trno] as $col) {
                    $summary[$key]['collection'] += abs((float)$col->collection);
                }
            }
        }

        usort($summary, function ($a, $b) {
            return strcmp($a['center'], $b['center']);
        });

        $result = array();
        foreach ($summary as $row) {
            $result[] = (object) $row;
        }

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reseller_summary_header($config);

        $count           = count($result);
        $salesTotal      = 0;
        $receivableTotal = 0;
        $collectionTotal = 0;
        $no              = 1;

        for ($i = 0; $i < $count; $i++) {
            $data = $result[$i];

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($no, '50', null, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->reseller, '350', null, false, '1px dotted', 'LB', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->sales       != 0 ? number_format($data->sales,       2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->receivables != 0 ? number_format($data->receivables, 2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->collection  != 0 ? number_format($data->collection,  2) : '-', '200', null, false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $no++;
            $salesTotal      += $data->sales;
            $receivableTotal += $data->receivables;
            $collectionTotal += $data->collection;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '25', false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '350', '25', false, '1px dotted', 'LB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', '25', false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', '25', false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', '25', false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('TOTAL', '350', null, false, '1px dotted', 'LB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($salesTotal      != 0 ? number_format($salesTotal,      2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($receivableTotal != 0 ? number_format($receivableTotal, 2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($collectionTotal != 0 ? number_format($collectionTotal, 2) : '-', '200', null, false, '1px dotted', 'LBR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '25', false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '350', '25', false, '1px dotted', 'LB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', '25', false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', '25', false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', '25', false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function detail_customer_summary_qry($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "select c.center, center.name as reseller, ifnull(client.clientname, '') as customer,
        head.trno, ifnull(ar.bal, 0) as receivables, sum(stock.ext) as sales
        from glhead head
        left join client on client.clientid = head.clientid
        left join cntnum as c on c.trno = head.trno
        left join center on center.code = c.center
        left join glstock stock on stock.trno = head.trno
        left join arledger as ar on ar.trno = head.trno
        where head.doc = 'SJ'
        and date(head.dateid) between '$start' and '$end'
        group by c.center, center.name, client.clientname, head.trno, ar.bal
        having sum(stock.ext) > 0
        union all
        select c.center, center.name as reseller, ifnull(client.clientname, '') as customer,
        head.trno, ifnull(ar.bal, 0) as receivables, sum(stock.ext) as sales
        from lahead head
        left join client on client.client = head.client
        left join cntnum as c on c.trno = head.trno
        left join center on center.code = c.center
        left join lastock as stock on stock.trno = head.trno
        left join arledger as ar on ar.trno = head.trno
        where date(head.dateid) between '$start' and '$end'
        group by c.center, center.name, client.clientname, head.trno, ar.bal
        having sum(stock.ext) > 0
        order by center, customer, trno";

        return $this->coreFunctions->opentable($query);
    }

    public function detail_customer_collection_qry($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "select detail.refx as sales_trno, ifnull(client.clientname, '') as customer, detail.cr as collection
        from glhead head
        left join gldetail as detail on detail.trno  = head.trno
        left join coa on coa.acnoid = detail.acnoid
        left join client on client.clientid = head.clientid
        left join cntnum as c on c.trno = head.trno
        where left(coa.alias, 2) = 'AR'
        and detail.refx is not null
        and detail.refx <> 0
        and date(head.dateid) between '$start' and '$end'
        union all
        select detail.refx as sales_trno,
        ifnull(client.clientname, '') as customer,
        detail.cr as collection
        from lahead head
        left join ladetail as detail ON detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join client on client.client = head.client
        left join cntnum as c on c.trno = head.trno
        where left(coa.alias, 2) = 'AR'
        and detail.refx is not null
        and detail.refx <> 0
        and date(head.dateid) between '$start' and '$end'
        order by customer, sales_trno";

        return $this->coreFunctions->opentable($query);
    }


    public function detail_per_customer_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = 'Roboto';
        $fontsize = "12";
        $this->reporter->linecounter = 0;

        $result         = $this->detail_customer_summary_qry($config);
        $allCollections = $this->detail_customer_collection_qry($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $collectionMap = array();
        foreach ($allCollections as $col) {
            if ((float)$col->collection != 0) {
                $collectionMap[$col->sales_trno][] = $col;
            }
        }

        $summary = array();
        foreach ($result as $row) {
            $key = $row->center . '|' . $row->reseller . '|' . $row->customer;
            if (!isset($summary[$key])) {
                $summary[$key] = array(
                    'center'      => $row->center,
                    'reseller'    => $row->reseller,
                    'customer'    => $row->customer,
                    'sales'       => 0,
                    'receivables' => 0,
                    'collection'  => 0,
                );
            }
            $summary[$key]['sales']       += $row->sales;
            $summary[$key]['receivables'] += $row->receivables;

            if (isset($collectionMap[$row->trno])) {
                foreach ($collectionMap[$row->trno] as $col) {
                    $summary[$key]['collection'] += abs((float)$col->collection);
                }
            }
        }

        $combine = array();
        foreach ($summary as $row) {
            $combine[] = (object) $row;
        }

        $result = $combine;
        $count  = count($result);

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reseller_summary_header($config);

        $currentReseller  = '';
        $resellerNo       = 1;
        $customerNo       = 1;

        $subtotalSales      = 0;
        $subtotalReceivable = 0;
        $subtotalCollection = 0;

        $grandSales      = 0;
        $grandReceivable = 0;
        $grandCollection = 0;

        for ($i = 0; $i < $count; $i++) {
            $data = $result[$i];

            // kapag nagbago reseller
            if ($currentReseller !== $data->reseller) {

                if ($currentReseller !== '') {

                    // Spacer before subtotal
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50',  25, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '350', 25, false, '1px dotted', 'LB',  'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50',  null, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('SUB TOTAL :', '350', null, false, '1px dotted', 'LB',  'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col($subtotalSales      != 0 ? number_format($subtotalSales,      2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col($subtotalReceivable != 0 ? number_format($subtotalReceivable, 2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col($subtotalCollection != 0 ? number_format($subtotalCollection, 2) : '-', '200', null, false, '1px dotted', 'LBR', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    // Spacer after subtotal
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50',  25, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '350', 25, false, '1px dotted', 'LB',  'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $subtotalSales      = 0;
                    $subtotalReceivable = 0;
                    $subtotalCollection = 0;
                    $customerNo         = 1;
                }

                // Reseller header
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($resellerNo, '50',  25, false, '1px dotted', 'LBR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data->reseller, '350', 25, false, '1px dotted', 'LB',  'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'B',  'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'B',  'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'BR', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $currentReseller = $data->reseller;
                $resellerNo++;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($resellerNo - 1 . '.' . $customerNo, '50',  null, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->customer, '350', null, false, '1px dotted', 'LB',  'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->sales       != 0 ? number_format($data->sales,       2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->receivables != 0 ? number_format($data->receivables, 2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->collection  != 0 ? number_format($data->collection,  2) : '-', '200', null, false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $subtotalSales      += $data->sales;
            $subtotalReceivable += $data->receivables;
            $subtotalCollection += $data->collection;

            $grandSales      += $data->sales;
            $grandReceivable += $data->receivables;
            $grandCollection += $data->collection;

            $customerNo++;

            // para sa last row ng reseller
            if ($i === $count - 1) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50',  25, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '350', 25, false, '1px dotted', 'LB',  'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50',  null, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('SUB TOTAL :', '350', null, false, '1px dotted', 'LB',  'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subtotalSales      != 0 ? number_format($subtotalSales,      2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subtotalReceivable != 0 ? number_format($subtotalReceivable, 2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subtotalCollection != 0 ? number_format($subtotalCollection, 2) : '-', '200', null, false, '1px dotted', 'LBR', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }

        // Grand Total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50',  25, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '350', 25, false, '1px dotted', 'LB',  'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50',  null, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '',  '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '350', null, false, '1px dotted', 'LB',  'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandSales      != 0 ? number_format($grandSales,      2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandReceivable != 0 ? number_format($grandReceivable, 2) : '-', '200', null, false, '1px dotted', 'LB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandCollection != 0 ? number_format($grandCollection, 2) : '-', '200', null, false, '1px dotted', 'LBR', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50',  25, false, '1px dotted', 'LBR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '350', 25, false, '1px dotted', 'LB',  'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LB',  'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', 25, false, '1px dotted', 'LBR', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class
