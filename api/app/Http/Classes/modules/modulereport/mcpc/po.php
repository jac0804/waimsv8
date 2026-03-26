<?php

namespace App\Http\Classes\modules\modulereport\mcpc;

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

class po
{

    private $modulename = "Purchase Order";
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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

        $fields = ['radioprint', 'received', 'prepared', 'approved', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }
    public function reportparamsdata($config)
    {
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);

        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '" . $username . "' as prepared,
            '' as approved,
            '' as received"
        );
    }
    public function report_default_query($trno)
    {

        $query = "select date(head.dateid) as dateid,wh.addr,concat(head.doc,right(head.docno, 5)) as ponum, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join client as wh on wh.client = head.wh
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid,wh.addr,concat(head.doc,right(head.docno, 5)) as ponum, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join client as wh on wh.client = head.wh
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        return $result;
    }
    public function reportplotting($config, $data)
    {
        return $config['params']['dataparams']['print'] == "PDFM" ? $this->default_PO_PDF($config, $data) : '';
    }
    public function default_PO_PDF($config, $data)
    {

        $companyid = $config['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $count = $page = 20;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_PO_header_PDF($config, $data);


        $countarr = 0;
        $i = 0;
        for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;


            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty']);
            $uom = $data[$i]['uom'];
            $amt = number_format($data[$i]['netamt'], 2);
            $ext = number_format($data[$i]['ext'], 2);

            $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

            for ($r = 0; $r < $maxrow; $r++) {

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(370, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(20, 15, "", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(90, 15, '' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(20, 15, "", '', 'C', false, 0, '', '', true, 0, false, true, 0, '', false);
                PDF::MultiCell(80, 15, '' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            }
            $totalext += $data[$i]['ext'];
            if (PDF::getY() > 900) {
                $this->default_PO_header_PDF($config, $data);
            }
        }

        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(70, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(370, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(20, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(90, 15, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(20, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 15, '', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($fontbold, '', 5);
        PDF::MultiCell(530, 0, '', '', '', false, 0);
        PDF::MultiCell(190, 0, '', 'T', '', false, 1);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 15, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 15, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(370, 15, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(20, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(90, 15, 'TOTAL AMOUNT', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(20, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 15, '' . number_format($totalext, $decimalcurr), 'B', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($fontbold, '', 2);
        PDF::MultiCell(530, 0, '', '', 'L', false, 0);
        PDF::MultiCell(190, 0, '', 'B', 'L', false, 1);

        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        if (!empty($data[0]['rem'])) {
            PDF::MultiCell(35, 0, 'NOTE: ', '', 'L', false, 0);
            PDF::MultiCell(223, 0, $data[0]['rem'], '', 'L', false, 0);
            PDF::MultiCell(446, 0, '', '', 'L', false, 1);
        }
        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $config['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function default_PO_header_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $dataid = $config['params']['dataid'];

        $qry = "select name,address,tel,shortname from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $font = "";
        $nfont = "";
        $nfontbold = "";
        $fontbold = "";
        $fontsize = 11;

        if (Storage::disk('sbcpath')->exists('/fonts/OPTIMA.TTF')) {
            $nfont = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/OPTIMA.TTF');
            $nfontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/OPTIMA_B.TTF');
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n\n");

        if (strtoupper($headerdata[0]->shortname != 'MULTICRYSTAL')) {
            // Blue color 
            $color   = array(36, 59, 117);      // text color
            $setfill = array(116, 143, 196);    // fill color
        } else {
            // Purple color 
            $color   = array(85, 26, 139);      // Deep Purple (text)
            $setfill = array(120, 81, 169);     // Royal Purple
        }

        // PDF::SetFont($nfontbold, '', 25);
        // PDF::SetTextColor($color[0], $color[1], $color[2]);
        // PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);

        PDF::SetFont($nfontbold, '', 25);
        // PDF::SetTextColor(36, 59, 117);
        PDF::SetTextColor($color[0], $color[1], $color[2]);
        PDF::MultiCell(0, 0, strtoupper($this->modulename), '', 'R');
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($nfontbold, '', 20);
        // PDF::SetTextColor(36, 59, 117);
        PDF::SetTextColor($color[0], $color[1], $color[2]);
        PDF::MultiCell(490, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0, '', '120');
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($nfontbold, '', 10);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(130, 0, '', '', 'C', false, 1, '', '');

        PDF::SetFont($nfontbold, '', 10);
        // PDF::SetTextColor(36, 59, 117);
        PDF::SetTextColor($color[0], $color[1], $color[2]);
        PDF::MultiCell(220, 0, strtoupper($headerdata[0]->address), '', 'L', false, 0, '', '140');
        PDF::MultiCell(270, 0, '', '', 'L', false, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($nfontbold, '', 10);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(130, 0, '', 'TLR', 'C', false, 1, '', '');

        PDF::SetFont($nfontbold, '', 10);
        PDF::MultiCell(520, 0, '', '', 'L', false, 0);
        PDF::SetFont($nfontbold, '', 10);
        PDF::MultiCell(70, 0, 'DATE', '', 'L', false, 0);
        PDF::SetFont($nfont, '', 10);
        $dateid = date("d-M-y", strtotime($data[0]['dateid']));
        PDF::MultiCell(130, 21, (isset($dateid) ? $dateid : ''), 'BLR', 'C', false, 1);

        PDF::SetFont($nfontbold, '', $fontsize);
        // PDF::SetTextColor(36, 59, 117);
        PDF::SetTextColor($color[0], $color[1], $color[2]);
        PDF::MultiCell(220, 0, '', '', 'L', false, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(280, 0, '', '', 'L', false, 0);
        PDF::SetFont($nfontbold, '', 10);
        PDF::MultiCell(90, 0, 'P.O. NUMBER', '', 'L', false, 0);
        PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : '-'), 'TBLR', 'C', false, 1, '', '');

        PDF::MultiCell(0, 0, "\n\n");

        // PDF::SetFillColor(116, 143, 196);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($nfontbold, '', $fontsize);
        PDF::MultiCell(160, 0, "VENDOR : ", 'TB', 'L', true, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(320, 10, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(240, 0, "DELIVERY ADDRESS : ", 'TB', 'L', true, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($nfontbold, '', $fontsize);
        PDF::MultiCell(220, 15, strtoupper($data[0]['clientname']), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(260, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 15, strtoupper($headerdata[0]->name), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(40, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($nfont, '', $fontsize);
        PDF::MultiCell(220, 0, $data[0]['address'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(260, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($nfont, '', $fontsize);
        PDF::MultiCell(200, 0, $data[0]['addr'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(40, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n\n");
        // PDF::SetFillColor(116, 143, 196);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($nfontbold, 'B', $fontsize);
        PDF::MultiCell(70, 0, "QUANTITY", 'TB', 'C', true, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 0, "UNIT", 'TB', 'C', true, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(370, 0, "DESCRIPTION", 'TB', 'C', true, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(20, 0, "", 'TB', 'L', true, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(90, 0, "UNIT PRICE", 'TB', 'C', true, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(20, 0, "", 'TB', 'L', true, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 0, "SUBTOTAL", 'TB', 'C', true, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }
}//end class
