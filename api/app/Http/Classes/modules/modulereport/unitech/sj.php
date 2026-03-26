<?php

namespace App\Http\Classes\modules\modulereport\unitech;

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

class sj
{

    private $modulename = "DELIVERY RECEIPT";
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
        $fields = ['radioprint', 'radiorepamountformat', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radiorepamountformat.options', [
            ['label' => 'With Price (Receipt)', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Without Price (Receipt)', 'value' => '1', 'color' => 'orange'],
            ['label' => 'SJ with Price', 'value' => '2', 'color' => 'orange'],
            ['label' => 'SJ without Price', 'value' => '3', 'color' => 'orange']
        ]);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $companyid = $config['params']['companyid'];


        return $this->coreFunctions->opentable(
            "select 'PDFM' as print,
                    '0' as reporttype,
                    '' as prepared,
                    '' as approved,
                    '' as received,
                    '0' as amountformat"
        );
    }
    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select head.rem,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,head.address, head.terms, item.barcode, client.tin,item.itemname, stock.isqty as qty, 
                         stock.uom,stock.line,stock.iss,ifnull((select uom from uom where itemid=stock.itemid 
                         and uom.factor = 1),'') as uompcs,stock.isamt as gross, stock.disc, 
                         stock.ext,stock.rem as notes,uom.factor
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.client=head.agent
            left join client as wh on wh.client=head.wh
            left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
            where head.doc='sj' and head.trno='$trno'
            UNION ALL
            select head.rem,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
                    head.address, head.terms, item.barcode, client.tin,item.itemname, stock.isqty as qty, 
                    stock.uom,stock.line ,stock.iss,ifnull((select uom from uom where itemid=stock.itemid 
                         and uom.factor = 1),'') as uompcs, stock.isamt as gross, 
                    stock.disc, stock.ext,stock.rem as notes,uom.factor
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.clientid=head.agentid
            left join client as wh on wh.clientid=head.whid
            left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
            where head.doc='sj' and head.trno='$trno' order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {

        switch ($params['params']['dataparams']['amountformat']) {
            case 0:
                return $this->default_sj_PDF($params, $data);
                break;
            case 1:
                return $this->default_sj2_PDF($params, $data);
                break;
            case 2:
            case 3:
                return $this->default_other_PDF($params, $data);
                break;
        }
    }

    public function default_sj_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 14;
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
        PDF::SetMargins(30, 40);

        PDF::SetFont($font, '', 9);

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 0, '', '', 'C', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(500, 0, '', '', 'C', false);

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(250, 0, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(250, 0, "", '', 'L', false);

        $client = $data[0]['clientname'];

        $cclient = strlen($client);
        PDF::SetFont($font, '', $fontsize);

        if ($cclient <= 47) {
            PDF::MultiCell(350, 0, $client, '', 'L', false, 1, 100, 106);
        } else {
            PDF::MultiCell(350, 0, $client, '', 'L', false, 1, 100, 88);
        }

        switch ($params['params']['dataparams']['amountformat']) {
            case 0:
                PDF::MultiCell(150, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, 525,  87);
                PDF::MultiCell(150, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, 525,  106);
                PDF::MultiCell(350, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, 100,  125);
                PDF::MultiCell(150, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, 525,  125);
                PDF::MultiCell(550, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1, 100,  150);
                break;
            case 1:
                PDF::MultiCell(150, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, 525,  87);
                PDF::MultiCell(150, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, 525,  106);
                PDF::MultiCell(350, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, 100,  125);
                PDF::MultiCell(150, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, 525,  125);
                PDF::MultiCell(550, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1, 100,  150);
                break;
        }
    }

    public function default_sj_PDF($params, $data)
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
        $fontsize = 14;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_sj_header_PDF($params, $data);

        PDF::MultiCell(0, 35, "");
        $countarr = 0;
        $totalqty = 0;
        $totalamt = 0;
        $j = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $qty = round($data[$i]['qty'], 0) . 'x' . round($data[$i]['factor'], 0);
                $uom = $data[$i]['uompcs'];
                $itemname = $data[$i]['barcode'] . ' ' . $data[$i]['itemname'];
                $ext = number_format($data[$i]['ext'], 2);
                $iss = number_format(($data[$i]['qty'] * $data[$i]['factor']), 2);
                $amt = number_format($data[$i]['gross'] / $data[$i]['factor'], 2);


                $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '10', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '8', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '43', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_itemname, $arr_iss, $arr_amt, $arr_ext]);

                for ($r = 0; $r < $maxrow; $r++) {
                    $j++;
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(20, 25, '', '', 'L', false, 0, '10', '', true, 1);
                    PDF::MultiCell(100, 25, (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '-9', '', false, 1);
                    PDF::MultiCell(100, 25, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '105', '', false, 1);
                    PDF::SetFont($font, '', 11);
                    PDF::MultiCell(270, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '165', '', false, 1);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(80, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '405', '', false, 1);
                    PDF::MultiCell(100, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '495', '', false, 1);
                    PDF::MultiCell(100, 25, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '610', '', false, 1);
                }

                if ($j >= 25) { //lines

                    // PDF::SetFont($fontbold, '', $fontsize);
                    // PDF::MultiCell(100, 0, number_format($totalqty, $decimalcurr), '', 'L', false, 0, '', '');
                    // PDF::MultiCell(130, 0, number_format($totalamt, $decimalcurr), '', 'R', false, 1, '', '');
                    if (count($data) != $i + 1) {
                        PDF::SetFont($fontbold, '', 7);
                        PDF::MultiCell(0, 5, "");

                        PDF::SetFont($fontbold, '', 14);
                        PDF::MultiCell(20, 25, '', '', 'L', false, 0, '10', '', true, 1);
                        PDF::MultiCell(100, 25, '', '', 'R', false, 0, '-9', '', false, 1);
                        PDF::MultiCell(100, 25, '', '', 'L', false, 0, '105', '', false, 1);
                        PDF::SetFont($font, '', 11);
                        PDF::MultiCell(270, 25, '', '', 'L', false, 0, '165', '', false, 1);
                        PDF::SetFont($fontbold, '', 14);
                        PDF::MultiCell(80, 25, '', '', 'R', false, 0, '385', '', false, 1);
                        PDF::MultiCell(100, 25, '', '', 'R', false, 0, '495', '', false, 1);
                        PDF::MultiCell(100, 25, '', '', 'R', false, 1, '610', '', false, 1);


                        $this->default_sj_header_PDF($params, $data);
                        PDF::MultiCell(0, 35, "");
                        $j = 0;
                    }
                }
                $totalqty += $iss;
                $totalamt += $data[$i]['ext'];
            }
            // $this->getline($j);
            PDF::SetFont($fontbold, '', 7);
            PDF::MultiCell(0, 5, "");
            PDF::SetFont($fontbold, '', 14);
            PDF::MultiCell(20, 25, '', '', 'L', false, 0, '10', '', true, 1);
            PDF::MultiCell(100, 25, '', '', 'R', false, 0, '-9', '', false, 1);
            PDF::MultiCell(100, 25, '', '', 'L', false, 0, '105', '', false, 1);
            PDF::MultiCell(250, 25, '', '', 'L', false, 0, '165', '', false, 1);
            PDF::SetFont($fontbold, '', 14);
            PDF::MultiCell(100, 25, 'Amount Due: ', '', 'R', false, 0, '385', '', false, 1);
            PDF::MultiCell(100, 25, '', '', 'R', false, 0, '495', '', false, 1);
            PDF::MultiCell(100, 25, number_format($totalamt, $decimalcurr), '', 'R', false, 1, '610', '', false, 1);
        }
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function getline($line)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = 14;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        if ($line < 25) {
            $line = 25 - $line;
            for ($i = 0; $i < $line; $i++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 1);
                PDF::MultiCell(100, 25, '', '', 'R', false, 0, '', '', false, 1);
                PDF::MultiCell(100, 25, '', '', 'L', false, 0, '', '', false, 1);
                PDF::SetFont($font, '', 12);
                PDF::MultiCell(250, 25, '', '', 'L', false, 0, '', '', false, 1);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(100, 25, '', '', 'R', false, 0, '', '', false, 1);
                PDF::MultiCell(100, 25, '', '', 'R', false, 0, '', '', false, 1);
                PDF::MultiCell(100, 25, '', '', 'R', false, 1, '', '', false, 1);
            }
        }
    }
    public function default_sj2_PDF($params, $data)
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
        $fontsize = 14;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_sj_header_PDF($params, $data);

        PDF::MultiCell(0, 35, "");
        $countarr = 0;
        $totalqty = 0;
        $totalamt = 0;
        $j = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $qty = round($data[$i]['qty'], 0) . 'x' . round($data[$i]['factor'], 0);
                $uom = $data[$i]['uompcs'];
                $itemname = $data[$i]['barcode'] . ' ' . $data[$i]['itemname'];
                $iss = number_format(($data[$i]['qty'] * $data[$i]['factor']), 2);
                $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '8', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_qty,$arr_uom, $arr_itemname, $arr_iss]);

                for ($r = 0; $r < $maxrow; $r++) {
                    $j++;
                    PDF::SetFont($font, '', $fontsize);
                    // PDF::MultiCell(100, 25, $i + 1, '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(100, 25, '', '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(100, 25, (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '-9', '', false, 1);
                    PDF::MultiCell(100, 25, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '105', '', false, 1);
                    PDF::MultiCell(350, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '165', '', false, 1);
                    PDF::MultiCell(100, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 1, '495', '', false, 1);
                }
                if ($j >= 25) {

                    if (count($data) != $i + 1) {
                        PDF::SetFont($font, '', $fontsize);
                        // PDF::MultiCell(100, 25, $i + 1, '', 'L', false, 0, '', '', true, 1);
                        PDF::MultiCell(100, 25, '', '', 'L', false, 0, '', '', true, 1);
                        PDF::MultiCell(100, 25, (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '-9', '', false, 1);
                        PDF::MultiCell(100, 25, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '105', '', false, 1);
                        PDF::MultiCell(450, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '165', '', false, 1);
                        PDF::MultiCell(100, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 1, '495', '', false, 1);

                        $this->default_sj_header_PDF($params, $data);
                        PDF::MultiCell(0, 35, "");
                        $j = 0;
                    }
                }
                $totalqty += $iss;
                $totalamt += $data[$i]['ext'];
            }
            $this->getline($j);
            PDF::SetFont($fontbold, '', 7);
            PDF::MultiCell(0, 5, "");

            PDF::SetFont($font, '', $fontsize);
            // PDF::MultiCell(100, 25, $i + 1, '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(100, 25, '', '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(100, 25, '', '', 'R', false, 0, '-9', '', false, 1);
            PDF::SetFont($fontbold, '', 14);
            PDF::MultiCell(100, 25, '', '', 'L', false, 0, '105', '', false, 1);
            PDF::MultiCell(450, 25, '', '', 'L', false, 0, '165', '', false, 1);
            PDF::MultiCell(100, 25, '', '', 'R', false, 1, '495', '', false, 1);
            // PDF::SetFont($fontbold, '', $fontsize);
            // PDF::MultiCell(100, 0, number_format($totalqty, $decimalcurr), '', 'L', false, 0, 40, 730);
            // PDF::MultiCell(130, 0, number_format($totalamt, $decimalcurr), '', 'R', false, 1, 575, 730);
        }
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function default_other_header_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $amtformat = $params['params']['dataparams']['amountformat'];
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name, address, tel, code from center where code = '" . $center . "'";
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
        PDF::SetMargins(30, 30);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
        PDF::MultiCell(0, 0, "\n");

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

        // PDF::SetFont($font, '', $fontsize);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Supplier : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


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
        PDF::MultiCell(735, 0, '', 'T');

        PDF::SetFont($font, 'B', 11);

        if ($amtformat == '2') {
            PDF::MultiCell(70, 0, "BARCODE", '', 'L', false, 0);
            PDF::MultiCell(50, 0, "QTY", '', 'R', false, 0);
            PDF::MultiCell(70, 0, "UNIT", '', 'R', false, 0);
            PDF::MultiCell(60, 0, "PCS / PACK", '', 'C', false, 0);
            PDF::MultiCell(70, 0, "TOTAL PCS", '', 'R', false, 0);
            PDF::MultiCell(5, 0, "", '', 'L', false, 0);
            PDF::MultiCell(185, 0, "DESCRIPTION", '', 'L', false, 0);
            PDF::MultiCell(60, 0, "NOTES", '', 'L', false, 0);
            PDF::MultiCell(65, 0, "UNIT PRICE", '', 'R', false, 0);
            PDF::MultiCell(40, 0, "(+/-) %", '', 'R', false, 0);
            PDF::MultiCell(75, 0, "TOTAL", '', 'R', false);
        } else {
            PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
            PDF::MultiCell(65, 0, "QTY", '', 'C', false, 0);
            PDF::MultiCell(65, 0, "UNIT", '', 'C', false, 0);
            PDF::MultiCell(70, 0, "PCS / PACK", '', 'R', false, 0);
            PDF::MultiCell(90, 0, "TOTAL PCS", '', 'R', false, 0);
            PDF::MultiCell(10, 0, "", '', 'L', false, 0);
            PDF::MultiCell(285, 0, "DESCRIPTION", '', 'L', false, 0);
            PDF::MultiCell(90, 0, "NOTES", '', 'L', false);
        }


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(730, 20, '', 'B');
    }

    public function default_other_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $amtformat = $params['params']['dataparams']['amountformat'];
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
        $fontsize = "14";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_other_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(800, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $qty = number_format($data[$i]['qty'], 2);
                $factor = number_format($data[$i]['factor'], 2);
                $iss = number_format(($data[$i]['qty'] * $data[$i]['factor']), 2);
                $uom = $data[$i]['uompcs'];
                $amt = number_format($data[$i]['gross'], 2);
                if ($amtformat == '2') {
                    $amt = number_format($data[$i]['gross'] / $data[$i]['factor'], 2);
                }
                $disc = $data[$i]['disc'];
                $ext = number_format($data[$i]['ext'], 2);
                $notes = $data[$i]['notes'];

                if ($amtformat == '2') {
                    $arr_barcode = $this->reporter->fixcolumn([$barcode], '7', 0);
                    $arr_itemname = $this->reporter->fixcolumn([$itemname], '18', 0);
                    $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
                    $arr_factor = $this->reporter->fixcolumn([$factor], '10', 0);
                    $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
                    $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
                    $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                    $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
                    $arr_ext = $this->reporter->fixcolumn([$ext], '10', 0);
                    $arr_notes = $this->reporter->fixcolumn([$notes], '10', 0);

                    $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_factor, $arr_iss, $arr_uom, $arr_amt, $arr_disc, $arr_ext, $arr_notes]);
                } else {
                    $arr_barcode = $this->reporter->fixcolumn([$barcode], '10', 0);
                    $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
                    $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
                    $arr_factor = $this->reporter->fixcolumn([$factor], '10', 0);
                    $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
                    $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);
                    $arr_notes = $this->reporter->fixcolumn([$notes], '18', 0);

                    $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_factor, $arr_iss, $arr_uom, $arr_notes]);
                }

                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);

                    if ($amtformat == '2') {
                        PDF::MultiCell(70, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 15, ' ' . (isset($arr_factor[$r]) ? $arr_factor[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(70, 15, ' ' . (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(5, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(185, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 15, ' ' . (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(65, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(40, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(75, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                    } else {
                        PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(65, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(65, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(70, 15, ' ' . (isset($arr_factor[$r]) ? $arr_factor[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(90, 15, ' ' . (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(285, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(90, 15, ' ' . (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                    }



                    if (PDF::getY() > 900) {
                        $this->default_other_header_PDF($params, $data);
                    }
                }
                $totalext += $data[$i]['ext'];
            }

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(735, 0, '', 'B');

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(735, 0, '', '');

            if ($amtformat == '2') {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(605, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
                PDF::MultiCell(130, 0, number_format($totalext, $decimalcurr) . ' ', '', 'R');
            }


            PDF::SetFont($font, '', $fontsize);

            PDF::MultiCell(0, 0, "\n\n\n");


            PDF::MultiCell(245, 0, 'Prepared By: ', '', 'L', false, 0);
            PDF::MultiCell(245, 0, 'Approved By: ', '', 'L', false, 0);
            PDF::MultiCell(245, 0, 'Received By: ', '', 'L');

            PDF::MultiCell(0, 0, "\n");

            PDF::MultiCell(245, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
            PDF::MultiCell(245, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
            PDF::MultiCell(245, 0, $params['params']['dataparams']['received'], '', 'L');

            return PDF::Output($this->modulename . '.pdf', 'S');
        }
    }
}
