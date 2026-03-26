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

class hn
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
                'PDFM' as print");
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];

        $query = "select head.trno, head.docno,date(head.dateid) as dateid,
                        head.empid,emp.clientname as empname, head.empjob,dept.clientname as deptname,
                            ir.docno as irno,ir.idescription,ir.iplace,ir.idate,ir.idetails,ir.icomments,
                    chead.code as artcode,chead.description as articlename,
                    cdetail.section as sectioncode,cdetail.description as sectionname,e.empfirst,e.emplast
                    from notice_explain as head
                    left join employee as e on e.empid=head.empid
                    left join client as emp on emp.clientid=head.empid
                    left join client as dept on dept.clientid=head.deptid
                    left Join hincidenthead as ir on head.refx=ir.trno
                    left join codehead as chead on chead.artid=head.artid
                    left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
                    left join hrisnum as num on num.trno = head.trno
                    where num.trno = '$trno' and num.doc='HN'
                    union all

                    select head.trno, head.docno,date(head.dateid) as dateid,
                        head.empid,emp.clientname as empname, head.empjob,dept.clientname as deptname,
                            ir.docno as irno,ir.idescription,ir.iplace,ir.idate,ir.idetails,ir.icomments,
                    chead.code as artcode,chead.description as articlename,
                    cdetail.section as sectioncode,cdetail.description as sectionname,e.empfirst,e.emplast
                    from hnotice_explain as head
                     left join employee as e on e.empid=head.empid
                    left join client as emp on emp.clientid=head.empid
                    left join client as dept on dept.clientid=head.deptid
                    left Join hincidenthead as ir on head.refx=ir.trno
                    left join codehead as chead on chead.artid=head.artid
                    left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
                    left join hrisnum as num on num.trno = head.trno
                    where num.trno = '$trno' and num.doc='HN'";


        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($config, $data)
    {
        $data = $this->report_default_query($config);
        $str = $this->rpt_HN_PDF($config, $data);
        return $str;
    }

    public function rpt_HN_PDF($config, $data)
    {
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
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 20, strtoupper($empcomp[0]->divname), '', 'L');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 20, strtoupper($empcomp[0]->address), '', 'L');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 20, $newformatteddate . "\n\n\n", '', 'L');


        if (!empty($empcomp)) {
            if ($empcomp[0]->division == '001') {
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'logocdo2cycles.jpg', '645', '20', 110, 110);
            }
            if ($empcomp[0]->division == '002') {
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'logombc.jpg', '645', '20', 110, 110);
            }
            if ($empcomp[0]->division == '003') {
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'logoridefund.png', '645', '20', 110, 110);
            }
        }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(700, 18, "NOTICE TO EXPLAIN", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 18, "To: ", '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 18, "Position: ", '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['empjob']) ? $data[0]['empjob'] : ''), '', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 18, "Department: ", '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(560, 18, 'Dear ' . $data[0]['empfirst'] . ' ' . $data[0]['emplast'] . ',', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, 'This is to formally notify you that you are being asked to provide a written explanation regarding the following incident/behavior.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 18, "Nature of the Incident: ", '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 18, "Incident Place: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['iplace']) ? $data[0]['iplace'] : ''), '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 18, "Incident Date: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['idate']) ? $data[0]['idate'] : ''), '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 18, "Incident Details: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['idetails']) ? $data[0]['idetails'] : ''), '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(350, 18, "Policy/Rule Allegedly Violated: ", '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 18, "Article Code: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['artcode']) ? $data[0]['artcode'] : ''), '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 18, "Article Description: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['articlename']) ? $data[0]['articlename'] : ''), '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 18, "Section Code: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['sectioncode']) ? $data[0]['sectioncode'] : ''), '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 18, "Section Description: ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(560, 18, (isset($data[0]['sectionname']) ? $data[0]['sectionname'] : ''), '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(700, 18, 'You are hereby given [5 working days] from receipt of this notice to submit a written explanation as to why no disciplinary action should be taken against you.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(700, 18, 'Failure to submit your explanation within the given period will be construed as a waiver of your right to be heard and may result in disciplinary action based on the information available.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(700, 18, 'Should you wish, you may also attach any supporting documents or evidence related to your explanation.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(700, 18, 'For any clarifications, you may contact your supervisor.', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $empid = $this->coreFunctions->getfieldvalue("client", "clientid", "email=?", [$user]);
        $jobid = $this->coreFunctions->getfieldvalue("employee", "jobid", "empid=?", [$empid]);
        $jobtitle = $this->coreFunctions->getfieldvalue("jobthead", "jobtitle", "line=?", [$jobid]);

        PDF::MultiCell(700, 18, 'Sincerely,', '', 'L', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(700, 18, $name[0]['clientname'], '', 'L', false);
        PDF::MultiCell(700, 18, $jobtitle, '', 'L', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
