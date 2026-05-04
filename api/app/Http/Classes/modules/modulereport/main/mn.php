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

class mn
{

    private $modulename = "Judiciary Summons";
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
            ['label' => 'Notice of Hearing', 'value' => '0', 'color' => 'red'],
            ['label' => 'Summons Print 1', 'value' => '1', 'color' => 'red'],
            ['label' => 'Summons Print 2', 'value' => '2', 'color' => 'red'],
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
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $query = "
        select dateid as summondate, date(head.createdate) as datecomp, head.docno, head.clientname as cname, head.address as caddr, head.contact as cno, head.ownername as rname, head.owneraddr as raddr,
        head.orderno as rno, head.ourref as enteredby, head.conaddr as tpdo, head.creditinfo as facts, head.crno as reason, head.layref as brgyid
        from lahead as head
        left join client on client.client = head.client
        where docno like 'MN%'
        union all
        select dateid as summondate, date(head.createdate) as datecomp, head.docno, head.clientname as cname, head.address as caddr , head.contact as cno,  head.ownername as rname, head.owneraddr as raddr,
        head.orderno as rno,  head.ourref as enteredby, head.conaddr as tpdo, head.creditinfo as facts, head.crno as reason, head.layref as brgyid
        from glhead as head
        left join client on client.clientid = head.clientid
        where docno like 'MN%';
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
        switch ($config['params']['dataparams']['reporttype']) {
        case '0':
        return $this->notice_layout_PDF($config, $data,$members);
        break;
        case '1':
        return $this->summons1_layout_PDF($config, $data,$members);
        break;
        case '2':
        return $this->summons2_layout_PDF($config, $data,$members);
        break;
        } 
    }

    public function default_PDF_header($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        //temp logos
        $path1 = public_path('images/barangay/1.jpg');
        $path2 = public_path('images/barangay/2.jpg');

        if (file_exists($path1)) {
            $logo1 = $path1;
        } else {
            $logo1 = null; 
        }

        if (file_exists($path2)) {
            $logo2 = $path2;
        } else {
            $logo2 = null; 
        }
        
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
        if ($reporttype == '0') {
            PDF::AddPage('p', 'LETTER');
        } else {
            PDF::AddPage('p', 'LEGAL');
        } 
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
        if ($reporttype == '1') {
        PDF::MultiCell(0, 0, "\n", '', '');
        }else {
        PDF::MultiCell(0, 0, "\n\n\n", '', '');
        }
        PDF::SetFont($fonttimesbold, '', 18);
        if ($reporttype == '0') {
        PDF::MultiCell(0, 0, 'NOTICE OF HEARING', '', 'C', false);
        PDF::MultiCell(0, 0, '(MEDIATION PROCEEDINGS)', '', 'C', false);
        PDF::MultiCell(0, 0, "\n\n\n", '', '');
        }
    }

    public function notice_PDF_header($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        //temp logos
        $path1 = public_path('images/barangay/1.jpg');
        $path2 = public_path('images/barangay/2.jpg');

        if (file_exists($path1)) {
            $logo1 = $path1;
        } else {
            $logo1 = null;
        }

        if (file_exists($path2)) {
            $logo2 = $path2;
        } else {
            $logo2 = null;
        }
        
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
        PDF::AddPage('p', 'LEGAL');
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
        if ($reporttype == '1') {
        PDF::MultiCell(0, 0, "\n", '', '');
        }else {
        PDF::MultiCell(0, 0, "\n\n\n", '', '');
        }
        PDF::SetFont($fonttimesbold, '', 18);
        if ($reporttype == '0') {
        PDF::MultiCell(0, 0, 'NOTICE OF HEARING', '', 'C', false);
        PDF::MultiCell(0, 0, '(MEDIATION PROCEEDINGS)', '', 'C', false);
        PDF::MultiCell(0, 0, "\n\n\n", '', '');
        }
    }
    public function notice_layout_PDF($config, $data, $members)
    {
        $center = $config['params']['center'];
        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontsize = 11;
            $fontsub = 9;
        }  
        $this->default_PDF_header($config, $data);
        //client
        $header = !empty($data) ? $data[0] : null;
        $rname = isset($header->rname) ? $header->rname : '';
        $rno = isset($header->rno) ? $header->rno : '';
        $raddr = isset($header->raddr) ? $header->raddr : '';
        
        //brgy
        $member = !empty($members) ? $members[0] : null;
        $name = isset($member->name) ? $member->name : '';

        //fixcolumn
        $arr_rname = $this->reporter->fixcolumn([$rname], '45', 0);
        $arr_rno = $this->reporter->fixcolumn([$rno], '45', 0);
        $arr_raddr = $this->reporter->fixcolumn([$raddr], '45', 0);
        $arr_brgyname = $this->reporter->fixcolumn([$name], '45', 0);
        //572

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(30, 15, 'TO: ', '', 'L', false, 0);
        PDF::MultiCell(150, 15, (isset($arr_rname[0]) ? $arr_rname[0] : ''), 'B', 'L', false); //name
        PDF::MultiCell(30, 15, '', '', 'L', false, 0);
        PDF::MultiCell(150, 15, (isset($arr_raddr[0]) ? $arr_raddr[0] : ''), 'B', 'L', false); //Adrress
        PDF::MultiCell(30, 15, '', '', 'L', false, 0);
        PDF::MultiCell(150, 15, (isset($arr_rno[0]) ? $arr_rno[0] : ''), 'B', 'L', false); //Contact
        PDF::MultiCell(30, 15, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsub);
        PDF::MultiCell(150, 15, 'Complaint/s', '', 'L', false); //Contact
        PDF::MultiCell(0, 0, "\n\n\n\n\n", '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 15, '', '', 'J', false, 0);
        PDF::MultiCell(362, 15, 'You are hereby required to appear before me on the ', '', 'J', false, 0);
        PDF::MultiCell(75, 15, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(50, 15, ' day of ', '', 'J', false);

        PDF::MultiCell(70, 15, '','B', 'C', false, 0);
        PDF::MultiCell(25, 15, ',  20','', 'L', false, 0);
        PDF::MultiCell(40, 15, '','B', 'L', false, 0);
        PDF::MultiCell(20, 15, ' at ','', 'L', false, 0);
        PDF::MultiCell(120, 15, '','B', 'C', false, 0);
        PDF::MultiCell(200, 15, ' for the hearing of complaint.', 0, 'J', false,0);
        PDF::MultiCell(30, 15, '', 0, 'J', false);

        PDF::MultiCell(0, 0, "\n\n", '', '');
        PDF::MultiCell(80, 15, '', '', 'L', false, 0);
        PDF::MultiCell(30, 15, 'This ', '', 'J', false, 0);
        PDF::MultiCell(120, 15, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(50, 15, ' day of ', '', 'J', false, 0);
        PDF::MultiCell(120, 15, '','B', 'C', false, 0);
        PDF::MultiCell(25, 15, ',  20','', 'L', false, 0);
        PDF::MultiCell(120, 15, '','B', 'L', false, 0);
        PDF::MultiCell(150, 15, ' at', 0, 'L', false);
        PDF::SetFontSpacing(1);
        PDF::MultiCell(0, 15, strtoupper($headerdata[0]->address).'.', '', '', false);//address var
        PDF::SetFontSpacing(0);
        //'Barangay Dona Imelda Quezon City, Metro Manila.'

        PDF::MultiCell(0, 0, "\n\n\n\n\n", '', '');
        PDF::MultiCell(0, 15, (isset($arr_brgyname[0]) ? $arr_brgyname[0] : ''), '', 'C', false); //Pungong Barangay
        PDF::SetFont($fontbold, '', $fontsub);
        PDF::MultiCell(0, 15, 'Punong Barangay ', '', 'C', false);
        PDF::MultiCell(0, 0, "\n\n\n\n", '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(88.5, 15, '', '', 'C', false, 0);
        PDF::MultiCell(80, 15, 'Notified this', '', 'C', false, 0);
        PDF::MultiCell(80, 15, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(50, 15, ' day of ', '', 'J', false, 0);
        PDF::MultiCell(80, 15, '','B', 'C', false, 0);
        PDF::MultiCell(25, 15, ',  20','', 'L', false, 0);
        PDF::MultiCell(80, 15, '','B', 'L', false,0);
        PDF::MultiCell(10, 15, '.','', 'L', false,0);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function summons1_layout_PDF($config, $data, $members)
    {

        $center = $config['params']['center'];
        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontsize = 10;
            $fontbody = 9;
            $fontsub = 8;
        }  
       
        $this->default_PDF_header($config, $data);
        //client
        $header = !empty($data) ? $data[0] : null;
        $brgyid = isset($header->brgyid) ? $header->brgyid : '';
        $cname = isset($header->cname) ? $header->cname : '';
        $caddr = isset($header->caddr) ? $header->caddr : '';
        $cno = isset($header->cno) ? $header->cno : '';
        $rname = isset($header->rname) ? $header->rname : '';
        $raddr = isset($header->raddr) ? $header->raddr : '';
        $rno = isset($header->rno) ? $header->rno : '';

        $enteredby = isset($header->enteredby) ? $header->enteredby : '';
        $reason = isset($header->reason) ? $header->reason : '';

        //brgy
        $member = !empty($members) ? $members[0] : null;
        $name = isset($member->name) ? $member->name : '';

        //fixcolumn
        $arr_brgyid = $this->reporter->fixcolumn([$brgyid], '45', 0);
        $arr_cname = $this->reporter->fixcolumn([$cname], '45', 0);
        $arr_caddr = $this->reporter->fixcolumn([$caddr], '45', 0);
        $arr_cno = $this->reporter->fixcolumn([$cno], '45', 0);
        $arr_rname = $this->reporter->fixcolumn([$rname], '45', 0);
        $arr_raddr = $this->reporter->fixcolumn([$raddr], '45', 0);
        $arr_rno = $this->reporter->fixcolumn([$rno], '45', 0);
        $arr_enteredby = $this->reporter->fixcolumn([$enteredby], '45', 0);
        $arr_reason = $this->reporter->fixcolumn([$reason], '45', 0);

        $arr_brgyname = $this->reporter->fixcolumn([$name], '45', 0);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 0,'  '. (isset($arr_cname[0]) ? $arr_cname[0] : ''), 'B', 'L', false, 0);// 
        PDF::MultiCell(212, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize); 
        PDF::MultiCell(80, 0, 'Brgy Case No:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 0, (isset($arr_brgyid[0]) ? $arr_brgyid[0] : ''), 'B', 'C', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 0, '  '. (isset($arr_caddr[0]) ? $arr_caddr[0] : ''), 'B', 'L', false); 
        PDF::MultiCell(150, 0, '  '. (isset($arr_cno[0]) ? $arr_cno[0] : ''), 'B', 'L', false); 

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(150, 0, 'Complaint/s', '', 'L', false, 0);
        PDF::MultiCell(212, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 0, 'For:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 0, (isset($arr_reason[0]) ? $arr_reason[0] : ''), 'B', 'L', false);
        PDF::MultiCell(0, 0, "\n", '', '');

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(150, 0, 'against', '', 'C', false); 
        PDF::MultiCell(0, 0, "\n", '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 0, '  '. (isset($arr_rname[0]) ? $arr_rname[0] : ''), 'B', 'L', false, 0); 
        PDF::MultiCell(212, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 0, 'Desk Officer:', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 0, (isset($arr_enteredby[0]) ? $arr_enteredby[0] : ''), 'B', 'L', false);
        PDF::MultiCell(150, 0, '  '. (isset($arr_raddr[0]) ? $arr_raddr[0] : ''), 'B', 'L', false); 
        PDF::MultiCell(150, 0, '  '. (isset($arr_rno[0]) ? $arr_rno[0] : ''), 'B', 'L', false); 

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(150, 0, 'Respondents/s', '', 'L', false, 0);
        PDF::MultiCell(212, 0, '', '', 'L', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::SetFont($fontbold, '',15);
        PDF::MultiCell(0, 0, 'S U M M O N S', '', 'C', false);
        PDF::MultiCell(0, 0, "\n", '', '');
        
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(30, 0, 'TO: ', '', 'L', false, 0);
        PDF::MultiCell(150, 0, (isset($arr_rname[0]) ? $arr_rname[0] : ''), 'B', 'L', false); 

        PDF::MultiCell(0, 5, '', '', 'L', false); 

        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(40, 0, '', '', 'L', false,0); 
        PDF::MultiCell(447, 0, 'You are hereby summoned to appear before me in person together with your witnesses, on the ', '', 'J', false, 0); 
        PDF::MultiCell(40, 0, '', 'B', 'C', false,0);  
        PDF::MultiCell(50, 0, ' day of ', '', 'J', false); 

        PDF::MultiCell(70, 0, '','B', 'C', false, 0);
        PDF::MultiCell(25, 0, ',  20','', 'L', false, 0);
        PDF::MultiCell(30, 0, '','B', 'L', false, 0);
        PDF::MultiCell(20, 0, ' at', '', 'L', false,0);
        PDF::MultiCell(125, 0, ' ', 'B', 'L', false, 0);
        PDF::MultiCell(300, 0, 'then and there to answer to a complaint made before me,', '', 'J', false);
        PDF::MultiCell(430, 0, 'copy of which is attached hereto, for a mediation/conciliation of your dispute with complainant/s', '', 'J', false);
        PDF::MultiCell(0, 0, ' ','', '', false); //extra space for the next paragraph
        PDF::MultiCell(40, 0, '', '', 'L', false,0); 
        PDF::MultiCell(0, 0, 'You are hereby warned that refusal or willful failure to appear in obedience to this summons entitle the complainant/s', '', 'J', false);
        PDF::MultiCell(0, 0, 'to proceed directly against you in court/government office, where you may be barred from filling any counterclaims arising', '', 'J', false);
        PDF::MultiCell(0, 0, 'from the said complaint', '', '');
        PDF::MultiCell(0, 0, '', '', ''); //extra space for the next paragraph
        PDF::MultiCell(60, 0, '', '', 'L', false,0); 
        PDF::MultiCell(0, 0, 'FAIL NOT or else face punishment for contempt of court', '', 'L', false);

        PDF::MultiCell(0, 5, '', '', '');
        PDF::MultiCell(60, 0, '', '', 'L', false,0); 
        PDF::MultiCell(20, 0, 'This', '', 'L', false,0); 
        PDF::MultiCell(20, 0, ' '.' ', 'B', 'C', false, 0);
        PDF::MultiCell(50, 0, ' day of ', '', 'J', false, 0);
        PDF::MultiCell(70, 0, '','B', 'C', false, 0);
        PDF::MultiCell(25, 0, ',  20','', 'L', false, 0);
        PDF::MultiCell(20, 0, '','B', 'L', false,0);

        PDF::MultiCell(310, 0, 'at '. strtoupper($headerdata[0]->address), '', 'L', false);     //address var
        //Barangay Doña Imelda, Quezon City, Metro Manila
        PDF::MultiCell(0, 20, '', '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, (isset($arr_brgyname[0]) ? $arr_brgyname[0] : ''), '', 'C', false); //Pungong Barangay
        PDF::SetFont($fontbold, '', $fontsub);
        PDF::MultiCell(0, 0, 'Punong Barangay ', '', 'C', false);

        PDF::MultiCell(0, 0, '', 'B', 'C', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 0, '', '', 'C', false); 
        PDF::MultiCell(0, 0, 'OFFICER\'S RETURN  ', '', 'C', false);
        PDF::MultiCell(0, 0, '', '', 'C', false);

        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(40, 0, '', '', 'L', false,0); 
        PDF::MultiCell(180, 0, 'I served this summons upon respondent', '', 'J', false, 0);
        PDF::MultiCell(147, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(35, 0, 'on the', '', 'J', false, 0);
        PDF::MultiCell(30, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(50, 0, ' day of ', '', 'J', false, 0);
        PDF::MultiCell(70, 0, ',','B', 'R', false);

        PDF::MultiCell(17, 0, '20','', 'L', false, 0);
        PDF::MultiCell(35, 0, ',','B', 'R', false,0);
        PDF::MultiCell(105, 0, 'and upon respondent', '', 'J', false, 0);
        PDF::MultiCell(145, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(35, 0, 'on the', '', 'J', false, 0);
        PDF::MultiCell(35, 0, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(50, 0, ' day of ', '', 'J', false, 0);
        PDF::MultiCell(75, 0, ' ','B', 'C', false,0);
        PDF::MultiCell(20, 0, ',  20','', 'L', false, 0);
        PDF::MultiCell(35, 0, ' ','B', 'L', false);

        PDF::MultiCell(380, 0, 'by (write name/s of respondent/s before mode by which he/they was/were served.)', '', 'J', false);
        PDF::MultiCell(0, 0,'', '', '');

        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::SetDrawColor(128, 128, 128);
        PDF::MultiCell(40, 0, '', '', 'L', false,0); 
        PDF::MultiCell(240, 0, 'Ibinigay itong patawag sa inireklamong si', '', 'J', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(25, 0, 'sa ika', '', 'J', false, 0);
        PDF::MultiCell(15, 0, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(40, 0, ' araw ng ', '', 'J', false, 0);
        PDF::MultiCell(40, 0, ', ','B', 'R', false);
        

        PDF::MultiCell(25, 0, '20','', 'L', false, 0);
        PDF::MultiCell(35, 0, ', ','B', 'R', false,0);
        PDF::MultiCell(80, 0, ' at gayundin sa ', '', 'J', false, 0);
        PDF::MultiCell(60, 0, 'inireklamong si', '', 'J', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(25, 0, 'sa ika', '', 'J', false, 0);
        PDF::MultiCell(25, 0, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, ' araw ng ', '', 'J', false, 0);
        PDF::MultiCell(50, 0, ' ','B', 'C', false,0);
        PDF::MultiCell(25, 0, ',  20','', 'L', false, 0);
        PDF::MultiCell(15, 0, ' ','B', 'L', false);

        PDF::MultiCell(572, 0,'(isulat and mga pangalan nila bago ibigay sa mga inireklamo.)','', 'L', false);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetDrawColor(0, 0, 0);

        PDF::MultiCell(0, 0, "\n", '', '');

        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(572, 0,'Respondent/s','', 'L', false);
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(572, 0,'________________ 1. Handling to him/them said summons in person, or','', 'L', false);

        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(92, 0,'','', 'L', false,0);
        PDF::MultiCell(480, 0,'Ibinigay ang nabanggit na patawag sa mismon taong inireklamo, o kaya','', 'L', false);
        PDF::SetTextColor(0, 0, 0);
        
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(572, 0,'________________ 2. Handling to him/them said summons and he/they refused to receive it, or ','', 'L', false);

        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(92, 0,'','', 'L', false,0);
        PDF::MultiCell(480, 0,'Ibinigay ang nabanggit na patawag sa inireklamo pero tnanggihan niyang tanggapin, o kaya','', 'L', false);
        PDF::SetTextColor(0, 0, 0);

        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(300, 0,'________________ 3. Leaving said summons at his/their dwelling with ','', 'L', false, 0);
        PDF::MultiCell(100, 0,'','B', 'C', false,0);
        PDF::MultiCell(30, 0,'thru','', 'C', false,0);
        PDF::MultiCell(100, 0,'','B', 'C', false);
        PDF::MultiCell(365, 0,'(Name)','', 'R', false,0);
        PDF::MultiCell(130, 0,'(Name)','', 'R', false);


        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(92, 0,'','', 'L', false,0);
        PDF::MultiCell(480, 0,'Iniwan ang nabanggit na patawag sa tirahan ni ________________________ kay _______________________','', 'L', false);
        PDF::MultiCell(350, 0,'(Pangalan)','', 'R', false,0);
        PDF::MultiCell(120, 0,'(Pangalan)','', 'R', false);
        PDF::SetTextColor(0, 0, 0);

        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(92, 0,'','', 'L', false, 0);
        PDF::MultiCell(250, 0,'a person of suitable age and discreton residing therein, or','', 'L', false);

        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(92, 0,'','', 'L', false, 0);
        PDF::MultiCell(250, 0,'may sapat na gulang ata malayang pagpili ang taong nakatra, o kaya','', 'L', false);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(0, 0, "\n", '', '');
        //---------------------------
        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(300, 0,'________________ 4. Leaving said summons at his/their dwelling with ','', 'L', false, 0);
        PDF::MultiCell(100, 0,'','B', 'C', false,0);
        PDF::MultiCell(30, 0,'thru','', 'C', false,0);
        PDF::MultiCell(100, 0,'','B', 'C', false);
        PDF::MultiCell(370, 0,'(Name)','', 'R', false,0);
        PDF::MultiCell(120, 0,'(Name)','', 'R', false);

        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(92, 0,'','', 'L', false,0);
        PDF::MultiCell(480, 0,'Iniwan ang nabanggit na patawag sa opisina/lugar ng negosyo ni ___________________ kay _____________________','', 'L', false);
        PDF::MultiCell(415, 0,'(Pangalan)','', 'R', false,0);
        PDF::MultiCell(90, 0,'(Pangalan)','', 'R', false);
        PDF::SetTextColor(0, 0, 0);

        PDF::SetFont($font, '', $fontbody);
        PDF::MultiCell(92, 0,'','', 'L', false, 0);
        PDF::MultiCell(250, 0,'a competent person in charge thereof.','', 'L', false);

        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(92, 13,'','', 'L', false, 0);
        PDF::MultiCell(250, 13,'na may kakayanan itnalaga','', 'L', false);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(0, 20, '', '', ''); 
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::MultiCell(50, 13,'Officers :','', 'C', false,0);
        PDF::MultiCell(25, 13,'','', 'L', false,0);
        PDF::MultiCell(125, 13,'','B', 'L', false,0);
        PDF::MultiCell(25, 13,'','', 'L', false,0);
        PDF::MultiCell(125, 13,'','B', 'L', false,0);
        PDF::MultiCell(25, 13,'','', 'L', false,0);
        PDF::MultiCell(125, 13,'','B', 'L', false,0);
        PDF::MultiCell(23, 13,'','', 'L', false);

        PDF::MultiCell(0, 0,'', '', '');

        PDF::MultiCell(280, 13,'Received by Respondent/s / Representatve/s','', 'C', false, 0);
        PDF::MultiCell(12, 13,'|','', 'C', false, 0);
        PDF::MultiCell(280, 13,'Received by Complainant/s / Representatve/s','', 'C', false);

        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(280, 13,'Natanggap ng Inireklamo / Kinatawan','', 'C', false, 0);
        PDF::SetFont($fontbold, '', $fontbody);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(12, 13,'|','', 'C', false, 0);
        PDF::SetFont($font, '', $fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(280, 13,'Natanggap ng Nagrereklamo / Kinatawan','', 'C', false);

        PDF::SetFont($font, '', $fontbody);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(11, 13,'','', 'C', false, 0);
        PDF::MultiCell(130, 13,'','B', 'C', false, 0);     
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'','B', 'C', false, 0);
        PDF::MultiCell(30, 13,'|','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'','B', 'C', false, 0);  
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(130, 13,'','B', 'C', false, 0);
        PDF::MultiCell(11, 13,'','', 'C', false);     

        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(11, 13,'','', 'C', false, 0);
        PDF::MultiCell(130, 13,'(Printed Name and Signiture','', 'C', false, 0);     
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'(Date)','', 'C', false, 0);
        PDF::MultiCell(30, 13,'|','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'(Date)','', 'C', false, 0);  
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(130, 13,'(Printed Name and Signiture','', 'C', false, 0);
        PDF::MultiCell(11, 13,'','', 'C', false); 

        PDF::SetFont($font, '', $fontbody);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(11, 13,'','', 'C', false, 0);
        PDF::MultiCell(130, 13,'','B', 'C', false, 0);     
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'','B', 'C', false, 0);
        PDF::MultiCell(30, 13,'|','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'','B', 'C', false, 0);  
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(130, 13,'','B', 'C', false, 0);
        PDF::MultiCell(11, 13,'','', 'C', false);     

        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(11, 13,'','', 'C', false, 0);
        PDF::MultiCell(130, 13,'(Printed Name and Signiture','', 'C', false, 0);     
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'(Date)','', 'C', false, 0);
        PDF::MultiCell(30, 13,'|','', 'C', false, 0);  
        PDF::MultiCell(120, 13,'(Date)','', 'C', false, 0);  
        PDF::MultiCell(10, 13,'','', 'C', false, 0);  
        PDF::MultiCell(130, 13,'(Printed Name and Signiture','', 'C', false, 0);
        PDF::MultiCell(11, 13,'','', 'C', false); 
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function summons2_layout_PDF($config, $data, $members)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontsize = 11;
            $fontbody = 10;
            $fontsub = 9;
        }  

        $this->default_PDF_header($config, $data);
        //client
        $header = !empty($data) ? $data[0] : null;
        $docno = isset($header->docno) ? $header->docno : '';
        $brgyid = isset($header->brgyid) ? $header->brgyid : '';
        $cname = isset($header->cname) ? $header->cname : '';
        $caddr = isset($header->caddr) ? $header->caddr : '';
        $cno = isset($header->cno) ? $header->cno : '';
        $rname = isset($header->rname) ? $header->rname : '';
        $raddr = isset($header->raddr) ? $header->raddr : '';
        $rno = isset($header->rno) ? $header->rno : '';
        $enteredby = isset($header->enteredby) ? $header->enteredby : '';
        $tpdo = isset($header->tpdo) ? $header->tpdo : '';
        $facts = isset($header->facts) ? $header->facts : '';
        $reason = isset($header->reason) ? $header->reason : '';

        //brgy
        $member = !empty($members) ? $members[0] : null;
        $name = isset($member->name) ? $member->name : '';

        //fixcolumn
        $arr_docno = $this->reporter->fixcolumn([$docno], '45', 0);
        $arr_brgyid = $this->reporter->fixcolumn([$brgyid], '45', 0);
        $arr_cname = $this->reporter->fixcolumn([$cname], '45', 0);
        $arr_caddr = $this->reporter->fixcolumn([$caddr], '45', 0);
        $arr_cno = $this->reporter->fixcolumn([$cno], '45', 0);
        $arr_rname = $this->reporter->fixcolumn([$rname], '45', 0);
        $arr_raddr = $this->reporter->fixcolumn([$raddr], '45', 0);
        $arr_rno = $this->reporter->fixcolumn([$rno], '45', 0);
        $arr_enteredby = $this->reporter->fixcolumn([$enteredby], '45', 0);
        $arr_tpdo = $this->reporter->fixcolumn([$tpdo], '45', 0);
        $arr_facts = $this->reporter->fixcolumn([$facts], '45', 0);
        $arr_reason = $this->reporter->fixcolumn([$reason], '45', 0);

        $arr_brgyname = $this->reporter->fixcolumn([$name], '45', 0);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(155, 15, (isset($arr_cname[0]) ? $arr_cname[0] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(207, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize); 
        PDF::MultiCell(80, 15, 'Brgy Case No:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 15, (isset($arr_brgyid[0]) ? $arr_brgyid[0] : ''), 'B', 'C', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(155, 15,'  '. (isset($arr_caddr[0]) ? $arr_caddr[0] : ''), 'B', 'L', false, 0); 
        PDF::SetTextColor(128, 128, 128);
        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(207, 15, '', '', 'L', false, 0);
        PDF::MultiCell(100, 15, 'Usaping Barangay Blg', '', 'L', false);
        PDF::SetTextColor(0, 0, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(155, 15, '  '. (isset($arr_cno[0]) ? $arr_cno[0] : ''), 'B', 'L', false); 

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(150, 15, 'Complaint/s', '', 'L', false, 0);
        PDF::MultiCell(422, 15, '', '', 'L', false);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(150, 15, 'Mga Maysumbong', '', 'L', false);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(0, 0, "\n", '', '');

        PDF::MultiCell(150, 15, '-against-', '', 'C', false, 0);
        PDF::MultiCell(212, 15, '', '', 'C', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 15, 'For:', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsub);
        PDF::MultiCell(180, 15, (isset($arr_reason[0]) ? $arr_reason[0] : ''), 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n", '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(155, 15, (isset($arr_rname[0]) ? $arr_rname[0] : ''), 'B', 'L', false, 0); 
        PDF::MultiCell(212, 15, '', '', 'L', false, 0);
        PDF::SetTextColor(128, 128, 128);
        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(50, 15, 'Ukol sa', '', 'L', false, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(190, 15, '', '', 'L', false);

        PDF::MultiCell(155, 15,'   ' . (isset($arr_raddr[0]) ? $arr_raddr[0] : ''), 'B', 'L', false); 
        PDF::MultiCell(155, 15, '   '.(isset($arr_rno[0]) ? $arr_rno[0] : ''), 'B', 'L', false); 

        PDF::SetFont($font, '', $fontsub);
        PDF::MultiCell(150, 15, 'Respondents/s', '', 'L', false);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(150, 15, 'Mga Ipinagsumbong', '', 'L', false);
        PDF::MultiCell(212, 15, '', '', 'L', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 0, "\n", '', '');
        PDF::SetFont($fontbold, '',13);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(0, 0, 'S U M M O N S', '', 'C', false);
        PDF::SetFont($fontbold, '',10);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(0, 0, 'PATAWAG', '', 'C', false);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(0, 0, "\n\n", '', '');

        PDF::SetFont($fontbold, '',$fontbody);
        PDF::MultiCell(130, 10, 'TO :     Respondent/s : ', '', 'L', false, 0);
        PDF::MultiCell(250, 10, (isset($arr_rname[0]) ? $arr_rname[0] : ''), 'B', 'L', false);
        PDF::SetFont($fontbold, '',$fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::MultiCell(0, 10, 'Kay :   Mga Ipinagsumbong : ', '', 'L', false);
        PDF::SetTextColor(0, 10, 0);
        PDF::MultiCell(0, 10, "\n\n", '', '');

        PDF::SetFont($fontbold, '',$fontbody);
        PDF::MultiCell(40, 10, ' ', '', 'L', false, 0);
        PDF::MultiCell(502  , 10, 'You are hereby summoned to appear before me in person together with your withnesses, on', '', 'J', false);
        PDF::MultiCell(55, 10, 'day of ', '', 'J', false, 0);
        PDF::MultiCell(85, 10, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(30, 10, '','B', 'C', false, 0);
        PDF::MultiCell(25, 10, ',  20','', 'R', false, 0);
        PDF::MultiCell(80, 10, '','B', 'L', false, 0);
        PDF::MultiCell(30, 10, ' at', '', 'C', false,0);
        PDF::MultiCell(200, 10, '', 'B', 'L', false,0); 
        PDF::MultiCell(37, 10, 'in the', '', 'J', false);

        PDF::MultiCell(542, 10, 'morning/afernoon, then and there to answer to a complaint made before me, copy of which is atached', '', 'J', false);
        PDF::MultiCell(350  , 10, 'hereto for a mediaton / conciliaton of your dispute with complainant/s.', '', 'J', false);
        PDF::MultiCell(0, 10, "\n", '', '');
        PDF::MultiCell(40, 10, ' ', '', 'L', false, 0);
        PDF::MultiCell(502  , 10, 'You are hereby warned that refusal or willful failure to appear in obedience to this summons will', '', 'J', false);
        PDF::MultiCell(542  , 10, 'entitled the complaintant/s to proceed directly against you in court/ goverment office, where you', '', 'J', false);
        PDF::MultiCell(400, 10, 'may be barred from fling any counterclaims arising from the said complaint/s.', '', 'J', false);
        PDF::MultiCell(0, 0, "\n\n", '', '');

        PDF::SetLineStyle(['dash' => '2,2']);
        PDF::Line(20, PDF::GetY(), 592, PDF::GetY());   // set to broken lines 
        PDF::SetLineStyle(['dash' => '0']);

        PDF::MultiCell(0, 0, "\n\n", '', '');

        PDF::SetTextColor(128, 128, 128);
        PDF::SetDrawColor(128, 128, 128);
        PDF::MultiCell(40, 10, ' ', '', 'L', false, 0);
        PDF::MultiCell(482, 10, 'Sa papamagitan nito, kayo\'y tnatawagan upang personal na humarap sa akin, kasama ang', '', 'J', false);
        PDF::MultiCell(150, 10, 'inyong mga testigo  sa ika', '', 'J', false, 0);
        PDF::MultiCell(40, 10, '', 'B', 'C', false, 0);
        PDF::MultiCell(50, 10, ' araw ng ', '', 'J', false, 0);
        PDF::MultiCell(90, 10, '','B', 'C', false, 0);
        PDF::MultiCell(25, 10, ',  20','', 'J', false, 0);
        PDF::MultiCell(50, 10, '','B', 'L', false, 0);   
        PDF::MultiCell(120, 10, ' ng umaga / ng hapon,', '', 'J', false);

        PDF::MultiCell(522  , 10, 'upang sagutin ang isang sumbong na idinulog sa akin na ang kopya ay kalakip nito,', '', 'J', false);
        PDF::MultiCell(522  , 10, 'para sa pamagitnaan / pagkasunduin kayo sa inyong alitan ng mga (may) sumbong.', '', 'J', false);
        
        PDF::MultiCell(0, 0, "\n", '', '');

        PDF::MultiCell(40, 10, ' ', '', 'L', false, 0);
        PDF::MultiCell(482  , 10, 'Sa papamagitan nito, kayo\'y binabalaan na ang inyong pagtanggi o sadyang hindi pagharap', '', 'J', false);
        PDF::MultiCell(522  , 10, 'bilang pagtalima sa patawag na ito ay magbibigay ng karapatan sa (mga) may sumbong upang', '', 'J', false);
        PDF::MultiCell(522  , 10, 'tuwiran kayong ipagsakdal sa hukuman/tanggapan  ng pamahalaan, na doon ay mahahadlangan', '', 'J', false);
        PDF::MultiCell(460  , 10, 'kayo na magharap ng kahit anumang kontra - demanda bunga ng nabanggit na sumbong.', '', 'J', false);

        PDF::MultiCell(0, 0, "\n\n\n", '', '');
        PDF::MultiCell(60, 10, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '',$fontbody);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(512, 10, 'FAIL NOT or else face punishment for contempt of court', '', 'L', false);
        PDF::SetFont($font, '',$fontsub);
        PDF::SetTextColor(128, 128, 128);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(60, 10, ' ', '', 'L', false, 0);
        PDF::MultiCell(512, 10, 'HINDI PAGTUPAD, at kung hindi\'y parurusahan kayo sa salang paglapastangan sa hukuman.', '', 'L', false);
        PDF::MultiCell(0, 0, "\n\n\n", '', '');

        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fontbold, '',$fontbody);
        PDF::MultiCell(60, 10, ' ', '', 'L', false, 0);
        PDF::MultiCell(20, 10, 'This', '', 'J', false, 0);
        PDF::MultiCell(30, 10, '', 'B', 'C', false, 0);
        PDF::MultiCell(40, 10, 'day of ', '', 'C', false, 0);
        PDF::MultiCell(90, 10, '','B', 'C', false, 0);
        PDF::MultiCell(25, 10, ',  20','', 'L', false, 0);
        PDF::MultiCell(50   , 10, ' .','B', 'R', false);

        PDF::SetTextColor(128, 128, 128);
        PDF::SetDrawColor(128, 128, 128);
        PDF::SetFont($font, '',$fontsub);
        PDF::MultiCell(60, 10, ' ', '', 'L', false, 0);
        PDF::MultiCell(65, 10, 'Ngayong ika', '', 'L', false, 0);
        PDF::MultiCell(30, 10, ' ', 'B', 'C', false, 0);
        PDF::MultiCell(60, 10, 'ng araw ng ', '', 'C', false, 0);
        PDF::MultiCell(90, 10, '','B', 'C', false, 0);
        PDF::MultiCell(25, 10, ',  20','', 'L', false, 0);
        PDF::MultiCell(50   , 10, ' '. '.','B', 'R', false);

        PDF::MultiCell(0, 0, "\n\n\n", '', '');

        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 15, (isset($arr_brgyname[0]) ? $arr_brgyname[0] : ''), '', 'C', false); //Pungong Barangay
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(0, 15, 'Punong Barangay ', '', 'C', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }



}
