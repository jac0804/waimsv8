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

class bi
{
    private $modulename = "INFRASTRUCTURE CLEARANCE";
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

        $fields = ['radioprint', 'radioreporttype',  'approved', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'approved.label', 'Punong Barangay');
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);

        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '0' as reporttype,
            '' as prepared,
            '$approved' as approved,
            'default' as reporttype
            "
        );
    }

    public function generateResult($config)
    {
        $trno = $config['params']['dataid'];

        $query = "
        select info.sentence1,info.sentence2,info.sentence3,info.bullet1,info.bullet2,info.bullet3,info.bullet4,info.bullet5,info.bullet6,info.bullet7 from lahead as head
        left join client as cl on cl.client = head.client
        left join clientinfo as info on info.clientid = cl.clientid
        where head.trno = $trno
        union all

        select info.sentence1,info.sentence2,info.sentence3,info.bullet1,info.bullet2,info.bullet3,info.bullet4,info.bullet5,info.bullet6,info.bullet7 from glhead as head
        left join client as cl on cl.clientid = head.clientid
        left join clientinfo as info on info.clientid = cl.clientid
        where head.trno = $trno";

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
        PDF::AddPage('p', [816, 1344]);
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
        PDF::MultiCell(60, 20, "Full Name : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(400, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 20, "RC No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(400, 20, (isset($data[0]['rcno']) ? $data[0]['rcno'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(80, 20, "Place Issue: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['plaissue']) ? $data[0]['plaissue'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 20, "Purpose: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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
        PDF::MultiCell(140, 0, "POSTDATE", '', 'L', false, 0);
        PDF::MultiCell(140, 0, "ACCOUNT", '', 'L', false, 0);
        PDF::MultiCell(140, 0, "DB", '', 'R', false, 0);
        PDF::MultiCell(140, 0, "CR", '', 'R', false);


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
        PDF::MultiCell(600, 0, '', '');


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

                        PDF::MultiCell(140, 15, ' ' . (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(140, 15, ' ' . (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(140, 15, ' ' . (isset($arr_db[$r]) ? $arr_db[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(140, 15, ' ' . (isset($arr_cr[$r]) ? $arr_cr[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
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
        // $width = 800; $height = 1000;

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
        // PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        // PDF::SetFont($fontbold, '', 14);
        // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        // PDF::SetFont($font, '', 13);
        // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 18);
    }
    public function default_default_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $approved = $params['params']['dataparams']['approved'];

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontbitalic = "";
        $fontitalic = "";
        $fontsize = "10";
        $fontsize2 = "9";
        if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
            $fontbitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbi.ttf');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesi.ttf');
        }
        $this->default_PDF_header($params, $data);

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($font, 'BI', 20);
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell('700', 0, 'C E R T I F I C A T I O N', '', 'C', false);
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'I', 16);
        PDF::MultiCell('700', 0, 'TO WHOM IT MAY CONCERNS:', '', 'l', false);
        PDF::MultiCell(0, 0, "\n");

        // sentence1
        PDF::SetFont($font, 'I', 14);
        PDF::MultiCell('700', 0, (isset($data[0]['sentence1']) ? $data[0]['sentence1'] : ''), '', 'l', false);
        PDF::MultiCell('700', '20', '', '', 'l', false);

        // bullets
        $bullets = ['bullet1', 'bullet2', 'bullet3', 'bullet4', 'bullet5', 'bullet6', 'bullet7'];
        foreach ($bullets as $bullet) {
            if (!empty($data[0][$bullet])) {
                PDF::MultiCell('30', 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, 'I', 14);
                PDF::MultiCell('20', 0, '*', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, 'I', 14);
                PDF::MultiCell('650', 0, $data[0][$bullet], '', 'l', false);
                PDF::MultiCell(0, 0, "\n");
            }
        }

        // sentence2
        PDF::MultiCell('700', '10', '', '', 'l', false);
        PDF::MultiCell('700', 0, (isset($data[0]['sentence2']) ? $data[0]['sentence2'] : ''), '', 'l', false);
        PDF::MultiCell(0, 0, "\n");

        // sentence3
        PDF::MultiCell('700', 0, (isset($data[0]['sentence3']) ? $data[0]['sentence3'] : ''), '', 'l', false);
        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell('700', '130', '', '', 'l', false);

        PDF::SetFont($font, 'BI', 16);
        // PDF::MultiCell('700', 0, 'HON. MA. GANDA A. YAP', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell('500', 0, '', '', 'C', false, 0);
        PDF::MultiCell('200', 0, $approved, '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, 'I', 10);
        PDF::MultiCell('500', 0, '*not valid without official seal*', '', 'l', false, 0);
        PDF::SetFont($font, 'I', 16);
        PDF::MultiCell('200', 0, 'Punong Barangay', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(0, 0, "\n");


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
