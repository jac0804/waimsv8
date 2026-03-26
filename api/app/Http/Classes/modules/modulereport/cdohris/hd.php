<?php

namespace App\Http\Classes\modules\modulereport\cdohris;

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
use Illuminate\Support\Facades\URL;
use DateTime;


use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class hd
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
        $fields = ['radioprint', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable("select 
      'PDFM' as print
    ");
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select head.trno, head.docno, head.empid, date(head.dateid) as dateid,head.artid, head.sectionno, 
                        head.violationno,head.startdate, head.enddate, head.amt,head.detail, emp.clientname as empname,
                        head.jobtitle,chead.description as articlename,cdetail.description as sectionname,
                        head.penalty, head.numdays,head.refx,emp.client as empcode,dept.client as dept,
                        dept.clientname as deptname,head.deptid,ir.docno as irno,ir.idescription as irdesc,
                        chead.code as artcode,cdetail.section as sectioncode,date(ne.dateid) as noticedate,
                        date(ir.dateid) as irdate,date(ne.ddate) as noticedeadline,head.findings,
                        date(head.startdate) as disciplinarystart,date(ir.idate) as memodate
                from disciplinary as head
                left join client as emp on emp.clientid=head.empid
                left join client as dept on dept.clientid=head.deptid
                left join hincidenthead as ir on head.refx=ir.trno
                left join hnotice_explain as ne on ne.refx=head.refx
                left join codehead as chead on chead.artid=head.artid
                left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
                left join hrisnum as num on num.trno = head.trno
                where num.trno = '$trno' and num.doc='HD'
                union all
                select head.trno, head.docno, head.empid, date(head.dateid) as dateid,head.artid, head.sectionno, 
                       head.violationno,head.startdate, head.enddate, head.amt,head.detail, emp.clientname as empname,
                       head.jobtitle,chead.description as articlename,cdetail.description as sectionname,
                       head.penalty, head.numdays,head.refx,emp.client as empcode,dept.client as dept,
                       dept.clientname as deptname,head.deptid,ir.docno as irno,ir.idescription as irdesc,
                       chead.code as artcode,cdetail.section as sectioncode,date(ne.dateid) as noticedate,
                       date(ir.dateid) as irdate,date(ne.ddate) as noticedeadline,head.findings,
                       date(head.startdate) as disciplinarystart,date(ir.idate) as memodate
                from hdisciplinary as head
                left join client as emp on emp.clientid=head.empid
                left join client as dept on dept.clientid=head.deptid
                left join hincidenthead as ir on head.refx=ir.trno
                left join hnotice_explain as ne on ne.refx=head.refx
                left join codehead as chead on chead.artid=head.artid
                left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
                left join hrisnum as num on num.trno = head.trno
                where num.trno = '$trno' and num.doc='HD'";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($config, $data)
    {
        $data = $this->report_default_query($config);
        $str = $this->rpt_HD_PDF($config, $data);

        return $str;
    }

    public function rpt_HD_PDF($config, $data)
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


        $user = $config['params']['user'];
        $qry = "select clientname from client where email='$user'";
        $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        $query = "select d.divcode as division,d.divname,d.address from division as d
                      left join employee as e on e.divid=d.divid where empid = " . $data[0]['empid'] . "";
        $empcomp = $this->coreFunctions->opentable($query);

        $dateval = $data[0]['dateid'];
        $dthere = new DateTime($dateval);
        $newformatteddate = strtoupper($dthere->format('M-j-Y'));

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 20, strtoupper($empcomp[0]->divname), '', 'L');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 20, strtoupper($empcomp[0]->address), '', 'L');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 20, $newformatteddate . "\n\n\n", '', 'L');


        // if (!empty($empcomp)) {
        //     if ($empcomp[0]->division == '001') {
        //         PDF::Image($this->companysetup->getlogopath($config['params']) . 'logocdo2cycles.jpg', '645', '20', 110, 110);
        //     }
        //     if ($empcomp[0]->division == '002') {
        //         PDF::Image($this->companysetup->getlogopath($config['params']) . 'logombc.jpg', '645', '20', 110, 110);
        //     }
        //     if ($empcomp[0]->division == '003') {
        //         PDF::Image($this->companysetup->getlogopath($config['params']) . 'logoridefund.png', '645', '20', 110, 110);
        //     }
        // }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 18, "NOTICE OF DECISION", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 18, "To: ", '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, $data[0]['empname'], '', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 18, "Position: ", '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, $data[0]['jobtitle'], '', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 18, "Department: ", '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, $data[0]['deptname'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, 'Dear ' . $data[0]['empname'] . ',', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(160, 18, 'Following the issuance of the ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 18, 'Notice to explain', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 18, 'dated', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        // $noticedate = $data[0]['noticedate'];
        // $nehere = new DateTime($noticedate);
        // $newformatneddate = strtoupper($nehere->format('M-j-Y'));

        $incidentdate = $data[0]['irdate'];
        $irhere = new DateTime($incidentdate);
        $newformatirdate = strtoupper($irhere->format('M-j-Y'));

        PDF::MultiCell(80, 18, $newformatirdate, '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(220, 18, 'regarding the incident that occured on', '', 'L', false, 0);

        PDF::SetFont($fontbold, '', $fontsize);
        $incidentdate2 = $data[0]['memodate'];
        $ir2here = new DateTime($incidentdate2);
        $newformatir2date = strtoupper($ir2here->format('M-j-Y'));
        PDF::MultiCell(100, 18, $newformatir2date . ',', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(360, 18, 'and after careful review of your written explanation submitted on ', '', 'L', false, 0);

        PDF::SetFont($fontbold, '', $fontsize);
        $noticedeadline = $data[0]['noticedeadline'];
        $ndlhere = new DateTime($noticedeadline);
        $newformatndldate = strtoupper($ndlhere->format('M-j-Y'));
        PDF::MultiCell(80, 18, $newformatndldate, '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(260, 18, ', as well as all relevant evidence and ', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, 'testimonies, we have reached a decision. ', '', 'L', false, 0);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 18, "Summary of Findings: ", '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, $data[0]['findings'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(350, 18, "Company Policy/Rule Violated: ", '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 18, "Article Code: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(570, 18, $data[0]['artcode'], '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 18, "Article Description: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(570, 18, $data[0]['articlename'], '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 18, "Section Code: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(570, 18, $data[0]['sectioncode'], '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 18, "Section Description: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(570, 18, $data[0]['sectionname'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 18, "Decision: ", '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, 'Based on the findings, the company has decided to impose the following disciplinary action.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");


        $decisions = $this->coreFunctions->opentable("select d1a, d1b, d2a, d2b, d3a, d3b, d4a, d4b, d5a, d5b, description from codedetail where artid=" . $data[0]['artid'] . " and section='" . $data[0]['sectioncode'] . "'");

        if (!empty($decisions)) {
            foreach ($decisions as $key => $value) {
                if ($value->d1a != '') {
                    $selected = "";
                    if ($value->d1a == $data[0]['penalty'] && $value->d1b == $data[0]['numdays']) {
                        PDF::SetFont($fontbold, '', $fontsize);
                        $selected = " X";
                    } else {
                        PDF::SetFont($font, '', $fontsize);
                    }

                    if (strtolower($value->d1a) == "suspension") {
                        $d1a = "Suspension for " . $value->d1b . " days (with/without pay)";
                    } else {
                        $d1a = $value->d1a;
                    }

                    PDF::MultiCell(15, 0, $selected, 'TBLR', 'L', false, 0);
                    PDF::MultiCell(15, 0, "", '', 'L', false, 0);
                    PDF::MultiCell(560, 18, $d1a, '', 'L', false);
                }

                if ($value->d2a != '') {
                    $selected = "";
                    if ($value->d2a == $data[0]['penalty'] && $value->d2b == $data[0]['numdays']) {
                        PDF::SetFont($fontbold, '', $fontsize);
                        $selected = " X";
                    } else {
                        PDF::SetFont($font, '', $fontsize);
                    }

                    if (strtolower($value->d2a) == "suspension") {
                        $d2a = "Suspension for " . $value->d2b . " days (with/without pay)";
                    } else {
                        $d2a = $value->d2a;
                    }

                    PDF::MultiCell(15, 0, $selected, 'TBLR', 'L', false, 0);
                    PDF::MultiCell(15, 0, "", '', 'L', false, 0);
                    PDF::MultiCell(560, 18, $d2a, '', 'L', false);
                }
                if ($value->d3a != '') {
                    $selected = "";
                    if ($value->d3a == $data[0]['penalty'] && $value->d3b == $data[0]['numdays']) {
                        PDF::SetFont($fontbold, '', $fontsize);
                        $selected = " X";
                    } else {
                        PDF::SetFont($font, '', $fontsize);
                    }
                    if (strtolower($value->d3a) == "suspension") {
                        $d3a = "Suspension for " . $value->d3b . " days (with/without pay)";
                    } else {
                        $d3a = $value->d3a;
                    }
                    PDF::MultiCell(15, 0, $selected, 'TBLR', 'L', false, 0);
                    PDF::MultiCell(15, 0, "", '', 'L', false, 0);
                    PDF::MultiCell(560, 18, $d3a, '', 'L', false);
                }
                if ($value->d4a != '') {
                    $selected = "";
                    if ($value->d4a == $data[0]['penalty'] && $value->d4b == $data[0]['numdays']) {
                        PDF::SetFont($fontbold, '', $fontsize);
                        $selected = " X";
                    } else {
                        PDF::SetFont($font, '', $fontsize);
                    }
                    if (strtolower($value->d4a) == "suspension") {
                        $d4a = "Suspension for " . $value->d4b . " days (with/without pay)";
                    } else {
                        $d4a = $value->d4a;
                    }
                    PDF::MultiCell(15, 0, $selected, 'TBLR', 'L', false, 0);
                    PDF::MultiCell(15, 0, "", '', 'L', false, 0);
                    PDF::MultiCell(560, 18, $d4a, '', 'L', false);
                }
                if ($value->d5a != '') {
                    $selected = "";
                    if ($value->d5a == $data[0]['penalty'] && $value->d5b == $data[0]['numdays']) {
                        PDF::SetFont($fontbold, '', $fontsize);
                        $selected = " X";
                    } else {
                        PDF::SetFont($font, '', $fontsize);
                    }
                    if (strtolower($value->d5a) == "suspension") {
                        $d5a = "Suspension for " . $value->d5b . " days (with/without pay)";
                    } else {
                        $d5a = $value->d5a;
                    }
                    PDF::MultiCell(15, 0, $selected, 'TBLR', 'L', false, 0);
                    PDF::MultiCell(15, 0, "", '', 'L', false, 0);
                    PDF::MultiCell(560, 18, $d5a, '', 'L', false);
                }
            }
        }

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(15, 0, "", 'TBLR', 'L', false, 0);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(560, 18, "Verbal Warning", '', 'L', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(15, 0, "", 'TBLR', 'L', false, 0);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(560, 18, "Written Warning", '', 'L', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(15, 0, "", 'TBLR', 'L', false, 0);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(560, 18, "Suspension for _____ days (with/without pay)", '', 'L', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(15, 0, "", 'TBLR', 'L', false, 0);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(560, 18, "Demotion", '', 'L', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(15, 0, "", 'TBLR', 'L', false, 0);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(560, 18, "Termination of Employment", '', 'L', false);

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(15, 0, "", 'TBLR', 'L', false, 0);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(560, 18, "Others: _____________________", '', 'L', false);

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(15, 0, "", '', 'L', false, 0);
        // if (strtolower($data[0]['penalty'])  == 'suspension') {
        //     PDF::MultiCell(560, 18, "Suspension for " . $data[0]['numdays'] . " days (with/without pay)", '', 'L', false);
        // } else {
        //     PDF::MultiCell(560, 18, $data[0]['penalty'], '', 'L', false);
        // }

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(210, 18, 'This disciplinary action takes effect on ', '', 'L', false, 0);

        PDF::SetFont($fontbold, '', $fontsize);
        $disciplinarystart = $data[0]['disciplinarystart'];
        $nodhere = new DateTime($disciplinarystart);
        $newformatnoddate = strtoupper($nodhere->format('M-j-Y'));
        PDF::MultiCell(590, 18, $newformatnoddate . '.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, 'You are reminded that any further violation of company policies may result in more serious disciplinary action, up to and including dismissal.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(435, 18, 'Should you have any questions or wish to appeal this decision, you may contact ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(95, 18, '[HR Department]', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 18, 'within', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 18, '[3 days]', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 18, 'from receipt', '', 'L', false);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, 'of this notice.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, 'We trust that this will serve as a reminder of your responsibilities and commitment to upholding company standards moving forward.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $empid = $this->coreFunctions->getfieldvalue("client", "clientid", "email=?", [$user]);
        $jobid = $this->coreFunctions->getfieldvalue("employee", "jobid", "empid=?", [$empid]);
        $jobtitle = $this->coreFunctions->getfieldvalue("jobthead", "jobtitle", "line=?", [$jobid]);

        PDF::MultiCell(700, 18, 'Sincerely,', '', 'L', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(700, 18, isset($name[0]['clientname']) ? $name[0]['clientname'] : '', '', 'L', false);
        PDF::MultiCell(700, 18, $jobtitle, '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(30, 18, 'CC:', '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(670, 18, '[HR Department / Employee File]', '', 'L', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
