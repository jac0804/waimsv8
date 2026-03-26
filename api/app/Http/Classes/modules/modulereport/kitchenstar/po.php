<?php

namespace App\Http\Classes\modules\modulereport\kitchenstar;

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

class po
{

    private $modulename = "Purchase Order";
    private $reportheader;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1300'];

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

        $fields = ['radioprint', 'prepared', 'approved', 'received'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function reportparamsdata($config)
    {
        $companyid = $config['params']['companyid'];
        $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";
        return $this->coreFunctions->opentable($paramstr);
    }
    // qwe @123qwE123
    public function report_default_query($trno)
    {
        $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.partno, item.barcode,item.color,item.dqty ,item.tqty as cbm,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.partno, item.barcode,item.color,item.dqty ,item.tqty as cbm,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "excel") {
            return $this->default_po_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_PO_PDF($params, $data);
        }
    }

    public function default_header($params, $data)
    {
        $companyid = $params['params']['companyid'];

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = "";
        $font =  "Arial";
        $fontsize = "11";
        $border = ".5px solid ";
        $layoutsize = $this->reportParams['layoutSize'];


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br>';
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('PO # :   '.(isset($data[0]['docno']) ? $data[0]['docno'] : ''), '220', null, false, $border, '', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER : ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '220', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('DATE :', '80', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('' . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '110', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->title_header($params, $layoutsize);
        return $str;
    }

    public function title_header($params, $layoutsize)
    {
        $companyid = $params['params']['companyid'];
        $border = ".5px solid ";
        $font =  "Arial";
        $str = "";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Description', '220', null, false, $border, 'BL', 'L', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Size', '110', null, false, $border, 'BL', 'L', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Code', '120', null, false, $border, 'BL', 'L', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Color', '100', null, false, $border, 'BL', 'L', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('QTY', '80', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('QTY/CTN', '80', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('TOTAL', '80', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Unit', '80', null, false, $border, 'BL', 'C', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Price/pc', '80', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Unit Price', '80', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Amount', '80', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('CBM', '80', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->col('Total CBM', '110', null, false, $border, 'BLR', 'R', $font, '12', 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function default_po_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $str = '';
        $count = 35;
        $page = 35;
        $font =  "Arial";
        $fontsize = "11";
        $border = ".5px solid ";
        $layoutsize = $this->reportParams['layoutSize'];
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($params, $data);

        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            // $str .= $this->reporter->addline();

            $str .= $this->reporter->col($data[$i]['itemname'], '220', null, false, $border, 'TL', 'L', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data[$i]['sizeid'], '110', null, false, $border, 'TL', 'L', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data[$i]['barcode'], '120', null, false, $border, 'TL', 'L', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data[$i]['color'], '100', null, false, $border, 'TL', 'L', $font, $fontsize, '', '');
            $qty = $data[$i]['qty'] != 0 && $data[$i]['dqty']  != 0 ? $data[$i]['qty'] / $data[$i]['dqty'] : 0;
            $str .= $this->reporter->col(number_format($qty, 2), '80', null, false, $border, 'TL', 'R', $font, $fontsize, '', '');
            $str .= $this->reporter->col(number_format($data[$i]['dqty'], 2), '80', null, false, $border, 'TL', 'R', $font, $fontsize, '', '');
            $str .= $this->reporter->col(number_format($data[$i]['qty'], 2), '80', null, false, $border, 'TL', 'R', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data[$i]['uom'], '80', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
            $price_pc = $qty != 0 ? $data[$i]['netamt'] / $qty : 0;
            $str .= $this->reporter->col(number_format($price_pc, 2), '80', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['netamt'], 2), '80', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '80', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['cbm'], 2), '80', null, false, $border, 'TL', 'R', $font, $fontsize, '', '');
            $totalcbm = $data[$i]['cbm'] * $qty;
            $str .= $this->reporter->col(number_format($totalcbm, 2), '110', null, false, $border, 'TLR', 'R', $font, $fontsize, '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($params, $data);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '220', null, false, $border, 'T', 'L', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, $fontsize, '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }


    public function default_PO_header_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
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

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)


        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        $this->reportheader->getheader($params);

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

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, 'B', $fontsize);
        switch ($companyid) {
            case 40:
                PDF::MultiCell(100, 25, "PART NO.", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(100, 25, "BARCODE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "QTY", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "UNIT", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(200, 25, "DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 25, "UNIT PRICE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "(+/-) %", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(100, 25, "TOTAL", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                break;
            default:
                PDF::MultiCell(125, 25, "CODE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "QTY", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "UNIT", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(250, 25, "DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(75, 25, "UNIT PRICE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "(+/-) %", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(100, 25, "TOTAL", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                break;
        }
    }

    public function default_PO_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
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
        $this->default_PO_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(($companyid == 40) ? 720 : 700, 0, '', '');

        $countarr = 0;
        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $partno = $data[$i]['partno'];
            $barcode = $data[$i]['barcode'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $uom = $data[$i]['uom'];
            $amt = number_format($data[$i]['netamt'], 2);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], 2);

            $arr_partno = $this->reporter->fixcolumn([$partno], '50', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_partno, $arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                switch ($companyid) {
                    case 40:
                        PDF::MultiCell(100, 15, ' ' . (isset($arr_partno[$r]) ? $arr_partno[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(70, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                        break;
                    default:
                        PDF::MultiCell(125, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(75, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                        break;
                }
            }

            $totalext += $data[$i]['ext'];

            if (PDF::getY() > 900) {
                $this->default_PO_header_PDF($params, $data);
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(($companyid == 40) ? 720 : 700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(($companyid == 40) ? 720 : 700, 0, '', '');

        switch ($companyid) {
            case 40:
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(620, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
                PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');
                break;
            default:
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
                PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');
        }

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

        //PDF::AddPage();
        //$b = 62;
        //for ($i = 0; $i < 1000; $i++) {
        //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
        //  PDF::MultiCell(0, 0, "\n");
        //  if($i==$b){
        //    PDF::AddPage();
        //    $b = $b + 62;
        //  }
        //}

        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    // public function report_maxipro_query($trno){
    //   $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
    //       head.terms,head.rem, item.barcode,
    //       item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as unitprice, stock.disc, stock.ext,
    //       m.model_name as model,item.sizeid,
    //       head.revision, client.tel, client.email, client.contact as contactperson,
    //       prj.name as projectname, prj.code as projectcode,
    //       sprj.subproject as subprojectname
    //       from pohead as head 
    //       left join postock as stock on stock.trno=head.trno
    //       left join client on client.client=head.client
    //       left join item on item.itemid = stock.itemid
    //       left join model_masterfile as m on m.model_id = item.model
    //       left join projectmasterfile as prj on prj.line = head.projectid
    //       left join subproject as sprj on sprj.line = head.subproject
    //       where head.doc='po' and head.trno='$trno'
    //       union all
    //       select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
    //       head.address, head.terms,head.rem, item.barcode,
    //       item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as unitprice, stock.disc, stock.ext,
    //       m.model_name as model,item.sizeid,
    //       head.revision, client.tel, client.email, client.contact as contactperson,
    //       prj.name as projectname, prj.code as projectcode,
    //       sprj.subproject as subprojectname
    //       from hpohead as head 
    //       left join hpostock as stock on stock.trno=head.trno
    //       left join client on client.client=head.client
    //       left join item on item.itemid = stock.itemid
    //       left join model_masterfile as m on m.model_id = item.model
    //       left join projectmasterfile as prj on prj.line = head.projectid
    //       left join subproject as sprj on sprj.line = head.subproject
    //       where head.doc='po' and head.trno='$trno'";

    //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    //   return $result;
    // }//end fn

    // public function maxipro_layout_PDF($params, $data)
    // {
    //   $companyid = $params['params']['companyid'];
    //   $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    //   $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    //   $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    //   $center = $params['params']['center'];
    //   $username = $params['params']['user'];
    //   $count = $page = 35;
    //   $totalext = 0;

    //   $font = "";
    //   $fontbold = "";
    //   $border = "1px solid ";
    //   $fontsize = "10";
    //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    //   }

    //   PDF::SetTitle($this->modulename);
    //   PDF::SetAuthor('Solutionbase Corp.');
    //   PDF::SetCreator('Solutionbase Corp.');
    //   PDF::SetSubject($this->modulename . ' Module Report');
    //   PDF::setPageUnit('px');
    //   PDF::AddPage('p', [800, 1000]);
    //   PDF::SetMargins(40, 40);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::MultiCell(500, 25, '', 'TLR', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(200, 25, '', 'TLR', 'C');

    //   PDF::SetFont($fontbold, '', 14);
    //   PDF::MultiCell(500, 40, strtoupper($this->modulename), 'LRB', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetTextColor(255, 0, 0);
    //   PDF::MultiCell(200, 40, $data[0]['docno'], 'LRB', 'C', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetTextColor(0, 0, 0);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(500, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(200, 0, $data[0]['revision'], '', 'C');
    //   PDF::Image('public/images/reports/mdc.jpg', '45', '35', 100, 40);
    //   PDF::Image('public/images/reports/tuv.jpg', '430', '35', 100, 40);

    //   PDF::MultiCell(0, 0, "\n");

    //   $left = '10';
    //   $top = '';
    //   $right = '';
    //   $bottom = '';

    //   PDF::setCellPadding( $left, $top, $right, $bottom);
    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(100, 20, 'Subcontractor: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(400, 20, $data[0]['clientname'], 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(50, 20, 'Date: ', 'B', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(150, 20, date('M d, Y', strtotime($data[0]['dateid'])), 'RB', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(75, 20, 'Address: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(425, 20, $data[0]['address'], 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(200, 20, 'Page '. PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'R', 'C', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(500, 40, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(200, 40, 'The order number must be appear on the papers, invoices, packing list and correspondence.', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(60, 20, 'Tel No.: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(440, 20, $data[0]['tel'], 'R', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(200, 20, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(60, 20, 'Email: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(440, 20, $data[0]['email'], 'R', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(200, 20, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(110, 20, 'Contact Person: ', 'LB', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(390, 20, $data[0]['contactperson'], 'BR', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(200, 20, 'PR No.', 'LRTB', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(100, 20, 'Project Name: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(350, 20, $data[0]['projectname'], '', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(150, 20, 'Terms of Payment: ', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 20, $data[0]['terms'], 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(100, 20, 'Project Code: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(350, 20, $data[0]['projectcode'], '', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(150, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 20, '', 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(120, 20, 'Subproject Name: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(330, 20, $data[0]['subprojectname'], '', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(150, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 20, '', 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(120, 20, 'Subproject Code: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(330, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(150, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 20, '', 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   PDF::MultiCell(100, 30, 'ITEM NO.', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(200, 30, 'DESCRIPTION', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 30, 'QTY', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 30, 'UOM', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 30, 'UNIT PRICE', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 30, 'AMOUNT', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(($companyid == 40) ? 720 : 700, 0, '', '');
    //   $counter = 0;
    //   for ($i = 0; $i < count($data); $i++) {
    //     $counter++;
    //     // $ext = number_format($data[$i]['ext'], $decimalcurr);
    //     // if ($ext < 1) $ext = '-';
    //     // $netamt = number_format($data[$i]['amt'], $decimalcurr);
    //     // if ($netamt < 1) $netamt = '-';
    //     $maxh = PDF::GetStringHeight(200, $data[$i]['itemname']);

    //     if($maxh <= 32.5) {
    //       $maxh = 0;
    //     }

    //     PDF::SetFont($font, '', $fontsize);
    //     // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    //     PDF::MultiCell(100, $maxh, $i+1, 'L', 'C', 0, 0, '', '', true, 0, true, false);
    //     PDF::MultiCell(200, $maxh, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
    //     PDF::MultiCell(100, $maxh, number_format($data[$i]['qty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
    //     PDF::MultiCell(100, $maxh, $data[$i]['uom'], '', 'C', 0, 0, '', '', true, 0, true, false);
    //     PDF::MultiCell(100, $maxh, number_format($data[$i]['unitprice'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
    //     PDF::MultiCell(100, $maxh, 'Php '.number_format($data[$i]['ext'], $decimalprice), 'R', 'R', 0, 0, '', '', true, 0, true, false);
    //     PDF::MultiCell(100, $maxh, '', '', 'L', 0, 1, '', '', true, 0, false, false);
    //     $totalext += $data[$i]['ext'];

    //     if (intVal($i) + 1 == $page) {
    //       $this->maxipro_layout_PDF_FOOTER($params, $data);

    //       PDF::AddPage('p', [800, 1000]);
    //       PDF::SetMargins(40, 40);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::MultiCell(500, 25, '', 'TLR', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(200, 25, '', 'TLR', 'C');

    //       PDF::SetFont($fontbold, '', 14);
    //       PDF::MultiCell(500, 40, strtoupper($this->modulename), 'LRB', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetTextColor(255, 0, 0);
    //       PDF::MultiCell(200, 40, $data[0]['docno'], 'LRB', 'C', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetTextColor(0, 0, 0);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(500, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(200, 0, $data[0]['revision'], '', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::Image('images/reports/mdc.jpg', '45', '95', 100, 40);
    //       PDF::Image('images/reports/tuv.jpg', '430', '95', 100, 40);

    //       PDF::MultiCell(0, 0, "\n");

    //       $left = '10';
    //       $top = '';
    //       $right = '';
    //       $bottom = '';

    //       PDF::setCellPadding( $left, $top, $right, $bottom);
    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(100, 20, 'Subcontractor: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(400, 20, $data[0]['clientname'], 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(50, 20, 'Date: ', 'B', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(150, 20, date('M d, Y', strtotime($data[0]['dateid'])), 'RB', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(75, 20, 'Address: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(425, 20, $data[0]['address'], 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(200, 20, 'Page '. PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'R', 'C', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(500, 40, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(200, 40, 'The order number must be appear on the papers, invoices, packing list and correspondence.', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(60, 20, 'Tel No.: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(440, 20, $data[0]['tel'], 'R', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(200, 20, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(60, 20, 'Email: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(440, 20, $data[0]['email'], 'R', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(200, 20, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(110, 20, 'Contact Person: ', 'LB', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(390, 20, $data[0]['contactperson'], 'BR', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(200, 20, 'PR No.', 'LRTB', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(100, 20, 'Project Name: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(350, 20, $data[0]['projectname'], '', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(150, 20, 'Terms of Payment: ', '', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 20, $data[0]['terms'], 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(100, 20, 'Project Code: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(350, 20, $data[0]['projectcode'], '', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(150, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 20, '', 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(120, 20, 'Subproject Name: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(330, 20, $data[0]['subprojectname'], '', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(150, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 20, '', 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(120, 20, 'Subproject Code: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(330, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(150, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 20, '', 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //       PDF::MultiCell(0, 0, "\n");

    //       PDF::MultiCell(100, 30, 'ITEM NO.', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(200, 30, 'DESCRIPTION', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 30, 'QTY', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 30, 'UOM', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 30, 'UNIT PRICE', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(100, 30, 'AMOUNT', 'LRTB', 'C', 0, 0, '', '', true, 0, true, false);
    //       PDF::MultiCell(($companyid == 40) ? 720 : 700, 0, '', '');
    //       $page += $count;
    //     }
    //   }

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(($companyid == 40) ? 720 : 700, 0, '***NOTHING TO FOLLOWS***', 'LR', 'C');

    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(600, 20, 'TOTAL: ', 'RTLB', 'R', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(100, 20, 'Php '. number_format($totalext, $decimalprice), 'RTLB', 'R', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");

    //   $maxh = 20;
    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(($companyid == 40) ? 720 : 700, 0, 'Remarks: ', 'LR', 'L');
    //   PDF::SetFont($font, '', $fontsize);
    //   $maxh = PDF::GetStringHeight(600, $data[0]['rem']);
    //   PDF::MultiCell(100, $maxh, '', 'LB', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(600, $maxh, $data[0]['rem'], 'RB', 'L');

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(300, 20, 'Approved By: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(400, 20, 'Signify your acceptance and agreement with the order by signing below: ', 'R', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(300, 40, '', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(85, 40, 'CONFORME:', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(315, 40, '___________________________________', 'R', 'L');
    //   PDF::MultiCell(150, 40, 'Lawrence Sy President', 'TL', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(150, 40, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(85, 40, 'POSITION:', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(315, 40, '___________________________________', 'R', 'L');
    //   PDF::MultiCell(300, 40, '', 'LB', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(85, 40, 'DATE:', '', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(315, 40, '___________________________________', 'R', 'L');

    //   // PDF::MultiCell(0, 0, "\n");
    //   $this->maxipro_layout_PDF_FOOTER($params, $data);

    //   return PDF::Output($this->modulename . '.pdf', 'S');
    // }

    // public function maxipro_layout_PDF_FOOTER($params, $data) {
    //   $center = $params['params']['center'];
    //   $username = $params['params']['user'];
    //   $font = "";
    //   $fontbold = "";
    //   $border = "1px solid ";
    //   $fontsize = "10";
    //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    //   }

    //   $qry = "select name,address,tel from center where code = '".$center."'";
    //   $headerdata = $this->coreFunctions->opentable($qry);

    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(200, 40, 'Office Address: ', 'TRL', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(300, 40, '', 'TRL', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::MultiCell(200, 40, 'Tel No.: '.$headerdata[0]->tel, 'TRL', 'L', 0, 0, '', '', true, 0, true, false);

    //   PDF::MultiCell(0, 0, "\n");
    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(200, 40, $headerdata[0]->address, 'RBL', 'L', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($fontbold, '', 14);
    //   PDF::MultiCell(300, 40, 'Maxipro Development Corporation', 'RBL', 'C', 0, 0, '', '', true, 0, true, false);
    //   PDF::SetFont($fontbold, '', $fontsize);
    //   PDF::MultiCell(200, 40, 'Fax No.: ', 'RBL', 'L', 0, 0, '', '', true, 0, true, false);
    // }
}
