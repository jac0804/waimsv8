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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class occ
{

    private $modulename = "";
    private $reportheader;
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
        $this->reportheader = new reportheader;
    }

    public function createreportfilter($config)
    {
        $companyid = $config['params']['companyid'];


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
        $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as noted";
        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($config)
    {

        $line = $config['params']['dataid'];
        // var_dump($date_filter);

        // $filter = explode('~', $date_filter);
        // $empid = $filter[0];
        // $line = $filter[1];

        $query = "select concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
                    date(ob.dateid) as dateid,dept.clientname,dept.clientname as deptname,jt.jobtitle,ob.reason
                    from obapplication as ob
                    left join employee as emp on emp.empid = ob.empid
                    left join client as dept on dept.clientid = emp.deptid
                    left join jobthead as jt on jt.line = emp.jobid where ob.line = $line ";
        // where ob.empid = $empid  and ob.line = $line ";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn  

    public function reportplotting($params, $data)
    {
        return $this->default_PDF($params, $data);
    }

    public function default_header($params, $data)
    {
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
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(60, 60);
        PDF::Image(public_path('images/cdohris/cdohris_logo.png'), '60', '10', 680, 60);


        PDF::MultiCell(0, 0, "\n\n\n\n\n");
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(680, 0, 'OB CANCELLATION', '', 'C', false, 1);


        PDF::MultiCell(0, 0, "\n\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 15, "NAME: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 15, "" . $data[0]['clientname'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(220, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 15, "DATE FILED: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 15, '' . $data[0]['dateid'], 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(680, 0, '', '', 'C', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 15, "DEPT./BRANCH: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(150, 15, "" . $data[0]['deptname'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(220, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 15, "POSITION: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 15, '' . $data[0]['jobtitle'], 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(260, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");
    }

    public function default_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_header($params, $data);
        $printline = 0;
        PDF::MultiCell(0, 0, "\n");

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $reason = $data[$i]['reason'];

                $arr_reason = $this->reporter->fixcolumn([$reason], '60', 0);

                if ($printline == 0) {
                    PDF::SetFont($font, 'B', 15);

                    PDF::MultiCell(300, 0, "", 'TL', 'C', false, 0);
                    PDF::MultiCell(120, 0, "CANCELLATION", 'BT', 'C', false, 0);
                    PDF::MultiCell(120, 0, "", 'T', 'C', false, 0);
                    PDF::MultiCell(140, 0, "", 'TR', 'C', false, 1);

                    PDF::SetFont($font, 'B', $fontsize);
                    PDF::MultiCell(680, 0, "", 'LR', 'C', false, 1);

                    PDF::SetFont($font, 'B', $fontsize);
                    PDF::MultiCell(25, 0, "", 'L', 'C', false, 0);
                    PDF::MultiCell(655, 0, "DATE OF APPLIED: ", 'R', 'L', false, 1);

                    PDF::SetFont($font, 'B', 5);
                    PDF::MultiCell(680, 0, "", 'LR', 'C', false, 1);


                    PDF::SetFont($font, 'B', $fontsize);
                    PDF::MultiCell(25, 0, "", 'L', 'C', false, 0);

                    PDF::MultiCell(200, 0, $data[$i]['dateid'], 'B', 'L', false, 0);
                    PDF::MultiCell(455, 0, "", 'R', 'C', false, 1);


                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(25, 15, "", 'L', 'C', false, 0);
                    PDF::MultiCell(655, 15, "PRIOR VALIDATION: (as per HR File)", 'R', 'L', false, 1);


                    PDF::SetFont($font, 'B', $fontsize);
                    PDF::MultiCell(680, 0, "", 'LR', 'C', false, 1);
                }
                PDF::SetFont($font, '', $fontsize);

                PDF::MultiCell(25, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(50, 15, 'Reason:', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_reason]);
                for ($r = 0; $r < $maxrow; $r++) {
                    $printline++;


                    if ($r == 0) {
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(580, 15, '' . (isset($arr_reason[$r]) ? $arr_reason[$r] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(25, 15, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', false);
                        if ($maxrow == 1) {
                            $this->reasonline($maxrow, $font, $fontsize);
                        }
                    } else {
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(25, 15, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(630, 15, '' . (isset($arr_reason[$r]) ? $arr_reason[$r] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(25, 15, '', 'R', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', false);

                        if ($maxrow == $r + 1) {
                            $this->reasonline($maxrow, $font, $fontsize);
                        }
                    }
                }
                if ($printline >= count($data)) {
                    $this->borderline($printline);
                }
            }
        }

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(680, 0, '', '', 'L', false, 1);
        // approvedby
        PDF::MultiCell(240, 0, '', 'B', 'L', false, 0); //----
        PDF::MultiCell(200, 0, '', '', '', false, 0);
        PDF::MultiCell(240, 0, '', 'B', 'L', false, 1);



        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(240, 0, 'HEAD DEPT MANAGER', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '', '', 'C', false, 0);
        PDF::MultiCell(240, 0, 'HR / DEPARTMENT ', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        // approvedby
        PDF::MultiCell(240, 0, '', 'B', 'L', false, 0); //----
        PDF::MultiCell(200, 0, '', '', '', false, 0);
        PDF::MultiCell(240, 0, '', 'B', 'L', false, 1);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(240, 0, 'CEO / CFO', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '', '', 'C', false, 0);
        PDF::MultiCell(240, 0, 'GENERAL MANAGER', '', 'C', false, 1);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function borderline($line)
    {
        if ($line > 4) {
            $line = $line + 2;
        } else {
            $line = 4 - $line;
        }
        for ($i = 0; $i < $line; $i++) {
            if ($i == ($line) - 1) {
                PDF::MultiCell(25, 25, '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', false);
                PDF::MultiCell(630, 25, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 25, 'M', false);
                PDF::MultiCell(25, 25, '', 'RB', 'L', false, 1, '',  '', true, 1, false, true, 25, 'M', false);
            } else {
                PDF::MultiCell(25, 15, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(630, 15, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(25, 15, '', 'R', 'L', false, 1, '',  '', true, 1, false, true, 15, 'M', false);
            }
        }
    }

    public function reasonline($line, $font, $fontsize)
    {
        if ($line < 3) {
            $line =  (3 - $line);
        } else {
            $line = 0;
        }
        for ($i = 0; $i < $line; $i++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(25, 15, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
            PDF::MultiCell(630, 15, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
            PDF::MultiCell(25, 15, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', false);
        }
    }
}
