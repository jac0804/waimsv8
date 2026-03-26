<?php

namespace App\Http\Classes\modules\modulereport\camera;

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
use Carbon\Carbon;

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

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'print'];
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
        $type = $this->coreFunctions->datareader("select type as value from obapplication where line = '$line' ");
        $approvedby = $this->coreFunctions->datareader("
        select client.clientname as value from obapplication  as ob
        left join client on client.email = ob.approvedby and client.email <> ''
        where ob.line = '$line'");

        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '$type' as reporttype,
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
                  obapp.type, date(obapp.dateid) as dateid,obapp.dateid as datein,time(dateid) as itime,date(obapp.dateid2) as dateid2,obapp.dateid2 as dateout,time(dateid2) as stime, obapp.rem as purpose,obapp.location,
                  dept.clientname as department,emp.jobtitle,date(obapp.scheddate) as scheddate,obapp.picture,
                  date(obapp.createdate) as createdate,divi.divname,
                  case
                    when obapp.status = 'E' then 'ENTRY'
                  END as jstatus
                  from obapplication as obapp
                  left join employee as emp on emp.empid = obapp.empid
                  left join client as cl on cl.clientid = emp.empid
                  left join client as dept on dept.clientid = emp.deptid
                  left join division as divi on divi.divid = emp.divid
                  where obapp.line = $trno";

        return $this->coreFunctions->opentable($query);
    } //end fn
    public function obdetail($trno)
    {
        $query = "select date_format(leadto,'%H:%i') as leadto, date_format(leadfrom,'%H:%i') as leadfrom,purpose,destination,contact,line from obdetail where trno = '" . $trno . "' ";
        return $this->coreFunctions->opentable($query);
    }


    public function reportplotting($config, $data)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        $data = $this->report_default_query($config);
        if ($reporttype == 'Off-setting') {
            return $this->rpt_obapplication_PDF($config, $data);
        } else {
            return $this->rpt_ob_offset_PDF($config, $data);
        }
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

        // PDF::Image(public_path('images/ulitc/united_limsun.png'), 50, 65, 150, 70);
        // PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(170, 25, "", 'TL', 'L', false, 0);
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(720, 25, "Human Resources Department", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 15);

        PDF::MultiCell(720, 25, "OFFICIAL BUSINESS/TRAVEL PERMIT", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(115, 25, "NAME OF EMPLOYEE : ", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(300, 25, (isset($data[0]->clientname) ? $data[0]->clientname : ''), 'TBR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        #jobtitle
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(55, 25, "POSITION: ", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(240, 25, (isset($data[0]->jobtitle) ? $data[0]->jobtitle : ''), 'TBR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 25, "COMPANY: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(355, 25, (isset($data[0]->divname) ? $data[0]->divname : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        #jobtitle
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(55, 25, "AGENCY: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(240, 25, (isset($data[0]->department) ? $data[0]->department : ''), 'TBR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(90, 25, "DATE PREPARED : ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(325, 25, (isset($data[0]->createdate) ? $data[0]->createdate : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        #jobtitle
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(155, 25, "TIME OUT AT THE HEAD OFFICE: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(140, 25, '', 'BR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 12);
        PDF::MultiCell(100, 20, "DATE", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(155, 20, "PURPOSE/S", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 20, "LOCATION/DESTINATION", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 20, "TIME IN", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 20, "TIME OUT", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(160, 20, "ATTACHMENT", 'LR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 12);
        PDF::MultiCell(100, 15, "", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(155, 15, "", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 15, "", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(140, 15, "AT THE PLACE VISITED", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(160, 15, "", 'BLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
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
        $fontitalic = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }
        $this->rpt_default_header_PDF($config, $data);

        $addline = 0;
        $fixline = 8;

        if (!empty($data)) {
            if (isset($data[0]->picture)) {
                PDF::Image(public_path($data[0]->picture), 602, 237, 155, 155);
            }
        }

        $maxrow = 1;
        foreach ($data as $key => $value) {
            $arr_dateid = $this->reporter->fixcolumn([$value->dateid], '38', 0);
            $arr_purpose = $this->reporter->fixcolumn([$value->purpose], '38', 0);
            $arr_location = $this->reporter->fixcolumn([$value->location], '20', 0);
            $arr_itime = $this->reporter->fixcolumn([$value->itime], '20', 0);
            $arr_stime = $this->reporter->fixcolumn([$value->stime], '25', 0);


            $maxrow = $this->othersClass->getmaxcolumn([$arr_purpose, $arr_dateid, $arr_location, $arr_itime, $arr_stime]);

            PDF::SetFont($font, '', $fontsize);
            for ($r = 0; $r < $maxrow; $r++) {

                PDF::MultiCell(100, 20, isset($arr_dateid[$r]) ? $arr_dateid[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(155, 20, isset($arr_purpose[$r]) ? $arr_purpose[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(165, 20, isset($arr_location[$r]) ? $arr_location[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20, isset($arr_itime[$r]) ? $arr_itime[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20, isset($arr_stime[$r]) ? $arr_stime[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(160, 20, '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                $addline++;
            }
            if ($addline < $fixline) {
                $this->addline($addline, $fixline);
            }
        }
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(260, 20, "Filed by : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 20, "Approved by : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 20, "Verified/Noted By : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 20, "ARRIVAL TIME AT", 'TLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(5, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(260, 20, "" . $config['params']['dataparams']['requested'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 20, "" . $config['params']['dataparams']['approved'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 20, "" . $config['params']['dataparams']['received'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, "THE OFFICE", 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(260, 15, "Employee's Signature", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 15, "Immediate Supervisor", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 15, "H/R Suppervisor/Manager", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 15, "SECURITY GUARD", 'BLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);



        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontitalic, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 15, "Notes: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(670, 15, "This OBT form is subject for approval and must be submitted before the official business will be rendered, and must be return to HR after the", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        // PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(670, 15, "OB was rendered and daily signed by the person visited.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
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
            PDF::SetFont($font, '', 12);

            if ($blankline != $i + 1) {
                PDF::MultiCell(100, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(155, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(165, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(160, 20, '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            } else {
                PDF::MultiCell(100, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(155, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(165, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 20,  '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(160, 20, '', 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            }
        }
    }
    public function rpt_offset_header_PDF($config, $data)
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

        // PDF::Image(public_path('images/ulitc/united_limsun.png'), 50, 65, 150, 70);
        // PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(170, 25, "", 'TL', 'L', false, 0);
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(720, 25, "Human Resources Department", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 15);

        PDF::MultiCell(720, 25, "OFF-SETTING FORM", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(115, 25, "NAME OF EMPLOYEE : ", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(300, 25, (isset($data[0]->clientname) ? $data[0]->clientname : ''), 'TBR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        #jobtitle
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(55, 25, "POSITION: ", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(240, 25, (isset($data[0]->jobtitle) ? $data[0]->jobtitle : ''), 'TBR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 25, "COMPANY: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(355, 25, (isset($data[0]->divname) ? $data[0]->divname : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        #jobtitle
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 25, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(55, 25, "DATE PREPARED: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(240, 25, (isset($data[0]->createdate) ? $data[0]->createdate : ''), 'TBR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(260, 30, "DATE FOR OFF-SETTING HOURS (Excess working hours, Work during Rest Days or Holidays)", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 30, "TIME IN ", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 30, "TIME OUT", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(80, 30, "TOTAL HOURS", 'TBL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(220, 30, "DATE OF OFFSET", 'TBLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }
    public function rpt_ob_offset_PDF($config, $data)
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
        $fontitalic = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }
        $this->rpt_offset_header_PDF($config, $data);

        $addline = 0;
        $fixline = 5;


        $start =
            $maxrow = 1;
        $hours = 0;
        foreach ($data as $key => $value) {


            $arr_scheddate = $this->reporter->fixcolumn([date("F j, Y", strtotime($value->scheddate))], '38', 0);
            $arr_purpose = $this->reporter->fixcolumn([$value->purpose], '38', 0);
            $arr_itime = $this->reporter->fixcolumn([$value->itime], '20', 0);
            $arr_stime = $this->reporter->fixcolumn([$value->stime], '25', 0);

            if ($value->datein != null) {
                $start = Carbon::parse($value->datein);
                if ($value->dateout != null) {
                    $end   = Carbon::parse($value->dateout);
                    $hours = $end->diffInHours($start);
                }
            }



            $maxrow = $this->othersClass->getmaxcolumn([$arr_purpose, $arr_scheddate, $arr_itime, $arr_stime]);

            PDF::SetFont($font, '', $fontsize);
            for ($r = 0; $r < $maxrow; $r++) {

                PDF::MultiCell(260, 20, isset($arr_scheddate[$r]) ? $arr_scheddate[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20, isset($arr_itime[$r]) ? $arr_itime[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20, isset($arr_stime[$r]) ? $arr_stime[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20, ($hours != 0 ? $hours . 'Hours' : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(220, 20, isset($arr_purpose[$r]) ? $arr_purpose[$r] : '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                $addline++;
            }
            if ($addline < $fixline) {
                $this->addline_offset($addline, $fixline);
            }
        }
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(720, 20, "", 'T', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(5, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(260, 20, "Filed by : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 20, "Approved by : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 20, "Verified/Noted By : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 20, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(5, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(260, 20, "" . $config['params']['dataparams']['requested'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 20, "" . $config['params']['dataparams']['approved'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 20, "" . $config['params']['dataparams']['received'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(260, 15, "Employee's Signature", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 15, "Immediate Supervisor", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(165, 15, "H/R Suppervisor/Manager", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 15, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);



        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontitalic, '', $fontsize);
        PDF::MultiCell(5, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 15, "Notes: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(685, 15, "Kindly attached supporting documents e.g OB Form, OT form or approval form immediate superior", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function addline_offset($addline, $fixline)
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
            PDF::SetFont($font, '', 12);

            // PDF::MultiCell(260, 20, isset($arr_scheddate[$r]) ? $arr_scheddate[$r] : '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(80, 20, isset($arr_itime[$r]) ? $arr_itime[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(80, 20, isset($arr_stime[$r]) ? $arr_stime[$r] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(80, 20, ($hours != 0 ? $hours : '') . ' Hours', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(220, 20, isset($arr_purpose[$r]) ? $arr_purpose[$r] : '', 'L', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

            if ($blankline != $i + 1) {
                PDF::MultiCell(260, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20,  '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(220, 20, '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            } else {
                PDF::MultiCell(260, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 20,  '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(220, 20, '', 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
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
