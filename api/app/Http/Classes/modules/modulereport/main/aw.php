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


class aw
{
    private $modulename = "WORK ORDER";
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

    public function createreportfilter($config)
    {

        $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Print out 1', 'value' => '0', 'color' => 'red'],
            ['label' => 'Print out 2', 'value' => '1', 'color' => 'red']
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
        select head.docno, head.clientname, head.address, head.dateid, c.tel,
        head.licenseno, head.tax, head.cryear,head.make, head.modelname, head.submodel, head.mileage
        from awhead as head
        left join client as c on c.clientname = head.clientname
        where trno = $trno ";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }


    public function report_parts_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "
        select stock.isqty, stock.uom, item.barcode, item.itemname, stock.ext 
        from awhead as head
        left join ptstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        where head.trno = $trno
        ";
        $parts = json_decode(json_encode($this->coreFunctions->opentable($query)));
        return $parts;
    } 

    public function report_repair_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "
        select head2.line, jt.jobtitle, jobs.description, task.cost
        from awhead as head
        left join ptjobs as head2 on head2.trno = head.trno
        left join jobthead as jt on jt.line = head2.jobid
        left join pttask as task on task.trno = head.trno and task.jobline = head2.line
        left join jobtask as jobs on jobs.line = task.laborline
        where head.trno = $trno
        ";
        $repair = json_decode(json_encode($this->coreFunctions->opentable($query)));
        return $repair;
    }


    public function reportplotting($params, $data)
    {
        $reporttype = $params['params']['dataparams']['reporttype'];
        $parts = $this->report_parts_query($params);
        $repair = $this->report_repair_query($params);
        switch ($reporttype) {
            case 0:
                return $this->print1_layout_PDF($params, $data, $parts, $repair);
                break;
            case 1:
                return $this->print2_layout_PDF($params, $data, $parts, $repair);
                break;
        }
    }

    private function formatQty($number) // removes trailing zero decimals  1.50 
    {
        return rtrim(rtrim(number_format($number, 2), '0'), '.');
    }

    public function print1_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

        $font = "";
        $fontbold = "";
        $fontsize = "10";
        $fontsize2 = "9";
        $maxRowsPerPage = 13;
        $rowHeight = 0;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(30, 30);

        $dateformat = ($data[0]['dateid']) ? date('M d, Y', strtotime($data[0]['dateid'])) : '';

        PDF::SetY(10);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetY(45);
        PDF::SetFont($fontbold, 'B', 16);
        PDF::MultiCell(260, 20, strtoupper($headerdata[0]->name), '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(460, 20, "", '', 'R', false);

        PDF::SetY(30);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(350, 15, "", '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'LT', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, 'REPAIR ORDER #' , 'T', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize2);
        PDF::MultiCell(100, 15, ': ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'TR', 'L', false);

        PDF::MultiCell(350, 15, "", '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '', ''); // 175
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, "DATE", '', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize2);
        PDF::MultiCell(100, 15, ': ' . $dateformat, 'R', 'L', false);
        $pagenumber = PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages();
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(350, 15, strtoupper($headerdata[0]->address), '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, "PAGE", '', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize2);
        PDF::MultiCell(100, 15, $pagenumber, 'R', 'L', false); //add page number

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(350, 15, strtoupper($headerdata[0]->tel), '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, "", 'LB', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, "FORM#", 'B', 'L', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(100, 15, " :", 'BR', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // left, top, right, bottom

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 10,'', '', '', false, 0, '', '');
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(270, 10, '' , '', '', false);

        PDF::SetCellPaddings(0, 4, 0, 0); // 550
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'LT', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, 'CUSTOMER', 'T', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(210, 15, ': ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'T', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 15, '', 'T', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'LT', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "LICENSE", 'T', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ':  ' . (isset($data[0]['licenseno']) ? $data[0]['licenseno'] : ''), 'RT', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, 'ADDRESS', '', 'LB', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(210, 15, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'LB', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 15, '', '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "YEAR", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['cryear']) ? $data[0]['cryear'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(315, 15, '', '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "MAKE", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['make']) ? $data[0]['make'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(315, 15, '', '', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "MODEL", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['modelname']) ? $data[0]['modelname'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, 'PHONE', '', 'LB', false, 0);
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(210, 15, ': ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), '', 'LB', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 15, '', '', '', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'L', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "SUB MODEL", '', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['submodel']) ? $data[0]['submodel'] : ''), 'R', 'LB', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 15, '', 'LB', 'L', false, 0, '', '');
        PDF::MultiCell(315, 15, '', 'B', 'L', false, 0, '', '');
        PDF::MultiCell(5, 15, '', 'LB', 'L', false, 0, '', '');
        PDF::MultiCell(70, 15, "MILEAGE", 'B', 'LB', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(155, 15, ': ' . (isset($data[0]['mileage']) ? $data[0]['mileage'] : ''), 'RB', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // left, top, right, bottom

        PDF::MultiCell(0, 0, "\n");

        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, '', 'LT', 'C', false, 0, '', '');
        PDF::SetFillColor(200, 200, 200); // Light gray 
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(265, 20, 'PARTS', 'T', 'C', true, 0, '', '');
        PDF::SetFillColor(255, 255, 255); // White
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, '', 'RT', 'C', false, 0, '', '');
        PDF::MultiCell(5, 20, '', 'T', 'C', false, 0, '', '');
        PDF::SetFillColor(200, 200, 200); // Light gray 
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(265, 20, 'LABOR', 'T', 'C', true, 0, '', '');
        PDF::SetFillColor(255, 255, 255); // White
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, '', 'RT', 'C', false);
        PDF::SetCellPaddings(0, 0, 0, 0); // reset padding

        PDF::SetFont($fontbold, '', $fontsize);//270
        PDF::MultiCell(40, 20, "QTY", 'L', 'C', false, 0);
        PDF::MultiCell(80, 20, "PART#", '', 'C', false, 0, '', '');
        PDF::MultiCell(100, 20, "DESCRIPTION", '', 'C', false, 0, '', '');
        PDF::MultiCell(50, 20, "PRICE", '', 'C', false, 0);
        PDF::MultiCell(5, 20, "", 'R', '', false, 0, '', '');
        PDF::MultiCell(5, 20, "", '', '', false, 0, '', '');
        PDF::MultiCell(40, 20, "OP", '', 'C', false, 0, '', '');
        PDF::MultiCell(80, 20, "TECH", '', 'C', false, 0, '', '');
        PDF::MultiCell(100, 20, "DESCRIPTION", '', 'C', false, 0, '', '');
        PDF::MultiCell(50, 20, "PRICE", 'R', 'C', false);
        // PDF::SetY(362);
        $rowHeight = ($maxRowsPerPage) * 29;
        if ($rowHeight > 0) {
            PDF::MultiCell(275, $rowHeight, '', 'BL', '', false, 0);
            PDF::MultiCell(275, $rowHeight, '', 'BLR', '', false);
        }

    }
    public function print1_layout_PDF($params, $data, $parts, $repair)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fonttable = 9;
        $count1 = 0;
        $rowlimit1 = 16;  
        $count2 = 0;
        $rowlimit2 = 16;  

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->print1_header_PDF($params, $data);
       
        $counting = 0;
        $display_jobtitle = '';
        $partCount = count($parts);
        $repairCount = count($repair);
        $total1 = 0;
        $total2 = 0;

        $startPage = PDF::getPage();
        // Parts - DATA
        PDF::SetY(250);
        for ($i = 0; $i < ($partCount); $i++) {
           
            $maxrow = 1;
            $qty = !empty($parts[$i]->isqty) ? $parts[$i]->isqty : '';
            $uom = !empty($parts[$i]->uom) ? $parts[$i]->uom : '';
            $barcode = !empty($parts[$i]->barcode) ? $parts[$i]->barcode : '';
            $itemname = !empty($parts[$i]->itemname) ? $parts[$i]->itemname : '';
            $ext = !empty($parts[$i]->ext) ? $parts[$i]->ext : '';

            $arr_qty = $this->reporter->fixcolumn([$this->formatQty($qty)], '10', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '20', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
            $arr_ext = $this->reporter->fixcolumn([number_format($ext, 2)], '40', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_barcode, $arr_qty, $arr_uom,  $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {
                $count1++;
                $isLastRow = ($i == $partCount - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetFont($font, '', $fonttable);
                PDF::MultiCell(20, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(20, 20, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(80, 20, isset($arr_barcode[$r]) ? $arr_barcode[$r] : '', '', 'L', false, 0);
                PDF::MultiCell(100, 20, isset($arr_itemname[$r]) ? $arr_itemname[$r] : '', '', 'L', false, 0);
                PDF::MultiCell(50, 20, isset($arr_ext[$r]) ? $arr_ext[$r] : '', $border, 'R', false);
            }
            $total1 += $ext;

            if ($count1 > $rowlimit1) {  //20
                PDF::SetXY(255, 612.7);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true); // white rectangle
                PDF::SetXY(258, 615);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);

                $this->print1_PDF_footer($params, $data, $total1, $total2, false);
                $this->print1_header_PDF($params, $data);
                PDF::SetY(250);
                $count1 = 0; // reset
                $count2 = 0;
            }
        }
        PDF::SetLineWidth(2);
        PDF::MultiCell(220, 0, '', '', 'R', false, 0);//$count1
        PDF::MultiCell(50, 0, number_format($total1, 2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        
        PDF::setPage($startPage);
        // Repair - DATA
        PDF::SetXY(390, 250); //force to start in pg1
        $preJob = null;
        for ($i = 0; $i < ($repairCount); $i++) {

            $line = !empty($repair[$i]->line) ? $repair[$i]->line : '';
            $jobtitle = !empty($repair[$i]->jobtitle) ? $repair[$i]->jobtitle : '';
            $description = !empty($repair[$i]->description) ? $repair[$i]->description : '';
            $cost = !empty($repair[$i]->cost) ? $repair[$i]->cost : 0;

            $same = ($jobtitle === $preJob); //same as previous
            if ($same) {
                $display_jobtitle = '';
            } else {
                $display_jobtitle = $jobtitle;
                $counting++;
            }

            $preJob = $jobtitle;

            $arr_jobtitle = $this->reporter->fixcolumn([$display_jobtitle], '40', 0) ?: [''];
            $arr_description = $this->reporter->fixcolumn([$description], '40', 0);
            $arr_cost = $this->reporter->fixcolumn([number_format($cost, 2)], '10', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_jobtitle, $arr_description, $arr_cost]);
            for ($r = 0; $r < $maxrow; $r++) {
                $count2++;
                $isLastRow = ($i == $repairCount - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetX(300);
                PDF::SetFont($font, '', $fonttable);

                if (!$same) { //if different job; print below
                    PDF::MultiCell(5, 20, '', '', 'L', false, 0);
                    PDF::MultiCell(40, 20, $same ? '' : $line, '', 'C', false, 0);
                    if (empty($description) || $description == null){
                        PDF::MultiCell(180, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 0);
                    }else{
                        $count2++;
                        PDF::MultiCell(180, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 1);
                    }
                }
                // $fcost = ($arr_cost[$r] == 0.00) ? '' : (isset($arr_cost[$r]) ? $arr_cost[$r] : '');
                if ($r == 0 && !empty($description)) {
                    PDF::SetX(360);
                    PDF::SetFont($font, '', $fonttable);
                    PDF::MultiCell(165, 20, '--- ' . $description, '', 'L', false, 0);
                    PDF::MultiCell(50, 20, isset($arr_cost[$r]) ? $arr_cost[$r] : '', $border, 'R', false);
                } else if ($r == 0 && empty($description)) {
                    PDF::MultiCell(50, 20, isset($arr_cost[$r]) ? $arr_cost[$r] : '', '', 'R', false);
                }
            }
            PDF::SetX(303);
            $total2 += $cost;

            if($count2 > $rowlimit2){
                PDF::SetXY(255, 612.7);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true); // white rectangle
                PDF::SetXY(258, 615);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);

                $this->print1_PDF_footer($params, $data, $total1, $total2, false);
                $this->print1_header_PDF($params, $data);
                PDF::SetXY(390, 250);
                $count1 = 0;
                $count2 = 0; // reset
            }

        }
        PDF::SetX(303);
        PDF::SetLineWidth(2);
        PDF::MultiCell(220, 0, '', '', 'R', false, 0);//$count2
        PDF::MultiCell(50, 0, number_format($total2, 2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        PDF::setPage(PDF::getNumPages());
        $this->print1_PDF_footer($params, $data, $total1, $total2);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function print1_PDF_footer($params, $data, $total1, $total2, $totals = true)
    {
        $font = "";
        $fontbold = "";
        $fonttable = 9;
        $fontsize2 = 6;
        $tax = 0;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetY(630); //643
        PDF::SetFont($fontbold, '', 8);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 15, '', 'LTB', 'LB', false, 0);
        PDF::MultiCell(540, 15, 'RECOMMENDED REPAIR', 'RBT', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        PDF::SetY(648);//662 // Left side of the footer
        PDF::SetFont($fontbold, '', $fontsize2);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 20, '', 'LT', 'LB', false, 0);
        PDF::MultiCell(410, 20, 'TERMS AND PROVISIONS : ', 'RT', 'LB', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::SetY(660);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "1. Customer authorizes  DAS or its employees to operate vehicle for purposes of testing , inspection and/or delivery at customer's risk.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "2. Customer acknowledges an express mechanic's lien to secure the amount of repairs indicated thereto.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "3. DAS will not be responsble or liable for loss or damage to vehicle or articles left in case of fire, theft, accident, flood, typhoon, earthquake", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "and/or other causes beyond the comapany's control.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "4. Customer agrees to pay interest at the rate of 2% per month on all accunts not paid when due.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "5. Vehicle not claimed and withdrawn from the company's premises within five(5) days from date of completion will be charged storage of", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, " P 60.00 per day untill withdrawn.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "6. Orall agreements, representations or promises not incorporated herein are unauthorized and therefore not binding.", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, "7. In case of litigation for non-payment of this repair order invoice, customer agrees to submit himself to the jurisdiction of the courts of", 'R', 'L', false);
        PDF::SetFont($font, '', 6);
        PDF::MultiCell(10, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(410, 5, " Manila, Quezon City or Makati City at the option of the comapany and to pay 25% for attorney's fees, minimum of P 500.00 plus court costs.", 'R', 'L', false);

        PDF::MultiCell(420, 5, '', 'LR', 'L', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(20, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(100, 5, "CUSTOMER CONFORME : ", '', 'L', false, 0);
        PDF::MultiCell(190, 5, '', 'B', 'L', false, 0);
        PDF::MultiCell(110, 5, "", 'R', 'L', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(20, 5, '', 'L', 'L', false, 0);
        PDF::MultiCell(100, 5, '', '', 'L', false, 0);
        PDF::MultiCell(190, 5, "PrintName and Signature", '', 'C', false, 0);
        PDF::MultiCell(110, 5, "", 'R', 'L', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(50, 5, '', 'LB', 'L', false, 0);
        PDF::MultiCell(130, 5, '', 'B', 'L', false, 0);
        PDF::MultiCell(160, 5, "", 'B', 'C', false, 0);
        PDF::MultiCell(80, 5, "", 'RB', 'L', false);


        PDF::SetCellPaddings(0, 0, 0, 0); // End of left side of the footer
        PDF::SetXY(450, 648); // RightSide of the footer
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', 'T', 'L', false, 0);
        PDF::MultiCell(50, 25, 'LABOR ', 'T', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', 'T', 'L', false, 0);
        PDF::SetFont($font, '', $fonttable);
        PDF::MultiCell(50, 25, $totals ? number_format($total2, 2) : '', 'T', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'TR', 'L', false);

        PDF::SetXY(450, 663);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'PARTS ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fonttable);
        PDF::MultiCell(50, 25, $totals ? number_format($total1, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'R', 'L', false);

        PDF::SetXY(450, 676);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, '', '', 'L', false, 0);
        PDF::MultiCell(220, 25, '', 'R', 'R', false);

        PDF::SetXY(450, 686);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'SUPPLIES ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::MultiCell(60, 25, '', 'R', 'L', false);

        $tax = $total1 * ((isset($data[0]['tax']) ? $data[0]['tax'] : '') / 100);
        PDF::SetXY(450, 699);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'TAX ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fonttable);
        PDF::MultiCell(50, 25, $totals ? number_format($tax, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'R', 'L', false);

        PDF::SetXY(450, 711);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, '', '', 'L', false, 0);
        PDF::MultiCell(220, 25, '', 'R', 'R', false);

        $ftotal = $total1 + $total2 + $tax;
        PDF::SetXY(450, 723);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', '', 'L', false, 0);
        PDF::MultiCell(50, 25, 'TOTAL ', '', 'L', false, 0);
        PDF::MultiCell(10, 25, ' : ', '', 'L', false, 0);
        PDF::MultiCell(50, 25, $totals ? number_format($ftotal, 2) : '', '', 'R', false, 0);
        PDF::MultiCell(10, 25, '  ', 'R', 'L', false);

        PDF::SetXY(450, 744);
        PDF::SetFont($fontbold, '', $fonttable);
        PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
        PDF::MultiCell(10, 25, '', 'B', 'L', false, 0);
        PDF::MultiCell(50, 25, '', 'B', 'L', false, 0);
        PDF::MultiCell(70, 25, '', 'RB', 'R', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
    }

    public function print2_PDF_header($params, $data) // 746
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

        $font = "";
        $fontbold = "";
        $fontcol = 7;
        $fontvar = 10;
        $fontvar2 = 8;
        $fontsize = 9;
        $fonttable = 9;
        $fonttitle = 12;
        $maxRowsPerPage = 30;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        
        $dateformat = ($data[0]['dateid'])? date('M d, Y', strtotime($data[0]['dateid'])) : '';

        PDF::SetTitle($this->modulename);
        PDF::SetY(10);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('l', 'LETTER');
        PDF::SetMargins(23,23);
        PDF::SetY(10);
        // PDF::MultiCell(0, 0, "\n", '', '');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', $fonttitle);
        PDF::MultiCell(373, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(373, 0, 'REPAIR ORDER # : ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
        PDF::MultiCell(300, 20, 'TeleFax: ' . strtoupper($headerdata[0]->tel), '', 'L', false);
        //1
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0); 
        PDF::MultiCell(50, 20, 'CUSTOMER ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(350, 20,': ' .(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(50, 20, 'RECEIVED ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(296, 20, ': ' . $dateformat, 'B', 'L', false);
        //2
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(50, 20, 'ADDRESS ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(350, 20, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(50, 20, 'PHONE ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar);
        PDF::SetCellPaddings(0, 5, 0, 0);
        PDF::MultiCell(296, 20, ': ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false);
        //3
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'LICENSE  ' , 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(65, 20, ':  ' . (isset($data[0]['licenseno']) ? $data[0]['licenseno'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'YEAR ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(50, 20, ': ' . (isset($data[0]['cryear']) ? $data[0]['cryear'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(30, 20, 'MAKE ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['make']) ? $data[0]['make'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'MODEL ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['modelname']) ? $data[0]['modelname'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'SUBMODEL ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['submodel']) ? $data[0]['submodel'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontcol);
        PDF::SetCellPaddings(0, 8, 0, 0);
        PDF::MultiCell(40, 20, 'MILEAGE ', 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontvar2);
        PDF::SetCellPaddings(0, 7, 0, 0);
        PDF::MultiCell(100, 20, ': ' . (isset($data[0]['mileage']) ? $data[0]['mileage'] : ''), 'B', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        PDF::SetY(132);
        PDF::MultiCell(746, 0, '', 'B', '', false);
        $rowHeight = ($maxRowsPerPage) * 11;
        if ($rowHeight > 0) {
            PDF::MultiCell(373, $rowHeight, '', 'L', '', false, 0);
            PDF::MultiCell(373, $rowHeight, '', 'LR', '', false);
        }
        PDF::MultiCell(746, 0, '', 'T', '', false);

        PDF::SetY(148);
        PDF::SetFont($fontbold, '', $fonttable);

        PDF::SetFillColor(192, 192, 192);
        PDF::MultiCell(5, 0, '', '', 'L', false, 0);
        PDF::MultiCell(48, 0, 'QTY', 'B', 'C', true, 0);
        PDF::MultiCell(220, 0, 'PART NUMBER / DESCRIPTION', 'B', 'L', true, 0);
        PDF::MultiCell(95, 0, 'PRICE       ', 'B', 'R', true, 0);
        PDF::MultiCell(10, 0, '', '', 'L', false, 0);
        PDF::MultiCell(48, 0, 'OPER', 'B', 'C', true, 0);
        PDF::MultiCell(220, 0, 'REPAIR INSTRUCTION', 'B', 'L', true, 0);
        PDF::MultiCell(95, 0, 'PRICE       ', 'B', 'R', true, 0);
        PDF::MultiCell(5, 0, '', '', 'L', false);
        PDF::SetFillColor(255, 255, 255);

    }
    public function print2_layout_PDF($params, $data, $parts, $repair) // 746
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fonttable = 9;
        $fontrows = 7;
        $counting = 0;
        $count1 = 0;
        $count2 = 0;
        $tax = 0;
        $display_jobtitle = '';
        $partCount = count($parts);
        $repairCount = count($repair);
        $total1 = 0;
        $total2 = 0;
        $ftotal = 0;
        $fill = 1;

        $rowlimit1 = 10;
        $rowlimit2 = 10;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->print2_PDF_header($params, $data);

        $startPage = PDF::getPage();
        // Parts - DATA
        PDF::SetY(165);
        for ($i = 0; $i < ($partCount); $i++) {
            $maxrow = 1;
            $qty = !empty($parts[$i]->isqty) ? $parts[$i]->isqty : '';
            $uom = !empty($parts[$i]->uom) ? $parts[$i]->uom : '';
            $itemname = !empty($parts[$i]->itemname) ? $parts[$i]->itemname : '';
            $ext = !empty($parts[$i]->ext) ? $parts[$i]->ext : '';

            $arr_qty = $this->reporter->fixcolumn([number_format($qty,2)], '5', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '20', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
            $arr_ext = $this->reporter->fixcolumn([number_format($ext, 2)], '40', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_itemname, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {
                $count1++;
                $isLastRow = ($i == $partCount - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetFont($font, '', $fonttable);
                PDF::MultiCell(5, 0, '', '', 'L', false, 0);
                PDF::MultiCell(24, 20, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(24, 20, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'C', false, 0);
                PDF::MultiCell(220, 20, isset($arr_itemname[$r]) ? $arr_itemname[$r] : '', '', 'L', false, 0);
                PDF::MultiCell(95, 20, isset($arr_ext[$r]) ? $arr_ext[$r] : '', $border, 'R', false);
            }
            $total1 += $ext;
            if ($count1 > $rowlimit1) {
                PDF::SetXY(340, 457.5);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true); // white rectangle
                PDF::SetXY(350, 460);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);
                
                $this->print2_PDF_footer($params, $data, $total1, $total2, false);
                $this->print2_PDF_header($params, $data);
                PDF::SetY(165);
                $count1 = 0; // reset
                $count2 = 0;
            }
        }
        PDF::SetLineWidth(2);
        PDF::MultiCell(271, 0, '', '', 'R', false,0);//$count1
        PDF::MultiCell(95, 0, number_format($total1,2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        PDF::setPage($startPage);
        // Repair - DATA
        PDF::SetXY(390, 165);
        $preJob = null;
        for ($i = 0; $i < ($repairCount); $i++) {
            
            $line = !empty($repair[$i]->line) ? $repair[$i]->line : '';
            $jobtitle = !empty($repair[$i]->jobtitle) ? $repair[$i]->jobtitle : '';
            $description = !empty($repair[$i]->description) ? $repair[$i]->description : '';
            $cost = !empty($repair[$i]->cost) ? $repair[$i]->cost : 0;

            $same = ($jobtitle === $preJob); //same as previous
            if ($same){
                $display_jobtitle = '';
            }else{
                $display_jobtitle = $jobtitle;
                $counting++;
            }

            $preJob = $jobtitle;

            $arr_jobtitle = $this->reporter->fixcolumn([$display_jobtitle], '40', 0) ?: [''];
            $arr_description = $this->reporter->fixcolumn([$description], '40', 0);
            $arr_cost = $this->reporter->fixcolumn([number_format($cost,2)], '10', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_jobtitle, $arr_description, $arr_cost]);
            for ($r = 0; $r < $maxrow; $r++) {
                $count2++;
                $isLastRow = ($i == $repairCount - 1) && ($r == $maxrow - 1);
                $border = $isLastRow ? 'B' : '';

                PDF::SetX(390);
                PDF::SetFont($font, '', $fonttable);

                if(!$same){ //if different job; print the below
                    PDF::MultiCell(10, 20, '', '', 'L', false, 0);
                    PDF::MultiCell(48, 20, $same ? '' : $line, '', 'C', false, 0);
                    if (empty($description) || $description == null) {
                        PDF::MultiCell(220, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 0);
                    } else {
                        $count2++;
                        PDF::MultiCell(220, 20, isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : '', '', 'L', false, 1);
                    }
                }
                // $fcost = ($arr_cost[$r] == 0.00) ? '' : (isset($arr_cost[$r]) ? $arr_cost[$r] : '');
                if ($r == 0 && !empty($description)) {
                    PDF::SetX(460);
                    PDF::SetFont($font, '', $fonttable);
                    PDF::MultiCell(210, 20,'--- ' .$description, '', 'L', false, 0);
                    PDF::MultiCell(95, 20,  isset($arr_cost[$r]) ? $arr_cost[$r] : '', $border, 'R', false);    
                } else if ($r == 0 && empty($description)){
                    PDF::MultiCell(95, 20, isset($arr_cost[$r]) ? $arr_cost[$r] : '', $border, 'R', false);
                }
            }
            PDF::SetX(397);
            $total2 += $cost;

            if ($count2 > $rowlimit2) {
                PDF::SetXY(340, 457.5);
                PDF::SetFillColor(255, 255, 255);
                PDF::MultiCell(115, 14, '', '', 'C', true); // white rectangle
                PDF::SetXY(350, 460);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(95, 12, '** See next page **', '', 'C', false);

                $this->print2_PDF_footer($params, $data, $total1, $total2, false);
                $this->print2_PDF_header($params, $data);
                PDF::SetXY(390, 165);
                $count1 = 0;
                $count2 = 0; // reset
            }
        }
        PDF::SetLineWidth(2);
        PDF::MultiCell(271, 0, '', '', 'R', false, 0); //$count2
        PDF::MultiCell(95, 0, number_format($total2, 2), 'B', 'R', false);
        PDF::SetLineWidth(0.5);

        PDF::setPage(PDF::getNumPages());
        $this->print2_PDF_footer($params,  $data,$total1, $total2);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function print2_PDF_footer($params, $data, $total1, $total2, $totals = true)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fonttable = 9;
        $fontrows = 7;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetY(475);
        PDF::MultiCell(746, 15, '  RECOMMENDED REPAIRS: ' , 'TLBR', '', false);

        PDF::SetY(493);
        PDF::SetFont($fontbold, '', 6);
        PDF::MultiCell(560, 0, '', 'TLR', '', false);
        PDF::MultiCell(560, 0, ' TERMS AND PROVISIONS', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(560, 0, ' 1. Customer authorizes DAS or its employees to operate vehicle for purposes of testing, inspection and/or delivery at customer’s risk', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 2. Customer acknowledges an express mechanic’s lien to secure the amount of repairs indicated thereto.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 3. DAS will not be responsible or liable for loss or damage to vehicle or articles left in case of fire, theft, accident, flood, typhoon, earthquake and/or other causes beyon the company’s control.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 4. Customer agrees to pay interest at the of 2% per month on all accounts not paid when due.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 5. Vehicle not claimed and withdrawn from the company’s premises within five (5) days from date of completion will be charged storage of P 70.00 per day until withdrawn.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, ' 6. Oral agreements, representations or promises not incorporated herein are unauthorized and therefore not binding.', 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //
        PDF::MultiCell(560, 0, " 7. In case of litigation for non-payment of this repair order invoice, customer agrees to submit himself to the jurisdiction of the courts of Manila, Quezon City or Makati City at the option of the company \n     and to pay 25% for attorney’s fees, minimum of P 500.00 plus cour", 'LR', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(746, 0, '', 'L', '', false);
        PDF::SetFont($fontbold, '', 7);
        PDF::MultiCell(30, 0, '', 'L', '', false, 0);
        PDF::MultiCell(100, 0, 'CUSTOMER CONFORME :', '', '', false, 0);
        PDF::MultiCell(200, 0, '', 'B', '', false, 0);
        PDF::MultiCell(230, 0, '', 'R', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false); //

        PDF::MultiCell(470, 0, 'PrintName and Signature', 'L', 'C', false, 0);
        PDF::MultiCell(90, 0, '', 'R', 'C', false);

        PDF::MultiCell(560, 0, '', 'LRB', '', false, 0);
        PDF::MultiCell(186, 0, '', '', '', false);

        PDF::SetXY(600, 500);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'LABOR', '', '', false, 0);//186
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 12,$totals ? number_format($total2, 2) : '', '', 'R', false);
        PDF::SetX(600);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'PARTS', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 12,$totals ? number_format($total1, 2) : '', '', 'R', false);

        PDF::SetX(600);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'SUBLET', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false);
        PDF::SetXY(600, 540);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'SUPPLIES', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false);
        PDF::SetX(600);

        $tax = $total1 * ((isset($data[0]['tax']) ? $data[0]['tax'] : '') / 100);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 12, 'TAX', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 12,$totals ? number_format($tax, 2) : '', '', 'R', false);

        $ftotal = $total1 + $total2 + $tax;
        PDF::SetXY(600, 570);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 20, 'TOTAL', '', '', false, 0);
        PDF::MultiCell(10, 12, ':', '', '', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(80, 12,$totals ? number_format($ftotal, 2) : '', '', 'R', false);
    }
}