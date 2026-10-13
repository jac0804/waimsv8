<?php

namespace App\Http\Classes\modules\modulereport\main;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\reportheader;
use App\Http\Classes\common\commonsbc;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class dd
{

    private $modulename = "Duty Detail Order";
    private $reportheader;
    private $commonsbc;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;


    public function __construct($params = null)
    {
        $this->fieldClass = new txtfieldClass($params);
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->reportheader = new reportheader;
        $this->commonsbc = new commonsbc;
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);

        $fields = ['prepared', 'checked', 'received', 'print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select
        'PDFM' as print,
        '' as prepared,
        '' as checked,
        '' as approved,
        '' as delivered,
        '' as received,
        'default' as reporttype
        "
        );
    }

    public function reportplotting($params, $data)
    {
        return $this->default_dd_layout($params, $data);
    }


    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];

        $query = "select head.docno, head.dateid, head.dateid2, emp.clientname, emp.client,
                  ifnull(emp.position,'') as designation, divs.divname, job.jobtitle, d.tmshifts
        from ddhead as head
        left join dddetail as d on d.trno = head.trno
        left join client as emp on emp.clientid = d.empid
        left join division as divs on divs.divid = head.divid
        left join employee on employee.empid = d.empid
        left join jobthead as job on job.line = employee.jobid
        where head.trno = '$trno'
        union all
        select head.docno, head.dateid, head.dateid2, emp.clientname, emp.client,
               ifnull(emp.position,'') as designation, divs.divname, job.jobtitle, d.tmshifts
        from hddhead as head
        left join hdddetail as d on d.trno = head.trno
        left join client as emp on emp.clientid = d.empid
        left join employee on employee.empid = d.empid
        left join jobthead as job on job.line = employee.jobid
        left join division as divs on divs.divid = head.divid
        where head.trno = '$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query, array($trno, $trno))), true);
        return $result;
    } //end fn

    private function getfirearms($config)
    {
        $trno = $config['params']['dataid'];

        $query = "select f.type, f.make, f.cal, f.serialno, f.expiry
        from ddfirearms
        left join firearms as f on f.line = ddfirearms.fireid
        where ddfirearms.trno = ?
        union all
        select f.type, f.make, f.cal, f.serialno, f.expiry
        from hddfirearms
        left join firearms as f on f.line = hddfirearms.fireid
        where hddfirearms.trno = ?";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query, array($trno, $trno))), true);
        return $result;
    } //end fn

    public function default_dd_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select code,name,address,tel, email from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');


        PDF::AddPage('p', array(816, 1248));
        PDF::SetMargins(30, 30);

        PDF::SetAutoPageBreak(false, 0);

        PDF::SetFont($font, '', 9);

        $header = !empty($data) ? $data[0] : null;
        $docno = isset($header['docno']) ? $header['docno'] : '';
        $dateid = isset($header['dateid']) ? $header['dateid'] : '';
        $dateid2 = isset($header['dateid2']) ? $header['dateid2'] : '';
        $paydate = isset($header['paydate']) ? $header['paydate'] : '';
        $paydate2 = isset($header['paydate2']) ? $header['paydate2'] : '';

        $this->reportheader->getheader($params);

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 20, "Duty Detail Order No.:", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(150, 20, $docno, 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(290, 20, " ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(150, 20, date('F j, Y', strtotime($dateid)), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "1. References: ", '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', '10');
        PDF::MultiCell(30, 0, "", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(600, 0, 'a. DOLE Department Order No. 150-16;', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', '10');
        PDF::MultiCell(30, 0, "", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(600, 0, 'b. Republic Act No. 10591, as amended, "Comprehensive Firearms and Ammunition Regulation Act"; and ', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', '10');
        PDF::MultiCell(30, 0, "", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(600, 0, 'c. Rule 39 Sections 154-156 of Republic Act No. 11917, "Strengthening the Private Security Industry Services Act".', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 20, '', '');

        PDF::SetFont($fontbold, '', '10');
        PDF::MultiCell(100, 0, '2. Purpose of Detail:', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', '10');
        PDF::MultiCell(140, 0, 'Post Security Service Duties.', 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 20, '', '');

        $from = $dateid  ? date('F j, Y', strtotime($dateid))  : '';
        $to   = $dateid2 ? date('F j, Y', strtotime($dateid2)) : '';
        PDF::SetFont($fontbold, '', '10');
        PDF::MultiCell(176, 0, '3. Duration/Inclusive Date of Detail:', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', '10');
        PDF::MultiCell(500, 0, 'From ' . $from . ' to ' . $to, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 20, '', '');

        PDF::SetFont($fontbold, '', '10');
        PDF::MultiCell(10, 0, '4.', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', '10');
        PDF::MultiCell(680, 0, 'The following security personnel is/are hereby assigned to render post security service duties in place/s indicated and are hereby issued agency/company/government owned firearms:', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');
    }

    private function default_dd_tablehead($font, $fontbold)
    {
        $x = 30;
        $y = PDF::getY();

        PDF::SetLineStyle(array('width' => 0.8, 'dash' => 0, 'color' => array(0, 0, 0)));
        PDF::SetFont($fontbold, '', 10);

        PDF::MultiCell(150, 50, 'NAME OF GUARD/S', 'LT', 'C', false, 0, $x, $y, true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 50, 'DESIGNATION', 'T', 'C', false, 0, $x + 150, $y, true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(140, 50, "SPECIFIC\nPOST/STATION", 'T', 'C', false, 0, $x + 260, $y, true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 50, "TIME OF\nSHIFT", 'T', 'C', false, 0, $x + 400, $y, true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(296, 20, 'FIREARMS INFORMATION', 'LTR', 'C', false, 0, $x + 460, $y, true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(55, 30, 'Kind', 'L', 'C', false, 0, $x + 460, $y + 20, true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 30, 'Make-Cal', '', 'C', false, 0, $x + 515, $y + 20, true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 30, 'Serial No.', '', 'C', false, 0, $x + 590, $y + 20, true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(91, 30, 'License Validity', 'R', 'C', false, 1, $x + 665, $y + 20, true, 0, false, true, 0, 'M', true);
    }

    private function default_dd_tableend()
    {
        PDF::SetFont('', '', 5);
        PDF::MultiCell(460, 0, '', 'LB', 'L', false, 0);
        PDF::MultiCell(296, 0, '', 'LRB', 'L', false, 1);
    }

    public function default_dd_layout($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];


        $count = 27;
        $x = 30;

        $forceSinglePage = false;

        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->default_dd_header($params, $data);


        $emps = array();
        if (!empty($data)) {
            foreach ($data as $row) {
                if (!empty($row['clientname'])) {
                    $emps[] = $row;
                }
            }
        }

        $fas = $this->getfirearms($params);
        if (!is_array($fas)) {
            $fas = array();
        }

        $rowH = 26;

        $this->default_dd_tablehead($font, $fontbold);

        $rows = max(count($emps), count($fas), 1);
        $countarr = 0;


        for ($i = 0; $i < $rows; $i++) {

            $clientname = isset($emps[$i]['clientname']) ? $emps[$i]['clientname'] : '';
            $designation = isset($emps[$i]['jobtitle']) ? $emps[$i]['jobtitle'] : '';
            $post = isset($emps[$i]['divname']) ? $emps[$i]['divname'] : '';
            $shift = isset($emps[$i]['tmshifts']) ? $emps[$i]['tmshifts'] : '';

            $kind = isset($fas[$i]['type']) ? trim($fas[$i]['type']) : '';
            $make = isset($fas[$i]['make']) ? trim($fas[$i]['make']) : '';
            $cal = isset($fas[$i]['cal']) ? trim($fas[$i]['cal']) : '';
            $makecal = $make . ($cal != '' ? ' ' . $cal : '');
            $serial = isset($fas[$i]['serialno']) ? trim($fas[$i]['serialno']) : '';

            $expiry = isset($fas[$i]['expiry']) ? $fas[$i]['expiry'] : '';
            $validity = '';
            if ($expiry != '' && substr($expiry, 0, 4) != '0000' && strtotime($expiry)) {
                $validity = date('M d, Y', strtotime($expiry));
            }

            if (!$forceSinglePage && $countarr == $count) {
                $this->default_dd_tableend();
                PDF::AddPage('p', array(816, 1248));
                PDF::SetY(40);
                $this->default_dd_tablehead($font, $fontbold);
                $countarr = 0;
            }

            $y = PDF::getY();
            PDF::SetFont($font, '', 10);

            PDF::MultiCell(150, $rowH, ' ' . $clientname, 'L', 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M', true);
            PDF::MultiCell(110, $rowH, ' ' . $designation, '', 'L', false, 0, $x + 150, $y, true, 0, false, true, $rowH, 'M', true);
            PDF::MultiCell(140, $rowH, ' ' . $post, '', 'C', false, 0, $x + 260, $y, true, 0, false, true, $rowH, 'M', true);
            PDF::MultiCell(60, $rowH, ' ' . $shift, '', 'L', false, 0, $x + 400, $y, true, 0, false, true, $rowH, 'M', true);

            PDF::MultiCell(55, $rowH, $kind, 'L', 'C', false, 0, $x + 460, $y, true, 0, false, true, $rowH, 'M', true);
            PDF::MultiCell(75, $rowH, $makecal, '', 'C', false, 0, $x + 515, $y, true, 0, false, true, $rowH, 'M', true);
            PDF::MultiCell(75, $rowH, $serial, '', 'C', false, 0, $x + 590, $y, true, 0, false, true, $rowH, 'M', true);
            PDF::MultiCell(91, $rowH, $validity, 'R', 'C', false, 0, $x + 665, $y, true, 0, false, true, $rowH, 'M', true);

            PDF::SetY($y + $rowH);
            $countarr++;


            if ($i < count($emps) - 1 && $countarr < $count) {
                $ly = PDF::getY();
                PDF::SetLineStyle(array('width' => 0.5, 'dash' => '2,2', 'color' => array(0, 0, 0)));
                PDF::Line($x, $ly, $x + 460, $ly);
                PDF::SetLineStyle(array('width' => 0.8, 'dash' => 0, 'color' => array(0, 0, 0)));
            }
        }

        $this->default_dd_tableend();

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 20, '', '');

        if (!$forceSinglePage && PDF::getY() > 1050) {
            PDF::AddPage('p', array(816, 1248));
            PDF::SetY(40);
        }

        PDF::SetFont($fontbold, '', '10');
        PDF::MultiCell(700, 0, '5. Specific Instructions:', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        $instructions = array(
            'a.' => 'Shall be issued by the Private Security Agency (PSA), Company Guard Force (CGF), and Government Security Force (GSF) Managers and/or the Security Officers to their Posted security personnel while carrying/bearing of firearms;',
            'b.' => 'Shall serve as authority to bear the issued firearms while in the actual performance of guard duties in respective specific guard posts/establishment/compound of the principal/client, and prescribed uniform;',
            'c.' => 'Shall serve as the authority to bear and transport the firearms outside of the respective guard posts and official registered residence of the firearms for routine rotation, repair, new posting, recall of the firearms, and escorting large amount of cash or valuables outside its specified post within 24 hours only. If it is beyond 24 hours, a permit to transport is required to be issued by FEO;',
            'd.' => 'Shall be valid for thirty (30) days, renewable until termination of the security service contract with the principal/client;',
            'e.' => 'The issued firearms shall be licensed and a copy of this DDO shall be in the actual possession of the posted security personnel. Electronic copy of this DDO may be presented in lieu of the original during any inspections. Provided that the original copy is presented when required by an authorized PNP personnel; and',
            'f.' => 'In the absence of the Admin Officer/Operations Manager, the Security Officer which has jurisdiction of a certain detachment/post shall sign this DDO.',
            'g.' => 'Remarks;'
        );

        foreach ($instructions as $letter => $text) {
            PDF::SetFont($font, '', '10');

            if (!$forceSinglePage && PDF::getY() + PDF::getStringHeight(650, $text) > 1150) {
                PDF::AddPage('p', array(816, 1248));
                PDF::SetY(40);
                PDF::SetFont($font, '', '10');
            }

            PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'T', true);
            PDF::MultiCell(20, 0, $letter, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'T', true);
            PDF::MultiCell(650, 0, $text, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'T', true);

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(700, 10, '', '');
        }

        PDF::SetFont($font, '', '10');
        PDF::MultiCell(50, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'T', true);
        PDF::MultiCell(650, 0, '"THIS IS NOT AN AUTHORITY TO BEAR FIREARM OUTSIDE THE PREMISES OF THE SPECIFIED POSTS/ESTABLISHMENT OF THE PRINCIPAL NOR SHALL THE FIREARM DESCRIBED HEREIN LEAVE THE POSTS/STATIONS OF THE PRINCIPAL."', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'T', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 10, '', '');

        if (!$forceSinglePage && PDF::getY() > 1050) {
            PDF::AddPage('p', array(816, 1248));
            PDF::SetY(40);
        }

        PDF::SetFont($fontbold, '', '10');
        PDF::MultiCell(700, 0, '6. For strict compliance.', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 40, '', '');

        $custodian = 'ATTY. EDGARDO C. SORIANO';
        $custodiantitle = 'Authorized Bonded Firearms Custodian';
        $manager = 'RODEL C. VILLAMIN';
        $managertitle = 'ADMIN OFFICER/OPERATIONS MANAGER';

        PDF::SetFont($font, '', '10');
        PDF::MultiCell(50, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', '10');
        PDF::MultiCell(50, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, $custodian, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, $manager, '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', '10');
        PDF::MultiCell(50, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, $custodiantitle, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, $managertitle, '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(50, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, '(Signature over printed name)', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, '(Signature over printed name)', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        $totalpages = PDF::getNumPages();
        for ($p = 1; $p <= $totalpages; $p++) {
            PDF::setPage($p);
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(700, 0, 'Page ' . $p . ' of ' . $totalpages, '', 'R', false, 1, 40, 1200);
        }
        PDF::lastPage();

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
