<?php

namespace App\Http\Classes\modules\modulereport\cdohris;

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
use DateTime;
use Illuminate\Support\Facades\Storage;

class hc
{

    private $modulename;
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
        $fields = ['radioprint', 'radioreporttype', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'CLEARANCE CERTIFICATE', 'value' => '0', 'color' => 'orange'],
            ['label' => 'AUTHORITY AND CONSENT', 'value' => '1', 'color' => 'orange'],
            ['label' => 'WAIVER and QUITCLAIM', 'value' => '2', 'color' => 'orange']


        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable("select 
        'PDFM' as print,
        '0' as reporttype");
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select num.trno,num.docno,head.empid,upper(client.clientname) as empname,
                        d.clientname as deptname,date(head.dateid) as dateid,
                        date(head.hired) as hired,date(head.lastdate) as lastday,head.jobtitle,
                        cl.client as emphead,cl.clientname as empheadname,
                        head.cause, ifnull(em.mobileno,'') as contactno,d.clientname as deptname,
                        br.clientname as brname, witness.clientname as witnessname,divi.divcode,
                        ifnull(cl.addr,'') as address,head.amount as lastpay,head.deduction,
                        ifnull(em.empfirst,'') as name,ifnull(em.empmiddle,'') as mname,ifnull(em.emplast,'') as lname,witness2.clientname as witnessname2
                from clearance as head
                left join client as cl on cl.clientid=head.empheadid   
                left join client on client.clientid=head.empid
                left join employee as em on em.empid=head.empid
                left join client as d on d.clientid=head.deptid
                left join hrisnum as num on num.trno = head.trno
                left join client as br on br.clientid=em.branchid
                left join client as witness on witness.clientid=head.witness
                left join client as witness2 on witness2.clientid=head.witness2
                left join division as divi on divi.divid = em.divid 
                where num.trno = '$trno'
                union all
                 select num.trno,num.docno,head.empid,upper(client.clientname) as empname,
                       d.clientname as deptname,date(head.dateid) as dateid,
                       date(head.hired) as hired,date(head.lastdate) as lastday,head.jobtitle,
                       cl.client as emphead,cl.clientname as empheadname,
                       head.cause,ifnull(em.mobileno,'') as contactno,d.clientname as deptname,
                       br.clientname as brname, witness.clientname as witnessname,divi.divcode,
                       ifnull(cl.addr,'') as address,head.amount as lastpay,head.deduction,
                       ifnull(em.empfirst,'') as name,ifnull(em.empmiddle,'') as mname,ifnull(em.emplast,'') as lname,witness2.clientname as witnessname2
                from hclearance as head
                left join client as cl on cl.clientid=head.empheadid
                left join client on client.clientid=head.empid
                left join employee as em on em.empid=head.empid
                left join client as d on d.clientid=head.deptid
                left join hrisnum as num on num.trno = head.trno
                left join client as br on br.clientid=em.branchid
                left join client as witness on witness.clientid=head.witness
                left join client as witness2 on witness2.clientid=head.witness2
                left join division as divi on divi.divid = em.divid 
                where num.trno = '$trno'";
        // var_dump($query);
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($config, $data)
    {

        $data = $this->report_default_query($config);
        $reporttype = $config['params']['dataparams']['reporttype'];
        if ($config['params']['dataparams']['print'] == "default") {
            $str = $this->rpt_HC_layout($config, $data);
        } else if ($config['params']['dataparams']['print'] == "PDFM") {
            switch ($reporttype) {
                case 0: //clerance cert
                    $str = $this->rpt_HC_PDF($config, $data);
                    break;
                case 1: //authority and consent
                    $str = $this->authority_and_consent_pdf($config, $data);
                    break;
                case 2: //waiver
                    $str = $this->waiver_pdf($config, $data);
                    break;
            }
        }

        return $str;
    }



    public function rpt_HC_layout($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $font = "Century Gothic ";
        $fontsize = "13";
        $border = "1px solid ";
        $count = 35;
        $page = 35;
        $str .= $this->reporter->beginreport();

        $str .= $this->reporter->begintable('600');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br><br>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('CLEARANCE', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col('', '360', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '75', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br />';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('To Whom It May Concern:', '105', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('This is to certify that ' . $data[0]['empname'] . ', whose signature appears below,', '305', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('has been cleared from any property and financial obligations with the company.', '305', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->printline();
        $str .= "<br>";

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE HIRED : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['hired'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LAST DAY OF WORK : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['lastday'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CAUSE OF SEPERATION : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['cause'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('IMMEDIATE HEAD : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['empheadname'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';



        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Department Head', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Accounting Department', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Human Resources Department', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Audit Department', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['empname'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('VP/President', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Print Name & Signature of Employee', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }


    public function rpt_HC_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1250]);
        PDF::SetMargins(40, 40);


        $empid = $data[0]['empid'];
        $division = $this->coreFunctions->getfieldvalue("employee", "divid", "empid=?", [$empid]); //divi
        $divcode = $this->coreFunctions->getfieldvalue("division", "divcode", "divid=?", [$division]);

        $divname = $this->coreFunctions->getfieldvalue("division", "divname", "divid=?", [$division]);
        switch ($divcode) {
            case '001':
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'paflogo.png', 40, 10,  720, 100); //x   x   width height
                break;
            case '002':
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'mbcpaflogo.png', 40, 10,  720, 100); //x   x   width height
                break;
            case '003':
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'ridefundpaf.png', 40, 10,  720, 100); //x   x   width height
                break;
        }

        PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n");
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(720, 18, "APPLICATION FOR CLEARANCE CERTIFICATE", '', 'C', false);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '');

        PDF::SetFont($fontbold, '', 11);

        PDF::MultiCell(360, 18, ' NAME: ' . (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'LT', 'L', false, 0);
        PDF::MultiCell(360, 18, ' DATE FILED: ' . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'LTR', 'L', false);
        PDF::MultiCell(360, 18, ' POSITION: ' . (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'LT', 'L', false, 0);
        PDF::MultiCell(360, 18, ' DATE HIRED: ' . (isset($data[0]['hired']) ? $data[0]['hired'] : ''), 'LTR', 'L', false);
        PDF::MultiCell(360, 18, ' COMPANY: ' . $divname, 'LT', 'L', false, 0);
        PDF::MultiCell(360, 18, ' DATE OF SEPARATION: ' . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'LTR', 'L', false);
        PDF::MultiCell(360, 18, ' BRANCH: ' . (isset($data[0]['brname']) ? $data[0]['brname'] : ''), 'LT', 'L', false, 0);
        PDF::MultiCell(360, 18, ' DEPARTMENT: ' . (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'LTR', 'L', false);
        PDF::MultiCell(720, 18, ' Contact No.: ' . (isset($data[0]['contactno']) ? $data[0]['contactno'] : ''), 'LTR', 'L', false);

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(720, 18, ' REASON FOR SEPARATION:', 'LTR', 'L', false);

        // Draw the 10x10 checkbox
        PDF::SetXY(40, 264);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(110, 20, '', 'L', 'L', false, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::SetLineWidth(0.5);
        PDF::Rect(137, 273.5, 10, 10); // slight Y adjustment to center with label
        // Align label to match box height 10 and font size 11
        PDF::SetXY(155, 273);
        PDF::MultiCell(65, 11, 'Resignation', '', 'L', false, 0);


        PDF::SetDrawColor(0, 0, 0);
        PDF::SetLineWidth(0.5);
        PDF::Rect(255, 273.5, 10, 10); // slight Y adjustment to center with label


        PDF::SetXY(273, 273);
        PDF::MultiCell(130, 11, 'End of Contract', '', 'L', false, 0);


        PDF::SetDrawColor(0, 0, 0);
        PDF::SetLineWidth(0.5);
        PDF::Rect(410, 273.5, 10, 10); // slight Y adjustment to center with label


        PDF::SetXY(428, 273);
        PDF::MultiCell(130, 11, 'Retirement', '', 'L', false, 0);


        PDF::SetDrawColor(0, 0, 0);
        PDF::SetLineWidth(0.5);
        PDF::Rect(528, 273.5, 10, 10); // slight Y adjustment to center with label

        PDF::SetXY(546, 273);
        PDF::MultiCell(130, 13, 'Others',  '', 'L', false, 0);


        PDF::SetFont($font, '', 11);
        PDF::SetXY(676, 264);
        PDF::MultiCell(84, 20, '', 'R', 'L', false, 1);

        PDF::SetFont($font, '', 2);
        PDF::MultiCell(720, 5, '', 'LBR', '', false, 1);

        // $currentY = PDF::GetY(); //289

        // var_dump($currentY);

        // track the deepest Y shift

        function printRow($startX, &$currentY, $texts, $widths, $fonts, $fontSizes, $borders = [], $aligns = [], $lineHeight = 18, $extraX = [], $extraY = [], $moveCursor = true, $fontStyles = [])
        {
            $x = $startX;
            $maxExtraY = 0;

            foreach ($texts as $i => $text) {
                $offsetX = isset($extraX[$i]) ? $extraX[$i] : 0;
                $offsetY = isset($extraY[$i]) ? $extraY[$i] : 0;
                $maxExtraY = max($maxExtraY, $offsetY);

                PDF::SetXY($x + $offsetX, $currentY + $offsetY);
                $font = isset($fonts[$i]) ? $fonts[$i] : 'dejavusans';
                $fontSize = isset($fontSizes[$i]) ? $fontSizes[$i] : 11;
                $fontStyle = isset($fontStyles[$i]) ? $fontStyles[$i] : '';
                $border = isset($borders[$i]) ? $borders[$i] : 0;
                $align = isset($aligns[$i]) ? $aligns[$i] : 'L';

                PDF::SetFont($font, $fontStyle, $fontSize);

                if ($text === '__checkbox__') {
                    PDF::SetDrawColor(0, 0, 0);
                    PDF::SetLineWidth(0.2);
                    PDF::Rect($x + $offsetX, $currentY + $offsetY, 10, 10);
                    PDF::MultiCell($widths[$i], $lineHeight, '', $border, $align, false, 0);
                } else {
                    PDF::MultiCell($widths[$i], $lineHeight, $text, $border, $align, false, 0);
                }

                $x += $widths[$i];
            }

            if ($moveCursor) {
                PDF::Ln();
                $currentY += $lineHeight + $maxExtraY;
            }
        }


        $startX = 40;
        $currentY = 289;
        $lineHeight = 18;
        // Header row
        printRow(
            $startX,
            $currentY,
            ['DEPARTMENT', 'REMARKS', 'APPROVAL'],
            [300, 220, 200],
            [$fontbold, $fontbold, $fontbold],
            [11, 11, 11],
            ['LB', 'LB', 'LBR'],
            ['C', 'C', 'C'],
            $lineHeight
        );
        /////// ito yung line na sasakop sa lahat pababa draw lang to line
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [300, 300, 300, 300], //HANGGANG 300 VERTICAL
            ['L',  'L', 'L', 'LR'], // Draw top border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            false // <---- DON'T MOVE CURRENT Y
        );


        // Data row
        printRow(
            $startX,
            $currentY,
            ['', 'BRANCH/DEPARTMENT :', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [15, 285, 110, 110, 200],
            [$fontbold, $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [0, 0, 5, 5, 5] //x
        );


        printRow(
            $startX,
            $currentY,
            ['', '', '', 'from', 'To', ''], // example texts
            [15, 240, 35, 110, 110, 200], // widths
            [$font, $font, $font, $font, $font, $font], // fonts
            [0, 7, 0, 11, 11, 11], // font sizes
            ['', 'B', '', '', '', ''], // borders
            ['L', '', '', '', '', ''], // aligns
            2, // line height
            [0, 0, 0, 15, 15, 0], //x
            [0, 0, 0, 1, 1, 0] //PAG NEGATIVE PATAAS
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Branch / Department Head', '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0],   // extraX 
            [13, 13, 0, 0, 0]  // extraY per column
        );


        printRow(
            $startX,
            $currentY,
            ['▶', 'Assets (CP, Sim, Service unit, etc.)', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['▶', 'Receipts pending at branch', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['', '(should be forwarded to H.O)', '', '', '', 'Supervisor/Branch OIC', ''],
            [20, 280, 110, 110, 20, 160, 20],
            [$font, $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', 'C', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontStyles
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Tools', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Uniforms, ID, Handbook, etc.', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'MC Unit - Charges ', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'TBA\'S, Helmet, Jersey, etc.', '', '', '', 'ROM /Dept. Head ', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );

        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Turn - over list (to be attach in this form)', '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0],
            [], // extraY
            false
        );

        //////ITO YUNG LINE PAG TAPOS NG TURN OVER

        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );


        printRow(
            $startX,
            $currentY,
            ['Purchasing & Distribution Department', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5] //x
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'MIS / Inventory (TBA\'S, Helmet, Jersey, etc.) ',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );




        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'MC Damages / Losses', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Others', '', '', '', 'Department Manager', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            false, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );

        /////////line after ng tba helmet jersy

        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );


        printRow(
            $startX,
            $currentY,
            ['COMPTROLLERS OFFICE', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5] //x
        );

        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Accounting Department',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Debit Memo', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Cash Advances', '', '', '', ' Accounting Manager', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', 'C', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', '']
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Vouchers ', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Receipts', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0]
        );


        /////////////////////////////////////////////////////////////////////////////////
        ///ANOTHER LINE KADUGSONG 300 SA TAAS
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [300, 300, 300, 300], //HANGGANG 300 VERTICAL
            ['L',  'L', 'L', 'LR'], // Draw top border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            false // <---- DON'T MOVE CURRENT Y
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Etc.', '', '', '', 'Budget & Asset Head', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', 'C', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', '']
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'BUDGET & ASSET', '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0] // extraY per column
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'MC Unit / Service', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'CP/Laptop, etc.', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Assets assigned', '', '', '', ' Comptrollers Head', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', 'C', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', '']
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Others', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            false // moveCursor
        );

        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );



        printRow(
            $startX,
            $currentY,
            ['LIAISON OFFICE:', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5] //x
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Turn - over of OR/CR',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );




        printRow(
            $startX,
            $currentY,
            ['✓', 'Plate No.', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Registration', '', '', '', ' Liaison Head', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Lacking Documents',  '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0], // extraY per column
            [], // extraY (optional if wala ka)
            false // moveCursor
        );

        /////////line after ng tLacking doc

        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );


        printRow(
            $startX,
            $currentY,
            ['CREDIT & COLLECTION & LOANS DEPARTMENT: ', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5] //x
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'MC UNIT LOAN',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );

        printRow(
            $startX,
            $currentY,
            ['✓', 'CO - MAKERS', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'PENDING ACCOUNTABLES', '', '', '', 'CCO Manager', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Others: _________________',  '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['LOANS: ',  '', '', '', '', ''],
            [300, 110, 110, 20, 160, 20],
            [$fontbold, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11],
            ['', '', '', '', 'B', ''],
            ['L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [5, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Charges', '', '', '', ' Loans In-charge', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Folders / Documents',  '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0] // extraY per column
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Others',  '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0], // extraY per column
            [], // extraY (optional if wala ka)
            false // moveCursor
        );

        /////////line after ng tLacking doc

        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );




        ///////////////////  RIDEFUND FINANCING CORP. 
        printRow(
            $startX,
            $currentY,
            ['RIDEFUND FINANCING CORP.', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5]
        );


        ///ANOTHER LINE KADUGSONG nagdagdag ako ng 50 pababa 
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [50, 50, 50, 50], //HANGGANG 300 VERTICAL
            ['L',  'L', 'L', 'LR'], // Draw top border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [-3, -3, -3, -3],
            false // <---- DON'T MOVE CURRENT Y
        );

        printRow(
            $startX,
            $currentY,
            ['✓', 'Salary Loan',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );




        printRow(
            $startX,
            $currentY,
            ['✓', 'MC Unit Loan', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Others:', '', '', '', 'Manager', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            false, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );


        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );



        ///ANOTHER LINE KADUGSONG nagdagdag ako ng 50 pababa 
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [50, 50, 50, 50], //HANGGANG 300 VERTICAL
            ['L',  'L', 'L', 'LR'], // Draw top border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            false // <---- DON'T MOVE CURRENT Y
        );

        printRow(
            $startX,
            $currentY,
            ['AUDIT DEPARTMENT:', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5]
        );


        printRow(
            $startX,
            $currentY,
            ['✓', 'Random Checking Findings',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Pending Cases, IR, Findings, etc.',  '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Charges (If Any)', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );


        ///ANOTHER LINE KADUGSONG nagdagdag ako ng 22 pababa 
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [22, 22, 22, 22], //HANGGANG 300 VERTICAL
            ['L',  'L', 'L', 'LR'], // Draw top border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [-9, -9, -9, -9],
            false // <---- DON'T MOVE CURRENT Y
        );



        printRow(
            $startX,
            $currentY,
            ['✓', 'Others: ___________________', '', '', '', ' Audit Head', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            false, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );


        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            false // <---- DON'T MOVE CURRENT Y
        );


        ///////////////////       NEW PAGE
        PDF::AddPage();
        $currentY =  PDF::GetY();

        ///ITO YUNG Guhit pababa dun sa bagong page 
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [300, 300, 300, 300], //HANGGANG 300 VERTICAL
            ['TL',  'TL', 'TL', 'TLR'], // Draw top border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            false // <---- DON'T MOVE CURRENT Y
        );


        printRow(
            $startX,
            $currentY,
            ['FINANCE DEPARTMENT:', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5]
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'LIQUIDATION',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );



        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'DM / Unliquidated Budget Request',  '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );




        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'PENDING CHECKS', '', '', '', '', ''],
            [15, 285, 110, 110, 10, 180, 10],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Turn-over List (Cashiers Only)', '', '', '', 'Finance Manager / Supervisor ', ''],
            [15, 285, 110, 110, 10, 180, 10],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Others: __________________',  '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0], // extraY per column
            [], // extraY (optional if wala ka)
            false // moveCursor
        );



        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );




        printRow(
            $startX,
            $currentY,
            ['PIONEERS COOPERATIVE FUND(PCF):', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5]
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Pending Loan',  'from', 'To', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0] // extraY per column
        );



        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Co - Maker', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Shares', '', '', '', 'PCF OFFICER', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );


        printRow(
            $startX,
            $currentY,
            ['Others ___________', '',  '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5],
            [], // extraY (optional if wala ka)
            false, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );



        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [],
            true // <---- DON'T MOVE CURRENT Y
        );


        // Data row
        printRow(
            $startX,
            $currentY,
            ['HUMAN RESOURCE DEPARTMENT', 'DATE RECEIVED', 'DATE FORWARDED', 'Signed By:'],
            [300, 110, 110, 200],
            [$fontbold, $font, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [0, 5, 5, 5] //x
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'EMPLOYMENT', 'from:', 'to:', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 5, 5, 0],   // extraX 
            [0, 0, 0, 0, 0]  // extraY per column
        );


        printRow(
            $startX,
            $currentY,
            ['▶', 'Handbook    __________', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['▶', 'Company ID __________ ', '', '', '', '', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['L', 'L', 'L', 'L', 'L', '', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Company Issued uniform ______', '', '', '', 'HR / Administrator ', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', 'C', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontStyles
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Exit Interview ________', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Others ______________', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Disciplinary Charges / Penalties', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Penalty P___________', '', '', '', 'Payroll & Benefits In-charge', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', 'C', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontStyles
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Penalty P ___________', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );



        ///////new line pababa 
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [273, 115, 115, 273], //HANGGANG 300 VERTICAL
            ['L',  'L', 'LR', 'R'], //lr border
            ['', '', '', ''],
            $lineHeight,
            [],
            [-2, -2, -2, -2],
            false // <---- DON'T MOVE CURRENT Y
        );

        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Timekeeping', '', '', ''],
            [15, 285, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['', 'L', 'L', 'L', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'Period Covered ____________ ', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['(Pls. attach last cut-off DTR)', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [68, 0, 0, 0]
        );





        printRow(
            $startX,
            $currentY,
            ['__checkbox__', 'Payroll / Benefits', '', '', '', '', ''],
            [15, 285, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', 'B', ''],
            ['', 'L', 'L', 'L', 'L', 'C', 'L'], //alignment
            $lineHeight,
            [35, 40, 0, 0, 0, 0, 0]
        );



        printRow(
            $startX,
            $currentY,
            ['▶', 'DM # __________Amount______', '', '', '', '  HR Manager', ''],
            [20, 280, 110, 110, 20, 160, 20],
            ['dejavusans', $font, $font, $font, $font, $font, $font],
            [11, 11, 11, 11, 11, 11, 11],
            ['', '', '', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L', 'C', ''],
            $lineHeight,
            [72, 68, 0, 0, 0, 0, 0],
            [], // extraY (optional if wala ka)
            true, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontStyles
        );


        printRow(
            $startX,
            $currentY,
            ['▶', 'DM # __________Amount______', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['▶', 'DM # __________Amount______', '', '', ''],
            [20, 280, 110, 110, 200],
            ['dejavusans', $font, $font, $font, $font],
            [11, 11, 11, 11, 11],
            ['', '', '', '', ''],
            ['L', 'L', 'L', 'L', 'L'],
            $lineHeight,
            [72, 68, 0, 0, 0]
        );


        printRow(
            $startX,
            $currentY,
            ['Others ___________', '',  '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [11, 11, 11, 11],
            ['', '', '', ''],
            ['L', 'L', 'L', 'L'],
            $lineHeight,
            [5, 5, 5, 5],
            [], // extraY (optional if wala ka)
            false, // moveCursor
            ['', '', '', '', '', 'I', ''] // fontstyle
        );


        ///////new line botom only
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [1, 1, 1, 1], //HANGGANG 300 VERTICAL
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [-2, -2, -2, -2],
            true // <---- DON'T MOVE CURRENT Y
        );

        // Data row
        printRow(
            $startX,
            $currentY,
            ['OVERALL REMARKS:', '(To be filled by HR DEPARTMENT)'],
            [105, 615],
            [$fontbold, $font],
            [11, 11],
            ['', ''],
            ['L', 'L'],
            $lineHeight,
            [5, 5],
            [],
            true, // moveCursor
            ['', 'I'] // fontstyle
        );


        ///////new line botom only
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [20, 20, 20, 20],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [0, 0, 0, 0],
            true // <---- DON'T MOVE CURRENT Y
        );



        // Data row
        printRow(
            $startX,
            $currentY,
            ['MANAGEMENT\'S SIGNATURE:'],
            [720],
            [$fontbold],
            [11],
            [''],
            ['L'],
            $lineHeight,
            [5], //x
            [9], //y
            true, // moveCursor
            [''] // fontstyle
        );

        ///space ito

        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [20, 20, 20, 20],
            ['',  '', '', ''],
            ['', '', '', ''],
            $lineHeight,
            [],
            [0, 0, 0, 0],
            true // <---- DON'T MOVE CURRENT Y
        );




        // Data row
        printRow(
            $startX,
            $currentY,
            ['CARIZSA JADE A. MACASARTE-DACUBOR', 'KRISTOPPER A. MACASARTE'],
            [360, 360],
            [$fontbold, $fontbold],
            [12, 12],
            ['', ''],
            ['L', 'L'],
            $lineHeight,
            [5, 0],
            [],
            true, // moveCursor
            ['', ''] // fontstyle
        );


        // Data row
        printRow(
            $startX,
            $currentY,
            ['GENERAL MANAGER', 'GENERAL OPERATION MANAGER'],
            [360, 360],
            [$font, $font],
            [11, 11],
            ['', ''],
            ['L', 'L'],
            $lineHeight,
            [5, 0],
            [],
            true, // moveCursor
            ['', ''] // fontstyle
        );

        // Data row
        printRow(
            $startX,
            $currentY,
            ['CDO 2CYCLES MKTG. CORP.', 'MOTORMATE MERCHANDISING CORP.'],
            [360, 360],
            [$font, $font],
            [11, 11],
            ['', ''],
            ['L', 'L'],
            $lineHeight,
            [5, 0],
            [],
            true, // moveCursor
            ['', ''] // fontstyle
        );






        // Data row
        printRow(
            $startX,
            $currentY,
            ['MARISSA A. MACASARTE', 'CARLOS R. MACASARTE'],
            [360, 360],
            [$fontbold, $fontbold],
            [12, 12],
            ['', ''],
            ['L', 'L'],
            $lineHeight,
            [5, 0],
            [25, 25],
            true, // moveCursor
            ['', ''] // fontstyle
        );


        // Data row
        printRow(
            $startX,
            $currentY,
            ['CFO/VP', 'CEO/PRESIDENT'],
            [360, 360],
            [$font, $font],
            [11, 11],
            ['', ''],
            ['L', 'L'],
            $lineHeight,
            [5, 0],
            [],
            false, // moveCursor
            ['', ''] // fontstyle
        );


        // ///////new line botom only
        printRow(
            $startX,
            $currentY, // same Y
            ['', '', '', ''],
            [300, 110, 110, 200],
            [$font, $font, $font, $font],
            [2, 2, 2, 2],
            ['B',  'B', 'B', 'B'], // Draw bottom border only, for example
            ['', '', '', ''],
            $lineHeight,
            [],
            [0, 0, 0, 0],
            true // <---- DON'T MOVE CURRENT Y
        );

        // Data row
        printRow(
            $startX,
            $currentY,
            ['Legend: (HO) Head Office, (JO) Job Order, (ROM) Regional Operations Manager, (IR) Incident Report, (DM) Debit Memo'],
            [720],
            [$fontbold],
            [11],
            [''],
            ['L'],
            $lineHeight,
            [2], //x
            [5], //y
            true, // moveCursor
            [''] // fontstyle
        );



        // Data row
        printRow(
            $startX,
            $currentY,
            ['Note:'],
            [720],
            [$fontbold],
            [11],
            [''],
            ['L'],
            $lineHeight,
            [2], //x
            [15], //y
            true, // moveCursor
            [''] // fontstyle
        );



        // Data row
        printRow(
            $startX,
            $currentY,
            ['* HR Department will ONLY accept CERTIFICATE OF CLEARANCE for Computation once duly ACCOMPLISHED all signatories.'],
            [720],
            [$font],
            [11],
            [''],
            ['L'],
            $lineHeight,
            [2], //x
            [0], //y
            true, // moveCursor
            [''] // fontstyle
        );

        // Data row
        printRow(
            $startX,
            $currentY,
            ['* Every outgoing personnel should submit turn-over list to corresponding head/oic and should be attached in this form.'],
            [720],
            [$font],
            [11],
            [''],
            ['L'],
            $lineHeight,
            [2], //x
            [0], //y
            true, // moveCursor
            [''] // fontstyle
        );

        // Data row
        printRow(
            $startX,
            $currentY,
            ['* Signature should be placed with full name. '],
            [720],
            [$font],
            [11],
            [''],
            ['L'],
            $lineHeight,
            [2], //x
            [0], //y
            true, // moveCursor
            [''] // fontstyle
        );


        // Data row
        printRow(
            $startX,
            $currentY,
            [$data[0]['empname'], ''],
            [300, 420],
            [$font, $font],
            [11, ''],
            ['B', ''],
            ['C', ''],
            $lineHeight,
            [2, 0], //x
            [100, 100], //y
            true, // moveCursor
            [''] // fontstyle
        );


        // Data row
        printRow(
            $startX,
            $currentY,
            ['EMPLOYEE PRINT NAME & SIGNATURE', ''],
            [300, 420],
            [$fontbold, $font],
            [11, ''],
            ['', ''],
            ['C', ''],
            $lineHeight,
            [2, 0], //x
            [0, 0], //y
            true, // moveCursor
            [''] // fontstyle
        );

        // Data row
        printRow(
            $startX,
            $currentY,
            ['DATE:', ''],
            [300, 420],
            [$font, $font],
            [11, ''],
            ['', ''],
            ['L', ''],
            $lineHeight,
            [55, 0], //x
            [0, 0], //y
            true, // moveCursor
            [''] // fontstyle
        );



        // Data row
        printRow(
            $startX,
            $currentY,
            ['Contact No.:', ''],
            [300, 420],
            [$font, $font],
            [11, ''],
            ['', ''],
            ['L', ''],
            $lineHeight,
            [55, 0], //x
            [0, 0], //y
            true, // moveCursor
            [''] // fontstyle
        );


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function authority_and_consent_pdf($params, $data)
    {
        $font = "";
        $fontbold = "";
        $fontsize1 = 14;
        $fontsize2 = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/ARIALUNIMS.OTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMS.OTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMSBOLD.OTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(90, 90, 80);
        PDF::SetY(150);

        $employee = isset($data[0]) ? $data[0] : [];
        $employee_name = isset($employee['empname']) ? $employee['empname'] : '';
        $address = isset($employee['address']) ? $employee['address'] : '';


        $lastday = isset($employee['lastday']) ? $employee['lastday'] : '';
        $effectdate = date('F d, Y', strtotime($lastday));
        $lastpay = $employee['lastpay'];
        $deduction = $employee['deduction'];

        // $fullname = isset($employee['fullname']) ? $employee['fullname'] : '';
        $fname = isset($employee['name']) ? $employee['name'] : '';
        $mname = isset($employee['mname']) ? $employee['mname'] : '';
        $lname = isset($employee['lname']) ? $employee['lname'] : '';
        $witnessname = isset($employee['witnessname']) ? $employee['witnessname'] : '';


        $this->company_logo($params, $data);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(620, 0, 'Republic of the Philippines}', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(620, 0, 'City of Cagayan de Oro City}', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);


        $html_content = '<br /><strong>AUTHORITY AND CONSENT</strong>';
        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: center; line-height: 1.5; font-size:' . $fontsize1 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

        $html_content = '<br />I, <strong>' . $fname .' '.$mname.' '.$lname .'</strong> of legal age, single/married, Filipino , and a resident of <strong><u>' . $address . '</u></strong> after having been sworn in accordance to law hereby depose and say:';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);


        PDF::MultiCell(0, 0, "\n");

        $number1 = '1.That I am currently employed with and have expressed my intention to resign/retire from employment effective <strong>' . $effectdate . '</strong>.';
        $lastpay1 = $this->reporter->ftNumberToWordsConverter($employee['lastpay'], false, 'PHP', false);
        $lastpay2 = ucwords(strtolower($lastpay1));
        $deduction1 = $this->reporter->ftNumberToWordsConverter($employee['deduction'], false, 'PHP', false);
        $deduction2 = ucwords(strtolower($deduction1));

        // $number2 = '2.That I acknowledge that my rightful net separations pay is '
        //     . '<strong><u>' . $lastpay2 . '</u></strong>'
        //     . '<u>( <span style="font-family:DejaVu Sans;">₱</span></u>'
        //     . '<u><span style="font-size:11px;font-weight:bold;">' . number_format($lastpay, 2) . '</span> ).</u>';
        $amount = '( <span style="font-family:DejaVu Sans;">₱</span>'
         . '<span style="font-size:11px;font-weight:bold;">' . number_format($lastpay, 2) . '</span> ).';

        $number2 = '2.That I acknowledge that my rightful net separations pay is '
                . '<strong><u>' . $lastpay2 . $amount . '</u></strong> ';

        $amount2 = '( <span style="font-family:DejaVu Sans;">₱</span>'
         . '<span style="font-size:11px;font-weight:bold;">' . number_format($deduction, 2) . '</span> ).';

        $number3 = '3.That I also acknowledge that I have an existing deduction direct to my salary in the amount of '
            . '<strong><u>' . $deduction2 . $amount2 . '</u></strong>';

        $number4 = '4.That I hereby give full consent to, and in fact authorize, the company to deduct from my gross separation the above stated obligation and that the said deduction
                shall constitute as full payment for my indebtedness;';
        $number5 = '5.That I am executing this affidavit to attest to the veracity of the foregoing facts;';
        $number6 = 'and for whatever legitimate purpose this may serve.';
        $number7 = 'IN WITNESS WHEREOF, I have hereunto set my hand this _____________________________, at Cagayan de Oro City. ';
        // $number8 = 'at Cagayan de Oro City.';

        $html = '
        <table cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td width="8%"></td>
            <td width="90%" valign="top" style="
            text-align: justify;
            font-size: ' . $fontsize2 . 'px;
            font-family: ' . $font . ';
            line-height: 1.8;
            padding:0;
            margin:0;
            ">' . $number1 . '</td>
        </tr>

        <tr>
            <td width="8%"></td>
            <td width="90%" valign="top" style="
            text-align: justify;
            font-size: ' . $fontsize2 . 'px;
            font-family: ' . $font . ';
            line-height: 1.8;
            padding:0;
            margin:0;
            ">' . $number2 . '</td>
        </tr>

        <tr>
            <td width="8%"></td>
            <td width="90%" valign="top" style="
            text-align: justify;
            font-size: ' . $fontsize2 . 'px;
            font-family: ' . $font . ';
               line-height: 1.8;
            padding:0;
            margin:0;
            ">' . $number3 . '</td>
        </tr>

          <tr>
            <td width="8%"></td>
            <td width="90%" valign="top" style="
            text-align: justify;
            font-size: ' . $fontsize2 . 'px;
            font-family: ' . $font . ';
               line-height: 1.8;
            padding:0;
            margin:0;
            ">' . $number4 . '</td>
        </tr>
           
             
        <tr>
        <td width="8%"></td>
        <td width="90%" valign="top" style="
            font-size: ' . $fontsize2 . 'px;
            font-family: ' . $font . ';
               line-height: 1.8;
            text-align: justify;
            padding:0; margin:0;
        ">' . $number5 . '
        </td>
        </tr>

         <tr>
        <td width="8%"></td>
        <td width="90%" valign="top" style="
            font-size: ' . $fontsize2 . 'px;
            font-family: ' . $font . ';
               line-height: 1.8;
            text-align: justify;
            padding:0; margin:0;
        ">' . $number6 . '
        </td>
        </tr>

  

        
        <tr><td colspan="3" style="height:10px;"></td></tr>

         <tr>
            <td width="8%"></td>
            <td width="90%" valign="top" style="
            text-align: left;
            font-size: ' . $fontsize2 . 'px;
            font-family: ' . $font . ';
            line-height: 1.8;
            padding:0;
            margin:0;
            ">' . $number7 . '</td>
        </tr>
       

        </table>
        ';

        PDF::writeHTML($html, true, false, true, false, '');

         //  <tr>
        //     <td width="13%"></td>
        //     <td width="85%" valign="top" style="
        //     text-align: justify;
        //     font-size: ' . $fontsize2 . 'px;
        //     font-family: ' . $font . ';
        //        line-height: 1.8;
        //     padding:0;
        //     margin:0;
        //     ">' . $number8 . '</td>
        // </tr>
      
        if($fname != ''){
            $fname = ucwords(strtolower(trim($fname)));
        }else{
            $fname='';
        }
        if($mname != ''){
            $mname=ucwords(strtolower(trim($mname)));
        }else{
            $mname='';
        }
         if($lname != ''){
            $lname=ucwords(strtolower(trim($lname)));
        }else{
            $lname='';
        }

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize2);

        PDF::MultiCell(310, 0, '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        $html_content = '<strong><u>' . $fname. ' ' . $mname. ' ' . $lname . '</u></strong>';
        PDF::writeHTMLCell(310, 0, '', '', '<p style="text-align: center; line-height: 1.5; font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(310, 0, '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(310, 0, 'Affiant', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(60, 0, '', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);


        // PDF::MultiCell(310, 0, '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(310, 0,  $fname. ' ' . $mname. ' ' . $lname, 'B', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        // // PDF::MultiCell(60, 0, '', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

        // PDF::SetFont($font, '', $fontsize2);
        // PDF::MultiCell(310, 0, '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(310, 0, 'Affiant', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        // // PDF::MultiCell(60, 0, '', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(310, 0, '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(310, 0, 'SSS No.', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(50, 0, '', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(0, 0, "\n");
        $html_content = '<br /><strong>A C K N O W L E D G E M E N T</strong>';
        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: center; line-height: 1.5; font-size:' . $fontsize1 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);


        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(620, 0, 'Republic of the Philippines}', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(620, 0, 'City of Cagayan de Oro City}', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");
        $html_content = 'At the City of Cagayan de Oro, on the ____________________________________, personally';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize2 . 'px; text-indent: 100px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);
        $html_content = 'appeared _____________________________________________, who established to me by satisfactory evidence';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

        $html_content = 'to be the same person who executed the foregoing instrument, and acknowledged to me that the same is her';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: left; line-height: 1.5; font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);
       
        $html_content = 'free act and deed.';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: left; line-height: 1.5; font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);


        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::MultiCell(200, 0, 'Doc. No. ________________', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 0, '', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 0, 'Page No. _______________', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 0, '', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 0, 'Book No. _______________', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 0, '', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(620, 0, 'Series of 20', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);




        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function waiver_pdf($params, $data)
    {
        $font = "";
        $fontbold = "";
        $fontsize1 = 14;
        $fontsize2 = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/ARIALUNIMS.OTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMS.OTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMSBOLD.OTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(90, 90, 80);
        PDF::SetY(120);


        $employee = isset($data[0]) ? $data[0] : [];
        $employee_name = isset($employee['empname']) ? $employee['empname'] : '';
        $address = isset($employee['address']) ? $employee['address'] : '';
        $company_name = 'CDO 2 CYCLES MARKETING CORPORATION';

        $lastday = isset($employee['lastday']) ? $employee['lastday'] : '';
        $effectdate = date('F d, Y', strtotime($lastday));
        $lastpay = $employee['lastpay'];
        $deduction = $employee['deduction'];

        $fname = isset($employee['name']) ? $employee['name'] : '';
        $mname = isset($employee['mname']) ? $employee['mname'] : '';
        $lname = isset($employee['lname']) ? $employee['lname'] : '';
        $witnessname = isset($employee['witnessname']) ? $employee['witnessname'] : '';
        $witnessname2 = isset($employee['witnessname2']) ? $employee['witnessname2'] : '';

        $lastpay1 = $this->reporter->ftNumberToWordsConverter($employee['lastpay'], false, 'PHP', false);
        $lastpay2 = ucwords(strtolower($lastpay1));
        $deduction1 = $this->reporter->ftNumberToWordsConverter($employee['deduction'], false, 'PHP', false);
        $deduction2 = ucwords(strtolower($deduction1));


        $this->company_logo($params, $data);

        $html_content = '<strong>WAIVER and QUITCLAIM</strong>';
        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: center; line-height: 1.5; font-size:' . $fontsize1 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(620, 0, 'KNOW ALL MEN BY THESE PRESENTS:', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

        $html_content = '<br />That I, <strong>' . $fname .''.  $mname .''.$lname.'</strong> legal age, Filipino ,and with address at <strong><u>' . $address . '</u></strong> on my own free will, and for valuable consideration, hereby declare and manifest:';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; text-indent: 50px;font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);


        $html_content = '<br />That I have ceased to be employed at <strong>' . $company_name . '</strong> effective at the close of business hours of <strong>' . $effectdate . '</strong>.';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; text-indent: 50px;font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

        $amount = '( <span style="font-family:DejaVu Sans;">₱</span>'
         . '<span style="font-size:11px;font-weight:bold;">' . number_format($lastpay, 2) . '</span> ).';


        $html_content = '<br />That in connection with my former employment with ' . $company_name . ', for valuable consideration  <strong>' . $lastpay2 .'<u>'.$amount.'</u>'. '</strong>
                        these presents, I hereby release, waive and forever discharge ' . $company_name . ', its officers, directors, representatives or employees from any actions for sums of money or other obligations arising from
                        my previous employment with ' . $company_name . ' I acknowledge that I have received all amounts that are now or in the future maybe due me from ' . $company_name . '
                        I therefore undertake not to do any act prejudicial to the interest of ' . $company_name . ', its branches, or its projects in the Philippines arising from my previous employment.';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; text-indent: 50px;font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

        PDF::MultiCell(620, 3,'');
        $html_content = 'That I acknowledge that I have no cause of actions whatsoever, criminal, civil or otherwise against ' . $company_name . ',  its officers, its agents or
                        representatives or project employees with respect to any matter arising from or cessation of my employment with ' . $company_name . ' I further warrant that I will
                        institute no action and will not continue to prosecute, pending actions, if any against ' . $company_name . ' , its officers, agents or representatives or project employees.';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; text-indent: 50px;font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);


        $html_content = '<br />In WITNESS WHEREOF, I have hereunto set my hand this ________________________________ day of ____________________ , ______________ at ' . $company_name . ', 
    Western Kolambog Lapasan, Cagayan de Oro City.';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; text-indent: 50px;font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

         if($fname != ''){
            $fname = ucwords(strtolower(trim($fname)));
        }else{
            $fname='';
        }
        if($mname != ''){
            // $mname=ucwords(strtolower(trim($mname)));
            
        }else{
            $mname='';
        }
         if($lname != ''){
            $lname=ucwords(strtolower(trim($lname)));
        }else{
            $lname='';
        }

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(310, 0, '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(310, 0,  $fname . ' ' . $mname . '. ' . $lname, '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(60, 0, '', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(310, 0, '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(310, 0, 'Employee’s Signature over printed name', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(60, 0, '', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(620, 0, 'Signed in the presence:', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize2);
        if ($witnessname != "") {
            $parts = array_map('trim', explode(',', $witnessname));
            $formattedname = ucwords(strtolower($parts[1])) . ' ' . ucwords(strtolower($parts[0]));
        } else {
            $formattedname = '';
        }

        if ($witnessname2 != "") {
            $parts2 = array_map('trim', explode(',', $witnessname2));
            $formattedname2 = ucwords(strtolower($parts2[1])) . ' ' . ucwords(strtolower($parts2[0]));
        } else {
            $formattedname2 = '';
        }

        // PDF::MultiCell(720, 0,  $formattedname, '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(206, 0,  $formattedname, '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(208, 0,  '', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(206, 0,  $formattedname2, '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(620, 0, 'REPUBLIC OF THE PHILIPPINESS)', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(620, 0, '___________________________ ) S.S.', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");
        $html_content = 'BEFORE ME, this ______________day of ____________________ in _____________, <u>personally</u>';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5;text-indent: 50px; font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);
        $html_content = 'appeared ' . '<strong>' . $fname . ' ' . $mname. '. ' .$lname . '</strong> Government Issued No._______________ issued at __________________________ on _________________________ known to me to be acknowledge to me that the same is his/her free act and deed.';

        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: left; line-height: 1.5; font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        $html_content = 'IN WITNESS WHEREOF, I have hereunto set my hand and affixed my notarial seal on the date and place above-mentioned.';
        PDF::writeHTMLCell(620, 0, '', '', '<p style="text-align: justify; line-height: 1.5; text-indent: 50px;  font-size:' . $fontsize2 . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);
        PDF::MultiCell(200, 0, 'Doc. No. ________________', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 0, '', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 0, 'Page No. _______________', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 0, '', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 0, 'Book No. _______________', '', 'L', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 0, '', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(620, 0, 'Series of 20', '', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);




        return PDF::Output($this->modulename . '.pdf', 'S');
    }



    public function company_logo($params, $data)
    {

        $font = "";
        $fontbold = "";
        $fontsize = 16;
        if (Storage::disk('sbcpath')->exists('/fonts/ARIALUNIMS.OTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMS.OTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMSBOLD.OTF');
        }

        $employee = isset($data[0]) ? $data[0] : [];

        $center = isset($employee['divcode']) ? $employee['divcode'] : '';
        $logo = '';
        // $center = '004';
        $width = "720px";
        $height = "100px";
        switch ($center) {
            case '001':
                // $logo = URL::to('/images/cdohris/cdohris_logo.png');
                $logo = '/images/cdohris/cdohris_logo.png';
                $width = "620px";
                $height = "100px";
                $align = "C";
                break;
            case '002':
                // $logo = URL::to('/images/cdohris/mbcpaflogo.png');
                $logo = '/images/cdohris/mbcpaflogo.png';
                break;
            case '003':
                // $logo = URL::to('/images/cdohris/ridefundpaf.png');
                $logo = '/images/cdohris/ridefundpaf.png';
                break;
            case '004':
                // $logo = URL::to('/images/cdohris/motormate.png');
                $logo = '/images/cdohris/samplelogo.png';
                $width = "620px";
                $height = "100px";
                $align = "C";
                break;
        }

        if ($logo != '') {
            PDF::Image(public_path($logo), '90', '10', $width, $height);
        }

        // PDF::SetY(150);

        // PDF::MultiCell(0, 0, "\n");
        // PDF::SetFont($fontbold, '', 22);

        // PDF::setFontSpacing(1.5);
        // PDF::MultiCell(0, 0, 'C E R T I F I C A T E ', 0, 'C', 0, 1);

        // PDF::setFontSpacing(0);
    }
}
