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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class hs
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
        $fields = ['radioprint', 'radiostatus', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        data_set($col1, 'radiostatus.label', 'Report Type');
        data_set($col1, 'prepared.label', 'HR Manager');
        data_set($col1, 'approved.label', 'General Manager');
        data_set($col1, 'received.label', 'Chief Executive Officer');

        data_set($col1, 'radiostatus.options', [
            ['label' => 'Personnel Action Form (PAF)', 'value' => '2', 'color' => 'blue']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $signatories = $this->othersClass->getSignatories($config);
        $hr = '';
        $gm = '';
        $ceo = '';

        foreach ($signatories as $key => $value) {
            switch ($value->fieldname) {
                case 'prepared':
                    $hr = $value->fieldvalue;
                    break;
                case 'approved':
                    $gm = $value->fieldvalue;
                    break;
                case 'received':
                    $ceo = $value->fieldvalue;
                    break;
            }
        }
        return $this->coreFunctions->opentable("select 
                'PDFM' as print,
                '2' as status,
                '$gm' as approved,
                '$ceo' as received,
                '$hr' as prepared");
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $reporttype = $config['params']['dataparams']['status'];

        $query = "select head.trno, head.docno, date(head.dateid) as dateid,concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
                    dept.clientname as deptname,jt.jobtitle,date(head.effdate) as effdate,date(head.feffdate) as feffdate,
                    head.fbasicrate,head.tbasicrate,stat.empstatus as empstat,head.hsperiod,
                    date(emp.empstatdate) as empstatdate,date(emp.jobdate) as jobdate,if(year(emp.hired)=1970,null,date(emp.hired)) as hired,
                    date(rate.dateeffect) as ratedate,divi.divcode as division,sup.clientname as supervisor,
                    emp.branchid,br.clientname as branch,head.salarytype,date(emp.regular) as regular,head.empid, head.tcola
                from eschange as head
                left join employee as emp on emp.empid=head.empid
                left join client as dept on dept.clientid=emp.deptid
                left join hrisnum as num on num.trno = head.trno
                left join jobthead as jt on jt.line = emp.jobid
                left join empstatentry as stat on stat.line = emp.empstatus
                left join ratesetup as rate on rate.empid=head.empid
                left join client as sup on sup.clientid=emp.supervisorid
                left join division as divi on divi.divid=emp.divid
                left join client as br on br.clientid=emp.branchid
                where num.trno = '$trno' and num.doc='HS'
                union all
                select head.trno, head.docno, date(head.dateid) as dateid,concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
                    dept.clientname as deptname,jt.jobtitle,date(head.effdate) as effdate,date(head.feffdate) as feffdate,
                    head.fbasicrate,head.tbasicrate,stat.empstatus as empstat,head.hsperiod,
                    date(emp.empstatdate) as empstatdate,date(emp.jobdate) as jobdate,if(year(emp.hired)=1970,null,date(emp.hired)) as hired,
                    date(rate.dateeffect) as ratedate,divi.divcode as division,sup.clientname as supervisor,
                    emp.branchid,br.clientname as branch,head.salarytype,date(emp.regular) as regular,head.empid, head.tcola
                from heschange as head
                left join employee as emp on emp.empid=head.empid
                left join client as dept on dept.clientid=emp.deptid
                left join hrisnum as num on num.trno = head.trno
                left join jobthead as jt on jt.line = emp.jobid
                left join empstatentry as stat on stat.line = emp.empstatus
                left join ratesetup as rate on rate.empid=head.empid
                left join client as sup on sup.clientid=emp.supervisorid
                left join division as divi on divi.divid=emp.divid
                left join client as br on br.clientid=emp.branchid
                where num.trno = '$trno' and num.doc='HS'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($config, $data)
    {
        $data = $this->report_default_query($config);
        $reporttype = $config['params']['dataparams']['status'];

        return $this->rpt_EC_PDF($config, $data);
    }

    //start Employee`s Compensation
    public function EC_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $f_size7 = 7;
        $f_size10 = 10;
        $f_size11 = 11;
        $f_size12 = 12;
        $f_size18 = 18;
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
        PDF::AddPage('p', [800, 1200]);
        PDF::SetMargins(20, 20);

        if (isset($data[0]['division'])) {
            switch ($data[0]['division']) {
                case '001':
                    PDF::Image($this->companysetup->getlogopath($config['params']) . 'paflogo.png', 40, 20, 720, 105);
                    break;
                case '002':
                    PDF::Image($this->companysetup->getlogopath($config['params']) . 'mbcpaflogo.png', 40, 20, 720, 105);
                    break;
                case '003':
                    PDF::Image($this->companysetup->getlogopath($config['params']) . 'ridefundpaf.png', 40, 20, 720, 105);
                    break;
            }
        }

        PDF::SetFont($font, '', 25);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetTextColor(0, 0, 0);

        PDF::MultiCell(0, 0, "\n\n\n");

        //start 1st row

        $date = $data[0]['dateid'];
        $date = date_create($date);
        $date = date_format($date, "M d, Y");

        $effdate = $data[0]['effdate'];
        $effdate = date_create($effdate);
        $effdate = date_format($effdate, "M d, Y");

        $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [192, 192, 192]];
        PDF::SetLineStyle($style);


        PDF::SetFillColor(229, 228, 226);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'TL', 'C', 1, 0);
        PDF::MultiCell(484, 0, "", 'TR', 'C', 1, 0);
        PDF::MultiCell(100, 0, "", 'T', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', 1, 0);
        PDF::MultiCell(484, 0, "PERSONNEL ACTION FORM - PAF", 'R', 'L', 1, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(96, 0, "DATE : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(118, 0, (isset($date) ? $date : ''), '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'BL', 'C', 1, 0);
        PDF::MultiCell(484, 0, "", 'BR', 'C', 1, 0);
        PDF::MultiCell(100, 0, "", 'B', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 1st row


        //start 2nd row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);
        PDF::MultiCell(484, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(100, 0, "", 'T', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(104, 0, "EMPLOYEE NAME : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $f_size10);
        PDF::MultiCell(380, 0, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'R', 'L', false, 0);

        if ($data[0]['hired'] != null) {
            $hired = date_format(date_create($data[0]['hired']), "M d, Y");
        } else {
            $hired = '';
        }

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(96, 0, "DATE HIRED : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(118, 0, $hired, '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(484, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(100, 0, "", 'B', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 2nd row

        //start 3rd row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);

        PDF::MultiCell(242, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(242, 0, "", 'TR', 'C', false, 0);

        PDF::MultiCell(100, 0, "", 'T', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(242, 0, "DEPARTMENT : ", 'R', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(236, 0, "REPORTING TO : ", '', 'L', false, 0);
        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(96, 0, "POSITION : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(118, 0, '', '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(248, 0, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(242, 0, (isset($data[0]['supervisor']) ? $data[0]['supervisor'] : ''), 'L', 'C', false, 0);
        PDF::MultiCell(230, 0, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(242, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(242, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(100, 0, "", 'B', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 3rd row

        //start 4th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "HR ACTION", 'LR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 4th row


        //start 5th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);

        PDF::MultiCell(242, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(242, 0, "", 'TR', 'C', false, 0);

        PDF::MultiCell(100, 0, "", 'T', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        // $datefromqry = $this->coreFunctions->opentable("select date(effdate) as effdate from eschange where empid = ? and trno < ? union all
        //         select date(effdate) as effdate from heschange where empid = ? and trno < ?
        //         order by trno desc limit 1", [$data[0]['empid'], $data[0]['trno'], $data[0]['empid'], $data[0]['trno']]);

        $datefrom = '';
        // if (!empty($datefrom)) {
        //     $datefrom = date_format(date_create($datefromqry[0]->effdate), "M d, Y");
        // }

        if ($data[0]['feffdate'] != null) {
            $datefrom = date_format(date_create($data[0]['feffdate']), "M d, Y");
        }

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(242, 0, "DATE FROM:  ", 'R', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(236, 0, "DATE TO : ", '', 'L', false, 0);
        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(96, 0, "PAF STATUS : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(118, 0, '', '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(248, 0, (isset($datefrom) ? $datefrom : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(242, 0, $effdate, 'L', 'C', false, 0);
        PDF::MultiCell(230, 0, (isset($data[0]['salarytype']) ? $data[0]['salarytype'] : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(242, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(242, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(100, 0, "", 'B', 'C', false, 0);
        PDF::MultiCell(130, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 5th row

        //start 6th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "STATUS DETAILS", 'LR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 6th row

        //start 7th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(435, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(435, 0, "CURRENT", 'LR', 'C', false, 0);
        PDF::MultiCell(285, 0, "EFFECTIVE DATE ", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(435, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 7th row

        //start 8th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(150, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(144, 0, "EMPLOYMENT STATUS", 'R', 'L', false, 0);
        $empstat = $data[0]['empstat'];
        $empstatdate = date_format(date_create($data[0]['empstatdate']), "M d, Y");
        $jobdate = date_format(date_create($data[0]['jobdate']), "M d, Y");
        $regular = date_format(date_create($data[0]['regular']), "M d, Y");

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(285, 0, (isset($empstat) ? $empstat : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(285, 0, $regular, 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, '', '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(150, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 8th row

        //start 9th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(150, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        $reassigndate = $this->coreFunctions->getfieldvalue("designation", "date(effectdate)", "empid=?", [$data[0]['empid']]);

        $reassigndate = '';
        if (!empty($reassigndate)) {
            $reassigndate = date_format(date_create($reassigndate), "M d, Y");
        }

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(144, 0, "POSITION", 'R', 'L', false, 0);

        $job = $data[0]['jobtitle'];
        PDF::SetFont($fontbold, '', $f_size11);

        PDF::MultiCell(285, 0, (isset($job) ? $job : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(285, 0, (isset($reassigndate) ? $reassigndate : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(150, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 9th row

        //start 10th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(150, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(144, 0, "DEPARTMENT", 'R', 'L', false, 0);
        $dept = $data[0]['deptname'];

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(285, 0, (isset($dept) ? $dept : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(285, 0, (isset($data[0]['branch']) ? $data[0]['branch'] : ''), 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(150, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(285, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 10th row

        //start 11th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "COMPENSATION DETAILS", 'LR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        //end 11th row

        //start 12th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "PREVIOUS", 'LR', 'C', false, 0);
        PDF::MultiCell(360, 0, "NEW", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        //end 12th row


        //start 13th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);

        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);
        PDF::MultiCell(161, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);
        PDF::MultiCell(187, 0, "", 'TR', 'C', false, 0);

        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);
        PDF::MultiCell(114, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);
        PDF::MultiCell(114, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'TL', 'C', false, 0);
        PDF::MultiCell(114, 0, "", 'TR', 'C', false, 0);

        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);

        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(161, 0, "SALARY ", 'R', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::SetFont($fontbold, '', $f_size11);

        // $fbasicrate = $this->coreFunctions->getfieldvalue("ratesetup", "basicrate", "empid=? and year(dateend)='9999'", [$data[0]['empid']]);

        // if (empty($fbasicrate)) {
        //     $fbasicrate = '-';
        // }

        $fbasicrate = '-';
        if ($data[0]['fbasicrate'] != 0) {
            $fbasicrate = number_format($data[0]['fbasicrate'], 2);
        }

        PDF::MultiCell(187, 0, $fbasicrate . '   ', 'R', 'R', false, 0);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, "BASIC ", 'R', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, "COLA", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, "EFFECTIVE DATE ", 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($fontbold, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);

        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(161, 0, " ", 'R', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(187, 0, " ", 'R', 'L', false, 0);

        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, '', 'R', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, "", '', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, '', 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        $rateeffect = date_format(date_create($data[0]['effdate']), "M d, Y");
        $tbasicrate = '-';
        $computedbasic = 0;
        if ($data[0]['tbasicrate'] != 0) {
            $tbasicrate = number_format($data[0]['tbasicrate'], 2);
        }

        $tcola = '-';
        if ($data[0]['tcola'] != 0) {
            $tcola = number_format($data[0]['tcola'], 2);
        }

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);

        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(161, 0, " ", 'R', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(187, 0, " ", 'R', 'L', false, 0);

        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, $tbasicrate . ' ', 'R', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, $tcola . ' ', '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(114, 0, $rateeffect, 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);

        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(161, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(187, 0, "", 'BR', 'C', false, 0);

        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(114, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(114, 0, "", 'BR', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'BL', 'C', false, 0);
        PDF::MultiCell(114, 0, "", 'BR', 'C', false, 0);

        PDF::MultiCell(20, 0, "", '', 'C', false);

        //end 13th row

    }

    public function rpt_EC_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $hr = strtoupper($config['params']['dataparams']['prepared']);
        $gm = strtoupper($config['params']['dataparams']['approved']);
        $ceo = strtoupper($config['params']['dataparams']['received']);

        $f_size7 = 7;
        $f_size10 = 10;
        $f_size11 = 11;
        $f_size12 = 12;
        $f_size18 = 18;
        $count = 35;
        $page = 35;
        $font = "";
        $fontbold = "";
        $totalallow = 0;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->EC_header_PDF($config, $data);

        //start 14th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "OTHER PRIVILAGES (ALLOWANCES)", 'LR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "AMOUNT", 'LR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "EFFECTIVE DATE", 'LR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        //end 14th row


        //////looping for allowances

        $qry = "select date(a.dateeffect) as alloweff,a.allowance,acc.codename as allowname
                from allowsetuptemp as a
                join eschange as hs on hs.trno=a.refx and hs.empid=a.empid
                left join paccount as acc on acc.line=a.acnoid
                where hs.trno = '" . $data[0]['trno'] . "' and a.isliquidation =0
                union all
                select date(a.dateeffect) as alloweff,a.allowance,acc.codename as allowname
                from allowsetup as a
                join heschange as hs on hs.trno=a.refx and hs.empid=a.empid
                left join paccount as acc on acc.line=a.acnoid
                where hs.trno = '" . $data[0]['trno'] . "' and a.isliquidation =0 and a.voiddate is null
                union all
                select date(a.dateeffect) as alloweff,a.allowance,acc.codename as allowname
                from allowsetup as a
                left join heschange as hs on hs.trno=a.refx and hs.empid=a.empid
                left join paccount as acc on acc.line=a.acnoid
                where a.empid=" . $data[0]['empid'] . " and a.refx <> '" . $data[0]['trno'] . "' and a.refx<>0 and a.isliquidation =0 and a.voiddate is null
                order by alloweff";

        $allow = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        // $totalallow = $this->coreFunctions->getfieldvalue("sum(allowance)", "allowsetuptemp", "refx=?", [$data[0]['trno']]);

        for ($i = 0; $i < count($allow); $i++) {
            $maxrow = 1;
            $adate = date_format(date_create($allow[$i]['alloweff']), "M d, Y");
            $totalallow += $allow[$i]['allowance'];
            $arr_allowname = $this->reporter->fixcolumn([$allow[$i]['allowname']], '70', 0);
            $arr_allow = $this->reporter->fixcolumn([number_format($allow[$i]['allowance'], 2)], '16', 0);
            $arr_alloweff = $this->reporter->fixcolumn([$adate], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_allowname, $arr_allow, $arr_alloweff]);

            PDF::SetFont($font, '', $f_size7);
            PDF::MultiCell(20, 0, "", '', 'C', false, 0);
            PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'TLR', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'TLR', 'C', false, 0);
            PDF::MultiCell(20, 0, "", '', 'C', false);


            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($fontbold, '', $f_size11);
                PDF::MultiCell(20, 0, "", '', 'C', false, 0);
                PDF::MultiCell(360, 0, (isset($arr_allowname[$r]) ? $arr_allowname[$r] : ''), 'LR', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(180, 0, (isset($arr_allow[$r]) ? $arr_allow[$r] : ''), 'LR', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(180, 0, (isset($arr_alloweff[$r]) ? $arr_alloweff[$r] : ''), 'LR', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(20, 0, "", '', 'C', false);
            }

            PDF::SetFont($font, '', $f_size7);
            PDF::MultiCell(20, 0, "", '', 'C', false, 0);
            PDF::MultiCell(360, 0, "", 'LRB', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'LRB', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'LRB', 'C', false, 0);
            PDF::MultiCell(20, 0, "", '', 'C', false);
        }

        /////////////////////////////////////////////////////

        //start 14th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "SUBJECT FOR LIQUIDATION (ALLOWANCES)", 'LR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "AMOUNT", 'LR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "EFFECTIVE DATE", 'LR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        //end 14th row


        //////looping for allowances

        $qry = "select date(a.dateeffect) as alloweff,
                    a.allowance,acc.codename as allowname
                from allowsetuptemp as a
                left join eschange as hs on hs.trno=a.refx and hs.empid=a.empid
                left join paccount as acc on acc.line=a.acnoid
                where hs.trno = '" . $data[0]['trno'] . "' and a.isliquidation =1
                union all
                select date(a.dateeffect) as alloweff,a.allowance,acc.codename as allowname
                from allowsetup as a
                left join heschange as hs on hs.trno=a.refx and hs.empid=a.empid
                left join paccount as acc on acc.line=a.acnoid
                where hs.trno = '" . $data[0]['trno'] . "' and a.isliquidation =1 and a.voiddate is null
                union all
                select date(a.dateeffect) as alloweff,a.allowance,acc.codename as allowname
                from allowsetup as a
                left join heschange as hs on hs.trno=a.refx and hs.empid=a.empid
                left join paccount as acc on acc.line=a.acnoid
                where a.empid=" . $data[0]['empid'] . " and a.refx <> '" . $data[0]['trno'] . "' and a.refx<>0 and a.isliquidation =1 and a.voiddate is null
                order by alloweff";

        $allow = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($allow); $i++) {
            $maxrow = 1;
            $adate = date_format(date_create($allow[$i]['alloweff']), "M d, Y");
            $totalallow += $allow[$i]['allowance'];
            $arr_allowname = $this->reporter->fixcolumn([$allow[$i]['allowname']], '70', 0);
            $arr_allow = $this->reporter->fixcolumn([number_format($allow[$i]['allowance'], 2)], '16', 0);
            $arr_alloweff = $this->reporter->fixcolumn([$adate], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_allowname, $arr_allow, $arr_alloweff]);

            PDF::SetFont($font, '', $f_size7);
            PDF::MultiCell(20, 0, "", '', 'C', false, 0);
            PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'TLR', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'TLR', 'C', false, 0);
            PDF::MultiCell(20, 0, "", '', 'C', false);


            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($fontbold, '', $f_size11);
                PDF::MultiCell(20, 0, "", '', 'C', false, 0);
                PDF::MultiCell(360, 0, (isset($arr_allowname[$r]) ? $arr_allowname[$r] : ''), 'LR', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(180, 0, (isset($arr_allow[$r]) ? $arr_allow[$r] : ''), 'LR', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(180, 0, (isset($arr_alloweff[$r]) ? $arr_alloweff[$r] : ''), 'LR', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(20, 0, "", '', 'C', false);
            }

            PDF::SetFont($font, '', $f_size7);
            PDF::MultiCell(20, 0, "", '', 'C', false, 0);
            PDF::MultiCell(360, 0, "", 'LRB', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'LRB', 'C', false, 0);
            PDF::MultiCell(180, 0, "", 'LRB', 'C', false, 0);
            PDF::MultiCell(20, 0, "", '', 'C', false);
        }
















        ////////////////////////////////////////////////

        $gtotal = 0;

        switch (strtoupper($data[0]['hsperiod'])) {
            case "DAILY":
            case "DAILY RATE":
                $gtotal = $totalallow + (($data[0]['tbasicrate'] * 313) / 12);
                break;
            default:
                $gtotal = $totalallow + $data[0]['tbasicrate'];
                break;
        }

        //start 15th row
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "", 'TLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "TOTAL INCOME PACKAGES", 'LR', 'C', 1, 0);

        PDF::MultiCell(180, 0, number_format($gtotal, 2), 'LR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "", 'LR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', 1, 0);

        PDF::MultiCell(180, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(180, 0, "", 'BLR', 'C', 1, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        //end 15th row

        //NOTE
        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(714, 0, "NOTE", 'R', 'L', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetTextColor(255, 49, 49); //neon red

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 15, "", '', 'C', false, 0);
        PDF::MultiCell(720, 15, "ALL ALLOWANCES AND/OR PRIVILAGES MAY BE TAKEN ANY TIME UPON THE DISCRETION OF THE MANAGEMENT.", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 15, "", '', 'C', false);

        PDF::MultiCell(20, 15, "", '', 'C', false, 0);
        PDF::MultiCell(720, 15, "CRA - a discretionary allowance given in compensation of your current position handled and cannot be transferrable once", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 15, "", '', 'C', false);

        PDF::MultiCell(20, 15, "", '', 'C', false, 0);
        PDF::MultiCell(720, 15, "transferred to other dept. (Shall be released through payroll)", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 15, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);
        ///

        PDF::SetTextColor(0, 0, 0);

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, "EMPLOYEE SIGNATURE : ", 'R', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, "IMMEDIATE SUPERIOR : ", 'R', 'L', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', 50);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'LR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        $supervisor = $data[0]['supervisor'];

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, strtoupper($data[0]['empname']), 'R', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, strtoupper($supervisor), 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', 15);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);


        //////////////////////////////////////

        PDF::SetFont($font, '', $f_size7);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, "HUMAN RESOURCE MANAGER : ", 'R', 'L', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, "GENERAL MANAGER / DIRECT MANAGER : ", 'R', 'L', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', 20);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'LR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, "$hr", 'R', 'C', false, 0);
        PDF::MultiCell(6, 0, "", 'L', 'L', false, 0);
        PDF::MultiCell(354, 0, "$gm", 'R', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(360, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        //////////////////////////////////////

        PDF::SetFont($font, '', 30);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($fontbold, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "$ceo", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', $f_size11);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "CHIEF EXECUTIVE OFFICER", 'LR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(20, 0, "", '', 'C', false, 0);
        PDF::MultiCell(720, 0, "", 'BLR', 'C', false, 0);
        PDF::MultiCell(20, 0, "", '', 'C', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
