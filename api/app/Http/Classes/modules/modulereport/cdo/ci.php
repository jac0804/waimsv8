<?php

namespace App\Http\Classes\modules\modulereport\cdo;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ci
{
    private $modulename = "Spare Parts Issuance";
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
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Default', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Invoice', 'value' => '1', 'color' => 'orange']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $companyid = $config['params']['companyid'];

        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
        $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
        $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);

        $paramstr = "select
                    'PDFM' as print,
                    '0' as reporttype,
                    '$username' as prepared,
                    '$approved' as approved,
                    '$received' as received";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($config)
    {

        $trno = $config['params']['dataid'];
        $query = "select head.vattype, stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
        right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, head.clientname,
        head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
        item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
        item.sizeid, ag.clientname as agname, item.brand,
        wh.client as whcode, wh.clientname as whname,item.partno,head.tax
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join client as ag on ag.client=head.agent
        left join client as wh on wh.client=head.wh
        where head.doc='ci' and head.trno='$trno'
        UNION ALL
        select head.vattype, stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
        right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, head.clientname,
        head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
        item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
        item.sizeid, ag.clientname as agname, item.brand,
        wh.client as whcode, wh.clientname as whname,item.partno,head.tax
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client on client.clientid=head.clientid
        left join item on item.itemid=stock.itemid
        left join client as ag on ag.clientid=head.agentid
        left join client as wh on wh.clientid=head.whid
        where head.doc='ci' and head.trno='$trno' order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {
        $reporttype = $params['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '1':
                return $this->invoice_ci_PDF($params, $data);
                break;
            case '0':
                return $this->default_ci_PDF($params, $data);
                break;
        }
    }

    public function default_ci_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, 'ORDER FORM', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 20, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(150, 0, " DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, " PART NO.", '', 'L', false, 0);
        PDF::MultiCell(80, 0, "UNIT PRICE", '', 'R', false, 0);
        PDF::MultiCell(70, 0, "(+/-) %", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_ci_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_ci_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $partno = $data[$i]['partno'];
                $qty = number_format($data[$i]['qty'], 2);
                $uom = $data[$i]['uom'];
                $amt = number_format($data[$i]['amt'], 2);
                $disc = $data[$i]['disc'];
                $ext = number_format($data[$i]['ext'], 2);

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
                $arr_partno = $this->reporter->fixcolumn([$partno], '15', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '7', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_partno, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(150, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_partno[$r]) ? $arr_partno[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                }

                $totalext += $data[$i]['ext'];

                if (PDF::getY() > 900) {
                    $this->default_ci_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        $tax = isset($data[0]['tax']) ? $data[0]['tax'] : 0;

        if ($tax != 0) {
            $total = $totalext / 1.12;
            $tlvat = $total * .12;
        } else {
            $total = $totalext;
            $tlvat = 0;
        }
        PDF::MultiCell(600, 0, 'TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($total, $decimalcurr), '', 'R', false, 1);

        PDF::MultiCell(600, 0, 'VAT: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($tlvat, $decimalcurr), '', 'R', false, 1);

        PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function invoice_ci_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 8;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        $date = '';
        // $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';
        $date = isset($data[0]['dateid']) ? $data[0]['dateid'] : ''; // "2026-06-17"
        $timestamp = $date ? strtotime($date) : false;
        $month = $timestamp ? date('m', strtotime($date)) : '';
        $day = $timestamp ? date('d', strtotime($date)) : '';
        $year = $timestamp ? date('y', strtotime($date)) : '';

        PDF::SetXY(110, 50);
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(250, 0, '', '', 'R', false, 0);
        PDF::MultiCell(82, 0, '', '', 'C', false);

        PDF::SetY(87);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(246, 0, '', '', 'R', false, 0);
        PDF::MultiCell(30, 0, $month . '.' . $day, '', 'C', false, 0);
        PDF::MultiCell(16, 0, '', '', 'C', false, 0);
        PDF::MultiCell(30, 0, $year, '', 'C', false);

        PDF::SetY(103);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 10, '', '', 'R', false, 0);
        PDF::MultiCell(288, 10, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

        PDF::SetY(115);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 10, '', '', 'R', false, 0);
        PDF::MultiCell(135, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
        PDF::MultiCell(85, 10, '', '', 'L', false, 0);
        PDF::MultiCell(75, 10, '   ', '', 'L', false, 0);

        PDF::SetY(127);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(90, 10, '', '', 'L', false, 0);
        PDF::MultiCell(80, 10, '', '', 'L', false, 0);
        PDF::MultiCell(95, 10, '', '', 'L', false);

        PDF::SetY(140);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 10, '', '', 'L', false, 0);
        PDF::MultiCell(295, 10, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);

    }

    public function invoice_ci_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "8";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->invoice_ci_header_PDF($params, $data);
        $counted = count($data);
        $maxRowsPerPage = 13;
        $rowPerPage = 0;
        $totalext = 0;
        $totaldisc = 0;
        $rowHeight = 0;
        $sales1 = 0;
        $sales2 = 0; // vat-exempt sales
        $sales3 = 0; //zero rated
        $vatsales = 0;
        $vat = 0;
        $netVatamt = 0;
        $lessVat = 0;
        $lessDisc = 0;
        $addVat = 0;
        $withholdingTax = 0;
        $totalAmtDue = 0;
        $amountDue = 0;

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            PDF::SetY(178);
            PDF::SetCellPaddings(0, 2.5, 0, 0); // left, top, right, bottom
            for ($i = 0; $i < ($counted); $i++) {
                $maxrow = 1;

                $uom = $data[$i]['uom'];
                $itemname = $data[$i]['itemname'];
                $disc = $data[$i]['disc'];
                $qty = $this->formatQty($data[$i]['qty']);
                $amt = number_format($data[$i]['amt'], 2);
                $ext = number_format($data[$i]['ext'], 2);

                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
                $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);

                PDF::SetX(20);
                for ($r = 0; $r < $maxrow; $r++) {
                    if ($rowPerPage == $maxRowsPerPage) {
                        break 2;
                    }
                    $rowPerPage++;
                    PDF::SetFont($font, '', $fontsize);
                    // PDF::MultiCell(7, 13, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(25, 14, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(25, 14, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(10, 14, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(185, 14, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(40, 14, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 14, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                }

                $discountedAmt = $this->othersClass->Discount($data[$i]['amt'] * $data[$i]['qty'], $disc);
                $lineDiscAmt   = ($data[$i]['amt'] * $data[$i]['qty']) - $discountedAmt;
                $totalext += $data[$i]['ext'];
                // $totaldisc += $data[$i]['disc'];
                $totaldisc += $lineDiscAmt;
            }
        }

        // computation of VAT
        $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
        $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

        if ($vattype == 'VATABLE') {
            $vat = $totalext / 1.12 * 0.12;
            $netVat = $totalext / 1.12;
            $vatsales = $totalext - $vat;
            if ($ewtrate !=0){
                $withholdingTax = $netVat * ($ewtrate / 100);
            }
            $sales1 = $totalext;
        } else if ($vattype == 'NON-VATABLE') {
            $vat = 0;
            $sales2 = $totalext;
        } else if ($vattype == 'ZERO-RATED') {
            $vat = 0;
            $sales3 = $totalext;
        }

        $lessVat = $vat;
        $lessDisc = $totaldisc;
        $netVatamt = $totalext - $lessVat;
        $amountDue = $netVatamt - $lessDisc;
        $totalAmtDue = $totalext + $addVat;

        //Right Side
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetXY(283, 363);
        PDF::MultiCell(80, 13, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false); // total (inclusive)

        PDF::SetXY(283, 377);
        PDF::MultiCell(80, 13, $vatsales != 0 ? number_format($vatsales, 2) : '', '', 'R', false); // VATable sales 

        PDF::SetXY(283, 391);
        PDF::MultiCell(80, 13, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false); // VAT amount

        PDF::SetXY(283, 405);
        PDF::MultiCell(80, 13, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false); // total amount due

        //Left Side
        PDF::SetXY(140, 363);
        PDF::MultiCell(80, 13, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false); // zero-rated

        PDF::SetXY(140, 377);
        PDF::MultiCell(80, 13, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false); // VAT-exempt

        PDF::SetXY(140, 391);
        PDF::MultiCell(80, 13, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false); // discount

        PDF::SetXY(140, 405);
        PDF::MultiCell(80, 13, $withholdingTax != 0 ? number_format($withholdingTax, 2) : '', '', 'R', false); // VAT amount

        PDF::MultiCell(0, 0, "\n");


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    private function formatQty($number)
    {
        return rtrim(rtrim(number_format($number, 2), '0'), '.');
    }
}
