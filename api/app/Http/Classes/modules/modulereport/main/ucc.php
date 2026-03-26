<?php

namespace App\Http\Classes\modules\modulereport\main;

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

class ucc
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
        $companyid = $config['params']['companyid'];
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

        $query = "select ut.line as clientid,ut.line as trno, ut.line,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
        dept.clientname as deptname,ut.forapproval,date(ut.dateid) as dateid,jt.jobtitle,ut.rem
        from undertime as ut
        left join employee as emp on emp.empid = ut.empid
        left join jobthead as jt on jt.line = emp.jobid
        left join client as dept on dept.clientid = emp.deptid
        where ut.line= $line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn  

    public function reportplotting($params, $data)
    {
        return $this->default_restday_PDF($params, $data);
    }

    public function default_restday_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name, address, tel, code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        PDF::MultiCell(0, 0, "\n\n");


        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

        PDF::SetFont($fontbold, '', 20);
        PDF::SetTextColor(245, 16, 0);
        PDF::MultiCell(0, 30, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetTextColor(0, 0, 0);

        // change logo
        // PDF::Image($this->companysetup->getlogopath($params['params']) . 'warningsign.jpg', '80', '25', 100, 100);

        PDF::SetTextColor(2, 47, 115);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(160, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $headerdata[0]->address, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0);

        // PDF::MultiCell(0, 0, "\n\n\n");
        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(720, 0, 'UNDERTIME CANCELLATION / CHANGE SLIP', '', 'C', false, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(720, 0, '', '', 'C', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 0, "NAME: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 0, "" . $data[0]['clientname'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(260, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 0, "DATE FILED: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, '' . $data[0]['dateid'], 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 0, "DEPT./BRANCH: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(150, 0, "" . $data[0]['deptname'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(260, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 0, "POSITION: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, '' . $data[0]['jobtitle'], 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(260, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");
    }

    public function default_restday_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_restday_header($params, $data);

        $countarr = 0;
        $printline = 0;
        PDF::MultiCell(0, 0, "\n");

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $clientname = $data[$i]['clientname'];
                $dateid = $data[$i]['dateid'];
                $rem = $data[$i]['rem'];

                $arr_rem = $this->reporter->fixcolumn([$rem], '60', 0);

                if ($printline == 0) {
                    PDF::SetFont($font, 'B', 15);
                    PDF::MultiCell(180, 0, "", 'R', 'C', false, 0);

                    PDF::MultiCell(140, 0, "", 'T', 'C', false, 0);
                    PDF::MultiCell(100, 0, "UNDERTIME", 'BT', 'C', false, 0);
                    PDF::MultiCell(120, 0, "", 'T', 'C', false, 0);
                    PDF::MultiCell(180, 0, "", 'L', 'C', false, 1);

                    PDF::SetFont($font, 'B', 15);
                    PDF::MultiCell(180, 0, "", 'R', 'C', false, 0);
                    PDF::MultiCell(360, 0, "", '', 'C', false, 0);
                    PDF::MultiCell(180, 0, "", 'L', 'C', false, 1);

                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(180, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(10, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(100, 15, 'DATE TIME: ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(90, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(40, 15, 'TIME: ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(90, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(20, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(180, 15, '', 'L', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', false);
                }
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(180, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(10, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(50, 15, 'remarks:', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_rem]);
                for ($r = 0; $r < $maxrow; $r++) {
                    $printline++;


                    if ($r == 0) {
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(280, 15, '' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(20, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(180, 15, '', 'L', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', false);
                        if ($maxrow == 1) {
                            $this->remline($maxrow, $font, $fontsize);
                        }
                    } else {
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(180, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(10, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::SetFont($font, '', $fontsize);
                        PDF::MultiCell(330, 15, '' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(20, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                        PDF::MultiCell(180, 15, '', 'L', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', false);

                        if ($maxrow == $r + 1) {
                            $this->remline($maxrow, $font, $fontsize);
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
        PDF::MultiCell(28, 0, '', '', 'L', false, 0);
        // approvedby
        PDF::MultiCell(185, 0, '', 'B', 'L', false, 0); //----
        PDF::MultiCell(27, 0, '', '', 'L', false, 0);


        PDF::MultiCell(240, 0, '', '', 'C', false, 0);

        PDF::MultiCell(195, 0, '', 'B', 'C', false, 0); // add value
        PDF::MultiCell(45, 0, '', '', 'C', false, 1);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(240, 0, 'HEAD DEPT MANAGER', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '', '', 'C', false, 0);
        PDF::MultiCell(280, 0, 'HR / DEPARTMENT ', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(28, 0, '', '', 'L', false, 0);
        // approvedby
        PDF::MultiCell(185, 0, '', 'B', 'L', false, 0); //----
        PDF::MultiCell(27, 0, '', '', 'L', false, 0);


        PDF::MultiCell(240, 0, '', '', 'C', false, 0);

        PDF::MultiCell(195, 0, '', 'B', 'C', false, 0); // add value
        PDF::MultiCell(45, 0, '', '', 'C', false, 1);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(240, 0, 'CEO / CFO', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '', '', 'C', false, 0);
        PDF::MultiCell(280, 0, 'GENERAL MANAGER', '', 'C', false, 1);



        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');


        return PDF::Output($this->modulename . '.pdf', 'S');
        // }
    }

    public function borderline($line)
    {
        if ($line > 4) {
            $line = $line + 2;
        } else {
            $line = 3 - $line;
        }
        for ($i = 0; $i < $line; $i++) {
            if ($i == ($line) - 1) {
                PDF::MultiCell(180, 15, '', 'R', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(360, 15, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'L', 'L', false, 1, '',  '', true, 1, false, true, 15, 'M', false);
            } else {
                PDF::MultiCell(180, 15, '', 'R', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(360, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'L', 'L', false, 1, '',  '', true, 1, false, true, 15, 'M', false);
            }
        }
    }

    public function remline($line, $font, $fontsize)
    {
        if ($line < 3) {
            $line =  (3 - $line);
        } else {
            $line = 0;
        }
        for ($i = 0; $i < $line; $i++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(180, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
            PDF::MultiCell(10, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(330, 15, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
            PDF::MultiCell(20, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
            PDF::MultiCell(180, 15, '', 'L', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', false);
        }
    }
}
