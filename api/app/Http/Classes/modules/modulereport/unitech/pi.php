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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\VarDumper\VarDumper;

class pi
{

    private $modulename = "Production Instruction";
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
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'notedby1.label', 'Noted By:');

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
            '' as prepared,
            '' as approved,
            '' as received "
        );
    }

    public function report_default_query($trno)
    {

        $query = "select date(head.dateid) as dateid, head.docno,head.rem as headrem,item.partno, item.barcode,
                        head.yourref,head.ourref,item.itemname, stock.rrqty as qty, stock.uom, 
                        stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid,
                        head.due,hi.barcode as itemcode,head.qty as headqty,stock.rem,head.uom as headuom,
                        hi.itemname as headitemname,head.weight,info.weight as standardweight
                from pihead as head 
                left join pistock as stock on stock.trno=head.trno 
                left join client on client.client=head.client
                left join client as warehouse on warehouse.client = head.wh
                left join item on item.barcode = stock.barcode
                left join iteminfo as info on info.itemid=head.itemid
                left join model_masterfile as m on m.model_id = item.model
                left join item as hi on hi.itemid=head.itemid
                where head.doc='PI' and head.trno='$trno'
                union all
                select date(head.dateid) as dateid, head.docno,head.rem as headrem,item.partno, item.barcode,
                       head.yourref,head.ourref,item.itemname, stock.rrqty as qty, stock.uom, 
                       stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid,
                       head.due,hi.barcode as itemcode,head.qty as headqty,stock.rem,head.uom as headuom,
                       hi.itemname as headitemname,head.weight,info.weight as standardweight
                from hpihead as head 
                left join hpistock as stock on stock.trno=head.trno 
                left join client on client.client=head.client
                left join item on item.barcode = stock.barcode
                left join iteminfo as info on info.itemid=head.itemid
                left join model_masterfile as m on m.model_id = item.model
                left join item as hi on hi.itemid=head.itemid
                where head.doc='PI' and head.trno='$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        return $this->default_PE_PDF($params, $data);
    }


    public function default_PI_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;
        $companyid = $params['params']['companyid'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
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
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        $this->reportheader->getheader($params);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(700, 0, $this->modulename, '', 'C', false);

        PDF::MultiCell(0, 20, "\n");


        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(420, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Itemname : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(320, 20, (isset($data[0]['headitemname']) ? $data[0]['headitemname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Barcode : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(140, 20, (isset($data[0]['itemcode']) ? $data[0]['itemcode'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Qty : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(140, 20, (isset($data[0]['headqty']) ? $data[0]['headqty'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "UOM : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 20, (isset($data[0]['headuom']) ? $data[0]['headuom'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Standard Weight : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(250, 20, (isset($data[0]['standardweight']) ? $data[0]['standardweight'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Actual Weight : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(250, 20, (isset($data[0]['weight']) ? $data[0]['weight'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Yourref : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(250, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Ourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(250, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Notes : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(600, 20, (isset($data[0]['headrem']) ? $data[0]['headrem'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(320, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(150, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, 'B', $fontsize);

        PDF::MultiCell(110, 25, "BARCODE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 25, "QTY", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 25, "UNIT", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(250, 25, "DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(180, 25, "NOTES", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }

    public function default_PE_PDF($params, $data)
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
        $this->default_PI_header_PDF($params, $data);

        $countarr = 0;

        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $partno = $data[$i]['partno'];
            $barcode = $data[$i]['barcode'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $uom = $data[$i]['uom'];
            $rem = $data[$i]['rem'];

            $arr_partno = $this->reporter->fixcolumn([$partno], '50', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '30', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_partno, $arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_rem]);

            if ($data[$i]['itemname'] == '') {
            } else {
                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(110, 18, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 18, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 18, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(250, 18, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(180, 18, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
            }

            if (PDF::getY() > 900) {
                $this->default_PI_header_PDF($params, $data);
            }
        }

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(600, 18, ' ', 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 18, '', 'T', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
