<?php

namespace App\Http\Classes\modules\modulereport\technolab;

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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ts
{
    private $modulename = "Transfer Slip";
    private $reportheader;
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
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $signatories = $this->othersClass->getSignatories($config);
        $approved = '';
        $received = '';
        $prepared = '';

        foreach ($signatories as $key => $value) {
            switch ($value->fieldname) {
                case 'approved':
                    $approved = $value->fieldvalue;
                    break;
                case 'received':
                    $received = $value->fieldvalue;
                    break;
                case 'prepared':
                    $prepared = $value->fieldvalue;
                    break;
            }
        }

        $paramstr = "select
          'PDFM' as print,
          '$prepared' as prepared,
          '$approved' as approved,
          '$received' as received";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];
        $query = "select head.vattype, head.tax, stock.rem as remarks, 
                    client.tel, wh.tel as wtel, date(head.dateid) as dateid, 
                    head.docno, client.client, client.clientname, head.address, head.terms,
                    head.rem, item.barcode,stock.line,
                    item.itemname, item.color, item.sizeid, stock.isqty as qty, stock.uom, 
                    stock.cost as acost,stock.isamt as cost,stock.amt, 
                    stock.disc, stock.ext, wh.client as swh, 
                    wh.clientname as whname,stock.expiry, wh.addr, 
                    client.addr as fromaddr, stock.loc, stock.loc2,stock.sortline
                    from lahead as head 
                    left join lastock as stock on stock.trno=head.trno 
                    left join client on client.client=head.client
                    left join client as wh on wh.clientid = stock.whid
                    left join item on item.itemid=stock.itemid
                    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
                    union all
                    select head.vattype, head.tax,  stock.rem as remarks,  
                    client.tel, wh.tel as wtel,  date(head.dateid) as dateid, 
                    head.docno, client.client, client.clientname, head.address, head.terms,
                    head.rem, item.barcode,stock.line,
                    item.itemname, item.color, item.sizeid, stock.isqty as qty, stock.uom, 
                    stock.cost as acost,stock.isamt as cost,stock.amt, 
                    stock.disc, stock.ext, wh.client  as swh, 
                    wh.clientname as whname,stock.expiry, wh.addr, 
                    client.addr as fromaddr, stock.loc, stock.loc2,stock.sortline
                    from glhead as head left join glstock as stock on stock.trno=head.trno 
                    left join client on client.clientid=head.clientid
                    left join item on item.itemid=stock.itemid 
                    left join client as wh on wh.clientid=stock.whid
                    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
                    order by sortline,line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        return $this->default_TS_PDF($params, $data);
    }

    public function default_TS_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);

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

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        $this->reportheader->getheader($params);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Source WH: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Destination", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, "", '', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 11);
        PDF::MultiCell(90, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(60, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(60, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(140, 0, "DESCRIPTION", '', 'L', false, 0);

        PDF::MultiCell(120, 0, "LOT", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "EXPIRY", '', 'C', false, 0);

        if ($viewcost == '1') {
            PDF::MultiCell(70, 0, "COST", '', 'R', false, 0);
            PDF::MultiCell(90, 0, "TOTAL", '', 'R', false);
        } else {
            PDF::MultiCell(70, 0, "", '', 'R', false, 0);
            PDF::MultiCell(90, 0, "", '', 'R', false);
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_TS_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = 10;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_TS_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $barcode = $data[$i]['barcode'];
            $qty = number_format($data[$i]['qty'], 2);
            $uom = $data[$i]['uom'];
            $amt = number_format($data[$i]['cost'], 2);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], 2);
            $lot = $data[$i]['loc'];
            $expiry = $data[$i]['expiry'];

            $itemlen = '';
            $itemname = '';
            $barcodelen = '';

            $itemname = $data[$i]['itemname'];
            $itemlen = '25';
            $barcodelen = '15';

            $arr_barcode = $this->reporter->fixcolumn([$barcode], $barcodelen, 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], $itemlen, 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '9', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
            $arr_lot = $this->reporter->fixcolumn([$lot], '22', 0);
            $arr_expiry = $this->reporter->fixcolumn([$expiry], '12', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext, $arr_lot, $arr_expiry]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(90, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(140, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r]  : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                PDF::MultiCell(120, 15, (isset($arr_lot[$r]) ? $arr_lot[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, (isset($arr_expiry[$r]) ? $arr_expiry[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                if ($viewcost == '1') {
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(90, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                } else {
                    PDF::MultiCell(70, 15, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(90, 15, '', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
            }

            $totalext += $data[$i]['ext'];

            if (PDF::getY() > 900) {
                $this->default_TS_header_PDF($params, $data);
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
        PDF::MultiCell(560, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L');

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
}
