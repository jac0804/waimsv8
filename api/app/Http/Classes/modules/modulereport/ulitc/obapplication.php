<?php

namespace App\Http\Classes\modules\modulereport\ulitc;

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

class obapplication
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
        // $fields = ['radioprint','prepared','approved','received','print'];
        $fields = ['radioprint', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'orange']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $adminid = $config['params']['adminid'];
        $line = $config['params']['trno'];
        $clientname = $this->coreFunctions->datareader("select clientname as value from client where clientid = '$adminid' ");
        $approvedby = $this->coreFunctions->datareader("
        select client.clientname as value from obapplication  as ob
        left join client on client.email = ob.approvedby and client.email <> ''
        where ob.line = '$line'");
        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '$clientname' as requested,
            '$approvedby' as approved,
            '' as received
        "
        );
    }

    public function report_default_query($config)
    {

        $trno = $config['params']['dataid'];

        $query = "select obapp.line as trno, obapp.line as clientid,
                  cl.client, cl.clientname, cl.clientid as empid,
                  obapp.type, date(obapp.dateid) as dateid,time(dateid) as itime, obapp.rem,dept.clientname as department,
                  jt.jobtitle,date(obapp.scheddate) as scheddate,obapp.createdate,
                  case
                    when obapp.status = 'E' then 'ENTRY'
                  END as jstatus
                  from obapplication as obapp
                  left join employee as emp on emp.empid = obapp.empid
                  left join jobthead as jt on jt.line = emp.jobid 
                  left join client as cl on cl.clientid = emp.empid
                  left join client as dept on dept.clientid = emp.deptid
                  where obapp.line = $trno";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn
    public function obdetail($trno)
    {
        $query = "select date_format(leadto,'%H:%i') as leadto, date_format(leadfrom,'%H:%i') as leadfrom,purpose,destination,contact,line from obdetail where trno = '" . $trno . "' order by line asc ";
        return $this->coreFunctions->opentable($query);
    }


    public function reportplotting($config, $data)
    {
        $data = $this->report_default_query($config);
        return $this->rpt_obapplication_PDF($config, $data);
    }

    public function rpt_default_header_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $fontsize = "11";
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
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, "\n\n\n");

        // for image
        // file path
        // horizontal position (mm or user units)
        // vertical position
        // width of the image
        // height of the image

        PDF::Image(public_path('images/ulitc/united_limsun.png'), 50, 65, 150, 70);
        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 16.5);
        PDF::MultiCell(170, 25, "", 'TL', 'L', false, 0);
        PDF::SetFont($fontbold, '', 16.5);
        PDF::MultiCell(450, 25, "HUMAN RESOURCES ADMIN. DEPARTMENT", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 25, "Copies", 'TLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        // PDF::MultiCell(100, 25, "PART NO.", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        // PDF::SetFont($fontbold, '', 10);
        // PDF::MultiCell(170, 5, "", 'LR', 'L', false, 0);
        // PDF::SetFont($fontbold, '', 10);
        // PDF::MultiCell(450, 5, "", '', 'C', false, 0);
        // PDF::SetFont($font, '', 10);
        // PDF::MultiCell(100, 5, "HRAD", 'LR', 'L', false);
        //      PDF::MultiCell(100, 25, "Requestor", 'BLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(170, 25, "", 'LR', 'L', false, 0);
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(450, 25, "REQUEST FOR AN OFFICIAL BUSINESS TRIP", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 25, "HRAD", 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 10);
        PDF::MultiCell(170, 0, "", 'BLR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(450, 0, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "Requestor", 'BLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        // PDF::SetFont($fontbold, '', 15);
        // PDF::MultiCell(720, 20, "", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(45, 25, "Name : ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(260, 25, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 25, "Department : ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(195, 25, (isset($data[0]['department']) ? $data[0]['department'] : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true); //department
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(45, 25, "Date : ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(85, 25, (isset($data[0]['createdate']) ? date("m-d-Y", strtotime($data[0]['createdate'])) : ''), 'BR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true); //date


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 25, "Position : ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(230, 25, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true); //Position

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(125, 25, "Date of Official Business : ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        // PDF::MultiCell(120, 25, "Date of Official Business : ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11); //scheddate
        PDF::MultiCell(280, 25, (isset($data[0]['scheddate']) ? date("m-d-Y", strtotime($data[0]['scheddate'])) : ''), 'BR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true); //Date of Official Business


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(720, 5, "", 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 12);
        PDF::MultiCell(155, 20, "Purpose of Travel", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(155, 20, "Destination", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(140, 20, "Time", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(135, 20, "Contact Person", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(135, 20, "Client Signature", 'TLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 12);
        PDF::MultiCell(155, 15, "", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(155, 15, "", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 15, "From", 'BTL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 15, "To", 'BTL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(135, 15, "", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(135, 15, "", 'BLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }

    public function rpt_obapplication_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $fontsize = "9";
        $count = 35;
        $page = 35;
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->rpt_default_header_PDF($config, $data);

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(100, 0, "", 'T', 'L', false, 0);
        // PDF::MultiCell(660, 0, "", 'T', 'L', false);
        $addline = 0;
        $fixline = 6;
        $line = 0;
        $obdetail = $this->obdetail($data[0]['trno']);
        for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;
            foreach ($obdetail as $key => $value) {
                $arr_purpose = $this->reporter->fixcolumn([$value->purpose], '38', 0);
                $arr_destination = $this->reporter->fixcolumn([$value->destination], '38', 0);
                $arr_leadfrom = $this->reporter->fixcolumn([$value->leadfrom], '20', 0);
                $arr_leadto = $this->reporter->fixcolumn([$value->leadto], '20', 0);
                $arr_contact = $this->reporter->fixcolumn([$value->contact], '25', 0);

                if ($line == 0) {
                    $line = $value->line;
                }

                $maxrow = $this->othersClass->getmaxcolumn([$arr_purpose, $arr_destination, $arr_leadfrom, $arr_leadto, $arr_contact]);
                PDF::SetFont($font, '', $fontsize);
                for ($r = 0; $r < $maxrow; $r++) {

                    if ($maxrow != ($r + 1)) {
                        PDF::MultiCell(155, 20, isset($arr_purpose[$r]) ? $arr_purpose[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(155, 20, isset($arr_destination[$r]) ? $arr_destination[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(70, 20, isset($arr_leadfrom[$r]) ? $arr_leadfrom[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(70, 20, isset($arr_leadto[$r]) ? $arr_leadto[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(135, 20, isset($arr_contact[$r]) ? $arr_contact[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(135, 20, '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                    } else {
                        PDF::MultiCell(155, 20, isset($arr_purpose[$r]) ? $arr_purpose[$r] : '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(155, 20, isset($arr_destination[$r]) ? $arr_destination[$r] : '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(70, 20, isset($arr_leadfrom[$r]) ? $arr_leadfrom[$r] : '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(70, 20, isset($arr_leadto[$r]) ? $arr_leadto[$r] : '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(135, 20, isset($arr_contact[$r]) ? $arr_contact[$r] : '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(135, 20, '', 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                    }
                    $addline++;
                }
                $line = $value->line;
            }
            if ($addline < $fixline) {
                $this->addline($addline, $fixline);
            }

            PDF::SetFont($font, '', 4);
            PDF::MultiCell(720, 5, "", 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            $remrow = 1;

            if (!empty($data[$i]['rem'])) {
                $arr_rem = $this->reporter->fixcolumn([$data[$i]['rem']], '120', 0);
                $remrow = $this->othersClass->getmaxcolumn([$arr_rem]);
                for ($c = 0; $c < $remrow; $c++) {
                    if ($c == 0) {
                        PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::SetFont($fontbold, '', 11);
                        PDF::MultiCell(50, 20, "Remarks : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::SetFont($font, '', 11);
                        PDF::MultiCell(665, 20, "" . (isset($arr_rem[$c]) ? $arr_rem[$c] : ''), 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                    } else {

                        PDF::SetFont($font, '', 11);
                        PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(50, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                        PDF::MultiCell(665, 20, (isset($arr_rem[$c]) ? $arr_rem[$c] : ''), 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                    }
                }
            } else {
                PDF::SetFont($fontbold, '', 11);
                PDF::MultiCell(5, 20, "", 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(715, 20, "Remarks : ", 'TR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            }
        }



        // PDF::MultiCell(715, 20, "", 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);



        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 20, "", 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(115, 20, "Guidelines : ", 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(600, 20, "", 'TR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(715, 20, '1. Filling of ROBT should be at least one (1) day in advance and must be approved Department Head', 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(715, 20, '2. Unfiled ROBT will be considered absent and subject to salary deduction', 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(715, 20, '3. Late filling ROBT will be subject for approval of the COO with the justification from concerned employee noted by the Dept. Head.', 'RB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 5, '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 20, "", 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(305, 20, "Requested by : ", 'TR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(205, 20, "Approved by : ", 'TR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(205, 20, "Received by : ", 'TR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(5, 20, "", 'BL', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(305, 20, "" . $config['params']['dataparams']['requested'], 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(205, 20, "" . $config['params']['dataparams']['approved'], 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(205, 20, "" . $config['params']['dataparams']['received'], 'BR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(310, 15, "Employee", 'LBR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(205, 15, "Department Head", 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(205, 15, "HRAD Department", 'BR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        // PDF::MultiCell(0, 0, "\n\n");

        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(100, 0, "Remarks : ", '', 'L', false, 0);
        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L', false);

        // PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
        // PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
        // PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

        // PDF::MultiCell(0, 0, "\n\n");
        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(266, 0, $config['params']['dataparams']['requested'], '', 'L', false, 0);
        // PDF::MultiCell(266, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
        // PDF::MultiCell(266, 0, $config['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function addline($addline, $fixline)
    {
        $fontsize = "9";
        $count = 35;
        $page = 35;
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $blankline = $fixline - $addline;

        for ($i = 0; $i < $blankline; $i++) {
            if ($blankline != $i + 1) {
                PDF::SetFont($font, '', 12);
                PDF::MultiCell(155, 20, '', 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(155, 20, '', 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(135, 20, '', 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(135, 20, '', 'TLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            } else {
                PDF::SetFont($font, '', 12);
                PDF::MultiCell(155, 20, '', 'TBL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(155, 20, '', 'TBL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'TBL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'TBL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(135, 20, '', 'TBL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(135, 20, '', 'TBLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            }
        }
    }
    public function notallowtoprint($config, $msg)
    {
        $font = "";
        $fontbold = "";
        $fontsize = 20;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(20, 20);
        PDF::AddPage('p', [800, 1000]);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, $msg, '', 'L', false, 1);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
