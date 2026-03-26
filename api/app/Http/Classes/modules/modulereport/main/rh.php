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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class rh
{
    private $modulename = "Received Cash";
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
                '' as prepared,
                '' as approved,
                '' as received
                "
        );
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];

        $query = "select detail.line, head.trno, date(head.dateid) as dateid, head.docno,ag.clientname as agent,
                        head.yourref,head.ourref,head.rem,detail.bank,detail.amount, detail.branch, client.clientname,detail.clientid
                from rhhead as head
                left join rhdetail as detail on detail.trno=head.trno
                left join client as ag on ag.client=head.agent
                left join client on client.clientid=detail.clientid
                where head.doc='rh' and head.trno='$trno'
                union all
                select detail.line, head.trno, date(head.dateid) as dateid, head.docno,ag.clientname as agent,
                       head.yourref,head.ourref,head.rem,detail.bank,detail.amount,detail.branch, client.clientname,detail.clientid
                from hrhhead as head
                left join hrhdetail as detail on detail.trno=head.trno
                left join client as ag on ag.client=head.agent
                left join client on client.clientid=detail.clientid
                where head.doc='rh' and head.trno='$trno' order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        return $this->default_RH_PDF($params, $data);
    }

    public function default_RH_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $companyid = $params['params']['user'];

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
        PDF::SetMargins(40, 40);
        PDF::AddPage('p', [800, 1000]);

        PDF::SetCellPaddings(4, 4, 4, 4);
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
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '130');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 40, "", '', 'L');
        PDF::MultiCell(80, 0, "Agent: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['agent']) ? $data[0]['agent'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'R', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Yourref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Ourref: ", '', 'R', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Notes: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(620, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 1, '',  '');


        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(200, 0, "CUSTOMER", 'TB', 'L', false, 0);
        PDF::MultiCell(200, 0, "BANK", 'TB', 'L', false, 0);
        PDF::MultiCell(200, 0, "BRANCH", 'TB', 'L', false, 0);
        PDF::MultiCell(100, 0, "AMOUNT", 'TB', 'R', false);
        PDF::SetFont($font, '', 5);
    }

    public function default_RH_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_RH_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);

        $countarr = 0;
        $totaldb = 0;
        PDF::SetCellPaddings(1, 1, 1, 1);
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;
                $bank = $data[$i]['bank'];
                $branch = $data[$i]['branch'];
                $client = $data[$i]['clientname'];
                $amount = number_format($data[$i]['amount'], $decimalcurr);

                $arrbank = $this->reporter->fixcolumn([$bank], '35', 0);
                $arrbranch = $this->reporter->fixcolumn([$branch], '35', 0);
                $arrclient = $this->reporter->fixcolumn([$client], '35', 0);
                $arramount = $this->reporter->fixcolumn([$amount], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arrbank, $arrbranch, $arrclient, $arramount]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(200, 0, (isset($arrclient[$r]) ? $arrclient[$r] : ''), '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(200, 0, (isset($arrbank[$r]) ? $arrbank[$r] : ''), '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(200, 0, (isset($arrbranch[$r]) ? $arrbranch[$r] : ''), '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(100, 0, (isset($arramount[$r]) ? $arramount[$r] : ''), '', 'R', false, 1, '', '', false, 1);
                }

                $totaldb += $data[$i]['amount'];
            }
        }

        PDF::SetCellPaddings(4, 4, 4, 4);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(260, 0, '', 'T', 'R', false, 0);
        PDF::MultiCell(290, 0, 'GRAND TOTAL: ', 'T', 'R', false, 0);
        PDF::MultiCell(150, 0, number_format($totaldb, $decimalprice), 'T', 'R', false, 0);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');

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
