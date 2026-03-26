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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class cc
{
    private $modulename = "Construction Order";
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

        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '0' as reporttype,
            '' as prepared,
            '' as approved,
            '' as received
            "
        );
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];

        $query = "select head.docno,head.client,head.clientname,head.address,head.dateid,head.due,head.wh,head.yourref,
                    head.ourref,head.projectid,head.phaseid,head.modelid,
                    head.blklotid,head.lot,head.rem as headrem,head.citrno,
                    stock.line,stock.barcode,stock.itemname,stock.uom,stock.rem as stockrem,stock.rrqty,stock.qty,
                    stock.qa,stock.void,stock.refx,stock.linex,stock.ref,stock.itemid,stock.whid,stock.amenity,
                    stock.subamenity,proj.code as projcode, proj.name as projname,phase.code as phasename,
                    model.model as housemodel,model.price,bl.blk,bl.sqm,ci.docno as cidocno,
                    am.code as amenitycode, am.description as amenityname,
                    subam.code as subamenitycode, subam.description as subamenityname
                from cohead as head
                left join costock as stock on stock.trno=head.trno
                left join projectmasterfile as proj on proj.line=head.projectid
                left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                left join housemodel as model on model.line=head.modelid and model.projectid=head.modelid
                left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                left join hcihead as ci on ci.trno=head.citrno
                left join amenities as am on am.line= stock.amenity
                left join subamenities as subam on subam.line=stock.subamenity and subam.amenityid=stock.amenity 
                where head.doc='CC' and head.trno = '$trno'
                union all
                select head.docno,head.client,head.clientname,head.address,head.dateid,head.due,head.wh,head.yourref,
                    head.ourref,head.projectid,head.phaseid,head.modelid,
                    head.blklotid,head.lot,head.rem as headrem,head.citrno,
                    stock.line,stock.barcode,stock.itemname,stock.uom,stock.rem as stockrem,stock.rrqty,stock.qty,
                    stock.qa,stock.void,stock.refx,stock.linex,stock.ref,stock.itemid,stock.whid,stock.amenity,
                    stock.subamenity,proj.code as projcode, proj.name as projname,phase.code as phasename,
                    model.model as housemodel,model.price,bl.blk,bl.sqm,ci.docno as cidocno,
                    am.code as amenitycode, am.description as amenityname,
                    subam.code as subamenitycode, subam.description as subamenityname
                from hcohead as head
                left join hcostock as stock on stock.trno=head.trno
                left join projectmasterfile as proj on proj.line=head.projectid
                left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                left join housemodel as model on model.line=head.modelid and model.projectid=head.modelid
                left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                left join hcihead as ci on ci.trno=head.citrno
                left join amenities as am on am.line= stock.amenity
                left join subamenities as subam on subam.line=stock.subamenity and subam.amenityid=stock.amenity 
                where head.doc='CC' and head.trno = '$trno' order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {
        return $this->default_cc_PDF($params, $data);
    }

    public function default_cc_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

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

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(450, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Document # : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(150, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Customer : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Reference # : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['cidocno']) ? $data[0]['cidocno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Project : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['projname']) ? $data[0]['projname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Warehouse : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['wh']) ? $data[0]['wh'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Phase : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['phasename']) ? $data[0]['phasename'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Block : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['blk']) ? $data[0]['blk'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "House Model : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['housemodel']) ? $data[0]['housemodel'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Lot : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['lot']) ? $data[0]['lot'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(50, 0, "QTY", '', 'R', false, 0);
        PDF::MultiCell(50, 0, "UOM", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(220, 0, "DESCRIPTION", '', 'C', false, 0);
        PDF::MultiCell(140, 0, "AMENITY", '', 'C', false, 0);
        PDF::MultiCell(140, 0, "SUB-AMENITY", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_cc_PDF($params, $data)
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
        $this->default_cc_header_PDF($params, $data);

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

                $amenityname = $data[$i]['amenityname'];
                $subamenityname = $data[$i]['subamenityname'];

                $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
                $arr_barcode = $this->reporter->fixcolumn([$barcode], '14', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '37', 0);

                $arr_amenityname = $this->reporter->fixcolumn([$amenityname], '25', 0);
                $arr_subamenityname = $this->reporter->fixcolumn([$subamenityname], '25', 0);


                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amenityname, $arr_subamenityname]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                    PDF::MultiCell(220, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(140, 15, ' ' . (isset($arr_amenityname[$r]) ? $arr_amenityname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(140, 15, ' ' . (isset($arr_subamenityname[$r]) ? $arr_subamenityname[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }


                if (PDF::getY() > 900) {
                    $this->default_cc_header_PDF($params, $data);
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
        PDF::MultiCell(560, 0, $data[0]['headrem'], '', 'L');

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
