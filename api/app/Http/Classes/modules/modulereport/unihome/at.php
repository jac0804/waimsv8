<?php

namespace App\Http\Classes\modules\modulereport\unihome;

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

class at
{
    private $modulename = "Actual Count";
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
        $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Actual Count', 'value' => 'AT', 'color' => 'red']
        ]);
        data_set($col1, 'radioreporttype.label', 'Report Format');
        data_set($col1, 'radioreporttype.name', 'format');
        data_set($col1, 'refresh.action', 'history');

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable("select 
      'PDFM' as print, 'AT' as format,
      '' as approved,
      '' as received,
      '' as prepared
    ");
    }

    public function report_default_query($filters)
    {
        $trno = md5($filters['params']['dataid']);
        $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,head.rem, item.barcode,
                    item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,stock.oqty as onhand, stock.asofqty, stock.rem as srem
                    from athead as head 
                    left join atstock as stock on stock.trno=head.trno 
                    left join client on client.client=head.wh
                    left join item on item.itemid = stock.itemid
                    where head.doc='AT' and md5(head.trno)='$trno'
                    union all
                    select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,head.rem, item.barcode,
                    item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,stock.oqty as onhand, stock.asofqty, stock.rem as srem
                    from hathead as head 
                    left join hatstock as stock on stock.trno=head.trno 
                    left join client on client.client=head.wh
                    left join item on item.itemid = stock.itemid
                    where head.doc='AT' and md5(head.trno)='$trno'
                    order by itemname";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == 'PDFM') {
            return $this->AT_PDF($params, $data);
        } else {
            return $this->default_ir_layout($params, $data);
        }
    }

    public function default_ir_layout($config, $result)
    {
        $companyid = $config['params']['companyid'];
        $decimal   = $this->companysetup->getdecimal('currency', $config['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $prepared = $config['params']['dataparams']['prepared'];
        $received = $config['params']['dataparams']['received'];
        $approved = $config['params']['dataparams']['approved'];

        $str = '';
        $count = 35;
        $page = 35;
        $totalcount = 0;
        $totalonhand = 0;
        $totaldiff = 0;

        $str .= $this->reporter->beginreport();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Inventory Reconciliation', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]['docno']) ? $result[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WAREHOUSE : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($result[0]['clientname']) ? $result[0]['clientname'] : ''), '510', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]['dateid']) ? $result[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BARCODE', '100', null, false, '1px solid', 'B', 'C', 'Century Gothic', '12', 'B', '30px', ' 8px');
        $str .= $this->reporter->col('UNIT', '85', null, false, '1px solid', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DESCRIPTION', '240', null, false, '1px solid', 'B', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ON HAND', '85', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('COUNT', '85', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DIFFERENCE', '85', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REMARKS', '120', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');

        $totalext = 0;

        foreach ($result as $key => $data) {
            $diff = $data['qty'] - $data['onhand'];
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data['barcode'], '100', null, false, '1px solid', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data['uom'], '85', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data['itemname'], '240', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data['onhand'], 2), '85', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data['qty'], 2), '85', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($diff, $decimalprice), '85', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data['rem'], '120', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $totalcount += $data['qty'];
            $totalonhand += $data['onhand'];
            $totaldiff += $diff;
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Inventory Reconciliation', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
                $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
                $str .= $this->reporter->col((isset($result[0]['docno']) ? $result[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('WAREHOUSE : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
                $str .= $this->reporter->col((isset($result[0]['clientname']) ? $result[0]['clientname'] : ''), '510', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
                $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
                $str .= $this->reporter->col((isset($result[0]['dateid']) ? $result[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
                $str .= $this->reporter->pagenumber('Page');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->printline();
                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('BARCODE', '100', null, false, '1px solid', 'B', 'C', 'Century Gothic', '12', 'B', '30px', ' 8px');
                $str .= $this->reporter->col('UNIT', '85', null, false, '1px solid', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('DESCRIPTION', '240', null, false, '1px solid', 'B', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('ON HAND', '85', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('COUNT', '85', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('DIFFERENCE', '85', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('REMARKS', '120', null, false, '1px solid', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'C', 'Century Gothic', '12', 'B', '', ' ');
        $str .= $this->reporter->col('', '85', null, false, '1px dotted', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL:', '240', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col(number_format($totalonhand, $decimalqty), '85', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col(number_format($totalcount, $decimalqty), '85', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col(number_format($totaldiff, $decimalqty), '85', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

        if (!empty($result)) {
            $str .= $this->reporter->col($result[0]['rem'], '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
        }

        $str .= $this->reporter->col('', '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prepared, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($approved, '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }



    public function AT_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

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
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

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
        PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, "", '', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(125, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(75, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(75, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(425, 0, "DESCRIPTION", '', 'L', false);
        // PDF::MultiCell(110, 0, "COST", '', 'R', false, 0);
        // PDF::MultiCell(115, 0, "TOTAL", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function AT_PDF($params, $data)
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

        $this->AT_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $qty = number_format($data[$i]['qty'], $decimalqty);
                $uom = $data[$i]['uom'];
                $cost = number_format($data[$i]['cost'], $decimalcurr);
                $ext = number_format($data[$i]['ext'], $decimalcurr);

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '75', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(125, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(425, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                $totalext += $data[$i]['ext'];

                if (PDF::getY() > 900) {
                    $this->AT_header_PDF($params, $data);
                }
            }
        }
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');


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
}
