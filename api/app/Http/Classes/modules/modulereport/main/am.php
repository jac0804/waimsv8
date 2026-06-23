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
        'sales' as radiosjafti,
        '' as prepared,
        '' as approved,
        '' as received
        "
        );
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select head.trno,head.docno,date(head.dateid) as dateid,client.client,head.clientname,client.bstyle,
        cmake.carname as vehicle,cvh.mileage,client.tin,stock.disc,agent.clientname as agent,i.barcode,
        head.yourref,head.ourref,head.terms,head.address,stock.uom,i.itemname,stock.isqty as qty,
        cvh.licenseno,cm.cryear,cm.model, cm.sub_model,cmake.carname,stock.amt, stock.ext,head.vattype
        from amjobs as jobs
        left join lahead as head on head.trno = jobs.trno
        left join lastock as stock on stock.trno=head.trno and stock.jobline = jobs.line
        left join client on client.client = head.client
        left join client as agent on agent.client = head.agent
        left join item as i on i.itemid =stock.itemid
        left join cmake on cmake.id=head.carid
        left join amtask as am on am.jobline = jobs.line and am.trno = jobs.trno and stock.taskline = am.line
        left join jobtask as jb on jb.line = am.laborline
        left join cvehicle as cvh on cvh.clientid=client.clientid
        left join cmodel as cm on cm.carid = cvh.carid and cm.line = cvh.cmodelline
        where head.doc = 'AM' and stock.itemid is not null and i.itemname is not null and head.trno = $trno
        group by head.trno,head.docno,date(head.dateid),client.client,head.clientname,client.bstyle,cmake.carname,cvh.licenseno,cm.cryear,i.barcode,
        cm.model,cm.sub_model,stock.uom,i.itemname,stock.isqty,jobs.line,am.line,cvh.mileage,head.yourref,head.ourref,head.terms,cmake.carname,
        head.address,client.tin,stock.disc,agent.clientname,stock.amt, stock.ext,head.vattype
        union all
        select head.trno,head.docno,date(head.dateid) as dateid,client.client,head.clientname,client.bstyle,
        cmake.carname as vehicle,cvh.mileage,client.tin,stock.disc,agent.clientname as agent,i.barcode,
        head.yourref,head.ourref,head.terms,head.address,stock.uom,i.itemname,stock.isqty as qty,
        cvh.licenseno,cm.cryear,cm.model, cm.sub_model,cmake.carname,stock.amt, stock.ext,head.vattype
        from hamjobs as jobs
        left join glhead as head on head.trno = jobs.trno
        left join glstock as stock on stock.trno=head.trno and stock.jobline = jobs.line
        left join client on client.clientid = head.clientid
        left join client as agent on agent.clientid = head.agentid
        left join item as i on i.itemid =stock.itemid
        left join cmake on cmake.id=head.carid
        left join hamtask as am on am.jobline = jobs.line and am.trno = jobs.trno and stock.taskline = am.line
        left join jobtask as jb on jb.line = am.laborline
        left join cvehicle as cvh on cvh.clientid=client.clientid
        left join cmodel as cm on cm.carid = cvh.carid and cm.line = cvh.cmodelline
        where head.doc = 'AM' and stock.itemid is not null and i.itemname is not null and head.trno = $trno
        group by head.trno,head.docno,date(head.dateid),client.client,head.clientname,client.bstyle,cmake.carname,cvh.licenseno,cm.cryear,i.barcode,
        cm.model,cm.sub_model,stock.uom,i.itemname,stock.isqty,jobs.line,am.line,cvh.mileage,head.yourref,head.ourref,head.terms,cmake.carname,
        head.address,client.tin,stock.disc,agent.clientname,stock.amt, stock.ext,head.vattype";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function report_labor_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select jobs.line,jt.jobtitle, am.cost, jb.description
        from amjobs as jobs
        left join lahead as head on head.trno = jobs.trno
        left join amtask as am on am.jobline = jobs.line and am.trno = jobs.trno
        left join jobtask as jb on jb.line = am.laborline
        left join jobthead as jt on jt.line = jobs.jobid
        where head.trno = $trno";

        $labor = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $labor;
    } //end fn

    public function reportplotting($params, $data)
    {
        $print = $params['params']['dataparams']['print'];
        $reporttype = $params['params']['dataparams']['radiosjafti'];
        $labor = $this->report_labor_query($params);
        // $repair = $this->report_repair_query($params);

        switch ($print) {
            case 'PDFM':
                switch ($reporttype) {
                    case 'sales': //NEW SALES INVOICE
                        return $this->sales_layout_PDF($params, $data);
                        break;
                    case 'service': //NEW SERVICE INVOICE
                        return $this->service_layout_PDF($params, $data, $labor);
                        break;
                    case 'print1': //PRINT OUT 1 
                        return $this->print1_layout_PDF($params, $data, $labor);
                        break;
                    case 'print2': //PRINT OUT 2
                        return $this->print2_layout_PDF($params, $data, $labor);
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
        $fontsize = "10";
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
        // PDF::SetMargins(40, 0);
        PDF::SetX(30);

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

        PDF::SetFont($fontbold, 'B', 13);
        PDF::MultiCell(550, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, strtoupper($headerdata[0]->address), '', 'C');
        PDF::MultiCell(550, 0, 'TELS : ' . strtoupper($headerdata[0]->tel), '', 'C');
        PDF::MultiCell(550, 0, 'VAT Reg. TIN : ' . strtoupper($headerdata[0]->tin), '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetX(30);
        PDF::SetFont($fontbold, 'B', 11);
        PDF::MultiCell(280, 0, 'SALES INVOICE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(190, 0, "No. : " . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetLineWidth(0.5);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 10, 'SOLD TO ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(270, 10, ': ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 10, 'DATE ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(170, 10, ': ' . (isset($data[0]['dateid']) ? date('M-d-Y', strtotime($data[0]['dateid'])) : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 10, 'TIN/SC-TIN ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(270, 10, ': ' . (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 10, 'P.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(160, 10, ': ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(110, 10, 'OSCA/PWD  ID No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(220, 10, ': ' . (isset($data[0]['disc']) ? $data[0]['disc'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 10, 'J.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(160, 10, ': ' . (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 10, 'Business Style ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(250, 10, ': ' . (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 10, 'Terms ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(170, 10, ': ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 10, 'Address ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(280, 10, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(55, 10, 'Salesman', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(155, 10, ': ' . (isset($data[0]['agent']) ? $data[0]['agent'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(550, 7, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetXY(30, 205);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(20, 10, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(40, 10, 'TERMS : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(490, 10, 'Cash unless otherwise arranged. 12% interest per annum is to be  charged on all  accounts overdue', '', 'J', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::SetX(30);
        PDF::MultiCell(550, 10, " plus 25% on said amount for attorney's fees and cost of collection. The parties expressly submit themselves to the ", '', 'J', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::SetX(30);
        PDF::MultiCell(550, 10, "jurisdiction of Quezon City in any legal action arising out of this transaction without in any way attemting to divert ", '', 'J', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::SetX(30);
        PDF::MultiCell(550, 10, "jurisdiction from any other court or courts. Goods travel at the risk of the buyer.", '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(550, 0, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(550, 0, '', '');

        PDF::SetLineWidth(0.5);

        PDF::SetX(30);
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(50, 0, "QUANTITY", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(190, 0, "DESCRIPTION OF ARTICLES", 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(110, 0, "UNIT PRICE", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(110, 0, "AMOUNT", 'B', 'C', false);

        PDF::SetLineWidth(0.2);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(550, 0, '', '');
    }

    public function sales_layout_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->sales_header_PDF($params, $data);
        $counted = count($data);
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $maxRowsPerPage = 12;

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

        PDF::SetY(273);
        for ($i = 0; $i < ($counted); $i++) {
            $maxrow = 1;

            $uom = $data[$i]['uom'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $amt = number_format($data[$i]['amt'], 2);
            $ext = number_format($data[$i]['ext'], 2);

            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0); //23
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
            $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {

                // if ($rowPerPage == $maxRowsPerPage) {
                //     break 2;
                // }
                if ($rowPerPage == $maxRowsPerPage) {
                    // this message only shows everytime there is a new pages 
                    PDF::SetFont($fontbold, 'I', $fontsize);
                    PDF::SetX(30);
                    PDF::MultiCell(550, 20, '** See next page **', '', 'C', false);
                    // footer
                    $this->sales_footer_PDF($params, $data, $font, $fontbold, $fontsize, $totalext, $totaldisc, $vat, $sales1, $sales2, $sales3, false);
                    // header
                    $this->sales_header_PDF($params, $data);
                    PDF::SetY(273);
                    $rowPerPage = 0;
                }
                $rowPerPage++;
                PDF::SetFont($font, '', $fontsize);
                PDF::SetX(30);
                PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
                PDF::MultiCell(50,  20, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, '', false);
                PDF::MultiCell(50,  20, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(190, 20, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(110,  20, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(110,  20, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetCellPaddings(0, 0, 0, 0); // reset the padding
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
            $sales1 = $totalext  - $vat;
        } else if ($vattype == 'NON-VATABLE') {
            $sales2 = $totalext;
        } else if ($vattype == 'ZERO-RATED') {
            $sales3 = $totalext;
        }

        // Footer for the layout
        $this->sales_footer_PDF($params, $data, $font, $fontbold, $fontsize, $totalext, $totaldisc, $vat, $sales1, $sales2, $sales3, true);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    private function sales_footer_PDF($params, $data, $font, $fontbold, $fontsize, $totalext, $totaldisc, $vat, $sales1, $sales2, $sales3, $showTotals = false)
    {
        $netVatamt          = $totalext - $vat;
        $lessVat            = $vat;
        $addVat             = $lessVat;
        $lessDisc           = $totaldisc;
        $amountDue          = $netVatamt - $lessDisc;
        $totalAmtDue        = $netVatamt - $lessDisc + $addVat;

        PDF::SetXY(30, 540);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(310, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'Total Sales (Vat Inclusive)', '', 'L', false, 0);
        PDF::MultiCell(110, 10, $showTotals && $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);

        PDF::SetX(30);
        PDF::MultiCell(310, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'Less : VAT', '', 'L', false, 0);
        PDF::MultiCell(110, 10, $showTotals && $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);

        PDF::SetX(30);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(120, 10, 'VATable Sales', '', 'L', false, 0);
        PDF::MultiCell(80,  10, $showTotals && $sales1 != 0.00 ? number_format($sales1, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(50, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'Amount : NET of VAT', '', 'L', false, 0);
        PDF::MultiCell(110, 10, $showTotals && $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);

        PDF::SetX(30);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(120, 10, 'VAT-Exempt Sales', '', 'L', false, 0);
        PDF::MultiCell(80,  10, $showTotals && $sales2 != 0.00 ? number_format($sales2, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(50, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'Less : SC/PWD Discount', '', 'L', false, 0);
        PDF::MultiCell(110, 10, $showTotals && $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);

        PDF::SetX(30);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(120, 10, 'Zero Rated Sales', '', 'L', false, 0);
        PDF::MultiCell(80,  10, $showTotals && $sales3 != 0.00 ? number_format($sales3, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(50, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'Amount Due ', '', 'L', false, 0);
        PDF::MultiCell(110, 10, $showTotals && $amountDue != 0 ? number_format($amountDue, 2) : '', '', 'R', false);

        PDF::SetX(30);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(120, 10, 'VAT Amount',  '', 'L', false, 0);
        PDF::MultiCell(80,  10, $showTotals && $vat != 0 ? number_format($vat, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(50, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'Add Vat ',  '', 'L', false, 0);
        PDF::MultiCell(110, 10, $showTotals && $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);

        PDF::SetX(30);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(120, 10, '', '', 'L', false, 0);
        PDF::MultiCell(80,  10, '', '', 'R', false, 0);
        PDF::MultiCell(50, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'TOTAL AMOUNT DUE',  '', 'L', false, 0);
        PDF::MultiCell(110, 10, $showTotals && $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

        PDF::SetXY(30, 620);
        PDF::SetLineWidth(2.0);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(550, 10, '', 'B', 'R', false, 0);
        PDF::SetLineWidth(0.2);

        PDF::SetXY(30, 635);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(45,  10, 'R.C. No.', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(35,  10, 'C - 13',   '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, '', '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(45,  10, 'Date', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(35,  10, 'Date', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, '', '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(45,  10, 'Place', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(35,  10, 'Place', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, '', 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(350,  10, '', '', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, "Cashier's/Authorized Representative", '', 'C', false);

        PDF::SetXY(30, 690);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(85, 0, '', '', 'L', false, 0);
        PDF::MultiCell(380, 0, 'THIS SALES INVOICE  SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP', 'B', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'L', false);

        PDF::SetXY(30, 710);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'LICENSE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['licenseno']) ? $data[0]['licenseno']  : ''), '', 'L', false, 0);
        PDF::MultiCell(20,  0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'MODEL', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['model']) ? $data[0]['model'] : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60,  0, 'SUB MODEL', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['sub_model']) ? $data[0]['sub_model'] : ''), '', 'L', false, 0);
        PDF::MultiCell(40,  0, '', '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'YEAR',    '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['cryear']) ? $data[0]['cryear'] : ''), '', 'L', false, 0);
        PDF::MultiCell(20,  0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'MAKE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['carname']) ? $data[0]['carname'] : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60,  0, 'MILEAGE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['mileage']) ? $data[0]['mileage'] : ''), '', 'L', false, 0);
        PDF::MultiCell(40,  0, '', '', 'L', false);
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
        $fontsize = "10";
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
        // PDF::SetMargins(40, 0);
        PDF::SetX(30);

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

        PDF::SetFont($fontbold, 'B', 13);
        PDF::MultiCell(550, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, strtoupper($headerdata[0]->address), '', 'C');
        PDF::MultiCell(550, 0, 'TELS : ' . strtoupper($headerdata[0]->tel), '', 'C');
        PDF::MultiCell(550, 0, 'VAT Reg. TIN : ' . strtoupper($headerdata[0]->tin), '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetX(30);
        PDF::SetFont($fontbold, 'B', 11);
        PDF::MultiCell(280, 0, 'SALES INVOICE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(190, 0, "No. : " . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetLineWidth(0.5);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 10, 'SOLD TO ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(270, 10, ': ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 10, 'DATE ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(170, 10, ': ' . (isset($data[0]['dateid']) ? date('M-d-Y', strtotime($data[0]['dateid'])) : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 10, 'TIN/SC-TIN ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(270, 10, ': ' . (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 10, 'P.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(160, 10, ': ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(110, 10, 'OSCA/PWD  ID No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(220, 10, ': ' . (isset($data[0]['disc']) ? $data[0]['disc'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 10, 'J.O. No. ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(160, 10, ': ' . (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 10, 'Business Style ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(250, 10, ': ' . (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 10, 'Terms ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(170, 10, ': ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 10, 'Address ', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(280, 10, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(55, 10, 'Salesman', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(155, 10, ': ' . (isset($data[0]['agent']) ? $data[0]['agent'] : ''), 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(550, 7, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetXY(30, 205);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(20, 10, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, 'B', $fontsize);
        PDF::MultiCell(40, 10, 'TERMS : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(490, 10, 'Cash unless otherwise arranged. 12% interest per annum is to be  charged on all  accounts overdue', '', 'J', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::SetX(30);
        PDF::MultiCell(550, 10, " plus 25% on said amount for attorney's fees and cost of collection. The parties expressly submit themselves to the ", '', 'J', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::SetX(30);
        PDF::MultiCell(550, 10, "jurisdiction of Quezon City in any legal action arising out of this transaction without in any way attemting to divert ", '', 'J', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::SetX(30);
        PDF::MultiCell(550, 10, "jurisdiction from any other court or courts. Goods travel at the risk of the buyer.", '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', '');
        PDF::SetLineWidth(2.0);
        PDF::MultiCell(550, 0, '', 'B', '', false, 1, '', '', true, 0, false, true, 0, 'M', false);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(550, 0, '', '');

        PDF::SetLineWidth(0.5);

        PDF::SetX(30);
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(50, 0, "QUANTITY", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(190, 0, "DESCRIPTION OF ARTICLES", 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(110, 0, "UNIT PRICE", 'B', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(110, 0, "AMOUNT", 'B', 'C', false);

        PDF::SetLineWidth(0.2);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(550, 0, '', '');
    }

    public function service_layout_PDF($params, $data, $labor)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->service_header_PDF($params, $data);
        $laborCount = count($labor);
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $maxRowsPerPage = 15;

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

        PDF::SetY(273);
        for ($i = 0; $i < ($laborCount); $i++) {
            $maxrow = 1;

            $jobtitle = $labor[$i]['jobtitle'];
            $description = $labor[$i]['description'];
            $cost = number_format($labor[$i]['cost'], 2);

            $arr_jobtitle = $this->reporter->fixcolumn([$jobtitle],    '35', 0);
            $arr_description = $this->reporter->fixcolumn([$description], '35', 0);
            $arr_cost = $this->reporter->fixcolumn([$cost], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_jobtitle, $arr_description, $arr_cost]);

            $prev_jobtitle = isset($labor[$i - 1]['jobtitle']) ? $labor[$i - 1]['jobtitle'] : null;
            if ($jobtitle !== $prev_jobtitle) {
                if ($rowPerPage == $maxRowsPerPage) {
                    PDF::SetFont($fontbold, 'I', $fontsize);
                    PDF::MultiCell(550, 20, '** See next page **', '', 'C', false);
                    $this->service_footer_PDF($params, $data, $font, $fontbold, $fontsize, $totalext, $totaldisc, $vat, $sales1, $sales2, $sales3, false);
                    $this->service_header_PDF($params, $data);
                    PDF::SetY(273);
                    $rowPerPage = 0;
                }
                $rowPerPage++;
                PDF::SetFont($fontbold, 'B', $fontsize);
                PDF::SetCellPaddings(0, 4, 0, 0);
                PDF::SetX(30);
                PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50,  20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(190, 20, strtoupper($jobtitle), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(110, 20, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(110, 20, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetCellPaddings(0, 0, 0, 0);
            }

            for ($r = 0; $r < $maxrow; $r++) {

                if ($rowPerPage == $maxRowsPerPage) {
                    PDF::SetFont($fontbold, 'I', $fontsize);
                    PDF::MultiCell(550, 20, '** See next page **', '', 'C', false);
                    $this->service_footer_PDF($params, $data, $font, $fontbold, $fontsize, $totalext, $totaldisc, $vat, $sales1, $sales2, $sales3, false);
                    $this->service_header_PDF($params, $data);
                    PDF::SetY(273);
                    $rowPerPage = 0;
                }

                $rowPerPage++;
                PDF::SetFont($font, '', $fontsize);
                PDF::SetCellPaddings(0, 4, 0, 0);
                PDF::SetX(30);
                PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', false);
                PDF::MultiCell(10,  20, '',     '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(40,  20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(180, 20, '->' . (isset($arr_description[$r]) ? $arr_description[$r] : ''),      '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(110, 20, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(10,  20, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(110, 20, $r == 0 ? (isset($arr_cost[$r]) ? $arr_cost[$r] : '') : '',    '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                PDF::SetCellPaddings(0, 0, 0, 0);
            }
            $totalext  += $labor[$i]['cost'];
        }

        // Footer for the layout
        $this->service_footer_PDF($params, $data, $font, $fontbold, $fontsize, $totalext, $totaldisc, $vat, $sales1, $sales2, $sales3, true);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    private function service_footer_PDF($params, $data, $font, $fontbold, $fontsize, $totalext, $totaldisc, $vat, $sales1, $sales2, $sales3, $showTotals = false)
    {
        $netVatamt = $totalext - $vat;
        $lessVat = $vat;
        $addVat = $lessVat;
        $lessDisc = $totaldisc;
        $amountDue = $netVatamt - $lessDisc;
        $totalAmtDue = $netVatamt - $lessDisc + $addVat;

        PDF::SetXY(30, 600);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 20, '', '', 'R', false, 0);
        PDF::MultiCell(120, 20, '', '', 'L', false, 0);
        PDF::MultiCell(80, 20, '', '', 'R', false, 0);
        PDF::MultiCell(50, 20, '', '', 'R', false, 0);
        PDF::MultiCell(130, 20, 'TOTAL AMOUNT DUE : ',  '', 'L', false, 0);
        PDF::MultiCell(110, 20, $showTotals && $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

        PDF::SetXY(30, 610);
        PDF::SetLineWidth(2.0);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(550, 10, '', 'B', 'R', false, 0);
        PDF::SetLineWidth(0.2);

        PDF::SetXY(30, 625);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(45,  10, 'R.C. No.', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(35,  10, 'C - 13',   '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, '', '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(45,  10, 'Date', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(35,  10, 'Date', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, '', '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(45,  10, 'Place', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(35,  10, 'Place', '', 'L', false, 0);
        PDF::MultiCell(130, 10, ':', 'B', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, '', 'B', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(350,  10, '', '', 'L', false, 0);
        PDF::MultiCell(10,  10, '', '', 'L', false, 0);
        PDF::MultiCell(190, 10, "Cashier's/Authorized Representative", '', 'C', false);

        PDF::SetXY(30, 680);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(160, 0, '', '', 'L', false, 0);
        PDF::MultiCell(230, 0, 'THIS DOCUMENT IS NOT CLAIMING INPUT TAXES', 'B', 'C', false, 0);
        PDF::MultiCell(160, 0, '', '', 'L', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(85, 0, '', '', 'L', false, 0);
        PDF::MultiCell(380, 0, 'THIS SALES INVOICE  SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP', 'B', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'L', false);

        PDF::SetXY(30, 710);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'LICENSE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['licenseno']) ? $data[0]['licenseno']  : ''), '', 'L', false, 0);
        PDF::MultiCell(20,  0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'MODEL', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['model']) ? $data[0]['model'] : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60,  0, 'SUB MODEL', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['sub_model']) ? $data[0]['sub_model'] : ''), '', 'L', false, 0);
        PDF::MultiCell(40,  0, '', '', 'L', false);

        PDF::SetX(30);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'YEAR',    '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['cryear']) ? $data[0]['cryear'] : ''), '', 'L', false, 0);
        PDF::MultiCell(20,  0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  0, 'MAKE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['carname']) ? $data[0]['carname'] : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60,  0, 'MILEAGE', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 0, ': ' . (isset($data[0]['mileage']) ? $data[0]['mileage'] : ''), '', 'L', false, 0);
        PDF::MultiCell(40,  0, '', '', 'L', false);
    }

    public function print1_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

        $font = "";
        $fontbold = "";
        $fontsize = "10";
        $fontsize2 = "9";
        $maxRowsPerPage = 13;
        $rowHeight = 0;
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
        PDF::SetMargins(30, 30);

        $dateformat = ($data[0]['dateid']) ? date('M d, Y', strtotime($data[0]['dateid'])) : '';

        PDF::SetY(10);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetY(45);
        PDF::SetFont($fontbold, 'B', 16);
        PDF::MultiCell(260, 20, strtoupper($headerdata[0]->name), '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(460, 20, "", '', 'R', false);

        PDF::SetY(30);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(350, 15, "", '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'LT', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, 'REPAIR ORDER #', 'T', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize2);
        PDF::MultiCell(100, 15, ': ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'TR', 'L', false);

        PDF::MultiCell(350, 15, "", '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '', ''); // 175
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, "DATE", '', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize2);
        PDF::MultiCell(100, 15, ': ' . $dateformat, 'R', 'L', false);
        $pagenumber = PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages();
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(350, 15, strtoupper($headerdata[0]->address), '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, "PAGE", '', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize2);
        PDF::MultiCell(100, 15, $pagenumber, 'R', 'L', false); //add page number

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(350, 15, strtoupper($headerdata[0]->tel), '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'LB', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, "FORM#", 'B', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(100, 15, " :", 'BR', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // left, top, right, bottom

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 10, '', '', '', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(270, 10, '', '', '', false);

        PDF::SetCellPaddings(0, 4, 0, 0); // 550
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'LT', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, 'CUSTOMER', 'T', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(210, 15, ': ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'T', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 15, '', 'T', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'LT', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "LICENSE", 'T', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ':  ' . (isset($data[0]['licenseno']) ? $data[0]['licenseno'] : ''), 'RT', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, 'ADDRESS', '', 'LB', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(210, 15, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'LB', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 15, '', '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "YEAR", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['cryear']) ? $data[0]['cryear'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(315, 15, '', '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "MAKE", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['carname']) ? $data[0]['carname'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(315, 15, '', '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "MODEL", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['model']) ? $data[0]['model'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, 'PHONE', '', 'LB', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(210, 15, ': ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), '', 'LB', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 15, '', '', '', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "SUB MODEL", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['sub_model']) ? $data[0]['sub_model'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'LB', 'L', false, 0, '', '');
        PDF::MultiCell(315, 15, '', 'B', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'LB', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "MILEAGE", 'B', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['mileage']) ? $data[0]['mileage'] : ''), 'RB', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // left, top, right, bottom

        PDF::MultiCell(0, 0, "\n");

        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, '', 'LT', 'C', false, 0, '', '');
        PDF::SetFillColor(200, 200, 200); // Light gray 
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(265, 20, 'PARTS', 'T', 'C', true, 0, '', '');
        PDF::SetFillColor(255, 255, 255); // White
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, '', 'RT', 'C', false, 0, '', '');
        PDF::MultiCell(5, 20, '', 'T', 'C', false, 0, '', '');
        PDF::SetFillColor(200, 200, 200); // Light gray 
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(265, 20, 'LABOR', 'T', 'C', true, 0, '', '');
        PDF::SetFillColor(255, 255, 255); // White
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, '', 'RT', 'C', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // reset padding

        PDF::SetFont($fontbold, '', $fontsize); //270
        PDF::MultiCell(40, 20, "QTY", 'L', 'C', false, 0);
        PDF::MultiCell(80, 20, "PART#", '', 'C', false, 0, '', '');
        PDF::MultiCell(100, 20, "DESCRIPTION", '', 'C', false, 0, '', '');
        PDF::MultiCell(50, 20, "PRICE", '', 'C', false, 0);
        PDF::MultiCell(5, 20, "", 'R', '', false, 0, '', '');
        PDF::MultiCell(5, 20, "", '', '', false, 0, '', '');
        PDF::MultiCell(40, 20, "OP", '', 'C', false, 0, '', '');
        PDF::MultiCell(80, 20, "TECH", '', 'C', false, 0, '', '');
        PDF::MultiCell(100, 20, "DESCRIPTION", '', 'C', false, 0, '', '');
        PDF::MultiCell(50, 20, "PRICE", 'R', 'C', false);
        // PDF::SetY(362);
        $rowHeight = ($maxRowsPerPage) * 29;
        if ($rowHeight > 0) {
            PDF::MultiCell(275, $rowHeight, '', 'BL', '', false, 0);
            PDF::MultiCell(275, $rowHeight, '', 'BLR', '', false);
        }
    }

    public function print1_layout_PDF($params, $data, $labor)
    {
        $font = "";
        $fontbold = "";
        $fonttable = 9;
        $count1 = 0;
        $rowlimit1 = 16;
        $count2 = 0;
        $rowlimit2 = 16;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->print1_header_PDF($params, $data);

        $counted = count($data);
        $laborCount = count($labor);
        $total1 = 0;
        $total2 = 0;
        $startPage = PDF::getPage();

        // PARTS - Left Side
        PDF::SetY(250);
        for ($i = 0; $i < $counted; $i++) {

            $qty = !empty($data[$i]['qty']) ? $data[$i]['qty'] : 0;
            $uom = !empty($data[$i]['uom']) ? $data[$i]['uom'] : '';
            $barcode = !empty($data[$i]['barcode'])  ? $data[$i]['barcode'] : '';
            $itemname = !empty($data[$i]['itemname']) ? $data[$i]['itemname'] : '';
            $ext = !empty($data[$i]['ext']) ? $data[$i]['ext'] : 0;

            $arr_qty = $this->reporter->fixcolumn([$this->formatQty($qty)], '10', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '20', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
            $arr_ext = $this->reporter->fixcolumn([number_format($ext, 2)], '40', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_barcode, $arr_qty, $arr_uom, $arr_ext]);

            for ($r = 0; $r < $maxrow; $r++) {
                $count1++;
                $isLastRow = ($i == $counted - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetFont($font, '', $fonttable);
                PDF::MultiCell(15, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(20, 20, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(80, 20, isset($arr_barcode[$r]) ? $arr_barcode[$r] : '', '', 'L', false, 0);
                PDF::MultiCell(90, 20, isset($arr_itemname[$r]) ? $arr_itemname[$r] : '', '', 'L', false, 0);
                PDF::MultiCell(65, 20, $r == 0 ? (isset($arr_ext[$r]) ? $arr_ext[$r] : '') : '', $border, 'R', false);
            }

            $total1 += $ext;

            if ($count1 > $rowlimit1) {
                PDF::SetXY(255, 612.7);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true);
                PDF::SetXY(258, 615);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);

                $this->print1_PDF_footer($params, $data, $total1, $total2, false);
                $this->print1_header_PDF($params, $data);
                PDF::SetY(250);
                $count1 = 0;
                $count2 = 0;
            }
        }

        PDF::SetLineWidth(2);
        PDF::MultiCell(205, 0, '', '',  'R', false, 0);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::MultiCell(64.5,  0, number_format($total1, 2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        // Right Side
        PDF::setPage($startPage);
        PDF::SetXY(390, 250);

        $preJob = null;
        $display_jobtitle = '';

        for ($i = 0; $i < $laborCount; $i++) {

            $jobtitle = !empty($labor[$i]['jobtitle']) ? $labor[$i]['jobtitle']    : '';
            $description = !empty($labor[$i]['description']) ? $labor[$i]['description'] : '';
            $cost = !empty($labor[$i]['cost']) ? $labor[$i]['cost'] : 0;

            // Track jobtitle grouping
            $same = ($jobtitle === $preJob);
            if ($same) {
                $display_jobtitle = '';
            } else {
                $display_jobtitle = $jobtitle;
            }
            $preJob = $jobtitle;

            $arr_jobtitle = $this->reporter->fixcolumn([$display_jobtitle], '40', 0) ?: [''];
            $arr_description = $this->reporter->fixcolumn([$description], '40', 0);
            $arr_cost = $this->reporter->fixcolumn([number_format($cost, 2)], '10', 0);

            $maxrow = $this->othersClass->getmaxcolumn([
                $arr_jobtitle,
                $arr_description,
                $arr_cost
            ]);

            for ($r = 0; $r < $maxrow; $r++) {
                $count2++;
                $isLastRow = ($i == $laborCount - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetX(300);
                PDF::SetFont($font, '', $fonttable);

                // Print jobtitle row (only when jobtitle changes)
                if (!$same && $r == 0) {
                    if (empty($description) || $description == null) {
                        PDF::MultiCell(5, 20, '', '', 'L', false, 0);
                        PDF::MultiCell(40, 20, '', '', 'C', false, 0);
                        PDF::MultiCell(180, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 0);
                    } else {
                        $count2++;
                        PDF::MultiCell(5, 20, '', '', 'L', false, 0);
                        PDF::MultiCell(40, 20, '', '', 'C', false, 0);
                        PDF::MultiCell(180, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 1);
                    }
                }

                // Print description row with cost
                if ($r == 0 && !empty($description)) {
                    PDF::SetX(360);
                    PDF::SetFont($font, '', $fonttable);
                    PDF::MultiCell(150, 20, '--- ' . $description, '', 'L', false, 0);
                    PDF::MultiCell(65, 20, isset($arr_cost[$r]) ? $arr_cost[$r] : '', $border, 'R', false);
                } else if ($r == 0 && empty($description)) {
                    PDF::MultiCell(65, 20, isset($arr_cost[$r]) ? $arr_cost[$r] : '', $border, 'R', false);
                }
            }

            PDF::SetX(303);
            $total2 += $cost;

            if ($count2 > $rowlimit2) {
                PDF::SetXY(255, 612.7);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true);
                PDF::SetXY(258, 615);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);

                $this->print1_PDF_footer($params, $data, $total1, $total2, false);
                $this->print1_header_PDF($params, $data);
                PDF::SetXY(390, 250);
                $count1 = 0;
                $count2 = 0;
            }
        }

        PDF::SetX(303);
        PDF::SetLineWidth(2);
        PDF::MultiCell(207, 0, '', '',  'R', false, 0);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::MultiCell(64.4,  0, number_format($total2, 2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        PDF::setPage(PDF::getNumPages());
        $this->print1_PDF_footer($params, $data, $total1, $total2, true);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function print1_PDF_footer($params, $data, $total1, $total2, $totals = true)
    {
        $font = "";
        $fontbold = "";
        $fonttable = 9;
        $fontsize2 = 6;
        $tax = 0;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetY(630); //643
        PDF::SetFont($fontbold, '', 8);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 15, '', 'LTB', 'LB', false, 0);
        PDF::MultiCell(540, 15, 'RECOMMENDED REPAIR', 'RBT', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        PDF::SetY(648); //662 // Left side of the footer
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 20, '', 'LT', 'LB', false, 0);
        PDF::MultiCell(410, 20, 'TERMS AND PROVISIONS : ', 'RT', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::SetY(660);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "1. Customer authorizes  DAS or its employees to operate vehicle for purposes of testing , inspection and/or delivery at customer's risk.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "2. Customer acknowledges an express mechanic's lien to secure the amount of repairs indicated thereto.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "3. DAS will not be responsble or liable for loss or damage to vehicle or articles left in case of fire, theft, accident, flood, typhoon, earthquake", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "and/or other causes beyond the comapany's control.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "4. Customer agrees to pay interest at the rate of 2% per month on all accunts not paid when due.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "5. Vehicle not claimed and withdrawn from the company's premises within five(5) days from date of completion will be charged storage of", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, " P 60.00 per day untill withdrawn.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "6. Orall agreements, representations or promises not incorporated herein are unauthorized and therefore not binding.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "7. In case of litigation for non-payment of this repair order invoice, customer agrees to submit himself to the jurisdiction of the courts of", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, " Manila, Quezon City or Makati City at the option of the comapany and to pay 25% for attorney's fees, minimum of P 500.00 plus court costs.", 'R', 'L', false);

        PDF::MultiCell(420, 5, '', 'LR', 'L', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(20, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(100, 5, "CUSTOMER CONFORME : ", '', 'L', false, 0);
        PDF::MultiCell(190, 5, '', 'B', 'L', false, 0);
        PDF::MultiCell(110, 5, "", 'R', 'L', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(20, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(100, 5, '', '', 'L', false, 0);
        PDF::MultiCell(190, 5, "PrintName and Signature", '', 'C', false, 0);
        PDF::MultiCell(110, 5, "", 'R', 'L', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(50, 5, '', 'LB', 'L', false, 0);
        PDF::MultiCell(130, 5, '', 'B', 'L', false, 0);
        PDF::MultiCell(160, 5, "", 'B', 'C', false, 0);
        PDF::MultiCell(80, 5, "", 'RB', 'L', false);


        PDF::SetCellPaddings(0, 0, 0, 0); // End of left side of the footer
        PDF::SetXY(450, 648); // RightSide of the footer
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', 'T', 'L', false, 0);
        PDF::MultiCell(50, 25, 'LABOR ', 'T', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', 'T', 'L', false, 0);
        PDF::SetFont($font, '', $fonttable);
        PDF::MultiCell(50, 25, $totals ? number_format($total2, 2) : '', 'T', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'TR', 'L', false);

        PDF::SetXY(450, 663);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'PARTS ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fonttable);
        PDF::MultiCell(50, 25, $totals ? number_format($total1, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'R', 'L', false);

        PDF::SetXY(450, 676);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, '', '', 'L', false, 0);
        PDF::MultiCell(220, 25, '', 'R', 'R', false);

        PDF::SetXY(450, 686);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'SUPPLIES ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::MultiCell(60, 25, '', 'R', 'L', false);

        $tax = $total1 * ((isset($data[0]['tax']) ? $data[0]['tax'] : '') / 100);
        PDF::SetXY(450, 699);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'TAX ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fonttable);
        PDF::MultiCell(50, 25, $totals ? number_format($tax, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'R', 'L', false);

        PDF::SetXY(450, 711);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, '', '', 'L', false, 0);
        PDF::MultiCell(220, 25, '', 'R', 'R', false);

        $ftotal = $total1 + $total2 + $tax;
        PDF::SetXY(450, 723);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'TOTAL ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::MultiCell(50, 25, $totals ? number_format($ftotal, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'R', 'L', false);

        PDF::SetXY(450, 744);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', 'B', 'L', false, 0);
        PDF::MultiCell(50, 25, '', 'B', 'L', false, 0);
        PDF::MultiCell(70, 25, '', 'RB', 'R', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
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
        $fontcol = 7;
        $fontvar = 10;
        $fontvar2 = 8;
        $fontsize = 9;
        $fonttable = 9;
        $fonttitle = 12;
        $maxRowsPerPage = 30;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }


        $dateformat = ($data[0]['dateid']) ? date('M d, Y', strtotime($data[0]['dateid'])) : '';

        PDF::SetTitle($this->modulename);
        PDF::SetY(10);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('l', 'LETTER');
        PDF::SetMargins(23, 23);
        PDF::SetY(10);
        // PDF::MultiCell(0, 0, "\n", '', '');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', $fonttitle);
        PDF::MultiCell(373, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(373, 0, 'REPAIR ORDER # : ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
        PDF::MultiCell(300, 20, 'TeleFax: ' . strtoupper($headerdata[0]->tel), '', 'L', false);
        //1
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(50, 20, 'CUSTOMER ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(350, 20, ': ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(50, 20, 'RECEIVED ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(296, 20, ': ' . $dateformat, 'B', 'L', false);
        //2
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(50, 20, 'ADDRESS ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(350, 20, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(50, 20, 'PHONE ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(296, 20, ': ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false);
        //3
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'LICENSE  ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(65, 20, ':  ' . (isset($data[0]['licenseno']) ? $data[0]['licenseno'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'YEAR ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(50, 20, ': ' . (isset($data[0]['cryear']) ? $data[0]['cryear'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(30, 20, 'MAKE ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['carname']) ? $data[0]['carname'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'MODEL ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['model']) ? $data[0]['model'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'SUBMODEL ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['sub_model']) ? $data[0]['sub_model'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'MILEAGE ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['mileage']) ? $data[0]['mileage'] : ''), 'B', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        PDF::SetY(132);
        PDF::MultiCell(746, 0, '', 'B', '', false);
        $rowHeight = ($maxRowsPerPage) * 11;
        if ($rowHeight > 0) {
            PDF::MultiCell(373, $rowHeight, '', 'L', '', false, 0);
            PDF::MultiCell(373, $rowHeight, '', 'LR', '', false);
        }
        PDF::MultiCell(746, 0, '', 'T', '', false);

        PDF::SetY(148);
        PDF::SetFont($fontbold, '', $fonttable);

        PDF::SetFillColor(192, 192, 192);
        PDF::MultiCell(5, 0, '', '', 'L', false, 0);
        PDF::MultiCell(48, 0, 'QTY', 'B', 'C', true, 0);
        PDF::MultiCell(220, 0, 'PART NUMBER / DESCRIPTION', 'B', 'L', true, 0);
        PDF::MultiCell(95, 0, 'PRICE       ', 'B', 'R', true, 0);
        PDF::MultiCell(10, 0, '', '', 'L', false, 0);
        PDF::MultiCell(48, 0, 'OPER', 'B', 'C', true, 0);
        PDF::MultiCell(220, 0, 'REPAIR INSTRUCTION', 'B', 'L', true, 0);
        PDF::MultiCell(95, 0, 'PRICE       ', 'B', 'R', true, 0);
        PDF::MultiCell(5, 0, '', '', 'L', false);
        PDF::SetFillColor(255, 255, 255);
    }

    public function print2_layout_PDF($params, $data, $labor)
    {
        $font = "";
        $fontbold = "";
        $fonttable = 9;
        $counting  = 0;
        $count1 = 0;
        $count2 = 0;
        $tax = 0;
        $display_jobtitle = '';
        $counted    = count($data);
        $laborCount = count($labor);
        $total1 = 0;
        $total2 = 0;
        $ftotal = 0;

        $rowlimit1 = 13;
        $rowlimit2 = 13;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->print2_PDF_header($params, $data);

        $startPage = PDF::getPage();

        // PARTS - Left Side
        PDF::SetY(165);
        for ($i = 0; $i < $counted; $i++) {

            $qty = !empty($data[$i]['qty']) ? $data[$i]['qty'] : 0;
            $uom = !empty($data[$i]['uom']) ? $data[$i]['uom'] : '';
            $itemname = !empty($data[$i]['itemname']) ? $data[$i]['itemname'] : '';
            $ext = !empty($data[$i]['ext']) ? $data[$i]['ext'] : 0;

            $arr_qty = $this->reporter->fixcolumn([$this->formatQty($qty)], '5',  0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '20', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
            $arr_ext = $this->reporter->fixcolumn([number_format($ext, 2)], '40', 0);

            $maxrow = $this->othersClass->getmaxcolumn([
                $arr_qty,
                $arr_uom,
                $arr_itemname,
                $arr_ext
            ]);

            for ($r = 0; $r < $maxrow; $r++) {
                $count1++;
                $isLastRow = ($i == $counted - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetFont($font, '', $fonttable);
                PDF::MultiCell(5, 20, '', '', 'L', false, 0);
                PDF::MultiCell(24, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(24, 20, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(220, 20, isset($arr_itemname[$r]) ? $arr_itemname[$r] : '', '',     'L', false, 0);
                PDF::MultiCell(95, 20, isset($arr_ext[$r]) ? $arr_ext[$r] : '', $border, 'R', false);
            }

            $total1 += $ext;

            if ($count1 > $rowlimit1) {
                PDF::SetXY(340, 457.5);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true);
                PDF::SetXY(350, 460);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);

                $this->print2_PDF_footer($params, $data, $total1, $total2, false);
                $this->print2_PDF_header($params, $data);
                PDF::SetY(165);
                $count1 = 0;
                $count2 = 0;
            }
        }

        PDF::SetLineWidth(2);
        PDF::MultiCell(273, 0, '', '',  'R', false, 0);
        PDF::MultiCell(95,  0, number_format($total1, 2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        // LABOR - Right Side
        PDF::setPage($startPage);
        PDF::SetXY(390, 165);
        $preJob = null;

        for ($i = 0; $i < $laborCount; $i++) {

            $line = !empty($labor[$i]['line']) ? $labor[$i]['line'] : '';
            $jobtitle = !empty($labor[$i]['jobtitle']) ? $labor[$i]['jobtitle'] : '';
            $description = !empty($labor[$i]['description']) ? $labor[$i]['description'] : '';
            $cost = !empty($labor[$i]['cost']) ? $labor[$i]['cost'] : 0;

            // Track jobtitle grouping
            $same = ($jobtitle === $preJob);
            if ($same) {
                $display_jobtitle = '';
            } else {
                $display_jobtitle = $jobtitle;
                $counting++;
            }
            $preJob = $jobtitle;

            $arr_jobtitle = $this->reporter->fixcolumn([$display_jobtitle], '40', 0) ?: [''];
            $arr_description = $this->reporter->fixcolumn([$description], '40', 0);
            $arr_cost = $this->reporter->fixcolumn([number_format($cost, 2)], '10', 0);

            $maxrow = $this->othersClass->getmaxcolumn([
                $arr_jobtitle,
                $arr_description,
                $arr_cost
            ]);

            for ($r = 0; $r < $maxrow; $r++) {
                $count2++;
                $isLastRow = ($i == $laborCount - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetX(390);
                PDF::SetFont($font, '', $fonttable);

                // Print jobtitle row only when jobtitle changes
                if (!$same && $r == 0) {
                    PDF::MultiCell(10, 20, '', '', 'L', false, 0);
                    PDF::MultiCell(48, 20, $same ? '' : $line, '', 'C', false, 0);
                    if (empty($description) || $description == null) {
                        PDF::MultiCell(220, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 0);
                    } else {
                        $count2++;
                        PDF::MultiCell(220, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 1);
                    }
                }

                // Print description with cost
                if ($r == 0 && !empty($description)) {
                    PDF::SetX(460);
                    PDF::SetFont($font, '', $fonttable);
                    PDF::MultiCell(210, 20, '--- ' . $description, '', 'L', false, 0);
                    PDF::MultiCell(95, 20, isset($arr_cost[$r]) ? $arr_cost[$r] : '', $border, 'R', false);
                } else if ($r == 0 && empty($description)) {
                    PDF::MultiCell(95, 20, isset($arr_cost[$r]) ? $arr_cost[$r] : '', '', 'R', false);
                }
            }

            PDF::SetX(397);
            $total2 += $cost;

            if ($count2 > $rowlimit2) {
                PDF::SetXY(340, 457.5);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true);
                PDF::SetXY(350, 460);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);

                $this->print2_PDF_footer($params, $data, $total1, $total2, false);
                $this->print2_PDF_header($params, $data);
                PDF::SetXY(390, 165);
                $count1 = 0;
                $count2 = 0;
            }
        }

        PDF::SetLineWidth(2);
        PDF::MultiCell(273, 0, '', '',  'R', false, 0);
        PDF::MultiCell(94.5,  0, number_format($total2, 2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        PDF::setPage(PDF::getNumPages());
        $this->print2_PDF_footer($params, $data, $total1, $total2, true);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function print2_PDF_footer($params, $data, $total1, $total2, $totals = true)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fonttable = 9;
        $fontrows = 7;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetY(475);
        PDF::MultiCell(746, 15, '  RECOMMENDED REPAIRS: ', 'TLBR', '', false);

        PDF::SetY(493);
        PDF::SetFont($fontbold, '', 6);
        PDF::MultiCell(560, 0, '', 'TLR', '', false);
        PDF::MultiCell(560, 0, ' TERMS AND PROVISIONS', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(560, 0, ' 1. Customer authorizes DAS or its employees to operate vehicle for purposes of testing, inspection and/or delivery at customer’s risk', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 2. Customer acknowledges an express mechanic’s lien to secure the amount of repairs indicated thereto.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 3. DAS will not be responsible or liable for loss or damage to vehicle or articles left in case of fire, theft, accident, flood, typhoon, earthquake and/or other causes beyon the company’s control.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 4. Customer agrees to pay interest at the of 2% per month on all accounts not paid when due.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 5. Vehicle not claimed and withdrawn from the company’s premises within five (5) days from date of completion will be charged storage of P 70.00 per day until withdrawn.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 6. Oral agreements, representations or promises not incorporated herein are unauthorized and therefore not binding.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, " 7. In case of litigation for non-payment of this repair order invoice, customer agrees to submit himself to the jurisdiction of the courts of Manila, Quezon City or Makati City at the option of the company \n     and to pay 25% for attorney’s fees, minimum of P 500.00 plus cour", 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(746, 0, '', 'L', '', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(30, 0, '', 'L', '', false, 0);
        PDF::MultiCell(100, 0, 'CUSTOMER CONFORME :', '', '', false, 0);
        PDF::MultiCell(200, 0, '', 'B', '', false, 0);
        PDF::MultiCell(230, 0, '', 'R', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(470, 0, 'PrintName and Signature', 'L', 'C', false, 0);
        PDF::MultiCell(90, 0, '', 'R', 'C', false);

        PDF::MultiCell(560, 0, '', 'LRB', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false);

        PDF::SetXY(600, 500);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'LABOR', '', '', false, 0); //186
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 12, $totals ? number_format($total2, 2) : '', '', 'R', false);
        PDF::SetX(600);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'PARTS', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 12, $totals ? number_format($total1, 2) : '', '', 'R', false);

        PDF::SetX(600);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'SUBLET', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false);
        PDF::SetXY(600, 540);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'SUPPLIES', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false);
        PDF::SetX(600);

        $tax = $total1 * ((isset($data[0]['tax']) ? $data[0]['tax'] : '') / 100);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'TAX', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 12, $totals ? number_format($tax, 2) : '', '', 'R', false);

        $ftotal = $total1 + $total2 + $tax;
        PDF::SetXY(600, 570);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 20, 'TOTAL', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(80, 12, $totals ? number_format($ftotal, 2) : '', '', 'R', false);
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
