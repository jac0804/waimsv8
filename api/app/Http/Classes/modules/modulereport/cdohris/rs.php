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
use DateTime;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class rs
{
    private $modulename;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
        $trno = $config['params']['trno'];

        return $this->coreFunctions->opentable(
            "select  'PDFM' as print"
        );
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];

        $query = "select head.docno,head.ourref, date(head.dateid) as dateid,c.client as empcode,
                        concat(emp.emplast,',',emp.empfirst,' ',emp.empmiddle) as empname,
                        stock.supid,date(stock.tdate1) as tdate1,
                        oldjob.jobtitle as oldjob,oldbranch.clientname as oldbranch,
                        newjob.jobtitle as newjob,newbranch.clientname as newbranch,
                        client.clientname as notedby1,head.createby,ifnull(d.clientname,'') as newdept,
                        stock.rem,stock.category, cat.category as categoryname
                    from rashead as head
                    left join rasstock as stock on stock.trno=head.trno
                    left join client as c on c.clientid=stock.empid
                    left join employee as emp on emp.empid=stock.empid
                    left join jobthead as oldjob on oldjob.line=stock.jobid
                    left join client as oldbranch on oldbranch.clientid =stock.branchid
                    left join jobthead as newjob on newjob.line=stock.ndesid
                    left join client as newbranch on newbranch.clientid = stock.tobranchid
                    left join client on client.clientid=head.notedid
                    left join client as d on d.clientid = stock.deptid
                    left join reqcategory as cat on cat.line=stock.category
                    where head.trno= '$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($config, $data)
    {
        $data = $this->report_default_query($config);
        $str = $this->default_RS_PDF($config, $data);
        return $str;
    }

    public function rpt_RS_HEADER_PDF($config, $data)
    {
        $border = '1px solid';
        $font_size = '11';
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $fsize9 = 9;
        $fsize10 = 9;
        $fsize12 = 12;
        $font = "";
        $fontbold = "";

        $count = 55;
        $page = 54;

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
        PDF::AddPage('p', [610, 820]);
        PDF::SetMargins(20, 20);

        $y = 10;

        PDF::Image($this->companysetup->getlogopath($config['params']) . 'cyclesM.png', 50, $y,  120, 100);

        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fontbold, '', $fsize10);
        PDF::MultiCell(200, 20, 'HRIS#' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, 370,  40);

        PDF::SetTextColor(65, 105, 225);
        PDF::MultiCell(200, 20, 'HRD#' . (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 0, 370,  58);

        PDF::SetTextColor(0, 0, 0);
        $date = isset($data[0]['dateid']) ? $data[0]['dateid'] : '';
        $dt = new DateTime($date);
        $formatted = strtoupper($dt->format('MjY'));

        PDF::SetFont($font, '', $fsize10);
        PDF::MultiCell(80, 20, 'Date Issued: ', '', 'L', false, 0, 370,  76);

        PDF::SetFont($fontbold, '', $fsize10);
        PDF::MultiCell(100, 20, $formatted, '', 'L', false, 0, 430,  76);

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::SetFont($fontbold, '', $fsize9);
        PDF::SetXY(50, PDF::GetY());

        PDF::MultiCell(510, 15, 'HUMAN RESOURCE DEPARTMENT', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($font, '', $fsize9);
        PDF::SetX(50);
        PDF::MultiCell(510, 15, 'Western Kolambog, Lapasan National Highway', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetX(50);
        PDF::MultiCell(510, 15, 'City : Cagayan De Oro, 9000', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetX(50);
        PDF::MultiCell(510, 15, 'Phone : (088) 881-2844 • 09177118913', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetX(50);
        PDF::MultiCell(510, 15, 'Email : cdo2cycles.hrd@gmail.com', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n\n");


        PDF::SetFont($fontbold, '', $fsize9);
        PDF::SetX(50);
        PDF::MultiCell(510, 18, 'To : All Concerned Personnel', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($font, '', $fsize9);
        PDF::SetX(50);
        PDF::MultiCell(510, 15, 'Subject: Re-Assignment / Tour of Duty', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetX(50);
        PDF::MultiCell(510, 30, 'This is to formally inform new work assignment of the following Personnel.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    }

    public function default_RS_PDF($params, $data)
    {

        $font = "";
        $fontbold = "";
        $fsize9 = 9;
        $fontsize = "11";
        $fontsizes = "13";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->rpt_RS_HEADER_PDF($params, $data);
        // $employee = "";

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;
                $empcode = $data[$i]['empcode'];
                $empname = $data[$i]['empname'];
                $manid = $data[$i]['supid'];
                $gender = $this->coreFunctions->getfieldvalue("employee", "gender", "empid=?", [$manid]);
                $stat = $this->coreFunctions->getfieldvalue("employee", "status", "empid=?", [$manid]);
                $fname = $this->coreFunctions->getfieldvalue("employee", "empfirst", "empid=?", [$manid]);
                $mname = $this->coreFunctions->getfieldvalue("employee", "empmiddle", "empid=?", [$manid]);
                $lname = $this->coreFunctions->getfieldvalue("employee", "emplast", "empid=?", [$manid]);
                $managername = $fname . ' ' . $mname . ' ' . $lname;
                // $stat = $this->coreFunctions->getfieldvalue("employee", "status", "empid=?", [$manid]);
                $title = '';

                switch ($gender) {
                    case 'Female':
                    case 'FEMALE':
                        if ($stat == 'Married' || $stat == 'MARRIED') {
                            $title = 'Mrs.';
                        } else {
                            $title = 'Ms.';
                        }
                        break;
                    default:
                        $title = 'Mr.';
                        break;
                }


                $arr_empcode = $this->reporter->fixcolumn([$empcode], '15', 0);
                $arr_empname = $this->reporter->fixcolumn([$empname], '65', 0);

                $maxrow = $this->othersClass->getmaxcolumn([
                    $arr_empcode,
                    $arr_empname
                ]);
                for ($r = 0; $r < $maxrow; $r++) {


                    PDF::SetFont($fontbold, '', $fsize9);
                    PDF::SetX(50);
                    PDF::MultiCell(100, 0, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    $empname = isset($arr_empname[$r]) ? $arr_empname[$r] : '';
                    $dateval = $data[$i]['tdate1'];
                    $dthere = new DateTime($dateval);
                    $newformatteddate = strtoupper($dthere->format('M-j-Y'));
                    PDF::MultiCell(300, 0, strtoupper($empname), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                    PDF::SetFont($fontbold, '', $fsize9);
                    PDF::MultiCell(130, 0, 'Effective : ' . $newformatteddate, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

                    PDF::SetFont($font, '', 10);
                    PDF::MultiCell(0, 0, '', '', 'L');

                    $label = '';
                    switch ($data[$i]['categoryname']) {
                        case 'ASSIGNED':
                            $label = 'Assigned as ';
                            break;
                        case 'REASSIGNED':
                            $label = 'Reassigned from : ';
                            break;
                        case 'TRANSFERRED':
                            $label = 'Transferred from : ';
                            break;
                        case 'PROMOTED':
                            $label = 'Promoted from : ';
                            break;
                        case 'ALTERED':
                            $label = 'Altered from : ';
                            break;
                    }

                    $prevdesignation = $label . $data[$i]['oldjob'] . ' of ' . $data[$i]['oldbranch'] . ' to ';

                    $newdesignation = '<b>' . $data[$i]['newjob'] . ' of ' . $data[$i]['newdept'] . ' Department, ' . $data[$i]['newbranch'] . '</b>. ' . $data[$i]['rem'] . ' and will be directly reporting to ' . $title . ' ' . $managername . '.';

                    $arr_designation = $this->reporter->fixcolumn([$prevdesignation, $newdesignation], 110, 0);
                    $maxrow2 = $this->othersClass->getmaxcolumn([$arr_designation]);

                    for ($s = 0; $s < $maxrow2; $s++) {
                        if (PDF::getY() > 748) {
                            PDF::SetY(787);
                            PDF::SetFont($font, '', 8);
                            PDF::MultiCell(0, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R');

                            PDF::AddPage();
                            PDF::SetY(30);
                        }
                        PDF::SetFont($font, '', $fsize9);
                        PDF::SetX(50);
                        PDF::MultiCell(700, 15, isset($arr_designation[$s]) ? $arr_designation[$s] : '', '', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', false);
                    }
                }
            }
        }

        $this->rpt_RS_FOOTER_PDF($params, $data);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }



    public function default_RS_PDFs($params, $data)
    {
        $font = '';
        $fontbold = '';
        $fsize9 = 9;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->rpt_RS_HEADER_PDF($params, $data);

        if (!empty($data)) {
            foreach ($data as $row) {
                $manid = $row['supid'];

                // Get manager info
                $gender = $this->coreFunctions->getfieldvalue("employee", "gender", "empid=?", [$manid]);
                $stat = $this->coreFunctions->getfieldvalue("employee", "status", "empid=?", [$manid]);
                $fname = $this->coreFunctions->getfieldvalue("employee", "empfirst", "empid=?", [$manid]);
                $mname = $this->coreFunctions->getfieldvalue("employee", "empmiddle", "empid=?", [$manid]);
                $lname = $this->coreFunctions->getfieldvalue("employee", "emplast", "empid=?", [$manid]);
                $managername = trim("$fname $mname $lname");

                // Determine title
                switch (strtoupper($gender)) {
                    case 'FEMALE':
                        $title = in_array(strtoupper($stat), ['MARRIED']) ? 'Mrs.' : 'Ms.';
                        break;
                    default:
                        $title = 'Mr.';
                }

                // Employee info
                $empname = strtoupper($row['empname']);
                $formattedDate = strtoupper((new DateTime($row['tdate1']))->format('M-j-Y'));

                // Category / designation label
                $label = '';
                switch ($row['categoryname']) {
                    case 'ASSIGNED':
                        $label = 'Assigned as ';
                        break;
                    case 'REASSIGNED':
                        $label = 'Reassigned from : ';
                        break;
                    case 'TRANSFERRED':
                    case 'PROMOTED':
                        $label = 'Promoted from : ';
                        break;
                    case 'ALTERED':
                        $label = 'Altered from : ';
                        break;
                }

                $prevDesignation = $label . '<b>' . $row['oldjob'] . '</b>' . ' of ' . '<b>' . $row['oldbranch'] . '</b>' . ' to ';
                $newDesignation = '<b>' . $row['newjob'] . ' of ' . $row['newdept'] . ' Department, '
                    . $row['newbranch'] . '</b>. '
                    . $row['rem']
                    . ' and will be directly reporting to ' . $title . ' ' . $managername . '.';


                PDF::SetLeftMargin(50);
                $html = '<span>____________________ </span>';
                $html .= '<b>' . $empname . ' &nbsp; Effective: ' . $formattedDate . '</b>';
                $html .= ' &nbsp; ' . $prevDesignation . $newDesignation;

                PDF::SetFont($font, '', $fsize9);
                PDF::writeHTML($html . '<br><br>', true, false, true, true, 'J');

                PDF::SetLeftMargin(50);
            }
        }

        $this->rpt_RS_FOOTER_PDF($params, $data);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }



    public function rpt_RS_FOOTER_PDF($config, $data)
    {
        $center = $config['params']['center'];

        $fsize9 = 9;
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }


        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', $fsize9);
        PDF::SetX(50);
        PDF::MultiCell(510, 0, 'Please be guided accordingly, Thank You!', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($font, '', $fsize9);
        PDF::SetX(50);
        PDF::MultiCell(235, 18, 'PREPARED BY', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(235, 18, 'NOTED BY', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fsize9);
        PDF::SetX(50); //
        PDF::MultiCell(235, 10, $data[0]['createby'], '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 10, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(235, 15, (isset($data[0]['notedby1']) ? $data[0]['notedby1'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($font, '', $fsize9);
        PDF::SetX(50);
        PDF::MultiCell(235, 0, 'Human Resource Staff', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(235, 0, 'Human Resource Manager', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetX(270);
        PDF::MultiCell(400, 0, 'cc.CRM,MAM,KMAC,CJAM,HR,ALL DEPARTMENT,All Heads,HR File', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);


        PDF::SetY(785);
        PDF::MultiCell(600, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    }
}
