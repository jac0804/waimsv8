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
use App\Http\Classes\sbcscript\sbcscript;

class loanapplicationportal
{

    private $modulename;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    private $sbcscript;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->sbcscript = new sbcscript;
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'radioreporttype', 'print']; //'startdate', 'start', 'end', 'endorseby', 'prepared', 'noted',

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'MULTI-PURPOSE LOAN', 'value' => '1', 'color' => 'orange'],
            ['label' => 'MULTI-PURPOSE LOAN 2', 'value' => '2', 'color' => 'orange'],
            ['label' => 'CAR LOAN', 'value' => '3', 'color' => 'orange']
        ]);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable("
      select
      'PDFM' as print,
      '1' as reporttype
  ");
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $curdate = $this->othersClass->getCurrentDate();
        $query = "select lpad(ss.trno,10,'0') as reff,ss.trno, ss.docno,date_format(ss.dateid, '%m-%d-%y') as dateid, ss.remarks,TIMESTAMPDIFF(YEAR, e.hired, '" . $curdate . "') as years, 
               
                st.batch, date_format(st.dateid, '%m-%d-%y') as stdateid, st.db, st.cr, st.ismanual,client.clientname,ss.licenseno,
                ss.licensetype,ss.purpose,ss.purpose1,dept.clientname as detpname,empno.client as empno,jt.jobtitle,ss.amortization,
                divi.divname as division,ss.amt as amount,date_format(ss.termfrom, '%m-%d-%y') as termfrom,date_format(ss.termto, '%m-%d-%y') as termto,
                date_format(ss.payrolldate, '%m-%d-%y') as payrolldate,
                app.email as approvedby,app2.clientname as approvedby2,app2.email as app2email,ss.date_approved_disapproved as appdate,
                ss.cashadv,ss.saldedpurchase,ss.chgduelosses,ss.uniforms,ss.otherchgloan,ss.sssploan
                from loanapplication as ss
                left join standardtrans as st on ss.trno = st.line
                left join employee as e on ss.empid = e.empid
                left join jobthead as jt on jt.line = e.jobid 
                left join client on client.clientid = ss.empid
                left join client as dept on dept.clientid = e.deptid
                left join client as empno on empno.clientid = ss.empid
                left join client as app on app.email = ss.approvedby_disapprovedby and app.email <> ''
                left join client as app2 on app2.email = ss.approvedby_disapprovedby2 and app2.email <> ''
       
                left join division as divi on divi.divid = e.divid
                where ss.trno = $trno
                order by ss.dateid";

        $result = $this->coreFunctions->opentable($query);
        return $result;
    } //end fn

    public function reportplotting($config, $data)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        $data = $this->report_default_query($config);

        if ($reporttype != '3') {
            $str = $this->multipurpose_loan_PDF($config, $data);
        } else {
            $str = $this->carl_loan_PDF($config, $data);
        }

        return $str;
    }

    public function rpt_default_header_PDF($config, $data)
    {

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $reporttype = $config['params']['dataparams']['reporttype'];


        $canvasheight = 1040;
        $carloan = false;
        if ($reporttype == 3) { //carloan
            $canvasheight = 1000;
            $carloan = true;
        }

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
        PDF::AddPage('p', [800, $canvasheight]);
        PDF::SetMargins(80, 80);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        // PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        // Image(file, x, y, w, h)
        // PDF::SetFont($fontbold, '', 20);
        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::Image(public_path('images/ulitc/united_limsun.png'), 40, 30, 150, 70);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 20);

        if ($carloan) {
            PDF::MultiCell(640, 20, 'EMPLOYEE CAR LOAN APPLICATION FORM', 0, 'C', false);
        } else {
            PDF::MultiCell(640, 20, 'EMPLOYEE MULTI-PURPOSE LOAN APPLICATION FORM', 0, 'C', false);
        }
    }

    public function multipurpose_loan_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $fontsize = "11";
        $count = 35;
        $page = 35;
        $font = "";
        $fontbold = "";
        $fontitalicbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontitalicbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
        }
        $this->rpt_default_header_PDF($config, $data);
        $empname = "";
        $empno = "";
        $detpname = "";
        $jobtitle = "";
        $years = "";
        $dateid = "";
        $purpose = "";
        $amortization = 0;
        $division = "";
        $amount = 0;
        $maxrow = 0;
        $approvedby = '';
        $approvedby2 = '';
        $appdate = '';
        $reff = '';

        $payrolldate = "";
        $termto = "";
        $termfrom = "";



        $cashadv = 0;
        $saldedpurchase = 0;
        $chgduelosses = 0;
        $uniforms = 0;
        $otherchgloan = 0;
        $sssploan = 0;
        $total_loans = 0;
        $app2email = '';

        foreach ($data as $key => $value) {
            $empname = $value->clientname;
            $empno = $value->empno;
            $detpname = $value->detpname;
            $jobtitle = $value->jobtitle;
            $years = $value->years;
            $dateid = $value->dateid;
            $purpose = $value->purpose;
            $amortization = $value->amortization;
            $division = $value->division;
            $amount = $value->amount;
            $approvedby = $value->approvedby;
            $appdate = $value->appdate;
            $approvedby2 = $value->approvedby2;
            $app2email = $value->app2email;
            $reff = $value->reff;

            $termfrom = $value->termfrom;
            $termto = $value->termto;
            $payrolldate = $value->payrolldate;

            $cashadv = $value->cashadv;
            $saldedpurchase = $value->saldedpurchase;
            $chgduelosses = $value->chgduelosses;
            $uniforms = $value->uniforms;
            $otherchgloan = $value->otherchgloan;
            $sssploan = $value->sssploan;

            $total_loans += ($value->cashadv + $value->saldedpurchase + $value->chgduelosses + $value->uniforms + $value->otherchgloan + $value->sssploan);
        }

        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(400, 10, "", '', '', false, 0);
        PDF::MultiCell(170, 10, "", '', '', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 10, "", '', '', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(100, 10, "Employee Name: ", 'T', 'L', false, 0);
        PDF::MultiCell(210, 10, "", 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(120, 10, "Date of Appplication:", 'T', 'L', false, 0);
        PDF::MultiCell(190, 10, "", 'TR', 'L', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(150, 10, "", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'L', false, 0);
        PDF::MultiCell(160, 10, "", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "" . $empname, '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "" . $dateid, 'R', 'C', false);
        // PDF::MultiCell(160, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "Position Title ", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $jobtitle, 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(150, 10, "Employee ID No.", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $empno, 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "Department ", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $detpname, 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(150, 10, "Year of Service", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $years, 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "", 'BT', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BT', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BT', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BTR', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(630, 10, "Purpose of Loan: (please check appropriate box)", 'R', 'L', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(630, 10, "", 'R', 'L', false);




        $checktuition = "D";
        $checkmedical = "D";
        $checkappliance = "D";
        $checkPurchase = "D";
        $checkRepairs = "D";
        $checkFinancial = "D";

        switch ($purpose) {
            case "Tuition fee of employee`s child":
                $checktuition = 'FD';
                break;
            case "Medical expenses of employee or his dependents":
                $checkmedical = 'FD';
                break;
            case "House Appliance/s":
                $checkappliance = 'FD';
                break;

            case "Purchase of laptop or desk top of computer": //Purchase of laptop or desk top of computer
                $checkPurchase = 'FD';
                break;
            case "House Repairs":
                $checkRepairs = 'FD';
                break;
            case "Financial assistance in cases of calamity":
                $checkFinancial = 'FD';
                break;
        }
        // PDF::Rect(20, 50, 6, 6, 'D'); Empty checkbox
        // PDF::Rect(20, 50, 6, 6, 'FD');Filled checkbox:
        // PDF::Rect($x, $y, 6, 6);

        // PDF::Rect($fontsize0, 200, 6, 6); // (x, y, width, height)
        // PDF::SetXY(273, 273);

        PDF::SetLineWidth(1.5);
        PDF::Rect(105, 278, 6, 6, $checktuition); // line 1
        PDF::Rect(395, 278, 6, 6, $checkmedical); //line 1

        PDF::Rect(105, 292, 6, 6, $checkappliance); //line 2
        PDF::Rect(395, 292, 6, 6, $checkPurchase); //line 2

        PDF::Rect(105, 306, 6, 6, $checkRepairs); //line 3
        PDF::Rect(395, 306, 6, 6, $checkFinancial); //line 3

        PDF::SetLineWidth(0.5);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(280, 10, "Tuition fee of employee`s child", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', 'L', false, 0);
        PDF::MultiCell(300, 10, "Medical expenses of employee or his dependents", '', '', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(280, 10, "House Appliance/s", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', 'L', false, 0);
        PDF::MultiCell(300, 10, "Purchase of laptop or desk top of computer", '', '', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(280, 10, "House Repairs", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', 'L', false, 0);
        PDF::MultiCell(300, 10, "Financial assistance in cases of calamity", '', '', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(640, 10, "", 'LRB', '', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        // PDF::MultiCell(630, 10, "Total Price: (please attach copy of quotation)", 'R', 'L', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(310, 10, "Total Price: (please attach copy of quotation)", 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(30, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(280, 10, "" . number_format($amount, 2), 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "Monthly Amortizations ", 'T', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(30, 10, "Php", 'T', 'L', false, 0);
        PDF::MultiCell(280, 10, "" . number_format($amortization, 2), 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(80, 10, "Payroll Dates: ", 'T', 'L', false, 0);
        PDF::MultiCell(550, 10, "" . $payrolldate, 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(40, 10, "From: ", 'T', 'L', false, 0);
        PDF::MultiCell(590, 10, "" . $termfrom, 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TLB', 'L', false, 0);
        PDF::MultiCell(40, 10, "To: ", 'T', 'L', false, 0);
        PDF::MultiCell(590, 10, "" . $termto, 'TRB', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(310, 10, "", 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(310, 10, "Endorsed by: ", 'TR', 'L', false);
        // PDF::MultiCell(160, 10, "", 'TR', 'R', false);

        // $endorse = isset($config['params']['dataparams']['endorseby']) ? $config['params']['dataparams']['endorseby'] : '';

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "" . $empname, 'R', 'C', false, 0);
        PDF::MultiCell(10, 10, "", '', 'L', false, 0);
        PDF::MultiCell(310, 10, "", 'R', 'C', false);
        // PDF::MultiCell(160, 10, "", 'TR', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Employee Name & Signature", 'R', 'C', false, 0);
        PDF::MultiCell(10, 10, "", '', 'L', false, 0);
        PDF::MultiCell(300, 10, "Employee's Immediate Supervisor", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TLB', 'L', false, 0);
        PDF::MultiCell(630, 10, "TO BE FILLED UP BY HR", 'TRB', 'C', false);

        PDF::SetFont($fontitalicbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "Employee's Outstanding Loans:", 'R', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "", 'R', 'L', false);


        $cashadv = $cashadv != 0 ? number_format($cashadv, 2) : '';
        $saldedpurchase = $saldedpurchase != 0 ? number_format($saldedpurchase, 2) : '';
        $chgduelosses = $chgduelosses != 0 ? number_format($chgduelosses, 2) : '';
        $uniforms = $uniforms != 0 ? number_format($uniforms, 2) : '';
        $otherchgloan = $otherchgloan != 0 ? number_format($otherchgloan, 2) : '';
        $sssploan = $sssploan != 0 ? number_format($sssploan, 2) : '';

        $total_loans =  $total_loans != 0 ? number_format($total_loans, 2) : '';

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Cash Advance", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $cashadv, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Salary Deduction Purchase", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $saldedpurchase, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "SSS/Pag-Ibig Loan", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $sssploan, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Charges due to Losses", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $chgduelosses, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Uniforms", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $uniforms, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Other Charges/Loans", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $otherchgloan, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "", 'R', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "TOTAL LOANS : ", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $total_loans, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(630, 10, "", 'BR', 'L', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "Prepared by:", 'T', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(150, 10, "Noted by: ", 'T', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'TR', 'R', false);
        $supervisorposition = "";

        if ($app2email != "") {
            $query = "select clientid as value from client where email = '" . $app2email . "'";
            $clientid = $this->coreFunctions->datareader($query, [$config['params']['adminid']]);
            $supervisorposition = $this->coreFunctions->datareader("select jt.jobtitle as value from employee as emp 
            left join jobthead as jt on jt.jobid = emp.jobid where emp.empid = ? ", [$clientid]);
        }


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(310, 10, "" . $approvedby2, 'R', 'C', false, 0); //approvedby2
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(300, 10, "", '', 'C', false, 0); //noted from dashboard approved
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', 'L', false, 0);
        PDF::MultiCell(310, 10, "" . $supervisorposition, 'BR', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(300, 10, "HR & Administrative Manager", 'B', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'BR', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(630, 10, "", 'BR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(150, 10, "Approved by:", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', 'L', false, 0);
        PDF::MultiCell(150, 10, "Approved by: ", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "", 'R', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);


        $approvedby = '';
        $appdate = '';
        $datedustine = '';
        $dateirish = '';
        $dusposition = '';
        $irisposition = '';

        $fixapp = $this->coreFunctions->opentable("
        select client.clientname,client.email,jt.jobtitle,emp.empid from employee as emp 
        left join client on client.clientid = emp.empid
        left join jobthead as jt on jt.line = emp.jobid 
        where emp.empid in (259,260)");

        foreach ($fixapp as $key => $app) {
            if ($app->empid == 259) { //dustine
                if ($app->email == $approvedby) {
                    $datedustine = $appdate;
                }
                $dusposition = $app->jobtitle;
            } else {
                if ($app->email == $approvedby) {
                    $dateirish = $appdate;
                }
                $irisposition = $app->jobtitle;
            }
        }

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "Irish Sun Lim " . $datedustine, 'R', 'C', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "Dustin Go Lim " . $dateirish, '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(310, 10, '' . $irisposition, 'BR', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'B', '', false, 0);
        PDF::MultiCell(300, 10, '' . $dusposition, 'B', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'BR', '', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(630, 10, "AUTHORIZATION FOR VOLUNTARY PAYROLL DEDUCTION", '', 'C', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(630, 10, "", '', 'C', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(20, 10, "", '', '', false, 0);
        PDF::MultiCell(25, 10, "I", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(170, 10, "" . $empname, 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 10, "hereby authorize ", '', 'C', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(280, 10, "" . $division, 'B', 'C', false, 0); //United Limsun International Trading Corporation 
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(25, 10, " to ", '', 'C', false);


        $amountword = $this->ftNumberToWordsConverter($amount);

        $arr_amountword = $this->reporter->fixcolumn([$amountword], '45', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_amountword]);

        $line1 = "";
        $line2 = "";
        for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
                $line1 = (isset($arr_amountword[$r]) ? $arr_amountword[$r] : '');
            }
            if ($r == 1) {
                $line2 = (isset($arr_amountword[$r]) ? $arr_amountword[$r] : '');
            }
        }

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(290, 10, "deduct from my wage for my Multi-Purpose Loan", '', 'L', false, 0);
        PDF::MultiCell(90, 10, "amounting to ", '', 'C', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(260, 10, "" . $line1, 'B', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(270, 10, "" . $line2, 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, ",", '', 'C', false, 0);
        PDF::MultiCell(80, 10, "beginning", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "" . $termfrom, 'B', 'C', false, 0);
        PDF::MultiCell(80, 10, "and ending", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "" . $termto, 'B', 'C', false, 0);
        PDF::MultiCell(10, 10, ",", '', 'C', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(110, 10, "until the amount of", '', 'L', false, 0);
        PDF::MultiCell(120, 10, $amount != 0 ? number_format($amount, 2) : '', 'B', 'C', false, 0);
        PDF::MultiCell(120, 10, "has been deducted.", '', 'C', false, 0);
        PDF::MultiCell(290, 10, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(20, 10, "", '', 'L', false, 0);
        PDF::MultiCell(620, 10, "In the event that my employment ends for any reason before are final deduction is made, the entire", '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(640, 10, "balance will be deducted from my final wages.", '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");
        // PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', 10); //. $empname
        PDF::MultiCell(300, 10, "" . $empname, 'B', 'C', false, 0);
        PDF::MultiCell(40, 10, "", '', 'L', false, 0);
        PDF::MultiCell(300, 10, "" . $dateid, 'B', 'C', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(300, 10, "Employee's Printed Name above Signature", '', 'C', false, 0);
        PDF::MultiCell(40, 10, "", '', 'L', false, 0);
        PDF::MultiCell(300, 10, "Date Signed", '', 'C', false);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(640, 10, "Rev.01 071823otm", '', 'L', false);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(640, 10, "Copy furnished: Accounting Department and employee's 201 records", '', 'L', false);

        // PDF::MultiCell(0, 0, "\n");
        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn
    public function carl_loan_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $fontsize = "10";
        $count = 35;
        $page = 35;
        $font = "";
        $fontbold = "";
        $fontitalicbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontitalicbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
        }
        $this->rpt_default_header_PDF($config, $data);

        $maxrow = 0;
        $empname = "";
        $empno = "";
        $detpname = "";
        $jobtitle = "";
        $years = "";
        $licensetype = "";
        $dateid = "";
        $purpose1 = "";
        $remarks = "";

        $cashadv = 0;
        $saldedpurchase = 0;
        $chgduelosses = 0;
        $uniforms = 0;
        $otherchgloan = 0;
        $sssploan = 0;
        $total_loans = 0;
        $app2email = "";
        $approvedby2 = "";


        foreach ($data as $key => $value) {
            $empname = $value->clientname;
            $empno = $value->empno;
            $detpname = $value->detpname;
            $jobtitle = $value->jobtitle;
            $years = $value->years;
            $licensetype = $value->licensetype;
            $licenseno = $value->licenseno;
            $dateid = $value->dateid;
            $purpose1 = $value->purpose1;
            $remarks = $value->remarks;


            $cashadv = $value->cashadv;
            $saldedpurchase = $value->saldedpurchase;
            $chgduelosses = $value->chgduelosses;
            $uniforms = $value->uniforms;
            $otherchgloan = $value->otherchgloan;
            $sssploan = $value->sssploan;
            $app2email = $value->app2email;
            $approvedby2 = $value->approvedby2;

            $total_loans += ($value->cashadv + $value->saldedpurchase + $value->chgduelosses + $value->uniforms + $value->otherchgloan + $value->sssploan);
        }


        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(100, 10, "Employee Name: ", 'T', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(210, 10, "" . $empname, 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(120, 10, "Date of Appplication:", 'T', 'L', false, 0);
        PDF::MultiCell(190, 10, "" . $dateid, 'TR', 'L', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(150, 10, "", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'L', false, 0);
        PDF::MultiCell(160, 10, "", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(150, 10, "", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'L', false, 0);
        PDF::MultiCell(160, 10, "", '', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'R', 'R', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "Position Title: ", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $jobtitle, 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(150, 10, "Employee ID No. :", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $empno, 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "Department: ", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $detpname, 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(150, 10, "Year of Service: ", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $years, 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "Driver's License no.:", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $licenseno, 'TR', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'T', 'L', false, 0);
        PDF::MultiCell(150, 10, "Trpe of Driver's License: ", 'TR', 'L', false, 0);
        PDF::MultiCell(160, 10, "" . $licensetype, 'TR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(150, 10, "", 'BT', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BT', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BT', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BTR', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'TL', 'L', false, 0);
        PDF::MultiCell(630, 10, "Purpose of Car Loan: ", 'R', 'L', false);


        $arr_purpose = $this->reporter->fixcolumn([$purpose1], '98', 0);
        $arr_remarks = $this->reporter->fixcolumn([$remarks], '98', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_purpose, $arr_remarks]);

        $line1 = "";
        $line2 = "";
        $line3 = "";

        $remarks1 = "";
        $remarks2 = "";
        for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
                $line1 = (isset($arr_purpose[$r]) ? $arr_purpose[$r] : '');
                $remarks1 = (isset($arr_remarks[$r]) ? $arr_remarks[$r] : '');
            }
            if ($r == 1) {
                $line2 = (isset($arr_purpose[$r]) ? $arr_purpose[$r] : '');
                $remarks2 = (isset($arr_remarks[$r]) ? $arr_remarks[$r] : '');
            }
            if ($r == 2) {
                $line3 = (isset($arr_purpose[$r]) ? $arr_purpose[$r] : '');
            }
        }


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(590, 10, "" . $line1, 'B', 'L', false, 0);
        PDF::MultiCell(20, 10, "", 'R', 'L', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(610, 10, "" . $line2, 'B', 'L', false, 0);
        PDF::MultiCell(20, 10, "", 'R', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(610, 10, "" . $line3, 'B', 'L', false, 0);
        PDF::MultiCell(20, 10, "", 'R', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'LB', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BR', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'LB', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(160, 10, "", 'BR', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "Remarks: ", 'R', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 10, "", 'L', '', false, 0);
        PDF::MultiCell(590, 10, "" . $remarks1, 'B', 'L', false, 0);
        PDF::MultiCell(20, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(610, 10, "" . $remarks2, 'B', 'L', false, 0);
        PDF::MultiCell(20, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(630, 10, "", 'RB', 'L', false);

        // $endorse = isset($config['params']['dataparams']['endorseby']) ? $config['params']['dataparams']['endorseby'] : '';
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "", 'R', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "Endorse by:", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "" . $empname, 'R', 'C', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'LB', '', false, 0);
        PDF::MultiCell(310, 10, "Employee Name and Signature", 'RB', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'B', '', false, 0);
        PDF::MultiCell(300, 10, "Employee's Immediate Head", 'B', 'c', false, 0);
        PDF::MultiCell(10, 10, "", 'BR', '', false);

        PDF::SetFont($fontitalicbold, '', 10);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(630, 10, "if in case the employee chose a car which is higher than the budget, any excess in amount will be shouldered by the employee.", 'BR', '', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(640, 10, "TO BE FILLED UP BY HR", 'BLR', 'C', false);



        $cashadv = $cashadv != 0 ? number_format($cashadv, 2) : '';
        $saldedpurchase = $saldedpurchase != 0 ? number_format($saldedpurchase, 2) : '';
        $chgduelosses = $chgduelosses != 0 ? number_format($chgduelosses, 2) : '';
        $uniforms = $uniforms != 0 ? number_format($uniforms, 2) : '';
        $otherchgloan = $otherchgloan != 0 ? number_format($otherchgloan, 2) : '';
        $sssploan = $sssploan != 0 ? number_format($sssploan, 2) : '';

        $total_loans =  $total_loans != 0 ? number_format($total_loans, 2) : '';


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "Employee's Outstanding Loans:", 'R', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "", 'R', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Cash Advance", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 10, "" . $cashadv, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Salary Deduction Purchase", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 10, "" . $saldedpurchase, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "SSS/Pag-Ibig Loan", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 10, "" . $sssploan, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Charges due to Losses", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 10, "" . $chgduelosses, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Uniforms", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 10, "" . $uniforms, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "Other Charges/Loans", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 10, "" . $otherchgloan, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "", 'R', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', 'L', false, 0);
        PDF::MultiCell(310, 10, "TOTAL LOANS : ", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(150, 10, "" . $total_loans, 'B', 'C', false, 0);
        PDF::MultiCell(150, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(630, 10, "", 'R', 'L', false);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "Employee's Disciplinary Action Record: ", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "Employee's Promotion Record: ", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", 'B', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(630, 10, "", 'BR', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "Prepared by: ", 'R', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "Noted by: ", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        // $noted = isset($config['params']['dataparams']['noted']) ? $config['params']['dataparams']['noted'] : '';
        // $prepared = isset($config['params']['dataparams']['prepared']) ? $config['params']['dataparams']['prepared'] : '';


        $supervisorposition = "";

        if ($app2email != "") {
            $query = "select clientid as value from client where email = '" . $app2email . "'";
            $clientid = $this->coreFunctions->datareader($query, [$config['params']['adminid']]);
            $supervisorposition = $this->coreFunctions->datareader("select jt.jobtitle as value from employee as emp 
            left join jobthead as jt on jt.jobid = emp.jobid where emp.empid = ? ", [$clientid]);
        }
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "" . $approvedby2, 'R', 'C', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(310, 10, "" . $supervisorposition, 'BR', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'B', '', false, 0);
        PDF::MultiCell(300, 10, "HR & Administrative Manager", 'B', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'BR', '', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(630, 10, "", 'BR', 'L', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "Approved by: ", 'R', 'L', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "Approved by: ", '', 'L', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "", 'R', 'C', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "", '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);


        $approvedby = '';
        $appdate = '';
        $datedustine = '';
        $dateirish = '';
        $dusposition = '';
        $irisposition = '';

        $fixapp = $this->coreFunctions->opentable("
        select client.clientname,client.email,jt.jobtitle from employee as emp 
        left join client on client.clientid = emp.empid
        left join jobthead as jt on jt.line = emp.jobid 
        where emp.empid in (259,260)");

        foreach ($fixapp as $key => $app) {
            if ($app->empid == 259) { //dustine
                if ($app->email == $approvedby) {
                    $datedustine = $appdate;
                }
                $dusposition = $app->jobtitle;
            } else {
                if ($app->email == $approvedby) {
                    $dateirish = $appdate;
                }
                $irisposition = $app->jobtitle;
            }
        }

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'L', '', false, 0);
        PDF::MultiCell(310, 10, "Irish Sun Lim " . $dateirish, 'R', 'C', false, 0);
        PDF::MultiCell(10, 10, "", '', '', false, 0);
        PDF::MultiCell(300, 10, "Dustin Go Lim " . $datedustine, '', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'R', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(310, 10, "" . $irisposition, 'BR', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'B', '', false, 0);
        PDF::MultiCell(300, 10, "" . $dusposition, 'B', 'C', false, 0);
        PDF::MultiCell(10, 10, "", 'BR', '', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(10, 10, "", 'BL', '', false, 0);
        PDF::MultiCell(630, 10, "Copy furnished: Accounting Department and Employees's 201 records.", 'BR', 'L', false);



        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function ftNumberToWordsConverter($number)
    {
        $numberwords = $this->reporter->ftNumberToWordsBuilder($number);

        if (strpos($numberwords, "/") == false) {
            $numberwords .= " PESOS ";
        } else {
            $numberwords = str_replace(" AND ", " PESOS AND ", $numberwords);
        } //end if

        return $numberwords;
    } //end function convert to words
}
