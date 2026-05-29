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

class outstanding_customer_receivables
{
    public $modulename = 'Outstanding Customer Receivables';
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
        $fields = ['radioprint', 'asofdate', 'dclientname', 'dcentername'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'asofdate.readonly', false);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
        ]);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dcentername.lookupclass', 'getmultibranch');

        $fields = ['radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.label', 'Sorting Type');
        data_set($col2, 'radioreporttype.options', array(
            ['label' => 'Sorting By Date', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Sorting By Client', 'value' => '1', 'color' => 'orange']
        ));

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $currentDate = $this->othersClass->getCurrentDate();
        return $this->coreFunctions->opentable("select 
    'default' as print,
      ' " . $currentDate . " ' as asofdate,
    '' as client,
    '' as dclientname, 
    '0' as clientid,
     '" . $defaultcenter[0]['dcentername'] . "' as dcentername, 
     '" . $defaultcenter[0]['center'] . "' as center,
     '" . $defaultcenter[0]['centername'] . "' as centername,
     '0' as reporttype
    ");
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
        $data = $this->default_query($config);
        // return $this->report_default_group($config, $data);
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0:
                $str = $this->report_default_date($config, $data);
                break;

            case 1:
                $str = $this->report_default_client($config, $data);
                break;

            default:
                $str =  $this->report_default_client($config, $data);
                break;
        } // end switch report type
        return $str;
    }

    public function default_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $client  = $config['params']['dataparams']['client'];
        $clientid = $config['params']['dataparams']['clientid'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $asof = date('Y-m-d', strtotime($config['params']['dataparams']['asofdate']));

        $filter = "";
        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }
        if ($center != "0") {
            $filter .= " and num.center='$center'";
        }
        switch ($reporttype) {
            case 0:
                $query = "select dateid, docno, client, clientname, MIN(particular) as particular, elapse, sum(db) as db, sum(cr) as cr, min(trno) as trno, sum(db - cr) as bal
              from (select date(head.dateid) as dateid, head.docno, client.client, client.clientname, concat(head.yourref, ' ', head.rem) as particular,
              datediff('$asof', head.dateid) as elapse, sum(stock.ext) as db, 0.0 as cr, stock.trno as trno, 1 as line, '' as ref, 0 as bal
              from lahead as head left join lastock as stock on stock.trno = head.trno
              left join client on client.client = head.client left join cntnum as num on head.trno = num.trno
              where head.doc = 'SJ' and date(head.dateid)<='$asof' $filter
              group by date(head.dateid), head.docno, client.client, client.clientname, concat(head.yourref, ' ', head.rem), datediff('$asof', head.dateid), stock.trno
              union all
              select dateid, docno, client, clientname, particular, elapse, sum(db) as db, sum(cr) as cr, min(trno) as trno, 1 as line, ref, sum(db - cr) as bal
              from (select date(ap.dateid) as dateid, head.docno, client.client, client.clientname, concat(head.yourref, ' ', head.rem) as particular,
              datediff('$asof', ap.dateid) as elapse, ap.db as db, ap.cr as cr, detail.trno as trno, detail.line as line, '' as ref
              from arledger as ap left join glhead as head on head.trno = ap.trno
              left join client on client.clientid = ap.clientid
              left join gldetail as detail on detail.trno = ap.trno and detail.line = ap.line
              left join cntnum as num on head.trno = num.trno
              left join coa as c on c.acnoid=detail.acnoid
              where date(ap.dateid)<='$asof' $filter
              union all
              select date(detail.postdate) as dateid, detail.ref as docno, client.client, client.clientname, concat(h.yourref, ' ', h.rem) as particular,
              datediff('$asof', detail.postdate) as elapse, detail.db as db, detail.cr as cr, detail.refx as trno, detail.linex as line, head.docno as ref
              from glhead as head left join gldetail as detail on detail.trno = head.trno
              left join client on client.clientid = detail.clientid
              left join cntnum as num on detail.trno = num.trno
              left join coa as c on c.acnoid=detail.acnoid
              left join glhead as h on h.trno = detail.refx
              where detail.refx <> 0 and left(c.alias, 2) = 'AR' and date(head.dateid)<='$asof' $filter
              union all
              select date(detail.postdate) as dateid, detail.ref as docno, client.client, client.clientname, concat(h.yourref, ' ', h.rem) as particular,
              datediff('$asof', detail.postdate) as elapse, detail.db as db, detail.cr as cr, detail.refx as trno, detail.linex as line, head.docno as ref
              from lahead as head left join ladetail as detail on detail.trno = head.trno
              left join client on client.client = detail.client
              left join cntnum as num on detail.trno = num.trno
              left join coa as c on c.acnoid=detail.acnoid
              left join glhead as h on h.trno = detail.refx
              where detail.refx <> 0 and left(c.alias, 2) = 'AR' and date(head.dateid)<='$asof' $filter
              ) x
              group by dateid, docno, client, clientname, particular, elapse, ref
              having sum(db - cr) <> 0
              )xx
              group by dateid, docno, client, clientname, elapse
              having sum(db - cr) <> 0
              order by dateid, docno; 
              ";
                break;
            case 1:
                $query = "select dateid, docno, client, clientname, MIN(particular) as particular, elapse, sum(db) as db, sum(cr) as cr, min(trno) as trno, sum(db - cr) as bal
              from (select date(head.dateid) as dateid, head.docno, client.client, client.clientname, concat(head.yourref, ' ', head.rem) as particular,
              datediff('$asof', head.dateid) as elapse, sum(stock.ext) as db, 0.0 as cr, stock.trno as trno, 1 as line, '' as ref, 0 as bal
              from lahead as head left join lastock as stock on stock.trno = head.trno
              left join client on client.client = head.client left join cntnum as num on head.trno = num.trno
              where head.doc = 'SJ' and date(head.dateid)<='$asof' $filter
              group by date(head.dateid), head.docno, client.client, client.clientname, concat(head.yourref, ' ', head.rem), datediff('$asof', head.dateid), stock.trno
              union all
              select dateid, docno, client, clientname, particular, elapse, sum(db) as db, sum(cr) as cr, min(trno) as trno, 1 as line, ref, sum(db - cr) as bal
              from (select date(ap.dateid) as dateid, head.docno, client.client, client.clientname, concat(head.yourref, ' ', head.rem) as particular,
              datediff('$asof', ap.dateid) as elapse, ap.db as db, ap.cr as cr, detail.trno as trno, detail.line as line, '' as ref
              from arledger as ap left join glhead as head on head.trno = ap.trno
              left join client on client.clientid = ap.clientid
              left join gldetail as detail on detail.trno = ap.trno and detail.line = ap.line
              left join cntnum as num on head.trno = num.trno
              left join coa as c on c.acnoid=detail.acnoid
              where date(ap.dateid)<='$asof' $filter
              union all
              select date(detail.postdate) as dateid, detail.ref as docno, client.client, client.clientname, concat(h.yourref, ' ', h.rem) as particular,
              datediff('$asof', detail.postdate) as elapse, detail.db as db, detail.cr as cr, detail.refx as trno, detail.linex as line, head.docno as ref
              from glhead as head left join gldetail as detail on detail.trno = head.trno
              left join client on client.clientid = detail.clientid
              left join cntnum as num on detail.trno = num.trno
              left join coa as c on c.acnoid=detail.acnoid
              left join glhead as h on h.trno = detail.refx
              where detail.refx <> 0 and left(c.alias, 2) = 'AR' and date(head.dateid)<='$asof' $filter
              union all
              select date(detail.postdate) as dateid, detail.ref as docno, client.client, client.clientname, concat(h.yourref, ' ', h.rem) as particular,
              datediff('$asof', detail.postdate) as elapse, detail.db as db, detail.cr as cr, detail.refx as trno, detail.linex as line, head.docno as ref
              from lahead as head left join ladetail as detail on detail.trno = head.trno
              left join client on client.client = detail.client
              left join cntnum as num on detail.trno = num.trno
              left join coa as c on c.acnoid=detail.acnoid
              left join glhead as h on h.trno = detail.refx
              where detail.refx <> 0 and left(c.alias, 2) = 'AR' and date(head.dateid)<='$asof' $filter
              ) x
              group by dateid, docno, client, clientname, particular, elapse, ref
              having sum(db - cr) <> 0
              )xx
              group by dateid, docno, client, clientname, elapse
              having sum(db - cr) <> 0
              order by clientname, dateid, docno;
              ";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function reportdatacsv($config)
    {
        $data = $this->default_query($config);
        $reporttype = $config['params']['dataparams']['reporttype'];

        $allData = [];

        switch ($reporttype) {
            case 0: // Sorting By Date
                foreach ($data as $row) {
                    $allData[] = [
                        'CLIENT_NAME'  => $row->clientname,
                        'PARTICULAR'   => $row->particular,
                        'DATE'         => date("Y-m-d", strtotime($row->dateid)),
                        'DOC_NO'       => $row->docno,
                        'DAYS'         => (float)$row->elapse,
                        'BALANCE_DUE'  => (float)$row->bal,
                    ];
                }
                break;

            case 1: // Sorting By Client
                foreach ($data as $row) {
                    $allData[] = [
                        'CLIENT_NAME'  => $row->clientname,
                        'PARTICULAR'   => $row->particular,
                        'DATE'         => date("Y-m-d", strtotime($row->dateid)),
                        'DOC_NO'       => $row->docno,
                        'DAYS'         => (float)$row->elapse,
                        'BALANCE_DUE'  => (float)$row->bal,
                    ];
                }
                break;

            default:
                foreach ($data as $row) {
                    $allData[] = [
                        'CLIENT_NAME'  => $row->clientname,
                        'PARTICULAR'   => $row->particular,
                        'DATE'         => date("Y-m-d", strtotime($row->dateid)),
                        'DOC_NO'       => $row->docno,
                        'DAYS'         => (float)$row->elapse,
                        'BALANCE_DUE'  => (float)$row->bal,
                    ];
                }
                break;
        }
        $status =  true;
        $msg = 'Generating CSV successfully';
        if (empty($data)) {
            $status =  false;
            $msg = 'No data Found';
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $allData, 'params' => $this->reportParams, 'name' => 'Outstanding Customer Receive'];
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $asof       = $config['params']['dataparams']['asofdate'];


        $str      = '';
        $font     = 'Tahoma';
        $fontsize = "11";
        $border   = "1px solid ";
        $layoutsize = 1000;

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->coreFunctions->opentable($qry)[0]->name, null, null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->coreFunctions->opentable($qry)[0]->address, null, null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OUTSTANDING CUSTOMER RECEIVABLE', null, null, false, '10px solid ', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('AS OF ' . $asof, null, null, false, '', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '980', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER NAME', '225', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PARTICULAR', '250', null, false, $border, 'B',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '130', null, false, $border, 'B',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC #', '135', null, false, $border, 'B',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DAYS', '100', null, false, $border, 'B',  'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE DUE', '110', null, false, $border, 'B',  'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function report_default_client($config, $data)
    {
        $str        = '';
        $layoutsize = '1000';
        $font       = 'Tahoma';
        $fontsize   = '10';
        $border     = '1px solid';

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($data));

        $grandTotal = 0;
        $lastClient = '';

        foreach ($data as $row) {

            $balance = $row->bal;
            $grandTotal += $balance;

            $dateid = date("Y-m-d", strtotime($row->dateid));

            $clientName = '';
            if ($lastClient != $row->clientname) {

                if ($lastClient != '') {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'B', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $clientName = $row->clientname;
                $lastClient = $row->clientname;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($clientName, '225', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($row->particular, '250', null, false, $border, '', 'LT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($dateid, '130', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($row->docno, '135', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($row->elapse, '100', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($balance, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // Grand total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, '1px dotted', 'TB', 'LT', $font, $fontsize, '',  '', '');
        $str .= $this->reporter->col('GRAND TOTAL', '605', null, false, '1px dotted', 'TB', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandTotal, 2), '245', null, false, '1px dotted', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function report_default_date($config, $data)
    {
        $str        = '';
        $layoutsize = '1000';
        $font       = 'Tahoma';
        $fontsize   = '10';
        $border     = '1px solid';

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($data));

        $grandTotal = 0;
        $lastClient = '';

        foreach ($data as $row) {

            $balance = $row->bal;
            $grandTotal += $balance;

            $dateid = date("Y-m-d", strtotime($row->dateid));

            $clientName = '';
            if ($lastClient != $row->clientname) {

                if ($lastClient != '') {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'B', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                // $clientName = $row->clientname;
                // $lastClient = $row->clientname;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->clientname, '225', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($row->particular, '250', null, false, $border, '', 'LT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($dateid, '130', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($row->docno, '135', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($row->elapse, '100', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($balance, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // Grand total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, '1px dotted', 'TB', 'LT', $font, $fontsize, '',  '', '');
        $str .= $this->reporter->col('GRAND TOTAL', '605', null, false, '1px dotted', 'TB', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandTotal, 2), '245', null, false, '1px dotted', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end of class