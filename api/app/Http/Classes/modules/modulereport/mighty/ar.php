<?php

namespace App\Http\Classes\modules\modulereport\mighty;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ar
{

    private $modulename = "Receivable Setup";
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'AR Setup', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Billing With Company Name', 'value' => '1', 'color' => 'orange'],
            ['label' => 'Billing Without Company Name', 'value' => '2', 'color' => 'orange']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received,
      '0' as reporttype
    "
        );
    }

    public function report_default_query($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0:
                $data = $this->report_ar_default_query($config);
                break;
            case 1:
            case 2:
                $data = $this->billing_query($config);
                break;
        }

        return $data;
    }

    public function report_ar_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "
      select head.rem, detail.rem as remarks, 
      date(head.dateid) as dateid, head.docno, 
      client.client, client.clientname, head.address, 
      head.terms, head.yourref, head.ourref,
      coa.acno, coa.acnoname, detail.ref, 
      date(detail.postdate) as postdate, detail.db, 
      detail.cr, detail.client as dclient, detail.checkno
      from lahead as head 
      left join ladetail as detail on detail.trno=head.trno 
      left join client on client.client=head.client
      left join coa on coa.acnoid=detail.acnoid
      where head.doc='ar' and head.trno='$trno'
      union all
      select head.rem, detail.rem as remarks, 
      date(head.dateid) as dateid, head.docno, 
      client.client, client.clientname, head.address, 
      head.terms, head.yourref, head.ourref,
      coa.acno, coa.acnoname, detail.ref, 
      date(detail.postdate) as postdate, detail.db, 
      detail.cr, dclient.client as dclient, detail.checkno
      from glhead as head 
      left join gldetail as detail on detail.trno=head.trno 
      left join client on client.clientid=head.clientid
      left join coa on coa.acnoid=detail.acnoid 
      left join client as dclient on dclient.clientid=detail.clientid
      where head.doc='ar' and head.trno='$trno'";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($config, $data)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0:
                if ($config['params']['dataparams']['print'] == "default") {
                    $str = $this->rpt_ar_default_layout($config, $data);
                } else if ($config['params']['dataparams']['print'] == "PDFM") {
                    $str = $this->default_AR_PDF($config, $data);
                }
                break;
            case 1:
            case 2:
                if ($config['params']['dataparams']['print'] == "default") {
                    $str = $this->billing_no_company($config, $data);
                } else if ($config['params']['dataparams']['print'] == "PDFM") {
                    $str = $this->billing_no_company_PDF($config, $data);
                }
                break;
        }

        return $str;
    }

    public function billing_query($config)
    {
        $trno = $config['params']['dataid'];
        $qry = "select * from (select
      head.trno, head.docno, head.client, head.clientname, services.line,
      head.address, date(head.dateid) as dateid,
      cl.tel, project.name as project_name,
      services.description, services.qty, services.amt
      from lahead as head
      left join client as cl on cl.client = head.client
      left join projectmasterfile as project on project.line = head.projectid
      left join arservicedetail as services on services.trno = head.trno
      where head.trno = '$trno'
      union all
      select
      head.trno, head.docno, cl.client, head.clientname, services.line,
      head.address, date(head.dateid) as dateid,
      cl.tel, project.name as project_name,
      services.description, services.qty, services.amt
      from glhead as head
      left join client as cl on cl.clientid = head.clientid
      left join projectmasterfile as project on project.line = head.projectid
      left join arservicedetail as services on services.trno = head.trno
      where head.trno = '$trno') as service order by line ;";
        $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        return $result;
    }

    public function rpt_ar_header_default($params, $data)
    {
        $str = '';
        $border = "1px solid";
        $font = "Century Gothic";
        $fontsize = "11";

        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('RECEIVABLE SETUP', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('REF. :', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ACCOUNT NAME', '280', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        return $str;
    }

    public function rpt_ar_default_layout($params, $data)
    {

        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $count = 35;
        $page = 35;
        $border = "1px solid";
        $font = "Century Gothic";
        $fontsize = "11";

        $str .= $this->reporter->beginreport();
        $str .= $this->rpt_ar_header_default($params, $data);
        $totaldb = 0;
        $totalcr = 0;
        for ($i = 0; $i < count($data); $i++) {

            $debit = number_format($data[$i]['db'], $decimal);
            if ($debit < 1) {
                $debit = '-';
            }
            $credit = number_format($data[$i]['cr'], $decimal);
            if ($credit < 1) {
                $credit = '-';
            }
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['acnoname'], '280', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['ref'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['postdate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($debit, '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($credit, '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['client'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $totaldb = $totaldb + $data[$i]['db'];
            $totalcr = $totalcr + $data[$i]['cr'];



            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();


                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('RECEIVABLE SETUP', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
                $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
                $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
                $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
                $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
                $str .= $this->reporter->col('REF. :', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
                $str .= $this->reporter->pagenumber('Page');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('ACCOUNT NAME', '280', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('GRAND TOTAL :', '265', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '95', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '40', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    } //end fn

    public function billing_header($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);
        $reporttype = $params['params']['dataparams']['reporttype'];

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = "";
        $border = "1px solid";
        $font = "Century Gothic";
        $fontsize = "11";

        switch ($reporttype) {
            case 1: // with company header
                $qry = "select name,address,tel from center where code = '" . $center . "'";
                $headerdata = $this->coreFunctions->opentable($qry);
                $current_timestamp = $this->othersClass->getCurrentTimeStamp();

                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
            case 2: // without company header

                break;
        }
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BILLING NO ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(': ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col((isset($data[0]['address']) ? nl2br($data[0]['address']) : ''), '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['project_name']) ? $data[0]['project_name'] : ''), '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col((isset($data[0]['tel']) ? $data[0]['tel'] : ''), '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(': ' . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sir/Miss/Madam&nbsp:&nbsp', '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col('We charge your account for the following:', '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEESCRIPTION', '50px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '500px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('AMOUNT', '125px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');

        return $str;
    }

    public function billing_no_company($params, $data)
    {
        $str = '';
        $count = 35;
        $page = 35;

        $border = "1px solid";
        $font = "Century Gothic";
        $fontsize = "11";

        $str .= $this->reporter->beginreport();
        $str .= $this->billing_header($params, $data);
        $totalext = 0;

        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['description'], '50px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['qty'], '500px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['amt'], '125px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->billing_header($params, $data);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    public function  default_AR_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];


        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);


        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(520, 0, 'RECEIVABLE SETUP', '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "DOCUMENT #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 0, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "CUSTOMER: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "DATE: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(0, 0, '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "ADDRESS: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "REF: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);


        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(80, 0, "ACCT NO.", '', 'L', false, 0);
        PDF::MultiCell(200, 0, "ACCOUNT NAME", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "REFERENCE #", '', 'C', false, 0);
        PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(75, 0, "DEBIT", '', 'R', false, 0);
        PDF::MultiCell(75, 0, "CREDIT", '', 'R', false, 0);
        PDF::MultiCell(120, 0, "CLIENT", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_AR_PDF($params, $data)
    {

        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_AR_header_PDF($params, $data);
        $countarr = 0;
        $totaldb = 0;
        $totalcr = 0;

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '');



        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $acno =  $data[$i]['acno'];
                $acnonamedescs = $data[$i]['acnoname'];
                $ref = $data[$i]['ref'];
                $postdate = $data[$i]['postdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $debit = $debit < 0 ? '-' : $debit;
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $credit = $credit < 0 ? '-' : $credit;
                $client = $data[$i]['client'];


                $arr_acno = $this->reporter->fixcolumn([$acno], '10', 0);
                $arr_acnonamedescs = $this->reporter->fixcolumn([$acnonamedescs], '28', 0);
                $arr_ref = $this->reporter->fixcolumn([$ref], '13', 0);
                $arr_postdate = $this->reporter->fixcolumn([$postdate], '13', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '12', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '12', 0);
                $arr_client = $this->reporter->fixcolumn([$client], '15', 0);


                $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnonamedescs, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(80, 15, ' ' . (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(200, 15, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ' . (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];
                if (PDF::getY() > 900) {
                    $this->default_AR_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(455, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(75, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(75, 0, number_format($totalcr, $decimalprice), '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function billing_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $reporttype = $params['params']['dataparams']['reporttype'];

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        switch ($reporttype) {
            case 1: // with company

                PDF::SetFont($fontbold, '', 13);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
                PDF::SetFont($fontbold, '', 15);
                PDF::MultiCell(0, 0,  "BILLING STATEMENT" . "\n", '', 'C');
                break;
            case 2: //without company

                PDF::SetFont($fontbold, '', 13);
                PDF::MultiCell(0, 0, '', '', 'C');
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, "\n", '', 'C');
                PDF::SetFont($fontbold, '', 15);
                PDF::MultiCell(0, 0,  "BILLING STATEMENT" . "\n", '', 'C');
                break;
        }

        PDF::MultiCell(0, 0, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(570, 15, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
        PDF::MultiCell(50, 15, "BILLING.: ", '', 'L', false, 0, '',  '');
        PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(570, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
        PDF::MultiCell(150, 0, (isset($data[0]['project_name']) ? $data[0]['project_name'] : ''), '', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(570, 15, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), '', 'L', false, 0, '',  '');
        PDF::MultiCell(50, 15, "DATE: ", '', 'L', false, 0, '',  '');
        PDF::MultiCell(100, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0, '',  '');
        PDF::MultiCell(0, 15, "\n");
        PDF::MultiCell(470, 15, "", '', 'L', false);
        PDF::MultiCell(0, 15, "\n");
        PDF::MultiCell(50, 15, "", '', 'L', false, 0, '',  '');
        PDF::MultiCell(470, 15, "We charge your account for the following: ", '', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');


        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(450, 0, "DESCRIPTION", '', 'C', false, 0);
        PDF::MultiCell(150, 0, "", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "AMOUNT", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function billing_no_company_PDF($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->billing_header_PDF($params, $data);


        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $arr_description = $this->reporter->fixcolumn([$data[$i]['description']], '80', 0);
            $arr_qty = $this->reporter->fixcolumn([$data[$i]['qty']], '16', 0);
            $arr_amt = $this->reporter->fixcolumn([$data[$i]['amt']], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_description, $arr_qty, $arr_amt]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(450, 0, (isset($arr_description[$r]) ? $arr_description[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(150, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', 0, 1, '', '', true, 0, true, false);
            }
            if (intVal($i) + 1 == $page) {
                $this->billing_header_PDF($params, $data);
                $page += $count;
            }
        }



        PDF::MultiCell(720, 0, "", "T");
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(180, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(180, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(180, 0, 'Received By: ', '', 'L', false, 0);
        PDF::MultiCell(180, 0, 'Noted By: ', '', 'L');



        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], '', 'L', false, 0);
        // PDF::MultiCell(180, 0, 'JENSEN EARL SY', '', 'L');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(180, 0, '', '', 'L', false, 0);
        PDF::MultiCell(180, 0, '', '', 'L', false, 0);
        PDF::MultiCell(180, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(180, 0, 'Chief Finance Officer', '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
