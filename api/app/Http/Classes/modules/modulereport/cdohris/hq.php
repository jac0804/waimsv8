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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class hq
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
        $fields = ['radioprint', 'requested', 'noted', 'approved', 'approved2', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'noted.label', 'Noted /Endorsed by');
        data_set($col1, 'approved.label', 'Recommending Approval');
        data_set($col1, 'approved2.label', 'Approved /Disapproved');
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $trno = $config['params']['trno'];
        $qry = "select empid,notedid,recappid,appdisid from personreq where trno=$trno 
                union all
                select empid,notedid,recappid,appdisid from hpersonreq where trno=$trno";

        $empid = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        $qry = "select concat(empfirst, ' ' ,emplast) as reqname from employee where empid='" . $empid[0]['empid'] . "'";
        $empname = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        $notedby = $recby = $appby = '';

        if ($empid[0]['notedid'] != 0) {
            $notedby = $this->coreFunctions->getfieldvalue('employee', "concat(empfirst, ' ' ,emplast)", "empid=?", [$empid[0]['notedid']]);
        }
        if ($empid[0]['recappid'] != 0) {
            $recby = $this->coreFunctions->getfieldvalue('employee', "concat(empfirst, ' ' ,emplast)", "empid=?", [$empid[0]['recappid']]);
        }
        if ($empid[0]['appdisid'] != 0) {
            $appby = $this->coreFunctions->getfieldvalue('employee', "concat(empfirst, ' ' ,emplast)", "empid=?", [$empid[0]['appdisid']]);
        }

        return $this->coreFunctions->opentable(
            "select 
        'PDFM' as print,
        '" . $empname[0]['reqname'] . "' as requested,
        '$notedby' as noted,
        '$recby' as approved,
        '$appby' as approved2"
        );
    }

    public function report_default_query($config)
    {

        $trno = $config['params']['dataid'];

        $query = "select num.trno,head.docno,date(head.dateid) as dateid, head.dept, personnel, 
                        date(dateneed) as dateneed,job,head.class,headcount,hpref, agerange,gpref, 
                        rank, reason,head.hirereason,date(head.prdstart) as prdstart,
                        date(head.prdend) as prdend,head.empstatus,head.empmonths,head.empdays,
                        remark, refx, qualification,d.clientname as deptname, em.clientname as personnelname, 
                        job.jobtitle,head.amount,head.skill,head.educlevel,head.civilstatus,
                        head.jobsumm,head.branchid,br.clientname as branch,
                        rreq.category as reasontype,sreq.category as empstattype
                from personreq as head
                left join client as em on em.clientid = head.empid
                left join client as d on d.client = head.dept
                left join hrisnum as num on num.trno = head.trno
                left join jobthead as job on job.docno=head.job
                left join client as br on br.clientid=head.branchid
                left join reqcategory as rreq on rreq.line= head.reason
                left join reqcategory as sreq on sreq.line= head.empstatusid
                where num.trno = '$trno' and num.doc='HQ'
                union all
                select num.trno,head.docno,  date(head.dateid) as dateid, head.dept, personnel, 
                       date(dateneed) as dateneed, job, head.class, headcount, hpref, agerange,gpref, 
                       rank, reason,head.hirereason,date(head.prdstart) as prdstart,
                       date(head.prdend) as prdend,head.empstatus,head.empmonths,head.empdays,
                       remark, refx, qualification,d.clientname as deptname,em.clientname as personnelname, 
                       job.jobtitle,head.amount,head.skill,head.educlevel,head.civilstatus,
                       head.jobsumm,head.branchid,br.clientname as branch,
                       rreq.category as reasontype,sreq.category as empstattype
                from hpersonreq as head
                left join client as em on em.clientid = head.empid
                left join client as d on d.client = head.dept
                left join hrisnum as num on num.trno = head.trno
                left join jobthead as job on job.docno=head.job
                left join client as br on br.clientid=head.branchid
                left join reqcategory as rreq on rreq.line= head.reason
                left join reqcategory as sreq on sreq.line= head.empstatusid
                where num.trno = '$trno' and num.doc='HQ'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $data = $this->report_default_query($config);
        $str = $this->rpt_cdo2_PDF($config, $data);
        return $str;
    }
    public function rpt_header_cdo2($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $border = '1px solid';

        $fontbold = "";
        $font_size = '11';
        $italic = "";
        $fontarial = "";

        if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
            $fontarial = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
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

        // PDF::SetFont($font, '', 9);
        // PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        // Image(filename, left, top, width, height, type, link, align, resize, dpi, align, ismask, imgmask, border, fitbox, hidden, fitonpage)
        PDF::Image($this->companysetup->getlogopath($config['params']) . 'cyclesM.png', '80', '5', 160, 114);
        PDF::Image($this->companysetup->getlogopath($config['params']) . 'mbclogo.png', '280', '20', 143, 83);
        PDF::Image($this->companysetup->getlogopath($config['params']) . 'starmaclogo.png', '460', '5', 280, 109);


        PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n");

        PDF::SetFont($fontbold, 'B', 19);
        PDF::MultiCell(197.5, 0, "", '', 'C', false, 0);
        PDF::MultiCell(325, 0, "MANPOWER REQUISITION FORM", 'B', 'C', false, 0);
        PDF::MultiCell(197.5, 0, "", '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontarial, '', $font_size);
        PDF::MultiCell(85, 0, "Date Requested: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 0, "" . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'C', false, 0);
        PDF::MultiCell(565, 0, "", '', 'C', false, 1);


        PDF::SetFont($fontarial, '', 9);
        PDF::MultiCell(85, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 0, "", '', 'C', false, 0);
        PDF::MultiCell(565, 0, "", '', 'C', false, 1);

        // PDF::MultiCell(60, 25, "", 'LRB', 'C', false, 1, '',  '', true, 0, false, true, 25, 'M', false);
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontarial, '', $font_size);
        PDF::MultiCell(190, 0, "Position Title/s of required personnel: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(530, 0, "" . (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($fontarial, '', 9);
        PDF::MultiCell(720, 0, "", '', 'C', false, 1);

        PDF::SetFont($fontarial, '', $font_size);
        PDF::MultiCell(110, 0, "Department/Section: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(260, 0, "" . (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(110, 0, "Place of assignment", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(240, 0, (isset($data[0]['branch']) ? $data[0]['branch'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($fontarial, '', 9);
        PDF::MultiCell(720, 0, "", '', 'C', false, 1);


        PDF::SetFont($fontarial, '', $font_size);
        PDF::MultiCell(130, 0, "No. of Personnel needed: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30, 0, "" . (isset($data[0]['headcount']) ? $data[0]['headcount'] : ''), 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(77, 0, "Date Needed: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 0, "" . (isset($data[0]['dateneed']) ? $data[0]['dateneed'] : ''), 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(38, 0, "Salary: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(375, 0, number_format($data[0]['amount'], 2), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    }
    public function rpt_cdo2_PDF($config, $data)
    {
        $border = '1px solid';
        $fontsize = '11';
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $count = 35;
        $page = 35;

        $font = "";
        $fontbold = "";
        $italic = "";
        $fontarial = "";
        $fontarialB = "";

        if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
            $fontarial = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
            $fontarialB = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
            $italic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALBI.TTF');
        }
        $this->rpt_header_cdo2($config, $data);

        $reason1 = '';
        $reason2 = '';
        $reason3 = '';
        $reason4 = '';
        $reason5 = '';
        $from = '';
        $to = '';
        $months1 = '';
        $months2 = '';
        $days1 = '';
        $days2 = '';

        switch ($data[0]['reasontype']) {
            case 'Resignation of':
                $reason1 = $data[0]['hirereason'];
                break;
            case 'Termination of':
                $reason2 = $data[0]['hirereason'];
                break;
            case 'Leave of Absence of':
                $reason3 = $data[0]['hirereason'];
                $from = (isset($data[0]['prdstart']) ? $data[0]['prdstart'] : '');
                $to = (isset($data[0]['prdend']) ? $data[0]['prdend'] : '');
                break;
            case 'New position due to':
                $reason4 = $data[0]['hirereason'];
                break;
            case 'End of Contract of':
                $reason5 = $data[0]['hirereason'];
                break;
        }

        switch ($data[0]['empstattype']) {
            case 'Contractual/Temporary':
                $months1 = $data[0]['empmonths'];
                $days1 = $data[0]['empdays'];
                break;
            case 'Project Based':
                $months2 = $data[0]['empmonths'];
                $days2 = $data[0]['empdays'];
                break;
        }

        for ($i = 0; $i < count($data); $i++) {

            ####################
            PDF::SetFont($fontarialB, 'B', $fontsize);
            PDF::MultiCell(720, 20, "Reason for Hiring: ", '', 'L', false, 1, '',  '', true, 0, false, true, 30, 'M', false);

            PDF::SetFont($fontarial, '', $fontsize);
            $r1 = '';
            $r2 = '';
            $r3 = '';
            $r4 = '';
            $r5 = '';

            switch ($data[0]['reasontype']) {
                case 'Resignation of':
                    $r1 = 'checked="checked"';
                    break;
                case 'Termination of':
                    $r2 = 'checked="checked"';
                    break;
                case 'Leave of Absence of':
                    $r3 = 'checked="checked"';
                    break;
                case 'New position due to':
                    $r4 = 'checked="checked"';
                    break;
                case 'End of Contract of':
                    $r5 = 'checked="checked"';
                    break;
            }


            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data ;">
                
                    <input type="checkbox" name="agree1" value="1" readonly="true" ' . $r1 . '/>  
                    <label for="agree1" style="font-size: 11;">
                    <span class="display:inline-block;width:100px;">Resignation of </span>
                    </label>
                   
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(27, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::MultiCell(593, 0, $reason1, 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);


            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="agree2" value="2" readonly="true" ' . $r2 . '/>  
                    <label for="agree2" style="font-size: 11; ">
                    <span class="display: inline-block;width:100px;">Termination of</span>
                    </label>
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(30, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(590, 0, $reason2, 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="agree3" value="3" readonly="true" ' . $r3 . '/>  
                    <label for="agree3" style="font-size: 11;">
                    <span class="display: inline-block;width: 200px;">Leave of Absence of</span>
                    </label>
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);

            PDF::MultiCell(340, 0, $reason3, 'B', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(30, 0, "from", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(100, 0, $from, 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(20, 0, "to", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(100, 0, $to, 'B', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="agree4" value="4" readonly="true" ' . $r4 . '/>  
                    <label for="agree4" style="font-size: 11; ">
                    <span class="display: inline-block;width: 100px;">New position due to</span>
                    </label>
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(3, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(592, 0, $reason4, 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="agree5" value="5" readonly="true" ' . $r5 . '/>  
                    <label for="agree5" style="font-size: 11; ">
                    <span class="display: inline-block;width: 100px;">End of Contract of</span>
                    </label>
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(11, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::MultiCell(589, 0, $reason5, 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            ///////////////////////


            ####################
            PDF::SetFont($fontbold, 'B', $fontsize);
            PDF::MultiCell(720, 20, "Employment status upon hiring: ", '', 'L', false, 1, '',  '', true, 0, false, true, 30, 'M', false);

            PDF::SetFont($fontarial, '', $fontsize);
            $s1 = '';
            $s2 = '';
            $s3 = '';
            $s4 = '';

            switch ($data[0]['empstattype']) {
                case 'Probationary/For Regular':
                    $s1 = 'checked="checked"';
                    break;
                case 'Contractual/Temporary':
                    $s2 = 'checked="checked"';
                    break;
                case 'Project Based':
                    $s3 = 'checked="checked"';
                    break;
                case 'On-the-Job Trainee (OJT)':
                    $s4 = 'checked="checked"';
                    break;
            }


            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data ;">
                
                    <input type="checkbox" name="agree1" value="1" readonly="true" ' . $s1 . '/>  
                    <label for="agree1" style="font-size: 11;">
                    <span class="display:inline-block;width:100px;">Probationary/For Regular </span>
                    </label>
                   
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(565, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="agree2" value="2" readonly="true" ' . $s2 . '/>  
                    <label for="agree2" style="font-size: 11; ">
                    <span class="display: inline-block;width:100px;">Contractual/Temporary</span>
                    </label>
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(30, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(20, 0, "for", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(100, 0, $months1, 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(30, 0, "mos.;", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(100, 0, $days1, 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(30, 0, "days", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(270, 0, "", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="agree3" value="3" readonly="true" ' . $s3 . '/>  
                    <label for="agree3" style="font-size: 11;">
                    <span class="display: inline-block;width: 200px;">Project Based</span>
                    </label>
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(75, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(20, 0, "for", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(100, 0, $months2, 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(30, 0, "mos.;", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(100, 0, $days2, 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(30, 0, "days", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(225, 0, "", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            $html = '<form  action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="agree4" value="4" readonly="true" ' . $s4 . '/>  
                    <label for="agree4" style="font-size: 11; ">
                    <span class="display: inline-block;width: 100px;">On-the-Job Trainee (OJT)</span>
                    </label>
                    </form>';
            PDF::writeHTML($html, false, 1, true, 0);
            PDF::MultiCell(3, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(592, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);



            // $italic
            PDF::SetFont($italic, '', $fontsize);
            PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(27, 0, "Sex:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(40, 0, "" . (isset($data[0]['gpref']) ? $data[0]['gpref'] : ''), 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(28, 0, "Age:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(40, 0, "" . (isset($data[0]['agerange']) ? $data[0]['agerange'] : ''), 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(70, 0, "Civil Status:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(60, 0, (isset($data[0]['civilstatus']) ? $data[0]['civilstatus'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            // PDF::SetFont($fontbold, 'B', $fontsize);
            PDF::MultiCell(140, 0, "Educational Attachment:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(295, 0, (isset($data[0]['educlevel']) ? $data[0]['educlevel'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarialB, '', 9);
            PDF::MultiCell(720, 0, "", '', 'C', false, 1);

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(70, 0, "Experience: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(630, 0, (isset($data[0]['qualification']) ? ucfirst(strtolower($data[0]['qualification'])) : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarial, '', 9);
            PDF::MultiCell(720, 0, "", '', 'C', false, 1);

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(85, 0, "Special Skills: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(615, 0, (isset($data[0]['skill']) ? ucfirst(strtolower($data[0]['skill'])) : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarial, '', 9);
            PDF::MultiCell(720, 0, "", '', 'C', false, 1);

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(115, 0, "Other Requirements: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(605, 0, '', 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarial, '', 9);
            PDF::MultiCell(720, 0, "", '', 'C', false, 1);

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(148, 0, "Equipment to be Handled: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(572, 0, "", 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);


            PDF::SetFont($fontarial, '', 9);
            PDF::MultiCell(720, 0, "", '', 'C', false, 1);

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(80, 0, "Job Summary: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(640, 15, '' . (isset($data[0]['jobsumm']) ? ucfirst(strtolower($data[0]['jobsumm'])) : ''), 'B', 'L', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(720, 21, "A Job Description (Draft) of the required personnel must be attached and submitted together with this form.", 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(100, 0, "Requested by:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(250, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(100, 0, "Noted /Endorsed by:", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(150, 0, "" . $config['params']['dataparams']['requested'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(200, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            $endorsedby = $config['params']['dataparams']['noted'];
            PDF::MultiCell(150, 0, "" . $endorsedby, 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(150, 0, "(Name & Signature) ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(200, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(150, 0, "(Name & Signature) ", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(150, 0, "Recommending Approval:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(200, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(170, 0, "APPROVED / DISAPPROVED :", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($fontarial, '', $fontsize);
            $reconmendapp = $config['params']['dataparams']['approved'];
            PDF::MultiCell(150, 0, "" . $reconmendapp, 'B', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(200, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            $approved = $config['params']['dataparams']['approved2'];
            PDF::MultiCell(150, 0, "" . $approved, 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);


            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(150, 0, "(Name & Signature) ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(200, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(150, 0, "President ", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(720, 0, "", 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(720, 0, "For processing: (Human Resource Department)", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(80, 0, "Received by:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(150, 0, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(120, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(35, 0, "Date: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(100, 0, "", 'B', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(120, 0, "Position Filled-up by:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(150, 0, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::MultiCell(80, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarialB, '', $fontsize);
            PDF::MultiCell(80, 0, "Effective Date:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', false);
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(100, 0, "", 'B', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', false);


            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(0, 0, "\n\n");
            PDF::MultiCell(720, 0, "cc: Human resource / concerned department", '', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);
        }
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function newline($fontarial, $fontsize, $nextline)
    {

        if ($nextline) {
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(640, 0, "", 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);

            PDF::SetFont($fontarial, '', 9);
            PDF::MultiCell(720, 0, "", '', 'C', false, 1);
            PDF::SetFont($fontarial, '', $fontsize);

            PDF::MultiCell(720, 0, "", 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);
        } else {
            PDF::SetFont($fontarial, '', $fontsize);
            PDF::MultiCell(720, 0, "", 'B', 'L', false, 1, '',  '', true, 0, false, true, 20, 'M', false);
        }
    }
}
