<?php

namespace App\Http\Classes\modules\reportlist\student;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class student_statement_of_account
{
    public $modulename = 'Student Statement of Accounts';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    private $logger;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    private $balance;
    private $acurrent;
    private $a30days;
    private $a60days;
    private $a90days;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->balance = 0;
        $this->acurrent = 0;
        $this->a30days = 0;
        $this->a60days = 0;
        $this->a90days = 0;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'dateid', 'ehstudentlookup'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'dateid.label', 'Balance as of');
        data_set($col1, 'dateid.readonly', false);
        data_set($col1, 'ehstudentlookup.lookupclass', 'lookupstudent');
        data_set($col1, 'ehstudentlookup.label', 'Student');
        $fields = ['radioreportcustomerfilter', 'attention', 'certifby'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $type = 'default';
        $username = '';

        $paramstr = "select 
            '" . $type . "' as print,
            left(now(),10) as dateid,'' as client,
           '' as ehstudentlookup, '0' as clientid,
            '' as attention,
            '" . $username . "' as certifby,
            '' as received,
            '0' as customerfilter,
            '0' as reporttype";

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
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        return $this->reportDefaultLayout($config);
    }


    public function reportDefault($config)
    {
        $attention = $config['params']['dataparams']['attention'];
        $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $center    = $config['params']['center'];
        $cl       = $config['params']['dataparams']['client'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientid    = $config['params']['dataparams']['clientid'];
        $customerfilter = $config['params']['dataparams']['customerfilter'];

        $filter = "";
        $code = "";
        switch ($customerfilter) {
            case '0':
            case '2':
                if ($client != "") {
                    $filter = "and head.clientid='$clientid'";
                }
                break;
            case '1':
                $code = "and ifnull(client.grpcode,'')<>''";
                if ($client != "") {
                    $filter = "and client.grpcode='$cl'";
                }
                break;
        }

        $query = "select head.trno,'p' as tr, 1 as trsort, client.client, client.clientname, client.addr,head.terms,
                    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit, client.tel,
                    ar.cr as credit, (ar.bal) as balance, ag.client as agent, ag.clientname as agentname, head.due, head.yourref, head.rem,
                    (case when head.doc='sj' then 'sales' else (case when head.doc='cm' then 'return' else 'adjustment' end) end) as trcode 
                    from (((glhead as head 
                    left join arledger as ar on ar.trno=head.trno)
                    left join client on client.clientid=head.clientid)
                    left join coa on coa.acnoid=ar.acnoid)
                    left join client as ag on ag.clientid=ar.agentid
                    left join cntnum as num on num.trno = head.trno
                    where left(coa.alias,2)='ar'
                    and num.center = '$center' 
                    and date(ar.dateid)<='$asof' and ar.bal<>0
                    $code $filter
                    order by clientname, docdate, refno";
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

        $width = '1000';

        $str = '';
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";


        $str .= $this->reporter->begintable($width);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';

        $str .= $this->reporter->begintable($width);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($width);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STUDENT STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($width);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $companyid = $config['params']['companyid'];
        $result     = $this->reportDefault($config);

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $attention  = $config['params']['dataparams']['attention'];
        $certifby   = $config['params']['dataparams']['certifby'];
        $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport('1000');
        $str .= $this->displayHeader($config);
        $customer = '';
        $customersub = '';
        $balance = 0;
        foreach ($result as $key => $data) {
            if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
                if ($customer != $data->clientname) {
                    $customer = $data->clientname;

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('STUDENT : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('DATE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    if ($data->applied == 0) {
                        $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    }
                    if ($data->debit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }
                    if ($data->credit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }
                    $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    if ($data->debit != 0) {
                        $balance = $balance + $data->balance;
                    } else {
                        $balance = $balance - $data->balance;
                    }


                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                } elseif ($customer == $data->clientname) {
                    $customer = $data->clientname;
                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
                    $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

                    if ($data->applied == 0) {
                        $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    }

                    if ($data->debit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }


                    if ($data->credit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }

                    $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

                    if ($data->debit != 0) {
                        $balance = $balance + $data->balance;
                    } else {
                        $balance = $balance - $data->balance;
                    }


                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                } else {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();
                }
            } else {
                $customer = $data->clientname;

                if (($customersub != '' && $customersub != $customer) && $balance != 0) {
                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');

                    $customersub = $data->clientname;
                    $balance = 0;
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
                    $str .= $this->reporter->endrow();


                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();

                    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
                    $str .= $this->reporter->col('<br>');
                    $str .= $this->reporter->col('<br>');
                    $str .= $this->reporter->col('<br>');
                    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
                    $str .= $this->reporter->col('<br>');
                    $str .= $this->reporter->col('<br>');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $str .= $this->reporter->begintable('1000');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('<br>');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->addline();

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader($config);


                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('STUDENT : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('DATE', '', '100', false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
                    $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

                    if ($data->applied == 0) {
                        $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    }

                    if ($data->debit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }


                    if ($data->credit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }

                    $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

                    if ($data->debit != 0) {
                        $balance = $balance + $data->balance;
                    } else {
                        $balance = $balance - $data->balance;
                    }

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $page = $page + $count;
                } else {
                    $str .= $this->reporter->page_break();


                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('STUDENT STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('STUDENT : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('DATE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable('1000');
                    $str .= $this->reporter->startrow();
                    //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
                    $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

                    if ($data->applied == 0) {
                        $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
                    }

                    if ($data->debit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }


                    if ($data->credit == 0) {
                        $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
                    }

                    $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

                    if ($data->debit != 0) {
                        $balance = $balance + $data->balance;
                    } else {
                        $balance = $balance - $data->balance;
                    }

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $page = $page + $count;
                }
            }

            if ($customersub == '') {
                $customersub = $data->clientname;
            }
        }

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        //$txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m=''
        $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class