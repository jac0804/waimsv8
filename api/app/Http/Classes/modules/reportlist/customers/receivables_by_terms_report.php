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

class receivables_by_terms_report
{
    public $modulename = 'Receivables By Terms Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = array('orientation' => 'P', 'format' => 'Letter', 'layoutSize' => '1000');

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
        $fields = array('radioprint', 'asofdate', 'dclientname', 'terms');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'asofdate.readonly', false);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'terms.type', 'lookup');
        data_set($col1, 'terms.action', 'lookupterms');
        data_set($col1, 'terms.lookupclass', 'ledgerterms');
        data_set($col1, 'terms.label', 'Terms');


        $fields = array('radioreporttype', 'print');
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreporttype.label', 'Format ');
        data_set($col2, 'radioreporttype.options', [
            ['label' => 'Summary', 'value' => '0', 'color' => 'red'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'red']
        ]);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $currentDate = $this->othersClass->getCurrentDate();
        return $this->coreFunctions->opentable("select 
    'default' as print,
    ' " . $currentDate . " ' as asofdate,
    '' as client,
    '' as clientname,
    '' as dclientname, 
    '0' as clientid,
    '0' as reporttype,
    '' as terms

    ");
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return array('status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams);
    }

    public function getloaddata($config)
    {
        return array();
    }

    public function reportplotting($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];

        if ($reporttype == 1) {
            $data = $this->data_query_detail($config);
            return $this->reportDetailLayout($config, $data);
        }

        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }


    public function data_query($config)
    {
        $client   = $config['params']['dataparams']['client'];
        $clientid = $config['params']['dataparams']['clientid'];
        $asof     = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $terms    = isset($config['params']['dataparams']['terms']) ? $config['params']['dataparams']['terms'] : '';

        $filter = "";
        if ($client != "") {
            $filter .= " and cl.clientid='$clientid'";
        }
        if ($terms != "") {
            $filter .= " and head.terms='$terms'";
        }

        $query = "
        select
        coalesce(t.terms, 'N/A') as terms,
        t.days,
        count(distinct cl.clientid) as customer_count,
        count(distinct ar.trno) as invoice_count,
        sum(ar.bal) as outstanding_ar,
        case
        when t.days = 0 then 'COD discipline'
        when t.days >= 60 then 'High exposure / monitor credit approvals'
        else 'Standard terms'
        end as risk_note
        from arledger as ar
        left join glhead as head on head.trno = ar.trno
        left join client as cl on cl.clientid = ar.clientid
        left join coa on coa.acnoid = ar.acnoid
        left join terms as t on t.terms = head.terms
        where left(coa.alias, 2) = 'AR'
        and ar.bal <> 0
        and head.dateid <= '" . $asof . "'
        " . $filter . "
        group by t.terms, t.days
        order by t.days
        ";

        return $this->coreFunctions->opentable($query);
    }

    public function data_query_detail($config)
    {
        $client   = $config['params']['dataparams']['client'];
        $clientid = $config['params']['dataparams']['clientid'];
        $asof     = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $terms    = isset($config['params']['dataparams']['terms']) ? $config['params']['dataparams']['terms'] : '';

        $filter = "";
        if ($client != "") {
            $filter .= " and cl.clientid='$clientid'";
        }
        if ($terms != "") {
            $filter .= " and head.terms='$terms'";
        }

        $query = "
        select CONCAT(LEFT(ar.docno, 3), RIGHT(ar.docno, 5)) AS docno,
        CONCAT(LEFT(cl.client, 3), RIGHT(cl.client, 5)) AS client,
        cl.clientname, cl.addr, agent.clientname as agent, head.terms,
        date(head.dateid) as invdate, date(head.due) as due,
        ar.bal, ar.db, ar.ref, 0 as short, 0 as unapplied
        from arledger as ar
        left join glhead as head on head.trno = ar.trno
        left join client as cl on cl.clientid = ar.clientid
        left join client as agent on agent.clientid = ar.agentid
        left join coa on coa.acnoid = ar.acnoid
        where left(coa.alias, 2) = 'AR'
            and ar.bal <> 0
            and head.dateid <= '" . $asof . "'
            " . $filter . "
        order by ar.dateid
        ";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $asof = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $format = ($reporttype == 1) ? 'Detailed' : 'Summary';
        $clientname = isset($config['params']['dataparams']['clientname']) ? $config['params']['dataparams']['clientname'] : '';
        $customer = $clientname != '' ? $clientname : 'All Customers';
        $terms = isset($config['params']['dataparams']['terms']) ? $config['params']['dataparams']['terms'] : '';
        $termsdisplay = $terms != '' ? $terms : 'All Terms';

        $str = '';
        $layoutsize = '1010';
        $font = 'Arial';
        $fontsize = "14";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Receivables By Terms Report', null, null, false, '', '', 'C', $font, '15', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Groups outstanding AR by customer payment terms. Totals should reconcile to Report 1 and the AR control account.', null, null, false, '', '', 'C', $font, '9', 'I');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As Of Date : ', '100', null, false, '', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($asof, '300', null, false, '', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('', '240', null, false, '', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('Format : ', '70', null, false, '', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($format, null, null, false, '', '', 'L', $font, '10', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer : ', '100', null, false, '', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($customer, '300', null, false, '', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('', '240', null, false, '', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('Terms : ', '70', null, false, '', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($termsdisplay, null, null, false, '', '', 'L', $font, '10', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function head($config)
    {

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];


        $str = '';
        $layoutsize = '1010';
        $font = 'Arial';
        $fontsize = "10";
        $border = '1px solid ';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Terms Code', '80', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Terms Description', '120', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Terms Days', '80', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Invoice Count', '80', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Customer Count', '80', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Outstanding AR', '100', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('% of Total AR', '80', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '10', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Risk/ Management Note', '270', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Short Payments', '110', '20', false, $border, 'BT', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    public function reportDefaultLayout($config, $data)
    {
        $layoutsize = '1010';
        $font = 'Arial';
        $fontsize = "10";
        $border  = "1px solid ";
        $companyid = $config['params']['companyid'];
        $bc = "rgb(175, 170, 170)";

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        // first pass: get grand totals for percentage calc and footer row
        $grandTotal = 0;
        $grandInvoiceCount = 0;
        $grandCustomerCount = 0;
        foreach ($data as $row) {
            $grandTotal += $row->outstanding_ar;
            $grandInvoiceCount += $row->invoice_count;
            $grandCustomerCount += $row->customer_count;
        }


        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->displayHeader($config);
        $str .= $this->head($config);

        $str .= $this->reporter->begintable($layoutsize);

        // second pass: print each row
        foreach ($data as $row) {
            $pctOfTotal = ($grandTotal == 0) ? 0 : ($row->outstanding_ar / $grandTotal) * 100;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->terms, '80', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col($row->terms, '120', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col($row->days, '80', '', false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col($row->invoice_count, '80', '', false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col($row->customer_count, '80', '', false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col(number_format($row->outstanding_ar, 2), '100', '', false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col(number_format($pctOfTotal, 1) . '%', '80', '', false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col('', '10', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col($row->risk_note, '270', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->col('', '110', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
            $str .= $this->reporter->endrow();
        }

        // grand total footer row
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', '', false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col('Total', '120', '', false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col('', '80', '', false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col($grandInvoiceCount, '80', '', false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col($grandCustomerCount, '80', '', false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col(number_format($grandTotal, 2), '100', '', false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col('100.0%', '80', '', false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col('', '10', '', false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col('Should reconcile to AR reports', '270', '', false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->col('', '110', '', false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    public function displayHeaderDetail($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $asof = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $format = ($reporttype == 1) ? 'Detailed' : 'Summary';
        $clientname = isset($config['params']['dataparams']['clientname']) ? $config['params']['dataparams']['clientname'] : '';
        $customer = $clientname != '' ? $clientname : 'All Customers';
        $terms = isset($config['params']['dataparams']['terms']) ? $config['params']['dataparams']['terms'] : '';
        $termsdisplay = $terms != '' ? $terms : 'All Terms';

        $str = '';
        $layoutsize = '1250';
        $font = 'TAHOMA';
        $fontsize = "10";
        $fontsize2 = "9";
        $border = '1px solid ';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Receivables By Terms Report - Detailed', null, null, false, '', '', 'C', $font, '16', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As-of Date : ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($asof, '300', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '480', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('Format : ', '70', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($format, null, null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer : ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($customer, '300', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '480', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('Terms : ', '70', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($termsdisplay, null, null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '20', false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->pagenumber('Page', null, null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', 70, '20', false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //columns
        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document No.', 80, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Customer Code', 80, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Customer Name', 150, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Location', 100, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Sales Agent', 100, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Terms', 60, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Invoice Date', 80, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Due Date', 80, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Outstanding AR', 80, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Days Overdue', 80, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Aging Bucket', 100, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Action/Exception', 100, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Short Payment', 80, '20', false, $border, 'TB', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDetailLayout($config, $result)
    {
        $asof = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $layoutsize = '1250';
        $font = 'TAHOMA';
        $fontsize2 = "8";
        $border = '1px solid ';
        $this->reporter->linecounter = 0;
        $count = 30;
        $str = '';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:100px');
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->displayHeaderDetail($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->begintable();
        foreach ($result as $key => $data) {
            $daysOverdue = isset($data->due) && $data->due != '' ? (strtotime($asof) - strtotime($data->due)) / 86400 : '';

            if ($data->bal <= 0) {
                $agingBucket = 'Settled';
            } else if ($daysOverdue <= 30) {
                $agingBucket = '1-30 days';
            } else if ($daysOverdue <= 60) {
                $agingBucket = '31-60 days';
            } else if ($daysOverdue <= 90) {
                $agingBucket = '61-90 days';
            } else {
                $agingBucket = '91+ days';
            }

            $exception = '';
            if ($data->bal == 0) {
                $exception = 'No action';
            } else if ($data->short > 0) {
                $exception = 'Resolve Short Payment';
            } else if ($data->unapplied > 0) {
                $exception = 'Match unapplied receipt';
            } else if ($data->bal && ($daysOverdue > 0)) {
                $exception = 'Collection follow-up';
            }

            $rowcols = [$data->docno, $data->clientname, $data->addr];
            $rowlens = [8, 28, 20];
            $next = $this->countline($rowcols, $rowlens, false);

            if (($this->reporter->linecounter + $next) > ($count + 1)) {
                $str .= $this->reporter->endtable();
                $this->reporter->linecounter = 0;
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeaderDetail($config);
                $str .= $this->reporter->begintable();
            }

            $this->countline($rowcols, $rowlens);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->docno, 80, '20', false, $border, '', 'L', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->client, 80, '20', false, $border, '', 'L', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->clientname, 150, '20', false, $border, '', 'L', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->addr, 100, '20', false, $border, '', 'L', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->agent, 100, '20', false, $border, '', 'L', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->terms, 60, '20', false, $border, '', 'L', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->invdate, 80, '20', false, $border, '', 'R', $font, $fontsize2, '');
            $str .= $this->reporter->col($data->due, 80, '20', false, $border, '', 'R', $font, $fontsize2, '');
            $str .= $this->reporter->col(isset($data->bal) && $data->bal <> 0 ? number_format($data->bal, 2) : '-', 80, '20', false, $border, '', 'R', $font, $fontsize2, '');
            $str .= $this->reporter->col($daysOverdue, 80, '20', false, $border, '', 'R', $font, $fontsize2, '');
            $str .= $this->reporter->col($agingBucket, 100, '20', false, $border, '', 'C', $font, $fontsize2, '');
            $str .= $this->reporter->col($exception, 100, '20', false, $border, '', 'L', $font, $fontsize2, '');
            $str .= $this->reporter->col('', 80, '20', false, $border, '', 'R', $font, $fontsize2, '');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();

        return $str;
    }

    function countline($col = [], $len = [], $commit = true)
    {
        if (!empty($col)) {
            $arr = [];
            foreach ($col as $key => $txt) {
                $collen = isset($len[$key]) ? $len[$key] : 0;
                if ($collen > 0) {
                    array_push($arr, $this->reporter->fixcolumn([$txt], $collen, 0));
                }
            }
            $lines = $this->othersClass->getmaxcolumn($arr);
            if ($commit) {
                $this->reporter->linecounter = $this->reporter->linecounter + $lines;
            }
            return $lines;
        } else {
            if ($commit) {
                $this->reporter->linecounter++;
            }
            return 1;
        }
    }
}//end class