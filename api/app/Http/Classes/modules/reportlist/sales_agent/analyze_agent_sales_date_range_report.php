<?php

namespace App\Http\Classes\modules\reportlist\sales_agent;

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


class analyze_agent_sales_date_range_report
{
    public $modulename = 'Analyze Agent Sales Date Range Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
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
        $fields = ['radioprint', 'start', 'end', 'pref', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.label', 'Transactions');
        data_set(
            $col1,
            'radioreporttype.options',
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
        return $this->coreFunctions->opentable("
        select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as pref, 
        '' as prefix,
        '0' as reporttype");
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
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = $this->othersClass->sbcdateformat($config['params']['dataparams']['start']);
        $end = $this->othersClass->sbcdateformat($config['params']['dataparams']['end']);
        $prefix = $config['params']['dataparams']['prefix'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $filter = '';

        if ($prefix != '') {
            $filter .= " and head.doc  = '$prefix'";
        }

        switch ($reporttype) {
            case '0': // posted
                $query = "select head.doc,head.trno as ref,concat(left(head.docno,3), ' - ', right(head.docno,5)) as docno,date(head.dateid) as date, 
                upper(ifnull(agent.clientname,'')) as agent,ifnull(cl.clientname,'') as client,cl.tin, ifnull(stock.amount,0) as amount
                from glhead as head
                left join (select trno, sum(ext) as amount from glstock group by trno) as stock on stock.trno=head.trno
                left join client as cl on cl.clientid = head.clientid
                left join client as agent on agent.clientid=head.agentid
                where head.doc in ('SJ','CM') and date(head.dateid) between '$start' and '$end' $filter
                order by agent, date, docno";
                break;
            case '1': // unposted
                $query = "select head.doc,head.trno as ref,concat(left(head.docno,3), ' - ', right(head.docno,5)) as docno,date(head.dateid) as date, 
                upper(ifnull(agent.clientname,'')) as agent,ifnull(cl.clientname,'') as client,cl.tin, ifnull(stock.amount,0) as amount
                from lahead as head
                left join (select trno, sum(ext) as amount from lastock group by trno) as stock on stock.trno=head.trno
                left join client as cl on cl.client = head.client
                left join client as agent on agent.client=head.agent
                where head.doc in ('SJ','CM') and date(head.dateid) between '$start' and '$end' $filter
                order by agent, date, docno";
                break;
            default: // all
                $query = "select head.doc,head.trno as ref,concat(left(head.docno,3), ' - ', right(head.docno,5)) as docno,date(head.dateid) as date,
                upper(ifnull(agent.clientname,'')) as agent,ifnull(cl.clientname,'') as client,cl.tin, ifnull(stock.amount,0) as amount
                from lahead as head
                left join (select trno, sum(ext) as amount from lastock group by trno) as stock on stock.trno=head.trno
                left join client as cl on cl.client = head.client
                left join client as agent on agent.client=head.agent
                where head.doc in ('SJ','CM') and date(head.dateid) between '$start' and '$end' $filter
                union all
                select head.doc,head.trno as ref,concat(left(head.docno,3), ' - ', right(head.docno,5)) as docno,date(head.dateid) as date, upper(ifnull(agent.clientname,'')) as agent,
                ifnull(cl.clientname,'') as client,cl.tin, ifnull(stock.amount,0) as amount
                from glhead as head
                left join (select trno, sum(ext) as amount from glstock group by trno) as stock on stock.trno=head.trno
                left join client as cl on cl.clientid = head.clientid
                left join client as agent on agent.clientid=head.agentid
                where head.doc in ('SJ','CM') and date(head.dateid) between '$start' and '$end' $filter
                order by agent, date, docno";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $prefix = $config['params']['dataparams']['prefix'];
        $start = date('m-d-Y', strtotime($config['params']['dataparams']['start']));
        $end = date('m-d-Y', strtotime($config['params']['dataparams']['end']));
        $printDate = date("m/d/Y  g:i:s A");

        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = '11';
        $border = "2px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('AGENT SALES REPORT', '1000', null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('as of <b>' . $start . '</b> to <b>' . $end . '</b>', '1000', null, false, '', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PREFIX :', '80', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col($prefix == '' ? 'ALL PREFIX' : strtoupper($prefix), '320', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '600');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PRINT DATE : ' . $printDate, '500', null, false, $border, 'B', 'L', $font, '10', '', 'B');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, 'B', 'R', $font, '10', '', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT #', '150', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('REFERENCE', '150', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DATE', '120', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('TIN', '150', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('CUSTOMER NAME', '280', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('AMOUNT', '150', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config, $data)
    {
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = '11';

        $maxrows = 40;
        $rowcount = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $currentagent = null;
        $subtotal = 0;
        $grandtotal = 0;

        foreach ($data as $key => $d) {
            $agent = trim($d->agent) == '' ? 'NO AGENT' : strtoupper($d->agent);

            if ($currentagent !== $agent) {
                if ($currentagent !== null) {
                    $str .= $this->agentSubtotal($currentagent, $subtotal, $layoutsize, $font, $fontsize);
                    $rowcount += 2;
                    $subtotal = 0;
                }

                if ($rowcount + 2 > $maxrows) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader($config);
                    $rowcount = 0;
                }

                $str .= $this->agentTitle($agent, $layoutsize, $font, $fontsize);
                $rowcount++;

                $currentagent = $agent;
            } elseif ($rowcount >= $maxrows) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $rowcount = 0;

                $str .= $this->agentTitle($agent, $layoutsize, $font, $fontsize);
                $rowcount++;
            }

            $amount = (float) $d->amount;
            if ($d->doc == 'CM') {
                $amount = -abs($amount);
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $d->docno, '150', null, false, '', '', 'L', $font, $fontsize);
            $str .= $this->reporter->col($d->ref, '150', null, false, '', '', 'L', $font, $fontsize);
            $str .= $this->reporter->col(date('m-d-Y', strtotime($d->date)), '120', null, false, '', '', 'C', $font, $fontsize);
            $str .= $this->reporter->col($d->tin, '150', null, false, '', '', 'C', $font, $fontsize);
            $str .= $this->reporter->col($d->client, '280', null, false, '', '', 'L', $font, $fontsize);
            $str .= $this->reporter->col($this->fmtAmount($amount), '150', null, false, '', '', 'R', $font, $fontsize);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $rowcount++;

            $subtotal += $amount;
            $grandtotal += $amount;
        }

        if ($currentagent !== null) {
            $str .= $this->agentSubtotal($currentagent, $subtotal, $layoutsize, $font, $fontsize);
            $rowcount += 2;
        }

        if ($rowcount + 1 > $maxrows) {
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config);
            $rowcount = 0;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL :', '850', null, false, '', '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($this->fmtAmount($grandtotal), '150', null, false, '1px solid ', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    private function fmtAmount($value)
    {
        $value = (float) $value;
        return $value < 0 ? '(' . number_format(abs($value), 2) . ')' : number_format($value, 2);
    }

    private function agentTitle($agent, $layoutsize, $font, $fontsize)
    {
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;<u>' . $agent . '</u>', '1000', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    private function agentSubtotal($agent, $subtotal, $layoutsize, $font, $fontsize)
    {
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col($agent . ' ' . ' Sub - Total :', '700', null, false, '', '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($this->fmtAmount($subtotal), '150', null, false, '1px dashed ', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        return $str;
    }
} //end class