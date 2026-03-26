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

class otapplicationadv
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
        $approvedby = $this->coreFunctions->datareader("
        select client.clientname as value from otapplication  as ob
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

        $query = "select client.clientname as empname,emp.jobtitle,time(ot.ottimein) as timein,time(ot.ottimeout) as timeout,date(ot.scheddate) as scheddate,
        ot.apothrs,ot.rem,app.clientname as approver
        from otapplication as ot
        left join client on client.clientid = ot.empid
        left join employee as emp on emp.empid = ot.empid
        left join division as divi on divi.divid = emp.divid
        left join client as app on app.email = ot.approvedby and app.email <> ''
        where ot.line = $trno";

        return $this->coreFunctions->opentable($query);
    } //end fn


    public function reportplotting($config, $data)
    {
        $data = $this->report_default_query($config);
        return $this->rpt_otapplication_PDF($config, $data);
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
        // PDF::SetFont($fontbold, '', 13);
        PDF::SetFont('helvetica', 'B', 10);
        PDF::MultiCell(720, 20, 'OVERTIME (OT) FORM', 'LBTR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(5, 20, '', 'LT', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 20, 'NAME : ', 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(315, 20, isset($data[0]->empname) ? $data[0]->empname : '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(360, 20, '', 'LTR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(5, 20, '', 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, 'DATE OF OVERTIME: ', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(245, 20,  isset($data[0]->scheddate) ? $data[0]->scheddate : '', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(5, 20, '', 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 20, 'POSITION: ', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(295, 20, isset($data[0]->jobtitle) ? $data[0]->jobtitle : '', 'TRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(0, 0, "\n");




        PDF::SetFont('helvetica', '', 10);

        // Table column widths
        $w1 = 180; // WORK SCHEDULE
        $w2 = 180; // OVERTIME HOURS
        $w3 = 100; // NO. OF OT HRS RENDERED
        $w4 = 260; // REASON(S) OF OVERTIME

        // Row height
        $h = 10;

        // --- HEADER ROW ---
        PDF::MultiCell($w1, $h * 2, "WORK SCHEDULE", 'TLBR', 'C', false, 0, '', '', true, 0, false, true, $h * 2, 'M', true);

        // OVERTIME HOURS with subcolumns
        PDF::MultiCell($w2, $h, "OVERTIME HOURS", 'BT', 'C', false, 0, '', '', true, 0, false, true, $h, 'M', true);
        PDF::MultiCell($w3, $h * 2, "NO. OF OT\nHRS RENDERED", 'LTB', 'C', false, 0, '', '', true, 0, false, true, $h * 2, 'M', true);
        PDF::MultiCell($w4, $h * 2, "REASON(S) OF OVERTIME", 'LTBR', 'C', false, 1, '', '', true, 0, false, true, $h * 2, 'M', true);



        $x_start = PDF::GetX();
        $y_start = PDF::GetY() - $h; // Move up to top row level
        PDF::SetXY(220, 150); // Move to next line under OVERTIME HOURS

        // --- ROW 2: SUBHEADERS UNDER OVERTIME HOURS ---
        // PDF::SetX($w1); // Start under OVERTIME HOURS column
        PDF::MultiCell($w2 / 2, $h, "FROM", 'B', 'C', false, 0, '', '', true, 0, false, true, $h, 'M', true);
        PDF::MultiCell($w2 / 2, $h, "TO", 'BL', 'C', false, 1, '', '', true, 0, false, true, $h, 'M', true);
        // PDF::MultiCell(0, 0, "\n");
    }

    public function rpt_otapplication_PDF($config, $data)
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
        PDF::SetFont('helvetica', '', 10);
        foreach ($data as $key => $value) {

            $maxrow = 1;

            $arr_scheddate = $this->reporter->fixcolumn([$value->scheddate], '10', 0);
            $arr_timein = $this->reporter->fixcolumn([$value->timein], '10', 0);
            $arr_timeout = $this->reporter->fixcolumn([$value->timeout], '10', 0);
            $arr_apothrs = $this->reporter->fixcolumn([$value->apothrs], '10', 0);
            $arr_rem = $this->reporter->fixcolumn([$value->rem], '50', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_scheddate, $arr_timein, $arr_timeout, $arr_apothrs, $arr_rem]);

            for ($i = 0; $i < $maxrow; $i++) {

                if ($maxrow != $i + 1) {
                    PDF::MultiCell(180, 20, isset($arr_scheddate[$i]) ? $arr_scheddate[$i] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(90, 20, isset($arr_timein[$i]) ? $arr_timein[$i] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(90, 20, isset($arr_timeout[$i]) ? $arr_timeout[$i] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(100, 20, isset($arr_apothrs[$i]) ? $arr_apothrs[$i] : '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(260, 20, isset($arr_rem[$i]) ? $arr_rem[$i] : '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                } else {
                    PDF::MultiCell(180, 20, isset($arr_scheddate[$i]) ? $arr_scheddate[$i] : '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(90, 20, isset($arr_timein[$i]) ? $arr_timein[$i] : '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(90, 20, isset($arr_timeout[$i]) ? $arr_timeout[$i] : '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(100, 20, isset($arr_apothrs[$i]) ? $arr_apothrs[$i] : '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                    PDF::MultiCell(260, 20, isset($arr_rem[$i]) ? $arr_rem[$i] : '', 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                }
            }
        }
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(5, 20, '', 'LT', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, 'FILED BY: ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(5, 20, '', 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, 'NOTED BY/APPROVED BY: ', 'TR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(5, 20, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(5, 20, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont('helvetica', 'B', 10);
        PDF::MultiCell(5, 20, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, '' . $data[0]->empname, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(5, 20, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, '' . $data[0]->approver, 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont('helvetica', '', 10);
        PDF::MultiCell(5, 20, '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, 'SIGNATURE OVER PRINTED NAME', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(5, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(355, 20, 'COMPANY APPROVING OFFICER', 'BR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

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
                PDF::MultiCell(180, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(90, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(90, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(100, 20,  '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(260, 20, '', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            } else {
                PDF::MultiCell(180, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(90, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(90, 20, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(100, 20,  '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(260, 20, '', 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
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
