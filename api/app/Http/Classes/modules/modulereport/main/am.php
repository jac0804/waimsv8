<?php

namespace App\Http\Classes\modules\modulereport\main;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\common\commonsbc;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class am
{

    private $modulename = "SERVICE INVOICE";
    private $reportheader;
    private $commonsbc;
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
        $this->reportheader = new reportheader;
        $this->commonsbc = new commonsbc;
    }

    public function createreportfilter($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'radiosjafti', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        data_set($col1, 'radiosjafti.options', [
            ['label' => 'New Sales Invoice', 'value' => 'sales', 'color' => 'red'],
            ['label' => 'New Service Invoice', 'value' => 'service', 'color' => 'red'],
            ['label' => 'Print Out 1', 'value' => 'print1', 'color' => 'red'],
            ['label' => 'Print Out 2', 'value' => 'print2', 'color' => 'red'],
            ['label' => 'Repair Cost', 'value' => 'repair', 'color' => 'red'],
            ['label' => 'Service Invoice', 'value' => 'invoice', 'color' => 'red'],
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select
        'PDFM' as print,
        '0' as reporttype,
        '' as radiosjafti,
        '' as prepared,
        '' as approved,
        '' as received
        "
        );
    }

    public function report_default_query($config)
    {

        $trno = $config['params']['dataid'];
        $query = "select head.trno,head.docno,date(head.dateid) as dateid,jb.code,jb.description,client.client,head.clientname,client.bstyle,
        cmake.carname as vehicle,cvh.mileage,client.tin,stock.disc,agent.clientname as agent,
        ifnull(sum(am.cost),0) as labor,head.yourref,head.ourref,head.terms,head.address,
        ifnull((select sum(amt) from lastock as s
        where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line),0) as stocks,
        ifnull(sum(am.cost), 0) + ifnull((select sum(amt) from lastock as s
        where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line), 0) as total_mechanic
        from amjobs as jobs
        left join lahead as head on head.trno = jobs.trno
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client = head.client
        left join client as agent on agent.client = head.agent
        left join cmake on cmake.id=head.carid
        left join amtask as am on am.jobline = jobs.line and am.trno = jobs.trno
        left join jobtask as jb on jb.line = am.laborline
        left join cvehicle as cvh on cvh.clientid=client.clientid
        where head.doc = 'AM' and head.trno = $trno
        group by head.trno,head.docno,date(head.dateid),jb.code,jb.description,client.client,head.clientname,client.bstyle,cmake.carname,
        jobs.line,am.line,cvh.mileage,head.yourref,head.ourref,head.terms,head.address,client.tin,stock.disc,agent.clientname
        union all
        select head.trno,head.docno,date(head.dateid) as dateid,jb.code,jb.description,client.client,head.clientname,client.bstyle,
        cmake.carname as vehicle,cvh.mileage,client.tin,stock.disc,agent.clientname as agent,
        ifnull(sum(am.cost),0) as labor,head.yourref,head.ourref,head.terms,head.address,
        ifnull((select sum(amt) from glstock as s
        where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line),0) as stocks,
        ifnull(sum(am.cost), 0) + ifnull((select sum(amt) from glstock as s
        where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line), 0) as total_mechanic
        from hamjobs as jobs
        left join glhead as head on head.trno = jobs.trno
        left join glstock as stock on stock.trno=head.trno
        left join client on client.clientid = head.clientid
        left join client as agent on agent.clientid = head.agentid
        left join cmake on cmake.id=head.carid
        left join hamtask as am on am.jobline = jobs.line and am.trno = jobs.trno
        left join jobtask as jb on jb.line = am.laborline
        left join cvehicle as cvh on cvh.clientid=client.clientid
        where head.doc = 'AM' and head.trno = $trno
        group by head.trno,head.docno,date(head.dateid),jb.code,jb.description,client.client,head.clientname,client.bstyle,cmake.carname,
        jobs.line,am.line,cvh.mileage,head.yourref,head.ourref,head.terms,head.address,client.tin,stock.disc,agent.clientname";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        $print = $params['params']['dataparams']['print'];
        $reporttype = $params['params']['dataparams']['radiosjafti'];

        switch ($print) {
            case 'PDFM':
                switch ($reporttype) {
                    case 'sales': //NEW SALES INVOICE
                        return $this->sales_layout_PDF($params, $data);
                        break;
                    case 'service': //NEW SERVICE INVOICE
                        return $this->service_layout_PDF($params, $data);
                        break;
                    case 'print1': //PRINT OUT 1 
                        return $this->print1_layout_PDF($params, $data);
                        break;
                    case 'print2': //PRINT OUT 2
                        return $this->print2_layout_PDF($params, $data);
                        break;
                    case 'repair': //REPAIR COST
                        return $this->repair_layout_PDF($params, $data);
                        break;
                    case 'invoice': //SERVICE INVOICE
                        return $this->invoice_layout_PDF($params, $data);
                        break;
                }
                break;
        }
    }

    private function formatQty($number)
    {
        return rtrim(rtrim(number_format($number, 2), '0'), '.');
    }

    public function sales_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = "13";
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

        $date = '';
        $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';



        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, 'B', 16);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'C');
        PDF::MultiCell(0, 0, 'TELS : ' . strtoupper($headerdata[0]->tel), '', 'C');
        PDF::MultiCell(0, 0, 'VAT Reg. TIN : ' . strtoupper($headerdata[0]->tin), '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, 'B', 13);
        PDF::MultiCell(450, 0, 'SALES INVOICE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(190, 0, "No. : " . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetLineWidth(0.5);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, 'SOLD TO ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(360, 20, ': ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'DATE ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ': ' . (isset($data[0]['dateid']) ? date('M-d-Y', strtotime($data[0]['dateid'])) : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, 'TIN/SC-TIN ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(360, 20, ': ' . (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'P.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ':' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 20, 'OSCA/PWD  ID No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(310, 20, ': ' . (isset($data[0]['disc']) ? $data[0]['disc'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'J.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ': ' . (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(90, 20, 'Business Style ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(350, 20, ': ' . (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'Terms ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ': ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 20, 'Address ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(380, 20, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'Salesman', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ': ' . (isset($data[0]['agent']) ? $data[0]['agent'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(720, 7, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetY(272);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(35, 20, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', 12);
        PDF::MultiCell(55, 20, 'TERMS : ', '', 'L', false, 0);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(630, 20, 'Cash unless otherwise arranged. 12% interest per annum is to be  charged on all  accounts overdue plus', '', 'J', false);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(720, 20, " 25% on said amount for attorney's fees and cost of collection. The parties expressly submit themselves to the jurisdiction of ", '', 'J', false);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(720, 20, "Quezon City in any legal action arising out of this transaction without in any way attemting to divert jurisdiction from any", '', 'J', false);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(720, 20, " other court or courts. Good travel at the risk of the buyer.", '', 'L', false);

        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(720, 0, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetLineWidth(0.5);

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(100, 0, "QUANTITY", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(70, 0, "UNIT", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(270, 0, "DESCRIPTION OF ARTICLES", 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(120, 0, "UNIT PRICE", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(120, 0, "AMOUNT", 'B', 'C', false);

        PDF::SetLineWidth(0.2);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');
    }

    public function sales_layout_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->sales_header_PDF($params, $data);
        $counted = count($data);
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $maxRowsPerPage = 13;

        $sales1 = 0;
        $sales2 = 0;
        $sales3 = 0;
        $vat = 0;
        $netVatamt = 0;
        $lessVat = 0;
        $lessDisc = 0;
        $addVat = 0;
        $lessWithholdingTax = 0;
        $totalAmtDue = 0;

        // PDF::SetY(215);
        // for ($i = 0; $i < ($counted); $i++) {
        //     $maxrow = 1;

        //     $uom = $data[$i]['uom'];
        //     $itemname = $data[$i]['itemname'];
        //     $qty = number_format($data[$i]['qty'], 2);
        //     $amt = number_format($data[$i]['amt'], 2);
        //     $ext = number_format($data[$i]['ext'], 2);

        //     $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        //     $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0); //23
        //     $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        //     $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        //     $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        //     $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
        //     for ($r = 0; $r < $maxrow; $r++) {

        //         if ($rowPerPage == $maxRowsPerPage) {
        //             break 2;
        //         }
        //         $rowPerPage++;
        //         PDF::SetFont($font, '', $fontsize);
        //         PDF::MultiCell(100,  20, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(70,  20, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(270, 20, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(120,  20, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //         PDF::MultiCell(120,  20, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        //     }
        //     $totalext += $data[$i]['ext'];
        //     $totaldisc += $data[$i]['disc'];
        // }
        // $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
        // $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

        // if ($vattype == 'VATABLE') {
        //     $vat = $totalext / 1.12 * 0.12;
        //     $netVat = $totalext / 1.12;
        //     $lessWithholdingTax = $netVat * ($ewtrate / 100);
        //     $sales1 = $totalext;
        // } else if ($vattype == 'NON-VATABLE') {
        //     $vat = 0;
        //     $sales2 = $totalext;
        // } else if ($vattype == 'ZERO-RATED') {
        //     $vat = 0;
        //     $sales3 = $totalext;
        // }

        // $lessVat = $vat;
        // $addVat = $lessVat;
        // $lessDisc = $totaldisc;
        // $netVatamt = $totalext - $lessVat;
        // $lessWithholdingTax = 0;
        // $amountDue = $netVatamt - $lessDisc;
        // $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

        // //Left & Right Side 
        // PDF::SetY(650);
        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(170, 20, 'Total Sales (Vat Inclusive)', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(170, 20, 'Less : VAT', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, 'VATable Sales', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(170, 20, 'Amount : NET of VAT', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, 'VAT-Exempt Sales', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(170, 20, 'Less : SC/PWD Discount', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, 'Zero Rated Sales', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(170, 20, 'Amount Due ', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $amountDue != 0 ? number_format($amountDue, 2) : '', '', 'R', false);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, 'VAT Amount', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(170, 20, 'Add Vat ', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(120, 20, '', '', 'R', false, 0);
        // PDF::MultiCell(170, 20, 'TOTAL AMOUNT DUE', '', 'L', false, 0);
        // PDF::MultiCell(75, 20, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

        // PDF::SetY(770);
        // PDF::SetLineWidth(2.0);
        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(720, 20, '', 'B', 'R', false, 0);
        // PDF::SetLineWidth(0.2);

        // PDF::SetY(795);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 20, 'R.C. No.', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(50, 20, 'C - 13', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(260, 20, '', '', 'L', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 20, 'Date', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(50, 20, 'Date', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(260, 20, '', '', 'L', false);
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 20, 'Place', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(50, 20, 'Place', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(260, 20, '', 'B', 'L', false);

        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(50, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(50, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(160, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        // PDF::MultiCell(260, 20, "Cashier's/Authorized Representative", '', 'C', false);
        // PDF::SetY(880);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(130, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(460, 0, 'THIS SALES INVOICE  SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP', 'B', 'C', false, 0);
        // PDF::MultiCell(130, 0, '', '', 'L', false);

        // PDF::SetY(920);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 0, 'LICENSE', '', 'L', false, 0);
        // PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        // PDF::MultiCell(20, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(50, 0, 'MODEL', '', 'L', false, 0);
        // PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        // PDF::MultiCell(70, 0, 'SUB MODEL', '', 'L', false, 0);
        // PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        // PDF::MultiCell(50, 0, '', '', 'L', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 0, 'YEAR', '', 'L', false, 0);
        // PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        // PDF::MultiCell(20, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(50, 0, 'MAKE', '', 'L', false, 0);
        // PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        // PDF::MultiCell(70, 0, 'MILEAGE', '', 'L', false, 0);
        // PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        // PDF::MultiCell(50, 0, '', '', 'L', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function service_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = "13";
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

        $date = '';
        $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';



        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, 'B', 16);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'C');
        PDF::MultiCell(0, 0, 'TELS : ' . strtoupper($headerdata[0]->tel), '', 'C');
        PDF::MultiCell(0, 0, 'VAT Reg. TIN : ' . strtoupper($headerdata[0]->tin), '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, 'B', 13);
        PDF::MultiCell(520, 0, 'SERVICE INVOICE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(120, 0, "No. : AS10020954", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetLineWidth(0.5);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, 'SOLD TO ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(360, 20, ' : ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'DATE ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ' : ' . (isset($data[0]['dateid']) ? date('M-d-Y', strtotime($data[0]['dateid'])) : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, 'TIN/SC-TIN ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(360, 20, ' : ' . (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'P.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ' : ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 20, 'OSCA/PWD  ID No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(310, 20, ' : SAMPLE' . (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'J.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ' : SAMPLE' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(90, 20, 'Business Style ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(350, 20, ' : SAMPLE' . (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'Terms ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ' : SAMPLE' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 20, 'Address ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(380, 20, ' : SAMPLE' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, 'Salesman', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(200, 20, ' : SAMPLE' . (isset($data[0]['agent']) ? $data[0]['agent'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(720, 7, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetY(272);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(35, 20, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', 12);
        PDF::MultiCell(55, 20, 'TERMS : ', '', 'L', false, 0);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(630, 20, 'Cash unless otherwise arranged. 12% interest per annum is to be  charged on all  accounts overdue plus', '', 'J', false);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(720, 20, " 25% on said amount for attorney's fees and cost of collection. The parties expressly submit themselves to the jurisdiction of ", '', 'J', false);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(720, 20, "Quezon City in any legal action arising out of this transaction without in any way attemting to divert jurisdiction from any", '', 'J', false);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(720, 20, " other court or courts. Good travel at the risk of the buyer.", '', 'L', false);

        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(720, 0, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetLineWidth(0.5);

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(100, 0, "QUANTITY", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(70, 0, "UNIT", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(270, 0, "DESCRIPTION OF ARTICLES", 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(120, 0, "UNIT PRICE", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(120, 0, "AMOUNT", 'B', 'C', false);

        PDF::SetLineWidth(0.2);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');
    }

    public function service_layout_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->service_header_PDF($params, $data);
        $counted = count($data);
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $maxRowsPerPage = 13;

        $sales1 = 0;
        $sales2 = 0;
        $sales3 = 0;
        $vat = 0;
        $netVatamt = 0;
        $lessVat = 0;
        $lessDisc = 0;
        $addVat = 0;
        $lessWithholdingTax = 0;
        $totalAmtDue = 0;

        PDF::SetY(215);
        for ($i = 0; $i < ($counted); $i++) {
            $maxrow = 1;

            $uom = $data[$i]['uom'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $amt = number_format($data[$i]['amt'], 2);
            $ext = number_format($data[$i]['ext'], 2);

            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0); //23
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
            $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {

                if ($rowPerPage == $maxRowsPerPage) {
                    break 2;
                }
                $rowPerPage++;
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(100,  20, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  20, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(270, 20, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(120,  20, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(120,  20, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            }
            $totalext += $data[$i]['ext'];
            $totaldisc += $data[$i]['disc'];
        }
        $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
        $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

        if ($vattype == 'VATABLE') {
            $vat = $totalext / 1.12 * 0.12;
            $netVat = $totalext / 1.12;
            $lessWithholdingTax = $netVat * ($ewtrate / 100);
            $sales1 = $totalext;
        } else if ($vattype == 'NON-VATABLE') {
            $vat = 0;
            $sales2 = $totalext;
        } else if ($vattype == 'ZERO-RATED') {
            $vat = 0;
            $sales3 = $totalext;
        }

        $lessVat = $vat;
        $addVat = $lessVat;
        $lessDisc = $totaldisc;
        $netVatamt = $totalext - $lessVat;
        $lessWithholdingTax = 0;
        $amountDue = $netVatamt - $lessDisc;
        $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

        //Total Amount Due
        PDF::SetY(765);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 0, '', '', 'R', false, 0);
        PDF::MultiCell(120, 0, '', '', 'L', false, 0);
        PDF::MultiCell(75, 0, '', '', 'R', false, 0);
        PDF::MultiCell(120, 0, '', '', 'R', false, 0);
        PDF::MultiCell(170, 0, 'TOTAL AMOUNT DUE', '', 'L', false, 0);
        PDF::MultiCell(75, 0, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

        PDF::SetY(770);
        PDF::SetLineWidth(2.0);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 20, '', 'B', 'R', false, 0);
        PDF::SetLineWidth(0.2);

        PDF::SetY(795);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 20, 'R.C. No.', '', 'L', false, 0);
        PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(50, 20, 'C - 13', '', 'L', false, 0);
        PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(260, 20, '', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 20, 'Date', '', 'L', false, 0);
        PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(50, 20, 'Date', '', 'L', false, 0);
        PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(260, 20, '', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 20, 'Place', '', 'L', false, 0);
        PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(50, 20, 'Place', '', 'L', false, 0);
        PDF::MultiCell(160, 20, ':', 'B', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(260, 20, '', 'B', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 20, '', '', 'L', false, 0);
        PDF::MultiCell(160, 20, '', '', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(50, 20, '', '', 'L', false, 0);
        PDF::MultiCell(160, 20, '', '', 'L', false, 0);
        PDF::MultiCell(20, 20, '', '', 'L', false, 0);
        PDF::MultiCell(260, 20, "Cashier's/Authorized Representative", '', 'C', false);
        PDF::SetY(880);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(230, 0, '', '', 'L', false, 0);
        PDF::MultiCell(260, 0, 'THIS DOCUMENT IS NOT CLAIMING INPUT TAXES', 'B', 'C', false, 0);
        PDF::MultiCell(230, 0, '', '', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 0, '', '', 'L', false, 0);
        PDF::MultiCell(460, 0, 'THIS SALES INVOICE  SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP', 'B', 'C', false, 0);
        PDF::MultiCell(130, 0, '', '', 'L', false);

        PDF::SetY(920);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'LICENSE', '', 'L', false, 0);
        PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        PDF::MultiCell(20, 0, '', '', 'L', false, 0);
        PDF::MultiCell(50, 0, 'MODEL', '', 'L', false, 0);
        PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        PDF::MultiCell(70, 0, 'SUB MODEL', '', 'L', false, 0);
        PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'YEAR', '', 'L', false, 0);
        PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        PDF::MultiCell(20, 0, '', '', 'L', false, 0);
        PDF::MultiCell(50, 0, 'MAKE', '', 'L', false, 0);
        PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        PDF::MultiCell(70, 0, 'MILEAGE', '', 'L', false, 0);
        PDF::MultiCell(160, 0, ':', '', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function print1_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = "13";
        $fontsize2 = "15";
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        $date = '';
        $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

        PDF::SetY(45);
        PDF::SetFont($fontbold, 'B', 16);
        PDF::MultiCell(260, 20, strtoupper($headerdata[0]->name), '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(460, 20, "", '', 'R', false);

        PDF::SetY(30);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 25, "", '', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, "", 'LT', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(105, 25, "SALES INVOICE", 'T', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(160, 25, " : AS10020954", 'RT', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 25, "", '', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, "", 'L', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(105, 25, "DATE", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(160, 25, " : AS10020954", 'R', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 25,  strtoupper($headerdata[0]->address), '', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, "", 'L', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(105, 25, "PAGE", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(160, 25, " : AS10020954", 'R', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 25,  strtoupper($headerdata[0]->tel), '', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, "", 'LB', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(105, 25, "FORM", 'B', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(160, 25, " :", 'BR', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // left, top, right, bottom

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 25, 'TIN: ' . strtoupper($headerdata[0]->tin), '', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(270, 25, "", '', 'L', false);

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 25, '', 'LT', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, 'CUSTOMER', 'T', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(315, 25, ':', 'T', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 25, '', 'T', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, '', 'LT', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, "LICENSED", 'T', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 25, ':', 'RT', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, 'ADDRESS', '', 'LB', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(315, 25, ':', '', 'LB', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 25, '', '', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, "YEAR", '', 'LB', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 25, ':', 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(430, 25, '', '', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, "MAKE", '', 'LB', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 25, ':', 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(430, 25, '', '', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, "MODEL", '', 'LB', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 25, ':', 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, 'PHONE', '', 'LB', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(315, 25, ':', '', 'LB', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 25, '', '', '', false, 0, '',  '');
        PDF::MultiCell(5, 25, '', 'L', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, "SUB MODEL", '', 'LB', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 25, ':', 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 25, '', 'LB', 'L', false, 0, '',  '');
        PDF::MultiCell(430, 25, '', 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(5, 25, '', 'LB', 'L', false, 0, '',  '');
        PDF::MultiCell(80, 25, "MILEAGE", 'B', 'LB', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 25, ':', 'RB', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // left, top, right, bottom

        PDF::MultiCell(0, 0, "\n");

        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, '', 'LT', 'C', false, 0, '',  '');
        PDF::SetFillColor(200, 200, 200); // Light gray 
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(340, 20, 'PARTS', 'T', 'C', true, 0, '',  '');
        PDF::SetFillColor(255, 255, 255); // White
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, '', 'RT', 'C', false, 0, '',  '');
        PDF::MultiCell(10, 20, '', 'T', 'C', false, 0, '',  '');
        PDF::SetFillColor(200, 200, 200); // Light gray 
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(340, 20, 'LABOR', 'T', 'C', true, 0, '',  '');
        PDF::SetFillColor(255, 255, 255); // White
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, '', 'RT', 'C', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // reset padding

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 20, "QTY", 'L', 'C', false, 0);
        PDF::MultiCell(100, 20, "PART#", '', 'C', false, 0, '',  '');
        PDF::MultiCell(120, 20, "DESCRIPTION", '', 'C', false, 0, '',  '');
        PDF::MultiCell(80, 20, "PRICE", '', 'C', false, 0);
        PDF::MultiCell(10, 20, "", 'R', '', false, 0, '',  '');
        PDF::MultiCell(10, 20, "", '', '', false, 0, '',  '');
        PDF::MultiCell(50, 20, "OP", '', 'C', false, 0, '',  '');
        PDF::MultiCell(100, 20, "TECH", '', 'C', false, 0, '',  '');
        PDF::MultiCell(120, 20, "DESCRIPTION", '', 'C', false, 0, '',  '');
        PDF::MultiCell(80, 20, "PRICE", 'R', 'C', false);

        // PDF::MultiCell(0, 0, "\n");
        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(700, 0, '', 'B');
    }

    public function print1_layout_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        $fontsize2 = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->print1_header_PDF($params, $data);
        $counted = count($data);
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $maxRowsPerPage = 13;
        $rowHeight = 0;

        $sales1 = 0;
        $sales2 = 0;
        $sales3 = 0;
        $vat = 0;
        $netVatamt = 0;
        $lessVat = 0;
        $lessDisc = 0;
        $addVat = 0;
        $lessWithholdingTax = 0;
        $totalAmtDue = 0;

        PDF::SetY(362);
        for ($i = 0; $i < ($counted); $i++) {
            $maxrow = 1;

            $uom = $data[$i]['uom'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $amt = number_format($data[$i]['amt'], 2);
            $ext = number_format($data[$i]['ext'], 2);

            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0); //23
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
            $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {
                if ($rowPerPage == $maxRowsPerPage) {
                    break 2;
                }
                $rowPerPage++;

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(100,  20, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  20, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(270, 20, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(120,  20, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(120,  20, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            }
            $totalext += $data[$i]['ext'];
            $totaldisc += $data[$i]['disc'];
        }

        $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
        $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

        if ($vattype == 'VATABLE') {
            $vat = $totalext / 1.12 * 0.12;
            $netVat = $totalext / 1.12;
            $lessWithholdingTax = $netVat * ($ewtrate / 100);
            $sales1 = $totalext;
        } else if ($vattype == 'NON-VATABLE') {
            $vat = 0;
            $sales2 = $totalext;
        } else if ($vattype == 'ZERO-RATED') {
            $vat = 0;
            $sales3 = $totalext;
        }

        $lessVat = $vat;
        $addVat = $lessVat;
        $lessDisc = $totaldisc;
        $netVatamt = $totalext - $lessVat;
        $lessWithholdingTax = 0;
        $amountDue = $netVatamt - $lessDisc;
        $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

        $rowHeight = ($maxRowsPerPage) * 30;
        if ($rowHeight > 0) {
            PDF::MultiCell(360, $rowHeight, '', 'L', '', false, 0);
            PDF::MultiCell(360, $rowHeight, '', 'LR', '', false);
        }

        PDF::SetY(745.5);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetY(765);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', 'LTB', 'LB', false, 0);
        PDF::MultiCell(710, 25, 'RECOMMENDED REPAIR', 'RBT', 'LB', false);

        PDF::SetCellPaddings(0, 0, 0, 0);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetY(800); // Left side of the footer
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 20, '', 'LT', 'LB', false, 0);
        PDF::MultiCell(410, 20, 'TERMS AND PROVISIONS : ', 'RT', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "1. Customer authorizes  DAS or its employees to operate vehicle for purposes of testing , inspection and/or delivery at customer's risk.", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "2. Customer acknowledges an express mechanic's lien to secure the amount of repairs indicated thereto.", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "3. DAS will not be responsble or liable for loss or damage to vehicle or articles left in case of fire, theft, accident, flood, typhoon, earthquake", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "and/or other causes beyond the comapany's control.", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "4. Customer agrees to pay interest at the rate of 2% per month on all accunts not paid when due.", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "5. Vehicle not claimed and withdrawn from the company's premises within five(5) days from date of completion will be charged storage of", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, " P 60.00 per day untill withdrawn.", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "6. Orall agreements, representations or promises not incorporated herein are unauthorized and therefore not binding.", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, "7. In case of litigation for non-payment of this repair order invoice, customer agrees to submit himself to the jurisdiction of the courts of", 'R', 'L', false);

        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 10, " Manila, Quezon City or Makati City at the option of the comapany and to pay 25% for attorney's fees, minimum of P 500.00 plus court costs.", 'R', 'L', false);

        PDF::MultiCell(420, 0, "\n", 'RL', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(50, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(130, 10, "CUSTOMER CONFORME : ", '', 'L', false, 0);
        PDF::MultiCell(160, 10, '', 'B', 'L', false, 0);
        PDF::MultiCell(80, 10, "", 'R', 'L', false);

        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(50, 10, '', 'L', 'L', false, 0);
        PDF::MultiCell(130, 10, '', '', 'L', false, 0);
        PDF::MultiCell(160, 10, "PrintName and Signature", '', 'C', false, 0);
        PDF::MultiCell(80, 10, "", 'R', 'L', false);

        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(50, 10, '', 'LB', 'L', false, 0);
        PDF::MultiCell(130, 10, '', 'B', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'B', 'C', false, 0);
        PDF::MultiCell(80, 10, "", 'RB', 'L', false);

        // PDF::SetFont($fontbold, '', 10);
        // PDF::MultiCell(50, 10, '', 'L', 'L', false, 0);
        // PDF::MultiCell(20, 10, '', '', 'L', false, 0);
        // PDF::MultiCell(100, 10, '', 'B', 'L', false, 0);
        // PDF::MultiCell(250, 10, "", 'R', 'L', false);


        PDF::SetCellPaddings(0, 0, 0, 0); // End of left side of the footer

        PDF::SetXY(460, 800); // RightSide of the footer
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', 'T', 'L', false, 0);
        PDF::MultiCell(70, 25, 'LABOR ', 'T', 'L', false, 0);
        PDF::MultiCell(10, 25,  ' : ', 'T', 'L', false, 0);
        PDF::MultiCell(210, 25, '', 'RT', 'L', false);

        PDF::SetXY(460, 820);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(70, 25, 'PARTS ', '', 'L', false, 0);
        PDF::MultiCell(10, 25,  ' : ', '', 'L', false, 0);
        PDF::MultiCell(210, 25, '', 'R', 'L', false);

        PDF::SetXY(460, 840);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(70, 25, '', '', 'L', false, 0);
        PDF::MultiCell(220, 25, '', 'R', 'R', false);

        PDF::SetXY(460, 855);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(70, 25, 'SUPPLIES ', '', 'L', false, 0);
        PDF::MultiCell(10, 25,  ' : ', '', 'L', false, 0);
        PDF::MultiCell(210, 25, '', 'R', 'L', false);

        PDF::SetXY(460, 875);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(70, 25, 'TAX ', '', 'L', false, 0);
        PDF::MultiCell(10, 25,  ' : ', '', 'L', false, 0);
        PDF::MultiCell(210, 25, '', 'R', 'L', false);

        PDF::SetXY(460, 900);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(70, 25, '', '', 'L', false, 0);
        PDF::MultiCell(220, 25, '', 'R', 'R', false);

        PDF::SetXY(460, 915);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(70, 25, 'TOTAL ', '', 'L', false, 0);
        PDF::MultiCell(10, 25,  ' : ', '', 'L', false, 0);
        PDF::MultiCell(210, 25, '', 'R', 'L', false);

        PDF::SetXY(460, 935);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', 'B', 'L', false, 0);
        PDF::MultiCell(70, 25, '', 'B', 'L', false, 0);
        PDF::MultiCell(220, 25, '', 'RB', 'R', false);

        PDF::SetCellPaddings(0, 0, 0, 0);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function print2_PDF_header($params, $data) // 746
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

        $font = "";
        $fontbold = "";
        $fontvar = 8;
        $fontsize = 9;
        $fonttitle = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('l', 'LETTER');
        PDF::SetMargins(23, 23);
        PDF::MultiCell(0, 0, "\n", '', '');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', $fonttitle);
        PDF::MultiCell(373, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(373, 0, 'SALES INVOICE#: ASI0020954', '', 'R', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
        PDF::MultiCell(300, 20, 'TeleFax: ' . strtoupper($headerdata[0]->tel), '', 'L', false, 0);
        PDF::MultiCell(300, 20, 'TIN : ' . strtoupper($headerdata[0]->tin), '', 'L', false);

        PDF::SetFont($font, '', $fontvar);
        PDF::SetCellPaddings(0, 8, 0, 0); // left, top, right, bottom
        PDF::MultiCell(400, 20, 'CUSTOMER  ', 'B', 'L', false, 0);
        PDF::MultiCell(346, 20, 'RECEIVED  ', 'B', 'L', false);

        PDF::MultiCell(400, 20, 'ADDRESS  ', 'B', 'L', false, 0);
        PDF::MultiCell(346, 20, 'PHONE  ', 'B', 'L', false);

        PDF::MultiCell(105, 20, 'LICENSE  ', 'B', 'L', false, 0);
        PDF::MultiCell(105, 20, 'YEAR  ', 'B', 'L', false, 0);
        PDF::MultiCell(105, 20, 'MAKE  ', 'B', 'L', false, 0);
        PDF::MultiCell(105, 20, 'MODEL  ', 'B', 'L', false, 0);
        PDF::MultiCell(105, 20, 'SUBMODEL  ', 'B', 'L', false, 0);
        PDF::MultiCell(105, 20, 'MILEAGE  ', 'B', 'L', false, 0);
        PDF::MultiCell(111, 20, 'YEAR  ', 'B', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
    }
    public function print2_layout_PDF($params, $data) // 746
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fonttable = 8;
        $fontrows = 7;

        $maxRowsPerPage = 30;


        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
        }
        $this->print2_PDF_header($params, $data);

        PDF::MultiCell(746, 0, '', 'B', '', false);
        $rowHeight = ($maxRowsPerPage) * 10;
        if ($rowHeight > 0) {
            PDF::MultiCell(373, $rowHeight, '', 'L', '', false, 0);
            PDF::MultiCell(373, $rowHeight, '', 'LR', '', false);
        }
        PDF::MultiCell(746, 0, '', 'T', '', false);

        // Table
        PDF::SetFillColor(192, 192, 192);
        PDF::SetY(150);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 3, 0, 0);
        PDF::MultiCell(5, 15, '', '', 'L', false, 0);
        PDF::MultiCell(48, 15, 'QTY', '', 'C', true, 0);
        PDF::MultiCell(220, 15, 'PART NUMBER / DESCRIPTION', '', 'L', true, 0);
        PDF::MultiCell(95, 15, 'PRICE       ', '', 'R', true, 0);
        PDF::MultiCell(10, 15, '', '', 'L', false, 0);
        PDF::MultiCell(48, 15, 'QTY', '', 'C', true, 0);
        PDF::MultiCell(220, 15, 'REAPAIR INSTRUCTION', '', 'L', true, 0);
        PDF::MultiCell(95, 15, 'PRICE       ', '', 'R', true, 0);
        PDF::MultiCell(5, 15, '', '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::SetFillColor(255, 255, 255);

        PDF::SetY(450);
        // PDF::MultiCell(746, 0, '', 'LRT', '', false);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(746, 0, '    RECOMMENDED', 'LRT', '', false);
        PDF::MultiCell(746, 0, '    REPAIRS: ', 'LRB', '', false);

        PDF::SetY(481);
        // PDF::MultiCell(560, 0, ' ', 'LR', '', false, 0);
        // PDF::MultiCell(186, 0, '', '', '', false);
        PDF::MultiCell(560, 0, 'TERMS AND PROVISIONS', 'LRT', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        PDF::SetFont($font, '', 6);

        PDF::MultiCell(560, 0, ' 1. Customer authorizes DAS or its employees to operate vehicle for purposes of testing, inspection and/or delivery at customer’s risk', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(560, 0, ' 2. Customer acknowledges an express mechanic’s lien to secure the amount of repairs indicated thereto.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(560, 0, ' 3. DAS will not be responsible or liable for loss or damage to vehicle or articles left in case of fire, theft, accident, flood, typhoon, earthquake and/or other causes beyond the company’s control.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(560, 0, ' 4. Customer agrees to pay interest at the of 2% per month on all accounts not paid when due.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(560, 0, ' 5. Vehicle not claimed and withdrawn from the company’s premises within five (5) days from date of completion will be charged storage of P 70.00 per day until withdrawn.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(560, 0, ' 6. Oral agreements, representations or promises not incorporated herein are unauthorized and therefore not binding.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(560, 0, " 7. In case of litigation for non-payment of this repair order invoice, customer agrees to submit himself to the jurisdiction of the courts of Manila, Quezon City or Makati City at the option of the company \n. and to pay 25% for attorney’s fees, minimum of P 500.00 plus cour", 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(746, 0, '', 'L', '', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(30, 0, '', 'L', '', false, 0);
        PDF::MultiCell(100, 0, 'CUSTOMER CONFORME :', '', '', false, 0);
        PDF::MultiCell(200, 0, '', 'B', '', false, 0);
        PDF::MultiCell(230, 0, '', 'R', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(130, 0, '', 'L', 'C', false, 0);
        PDF::MultiCell(200, 0, 'PrintName and Signature', '', 'C', false, 0);
        PDF::MultiCell(230, 0, '', 'R', 'C', false);

        PDF::MultiCell(560, 0, '', 'LRB', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false);

        PDF::SetXY(600, 485);
        PDF::MultiCell(560, 18, 'LABOR', '', '', false);
        PDF::SetX(600);
        PDF::MultiCell(186, 18, 'PARTS', '', '', false);
        PDF::SetX(600);
        PDF::MultiCell(186, 18, 'SUBLET', '', '', false);
        PDF::SetX(600);
        PDF::MultiCell(186, 18, 'SUPPLIES', '', '', false);
        PDF::SetX(600);
        PDF::MultiCell(186, 18, 'TAX', '', '', false);
        PDF::SetX(600);
        PDF::MultiCell(186, 18, 'TOTAL', '', '', false);

        PDF::SetCellPaddings(0, 0, 0, 0); // left, top, right, bottom


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function repair_header_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(20, 20);

        $date = '';
        $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

        PDF::SetY(103);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(330, 0, $date, '', 'R', false);

        PDF::SetY(125);
        PDF::MultiCell(50, 18, '', '', 'R', false, 0);
        PDF::MultiCell(245, 18, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
        PDF::MultiCell(50, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'R', false);

        PDF::SetY(137);
        PDF::MultiCell(90, 18, '', '', 'R', false, 0);
        PDF::MultiCell(255, 18, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false);

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(50, 10, '', '', 'R', false, 0);
        PDF::MultiCell(295, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);
        $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
        $arr_address = $this->reporter->fixcolumn([$address], '56', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(90, 8, '', '', 'R', false, 0);
            PDF::MultiCell(250, 8, (isset($arr_address[$r]) ? $arr_address[$r] : ''), 'B', 'L', false);
        }
    }

    public function repair_layout_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->repair_header_PDF($params, $data);
        $counted = count($data);
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $maxRowsPerPage = 13;

        $sales1 = 0;
        $sales2 = 0;
        $sales3 = 0;
        $vat = 0;
        $netVatamt = 0;
        $lessVat = 0;
        $lessDisc = 0;
        $addVat = 0;
        $lessWithholdingTax = 0;
        $totalAmtDue = 0;

        PDF::SetY(215);
        for ($i = 0; $i < ($counted); $i++) {
            $maxrow = 1;

            $uom = $data[$i]['uom'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $amt = number_format($data[$i]['amt'], 2);
            $ext = number_format($data[$i]['ext'], 2);

            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0); //23
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
            $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {

                if ($rowPerPage == $maxRowsPerPage) {
                    break 2;
                }
                $rowPerPage++;
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(10,  12.8, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(30,  12.8, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(166, 12.8, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(33,  12.8, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(38,  12.8, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(68,  12.8, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(5,  12.8, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
            }
            $totalext += $data[$i]['ext'];
            $totaldisc += $data[$i]['disc'];
        }
        $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
        $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

        if ($vattype == 'VATABLE') {
            $vat = $totalext / 1.12 * 0.12;
            $netVat = $totalext / 1.12;
            $lessWithholdingTax = $netVat * ($ewtrate / 100);
            $sales1 = $totalext;
        } else if ($vattype == 'NON-VATABLE') {
            $vat = 0;
            $sales2 = $totalext;
        } else if ($vattype == 'ZERO-RATED') {
            $vat = 0;
            $sales3 = $totalext;
        }

        $lessVat = $vat;
        $addVat = $lessVat;
        $lessDisc = $totaldisc;
        $netVatamt = $totalext - $lessVat;
        $lessWithholdingTax = 0;
        $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

        //Right Side
        PDF::SetFont($fontbold, '', 9);
        PDF::SetXY(288, 395);
        PDF::MultiCell(75, 4, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 2, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
        PDF::SetXY(288, 460);
        PDF::MultiCell(75, 4, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);


        //Left Side
        PDF::SetXY(135, 397);
        PDF::MultiCell(65, 4, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
        PDF::SetX(135);
        PDF::MultiCell(65, 4, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
        PDF::SetX(135);
        PDF::MultiCell(65, 4, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
        PDF::SetX(135);
        PDF::MultiCell(65, 4, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
        PDF::SetCellPaddings(0, 9, 0, 0);
        PDF::SetX(135);
        PDF::MultiCell(65, 20, '', '', 'R', false);
        PDF::SetCellPaddings(0, 12, 0, 0);
        PDF::SetX(135);
        PDF::MultiCell(65, 20, '', '', 'R', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function invoice_header_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(20, 20);

        $date = '';
        $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

        PDF::SetY(103);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(330, 0, $date, '', 'R', false);

        PDF::SetY(125);
        PDF::MultiCell(50, 18, '', '', 'R', false, 0);
        PDF::MultiCell(245, 18, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
        PDF::MultiCell(50, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'R', false);

        PDF::SetY(137);
        PDF::MultiCell(90, 18, '', '', 'R', false, 0);
        PDF::MultiCell(255, 18, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false);

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(50, 10, '', '', 'R', false, 0);
        PDF::MultiCell(295, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);
        $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
        $arr_address = $this->reporter->fixcolumn([$address], '56', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(90, 8, '', '', 'R', false, 0);
            PDF::MultiCell(250, 8, (isset($arr_address[$r]) ? $arr_address[$r] : ''), 'B', 'L', false);
        }
    }

    public function invoice_layout_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->invoice_header_PDF($params, $data);
        $counted = count($data);
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $maxRowsPerPage = 13;

        $sales1 = 0;
        $sales2 = 0;
        $sales3 = 0;
        $vat = 0;
        $netVatamt = 0;
        $lessVat = 0;
        $lessDisc = 0;
        $addVat = 0;
        $lessWithholdingTax = 0;
        $totalAmtDue = 0;

        PDF::SetY(215);
        for ($i = 0; $i < ($counted); $i++) {
            $maxrow = 1;

            $uom = $data[$i]['uom'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $amt = number_format($data[$i]['amt'], 2);
            $ext = number_format($data[$i]['ext'], 2);

            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0); //23
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
            $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {

                if ($rowPerPage == $maxRowsPerPage) {
                    break 2;
                }
                $rowPerPage++;
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(10,  12.8, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(30,  12.8, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(166, 12.8, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(33,  12.8, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(38,  12.8, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(68,  12.8, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(5,  12.8, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
            }
            $totalext += $data[$i]['ext'];
            $totaldisc += $data[$i]['disc'];
        }
        $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
        $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

        if ($vattype == 'VATABLE') {
            $vat = $totalext / 1.12 * 0.12;
            $netVat = $totalext / 1.12;
            $lessWithholdingTax = $netVat * ($ewtrate / 100);
            $sales1 = $totalext;
        } else if ($vattype == 'NON-VATABLE') {
            $vat = 0;
            $sales2 = $totalext;
        } else if ($vattype == 'ZERO-RATED') {
            $vat = 0;
            $sales3 = $totalext;
        }

        $lessVat = $vat;
        $addVat = $lessVat;
        $lessDisc = $totaldisc;
        $netVatamt = $totalext - $lessVat;
        $lessWithholdingTax = 0;
        $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

        //Right Side
        PDF::SetFont($fontbold, '', 9);
        PDF::SetXY(288, 395);
        PDF::MultiCell(75, 4, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 4, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
        PDF::SetX(288);
        PDF::MultiCell(75, 2, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
        PDF::SetXY(288, 460);
        PDF::MultiCell(75, 4, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);


        //Left Side
        PDF::SetXY(135, 397);
        PDF::MultiCell(65, 4, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
        PDF::SetX(135);
        PDF::MultiCell(65, 4, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
        PDF::SetX(135);
        PDF::MultiCell(65, 4, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
        PDF::SetX(135);
        PDF::MultiCell(65, 4, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
        PDF::SetCellPaddings(0, 9, 0, 0);
        PDF::SetX(135);
        PDF::MultiCell(65, 20, '', '', 'R', false);
        PDF::SetCellPaddings(0, 12, 0, 0);
        PDF::SetX(135);
        PDF::MultiCell(65, 20, '', '', 'R', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
} // end of class
