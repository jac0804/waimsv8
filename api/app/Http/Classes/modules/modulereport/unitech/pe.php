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

class pe
{

    private $modulename = "Production Request";
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

        $fields = ['radioprint', 'prepared', 'approved', 'received', 'requested', 'notedby1', 'print'];
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
            '' as received,
            '' as requested,
            '' as notedby1 "
        );
    }

    public function report_default_query($trno)
    {

        $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
                        head.terms,head.rem, item.partno, item.barcode,item.itemname, stock.rrqty as qty, stock.uom, 
                        stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid,
                        head.due,hi.barcode as itemcode,head.color,head.qty as headqty,head.weight,
                        info.weight as standardweight,sinfo.weight as stockweight
                from prhead as head 
                left join prstock as stock on stock.trno=head.trno 
                left join client on client.client=head.client
                left join item on item.itemid = stock.itemid
                left join model_masterfile as m on m.model_id = item.model
                left join item as hi on hi.itemid=head.itemid
                left join iteminfo as info on info.itemid=head.itemid
                left join iteminfo as sinfo on sinfo.itemid=stock.itemid
                where head.doc='PE' and head.trno='$trno'
                union all
                select date(head.dateid) as dateid, head.docno, client.client, client.clientname, 
                        head.address, head.terms,head.rem, item.partno, item.barcode, item.itemname, stock.rrqty as qty, stock.uom, 
                        stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid,
                        head.due,hi.barcode as itemcode,head.color,head.qty as headqty,head.weight,
                        info.weight as standardweight,sinfo.weight as stockweight
                from hprhead as head 
                left join hprstock as stock on stock.trno=head.trno 
                left join client on client.client=head.client
                left join item on item.itemid = stock.itemid
                left join model_masterfile as m on m.model_id = item.model
                left join item as hi on hi.itemid=head.itemid
                left join iteminfo as info on info.itemid=head.itemid
                left join iteminfo as sinfo on sinfo.itemid=stock.itemid
                where head.doc='PE' and head.trno='$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        return $this->default_PE_PDF($params, $data);
    }


    public function default_PE_header_PDF($params, $data)
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
        PDF::MultiCell(700, 0, 'Request For Production', '', 'C', false);

        // PDF::MultiCell(0, 20, "\n");

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Date Requested: ', '', '', false, 0, 20, 150);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $data[0]['dateid'], 'B', 'L', false, 0, 150, 150);

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Date Needed: ', '', '', false, 0, 20, 180);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $data[0]['due'], 'B', 'L', false, 0, 150, 180);

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Item Code: ', '', '', false, 0, 20, 210);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $data[0]['itemcode'], 'B', 'L', false, 0, 150, 210);


        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Color Needed: ', '', '', false, 0, 20, 240);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $data[0]['color'], 'B', 'L', false, 0, 150, 240);

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Quantity: ', '', '', false, 0, 20, 270);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $data[0]['headqty'], 'B', 'L', false, 0, 150, 270);


        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Standard Weight: ', '', '', false, 0, 20, 300);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $data[0]['standardweight'], 'B', 'L', false, 0, 150, 300);

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Actual Weight: ', '', '', false, 0, 20, 330);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $data[0]['weight'], 'B', 'L', false, 0, 150, 330);

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Requested By: ', '', '', false, 0, 20, 360);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $params['params']['dataparams']['requested'], 'B', 'L', false, 0, 150, 360);


        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(150, 0, 'Noted By: ', '', '', false, 0, 20, 390);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, $params['params']['dataparams']['notedby1'], 'B', 'L', false, 0, 150, 390);

        // PDF::MultiCell(0, 0, "\n\n\n\n\n");
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(360, 25, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(340, 25, "RAW MATERIALS", 'TLR', 'C', false, 0, '400',  '155', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(360, 25, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 25, " ITEMNAME", 'TBL', 'L', false, 0, '400',  '180', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 25, "WEIGHT", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 25, "QTY ", 'TBR', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
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
        $this->default_PE_header_PDF($params, $data);

        $tqty = 0;
        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $itemname = $data[$i]['itemname'];
            $stockweight = $data[$i]['stockweight'];
            $qty = number_format($data[$i]['qty'], 2);

            $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_stockweight = $this->reporter->fixcolumn([$stockweight], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_stockweight]);

            if ($data[$i]['itemname'] == '') {
            } else {
                PDF::SetFont($font, '', 5);
                PDF::MultiCell(360, 0, ' ', '', 'L', false, 0);
                PDF::MultiCell(340, 0, ' ', 'LR', 'L', false);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(360, 15, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(210, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_stockweight[$r]) ? $arr_stockweight[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'R', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
            }

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(360, 0, "", '', 'C', false, 0);
            PDF::MultiCell(340, 0, "", 'BLR', 'C', false);

            if (PDF::getY() > 900) {
                $this->default_PE_header_PDF($params, $data);
            }

            $tqty = $tqty + $data[$i]['qty'];

        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(360, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(340, 0, ' ', 'LR', 'L', false);                
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(360, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(210, 15, ' TOTAL QTY: ' , 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' , '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 15, number_format($tqty,2), 'R', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(360, 0, "", '', 'C', false, 0);
        PDF::MultiCell(340, 0, "", 'BLR', 'C', false);
    

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
