<?php

namespace App\Http\Classes\modules\modulereport\nte;

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

class pc
{

    private $modulename = "Physical Count";
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
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Physical Count', 'value' => '0', 'color' => 'orange'],
                ['label' => 'Inventory Reconciliation', 'value' => '1', 'color' => 'orange']
            ]
        );
        data_set($col1, 'reporttype.label', 'Employee');
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared,
       '0' as reporttype
    ");
    }

    public function report_default_query($filters)
    {
        return $this->default_query($filters);
    }

    public function default_query($filters)
    {
        $trno = md5($filters['params']['dataid']);
        $query = "
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
        head.yourref, head.ourref, stock.rem as srem, stock.consignee,stock.expiry,stock.loc,stock.oqty,stock.asofqty,(stock.rrqty - stock.oqty) as dif
        from pchead as head 
        left join pcstock as stock on stock.trno=head.trno 
        left join client on client.client=head.wh
        left join item on item.itemid = stock.itemid
        where head.doc='pc' and md5(head.trno)='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
        head.yourref, head.ourref, stock.rem as srem, stock.consignee,stock.expiry,stock.loc,stock.oqty,stock.asofqty, (stock.rrqty - stock.oqty) as dif
        from hpchead as head 
        left join hpcstock as stock on stock.trno=head.trno 
        left join client on client.client=head.wh
        left join item on item.itemid = stock.itemid
        where head.doc='pc' and md5(head.trno)='$trno'
        order by itemname ";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        return $result;
    }

    public function reportplotting($params, $data)
    {
        $reporttype = $params['params']['dataparams']['reporttype'];

        if ($params['params']['dataparams']['print'] == "default" || $params['params']['dataparams']['print'] == "excel") {
            // return $this->default_aj_layout($params, $data);
            switch ($reporttype) {
                case '1':
                    return $this->default_ir_layout($params, $data);
                    break;
                default:
                    return $this->default_pc_layout($params, $data);
                    break;
            }
        } else {

            switch ($reporttype) {
                case '1':
                    return $this->Inventory_Reconciliation($params, $data);
                    break;
                default:
                    return $this->default_PC_PDF($params, $data);
                    break;
            }
        }
    }


    public function default_PC_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $reporttype = $params['params']['dataparams']['reporttype'];
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
        if ($reporttype != 0) {
            $this->modulename = 'Inventory Reconciliation';
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
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(540, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(120, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::MultiCell(0, 0, "\n");

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
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(60, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(60, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(80, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(260, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "LOCATION", '', 'L', false, 0);
        PDF::MultiCell(60, 0, "EXPIRY", '', 'L', false, 0);
        PDF::MultiCell(60, 0, "COST", '', 'R', false, 0);
        PDF::MultiCell(70, 0, "TOTAL", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function default_PC_PDF($params, $data)
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
        $this->default_PC_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;

            $barcode = $data[$i]['barcode'];
            $loc = $data[$i]['loc'];
            $expiry = $data[$i]['expiry'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $uom = $data[$i]['uom'];
            $amt = number_format($data[$i]['cost'], 2);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], 2);


            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_loc = $this->reporter->fixcolumn([$loc], '15', 0);
            $arr_expiry = $this->reporter->fixcolumn([$expiry], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '48', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext, $arr_loc, $arr_expiry]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(80, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(260, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, ' ' . (isset($arr_expiry[$r]) ? $arr_expiry[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_loc[$r]) ? $arr_loc[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            }

            $totalext += $data[$i]['ext'];

            if (PDF::getY() > 900) {
                $this->default_PC_header_PDF($params, $data);
            }
        }


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(620, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

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
    public function Invemtory_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $reporttype = $params['params']['dataparams']['reporttype'];
        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $this->modulename = 'Inventory Reconciliation';
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

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(540, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(120, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "", '', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(70, 0, "BARCODE", '', 'L', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(180, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "LOCATION", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "EXPIRY", '', 'L', false, 0);
        PDF::MultiCell(60, 0, "ON HAND", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "COUNT", '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", '', 'R', false, 0);
        PDF::MultiCell(70, 0, "DIFFERENCE", '', 'R', false, 0);
        PDF::MultiCell(60, 0, "REMARK", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }
    public function Inventory_Reconciliation($params, $data)
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
        $this->Invemtory_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        $countarr = 0;

        for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;

            $barcode = $data[$i]['barcode'];
            $expiry = $data[$i]['expiry'];
            $loc = $data[$i]['loc'];
            $itemname = $data[$i]['itemname'];
            $oqty = number_format($data[$i]['oqty'], 2);
            $uom = $data[$i]['uom'];
            $amt = number_format($data[$i]['cost'], 2);
            $asofqty = number_format($data[$i]['qty'], 2);
            $rem = $data[$i]['srem'];
            $dif = number_format($data[$i]['dif'], 2);

            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '28', 0);
            $arr_oqty = $this->reporter->fixcolumn([$oqty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_dif = $this->reporter->fixcolumn([$dif], '13', 0);
            $arr_asofqty = $this->reporter->fixcolumn([$asofqty], '15', 0);
            $arr_expiry = $this->reporter->fixcolumn([$expiry], '13', 0);
            $arr_loc = $this->reporter->fixcolumn([$loc], '13', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_expiry, $arr_loc, $arr_oqty, $arr_uom, $arr_rem, $arr_asofqty, $arr_dif]);

            for ($r = 0; $r < $maxrow; $r++) {


                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(70, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(180, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(100, 15, ' ' . (isset($arr_loc[$r]) ? $arr_loc[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, ' ' . (isset($arr_expiry[$r]) ? $arr_expiry[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_oqty[$r]) ? $arr_oqty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_asofqty[$r]) ? $arr_asofqty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, ' ' . (isset($arr_dif[$r]) ? $arr_dif[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(60, 15, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            }

            $totalext += $data[$i]['ext'];

            if (PDF::getY() > 900) {
                $this->Invemtory_header_PDF($params, $data);
            }
        }


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(580, 0, $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(240, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function default_pc_header($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal   = $this->companysetup->getdecimal('currency', $config['params']);

        $center   = $config['params']['center'];
        $username = $config['params']['user'];

        $prepared = $config['params']['dataparams']['prepared'];
        $received = $config['params']['dataparams']['received'];
        $approved = $config['params']['dataparams']['approved'];

        $str = '';
        $count = 35;
        $page = 35;

        $str = '';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('PHYSICAL COUNT', '70px', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->col('', '60px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '80px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '200px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '70px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '60px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '80px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('WAREHOUSE : ', '70px', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '60px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '80px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '200px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '70px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('DATE : ', '60px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '80px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('BARCODE', '70px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '60px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R I P T I O N', '200px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('LOCATION', '70px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('EXPIRY', '60px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('COST', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        return $str;
    }




    public function default_pc_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal   = $this->companysetup->getdecimal('currency', $params['params']);

        $center   = $params['params']['center'];
        $username = $params['params']['user'];

        $prepared = $params['params']['dataparams']['prepared'];
        $received = $params['params']['dataparams']['received'];
        $approved = $params['params']['dataparams']['approved'];

        $str = '';
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->default_pc_header($params, $data);

        $totalext = 0;
 
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            // $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['barcode'], '70px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['qty'], 2), '60px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '80px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '200px', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['loc'], '70px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['expiry'], '60px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['cost'], 2), '80px', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');

            $totalext = $totalext + $data[$i]['ext'];
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_ir_header($params, $data);
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '70px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '60px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '80px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '200px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '70px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '60px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '80px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '100px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

        if (!empty($result)) {
            $str .= $this->reporter->col($data[0]['rem'], '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
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



    public function default_ir_header($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal   = $this->companysetup->getdecimal('currency', $config['params']);

        $center   = $config['params']['center'];
        $username = $config['params']['user'];

        $prepared = $config['params']['dataparams']['prepared'];
        $received = $config['params']['dataparams']['received'];
        $approved = $config['params']['dataparams']['approved'];

        $str = '';
        $count = 35;
        $page = 35;

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Inventory Reconciliation', '70px', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->col('', '60px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '80px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '200px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '70px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '60px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '80px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('WAREHOUSE : ', '70px', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '60px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '80px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '200px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '70px', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('DATE : ', '60px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '80px', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col('', '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('BARCODE', '70px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R I P T I O N', '200px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('LOCATION', '70px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('EXPIRY', '60px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ON HAND', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('COUNT', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DIFFERENCE', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REMARK', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        return $str;
    }

    public function default_ir_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal   = $this->companysetup->getdecimal('currency', $params['params']);

        $center   = $params['params']['center'];
        $username = $params['params']['user'];

        $prepared = $params['params']['dataparams']['prepared'];
        $received = $params['params']['dataparams']['received'];
        $approved = $params['params']['dataparams']['approved'];

        $str = '';
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->default_ir_header($params, $data);

        $str .= '<br/><br/>';
        // $totalext = 0;

        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['barcode'], '70px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '80px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '200px', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['loc'], '70px', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['expiry'], '60px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['oqty'], 2), '80px', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['qty']), '80px', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['dif'], 2), '80px', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['srem'], '80px', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_ir_header($params, $data);
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

        if (!empty($result)) {
            $str .= $this->reporter->col($data[0]['rem'], '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
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
}
