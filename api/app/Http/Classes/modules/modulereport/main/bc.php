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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class bc
{

    private $modulename = "Business Clearance";
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
        $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Business Clearance', 'value' => '1', 'color' => 'red'],
            ['label' => 'Clearance for Liquor', 'value' => '2', 'color' => 'red']
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
            '1' as reporttype
            "
        );
    }

    public function generateResult($config) // query
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $query = "
        select date(dateid), head.docno, head.clientname as bname,client.client as bid, head.address, head.ownertype, head.ownername
        from lahead as head
        left join client on client.client = head.client
        where docno like 'BC%'
        union all
        select date(dateid), head.docno, head.clientname as bname,client.client as bid, head.address, head.ownertype, head.ownername
        from glhead as head
        left join client on client.clientid = head.clientid
        where docno like 'BC%';
        ";

        return $this->coreFunctions->opentable($query);
    }

    public function report_members_query($config) //brgy officials
    {
        $query = "
        select category as name, position  
        from reqcategory
        where isbrgyoff = 1 and upper(position) = 'PUNONG BARANGAY'
        ";
        $brgy = json_decode(json_encode($this->coreFunctions->opentable($query)));
        return $brgy;
    } //end fn



    public function reportplotting($config, $data)
    {
        $members = $this->report_members_query($config);
        switch ($config['params']['dataparams']['reporttype']) {
            case '1':
                return $this->default_layout_PDF($config, $data, $members);
                break;
            case '2':
                return $this->liquor_layout_PDF($config, $data, $members);
                break;
        }
    }


    public function default_PDF_header($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        //temp logos
        $logo1 = public_path('images/barangay/1.jpg');
        $logo2 = public_path('images/barangay/2.jpg');

        $currentYear = date('Y');
        $font = "";
        $fontbody = "";
        $fontbold = "";
        $fontsize = "";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $isTimes = file_exists(database_path() . '/images/fonts/times.TTF');
            $isTimesBold = file_exists(database_path() . '/images/fonts/timesbd.TTF');
            if ($isTimes && $isTimesBold) {
                $fonttimes = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
                $fonttimesbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
                $fontbody = 9;
                $fontsize = 12;
                $fontsize2 = 9;
            } else {
                $fonttimes = $font;
                $fonttimesbold = $fontbold;
                $fontbody = 8;
                $fontsize = 11;
                $fontsize2 = 8;
            }
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(40, 40);
        PDF::SetAlpha(0.2); // 0 = invisible, 1 = solid
        PDF::Image($logo2, 10, 110, 600, 600);  //Backgroung logo temp
        PDF::SetAlpha(1);
        //532 
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        PDF::SetFont($fonttimes, '', 9);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(0, 0, '', '', 'L');
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(0, 0, "\n", 'TLR', '');
        PDF::MultiCell(0, 0, 'Republika ng Pilipinas', 'LR', 'C', false);
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), 'LR', 'C');
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(150, 20, '', 'L', '', false, 0);
        PDF::MultiCell(232, 20, strtoupper($headerdata[0]->address), '', 'C', false, 0);
        PDF::MultiCell(150, 20, '', 'R', '', false);
        PDF::MultiCell(0, 0, 'TeleFax: ' . strtoupper($headerdata[0]->tel), 'LR', 'C', false);
        if (file_exists($logo1)) { //temp logo
            PDF::Image($logo1, 80, 30, 80, 80);
        }
        if (file_exists($logo2)) { //temp logo
            PDF::Image($logo2, 450, 30, 80, 80);
        }
        PDF::MultiCell(0, 0, "\n", 'LR', '');
        PDF::MultiCell(0, 0, "\n", 'LR', '');

        PDF::SetFont($fonttimesbold, '', $fontsize);

        PDF::MultiCell(0, 0, 'BARANGAY BUSINESS CLEARANCE', 'LR', 'C', false);
        PDF::MultiCell(0, 0, 'for C.Y ' .  $currentYear, 'LR', 'C', false);
        PDF::MultiCell(0, 0, "\n", 'LR', '');
        PDF::SetFont($fonttimesbold, 'U', 18);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(0, 0, "\n", 'LR', '');
        PDF::MultiCell(0, 0, "\n", 'LR', '');
    }
    public function default_layout_PDF($config, $data, $members)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $fontbold = "";
        $fontsize = "13";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontsize = 11;
        }
        $this->default_PDF_header($config, $data);
        //client
        $header = !empty($data) ? $data[0] : null;
        $docno = isset($header->docno) ? $header->docno : '';
        $bname = isset($header->bname) ? $header->bname : '';
        $bid = isset($header->bid) ? $header->bid : '';
        $ownername = isset($header->ownername) ? $header->ownername : '';
        $address = isset($header->address) ? $header->address : '';
        $type = isset($header->ownertype) ? $header->ownertype : '';
        $dateid = isset($header->dateid) ? $header->dateid : '';
        //brgy
        $member = !empty($members) ? $members[0] : null;
        $name = isset($member->name) ? $member->name : '';
        $position = isset($member->position) ? $member->position : '';

        //fixcolumn
        $arr_docno = $this->reporter->fixcolumn([$docno], '45', 0);
        $arr_bname = $this->reporter->fixcolumn([$bname], '45', 0);
        $arr_bid = $this->reporter->fixcolumn([$bid], '45', 0);
        $arr_ownername = $this->reporter->fixcolumn([$ownername], '45', 0);
        $arr_address = $this->reporter->fixcolumn([$address], '45', 0);
        $arr_type = $this->reporter->fixcolumn([$type], '45', 0);

        $arr_brgyname = $this->reporter->fixcolumn([$name], '45', 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::setFontSpacing(0.7); //extra mm per character
        PDF::MultiCell(15, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(502, 20, 'This is to certify that the APPLICANT whose name appears below is being issued this', '', 'L', false, 0);
        PDF::MultiCell(15, 20, '', 'R', 'C', false);

        PDF::setFontSpacing(0.8); //extra mm per character
        PDF::MultiCell(15, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(502, 20, 'Barangay Business Clearance pursuant to R.A. 7160: Local Government of 1991,', '', 'L', false, 0);
        PDF::MultiCell(15, 20, '', 'R', 'C', false);

        PDF::MultiCell(15, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(502, 20, 'Section 152.', '', 'L', false, 0);
        PDF::MultiCell(15, 20, '', 'R', 'C', false);
        PDF::setFontSpacing(0); // reset

        PDF::MultiCell(0, 0, "\n", 'LR', '');

        PDF::MultiCell(532, 20, '', 'LR', 'C', false);

        PDF::MultiCell(30, 30, '', 'L', 'C', false, 0);
        PDF::MultiCell(130, 30, 'Brgy. Business ID', '', 'L', false, 0);
        PDF::MultiCell(10, 30, ': ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(362, 30, (isset($arr_bid[0]) ? $arr_bid[0] : ''), 'R', 'L', false);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(30, 30, '', 'L', 'C', false, 0);
        PDF::MultiCell(130, 30, 'Business Name', '', 'L', false, 0);
        PDF::MultiCell(10, 30, ': ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(362, 30, (isset($arr_bname[0]) ? $arr_bname[0] : ''), 'R', 'L', false);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(30, 30, '', 'L', 'C', false, 0);
        PDF::MultiCell(130, 30, 'Business Address', '', 'L', false, 0);
        PDF::MultiCell(10, 30, ': ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(362, 30, (isset($arr_address[0]) ? $arr_address[0] : ''), 'R', 'L', false);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(30, 30, '', 'L', 'C', false, 0);
        PDF::MultiCell(130, 30, 'Business Type', '', 'L', false, 0);
        PDF::MultiCell(10, 30, ': ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(362, 30, (isset($arr_type[0]) ? $arr_type[0] : ''), 'R', 'L', false);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(30, 30, '', 'L', 'C', false, 0);
        PDF::MultiCell(130, 30, 'Owner\'s Name', '', 'L', false, 0);
        PDF::MultiCell(10, 30, ': ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(362, 30, (isset($arr_ownername[0]) ? $arr_ownername[0] : ''), 'R', 'L', false);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(532, 20, '', 'LR', 'C', false); //space

        PDF::setFontSpacing(0.7); //extra mm per character
        PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(512, 20, 'This Business Clearance is revocable upon failure of herein Clearance to comply with', '', 'L', false, 0);
        PDF::MultiCell(10, 20, '', 'R', 'C', false);

        PDF::setFontSpacing(1.1); //extra mm per character
        PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(512, 20, 'the provision of existing laws, City Ordinances, rules or regulations that are now', '', 'L', false, 0);
        PDF::MultiCell(10, 20, '', 'R', 'C', false);

        PDF::setFontSpacing(0.7); //extra mm per character
        PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(512, 20, 'prevailing in connection with the said business / activity, without prejudice to future', '', 'L', false, 0);
        PDF::MultiCell(10, 20, '', 'R', 'C', false);

        PDF::setFontSpacing(0.6); //extra mm per character
        PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(512, 20, 'complaints, emanating from the neighbors concerning health, fire or other legitimate', '', 'L', false, 0);
        PDF::MultiCell(10, 20, '', 'R', 'C', false);

        PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(512, 20, 'hazards. This Clearance shall be presented to competent authority upon demand.', '', 'L', false, 0);
        PDF::MultiCell(10, 20, '', 'R', 'C', false);
        PDF::setFontSpacing(0); // reset

        PDF::MultiCell(532, 10, '', 'LR', 'C', false); //space

        PDF::setFontSpacing(0.4); //extra mm per character

        $textMonthYear = date('F') . ' ' . date('Y'); //Month Year
        //'September 2026';
        $actualWidth = PDF::GetStringWidth($textMonthYear);
        if ($actualWidth < 70) {
            $monthWidth = 75;
        } elseif ($actualWidth < 80) {
            $monthWidth = 85;
        } elseif ($actualWidth < 90) {
            $monthWidth = 95;
        } elseif ($actualWidth < 100) {
            $monthWidth = 105;
        } else {
            $monthWidth = 115;
        }
        $Adjuested = (362 - $monthWidth);

        PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(70, 20, 'Given this', '', '', false, 0); //80
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 20, date('jS'), '', '', false, 0); //40
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 20, ' day of ', '', '', false, 0); //60
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell($monthWidth, 20, $textMonthYear, '', 'C', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell($Adjuested, 20, ' at Barangay Dona Imelda, District IV,', 'R', '', false);

        PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
        PDF::MultiCell(522, 20, 'Quezon City.', 'R', '', false);
        PDF::setFontSpacing(0); // reset

        PDF::MultiCell(532, 30, '', 'LR', 'C', false); //space

        PDF::MultiCell(266, 20, '', 'L', 'C', false, 0); //space    
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(266, 20, (isset($arr_brgyname[0]) ? $arr_brgyname[0] : ''), 'R', 'C', false);
        PDF::SetFont($font, '', 8);
        PDF::MultiCell(266, 20, '', 'L', 'C', false, 0); //space      
        PDF::MultiCell(266, 20, 'Punong Barangay', 'R', 'C', false);

        PDF::MultiCell(0, 30, "\n", 'LR', '');


        PDF::MultiCell(10, 0, '', 'L', 'L', false, 0);
        PDF::SetFont($fontbold, '', 8);
        PDF::MultiCell(522, 0, 'Control # : ' . (isset($arr_docno[0]) ? $arr_docno[0] : ''), 'R', 'L', false);

        PDF::MultiCell(0, 50, "\n", 'LR', '');


        PDF::MultiCell(532, 20, 'Not valid without official dry seal. Any alteration / erasure invalidates this business clearance.', 'LRB', 'C', false);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function liquor_layout_PDF($config, $data, $members)
    {
        $fontbold = "";
        $fontsize = "12";
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(50, 50);
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $isTimes = file_exists(database_path() . '/images/fonts/times.TTF');
            $isTimesBold = file_exists(database_path() . '/images/fonts/timesbd.TTF');
            if ($isTimes && $isTimesBold) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
                $fontsize = 13;
            }
        }
        //client
        $header = !empty($data) ? $data[0] : null;
        $bname = isset($header->bname) ? $header->bname : '';
        $ownername = isset($header->ownername) ? $header->ownername : '';
        $address = isset($header->address) ? $header->address : '';
        //brgy
        $member = !empty($members) ? $members[0] : null;
        $name = isset($member->name) ? $member->name : '';
        //fixcolumn
        $arr_bname = $this->reporter->fixcolumn([$bname], '45', 0);
        $arr_ownername = $this->reporter->fixcolumn([$ownername], '45', 0);
        $arr_address = $this->reporter->fixcolumn([$address], '45', 0);
        $arr_brgyname = $this->reporter->fixcolumn([$name], '45', 0);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::MultiCell(0, 0, date('F j, Y'), '', 'R', false);
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, 'B A R A N G A Y  C L E A R A N C E', '', 'C', false);
        PDF::MultiCell(0, 0, 'FOR LIQUOR', '', 'C', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 10, "\n", '', '');
        PDF::setFontSpacing(0.5); //extra mm per character
        PDF::MultiCell(340, 0, 'The Chairman / Executive Officer', '', '', false);
        PDF::MultiCell(340, 0, 'Liquor Licensing and Regulatory Board', '', '', false);
        PDF::MultiCell(340, 0, 'Quezon City', '', '', false);
        PDF::setFontSpacing(0); //RESET
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::MultiCell(0, 0, "\n", '', '');

        // PDF::setFontSpacing(2); //extra mm per character
        // PDF::MultiCell(522, 30, 'This is to certify that this barangay interposes no objection to the application for liquor permit of ' .(isset($arr_bname[0]) ? $arr_bname[0] : '') . ' owned by ' . (isset($arr_ownername[0]) ? $arr_ownername[0] : '')   .' whose business establishment is located at '. (isset($arr_address[0]) ? $arr_address[0] : '') .  ' this barangay provided that it complies with the required operating hours allowed by Quezon City Ordinance No. NC-85, S-89;s and that it complies with all other necessary requirements, and' , '', 'J', false);
        // PDF::setFontSpacing(0); //RESET

        PDF::setFontSpacing(1.5); //extra mm per character
        PDF::SetFont($font, '', 11);
        PDF::Write(6, 'This is to certify that this barangay interposes no objection to the application for liquor permit of ');
        PDF::SetFont($font, 'B', 11);
        PDF::Write(6, isset($arr_bname[0]) ? $arr_bname[0] : '');
        PDF::SetFont($font, '', 11);
        PDF::Write(6, ' owned by ');
        PDF::SetFont($font, 'B', 11);
        PDF::Write(6, isset($arr_ownername[0]) ? $arr_ownername[0] : '');
        PDF::SetFont($font, '', 11);
        PDF::Write(6, ' whose business establishment is located at ');
        PDF::SetFont($font, 'B', 11);
        PDF::Write(6, isset($arr_address[0]) ? $arr_address[0] : '');
        PDF::SetFont($font, '', 11);
        PDF::Write(6, ' this barangay provided that it complies with the required operating hours allowed by Quezon City Ordinance No. NC-85, S-89; and that it complies with all other necessary requirements, and');
        PDF::setFontSpacing(0); //RESET


        PDF::MultiCell(0, 30, "\n", '', '');
        PDF::MultiCell(532, 0, 'This is to certify further that:', '', '', false);
        PDF::setFontSpacing(2); //extra mm per character
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::MultiCell(492, 6, '1. This business establishment is more than 50 meters away from an', '', 'J', false);
        PDF::setFontSpacing(0.6); //extra mm per character
        PDF::MultiCell(492, 6, '       academic school. ( Sec. 17, Ord. 85 )', '', '', false);
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::setFontSpacing(0.8); //extra mm per character
        PDF::MultiCell(492, 6, '2. This business establishment is not erected on a public sidewalk, street,', '', 'J', false);
        PDF::setFontSpacing(0.6); //extra mm per character
        PDF::MultiCell(492, 6, '        avenue, park or plaza on government property ( Sec. 17, Ord. 85 )', '', '', false);
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::setFontSpacing(1); //extra mm per character
        PDF::MultiCell(492, 6, '3. This business establishment is not a nuisance to public safety and', '', 'J', false);
        PDF::MultiCell(492, 6, '       order.', '', '', false);
        PDF::setFontSpacing(0); //RESET

        PDF::MultiCell(0, 10, "\n", '', '');
        PDF::MultiCell(532, 6, '', '', '', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(266, 0, '', '', '', false, 0);
        PDF::MultiCell(266, 0, (isset($arr_brgyname[0]) ? $arr_brgyname[0] : ''), '', 'C', false);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(266, 0, '', '', '', false, 0);
        PDF::MultiCell(266, 0, 'Punong Barangay', '', 'C', false);
        PDF::MultiCell(0, 270, "\n", '', '');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 0, '* * * Not valid without official seal * * *', '', 'C', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
