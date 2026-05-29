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

class wr
{
    private $modulename = "BARANGAY WORKING CLEARANCE";
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

    public function generateResult($config)
    {
        $trno = $config['params']['dataid'];

        $query = " select head.trno,head.docno,date(head.dateid) as dateid,head.client,head.clientname,
        head.yourref as rcno,head.ourref as plaissue,
        locl.clearance as purpose,format(head.amount,2) as amount,
        info.addressno,cl.addr,cl.bday,info.civilstatus,cl.sex,cl.position,info.employer,
        head.rem,info.companyaddress,info.editdate,'' as ref
        from lahead as head
        left join client as cl on cl.client = head.client
        left join clientinfo as info on info.clientid = cl.clientid
        left join locclearance as locl on locl.line = head.purposeid
        where doc = 'WR' and head.trno = $trno
        union all
        select head.trno,head.docno,date(head.dateid) as dateid,cl.client,head.clientname,
        head.yourref as rcno,head.ourref as plaissue,
        locl.clearance as purpose,format(head.amount,2) as amount,
        info.addressno,cl.addr,cl.bday,info.civilstatus,cl.sex,cl.position,info.employer,
        head.rem,info.companyaddress,info.editdate,(select h.docno from ladetail as d left join lahead as h on h.trno = d.trno  where d.refx = head.trno  and h.doc = 'CR'
        union all
        select h.docno from gldetail as d left join glhead as h on h.trno = d.trno  where d.refx = head.trno  and h.doc = 'CR') as ref
        from glhead as head
        left join client as cl on cl.clientid = head.clientid
        left join clientinfo as info on info.clientid = head.clientid
        left join locclearance as locl on locl.line = head.purposeid
        where head.doc = 'WR' and head.trno = $trno ";

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
        // var_dump($params['params']['dataparams']);
        $reporttype = $params['params']['dataparams']['reporttype'];
        $brgy = $this->report_default_brgy($params);
        return $this->default_default_PDF($params, $data, $brgy);
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
        PDF::MultiCell(70,20, "RC No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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
        PDF::Image(public_path('images/barangay/1.jpg'), 100, 45, 100, 100);
        PDF::Image(public_path('images/barangay/2.jpg'), 590, 45, 100, 100);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), 'TLR', 'C');
        PDF::SetFont($font, '', 13);
        // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), 'LR', 'C');
        // PDF::MultiCell(null, 0, 'TeleFax:', 'LR', 'C', false);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), 'LR', 'C');
        PDF::MultiCell(0, 20,'TeleFax: ' . strtoupper($headerdata[0]->tel), 'LR', 'C');

        PDF::MultiCell(0, 0, "\n",'LR');

        PDF::SetFont($font, '', 18);


        PDF::MultiCell(0, 0, "\n",'LR');
        PDF::SetFont($fontbold, 'U', 18);
        PDF::MultiCell(null, 0, $this->modulename, 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, 'I', 12);
        PDF::MultiCell(null, 0, '(Pursuant to the provisions of Barangay OrdinanceNo. 04 Series 2008)', 'LR', 'C', false);
        PDF::SetFont($font, '', 5);
        // PDF::MultiCell(180, 0, '', 'T', '', false, 0);
        // PDF::MultiCell(10, 0, '', '', '', false, 0);
        // PDF::MultiCell(530, 0, '', 'T');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(180, 0, '', 'LB', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(530, 0, '', 'R');
    }
    public function default_default_PDF($params, $data,$brgy)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $sbc1 = public_path('images/barangay/2.jpg'); //temp image
        $count = $page = 35;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontbitalic = "";
        $fontitalic = "";
        $fontsize = "11";
        $fontsize2 = "9";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontbitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }
        $this->default_PDF_header($params, $data);

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(720, 0, '', '');

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
        $docno = isset($header['docno']) ? $header['docno'] : '';
        $clientname = isset($header['clientname']) ? $header['clientname'] : '';
        $address = isset($header['addr']) ? $header['addr'] : '';
        $ref = isset($header['ref']) ? $header['ref'] : '';
        $bday = isset($header['bday']) ? $header['bday'] : '';
        $bdayFormatted = (!empty($bday) && $bday != '0000-00-00')? date('F j, Y', strtotime($bday)) : '';
        $dateIssued = (!empty($data[0]['dateid']) && $data[0]['dateid'] != '0000-00-00')? date('F j, Y', strtotime($data[0]['dateid'])): '';
        $status = isset($header['civilstatus']) ? $header['civilstatus'] : '';
        $gender = isset($header['sex']) ? $header['sex'] : '';
        $plaissue = isset($header['plaissue']) ? $header['plaissue'] : '';
        $occupation = isset($header['position']) ? $header['position'] : '';
        $client = isset($header['client']) ? $header['client'] : '';
        $companyaddress = isset($header['companyaddress']) ? $header['companyaddress'] : '';
        $employer = isset($header['employer']) ? $header['employer'] : '';
        $editdate = isset($header['editdate']) ? $header['editdate'] : '';
        $editdateFormatted = (!empty($editdate) && $editdate != '0000-00-00') ? date('F j, Y', strtotime($editdate))  : '';


        $arr_docno = $this->reporter->fixcolumn([$docno], '45', 0);
        $arr_clientname = $this->reporter->fixcolumn([$clientname], '45', 0);
        $arr_ref = $this->reporter->fixcolumn([$ref], '15', 0);
        $arr_address = $this->reporter->fixcolumn([$address], '45', 0);
        $arr_bday = $this->reporter->fixcolumn([$bday], '45', 0);
        $arr_bdayFormatted = $this->reporter->fixcolumn([$bdayFormatted], '45', 0);
        $arr_status = $this->reporter->fixcolumn([$status], '45', 0);
        $arr_gender = $this->reporter->fixcolumn([$gender], '45', 0);
        $arr_plaissue = $this->reporter->fixcolumn([$plaissue], '45', 0);
        $arr_occupation = $this->reporter->fixcolumn([$occupation], '45', 0);
        $arr_client = $this->reporter->fixcolumn([$client], '45', 0);
        $arr_companyaddress = $this->reporter->fixcolumn([$companyaddress], '45', 0);
        $arr_employer = $this->reporter->fixcolumn([$employer], '45', 0);
        $arr_editdate = $this->reporter->fixcolumn([$editdateFormatted], '45', 0);
        

        $color   = array(36, 59, 117);      // text color
        $setfill = array(179, 226, 255);    // fill color
        // $setfill = array(105, 176, 255); 
        $setfillB = array(0, 129, 194);  // left panel
        // PDF::SetTextColor($color[0], $color[1], $color[2]);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetTextColor(255,255,255);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'TL', '', true, 0);
        PDF::MultiCell(510, 0, '', 'RT', '', true, 0);
        PDF::MultiCell(10, 0, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 0,  $officials['punong'], 'LR', 'C', true, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::MultiCell(510, 0, '', 'R', '', true,0);
        PDF::MultiCell(10, 0, '', 'R', '', false);
        
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 0, '', '', '', false, 0);  // space

        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::SetFont($fontbitalic, '', $fontsize);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(340, 0, 'THIS WORKING CLEARANCE IS HEREBY GRANTED TO :', '', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(70, 0, 'Employee No. :', '', '', true, 0);
        PDF::MultiCell(100, 0, ''. (isset($arr_client[0]) ? $arr_client[0] : ''), 'R', '', true,0);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 0, 'Punong Barangay', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 0, '', '', '', false, 0);  // space

        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 0, '', 'L', 'C', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', true, 0); //space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(330, 0, '', 'R', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0); 
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0); // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'L', '', true, 0);
        PDF::MultiCell(340, 0, '', '', '', true, 0);
        PDF::MultiCell(70, 0, '', '', '', true, 0);
        PDF::MultiCell(100, 0, '', 'R', '', true,0);    
        PDF::MultiCell(10, 0, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 15, '', '', '',false, 0); // space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Name', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_clientname[0]) ? $arr_clientname[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(140, 15, '', 'TLR', '', true, 0);
        PDF::MultiCell(15, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(180, 15, 'MGA KAGAWAD', 'LR', 'C', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Address', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_address[0]) ? $arr_address[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(140, 15, '', 'LR', '', true, 0);
        PDF::MultiCell(15, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(100, 15, '', '', '', true, 0);
        PDF::MultiCell(240, 15, '', '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(140, 15, '', 'LR', '', true, 0);
        PDF::MultiCell(15, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15,  $officials['kagawad'][0], 'LR', 'C', true, 0);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Date of Birth', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_bdayFormatted[0]) ? $arr_bdayFormatted[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(140, 15, '', 'LR', '', true, 0);
        PDF::MultiCell(15, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Civil Status', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_status[0]) ? $arr_status[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(140, 15, '', 'LR', '', true, 0);
        PDF::MultiCell(15, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15,  $officials['kagawad'][1], 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Gender', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_gender[0]) ? $arr_gender[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(140, 15, '', 'LRB', '', true, 0);
        PDF::MultiCell(15, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);
        

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0);//space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(340, 15, '', '', '', true, 0);
        PDF::MultiCell(170, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15,  $officials['kagawad'][2], 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Company / Employer', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_employer[0]) ? $arr_employer[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::MultiCell(140, 15, '', '', '', true, 0);
        PDF::MultiCell(15, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Company Address', '', '', true, 0);
        PDF::MultiCell(340, 15, ': ' . (isset($arr_companyaddress[0]) ? $arr_companyaddress[0] : ''), '', '', true, 0);
        PDF::MultiCell(55, 15, '', '', '', true, 0);
        PDF::MultiCell(15, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15,  $officials['kagawad'][3], 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Position / Job', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_occupation[0]) ? $arr_occupation[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::MultiCell(140, 15, '', '', '', true, 0);
        PDF::MultiCell(15, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::MultiCell(340, 15, '', '', '', true, 0);
        PDF::MultiCell(160, 15, '', '', '', true, 0);
        PDF::MultiCell(10, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15,  $officials['kagawad'][4], 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Date Issued', '', '', true, 0);
        PDF::MultiCell(240, 15, ': ' . (isset($arr_editdate[0]) ? $arr_editdate[0] : ''), '', '', true, 0);
        PDF::MultiCell(15, 15, '', '', '', true, 0);
        PDF::MultiCell(140, 15, '', '', '', true, 0);
        PDF::MultiCell(15, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'W.C No.', '', '', true, 0);
        PDF::MultiCell(260, 15, ': ' . (isset($arr_docno[0]) ? $arr_docno[0] : ''), '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(130, 15, '', 'TLR', '', true, 0);
        PDF::MultiCell(20, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15,  $officials['kagawad'][5], 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'O.R No.', '', '', true, 0);
        PDF::MultiCell(260, 15, ': ' . (isset($arr_ref[0]) ? $arr_ref[0] : ''), '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(130, 15, '', 'LR', '', true, 0);
        PDF::MultiCell(20, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, 'Date Expiration', '', '', true, 0);
        PDF::MultiCell(260, 15, ': ' . (isset($arr_editdate[0]) && $arr_editdate[0] !== '' ? date('F j, Y', strtotime($arr_editdate[0] . ' +1 year')) : ''), '', '', true, 0);
        // PDF::MultiCell(260, 15, ': Sample', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(130, 15, '', 'LR', '', true, 0);
        PDF::MultiCell(20, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(100, 15, '', '', '', true, 0);
        PDF::MultiCell(260, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(130, 15, '', 'LR', '', true, 0);
        PDF::MultiCell(20, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(170, 15, '', 'B', '', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(190, 15, '', '', '', true, 0);
        PDF::SetDrawColor(0, 0, 0);
        PDF::MultiCell(130, 15, '', 'LRB', '', true, 0);
        PDF::MultiCell(20, 15, '', 'LR', '', true,0);
        PDF::SetDrawColor(25, 119, 181);
        PDF::MultiCell(10, 15, '', 'LR', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::SetDrawColor(0, 0, 0); //line color
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(170, 15, "Applicant's Signature", 'T', 'C', true, 0);
        PDF::MultiCell(190, 15, '', '', '', true, 0);
        PDF::MultiCell(130, 15, 'Right Thumb Mark', '', 'C', true, 0);
        PDF::SetDrawColor(25, 119, 181); //line color
        PDF::MultiCell(20, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 15,  $officials['secretary'], 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 15, '', '', '', false, 0); //space?
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 15, '', 'L', '', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(400, 15, 'This Working Clearance shall be deemed cancelled and rendered null and void upon its expiration', '', '', true, 0);
        PDF::MultiCell(100, 15, '', '', '', true, 0);
        PDF::MultiCell(10, 15, '', 'R', '', true,0);
        PDF::MultiCell(10, 15, '', 'R', '', false);
        



        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 0, 'Punong Secretary', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0); //space
        PDF::SetFillColor($setfill[0], $setfill[1], $setfill[2]);
        PDF::MultiCell(10, 0, '', 'LB', '', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', 'B', '', true, 0);
        PDF::MultiCell(330, 0, '', 'B', '', true, 0);
        PDF::MultiCell(130, 0, '', 'B', '', true, 0);
        PDF::MultiCell(20, 0, '', 'RB', '', true,0);
        PDF::MultiCell(10, 0, '', 'R', '', false);


        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0); //space
        PDF::SetFillColor(255, 255, 255);
        PDF::MultiCell(10, 0, '', '', '', true, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', '', '', true, 0);
        PDF::MultiCell(330, 0, '', '', '', true, 0);
        PDF::MultiCell(130, 0, '', '', '', true, 0);
        PDF::MultiCell(20, 0, '', '', '', true,0);
        PDF::MultiCell(10, 0, '', 'R', '', false);
        
        
        
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(180, 0, $officials['treasurer'], 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(370, 0, 'Note: NOT VALID without Barangay Seal', '', '', false, 0);
        PDF::MultiCell(130, 0, '', '', '', false, 0);
        PDF::MultiCell(20, 0, '', 'R', false);

        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::SetTextColor(255,255,255);
        PDF::MultiCell(180, 0, 'Barangay Treasurer', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        // PDF::MultiCell(30, 0, '', '', '', false, 0);
        // PDF::MultiCell(340, 0, 'HON. MA. GANDA A.YAP', '', '', false, 0);
        // PDF::MultiCell(130, 0, '', '', '', false, 0);
        // PDF::MultiCell(20, 0, '', '', false);

        PDF::SetTextColor(0,0,0);
        PDF::MultiCell(520, 0, $officials['punong'], 'R', 'C', false);
        PDF::SetFillColor($setfillB[0], $setfillB[1], $setfillB[2]);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LR', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(520, 0, 'Punong Barangay', 'R', 'C', false);

        //temp image - Punong Barangay photo
        if (file_exists($sbc1)) {
            PDF::Image($sbc1, 570, 522, 50, 50);
        }

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
        PDF::SetFont($fontbitalic, '', $fontsize2);
        PDF::MultiCell(180, 0, '', 'LRB', 'C', true, 0);
        PDF::SetFont($font, '', $fontsize2);
        PDF::MultiCell(10, 0, '', 'B', '', false, 0);
        PDF::MultiCell(10, 0, '', 'B', '', false, 0);
        PDF::SetFont($fontitalic, '', $fontsize2);
        PDF::MultiCell(30, 0, '', 'B', '', false, 0);
        PDF::MultiCell(340, 0, '', 'B', '', false, 0);
        PDF::MultiCell(130, 0, '', 'B', '', false, 0);
        PDF::MultiCell(20, 0, '', 'RB', false);




        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}