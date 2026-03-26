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

class bd
{
    private $modulename = "BARANGAY CLEARANCE";
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

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];

        $query = "
        select head.trno,head.docno,date(head.dateid) as dateid,head.client,head.clientname,
        head.yourref as rcno,head.ourref as plaissue,
        locl.clearance as purpose,format(head.amount,2) as amount,
        info.addressno,cl.addr, date(cl.bday) as bday, cl.province,
        head.rem
        from lahead as head
        left join client as cl on cl.client = head.client
        left join clientinfo as info on info.clientid = cl.clientid 
        left join locclearance as locl on locl.line = head.purposeid
        where doc = 'BD' and head.trno = $trno 
        union all
        select head.trno,head.docno,date(head.dateid) as dateid,cl.client,head.clientname,
        head.yourref as rcno,head.ourref as plaissue,
        locl.clearance as purpose,format(head.amount,2) as amount,
        info.addressno,cl.addr, date(cl.bday) as bday, cl.province,
        head.rem
        from glhead as head
        left join client as cl on cl.clientid = head.clientid
        left join clientinfo as info on info.clientid = head.clientid
        left join locclearance as locl on locl.line = head.purposeid
        where head.doc = 'BD' and head.trno = $trno ";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function report_members_query($config)
    {
        $query = "
        select category as name, position  from reqcategory
        where isbrgyoff = 1
        ";

        $brgy = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $brgy;
    } //end fn


    public function reportplotting($params, $data)
    {
        // var_dump($params['params']['dataparams']);
        $reporttype = $params['params']['dataparams']['reporttype'];
        $members = $this->report_members_query($params);
        return $this->default_default_PDF($params, $data, $members);
    }

    public function default_cc_header_PDF($params, $data)
    {
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

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(470, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Document # : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(150, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Full Name : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(400, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "RC No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(400, 20, (isset($data[0]['rcno']) ? $data[0]['rcno'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Place Issue: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['plaissue']) ? $data[0]['plaissue'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Purpose: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(400, 20, (isset($data[0]['purpose']) ? $data[0]['purpose'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Amount Fee: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['amount']) ? $data[0]['amount'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(180, 0, "POSTDATE", '', 'L', false, 0);
        PDF::MultiCell(180, 0, "ACCOUNT", '', 'L', false, 0);
        PDF::MultiCell(180, 0, "DB", '', 'R', false, 0);
        PDF::MultiCell(180, 0, "CR", '', 'R', false);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');


        // PDF::MultiCell(0, 0, "\n\n");
    }

    public function default_cc_PDF($params, $data)
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
        $this->default_cc_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');


        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {



                $maxrow = 1;

                $detail = $this->detail($data[0]['trno']);


                foreach ($detail as $key => $value) {

                    $acnoname = $value->acnoname;
                    $postdate = $value->postdate;
                    $db = number_format($value->db, 2);
                    $cr = number_format($value->cr, 2);


                    $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '45', 0);
                    $arr_postdate = $this->reporter->fixcolumn([$postdate], '15', 0);
                    $arr_db = $this->reporter->fixcolumn([$db], '11', 0);
                    $arr_cr = $this->reporter->fixcolumn([$cr], '11', 0);


                    $maxrow = $this->othersClass->getmaxcolumn([$arr_acnoname, $arr_postdate, $arr_db, $arr_cr]);
                    for ($r = 0; $r < $maxrow; $r++) {
                        PDF::SetFont($font, '', $fontsize);

                        PDF::MultiCell(180, 15, ' ' . (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(180, 15, ' ' . (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(180, 15, ' ' . (isset($arr_db[$r]) ? $arr_db[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(180, 15, ' ' . (isset($arr_cr[$r]) ? $arr_cr[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                    }
                }
            }
        }
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, 'Detail of Purpose: ', '', 'L', false, 0);
        PDF::MultiCell(620, 0, '' . $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(240, 0, 'Prepared By: ' . $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, '', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Approved By: ' . $params['params']['dataparams']['approved'], '', 'L', false);
        PDF::MultiCell(0, 0, "\n");


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function detail($trno)
    {
        $query = "
        select coa.acnoname,detail.db,detail.cr,date(detail.postdate) as postdate 
        from ladetail as detail 
        left join coa on coa.acnoid = detail.acnoid 
        where detail.trno = $trno
        union all 
        select coa.acnoname,detail.db,detail.cr,date(detail.postdate) as postdate 
        from gldetail as detail 
        left join coa on coa.acnoid = detail.acnoid 
        where detail.trno = $trno
        ";

        return $this->coreFunctions->opentable($query);
    }
    public function default_PDF_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        //temp logos
        $logo1 = public_path('images/barangay/1.jpg');
        $logo2 = public_path('images/barangay/2.jpg');
        

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHICB.TTF')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
        $fontarial = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
        $fontarialB = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
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

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(0, 0, '', '', 'L'); //$reporttimestamp,
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(null, 0, 'Republika ng Pilipinas', 'TLR', 'C', false);
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), 'LR', 'C');
        PDF::SetFont($font, '', 13);

        if (file_exists($logo1)) { //temp logo
            PDF::Image($logo1, 100, 50, 70, 70);
        }
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), 'LR', 'C');

        if (file_exists($logo2)) { //temp logo
            PDF::Image($logo2, 620, 50, 70, 70);
        }
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(0, 0,'TeleFax: ' . strtoupper($headerdata[0]->tel) . "\n" . strtoupper($headerdata[0]->tel), 'LR', 'C');

        PDF::MultiCell(0, 0, "\n", 'LR', '');

        PDF::SetFont($font, '', 17);

        PDF::MultiCell(null, 0, 'OFFICE OF THE BARANGAY CAPTAIN', 'LR', 'C', false);
        PDF::MultiCell(0, 0, "\n", 'LR', '');
        PDF::SetFont($fontbold, 'U', 18);
        PDF::MultiCell(null, 0, $this->modulename, 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n", 'LR', '');


        PDF::MultiCell(0, 0, "\n\n" , 'LR', '');
        PDF::SetFont($font, '', 5);
    }
    public function default_default_PDF($params, $data, $members)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $brgyimg = public_path('images/barangay/3.jpg'); //temp image

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontbitalic = "";
        $fontitalic = "";
        $fontsize = "11";
        $fontsize2 = "9";
        $fontsize3 = "8";
        $fontbody = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fonttimesbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
            $fontbitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }
        $this->default_PDF_header($params, $data);

        $color   = array(36, 59, 117);      // text color
        $setfill = array(179, 226, 255);    // fill color 
        $setfillB = array(0, 129, 194);  // left panel


        //client info
        $header = !empty($data) ? $data[0] : null;
        $clientname = isset($header['clientname']) ? $header['clientname'] : '';
        $address = isset($header['addr']) ? $header['addr'] : '';
        $bday = isset($header['bday']) ? $header['bday'] : '';
        $bdayFormatted = (!empty($bday) && $bday != '0000-00-00')? date('F j, Y', strtotime($bday)) : '';
        $dateIssued = (!empty($data[0]['dateid']) && $data[0]['dateid'] != '0000-00-00')? date('F j, Y', strtotime($data[0]['dateid'])): '';
        $bplace  = isset($header['province']) ? $header['province'] : '';
        $rem = isset($header['rem']) ? $header['rem'] : '';
        $plaissue = isset($header['plaissue']) ? $header['plaissue'] : '';
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
        $arr_address = $this->reporter->fixcolumn([$address],'45', 0);
        $arr_bday = $this->reporter->fixcolumn([$bdayFormatted],'45', 0);
        $arr_bplace = $this->reporter->fixcolumn([$bplace],'45', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem],'45', 0);
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
        PDF::MultiCell(180, 0, '', 'TLR', 'C', true, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'TL', '', true, 0);
        PDF::MultiCell(510, 0, '', 'TR', '', true,0);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 0, (isset($arr_punongBarangay[0]) ? $arr_punongBarangay[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::MultiCell(510, 0, '', 'R', '', true,0);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 0, 'Punong Barangay', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 0, '', '', '', false, 0);  // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(340, 0, 'TO WHOM IT MAY CONCERN :', '', '', true, 0);
        PDF::SetTextColor(0, 18, 77);
        PDF::MultiCell(70, 0, 'CONTROL #:', '', '', true, 0);
        PDF::MultiCell(100, 0, 'BCC-25-0088980', 'R', '', true, 0);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetTextColor(0, 0, 0);
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::MultiCell(340, 0, 'This is to Certify that the person whose name right thumb mark and', '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(70, 0, 'Date of Issue: ', '', '', true, 0);
        PDF::MultiCell(100, 0, $dateIssued, 'R', '', true, 0);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0); 
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0); // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::MultiCell(340, 0, 'picture appear hereon has requested a Record and Barangay Clearance', '', '', true, 0);
        PDF::MultiCell(70, 0, '', '', '', true, 0);
        PDF::MultiCell(100, 0, '', 'R', '', true,0);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, 'U', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 0, 'MGA KAGAWAD', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '',false, 0); // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::MultiCell(340, 0, 'from this office and result/s is/are listed below:', '', '', true, 0);
        PDF::MultiCell(15, 0, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(140, 0, '', 'TLR', '', true, 0);
        PDF::MultiCell(7.5, 0, '', 'L', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(7.5, 0, '', 'R', '', true, 0);
        PDF::MultiCell(10, 0, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(340, 20, '', '', '', true, 0);
        PDF::MultiCell(15, 20, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(140, 20, '', 'LR', '', true, 0);
        PDF::MultiCell(7.5, 20, '', 'L', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(7.5, 20, '', 'R', '', true, 0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);   
        PDF::MultiCell(180, 20, (isset($arr_kagawad0[0]) ? $arr_kagawad0[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Name:', '', '', true, 0);
        PDF::MultiCell(270, 20, ': '. (isset($arr_clientname[0]) ? $arr_clientname[0] : ''), '', '', true, 0); //inserted
        PDF::MultiCell(15, 20, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(140, 20, '', 'LR', '', true, 0);
        PDF::MultiCell(7.5, 20, '', 'L', '', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(7.5, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Address', '', '', true, 0);
        PDF::MultiCell(270, 20, ': '. (isset($arr_address[0]) ? $arr_address[0] : ''), '', '', true, 0); //inserted
        PDF::MultiCell(15, 20, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(140, 20, '', 'LR', '', true, 0);
         PDF::MultiCell(7.5, 20, '', 'L', '', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(7.5, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 20, (isset($arr_kagawad1[0]) ? $arr_kagawad1[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(340, 20, '', '', '', true, 0);
        PDF::MultiCell(15, 20, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(140, 20, '', 'BLR', '', true, 0);
        PDF::MultiCell(7.5, 20, '', 'L', '', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(7.5, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Date of Birth', '', '', true, 0);
        PDF::MultiCell(270, 20, ': '. (isset($arr_bday[0]) ? $arr_bday[0] : ''), '', '', true, 0); //inserted
        PDF::MultiCell(15, 20, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(140, 20, '', 'T', 'C', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(15, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 20, (isset($arr_kagawad2[0]) ? $arr_kagawad2[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Place of Birth', '', '', true, 0);
        PDF::MultiCell(270, 20, ': ' . (isset($arr_bplace[0]) ? $arr_bplace[0] : ''), '', '', true, 0); //inserted
        PDF::SetFont($fonttimesbold, '', $fontsize);
        PDF::MultiCell(15, 20, '', '', '', true, 0);
        PDF::MultiCell(140, 20, 'BDI-25-0828', '', 'C', true, 0);
        PDF::MultiCell(15, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(340, 20, '', '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(170, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 20, (isset($arr_kagawad3[0]) ? $arr_kagawad3[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Purpose', '', '', true, 0);
        PDF::MultiCell(270, 20, ': '.(isset($arr_rem[0]) ? $arr_rem[0] : ''), '', '', true, 0); //inserted
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(160, 20, '', 'B', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(10, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Remarks', '', '', true, 0);
         PDF::MultiCell(270, 20, ': Remarks : No derogatory record on file as of date ', '', '', true, 0); //inserted
        PDF::SetFont($fontbitalic, '', $fontsize3);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(160, 20, "Applicant's Signature", 'T', 'C', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(10, 20, '', 'R', 'C', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false); 



        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 20, (isset($arr_kagawad4[0]) ? $arr_kagawad4[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(340, 20, '', '', '', true, 0);
        PDF::MultiCell(170, 20, '', 'R', '', true, 0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody); 
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'CTC No.#', '', '', true, 0);
        PDF::MultiCell(290, 20, ': ', '', '', true, 0); //inserted
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(130, 20, '', 'LTR', 'C', true, 0);
        PDF::MultiCell(10, 20, '', 'L', '', true,0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(10, 20, '', 'R', '', true,0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 20, (isset($arr_kagawad5[0]) ? $arr_kagawad5[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Issued At', '', '', true, 0);
        PDF::MultiCell(290, 20, ': ' , '', '', true, 0); //inserted
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(130, 20, '', 'LR', '', true, 0);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(10, 20, '', 'R', '', true, 0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetFont($fonttimesbold, '', $fontbody);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(70, 20, 'Issued On', '', '', true, 0);
        PDF::MultiCell(290, 20, ': ', '', '', true, 0); //inserted
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(130, 20, '', 'LR', '', true, 0);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(10, 20, '', 'R', '', true, 0);
        PDF::MultiCell(10, 20, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 20, (isset($arr_kagawad6[0]) ? $arr_kagawad6[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 20, '', '', '', false, 0); //true
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(360, 20, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(130, 20, '', 'LBR', '', true, 0);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(10, 20, '', 'R', '', true, 0);
        PDF::MultiCell(10, 20, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 20, '', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 20, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 20, '', 'L', '', true, 0);
        PDF::MultiCell(30, 20, '', '', '', true, 0);
        PDF::MultiCell(330, 20, 'This certification valid only from six (6) months from date of issue.', '', '', true, 0);
        PDF::SetFont($fontbitalic, '', $fontsize3);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::MultiCell(130, 20, 'Right Thumb Mark', 'T', 'C', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(20, 20, '', 'R', '', true,0);
        PDF::SetFont($fontbold, '', $fontsize2, 0);
        PDF::MultiCell(10, 20, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 0, (isset($arr_secretary[0]) ? $arr_secretary[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', '', '', true, 0);
        PDF::SetTextColor(0, 18, 77);
        PDF::MultiCell(330, 0, 'Note: Note valid without Barangay Seal', '', '', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(130, 0, '', '', '', true, 0);
        PDF::MultiCell(20, 0, '', 'R', '', true, 0);
        PDF::MultiCell(10, 0, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 0, 'Barangay Secretary', 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0); 
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'LB', '', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', 'B', '', true, 0);
        PDF::MultiCell(330, 0, '', 'B', '', true, 0);
        PDF::MultiCell(130, 0, '', 'B', '', true, 0);
        PDF::MultiCell(20, 0, '', 'RB', '', true, 0);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', '', '', false, 0);
        PDF::MultiCell(340, 0, '', '', '', false, 0);
        PDF::MultiCell(130, 0, '', '', '', false, 0);
        PDF::MultiCell(20, 0, '', 'R', false);
        
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 0, (isset($arr_treasurer[0]) ? $arr_treasurer[0] : ''), 'LR', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', '', '', false, 0);
        PDF::MultiCell(340, 0, '', '', '', false, 0);
        PDF::MultiCell(130, 0, '', '', '', false, 0);
        PDF::MultiCell(20, 0, '', 'R', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(180, 0, 'Barangay Treasure', 'R', 'C', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);

        PDF::MultiCell(520, 0, (isset($arr_punongBarangay[0]) ? $arr_punongBarangay[0] : ''), 'R', 'C', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(520, 0, 'Punong Barangay', 'R', 'C', false);

        //temp image
        if (file_exists($brgyimg)) {
            PDF::Image($brgyimg, 580, 593, 50, 50);
        }

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LRB', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', 'B', '', false, 0);
        PDF::MultiCell(10, 0, '', 'B', '', false, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', 'B', '', false, 0);
        PDF::MultiCell(340, 0, '', 'B', '', false, 0);
        PDF::MultiCell(130, 0, '', 'B', '', false, 0);
        PDF::MultiCell(20, 0, '', 'BR', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
