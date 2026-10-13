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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class hb
{
    private $modulename = "Beginning and Closing Balance";
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
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'prepared', 'checked', 'approved', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '0' as reporttype,
            '' as prepared,
            '' as approved
            "
        );
    }
    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "
        select head.trno,head.trno, head.docno,head.empid,emp.clientname as empname,
        left(head.dateid,10) as dateid,d.line,pa.code as acno,pa.codename as acnoname,
        d.uom,d.db,d.cr,d.rem
        from hbdetail as d
        left join hbhead as head on head.trno=d.trno
        left join client as emp on head.empid = emp.clientid
        left join paccount as pa on d.pacnoid=pa.line
        where head.trno = $trno
        union all
        select head.trno,head.trno, head.docno,head.empid,emp.clientname as empname,
        left(head.dateid,10) as dateid,d.line,pa.code as acno,pa.codename as acnoname,
        d.uom,d.db,d.cr,d.rem
        from hhbdetail as d
        left join hhbhead as head on head.trno=d.trno
        left join client as emp on head.empid = emp.clientid
        left join paccount as pa on d.pacnoid=pa.line
        where head.trno = $trno
        order by line
        ";
        // var_dump($query);
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        return $this->default_HB_PDF($params, $data);
    }


    public function header_HB_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(55, 55);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(500, 0, ' ' . strtoupper($headerdata[0]->name), '', 'C', false);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(500, 0, ' ' . strtoupper($headerdata[0]->address), '', 'L', false);
        PDF::MultiCell(500, 0, "\n", '');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(500, 0, $this->modulename, '', 'C', false);
        PDF::MultiCell(500, 0, "\n\n", '');

        PDF::SetFont($font, '', 10);
        PDF::Cell(70, 15, ' EMPLOYEE: ', '', '', false, 0);
        PDF::Cell(300, 15, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', '', false, 0);
        PDF::Cell(50, 15, ' DATE: ', '', '', false, 0);
        PDF::Cell(280, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 1, false, 0);

        PDF::Cell(70, 15, 'DOCNO ', '', '', false, 0);
        PDF::Cell(280, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 1, false, 0);
        PDF::MultiCell(500, 0, "\n\n", '');

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(50, 20, "CODE", 'TB', 'L', false, 0);
        PDF::MultiCell(120, 20, "CODE NAME", 'TB', 'L', false, 0, '', '');
        PDF::MultiCell(40, 20, "UOM", 'TB', 'C', false, 0, '', '');
        PDF::MultiCell(70, 20, "DEBIT", 'TB', 'C', false, 0);
        PDF::MultiCell(70, 20, "CREDIT", 'TB', 'C', false, 0, '', '');
        PDF::MultiCell(120, 20, "NOTES", 'TB', 'C', false, 1);
    }

    public function default_HB_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = 9;
        $totaldb = 0;
        $totalcr = 0;
        $count = 0;
        $maxRow = 35;
        $rowPerPage = 0;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->header_HB_PDF($params, $data);
        $counted = count($data);

        for ($i = 0; $i < ($counted); $i++) {
            $maxrow = 2;
            $code = $data[$i]['acno'];
            $codename = $data[$i]['acnoname'];
            $uom = $data[$i]['uom'];
            $db = number_format($data[$i]['db'], 2);
            $cr = number_format($data[$i]['cr'], 2);
            $rem = $data[$i]['rem'];

            $arr_code = $this->reporter->fixcolumn([$code], '10', 0);
            $arr_codename = $this->reporter->fixcolumn([$codename], '20', 0);
            $arr_uom =  $this->reporter->fixcolumn([$uom], '10', 0);
            $arr_db =  $this->reporter->fixcolumn([$db], '18', 0);
            $arr_cr =  $this->reporter->fixcolumn([$cr], '18', 0);
            $arr_rem =  $this->reporter->fixcolumn([$rem], '25', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_rem, $arr_codename, $arr_code, $arr_uom, $arr_db, $arr_cr]);
            for ($r = 0; $r < $maxrow; $r++) {


                if ($rowPerPage == $maxRow) { //new page
                    $this->header_HB_PDF($params, $data);
                    $rowPerPage = 0; // reset
                }
                $rowPerPage++;
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(50, 15, (isset($arr_code[$r]) ? $arr_code[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(120, 15, (isset($arr_codename[$r]) ? $arr_codename[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(40, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, (isset($arr_db[$r]) ? $arr_db[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, 15, (isset($arr_cr[$r]) ? $arr_cr[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(120, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                if ($r == 0) {
                    $count++;
                    $totaldb += $data[$i]['db'];
                    $totalcr += $data[$i]['cr'];
                }
            }
        }
        //total
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 15, 'Number of Records:  ' . $count, 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(320, 15, 'DEBIT: ' . number_format($totaldb, 2) . '    ' . 'CREDIT: ' . number_format($totalcr, 2), 'T', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
