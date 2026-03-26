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
use DateTime;


class customer_account_analysis_report
{
    public $modulename = 'Customer Account Analysis Report';
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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'start', 'end', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dclientname.lookupclass', 'rcustomer');
        data_set($col1, 'dclientname.required', true);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1,  'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
            'default' as print,
            adddate(left(now(),10),-30) as start,
            left(now(),10) as end,
            '' as client,
            '' as clientname,
            '' as dclientname ";

        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_query($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        // $filtercenter = $config['params']['dataparams']['center'];
        $client    = $config['params']['dataparams']['client'];
        $clientid    = $config['params']['dataparams']['clientid'];
        $filter = "";
        $filter2 = "";
        $filter3 = "";


        if ($client != "") {
            $filter .= " and cl.clientid='$clientid' ";
            $filter2 .= " and a.clientid='$clientid' ";
            $filter3 .= " and ar.clientid='$clientid' ";
        }

        // if ($filtercenter != "") {
        //     $filter = " and cntnum.center='$filtercenter'";
        // }

        $query = "select sum(collectionamt) as collectionamt, sum(bounced) as bounced,
                            (SELECT SUM(checks)
                            FROM (
                            SELECT SUM(a.db - a.cr) AS checks, a.clientid
                            FROM crledger AS a
                            LEFT JOIN cntnum ON cntnum.trno = a.trno
                            WHERE a.depodate IS NULL
                            AND DATE(a.checkdate)  between '$start' and '$end'   $filter2
                            GROUP BY a.clientid

                            UNION ALL

                            SELECT SUM(a.db - a.cr) AS checks, cl.clientid
                            FROM ladetail AS a
                            LEFT JOIN lahead AS h ON h.trno = a.trno
                            LEFT JOIN cntnum ON cntnum.trno = a.trno
                            LEFT JOIN coa ON coa.acnoid = a.acnoid
                            LEFT JOIN client as cl on cl.client = h.client
                            WHERE DATE(h.dateid)  between '$start' and '$end'   $filter  
                            AND LEFT(coa.alias, 2) = 'CR'
                            GROUP BY cl.clientid
                        ) AS u) AS uncleared_amt,

                        sum((select (case when ar.db > 0 then ar.bal else (ar.bal * -1) end) as balance
                            from arledger as ar
                            where ar.trno = ax.trno and ar.clientid = ax.clientid  and date(ar.dateid) <= '$end'  limit 1)) as balance,clientname

                    from (

                    SELECT sum(if(left(coa.alias,2) in ('ca','cr','pc') and head.doc='cr', d.db, 0)) as collectionamt,
                            sum(if(coa.alias='arb', d.db, 0)) as bounced,head.trno,head.clientid,cl.clientname
                            FROM glhead AS head
                            LEFT JOIN gldetail AS d on d.trno = head.trno
                            LEFT JOIN client as cl on cl.clientid = head.clientid
                            LEFT JOIN coa ON d.acnoid = coa.acnoid
                            LEFT JOIN cntnum on cntnum.trno = head.trno
                            WHERE  date(head.dateid) between '$start' and '$end'   $filter  
                            group by head.trno,head.clientid, cl.clientname) as ax 
                            group by clientname";
        // var_dump($query);
        return $query;
    }

    private function default_displayHeader($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '12';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();


        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer Account Analysis Report', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

        $startdate = $start;
        $startt = new DateTime($startdate);
        $start = $startt->format('m/d/Y');

        $enddate = $end;
        $endd = new DateTime($enddate);
        $end = $endd->format('m/d/Y');

        $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
        $str .= $this->reporter->col('Customer: ' . strtoupper($clientname), null, null, '', $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('PARTICULAR', '490', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('AMOUNT', '290', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '10', '', '', $border, 'TBR', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // // $str .= '<br>';
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', '',  'L', $font, $font_size, '', '', '', '2px');
        // $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, $font_size, '', '', '', '2px');
        // $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, $font_size, '', '', '', '2px');
        // $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, $font_size, '', '', '', '2px');
        // $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $border = '1px solid';

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';

        $result = $this->reportDefault($config);

        // var_dump($result[0]->collectionamt);


        $count = 33;
        $page = 34;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('TOTAL OR/CR AMOUNT', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(number_format($result[0]->collectionamt, 2), '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('UNCLEARED CHECKS', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(number_format($result[0]->uncleared_amt, 2), '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('BOUNCED CHECKS BALANCE', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(number_format($result[0]->bounced, 2), '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('ACCOUNT RECEIVABLE BALANCE', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col(number_format($result[0]->balance, 2), '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('TOTAL CLEARED PAYMENTS', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('TOTAL EXPOSURE', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('TOTAL PURCHASES', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();








        // foreach ($result as $key => $data) {
        //     $str .= $this->reporter->addline();
        //     $str .= $this->reporter->startrow();
        //     $str .= $this->reporter->col($data->collectionamt, '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '2px', '');
        //     $str .= $this->reporter->col('', '490', null, false, $border, '', 'L', $font, $font_size, '', '', '2px', '');
        //     $str .= $this->reporter->col('', '290', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
        //     $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
        //     $str .= $this->reporter->endrow();
        //     // $tlsales += $data->netsales;

        //     if ($this->reporter->linecounter == $page) {

        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'L', $font, $font_size, '', '', '2px', '');
        //         $str .= $this->reporter->col('', '490', null, false, $border, 'T', 'L', $font, $font_size, '', '', '2px', '');
        //         $str .= $this->reporter->col('', '290', null, false, $border, 'T', 'R', $font, $font_size, '', '', '2px', '');
        //         $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $font_size, '', '', '2px', '');
        //         $str .= $this->reporter->endrow();

        //         $str .= $this->reporter->endtable();
        //         $str .= $this->reporter->page_break();
        //         $str .= $this->default_displayHeader($config);
        //         $page = $page + $count;
        //     }
        // }


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '490', null, false, $border, 'T', 'L', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '290', null, false, $border, 'T', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();


        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('&nbsp;', '10', null, false,  '', '',  'L', $font, '4', '', '',  '');
        // $str .= $this->reporter->col('&nbsp;', '490', null, false,  '', '',  'L', $font, '4', '', '',  '');
        // $str .= $this->reporter->col('&nbsp;', '290', null, false,  '', '',  'L', $font, '4', '', '',  '');
        // $str .= $this->reporter->col('&nbsp;', '10', null, false,  '', '',  'L', $font, '4', '', '',  '');
        // $str .= $this->reporter->endrow();

        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '10', null, false, $border, 'TLB', 'L', $font, $font_size, '', '', '2px', '');
        // $str .= $this->reporter->col('PAGE TOTAL', '490', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '2px', '');
        // $str .= $this->reporter->col('', '290', null, false, $border, 'TLB', 'R', $font, $font_size, 'B', '', '2px', '');
        // $str .= $this->reporter->col('', '10', null, false, $border, 'TRB', 'R', $font, $font_size, '', '', '2px', '');
        // $str .= $this->reporter->endrow();


        // $printeddate = $this->othersClass->getCurrentTimeStamp();
        // $datetime = new DateTime($printeddate);
        // $formattedDate = $datetime->format('m/d/Y h:i:s a'); //2025-09-25 16:46:32 pm

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class