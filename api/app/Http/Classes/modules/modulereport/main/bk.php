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

class BK
{
    private $modulename = "Barangay Identification Card";
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
            ['label' => 'Format Front', 'value' => '0', 'color' => 'red'],
            ['label' => 'Format Back', 'value' => '1', 'color' => 'red']
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
            '0' as reporttype
            "
        );
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];

        $query = "select head.trno,head.docno,date(head.dateid) as dateid,head.client,head.clientname,info.precintno,
        info.settlertype,cl.addr,cl.bday,info.civilstatus,cl.sex,info.height,info.weight,ifnull(info.relation,'') as relation,
        ifnull(info.names,'') as names,ifnull(info.contactno,'') as contactno,info.address
        from lahead as head
        left join client as cl on cl.client = head.client
        left join clientinfo as info on info.clientid = cl.clientid
        left join locclearance as locl on locl.line = head.purposeid
        where doc = 'Bk' and head.trno = $trno
        union all
        select head.trno,head.docno,date(head.dateid) as dateid,cl.client,head.clientname,info.precintno,
        info.settlertype,cl.addr,cl.bday,info.civilstatus,cl.sex,info.height,info.weight,ifnull(info.relation,'') as relation,
        ifnull(info.names,'') as names,ifnull(info.contactno,'') as contactno,info.address
        from glhead as head
        left join client as cl on cl.clientid = head.clientid
        left join clientinfo as info on info.clientid = head.clientid
        left join locclearance as locl on locl.line = head.purposeid
        where head.doc = 'BK' and head.trno = $trno";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function report_default_brgy($config)
    {
         $query = "select category, position from reqcategory
            where isbrgyoff = 1";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        $reporttype = $params['params']['dataparams']['reporttype'];
        $brgy = $this->report_default_brgy($params);

        if ($reporttype == '1') {
            return $this->default_back_PDF($params, $data, $brgy);
        }
        return $this->default_front_PDF($params, $data, $brgy);
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
        $logo1 = public_path('images/barangay/background.jpg');
        $font = "";
        $fontbold = "";
        $fontsize = 7;
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
        // PDF::SetMargins(40, 40);
        PDF::SetAlpha(0.5); // 0 = invisible, 1 = solid, [para sa background logo]
        PDF::Image($logo1, 30, 10, 320, 200);  //Backgroung logo temp
        PDF::SetAlpha(1);

        
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetX(50);
        PDF::Image(public_path('images/barangay/leftid.png'), 38, 20, 33, 28);
        PDF::Image(public_path('images/barangay/rightid.png'), 310, 20, 34, 28);

        PDF::SetX(30);
        PDF::SetFont($font, '', 8);
        PDF::MultiCell(320, 0, 'Republika ng Pilipinas', 'TLR', 'C', false);

        PDF::SetX(30);
        PDF::SetFont($font, 'B', 9);
        PDF::MultiCell(320, 10, strtoupper($headerdata[0]->name), 'LR', 'C');

        PDF::SetX(30);
        PDF::SetFont($font, '', 8);
        PDF::MultiCell(25, 10, '', 'L', 'C',false,0);
        PDF::MultiCell(270, 10, strtoupper($headerdata[0]->address), '', 'C',false,0);
        PDF::MultiCell(25, 10, '', 'R', 'C');

        PDF::SetX(30);
        PDF::MultiCell(320, 20,'TeleFax: ' . strtoupper($headerdata[0]->tel), 'LR', 'C');

    }

    public function default_front_PDF($params, $data, $brgy)
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
        $fontbitalic = "";
        $fontitalic = "";
        $fontsize = "8";
        $fontsize2 = "8";
        $defaultid = public_path('images/barangay/defaultid.jpg'); // temp id picture
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontbitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }
        $this->default_PDF_header($params, $data);

         $officials = [
            'punong' => '',
            'secretary' => '',
            'treasurer' => '',
            'kagawad' => []
        ];
        
        foreach ($brgy as $row) {
            $position = strtolower(trim($row['position']));
            $name = strtoupper($row['category']);

            if (strpos($position, 'punong') !== false) {
                $officials['punong'] = $name;

            } elseif (strpos($position, 'secretary') !== false) {
                $officials['secretary'] = $name;

            } elseif (strpos($position, 'treasurer') !== false) {
                $officials['treasurer'] = $name;

            } else {
                $officials['kagawad'][] = $name;
            }
        }

        //client info
        $header = !empty($data) ? $data[0] : null;
        $clientname = isset($header['clientname']) ? $header['clientname'] : '';
        $address = isset($header['addr']) ? $header['addr'] : '';
        $bday = isset($header['bday']) ? $header['bday'] : '';
        $bdayFormatted = (!empty($bday) && $bday != '0000-00-00')? date('F j, Y', strtotime($bday)) : '';
        $status = isset($header['civilstatus']) ? $header['civilstatus'] : '';
        $gender = isset($header['sex']) ? $header['sex'] : '';
        $height = isset($header['height']) ? $header['height'] : '';
        $weight = isset($header['weight']) ? $header['weight'] : '';
        $precintno = isset($header['precintno']) ? $header['precintno'] : '';
        $settlertype = isset($header['settlertype']) ? $header['settlertype'] : '';

        $arr_clientname = $this->reporter->fixcolumn([$clientname], '45', 0);
        $arr_address = $this->reporter->fixcolumn([$address], '45', 0);
        $arr_bday = $this->reporter->fixcolumn([$bday], '45', 0);
        $arr_bdayFormatted = $this->reporter->fixcolumn([$bdayFormatted], '45', 0);
        $arr_status = $this->reporter->fixcolumn([$status], '45', 0);
        $arr_gender = $this->reporter->fixcolumn([$gender], '45', 0);
        $arr_height = $this->reporter->fixcolumn([$height], '45', 0);
        $arr_weight = $this->reporter->fixcolumn([$weight], '45', 0);
        $arr_precintno = $this->reporter->fixcolumn([$precintno], '45', 0);
        $arr_settlertype = $this->reporter->fixcolumn([$settlertype], '45', 0);

        $setfill  = array(255, 255, 255);
        $setfillB = array(0, 129, 194);

        PDF::SetXY(30, 60);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontbold, '', 8);
         PDF::SetTextColor(255, 102, 128); // Light Pink
        PDF::MultiCell(320, 15, 'B a r a n g a y  I d e n t i f i c a t i o n  C a r d', 'LR', 'C', false, 0);

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(320, 10, '', '', '', false);

        PDF::SetXY(30, 75);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(5, 13, '', 'L', '', true, 0);
        PDF::MultiCell(93, 13, '', 'TLR', '', false, 0);
        PDF::SetFillColor(255, 182, 193); // Light Pink
        PDF::MultiCell(6, 13, '', 'L', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(210, 13, '' . (isset($arr_clientname[0]) ? $arr_clientname[0] : ''), '', '', true, 0);
        PDF::MultiCell(6, 13, '', 'R', '', false);

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(5, 25, '', 'L', '', true, 0);
        PDF::MultiCell(93, 25, '', 'LR', '', false, 0);
        PDF::MultiCell(6, 25, '', 'L', '', true, 0);
        PDF::MultiCell(210, 25, '' . (isset($arr_address[0]) ? $arr_address[0] : ''), '', '', true, 0);
        PDF::MultiCell(6, 25, '', 'R', '', false);
        PDF::SetTextColor(0, 0, 0);

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(5, 13, '', 'L', '', true, 0);
        PDF::MultiCell(93, 13, '', 'LR', '', false, 0);
        PDF::MultiCell(6, 13, '', 'L', '', true, 0);
        PDF::SetTextColor(0, 0, 0); // "Sex" in black, value in blue
        PDF::MultiCell(24, 13, 'Sex : ', '', '', true, 0);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(30, 13, (isset($arr_gender[0]) ? $arr_gender[0] : ''), '', '', true, 0);
        PDF::MultiCell(51, 13, '', '', '', true, 0);
        PDF::SetTextColor(0, 0, 0);// "Birth Date" in black,value in blue
        PDF::MultiCell(44, 13, 'Birth Date : ', '', '', true, 0);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(65, 13, (isset($arr_bdayFormatted[0]) ? $arr_bdayFormatted[0] : ''), '', '', true, 0);
        PDF::MultiCell(2, 13, '', 'R', '', false);
        PDF::SetTextColor(0, 0, 0);  // reset color        

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(5, 13, '', 'L', '', true, 0);
        PDF::MultiCell(93, 13, '', 'LR', '', false, 0);
        PDF::MultiCell(6, 13, '', 'L', '', true, 0);
        PDF::SetTextColor(0, 0, 0);// "Height" in black, value in blue
        PDF::MultiCell(33, 13, 'Height : ', '', '', true, 0);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(20, 13, (isset($arr_height[0]) ? $arr_height[0] : ''), '', '', true, 0);
        PDF::SetTextColor(0, 0, 0);// "Weight" in black, value in blue
        PDF::MultiCell(37, 13, 'Weight : ', '', '', true, 0);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(16, 13, (isset($arr_weight[0]) ? $arr_weight[0] : ''), '', '', true, 0);
        PDF::SetTextColor(0, 0, 0);// "Status" in black, value in blue
        PDF::MultiCell(34, 13, 'Status : ', '', '', true, 0);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(72, 13, (isset($arr_status[0]) ? $arr_status[0] : ''), '', '', true, 0);
        PDF::MultiCell(4, 13, '', 'R', '', false);
        PDF::SetTextColor(0, 0, 0); // reset color        

         // photo image para sa id picture, temp picture muna
        PDF::SetX(30);
        if (file_exists($defaultid)) {
            PDF::Image($defaultid, 37.5, 77, 88, 86);
        }

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(5, 0, '', 'L', '', true, 0);
        PDF::MultiCell(93, 0, '', 'LR', '', true, 0);
        PDF::MultiCell(6, 0, '', 'L', '', true, 0);
        PDF::SetTextColor(0, 0, 0);// "Precinct No." in black, value in blue
        PDF::MultiCell(55, 0, 'Precinct No. : ', '', '', true, 0);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(45, 0, (isset($arr_precintno[0]) ? $arr_precintno[0] : ''), '', '', true, 0);
        PDF::MultiCell(6, 0, '', '', '', true, 0);
        PDF::SetTextColor(0, 0, 0);// "Type" in black, value in blue
        PDF::MultiCell(25, 0, 'Type : ', '', '', true, 0);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(79, 0, (isset($arr_settlertype[0]) ? $arr_settlertype[0] : ''), '', '', true, 0);
        PDF::MultiCell(6, 0, '', 'R', '', false);
        PDF::SetTextColor(0, 0, 0);// reset color

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 16, '', 'L', '', true, 0);          
        PDF::MultiCell(93, 16, '', 'LBR', '', true, 0);         
        PDF::MultiCell(6, 16, '', 'L', '', true, 0);          
        PDF::MultiCell(210, 16, '', '', '', true, 0);        
        PDF::MultiCell(6, 16, '', 'R', '', false); 
        
        PDF::SetX(30);
        PDF::MultiCell(320, 0, "\n",'LR');
        PDF::SetX(30);
        PDF::MultiCell(320, 0, "\n",'LR');

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(5, 0, '', 'L', '', true, 0);              
        PDF::MultiCell(95, 0, '', 'B', '', true, 0);           
        PDF::MultiCell(6, 0, '', '', '', true, 0); 
        PDF::SetTextColor(0, 70, 127);             
        PDF::MultiCell(208, 0, $officials['punong'], '', 'C', true, 0); 
        PDF::MultiCell(6, 0, '', 'R', '', false);               

        PDF::SetX(30);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontitalic, '', $fontsize);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(5, 15, '', 'LB', '', true, 0);
        PDF::MultiCell(95, 15, 'Signature', 'TB', 'C', true, 0);
        PDF::MultiCell(6, 15, '', 'B', '', true, 0);
        PDF::SetFont($fontitalic, 'B', $fontsize);
        PDF::MultiCell(208, 15,'Punong Barangay', 'B', 'C', true, 0);
        PDF::MultiCell(6, 15, '', 'RB', '', false);

        
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function default_back_PDF($params, $data, $brgy)
    {
        $font = "";
        $fontbold = "";
        $fontitalic = "";
        $fontbitalic = "";
        $fontsize = "9";
        $fontsize2 = "8";
        $rightid = public_path('images/barangay/rightid.png'); // temp id picture for the back, use for brgy Captain picture

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontbitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }

        $officials = ['punong' => '', 'kagawad' => []];
        foreach ($brgy as $row) {
            $position = strtolower(trim($row['position']));
            $name = strtoupper($row['category']);
            if (strpos($position, 'punong') !== false) {
                $officials['punong'] = $name;
            } else {
                $officials['kagawad'][] = $name;
            }
        }

        $header        = !empty($data) ? $data[0] : null;
        $punong        = $officials['punong'];
        $emergencyname = isset($header['names']) ? $header['names'] : '';
        $emergencyrel  = isset($header['relation'])  ? $header['relation']  : '';
        $emergencyaddr = isset($header['address']) ? $header['address'] : '';
        $emergencytel  = isset($header['contactno'])  ? $header['contactno']  : '';

        $setfill  = array(179, 226, 255);
        $setfillW = array(255, 255, 255);
        $colorRed = array(192, 0, 0);

        PDF::SetTitle($this->modulename . ' - Back');
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        // PDF::SetMargins(40, 40);
        PDF::SetDrawColor(0, 0, 0);

        PDF::SetX(30);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::MultiCell(320, '', '', 'TLR', '', true);

        PDF::SetXY(30, 15);
        
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::SetTextColor($colorRed[0], $colorRed[1], $colorRed[2]);
        PDF::SetFont($fontbold, '', 11);
        $text = '<span color="#004680">"</span> PANTAY-PANTAY NA PAGSESERBISYO <span color="#004680">"</span>';
        PDF::writeHTMLCell(320, 20, '', '', $text, 'LR', 1, true, true, 'C');

        PDF::SetX(30);

        $bodyText  = "This card bears your validated ";
        $bodyText .= "Barangay ID Number (BIN).\n\n";
        $bodyText .= "In case of loss of this card and ";
        $bodyText .= "change in name and address of ";
        $bodyText .= "the bearer, please report ";
        $bodyText .= "immediately to the Barangay ";
        $bodyText .= "Office.";

        PDF::SetTextColor(0, 70, 127);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::MultiCell(10, 70, '', 'L', '', true, 0);
        PDF::MultiCell(160, 70, $bodyText, '', 'J', true, 0);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::MultiCell(150, 70, '', 'R', '', true);

        // photo image para sa brgy captain, temp picture muna
        if (file_exists($rightid)) {
            PDF::Image($rightid, 220, 50, 110, 80);
        }

        PDF::SetX(30);
        PDF::MultiCell(320, 15, "\n", 'LR');
        PDF::SetX(30);
        PDF::MultiCell(320, 15, "\n", 'LR');

        PDF::SetX(30);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(0, 70, 127);
        PDF::MultiCell(160, 10, '', 'L', '', true, 0);
        PDF::MultiCell(160, 10, $punong, 'R', 'C', true);

        PDF::SetX(30);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(160, 10, '', 'L', '', true, 0);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(160, 10, 'Punong Barangay', 'R', 'C', true);

        PDF::SetX(30);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::MultiCell(320, '', '', 'LR', '', true);

        PDF::SetX(30);
        PDF::SetDrawColor(0, 0, 0);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(320, 15, 'IN CASE OF EMERGENCY, PLEASE NOTIFY:', 'TLRB', 'C', true);

        PDF::SetX(30);
        PDF::SetFillColor($setfillW[0], $setfillW[1], $setfillW[2]);
        PDF::SetFont($fontbold, 'I', $fontsize2);

        PDF::SetX(30);
        PDF::MultiCell(10, 15, '', 'TL', '', true, 0);
        PDF::SetTextColor(0, 0, 0); // in black
        PDF::MultiCell(35, 15, 'Name : ', 'T', '', true, 0);
        PDF::SetTextColor(0, 70, 127); // in blue
        PDF::MultiCell(120, 15, $emergencyname, 'T', '', true, 0); 
        PDF::SetTextColor(0, 0, 0); // in black
        PDF::MultiCell(55, 15, 'Relationship : ', 'T', '', true, 0);
        PDF::SetTextColor(0, 70, 127); // in blue
        PDF::MultiCell(100, 15, $emergencyrel, 'TR', '', true); 

        PDF::SetX(30);
        PDF::MultiCell(10, 15, '', 'BL', '', true, 0);
        PDF::SetTextColor(0, 0, 0); // in black
        PDF::MultiCell(40, 15, 'Address : ', 'B', '', true, 0);
        PDF::SetTextColor(0, 70, 127); // in blue
        PDF::MultiCell(115, 15, $emergencyaddr, 'B', '', true, 0); 
        PDF::SetTextColor(0, 0, 0); // in black
        PDF::MultiCell(55, 15, 'Contact No. : ', 'B', '', true, 0);
        PDF::SetTextColor(0, 70, 127); // in blue
        PDF::MultiCell(100, 15, $emergencytel, 'BR', '', true); 

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}