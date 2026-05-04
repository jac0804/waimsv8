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

class ju
{

    private $modulename = "Judiciary Complaint";
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
            ['label' => 'Complaints Printout', 'value' => '0', 'color' => 'red'],
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
            '' as approved
            "
        );
    }

    public function generateResult($config) // query
    {  
        $query = "
        select head.trno as brgy,
        head.docno,date(head.dateid) as dateid,head.clientname as complainant,head.address as complainant_address,head.contact as complainant_contact,head.ownername as respondent,
        head.owneraddr as respondent_address,head.orderno as respondent_contact,head.bstype as time,head.ourref as entered_by,
        head.crno as `for`,head.conaddr as tdpo,head.creditinfo as facts
        from lahead as head
        left join  cntnum as num on num.trno = head.trno
        where num.doc = 'JU' 
        union all
        select head.trno as brgy,
        head.docno,date(head.dateid) as dateid,head.clientname as complainant,head.address as complainant_address,head.contact as complainant_contact,
        head.ownername as respondent,head.owneraddr as respondent_address,head.orderno as respondent_contact,head.bstype as time,
        head.ourref as entered_by,head.crno as `for`,head.conaddr as tdpo,head.creditinfo as facts
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        where num.doc = 'JU' 
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
        $data = $this->generateResult($config);
        $members = $this->report_members_query($config);
        $str = $this->default_layout_PDF($config, $data, $members);
        return $str;
    }

    public function default_PDF_header($config, $data)
    {
        $center = $config['params']['center'];
        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        //temp logos
        $logo1 = public_path('images/barangay/1.jpg');
        $logo2 = public_path('images/barangay/2.jpg');

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
                } else {
                    $fonttimes = $font; 
                    $fonttimesbold = $fontbold; 
                    $fontbody = 8;
                    $fontsize = 11;
                }
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(20, 20);
        PDF::SetAlpha(1);
        //532 
        PDF::MultiCell(0, 0, '', '', ''); 
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::MultiCell(0, 15, 'NATIONAL CAPITAL REGION', '', 'C', false);
        PDF::MultiCell(0, 15, 'QUEZON CITY', '', 'C', false);
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(0,15, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(45, 15, '', '', '', false, 0);
        PDF::MultiCell(80, 15, 'Hawak Kamay para sa Kaunlaran', '', 'C', false, 0);
        PDF::MultiCell(45, 15, '', '', '', false, 0);
        PDF::MultiCell(232, 15, strtoupper($headerdata[0]->address), '', 'C', false,0);
        PDF::MultiCell(45, 15, '', '', '', false, 0);
        PDF::MultiCell(80, 15, '     Q.C ITO       Tulong-Tulong Tayo', '', 'C',false,0);
        PDF::MultiCell(45, 15, '', '', '', false);
        if (file_exists($logo2)) { //temp logo
            PDF::Image($logo2, 80,20, 60, 60);
        }
        if (file_exists($logo1)) { //temp logo
            PDF::Image($logo1, 480,20, 60, 60);
        }
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(0, 0, 'OFFICE OF THE BARANGAY CAPTAIN', '', 'C', false);
        PDF::MultiCell(0, 0, "\n", '', '');
    }
    public function default_layout_PDF($config, $data, $members)
    {
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontsize = 11;
            $fontsub = 9;
        }  
        $this->default_PDF_header($config, $data);
        //client
        $header = !empty($data) ? $data[0] : null;
        $brgy = isset($header->brgy) ? (string)$header->brgy : ''; //into -> string
        $dateid = isset($header->dateid) ? $header->dateid : '';
            $formattedDate = !empty($dateid)  ? date('m-d-Y', strtotime($dateid)) : '';
        $complainant = isset($header->complainant) ? $header->complainant : '';
        $complainant_address = isset($header->complainant_address) ? $header->complainant_address : '';
        $complainant_contact = isset($header->complainant_contact) ? $header->complainant_contact : '';
        $respondent = isset($header->respondent) ? $header->respondent : '';
        $respondent_address = isset($header->respondent_address) ? $header->respondent_address : '';
        $respondent_contact = isset($header->respondent_contact) ? $header->respondent_contact : '';
        $entered_by = isset($header->entered_by) ? $header->entered_by : '';
        $for = isset($header->for) ? $header->for : '';
        $facts = isset($header->facts) ? $header->facts : '';
        //brgy
        $member = !empty($members) ? $members[0] : null;
        $name = isset($member->name) ? $member->name : '';

        //fixcolumn
        $arr_brgy = $this->reporter->fixcolumn([$brgy], '45', 0);
        $arr_complainant = $this->reporter->fixcolumn([$complainant], '45', 0);
        $arr_complainant_address = $this->reporter->fixcolumn([$complainant_address], '45', 0);
        $arr_complainant_contact = $this->reporter->fixcolumn([$complainant_contact], '45', 0);
        $arr_respondent = $this->reporter->fixcolumn([$respondent], '45', 0);
        $arr_respondent_address = $this->reporter->fixcolumn([$respondent_address], '45', 0);
        $arr_respondent_contact = $this->reporter->fixcolumn([$respondent_contact], '45', 0);
        $arr_entered_by = $this->reporter->fixcolumn([$entered_by], '45', 0);
        $arr_for = $this->reporter->fixcolumn([$for], '45', 0);
        $arr_facts = $this->reporter->fixcolumn([$facts], '700', 0);

        $arr_brgyname = $this->reporter->fixcolumn([$name], '45', 0);
        //572

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 15, (isset($arr_complainant[0]) ? $arr_complainant[0] : ''), 'B', 'L', false, 0);// variable
        PDF::MultiCell(212, 15, '', '', 'L', false, 0); //212
        PDF::SetFont($font, '', $fontsize); 
        PDF::MultiCell(70, 15, 'Brgy Case No:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 15, (isset($arr_brgy[0]) ? $arr_brgy[0] : ''), 'B', 'C', false);// variable

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 15, (isset($arr_complainant_address[0]) ? $arr_complainant_address[0] : ''), 'B', 'L', false);// variable
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 15, (isset($arr_complainant_contact[0]) ? $arr_complainant_contact[0] : ''), 'B', 'L', false);// variable

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(150, 15, 'Complaint/s', '', 'L', false, 0);
        PDF::MultiCell(212, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 15, 'For:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 15, (isset($arr_for[0]) ? $arr_for[0] : ''), 'B', 'L', false);//variable
        PDF::MultiCell(0, 15, "\n", '', '');

        PDF::MultiCell(150, 15, '-against-', '', 'C', false); 
        PDF::MultiCell(0, 15, "\n", '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 15, (isset($arr_respondent[0]) ? $arr_respondent[0] : ''), 'B', 'L', false, 0);// variable
        PDF::MultiCell(212, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 15, 'Desk Officer:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 15, (isset($arr_entered_by[0]) ? $arr_entered_by[0] : ''), 'B', 'L', false);//variable
        PDF::MultiCell(150, 15, (isset($arr_respondent_address[0]) ? $arr_respondent_address[0] : ''), 'B', 'L', false);// variable
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 15, (isset($arr_respondent_contact[0]) ? $arr_respondent_contact[0] : ''), 'B', 'L', false);// variable

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(150, 15, 'Respondents/s', '', 'L', false, 0);
        PDF::MultiCell(212, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 15, 'Date:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 15, $formattedDate, 'B', 'L', false);//variable
        PDF::MultiCell(0, 15, "\n\n", '', '');

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(0, 15, 'C O M P L A I N T / R E K L A M O', '', 'C', false);
        PDF::MultiCell(0, 15, "\n", '', '');

        PDF::SetFont($font, '', $fontsize); 
        //variable for the 'complaint'
        $paragraph = isset($arr_facts[0]) ? $arr_facts[0] : '';
        $lines = PDF::getNumLines($paragraph, 0); // number of printed rows

        $wrapped = explode("\n", wordwrap($paragraph, 85, "\n", true));
        $counted = count($wrapped);

        $counting = 0;

        foreach ($wrapped as $line) {
            $counting++;
            $align = ($counting == $counted) ? 'L' : 'J';
            PDF::MultiCell(0, 15, $line, 'B', $align, false);
        }

        for ($i = $lines; $i < 10; $i++) {
            PDF::MultiCell(0, 15, '', 'B', 'L', false);
        }

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(0, 15, '* Additonal sheet /s may be used, if necessary', '', 'L', false);
        PDF::MultiCell(0, 15, "\n\n\n\n", '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(261, 0, (isset($arr_complainant[0]) ? $arr_complainant[0] : ''), 'B', 'L', false, 0); //variable
        PDF::MultiCell(212, 15, '', '', 'L', false);
        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(261, 0, 'Complainant/s', '', 'L', false, 0); //variable
        PDF::MultiCell(212, 15, '', '', 'L', false);
        PDF::MultiCell(0, 15, "\n\n\n\n", '', '');


        PDF::MultiCell(170, 0, '', '', 'L', false, 0); 
        PDF::MultiCell(170, 0, '', '', 'L', false, 0); 
        PDF::MultiCell(232, 0, 'Conformed', '', 'L', false); //variable
        PDF::MultiCell(0, 15, "\n\n", '', '');

        PDF::MultiCell(170, 0, '', '', 'L', false, 0); 
        PDF::MultiCell(170, 0, '', '', 'L', false, 0); 
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(232, 0, (isset($arr_brgyname[0]) ? $arr_brgyname[0] : ''), 'B', 'C', false); //variable
        PDF::MultiCell(170, 0, '', '', 'L', false, 0); 
        PDF::MultiCell(170, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(232, 0, 'Punong Barangay', ' ', 'C', false); //variable

        return PDF::Output($this->modulename . '.pdf', 'S');
    }



}
