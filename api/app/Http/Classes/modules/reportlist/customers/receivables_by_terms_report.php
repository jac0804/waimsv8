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
        $fields = array('radioprint', 'asofdate', 'dclientname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'asofdate.readonly', false);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col1, 'dclientname.label', 'Customer');

        $fields = array('print');
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $currentDate = $this->othersClass->getCurrentDate();
        return $this->coreFunctions->opentable("select 
        'default' as print,
        ' " . $currentDate . " ' as asofdate,
        '' as client,
        '' as dclientname, 
        '0' as clientid
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
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }


    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $asof      = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $client    = $config['params']['dataparams']['client'];
        $clientid  = $config['params']['dataparams']['clientid'];

        $filter = "";
        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }

        $query = "
        select
            risks.terms,
            risks.days,
            count(distinct risks.clientid) as customer_count,
            count(distinct risks.trno) as invoice_count,
            sum(risks.bal) as outstanding_ar,
            case
                when risks.days = 0 then 'COD discipline'
                when risks.days >= 60 then 'High exposure / monitor credit approvals'
                else 'Standard terms'
            end as risk_note
        from (
            select
                xx.trno,
                xx.clientid,
                coalesce(gh.terms, lh.terms) as terms,
                terms.days,
                sum(xx.db - xx.cr) as bal
            from (
                select
                    stock.trno as trno,
                    client.clientid as clientid,
                    sum(stock.ext) as db,
                    0.0 as cr
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join client on client.client = head.client
                left join cntnum as num on head.trno = num.trno
                where head.doc = 'SJ'
                    and date(head.dateid) <= '" . $asof . "'
                    " . $filter . "
                group by stock.trno, client.clientid

                union all

                select
                    detail.trno as trno,
                    client.clientid as clientid,
                    ap.db as db,
                    ap.cr as cr
                from arledger as ap
                left join glhead as head on head.trno = ap.trno
                left join client on client.clientid = ap.clientid
                left join gldetail as detail on detail.trno = ap.trno and detail.line = ap.line
                left join cntnum as num on head.trno = num.trno
                left join coa as c on c.acnoid = detail.acnoid
                where date(ap.dateid) <= '" . $asof . "'
                    " . $filter . "

                union all

                select
                    detail.refx as trno,
                    client.clientid as clientid,
                    detail.db as db,
                    detail.cr as cr
                from glhead as head
                left join gldetail as detail on detail.trno = head.trno
                left join client on client.clientid = detail.clientid
                left join cntnum as num on detail.trno = num.trno
                left join coa as c on c.acnoid = detail.acnoid
                where detail.refx <> 0
                    and left(c.alias, 2) = 'AR'
                    and date(head.dateid) <= '" . $asof . "'
                    " . $filter . "

                union all

                select
                    detail.refx as trno,
                    client.clientid as clientid,
                    detail.db as db,
                    detail.cr as cr
                from lahead as head
                left join ladetail as detail on detail.trno = head.trno
                left join client on client.client = detail.client
                left join cntnum as num on detail.trno = num.trno
                left join coa as c on c.acnoid = detail.acnoid
                where detail.refx <> 0
                    and left(c.alias, 2) = 'AR'
                    and date(head.dateid) <= '" . $asof . "'
                    " . $filter . "
            ) as xx
            left join glhead as gh on gh.trno = xx.trno
            left join lahead as lh on lh.trno = xx.trno
            left join terms on terms.terms = coalesce(gh.terms, lh.terms)
            group by xx.trno, xx.clientid, coalesce(gh.terms, lh.terms), terms.days
            having sum(xx.db - xx.cr) <> 0
        ) as risks
        group by risks.terms, risks.days
        order by risks.days";


        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];


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
        $str .= $this->reporter->col('As Of Date :', null, null, false, '', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Filter by :', null, null, false, '', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //space
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '20', false, '', '', 'L', $font, '10', 'B');
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
}//end class