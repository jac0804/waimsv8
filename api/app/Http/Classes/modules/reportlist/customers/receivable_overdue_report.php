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

class receivable_overdue_report
{
    public $modulename = 'Receivable Overdue Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = array('orientation' => 'l', 'format' => 'Letter', 'layoutSize' => '1000');

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
        $fields = array('radioprint', 'asofdate', 'dclientname','radioreporttype');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'asofdate.readonly', false);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'radioreporttype.label', 'Sort By');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Document', 'value' => '0', 'color' => 'red'],
            ['label' => 'Customer', 'value' => '1', 'color' => 'red'],
            ['label' => 'Transaction Date', 'value' => '2', 'color' => 'red']
        ]);

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
        '' as clientname,
        '' as dclientname, 
        '0' as clientid,
        '0' as reporttype
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
        $result = $this->data_query($config);
        return $this->reportDefaultLayout($config, $result);
    }

    public function data_query($config)
    {
        $client  = $config['params']['dataparams']['client'];
        $clientid = $config['params']['dataparams']['clientid'];
        $asof = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $sort = "";

        $filter = "";
        if ($client != "") {
            $filter = " and cl.client='$client'";
        }

        $sortBy = $config['params']['dataparams']['reporttype'];
        if ($sortBy == 0) {
            $sort .= " order by ar.docno";
        }else if ($sortBy == 1) {
            $sort .= " order by cl.clientname";
        } else if ($sortBy == 2) {
            $sort .= " order by ar.dateid";
        }

        $query = "
        select CONCAT(LEFT(ar.docno, 3), RIGHT(ar.docno, 5)) AS docno,
        CONCAT(LEFT(cl.client, 3), RIGHT(cl.client, 5)) AS client,
        cl.clientname, cl.addr,agent.clientname as agent,head.terms, date(head.dateid) as  invdate, date(head.due) as due, ar.bal,ar.db, ar.ref, 0 as short, 0 as unapplied
        from arledger as ar
        left join glhead as head on head.trno = ar.trno
        left join client as cl on cl.clientid = ar.clientid
        left join client as agent on agent.clientid = ar.agentid
        left join coa on coa.acnoid = ar.acnoid
        where left(coa.alias, 2) = 'AR'  and head.dateid <= '" . $asof . "'  " . $filter . "  
        " . $sort . "
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $asof = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
        $sortBy = $config['params']['dataparams']['reporttype'];
        $clientname =  $config['params']['dataparams']['clientname'];
        $customer = '';

        if ($clientname != '') {
            $customer = $clientname ;
        }else{
            $customer = 'All Customers';
        }

        if ($sortBy == 0) {
            $sort = "Document";
        }else if ($sortBy == 1) {
            $sort = "Customer";
        } else if ($sortBy == 2) {
            $sort = "Transaction Date";
        }

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
        $str .= $this->reporter->col('Receivable Overdue Report', null, null, false, '', '', 'C', $font, '16', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As-of Date : ' , 100, null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col( $asof, null, null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer : ', 100, null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col(  $customer, null, null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sort By : ', 100, null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col(  $sort, null, null, false, '', '', 'L', $font, $fontsize, '');
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

    public function reportDefaultLayout($config, $result)
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
        $str .= $this->displayHeader($config);
        
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->begintable();
        foreach ($result as $key => $data) {
        $daysOverdue = isset($data->due) && $data->due != '' ? (strtotime($asof) - strtotime($data->due)) / 86400 : '';

        // for Aging Bucket
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

        //Action/Exception
        $exception = '';
        if ($data->bal == 0) {
            $exception = 'No action';
        } else if ($data->short > 0) { //placeholder, will replace
            $exception = 'Resolve Short Payment';
        } else if ($data->unapplied > 0)  {  //placeholder, will replace
            $exception = 'Match unapplied receipt';
        } else if ($data->bal && ($daysOverdue > 0)) {
            $exception = 'Collection follow-up';
        }

        $rowcols = [$data->docno, $data->clientname, $data->addr];
        $rowlens = [8, 28, 20];
        $next = $this->countline($rowcols, $rowlens, false); //check next data

        if (($this->reporter->linecounter + $next) > ($count + 1) ) {
            $str .= $this->reporter->endtable();
            $this->reporter->linecounter = 0;
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config);
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
        $str .= $this->reporter->col(isset ($data->bal) && $data->bal <> 0 ? number_format($data->bal, 2): '-', 80, '20', false, $border, '', 'R', $font, $fontsize2, '');
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
            $lines = $this->othersClass->getmaxcolumn($arr);  //whichever column wrapped into the most lines
            if ($commit) { //used by $next to check if the next row will exceed the page limit
                $this->reporter->linecounter = $this->reporter->linecounter + $lines;
            }
            return $lines;
        } else {
            if ($commit) { // then count this data
                $this->reporter->linecounter++;
            }
            return 1;
        }
    }

}