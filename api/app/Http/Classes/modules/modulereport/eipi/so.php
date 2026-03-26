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

class so
{

    private $modulename = "Sales Order";
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

    public function createreportfilter()
    {
        $fields = ['radioprint', 'radiosjeipi', 'prepared', 'checked', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        data_set($col1, 'radiosjeipi.options', [
            ['label' => 'Default', 'value' => '0', 'color' => 'green'],
            ['label' => 'Withdrawal Slip', 'value' => '1', 'color' => 'green']
        ]);

        data_set($col1, 'received.label', 'Loaded By');
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);

        return $this->coreFunctions->opentable(
            "select 
            'PDFM' as print,
             '0' as radiosjeipi,
            '$username' as prepared,
            '' as checked,
            '' as received
            "
        );
    }
    public function report_default_query($trno)
    {
        $query = "select head.docno,head.clientname,head.address, head.dateid,head.terms,
                         head.rem,head.agent,head.yourref,agent.clientname as agentname,
                         item.barcode, item.itemname, stock.isamt as gross,
                         stock.amt as netamt, stock.isqty as qty,stock.uom, stock.disc, 
                         stock.ext, stock.line,head.shipto,stock.expiry,stock.rem as srem
                from hsohead as head
                left join hsostock as stock on stock.trno=head.trno
                left join client as agent on agent.client=head.agent
                left join item on item.itemid=stock.itemid
                where head.doc='SO' and head.trno='$trno'
                union all
                select head.docno,head.clientname,head.address, head.dateid,head.terms,
                       head.rem,head.agent,head.yourref,agent.clientname as agentname,
                       item.barcode, item.itemname, stock.isamt as gross,
                       stock.amt as netamt, stock.isqty as qty,stock.uom, stock.disc, 
                       stock.ext, stock.line,head.shipto,stock.expiry,stock.rem as srem
                from sohead as head
                left join sostock as stock on stock.trno=head.trno
                left join client as agent on agent.client=head.agent
                left join item on item.itemid=stock.itemid
                where head.doc='SO' and head.trno='$trno' order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn  

    public function reportplotting($params, $data)
    {
        $options = $params['params']['dataparams']['radiosjeipi'];
        switch ($options) {
            case '0':
                return $this->default_so_PDF($params, $data);
                break;
            case '1':
                return $this->withdrawal_PDF($params, $data);
                break;
        }
    }


    public function default_so_header_PDF($params, $data)
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
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n", '', 'C');

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Supplier : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Delivered To : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(430, 20, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "PO # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(80, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(420, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_so_PDF($params, $data)
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
        $this->default_so_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $qty = number_format($data[$i]['qty'], 2);
                $uom = $data[$i]['uom'];
                $ext = number_format($data[$i]['ext'], 2);

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '12', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '67', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_ext]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(80, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(420, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                $totalext += $data[$i]['ext'];

                if (PDF::getY() > 900) {
                    $this->default_so_header_PDF($params, $data);
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


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Checked By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


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
