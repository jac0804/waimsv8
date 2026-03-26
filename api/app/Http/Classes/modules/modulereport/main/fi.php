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
use Symfony\Component\VarDumper\VarDumper;

class fi
{

    private $modulename = "Issue Items";
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
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'approved',  'print'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
      'PDFM' as print,
      '' as approved
      "
        );
    }

    public function report_default_query($trno)
    {

        $query = "select item.itemname as itemdesc, item.barcode, info.serialno,cl.clientname as employee,iss.docno,date(iss.dateid) as dateid, 1 as qty,stock.returnrem
        FROM issueitemstock as stock
        left join item on item.itemid=stock.itemid
        left join issueitem as iss on iss.trno=stock.trno
        left join iteminfo as info on info.itemid=item.itemid
        left join client as cl on cl.clientid=iss.clientid where  stock.trno='$trno'
        ";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_PR_PDF($params, $data);
        }
    }


    public function default_PR_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

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
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        // PDF::SetFont($fontbold, '', 18);
        // PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, "ACCOUNTABILITY FORM ", '', 'C', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        //  PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);


        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 0, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Name: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(300, 0, (isset($data[0]['employee']) ? $data[0]['employee'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(100, 0, " ", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'TLR');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(100, 0, "QTY", 'L', 'C', false, 0);
        PDF::MultiCell(100, 0, "ITEMCODE", '', 'C', false, 0);
        PDF::MultiCell(8, 0, "", '', 'C', false, 0);
        PDF::MultiCell(292, 0, "DESCRIPTION", '', 'C', false, 0);
        PDF::MultiCell(200, 0, "SERIALNO", 'R', 'C', false);
    }

    public function default_PR_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 30;
        $totalext = 0;


        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_PR_header_PDF($params, $data);

        //  PDF::SetFont($font, '', 5);
        //  PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        for ($i = 0; $i < count($data); $i++) {

            $itemname =   $this->reporter->fixcolumn([$data[$i]['itemdesc']], '300', 0);
            $maxrow = 1;

            $countarr = count($itemname);
            $maxrow = $countarr;

            if ($data[$i]['itemdesc'] == '') {
            } else {
                for ($r = 0; $r < $maxrow; $r++) {
                    if ($r == 0) {
                        $barcode = $data[$i]['barcode'];
                        $qty = $data[$i]['qty'];
                        $serialno = $data[$i]['serialno'];
                    } else {
                        $barcode = '';
                        $qty = '';
                        $serialno = '';
                    }
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(100, 0, $qty, 'LRT', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, $barcode, 'RT', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(8, 0, '', 'T', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(292, 0,  isset($itemname[$r]) ? $itemname[$r] : '', 'RT', 'L', false, 0, '', '', false, 1, false, true);
                    PDF::MultiCell(200, 0, $serialno, 'RT', 'C', false, 1, '', '', false, 0);
                }
            }
            if (intVal($i) + 1 == $page) {
                PDF::SetFont($font, '', 5);
                PDF::MultiCell(700, 0, '', 'T');
                $this->default_PR_header_PDF($params, $data);
                $page += $count;
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, 'REASON: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(650, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(0, 0, "\n\n");


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'TLR');
        PDF::SetFont($fontbold, '', $fontsize);



        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 0, '  Note:LIABILITY CLAUSE: The Requestor hereby acknowledges that any loses (which include,but not limited to any missing items or  
  clients inability or refusal to pay, etc.) or damages (including but limited to defective items directly delivered from supplier to
  client  and assumed in good condition) that may occur from this special arrangement are solely the responsibility of the requestor.', 'LR', 'J', false, 1);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'BLR');
        PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n");


        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(160, 0, 'Department Head Signature: ', '', 'L', false, 0, '', '880');
        PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false);
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(410, 0, '', '', 'L', false, 0);
        PDF::MultiCell(290, 0, '', 'B', 'C', false, 0, '450', '900');
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(410, 0, '', '', 'L', false, 0);
        PDF::MultiCell(290, 0, 'Requester', '', 'C', false, 0, '450', '915');
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(410, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(290, 0, 'Signature Over Printed Name', '', 'C', false, 0, '450', '930');
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(160, 0, 'Approved by:', '', 'L', false, 0);
        PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'B', 'L', false);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
