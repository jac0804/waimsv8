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
use App\Http\Classes\reportheader;
use App\Http\Classes\common\commonsbc;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ta
{
    private $modulename = "Ticket Application";
    private $reportheader;
    private $commonsbc;
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
        $this->commonsbc = new commonsbc;
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
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

        $query = "select head.docno, head.terms, head.yourref, head.ourref, head.contra, 
                coa.acnoname, left(head.dateid,10) as dateid, head.address, client.clientname, 
                warehouse.clientname as whname, ifnull(project.name,'') as projectname, info.rem2, info.rem3
                from lahead as head
                left join cntnum as num on num.trno = head.trno
                left join client on client.client = head.client
                left join coa on coa.acno = head.contra
                left join client as warehouse on warehouse.client = head.wh
                left join projectmasterfile as project on project.line=head.projectid 
                left join cntnuminfo as info on info.trno=head.trno
                where head.trno = " . $trno . "";


        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        return $this->default_ta_PDF($params, $data);
    }

    public function default_ta_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

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

        PDF::SetFont($font, '', 9);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
        $this->reportheader->getheader($params);

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

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
        PDF::MultiCell(500, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(80, 20, "Warehouse : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(470, 20, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(50, 20, "Yourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(100, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(80, 20, "Project : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(470, 20, (isset($data[0]['projectname']) ? $data[0]['projectname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(50, 20, "Ourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(100, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n\n");
    }

    public function default_ta_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->default_ta_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 0, 'ITEM DESCRIPTION:     ', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(400, 0, $data[0]['rem2'], '', 'L');
        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 0, 'REMARKS:     ', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(400, 0, $data[0]['rem3'], '', 'L');
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
}
