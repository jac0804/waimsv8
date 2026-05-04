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

class bt
{

    private $modulename = "TRU Clearance";
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
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
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
            'default' as reporttype
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
        select client.client as docno,client.clientname, client.addr, client.make, client.motorno, client.color, client.plateno, info.chassisno, info.sidecarno,
        case when la.bonafideid <> 0 then req.description when gl.bonafideid <> 0 then req.description else 0 end as `bonafideid`, tru.description as `tru`
        from client
        left join clientinfo as info on info.clientid = client.clientid
        left join lahead as la on la.client = client.client
        left join glhead as gl on gl.clientid = client.clientid
        left join reqcategory as req on req.line = la.bonafideid or req.line = gl.bonafideid
        left join reqcategory as tru on tru.line = la.truid or tru.line = gl.truid
        where client.istru = 1;
        ";

        return $this->coreFunctions->opentable($query);
    }

    public function report_members_query($config) //brgy officials
    {
        $query = "
        select category as name, position  
        from reqcategory
        where isbrgyoff = 1
        ";
        $brgy = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $brgy;
    } //end fn



    public function reportplotting($config, $data)
    {
        $data = $this->generateResult($config);
        $members = $this->report_members_query($config);
        $str = $this->default_default_PDF($config, $data, $members);
        return $str;
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
        

        $font = "";
        $fontbody = "";
        $fontbold = "";
        $fontsize = "";
        $fontsize2 = "";
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
        PDF::AddPage('p', 'LEGAL');
        PDF::SetMargins(40, 40);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(0, 0, '', '', 'L'); //$reporttimestamp,
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(0, 10, '', 'TLR', '');
        PDF::MultiCell(0, 0, 'Republika ng Pilipinas', 'LR', 'C', false);
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), 'LR', 'C');
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) , 'LR', 'C');
        PDF::MultiCell(0, 0,'TeleFax: '. strtoupper($headerdata[0]->tel), 'LR', 'C');
        if (file_exists($logo1)) { //temp logo
            PDF::Image($logo1, 100, 30, 50,50);
        }
        if (file_exists($logo2)) { //temp logo
            PDF::Image($logo2, 460, 30, 50,50);
        }
        PDF::MultiCell(0, 0, "\n", 'LR', '');

        PDF::SetFont($fonttimes, '', $fontsize);

        PDF::MultiCell(0, 0, 'OFFICE OF THE BARANGAY CAPTAIN', 'LR', 'C', false);
        PDF::MultiCell(0, 0, "\n", 'LR', '');
        PDF::SetFont($fonttimesbold, 'U', 16);
        PDF::SetFont($font, '', 5);
    }

    public function default_default_PDF($config, $data, $members)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $count = 55;
        $page = 54;
        $border = "1px solid ";
        
        $fontbold = "";
        $fontbitalic = "";
        $fontitalic = "";
        $fonttimes = "";
        $fonttimesbold = "";
        $fontsize = "11";
        $fontsize2 = "9";
        $fontsize3 = "8";
        $fontbody = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontbitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
                $isTimes = file_exists(database_path() . '/images/fonts/times.TTF');
                $isTimesBold = file_exists(database_path() . '/images/fonts/timesbd.TTF');
                if ($isTimes && $isTimesBold) {
                    $fonttimes = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
                    $fonttimesbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
                    $fontbody = 8;
                    $fontsize = 9;
                    $fontsize2 = 7;
                    $lowerlogo = 462;
                } else {
                    $fonttimes = $font; 
                    $fonttimesbold = $fontbold; 
                    $fontbody = 7;
                    $fontsize = 8;
                    $fontsize2 = 7;
                    $lowerlogo = 455;
                }
        }
        
        $this->default_PDF_header($config, $data);
        $brgyimg = public_path('images/barangay/3.jpg'); //temp image

        $color   = array(36, 59, 117);      // text color
        $setfill = array(179, 226, 255);    // fill color 
        $setfillB = array(0, 129, 194);  // left panel

         //client info
        $header = !empty($data) ? $data[0] : null;
        $docno = isset($header->docno) ? $header->docno: '';
        $clientname = isset($header->clientname) ? $header->clientname : '';
        $address = isset($header->addr) ? $header->addr : '';
        $make = isset($header->make) ? $header->make: '';
        $motorno = isset($header->motorno) ? $header->motorno: '';
        $chassisno = isset($header->chassisno) ? $header->chassisno : '';
        $colorno = isset($header->color)? $header->color: '';
        $sidecarno = isset($header->sidecarno)? $header->sidecarno : '';
        $plateno = isset($header->plateno)? $header->plateno : '';
        $bon = isset($header->bonafideid) ? $header->bonafideid : '';
        $tru = isset($header->tru) ? $header->tru : '';

        //brgy members
        $punongBarangay = '';
        $secretary = '';
        $treasurer = '';
        $kagawadList = [];
        foreach ($members as $m) {
            $pos = strtoupper($m['position']);
            if ($pos === 'PUNONG BARANGAY') {
                $punongBarangay = $m['name'];
            } elseif ($pos === 'BARANGAY SECRETARY') {
                $secretary = $m['name'];
            } elseif ($pos === 'BARANGAY TREASURER') {
                $treasurer = $m['name'];
            } elseif (strpos($pos, 'KAGAWAD') === 0) { //my sample consists of 'KAGAWAD-x' (x = number)
                $kagawadList[] = $m['name'];
            }
        }
        //fixcolumn
        $arr_clientname = $this->reporter->fixcolumn([$clientname], '45', 0);
        $arr_docno = $this->reporter->fixcolumn([$docno], '45', 0);
        $arr_addr = $this->reporter->fixcolumn([$address],'45', 0);
        $arr_make = $this->reporter->fixcolumn([$make],'45', 0);
        $arr_motorno = $this->reporter->fixcolumn([$motorno],'45', 0);
        $arr_chassisno = $this->reporter->fixcolumn([$chassisno],'45', 0);
        $arr_colorno = $this->reporter->fixcolumn([$colorno],'45', 0);
        $arr_sidecarno = $this->reporter->fixcolumn([$sidecarno],'45', 0);
        $arr_plateno = $this->reporter->fixcolumn([$plateno],'45', 0);
        $arr_bon = $this->reporter->fixcolumn([$bon],'45', 0);
        $arr_tru = $this->reporter->fixcolumn([$tru],'45', 0);
        $arr_punongBarangay = $this->reporter->fixcolumn([$punongBarangay],'30', 0);
        $arr_secretary  = $this->reporter->fixcolumn([$secretary],'30', 0);
        $arr_treasurer = $this->reporter->fixcolumn([$treasurer],'30', 0);
        for ($k = 0; $k < 7; $k++) {
            $name = isset($kagawadList[$k]) ? $kagawadList[$k] : '';
            ${'arr_kagawad' . $k} = $this->reporter->fixcolumn([$name], '30', 0);
        } 
        
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetDrawColor(25, 119, 181);    
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 0, '', 'TLR', 'C', true, 0);
        PDF::MultiCell(5, 0, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 0, '', 'TL', '', true, 0);
        PDF::MultiCell(377, 0, '', 'TR', '', true,0);  //17
        PDF::MultiCell(5, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 0, (isset($arr_punongBarangay[0]) ? $arr_punongBarangay[0] : ''), 'LR', 'C', true, 0); 
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(5, 0, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 0, '', 'L', '', true, 0);
        PDF::SetFont($fonttimes, '', $fontsize);
        PDF::MultiCell(237, 0, 'The Chief', '', '', true,0);
        PDF::SetTextColor(0, 18, 77);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, 'CONTROL #:', '', '', true, 0);
        PDF::MultiCell(80, 0, (isset($arr_docno[0]) ? $arr_docno[0] : ''), 'R', '', true, 0);
        PDF::MultiCell(5, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 20, 'Punong Barangay', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 0, '', '', '', false, 0);  
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 0, '', 'L', '', true, 0);
        PDF::SetFont($fonttimes, '', $fontsize);
        PDF::MultiCell(267, 0, 'Tricycle Regulation Unit', '', '', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(50, 0, 'TUnit ID : ', '', 'R', true, 0);
        PDF::MultiCell(55, 0, (isset($arr_tru[0]) ? $arr_tru[0] : ''), '', 'R', true, 0);
        PDF::MultiCell(5, 0, '', 'R', 'R', true, 0);
        PDF::MultiCell(5, 0, '', 'R', '', false);

        PDF::SetTextColor(0, 0, 0);
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(140, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($fonttimes, '', $fontsize);
        PDF::MultiCell(5, 0, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 0, '', 'L', '', true, 0);
        PDF::MultiCell(237, 0, 'Quezon City', '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(60, 0, ' ', '', '', true, 0);
        PDF::MultiCell(80, 0, '', 'R', '', true, 0);
        PDF::MultiCell(5, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(140, 0, '', 'LR', 'C', true, 0); 
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(5, 0, '', '', '', false, 0); // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(15, 0, '', 'L', '', true, 0);
        PDF::MultiCell(237, 0, '', '', '', true, 0);
        PDF::MultiCell(60, 0, '', '', '', true, 0);
        PDF::MultiCell(70, 0, '', 'R', '', true,0);
        PDF::MultiCell(5, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, 'U', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, 'MGA KAGAWAD', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 15, 0);
        PDF::SetFont($fontitalic, '', $fontsize);
        PDF::MultiCell(5, 15, '', '', '',false, 0); // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 15, '', 'L', '', true, 0);
        PDF::MultiCell(237, 15, '', '', '', true, 0);  
        PDF::MultiCell(5, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(125, 15, '', '', '', true, 0);
        PDF::MultiCell(5, 15, '', '', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', '', true, 0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '',false, 0); // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 15, '', 'L', '', true, 0);
        PDF::MultiCell(100, 15, 'This is to certify that', '', '', true, 0); 
        PDF::SetFont($fontbold, 'U', $fontbody);
        PDF::MultiCell(262, 15, (isset($arr_clientname[0]) ? $arr_clientname[0] : ''), '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(5, 15, '', '', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', '', true, 0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255); 
        PDF::MultiCell(140, 15, (isset($arr_kagawad0[0]) ? $arr_kagawad0[0] : ''), 'LR', 'C', true, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0, 0, 0); 
        PDF::MultiCell(100, 15, 'with postal address at', '', '', true, 0);
        PDF::SetFont($font, 'U', $fontbody);
        PDF::MultiCell(262, 15, (isset($arr_addr[0]) ? $arr_addr[0] : ''), '', '', true, 0);
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(5, 15, '', '', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', '', true, 0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::MultiCell(140, 15, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 15, '', 'L', '', true, 0);
        PDF::MultiCell(100, 15, 'is a bonafide member/s of ', '', '', true, 0);
        PDF::SetFont($fontbold, 'U', $fontbody);
        PDF::MultiCell(262, 15, (isset($arr_bon[0]) ? $arr_bon[0] : ''), '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(10, 15, '', '', '', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', '', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, (isset($arr_kagawad1[0]) ? $arr_kagawad1[0] : ''), 'LR', 'C', true, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(237, 15, 'as an operator/s of a tricycle unit below', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(125, 15, '', '', '', true, 0);
         PDF::MultiCell(10, 15, '', '', '', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', '', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 15, '', 'L', '', true, 0);
        PDF::MultiCell(237, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(135, 15, '', '', '', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', '', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, (isset($arr_kagawad2[0]) ? $arr_kagawad2[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(367, 15, '               This office interposes no objection to the application for franchise (New / Renewal) of the', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', '', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(367, 15, 'application to the operate PUBLIC MOTORIZED TRICYCLE, herein described, within the Barangay,', '', '', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'R', '', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetTextColor(255, 255, 255);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 15, (isset($arr_kagawad3[0]) ? $arr_kagawad3[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(367, 15, 'subject to the provisions of the Quezon City Tricycle Ordinance of 1992 and its Implementing', '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(5, 15, '', 'R', '', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetTextColor(255, 255, 255);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 15, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(367, 15, 'Guidelines.', '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(5, 15, '', 'R', '', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, (isset($arr_kagawad4[0]) ? $arr_kagawad4[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(367, 15, '', '', '', true, 0); 
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', 'C', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false); 

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, '' , 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(20, 15, '', 'L', '', true, 0);
        PDF::MultiCell(40, 15, '', '', '', true, 0);
        PDF::MultiCell(60, 15, 'Make :', '', '', true, 0); 
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(98.5, 15, (isset($arr_make[0]) ? $arr_make[0] : ''), '', '', true, 0);  //var
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(60, 15, 'Color Code:', '', '', true, 0); 
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(98.5, 15, (isset($arr_colorno[0]) ? $arr_colorno[0] : ''), '', '', true, 0);  //var
        PDF::MultiCell(5, 15, '', 'R', '', true, 0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, (isset($arr_kagawad5[0]) ? $arr_kagawad5[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(20, 15, '', 'L', '', true, 0);
        PDF::MultiCell(40, 15, '', '', '', true, 0);
        PDF::MultiCell(60, 15, 'Motor No :', '', '', true, 0); 
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(98.5, 15, (isset($arr_motorno[0]) ? $arr_motorno[0] : ''), '', '', true, 0); //var
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(60, 15, 'Side Car No:', '', '', true, 0); 
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(98.5, 15, (isset($arr_sidecarno[0]) ? $arr_sidecarno[0] : ''), '', '', true, 0); //var
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', 'C', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false); 

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(20, 15, '', 'L', '', true, 0);
        PDF::MultiCell(40, 15, '', '', '', true, 0);
        PDF::MultiCell(60, 15, 'Chassis No :', '', '', true, 0); 
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(98.5, 15, (isset($arr_chassisno[0]) ? $arr_chassisno[0] : ''), '', '', true, 0); //var
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(60, 15, 'Plate No:', '', '', true, 0); 
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(98.5, 15, (isset($arr_plateno[0]) ? $arr_plateno[0] : ''), '', '', true, 0); //var
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(5, 15, '', 'R', 'C', true,0);
        PDF::MultiCell(5, 15, '', 'R', '', false); 

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, (isset($arr_kagawad6[0]) ? $arr_kagawad6[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(5, 15, '', '', '', false, 0); //true
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(237, 15, '', '', '', true, 0);
        PDF::MultiCell(130, 15, '', '', '', true, 0);
        PDF::MultiCell(5, 15, '', 'R', '', true, 0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 20, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(5, 20, '', '', '', false, 0); //true
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        // PDF::MultiCell(370, 20, 'Issued this '. date('jS') .' day of '. date('F'). ' ' . date('Y') .' Quezon City, Metro Manila', '', '', true, 0);
        $textMonthYear = date('F') . ' ' . date('Y'); //Month Year
        $actualWidth = PDF::GetStringWidth($textMonthYear);
        if ($actualWidth < 55) {
            $monthWidth = 55;
        }elseif ($actualWidth < 65) {
            $monthWidth = 65;
        } elseif ($actualWidth < 75) {
            $monthWidth = 75;
        } elseif ($actualWidth < 85) {
            $monthWidth = 85;
        } else {
            $monthWidth = 90; 
        }
        $Adjuested = (249 - $monthWidth); 

        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(50, 20, 'Issued this ', '', '', true, 0); 
        PDF::SetFont($fontbold, 'U', $fontbody);
        PDF::MultiCell(28, 20, date('jS') , '', '', true, 0); //Day
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(40, 20, ' day of ', '', '', true, 0);
        PDF::SetFont($fontbold, 'U', $fontbody); 
        PDF::MultiCell($monthWidth, 20, $textMonthYear, '', '', true, 0);//MonthYear
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell($Adjuested, 20,' Quezon City, Metro Manila', '', '', true, 0);
        PDF::MultiCell(5, 20, '', 'R', '', true, 0);
        PDF::MultiCell(5, 20, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 0, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimes, '', $fontbody);
        PDF::MultiCell(5, 0, '', '', '', false, 0); //true
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::MultiCell(100, 0, 'Signature of the Bearer: ', '', '', true, 0);
        PDF::MultiCell(5, 0, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(100, 0, '', 'B', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(162, 0, '', '', '', true, 0);
        PDF::MultiCell(5, 0, '', 'R', '', true, 0);
        PDF::MultiCell(5, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 0, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(5, 0, '', '', '', false, 0); //true
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(377, 0, '', 'LB', '', true, 0);
        PDF::MultiCell(5, 0, '', 'BR', '', true, 0);
        PDF::MultiCell(5, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, (isset($arr_secretary[0]) ? $arr_secretary[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($font, '', $fontsize2);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(237, 15, 'Tax Cert :', '', '', false, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(140, 15, '', '', '', false, 0);
        PDF::MultiCell(5, 15, '', '', '', false, 0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 15, 'Barangay Secretary', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(5, 15, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(237, 15, 'Date Issued :', '', '', false, 0);
        PDF::MultiCell(140, 15, '', '', '', false, 0);
        PDF::MultiCell(5, 15, '', '', '', false, 0);
        PDF::MultiCell(5, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::MultiCell(140, 15, '', 'LR', 'C', true, 0);
        PDF::MultiCell(5, 15, '', '', '', false, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(187, 15, 'Issued at :', '', '', false, 0);
        PDF::SetFont($fontbold, 'R', $fontsize2);
        PDF::MultiCell(140, 15, (isset($arr_punongBarangay[0]) ? $arr_punongBarangay[0] : ''), '', 'C', false, 0); 
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::MultiCell(10, 15, '', 'R', false);
        
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetTextColor(255, 255, 255);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 0, (isset($arr_treasurer[0]) ? $arr_treasurer[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(5, 0, '', '', '', false, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::SetTextColor(0, 18, 77);
        PDF::MultiCell(187, 0, 'Note: Not valid Without Barangay Seal', '', '', false, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(140, 0, 'Punong Barangay', '', 'C', false, 0);
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::MultiCell(10, 0, '', 'R', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(140, 0, 'Barangay Treasure', 'R', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(5, 0, '', '', '', false, 0);
        PDF::MultiCell(5, 0, '', '', '', false, 0);
        PDF::MultiCell(382, 0, '', 'R', 'C', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(140, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(392, 0, '', 'BR', 'C', false);

        //temp image
        if (file_exists($brgyimg)) {
            PDF::Image($brgyimg, 500, $lowerlogo , 50, 50);
        }
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
