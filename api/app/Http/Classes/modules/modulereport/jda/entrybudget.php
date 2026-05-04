<?php

namespace App\Http\Classes\modules\modulereport\jda;

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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entrybudget
{

    private $modulename = "Budget Setup";
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    private $reportheader;

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
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'refresh.action', 'history');

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $companyid = $config['params']['companyid'];

        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
        $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
        $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);

        $paramstr = "select
        'PDFM' as print,
        '$username' as prepared,
        '$approved' as approved,
        '$received' as received";
        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($filters)
    {
        $reporttype = $filters['params']['dataparams']['reporttype'];
        if ($reporttype == '1') {
            return $this->annual_query($filters);
        }else{
            return $this->default_query($filters);
        }
    }

    public function default_query($filters)
    {
        $companyid = $filters['params']['companyid'];
        $year      = isset($filters['params']['dataparams']['year'])      ? $filters['params']['dataparams']['year']      : '';
        $projectid = isset($filters['params']['dataparams']['projectid']) ? $filters['params']['dataparams']['projectid'] : 0;
        $deptid    = isset($filters['params']['dataparams']['deptid'])    ? $filters['params']['dataparams']['deptid']    : 0;
        $branchid  = isset($filters['params']['dataparams']['branch'])    ? $filters['params']['dataparams']['branch']    : 0;
        $poption   = isset($filters['params']['dataparams']['poption'])   ? $filters['params']['dataparams']['poption']   : 1;

        if ($projectid == 0 || $year == '') {
            return [];
        }

        $cat = $poption == 1 ? "('R','E')" : "('A','L','C')";

        $addonfilter = '';
        if ($companyid == 10) {
            if ($deptid != 0) $addonfilter .= " and b.deptid = " . $deptid;
            if ($branchid != 0) $addonfilter .= " and b.branch = " . $branchid;
        }

        $query = "
        select b.line, b.year, 
        concat(c.acno,' ',c.acnoname) as acno, c.acnoname, c.acno as acnocode,
        c.parent, concat(p.acno,' ',p.acnoname) as parentlabel,
        b.amt1, b.amt2, b.amt3, b.amt4, b.amt5, b.amt6,
        b.amt7, b.amt8, b.amt9, b.amt10, b.amt11, b.amt12,
        (b.amt1+b.amt2+b.amt3+b.amt4+b.amt5+b.amt6+b.amt7+b.amt8+b.amt9+b.amt10+b.amt11+b.amt12) as total
        from budget as b
        left join coa as c on c.acnoid = b.acnoid
        left join coa as p on p.acno = c.parent
        where c.cat in $cat
        and c.acno not in (select distinct parent from coa)
        and b.projectid = $projectid
        and b.year = $year
        $addonfilter
        order by c.parent, c.acno
        ";

        return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    }

    public function annual_query($filters)
    {
        $data = $this->default_query($filters);
        return $this->groupWithSubtotals($data);
    }

    public function reportplotting($params, $data)
    {
        $reporttype = $params['params']['dataparams']['reporttype'];
        switch($reporttype){
            case '0':
                return $this->budget_setup_PDF($params, $data);
                break;
            default:
                return $this->budget_setup_annual_PDF($params, $data);
                break;
        }
        return $this->budget_setup_PDF($params, $data);
    }

    public function budget_setup_PDF_HEADER($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $year = $params['params']['dataparams']['year'];
        $poption = $params['params']['dataparams']['poption'];
        $projectid = $params['params']['dataparams']['projectid'];
        $project = $params['params']['dataparams']['project'];
        $reporttype = $params['params']['dataparams']['reporttype'];

        $result = $this->coreFunctions->opentable("select name from projectmasterfile where line = ? and code = ?", [$projectid, $project]);
        $costcenter = !empty($result) ? $result[0]->name : '';

        $optionlbl = "";
        switch ($poption) {
            case 0:
                $optionlbl = "BALANCE SHEET";
                break;
            default:
                $optionlbl = "INCOME STATEMENT";
                break;
        }

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/tahoma.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('l', [1200, 800]);
        PDF::SetMargins(20, 20);

        $this->reportheader->getheader($params);

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(1160, 0, 'MONTHLY BUDGET', '', 'C');

        PDF::MultiCell(0, 0, "\n");

        $year = $params['params']['dataparams']['year'];
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Cost Center: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(530, 0, $costcenter, '', 'L', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Year: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(500, 0, $year . '  ' . $optionlbl, '', 'L', false, 1);

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(1160, 0, '', 'B');

        // column headers
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(240, 0, 'ACCOUNT', '', 'L', false, 0);
        PDF::MultiCell(70, 0, 'JAN', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'FEB', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'MAR', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'APR', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'MAY', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'JUN', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'JUL', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'AUG', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'SEP', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'OCT', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'NOV', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'DEC', '', 'R', false, 0);
        PDF::MultiCell(80, 0, 'TOTAL', '', 'R');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(1160, 0, '', 'B');
    }

    public function budget_setup_PDF($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);

        $font = "";
        $fontbold = "";
        $fontsize = 9;

        if (Storage::disk('sbcpath')->exists('/fonts/tahoma.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.TTF');
        }

        $this->budget_setup_PDF_HEADER($params, $data);

        if (empty($data)) {
            return PDF::Output($this->modulename . '.pdf', 'S');
        }

        $grand_total = 0;
        $month_totals = array_fill(1, 12, 0);

        for ($i = 0; $i < count($data); $i++) {
            $acno  = $data[$i]['acno'];
            $amt1  = $data[$i]['amt1'];
            $amt2  = $data[$i]['amt2'];
            $amt3  = $data[$i]['amt3'];
            $amt4  = $data[$i]['amt4'];
            $amt5  = $data[$i]['amt5'];
            $amt6  = $data[$i]['amt6'];
            $amt7  = $data[$i]['amt7'];
            $amt8  = $data[$i]['amt8'];
            $amt9  = $data[$i]['amt9'];
            $amt10 = $data[$i]['amt10'];
            $amt11 = $data[$i]['amt11'];
            $amt12 = $data[$i]['amt12'];
            $total = $data[$i]['total'];

            $month_totals[1]  += $amt1;
            $month_totals[2]  += $amt2;
            $month_totals[3]  += $amt3;
            $month_totals[4]  += $amt4;
            $month_totals[5]  += $amt5;
            $month_totals[6]  += $amt6;
            $month_totals[7]  += $amt7;
            $month_totals[8]  += $amt8;
            $month_totals[9]  += $amt9;
            $month_totals[10] += $amt10;
            $month_totals[11] += $amt11;
            $month_totals[12] += $amt12;
            $grand_total += $total;

            $maxrow = 1;

            $arr_acno  = $this->reporter->fixcolumn([$acno],                              55, 0);
            $arr_amt1  = $this->reporter->fixcolumn([$amt1  == 0 ? '-' : number_format($amt1,  $decimalcurr)], 13, 0);
            $arr_amt2  = $this->reporter->fixcolumn([$amt2  == 0 ? '-' : number_format($amt2,  $decimalcurr)], 13, 0);
            $arr_amt3  = $this->reporter->fixcolumn([$amt3  == 0 ? '-' : number_format($amt3,  $decimalcurr)], 13, 0);
            $arr_amt4  = $this->reporter->fixcolumn([$amt4  == 0 ? '-' : number_format($amt4,  $decimalcurr)], 13, 0);
            $arr_amt5  = $this->reporter->fixcolumn([$amt5  == 0 ? '-' : number_format($amt5,  $decimalcurr)], 13, 0);
            $arr_amt6  = $this->reporter->fixcolumn([$amt6  == 0 ? '-' : number_format($amt6,  $decimalcurr)], 13, 0);
            $arr_amt7  = $this->reporter->fixcolumn([$amt7  == 0 ? '-' : number_format($amt7,  $decimalcurr)], 13, 0);
            $arr_amt8  = $this->reporter->fixcolumn([$amt8  == 0 ? '-' : number_format($amt8,  $decimalcurr)], 13, 0);
            $arr_amt9  = $this->reporter->fixcolumn([$amt9  == 0 ? '-' : number_format($amt9,  $decimalcurr)], 13, 0);
            $arr_amt10 = $this->reporter->fixcolumn([$amt10 == 0 ? '-' : number_format($amt10, $decimalcurr)], 13, 0);
            $arr_amt11 = $this->reporter->fixcolumn([$amt11 == 0 ? '-' : number_format($amt11, $decimalcurr)], 13, 0);
            $arr_amt12 = $this->reporter->fixcolumn([$amt12 == 0 ? '-' : number_format($amt12, $decimalcurr)], 13, 0);
            $arr_total = $this->reporter->fixcolumn([$total == 0 ? '-' : number_format($total, $decimalcurr)], 13, 0);

            $maxrow = $this->othersClass->getmaxcolumn([
                $arr_acno,
                $arr_amt1,
                $arr_amt2,
                $arr_amt3,
                $arr_amt4,
                $arr_amt5,
                $arr_amt6,
                $arr_amt7,
                $arr_amt8,
                $arr_amt9,
                $arr_amt10,
                $arr_amt11,
                $arr_amt12,
                $arr_total
            ]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(240, 15, isset($arr_acno[$r])  ? $arr_acno[$r]  : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt1[$r])  ? $arr_amt1[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt2[$r])  ? $arr_amt2[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt3[$r])  ? $arr_amt3[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt4[$r])  ? $arr_amt4[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt5[$r])  ? $arr_amt5[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt6[$r])  ? $arr_amt6[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt7[$r])  ? $arr_amt7[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt8[$r])  ? $arr_amt8[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt9[$r])  ? $arr_amt9[$r]  : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt10[$r]) ? $arr_amt10[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt11[$r]) ? $arr_amt11[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70,  15, isset($arr_amt12[$r]) ? $arr_amt12[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(80,  15, isset($arr_total[$r]) ? $arr_total[$r] : '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
            }

            if (PDF::getY() > 760) {
                $this->budget_setup_PDF_HEADER($params, $data);
            }
        }

        // grand total row
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(1160, 0, '', 'B');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(240, 0, 'GRAND TOTAL', '', 'L', false, 0);
        PDF::MultiCell(70,  0, $month_totals[1]  == 0 ? '-' : number_format($month_totals[1],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[2]  == 0 ? '-' : number_format($month_totals[2],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[3]  == 0 ? '-' : number_format($month_totals[3],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[4]  == 0 ? '-' : number_format($month_totals[4],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[5]  == 0 ? '-' : number_format($month_totals[5],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[6]  == 0 ? '-' : number_format($month_totals[6],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[7]  == 0 ? '-' : number_format($month_totals[7],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[8]  == 0 ? '-' : number_format($month_totals[8],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[9]  == 0 ? '-' : number_format($month_totals[9],  $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[10] == 0 ? '-' : number_format($month_totals[10], $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[11] == 0 ? '-' : number_format($month_totals[11], $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(70,  0, $month_totals[12] == 0 ? '-' : number_format($month_totals[12], $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(80,  0, $grand_total      == 0 ? '-' : number_format($grand_total,       $decimalcurr), '', 'R');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function budget_setup_annual_PDF_HEADER($params, $data)
    {
        $center    = $params['params']['center'];
        $username  = $params['params']['user'];
        $year      = $params['params']['dataparams']['year'];
        $poption   = $params['params']['dataparams']['poption'];
        $projectid = $params['params']['dataparams']['projectid'];
        $project   = $params['params']['dataparams']['project'];

        $result = $this->coreFunctions->opentable(
            "select name from projectmasterfile where line = ? and code = ?",
            [$projectid, $project]
        );
        $costcenter = !empty($result) ? $result[0]->name : '';

        $optionlbl = "";
        switch ($poption) {
            case 0:
                $optionlbl = "BALANCE SHEET";
                break;
            default:
                $optionlbl = "INCOME STATEMENT";
                break;
        }

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/tahoma.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        $this->reportheader->getheader($params);

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(720, 0, 'ANNUAL BUDGET', '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Cost Center: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(640, 0, $costcenter, '', 'L', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Year: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(640, 0, $year . '  ' . $optionlbl, '', 'L', false, 1);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        // column headers - only 3 columns
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(420, 0, 'ACCOUNT',    '', 'L', false, 0);
        PDF::MultiCell(150, 0, 'AVE/MONTH',  '', 'R', false, 0);
        PDF::MultiCell(150, 0, 'TOTAL',      '', 'R');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function budget_setup_annual_PDF($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);

        $font = "";
        $fontbold = "";
        $fontsize = 9;

        if (Storage::disk('sbcpath')->exists('/fonts/tahoma.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.TTF');
        }

        $this->budget_setup_annual_PDF_HEADER($params, $data);
        if (empty($data)) {
            return PDF::Output($this->modulename . '.pdf', 'S');
        }

        $grand_total    = 0;
        $grand_avemonth = 0;

        for ($i = 0; $i < count($data); $i++) {
            $isHeader   = isset($data[$i]['is_header'])   && $data[$i]['is_header'];
            $isSubtotal = isset($data[$i]['is_subtotal']) && $data[$i]['is_subtotal'];

            $acno     = $data[$i]['acno'];
            $total    = $data[$i]['total'];
            $avemonth = round($total / 12, 2);

            // only accumulate grand total from actual data rows
            if (!$isHeader && !$isSubtotal) {
                $grand_total    += $total;
                $grand_avemonth += $avemonth;
            }

            $arr_acno     = $this->reporter->fixcolumn([$acno],                                  55, 0);
            $arr_avemonth = $this->reporter->fixcolumn([$avemonth == 0 ? '-' : number_format($avemonth, $decimalcurr)], 13, 0);
            $arr_total    = $this->reporter->fixcolumn([$total    == 0 ? '-' : number_format($total,    $decimalcurr)], 13, 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_avemonth, $arr_total]);

            for ($r = 0; $r < $maxrow; $r++) {
                if ($isHeader) {
                    // bold, no amounts
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(720, 15, isset($arr_acno[$r]) ? $arr_acno[$r] : '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                } elseif ($isSubtotal) {
                    // bold with amounts and separator
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(420, 15, isset($arr_acno[$r])     ? $arr_acno[$r]     : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(150, 15, isset($arr_avemonth[$r]) ? $arr_avemonth[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(150, 15, isset($arr_total[$r])    ? $arr_total[$r]    : '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                } else {
                    // normal row
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(420, 15, isset($arr_acno[$r])     ? $arr_acno[$r]     : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(150, 15, isset($arr_avemonth[$r]) ? $arr_avemonth[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(150, 15, isset($arr_total[$r])    ? $arr_total[$r]    : '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                }
            }

            // separator line after subtotal
            if ($isSubtotal) {
                PDF::SetFont($font, '', 5);
                PDF::MultiCell(720, 0, '', '');
            }

            if (PDF::getY() > 1140) {
                $this->budget_setup_annual_PDF_HEADER($params, $data);
            }
        }

        // grand total row
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(420, 0, 'GRAND TOTAL',                                '', 'L', false, 0);
        PDF::MultiCell(150, 0, $grand_avemonth == 0 ? '-' : number_format($grand_avemonth, $decimalcurr), '', 'R', false, 0);
        PDF::MultiCell(150, 0, $grand_total    == 0 ? '-' : number_format($grand_total,    $decimalcurr), '', 'R');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function groupWithSubtotals($data)
    {
        $grouped = [];
        $result  = [];

        foreach ($data as $row) {
            $parent = $row['parent'];
            if (!isset($grouped[$parent])) {
                $grouped[$parent] = [
                    'parentlabel' => $row['parentlabel'],
                    'rows'        => []
                ];
            }
            $grouped[$parent]['rows'][] = $row;
        }

        foreach ($grouped as $parent => $group) {
            $sub_amt1  = $sub_amt2  = $sub_amt3  = $sub_amt4  = 0;
            $sub_amt5  = $sub_amt6  = $sub_amt7  = $sub_amt8  = 0;
            $sub_amt9  = $sub_amt10 = $sub_amt11 = $sub_amt12 = 0;
            $sub_total = 0;

            foreach ($group['rows'] as $row) {
                $result[]   = array_merge($row, ['is_subtotal' => false, 'is_header' => false]);
                $sub_amt1  += $row['amt1'];
                $sub_amt2  += $row['amt2'];
                $sub_amt3  += $row['amt3'];
                $sub_amt4  += $row['amt4'];
                $sub_amt5  += $row['amt5'];
                $sub_amt6  += $row['amt6'];
                $sub_amt7  += $row['amt7'];
                $sub_amt8  += $row['amt8'];
                $sub_amt9  += $row['amt9'];
                $sub_amt10 += $row['amt10'];
                $sub_amt11 += $row['amt11'];
                $sub_amt12 += $row['amt12'];
                $sub_total += $row['total'];
            }

            // subtotal row - simplified label
            $result[] = [
                'is_subtotal' => true,
                'is_header'   => false,
                'acno'        => '',
                'parent'      => $parent,
                'parentlabel' => $group['parentlabel'],
                'amt1'        => $sub_amt1,
                'amt2'        => $sub_amt2,
                'amt3'        => $sub_amt3,
                'amt4'        => $sub_amt4,
                'amt5'        => $sub_amt5,
                'amt6'        => $sub_amt6,
                'amt7'        => $sub_amt7,
                'amt8'        => $sub_amt8,
                'amt9'        => $sub_amt9,
                'amt10'       => $sub_amt10,
                'amt11'       => $sub_amt11,
                'amt12'       => $sub_amt12,
                'total'       => $sub_total,
            ];
        }

        return $result;
    }
}