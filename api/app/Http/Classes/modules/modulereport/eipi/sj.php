<?php

namespace App\Http\Classes\modules\modulereport\eipi;

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

class sj
{

    private $modulename = "Sales Journal";
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
        $fields = ['radioprint', 'radiosjeipi', 'prepared', 'received', 'checked', 'print'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        data_set($col1, 'prepared.label', 'Prepared / Booked By');
        data_set($col1, 'received.label', 'Loaded By');

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);

        return $this->coreFunctions->opentable(
            "select
        'PDFM' as print,
        '0' as reporttype,
        '0' as radiosjeipi,
        '$username' as prepared,
        '' as received,
        '' as checked
        "
        );
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select head.trno,stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
                        right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
                        head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
                        item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
                        item.sizeid, ag.clientname as agname, item.brand,
                        wh.client as whcode, wh.clientname as whname,head.shipto,head.vattype,stock.noprint,stock.itemstatus,
                        cat.cat_name as bstyle,stock.expiry,sku.sku
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join client on client.client=head.client
                    left join item on item.itemid=stock.itemid
                    left join client as ag on ag.client=head.agent
                    left join client as wh on wh.client=head.wh
                    left join category_masterfile as cat on cat.cat_id=client.category
                    left join sku on sku.itemid=stock.itemid and sku.clientid=client.clientid
                    where head.doc='sj' and head.trno='$trno' and stock.noprint <> 1
                    UNION ALL
                    select head.trno,stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
                        right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
                        head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
                        item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
                        item.sizeid, ag.clientname as agname, item.brand,
                        wh.client as whcode, wh.clientname as whname,head.shipto,head.vattype,stock.noprint,stock.itemstatus,
                        cat.cat_name as bstyle,stock.expiry,sku.sku
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join client on client.clientid=head.clientid
                    left join item on item.itemid=stock.itemid
                    left join client as ag on ag.clientid=head.agentid
                    left join client as wh on wh.clientid=head.whid
                    left join category_masterfile as cat on cat.cat_id=client.category
                    left join sku on sku.itemid=stock.itemid and sku.clientid=head.clientid
                    where head.doc='sj' and head.trno='$trno' and stock.noprint <> 1 order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        $options = $params['params']['dataparams']['radiosjeipi'];
        switch ($options) {
            case 0:
                return $this->default_sj_PDF($params, $data);
                break;
            case 1:
                // return $this->salesinvoice_PDF($params, $data);
                return $this->salesinvoicenew_PDF($params, $data);
                break;
            case 2:
                return $this->salesinvoice_kmp_PDF($params, $data);
                break;
            case 3:
                return $this->transmittal_PDF($params, $data);
                break;
                // case 4:
                //     return $this->withdrawal_PDF($params, $data);
                //     break;
        }
    }

    public function default_sj_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
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

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
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
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
        PDF::MultiCell(50, 0, "(+/-) %", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_sj_PDF($params, $data)
    {

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
        $this->default_sj_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $qty = number_format($data[$i]['qty'], 0);
                $uom = $data[$i]['uom'];
                $amt = number_format($data[$i]['amt'], 2);
                $disc = $data[$i]['disc'];
                $ext = number_format($data[$i]['ext'], 2);

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                $totalext += $data[$i]['ext'];

                if (PDF::getY() > 900) {
                    $this->default_sj_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(253, 0, 'Booked By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Checked By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['checked'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }



    public function salesinvoice_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(20, 20);
        PDF::AddPage('p', [800, 1000]);

        PDF::SetFont($fontbold, '', $fontsize);

        $date = $data[0]['dateid'];
        $date = date_create($date);
        $date = date_format($date, "F d, Y");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '645', '85'); //620,85

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(300, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '110',  '85');
        PDF::MultiCell(500, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '110',  '105');
        PDF::MultiCell(200, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '110',  '125');
        PDF::MultiCell(500, 0, isset($data[0]['shipto']) ? $data[0]['shipto'] : '', '', 'L', false, 1, '110',  '147');

        PDF::MultiCell(100, 0, isset($data[0]['bstyle']) ? $data[0]['bstyle'] : '', '', 'L', false, 1, '370',  '125'); //360,125

        PDF::SetFont($font, '', 40);
        PDF::MultiCell(700, 0, '', '', 'L', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(190, 0, isset($data[0]['ourref']) ? $data[0]['ourref'] : '', '', 'L', false, 1, '645', '105'); //620,105
        PDF::MultiCell(190, 0, isset($data[0]['yourref']) ? $data[0]['yourref'] : '', '', 'L', false, 1, '645', '125'); //620,125
        PDF::MultiCell(190, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, '645', '147'); //620,147
    }

    public function salesinvoice_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $count = $page = 13;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->salesinvoice_header_PDF($config, $data);
        PDF::SetFont($font, '', 23);
        PDF::MultiCell(700, 0, '', '', '', false, 1, '', '170'); //166
        $countarr = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $itemtoprint = '';

                if ($data[$i]['sku'] != '') {
                    $itemtoprint = $data[$i]['sku'] . ' - ';
                }

                $itemtoprint .= $data[$i]['itemname'];

                $arritem = [$itemtoprint];
                $arr_item = $this->reporter->fixcolumn($arritem, 80);
                $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 0)], 12);
                $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], 10);
                $arr_srem = $this->reporter->fixcolumn([$data[$i]['srem']], 20);
                $arr_amt = $this->reporter->fixcolumn([number_format($data[$i]['amt'], 2)], 20);
                $arr_ext = $this->reporter->fixcolumn([number_format($data[$i]['ext'], 2)], 20);

                $maxrow = $this->othersClass->getmaxcolumn($arr_item, $arr_qty, $arr_uom, $arr_srem, $arr_amt, $arr_ext);

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {

                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
                        PDF::MultiCell(100, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'R', false, 0, '-15', '', true, 1);
                        PDF::MultiCell(100, 20, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'L', false, 0, '110', '', false, 1);
                        PDF::MultiCell(300, 20, isset($arr_item[$r]) ? $arr_item[$r] : '', '', 'L', false, 0, '182', '', false, 1); //170
                        PDF::MultiCell(100, 20, isset($arr_srem[$r]) ? $arr_srem[$r] : '', '', 'R', false, 0, '435', '', false, 1);
                        PDF::MultiCell(100, 20, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0, '537', '', false, 1); //507
                        PDF::MultiCell(100, 20, isset($arr_ext[$r]) ? $arr_ext[$r] : '', '', 'R', false, 1, '677', '', false, 1); //654

                    }
                }
                $totalext += $data[$i]['ext'];
            }
        }
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);

        PDF::SetFont($font, '', 13);
        if ($data[0]['vattype'] == 'VATABLE') {
            $vatsales = $totalext / 1.12;
            PDF::MultiCell(75, 0, number_format($vatsales, 2), '', 'R', false, 1, '701', '603'); //680,590

            $vat = $totalext - $vatsales;
            PDF::MultiCell(75, 0, number_format($vat, 2), '', 'R', false, 1, '701', '685'); //680,662
        }
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(355, 0, '', '', 'C', false, 0);
        PDF::MultiCell(75, 0, number_format($totalext, 2), '', 'R', false, 1, '701', '706'); //678,684

        PDF::SetFont($font, '', $fontsize);
        $prep = $config['params']['dataparams']['prepared'];
        $app = $config['params']['dataparams']['checked'];

        PDF::MultiCell(175, 15, $prep, '', 'C', false, 0, '-5', '775'); //Prepared  -5,755
        PDF::MultiCell(175, 15, $app, '', 'C', false, 0, '210', ''); //Checked 210,


        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function salesinvoicenew_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(20, 20);
        PDF::AddPage('p', [800, 1000]);

        PDF::SetFont($fontbold, '', $fontsize);

        $date = $data[0]['dateid'];
        $date = date_create($date);
        $date = date_format($date, "F d, Y");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '645', '85'); //620,85

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(300, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '110', '95');
        PDF::MultiCell(200, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '110', '115');
        PDF::MultiCell(500, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '110', '130');
        PDF::MultiCell(500, 0, isset($data[0]['shipto']) ? $data[0]['shipto'] : '', '', 'L', false, 1, '110', '150');

        PDF::SetFont($font, '', 40);
        PDF::MultiCell(700, 0, '', '', 'L', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(190, 0, isset($data[0]['ourref']) ? $data[0]['ourref'] : '', '', 'L', false, 1, '645', '105'); //620,105
        PDF::MultiCell(190, 0, isset($data[0]['yourref']) ? $data[0]['yourref'] : '', '', 'L', false, 1, '645', '125'); //620,125
        PDF::MultiCell(190, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, '645', '147'); //620,147
    }

    public function salesinvoicenew_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $count = 780; //790
        $page = 770; //780
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->salesinvoicenew_header_PDF($config, $data);

        $trno = $data[0]['trno'];
        $total = "select sum(ext) as ext from (select sum(ext) as ext from lastock where trno = $trno
              union select sum(ext) as ext from glstock where trno = $trno) as a ";

        $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

        $grandtotal = $totresult[0]['ext'];


        PDF::SetFont($font, '', 23);
        PDF::MultiCell(700, 0, '', '', '', false, 1, '', '170'); //166
        $countarr = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $itemtoprint = '';

                if ($data[$i]['sku'] != '') {
                    $itemtoprint = $data[$i]['sku'] . ' - ';
                }

                $itemtoprint .= $data[$i]['itemname'];

                $arritem = [$itemtoprint];
                $arr_item = $this->reporter->fixcolumn($arritem, 80);
                $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 0)], 12);
                $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], 10);
                $arr_amt = $this->reporter->fixcolumn([number_format($data[$i]['amt'], 2)], 20);
                $arr_ext = $this->reporter->fixcolumn([number_format($data[$i]['ext'], 2)], 20);

                $maxrow = $this->othersClass->getmaxcolumn($arr_item, $arr_qty, $arr_uom, $arr_amt, $arr_ext);

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
                        PDF::MultiCell(100, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'R', false, 0, '-15', '', true, 1);
                        PDF::MultiCell(100, 20, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'L', false, 0, '100', '', false, 1);
                        PDF::MultiCell(400, 20, isset($arr_item[$r]) ? $arr_item[$r] : '', '', 'L', false, 0, '170', '', false, 1); //170
                        PDF::MultiCell(100, 20, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0, '517', '', false, 1); //507
                        PDF::MultiCell(100, 20, isset($arr_ext[$r]) ? $arr_ext[$r] : '', '', 'R', false, 1, '667', '', false, 1); //654

                        if (PDF::getY() > 500) { //580
                            $this->eipi_sj_footer_PDF($config, $data, $grandtotal);
                            $this->salesinvoicenew_header_PDF($config, $data);
                        }
                    }
                }
                $totalext += $data[$i]['ext'];
            }
        }

        $this->eipi_sj_footer_PDF($config, $data, $grandtotal);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function eipi_sj_footer_PDF($config, $data, $grandtotal)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $font = "";
        $count = 890;
        $page = 880;
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);

        PDF::SetFont($font, '', 13);

        $vatsales = $grandtotal / 1.12;
        $vat = $grandtotal - $vatsales;

        PDF::MultiCell(75, 0, number_format($grandtotal, 2), '', 'R', false, 1, '691', '603');
        PDF::MultiCell(75, 0, number_format($grandtotal, 2), '', 'R', false, 1, '691', '603');

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(355, 0, '', '', 'C', false, 0);
        PDF::MultiCell(75, 0, number_format($grandtotal, 2), '', 'R', false, 1, '691', '706');


        switch ($data[0]['vattype']) {
            case 'VATABLE':
                PDF::MultiCell(75, 0, number_format($vatsales, 2), '', 'L', false, 1, '310', '525');
                PDF::MultiCell(75, 0, number_format($vat, 2), '', 'L', false, 1, '310', '557');
                break;
            case 'ZERO-RATED':
                PDF::MultiCell(75, 0, number_format($grandtotal, 2), '', 'L', false, 1, '310', '596');
                break;
        }


        PDF::SetFont($font, '', $fontsize);
        $prep = $config['params']['dataparams']['prepared'];
        $app = $config['params']['dataparams']['checked'];

        PDF::MultiCell(175, 15, $prep, '', 'C', false, 0, '-5', '775');
        PDF::MultiCell(175, 15, $app, '', 'C', false, 0, '210', '');
    }


    public function salesinvoice_kmp_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(20, 20);
        PDF::AddPage('p', [800, 1000]);


        PDF::SetFont($fontbold, '', $fontsize);

        $date = $data[0]['dateid'];
        $date = date_create($date);
        $date = date_format($date, "F d, Y");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '635', '70');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(300, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '105',  '70');
        PDF::MultiCell(500, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '105',  '90');
        PDF::MultiCell(200, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '105',  '110');
        PDF::MultiCell(200, 0, isset($data[0]['shipto']) ? $data[0]['shipto'] : '', '', 'L', false, 1, '105',  '130');

        PDF::MultiCell(100, 0, isset($data[0]['bstyle']) ? $data[0]['bstyle'] : '', '', 'L', false, 1, '360',  '110');

        PDF::SetFont($font, '', 40);
        PDF::MultiCell(700, 0, '', '', 'L', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(190, 0, isset($data[0]['ourref']) ? $data[0]['ourref'] : '', '', 'L', false, 1, '635', '90');
        PDF::MultiCell(190, 0, isset($data[0]['yourref']) ? $data[0]['yourref'] : '', '', 'L', false, 1, '635', '110');
        PDF::MultiCell(190, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, '635', '130');
    }

    public function salesinvoice_kmp_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $count = $page = 13;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->salesinvoice_kmp_header_PDF($config, $data);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(700, 0, '', '', '', false, 1, '', '166');

        $countarr = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $item = $this->reporter->fixcolumn([$data[$i]['itemname']], '95', 0);
                $arritem = (str_split($data[$i]['itemname'], 30));
                $maxrow = 1;
                $countarr = count($arritem);
                $maxrow = $countarr;

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {
                        if ($r == 0) {
                            $qty = round($data[$i]['qty'], 0);
                            $item = isset($arritem[$r]) ? $arritem[$r] : '';
                            PDF::SetFont($font, '', $fontsize);
                            PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
                            PDF::MultiCell(100, 20, $qty, '', 'R', false, 0, '-18', '', false, 1);
                            PDF::MultiCell(100, 20, $data[$i]['uom'], '', 'L', false, 0, '108', '', false, 1);
                            PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '180', '', false, 1);
                            PDF::MultiCell(100, 20, $data[$i]['srem'], '', 'R', false, 0, '435', '', false, 1);
                            PDF::MultiCell(100, 20, number_format($data[$i]['amt'], 2), '', 'R', false, 0, '510', '', false, 1);
                            PDF::MultiCell(100, 20, number_format($data[$i]['ext'], 2), '', 'R', false, 1, '655', '', false, 1);
                        }
                    }
                }
                $totalext += $data[$i]['ext'];
            }
        }
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);

        PDF::SetFont($font, '', 13);

        if ($data[0]['vattype'] == 'VATABLE') {
            $vatsales = $totalext / 1.12;
            PDF::MultiCell(75, 0, number_format($vatsales, 2), '', 'R', false, 1, '680', '580');

            $vat = $totalext - $vatsales;
            PDF::MultiCell(75, 0, number_format($vat, 2), '', 'R', false, 1, '680', '650');
        }
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(355, 0, '', '', 'C', false, 0);
        PDF::MultiCell(75, 0, number_format($totalext, 2), '', 'R', false, 1, '680', '670');

        PDF::SetFont($font, '', $fontsize);

        $prep = $config['params']['dataparams']['prepared'];
        $app = $config['params']['dataparams']['checked'];

        PDF::MultiCell(175, 15, $prep, '', 'L', false, 0, '48', '745'); //Prepared
        PDF::MultiCell(175, 15, $app, '', 'L', false, 0, '250', ''); //Checked

        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function transmittal_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(20, 20);
        PDF::AddPage('p', [800, 1000]);

        PDF::SetFont($fontbold, '', $fontsize);

        $date = $data[0]['dateid'];

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '400', '63'); //380,67
        PDF::MultiCell(200, 0, isset($data[0]['ourref']) ? $data[0]['ourref'] : '', '', 'L', false, 1, '490',  '63'); //480

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(230, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '40',  '80'); //30,85

        PDF::MultiCell(200, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '326',  '115'); //305,115


        PDF::SetFont($font, '', 40);
        PDF::MultiCell(700, 0, '', '', 'L', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(190, 0, isset($data[0]['yourref']) ? $data[0]['yourref'] : '', '', 'L', false, 1, '35', '200');
        PDF::MultiCell(190, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 1, '190', '200');
        PDF::MultiCell(190, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, '430', '200'); //410,200
    }

    public function transmittal_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $count = $page = 13;
        $totalext = 0;
        $maxrow = 1;
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->transmittal_header_PDF($config, $data);
        PDF::SetFont($font, '', 100);
        PDF::MultiCell(700, 0, '', '', '', false, 1, '', '125');

        $countarr = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                // $itemtoprint = $data[$i]['itemname'];

                $itemtoprint = '';

                if (
                    $data[$i]['sku'] != ''
                ) {
                    $itemtoprint = $data[$i]['sku'] . ' - ';
                }

                $itemtoprint .= $data[$i]['itemname'];

                if ($data[$i]['srem'] != '') {
                    $itemtoprint .= ' - Notes: ' . $data[$i]['srem'];
                }

                $arritem = [$itemtoprint];
                $arr_item = $this->reporter->fixcolumn($arritem, 80);
                $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 0)], 12);

                $maxrow = $this->othersClass->getmaxcolumn($arr_item, $arr_qty);

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
                        PDF::MultiCell(100, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'R', false, 0, '-17', '', false, 1);
                        PDF::MultiCell(300, 20, isset($arr_item[$r]) ? $arr_item[$r] : '', '', 'L', false, 0, '100', '', false, 1);
                        PDF::MultiCell(100, 20, number_format($data[$i]['amt'], 2), '', 'R', false, 0, '360', '', false, 1); //345
                        PDF::MultiCell(100, 20, number_format($data[$i]['ext'], 2), '', 'R', false, 1, '453', '', false, 1); //438
                    }
                }
                $totalext += $data[$i]['ext'];
            }
        }

        if ($data[0]['rem'] != '') {
            PDF::SetFont($fontbold, '', 13);
            PDF::MultiCell(100, 0, 'Note: ', '', 'L', false, 0, 100, 630);
            PDF::SetFont($font, '', 13);
            PDF::MultiCell(500, 0, $data[0]['rem'], '', 'L', false, 1, 140, 630);
        }



        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);

        PDF::MultiCell(355, 0, '', '', 'C', false, 0);
        PDF::MultiCell(75, 0, number_format($totalext, 2), '', 'R', false, 1, '482', '630'); //462,620


        $prep = $config['params']['dataparams']['prepared'];
        $app = $config['params']['dataparams']['checked'];
        PDF::MultiCell(175, 15, $app, '', 'C', false, 0, '-2', 742); //Checked


        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function withdrawal_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(20, 20);
        PDF::AddPage('p', [800, 1000]);

        PDF::SetFont($fontbold, '', $fontsize);

        $date = $data[0]['dateid'];
        $date = date_create($date);
        $date = date_format($date, "F d, Y");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, 400, 130);

        PDF::SetFont($fontbold, '', $fontsize);

        $payor = $data[0]['clientname'];
        $cpayor = strlen($payor);
        if ($cpayor <= 40) {
            PDF::MultiCell(290, 0,  $payor, '', 'L', false, 0, 80, 130);
        } else {
            PDF::MultiCell(290, 0, $payor, '', 'L', false, 0, 80, 115);
        }


        $addr = $data[0]['shipto'];
        $caddr = strlen($addr);

        if ($caddr <= 40) {
            PDF::MultiCell(290, 0,  $addr, '', 'L', false, 0, 80, 165);
        } else {
            PDF::MultiCell(290, 0, $addr, '', 'L', false, 0, 80, 150);
        }

        PDF::SetFont($font, '', 40);
        PDF::MultiCell(700, 0, '', '', 'L', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(190, 0, isset($data[0]['yourref']) ? $data[0]['yourref'] : '', '', 'L', false, 1, 400, 165);
    }

    public function withdrawal_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $count = $page = 13;
        $totalqty = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->withdrawal_header_PDF($config, $data);
        PDF::SetFont($font, '', 15);
        PDF::MultiCell(700, 0, '', '', '', false, 1, '', 199);

        $countarr = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $itemtoprint = $data[$i]['itemname'];

                if ($data[$i]['expiry'] != '') {
                    $itemtoprint .= ' - Expiry: ' . $data[$i]['expiry'];
                }

                if ($data[$i]['srem'] != '') {
                    $itemtoprint .= ' - Notes: ' . $data[$i]['srem'];
                }

                $arritem = [$itemtoprint];
                $arr_item = $this->reporter->fixcolumn($arritem, 95);
                $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 0)], 12);
                $maxrow = 1;
                $maxrow = $this->othersClass->getmaxcolumn($arr_item, $arr_qty);

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
                        PDF::MultiCell(100, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'R', false, 0, '-4', '', false, 1);
                        PDF::MultiCell(560, 20, isset($arr_item[$r]) ? $arr_item[$r] : '', '', 'L', false, 1, '125', '', false, 1);
                    }
                }
                $totalqty += $data[$i]['qty'];
            }
        }

        if ($data[0]['rem'] != '') {
            PDF::SetFont($fontbold, '', 13);
            PDF::MultiCell(100, 0, 'Note: ', '', 'L', false, 0, 120, 575); //543
            PDF::SetFont($font, '', 13);
            PDF::MultiCell(660, 0, $data[0]['rem'], '', 'L', false, 1, 160, 575); //543
        }


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);
        PDF::MultiCell(700, 0, '', '', '', false, 1);

        PDF::MultiCell(355, 0, '', '', 'C', false, 0);
        PDF::MultiCell(75, 0, number_format($totalqty, 2), '', 'R', false, 1, '20', '610');

        $prep = $config['params']['dataparams']['prepared'];
        $rec = $config['params']['dataparams']['received'];
        $app = $config['params']['dataparams']['checked'];

        PDF::MultiCell(175, 15, $prep, '', 'C', false, 0, '18', '660'); //Prepared
        PDF::MultiCell(175, 15, $rec, '', 'C', false, 0, '18', '720'); //Loaded
        PDF::MultiCell(175, 15, $app, '', 'C', false, 0, '305', ''); //Checked
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
