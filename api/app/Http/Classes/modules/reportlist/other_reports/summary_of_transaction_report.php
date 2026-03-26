<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use DateTime;
use DateInterval;
use DatePeriod;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;
use App\Http\Classes\reportheader;

class summary_of_transaction_report
{
    public $modulename = 'Summary of Transactions Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    private $reportheader;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $colsize = 0;
    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '360'];
    public $displaylayoutsize = 0;
    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
        $this->reportheader = new reportheader;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red'],
        ]);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr = "select 
        'default' as print,
        date_format(now(),'%Y-%m-01') as start,
        left(now(),10) as end
        ";

        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata()
    {
        $str = $this->reportplotting();
        //return $str;
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    private function get_module_values($doc, $num, $start, $end, $head, $hhead, $table, $htable, $amount_origin_field, $branch_code)
    {
        switch ($doc) {
                // case 'MC':
                //     $qry = "select ifnull(count(num.docno),0) as value
                //             from $num as num 
                //             left join (
                //             select h.trno,h.dateid,h.crtrno from $head as h
                //             union all
                //             select h.trno,h.dateid,h.crtrno from $hhead as h
                //             ) as head on head.trno=num.trno
                //             where num.doc = '$doc' and num.center = '$branch_code'
                //             #and head.dateid between '$start' and '$end'
                //             ";

                //     $count = $this->coreFunctions->datareader($qry);
                //     $posted = $this->coreFunctions->datareader($qry . " and head.crtrno <> 0");
                //     $unposted = $this->coreFunctions->datareader($qry . " and head.crtrno = 0");
                //     $trno_list_for_total_amount = $this->coreFunctions->datareader(
                //         str_replace("ifnull(count(num.docno),0)", "group_concat(num.trno)", $qry)
                //     );
                //     $totalamount = $this->coreFunctions->datareader("select ifnull(sum($amount_origin_field),0) as value from(
                //                 select trno,$amount_origin_field from $table
                //                 union all
                //                 select trno,$amount_origin_field from $htable
                //                 ) as s
                //                 where trno in ($trno_list_for_total_amount)");
                //     break;


            default:
                $qry = "select ifnull(count(num.docno),0) as value
                        from $num as num 
                        left join (
                        select h.trno,h.dateid from $head as h
                        union all
                        select h.trno,h.dateid from $hhead as h
                        ) as head on head.trno=num.trno
                        where num.doc = '$doc' and num.center = '$branch_code'
                        and date(head.dateid) between '$start' and '$end'
                        ";



                $count = $this->coreFunctions->datareader($qry);
                $posted = $this->coreFunctions->datareader($qry . " and num.postdate is not null");
                $unposted = $this->coreFunctions->datareader($qry . " and num.postdate is null");
                $trno_list_for_total_amount = $this->coreFunctions->datareader(
                    str_replace("ifnull(count(num.docno),0)", "group_concat(num.trno)", $qry)
                );

                $totalamount = $this->coreFunctions->datareader("select ifnull(sum($amount_origin_field),0) as value from(
                            select trno,$amount_origin_field from $table
                            union all
                            select trno,$amount_origin_field from $htable
                            ) as s
                            where trno in ($trno_list_for_total_amount)");

                break;
        }


        return [
            $branch_code . "~count" => $count,
            $branch_code . "~posted" => $posted,
            $branch_code . "~unposted" => $unposted,
            $branch_code . "~totalamount" => $totalamount
        ];
    }

    //get all branch
    private function get_branch()
    {
        $qry = "select code,name from center order by code asc";
        return $this->coreFunctions->opentable($qry);
    }

    //for setting up layout size
    private function count_branch()
    {
        return $this->coreFunctions->datareader("select count(code) as value from center");
    }


    public function default_query()
    {

        //add more based on PURCHASES, SALES, PAYABLES, RECEIVABLES
        $require_docs = [
            'PR',
            'PO',
            'RR',
            'DM',
            'SO',
            'MJ',
            'CM',
            'CI',
            'MC',
            'PQ',
            'SV',
            'AP',
            'PV',
            'CV',
            'AR',
            'CR'
        ];

        // $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        // $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        // $start = date("Y-m-d", strtotime('-1 day',strtotime($this->othersClass->getCurrentDate())));
        // $end = date("Y-m-d", strtotime('-1 day',strtotime($this->othersClass->getCurrentDate())));
        $asofdate = date("Y-m-d", strtotime($this->othersClass->getCurrentDate()));
        //$asofdate = date("Y-m-d",strtotime("2024-08-01"));
        $start = date("Y", strtotime($asofdate)) . "-" . date("m", strtotime($asofdate)) . "-01";

        $end = date("Y-m-d", strtotime($this->othersClass->getCurrentDate()));




        //get all branches
        $branch_data = $this->get_branch();

        $num = '';
        $table = '';
        $htable = '';
        $amount_origin_field = '';
        $current_branch = '';
        //$key or required docs is what goes down
        foreach ($require_docs as $key => $value) {
            $module_data[$value] = [];
            switch ($value) {
                case 'PR':
                    $num = 'transnum';
                    $head = 'prhead';
                    $hhead = 'hprhead';
                    $table = 'prstock';
                    $htable = 'hprstock';
                    $amount_origin_field = 'ext';
                    break;
                case 'PO':
                    $num = 'transnum';
                    $head = 'pohead';
                    $hhead = 'hpohead';
                    $table = 'postock';
                    $htable = 'hpostock';
                    $amount_origin_field = 'ext';
                    break;
                case 'SO':
                    $num = 'transnum';
                    $head = 'sohead';
                    $hhead = 'hsohead';
                    $table = 'sostock';
                    $htable = 'hsostock';
                    $amount_origin_field = 'ext';
                    break;
                case 'MC':
                    $num = 'transnum';
                    $head = 'mchead';
                    $hhead = 'hmchead';
                    $table = 'mcdetail';
                    $htable = 'hmcdetail';
                    $amount_origin_field = 'amount';
                    break;

                case 'RR':
                case 'DM':
                case 'MJ':
                case 'CM':
                case 'CI':
                    $num = 'cntnum';
                    $head = 'lahead';
                    $hhead = 'glhead';
                    $table = 'lastock';
                    $htable = 'glstock';
                    $amount_origin_field = 'ext';
                    break;

                case 'PQ':
                    $num = 'transnum';
                    $head = 'pqhead';
                    $hhead = 'hpqhead';
                    $table = 'pqdetail';
                    $htable = 'hpqdetail';
                    $amount_origin_field = 'amt';
                    break;
                case 'SV':
                    $num = 'transnum';
                    $head = 'svhead';
                    $hhead = 'hsvhead';
                    $table = 'svdetail';
                    $htable = 'hsvdetail';
                    $amount_origin_field = 'db';
                    break;

                case 'AP':
                case 'PV':
                case 'CV':
                case 'AR':
                case 'CR':
                    $num = 'cntnum';
                    $head = 'lahead';
                    $hhead = 'glhead';
                    $table = 'ladetail';
                    $htable = 'gldetail';
                    $amount_origin_field = 'db';
                    break;
            }
            //$branch_data is what goes sideways
            foreach ($branch_data as $index => $branch) {

                $current_branch =
                    $this->get_module_values($value, $num, $start, $end, $head, $hhead, $table, $htable, $amount_origin_field, $branch->code);

                //data assembly
                $module_data[$value][$branch->code . "~count"] = $current_branch[$branch->code . "~count"];
                $module_data[$value][$branch->code . "~unposted"] = $current_branch[$branch->code . "~unposted"];
                $module_data[$value][$branch->code . "~posted"] = $current_branch[$branch->code . "~posted"];
                $module_data[$value][$branch->code . "~totalamount"] = $current_branch[$branch->code . "~totalamount"];
            }
        }

        return $module_data;
    }


    public function reportplotting()
    {
        $result = $this->default_query();
        $data =  $this->default_layout($result);

        return $data;
    }

    private function table_header($layoutsize, $branch_data, $column_size, $center_size)
    {
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = 'Arial';
        $printtype   = 'default';

        $fontsize = '12';
        $font_size = '12';
        $padding = '';
        $margin = '';
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= '<br/>';
        $asofdate = date("m/d/Y", strtotime($this->othersClass->getCurrentDate()));
        $start = date("m", strtotime($asofdate)) . "/1/" . date("Y", strtotime($asofdate));
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename . ' ' . $start . ' to ' . $asofdate, null, null, false, $border, '', 'L', $font, '16', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "<br>";

        //for center names
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'TLR', 'L', $font, '8', 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TLR', 'L', $font, '8', 'B', '', '');
        foreach ($branch_data as $key => $value) {
            $str .= $this->reporter->col($value->name, $column_size, null, false, $border, 'TBL', 'L', $font, '8', 'B', '', '');
            $str .= $this->reporter->col('', $column_size, null, false, $border, 'TB', 'C', $font, '8', 'B', '', '');
            $str .= $this->reporter->col('', $column_size, null, false, $border, 'TB', 'C', $font, '8', 'B', '', '');
            $str .= $this->reporter->col('', $column_size, null, false, $border, 'TBR', 'C', $font, '8', 'B', '', '');
        }
        // if ($printtype == 'default') {
        //     foreach ($branch_data as $key => $value) {
        //         $str .= $this->reporter->col($value->name, $center_size, null, false, $border, 'TBLR', 'C', $font, '9', 'B', '', '');
        //     }
        // } else {
        //     foreach ($branch_data as $key => $value) {
        //         $str .= $this->reporter->col($value->name, $column_size, null, false, $border, 'TBL', 'L', $font, '8', 'B', '', '');
        //         $str .= $this->reporter->col('', $column_size, null, false, $border, 'TB', 'C', $font, '8', 'B', '', '');
        //         $str .= $this->reporter->col('', $column_size, null, false, $border, 'TB', 'C', $font, '8', 'B', '', '');
        //         $str .= $this->reporter->col('', $column_size, null, false, $border, 'TBR', 'C', $font, '8', 'B', '', '');
        //     }
        // }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //for center required columns
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Module ', '115', null, false, $border, 'BLR', 'C', $font, '8', 'B', '', '');
        $str .= $this->reporter->col('Total', '110', null, false, $border, 'BLR', 'C', $font, '8', 'B', '', '');
        foreach ($branch_data as $key => $value) {
            $str .= $this->reporter->col('# of Transactions', $column_size, null, false, $border, 'TBLR', 'C', $font, '8', 'B', '', '');
            $str .= $this->reporter->col('Unposted', $column_size, null, false, $border, 'TBLR', 'C', $font, '8', 'B', '', '');
            $str .= $this->reporter->col('Posted', $column_size, null, false, $border, 'TBLR', 'C', $font, '8', 'B', '', '');
            $str .= $this->reporter->col('Total Amount', $column_size, null, false, $border, 'TBLR', 'C', $font, '8', 'B', '', '');
        }

        $this->displaylayoutsize = $layoutsize;
        return $str;
    }

    private function default_layout($data)
    {

        $branch_count = $this->count_branch();
        //100 for module label, 70x4 or 280 per branch, 100 for grandtotal
        // $this->reportParams['layoutSize'] = 100+($this->reportParams['layoutSize'] * $branch_count)+100;
        $layoutsize = 220 + ($this->reportParams['layoutSize'] * $branch_count);

        $column_size = (($layoutsize - 220) / $branch_count) / 4;
        $center_size = ($layoutsize - 220) / $branch_count;

        $border = '1px solid';
        $end = date("Y-m-d", strtotime($this->othersClass->getCurrentDate()));
        $font = 'Arial';
        $font_size = '12';
        $padding = '10';
        $margin = '20';
        $str = '';
        $count = 41;
        $page = 40;

        $str .= $this->reporter->beginreport($layoutsize);

        $branch_data = $this->get_branch();
        $str .= $this->table_header($layoutsize, $branch_data, $column_size, $center_size);

        $current_col = '';
        $align = '';
        $module_total = 0;
        $grand_total = 0;
        $module_label = '';
        //remove 0 count modules
        $data = $this->clean_display($data);
        foreach ($data as $key => $value) {
            $module_total = 0;

            switch ($key) {
                case 'PR':
                    $module_label = 'Purchase Requisition';
                    break;
                case 'PO':
                    $module_label = 'Purchase Order';
                    break;
                case 'RR':
                    $module_label = 'Receiving Report';
                    break;
                case 'DM':
                    $module_label = 'Purchase Return';
                    break;

                case 'SO':
                    $module_label = 'Sales Order';
                    break;
                case 'MC':
                    $module_label = 'MC Collection';
                    break;

                case 'MJ':
                    $module_label = 'Sales Journal';
                    break;
                case 'CM':
                    $module_label = 'Sales Return';
                    break;
                case 'CI':
                    $module_label = 'Spare Parts Issuance';
                    break;

                case 'PQ':
                    $module_label = 'Petty Cash Request';
                    break;
                case 'SV':
                    $module_label = 'Petty Cash Voucher';
                    break;

                case 'AP':
                    $module_label = 'AP Setup';
                    break;
                case 'PV':
                    $module_label = 'Accounts Payable Voucher';
                    break;
                case 'CV':
                    $module_label = 'Cash/Check Voucher';
                    break;
                case 'AR':
                    $module_label = 'AR Setup';
                    break;
                case 'CR':
                    $module_label = 'Received Payment';
                    break;
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($module_label, '115', null, false, $border, 'TBLR', 'L', $font, '7', 'B', '', '');

            foreach ($value as $col => $colval) {
                $current_col = explode('~', $col);
                if ($current_col[1] == 'totalamount') {
                    $module_total += (float)$colval;
                }
            }
            $str .= $this->reporter->col(number_format($module_total, 2), '110', null, false, $border, 'TBLR', 'R', $font, '8', '', '', '');
            foreach ($value as $col => $colval) {
                $current_col = explode('~', $col);
                if ($current_col[1] == 'count') {
                    $align = 'C';
                } else {
                    $align = 'R';
                }
                if ($current_col[1] == 'totalamount') {
                    $colval = number_format((float)$colval, 2);
                }
                $str .= $this->reporter->col($colval, $column_size, null, false, $border, 'TBLR', $align, $font, '8', '', '', '');
            }
            $grand_total += $module_total;
        }
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }

    private function clean_display($data)
    {
        $count = 0;
        $temp = $data;
        foreach ($temp as $key => $value) {
            $count = 0;
            foreach ($value as $col => $colval) {
                $current_col = explode('~', $col);
                if ($current_col[1] == 'count') {
                    $count += $colval;
                }
            }
            if ($count == 0) {
                unset($temp[$key]);
            }
        }
        return $temp;
    }

    public function default_PDF($data)
    {
        $branch_count = $this->count_branch();
        $branch_data = $this->get_branch();
        $font = "";
        $fontbold = "";
        $fontsize = "8";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_header_PDF($branch_data, $branch_count);
        PDF::SetFont($font, '', 5);

        if (!empty($data)) {
            $current_col = '';
            $align = '';
            $module_total = 0;
            $grand_total = 0;
            $module_label = '';
            $j = 0;
            $codebase = '';
            $data = $this->clean_display($data);
            foreach ($data as $key => $value) {
                $module_total = 0;

                switch ($key) {
                    case 'PR':
                        $module_label = 'Purchase Requisition';
                        break;
                    case 'PO':
                        $module_label = 'Purchase Order';
                        break;
                    case 'RR':
                        $module_label = 'Receiving Report';
                        break;
                    case 'DM':
                        $module_label = 'Purchase Return';
                        break;

                    case 'SO':
                        $module_label = 'Sales Order';
                        break;
                    case 'MC':
                        $module_label = 'MC Collection';
                        break;

                    case 'MJ':
                        $module_label = 'Sales Journal';
                        break;
                    case 'CM':
                        $module_label = 'Sales Return';
                        break;
                    case 'CI':
                        $module_label = 'Spare Parts Issuance';
                        break;

                    case 'PQ':
                        $module_label = 'Petty Cash Request';
                        break;
                    case 'SV':
                        $module_label = 'Petty Cash Voucher';
                        break;

                    case 'AP':
                        $module_label = 'AP Setup';
                        break;
                    case 'PV':
                        $module_label = 'Accounts Payable Voucher';
                        break;
                    case 'CV':
                        $module_label = 'Cash/Check Voucher';
                        break;
                    case 'AR':
                        $module_label = 'AR Setup';
                        break;
                    case 'CR':
                        $module_label = 'Received Payment';
                        break;
                }
                $i = 0;
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(100, 20, $module_label, 'TBLR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                foreach ($value as $col => $colval) {
                    $current_col = explode('~', $col);
                    if ($current_col[1] == 'totalamount') {
                        $module_total += (float)$colval;
                        $colval = number_format((float)$colval, 2);
                    }
                    PDF::MultiCell(50, 20, $colval, 'RB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                }
                PDF::MultiCell(60, 20, number_format($module_total, 2), 'BR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
                $grand_total += $module_total;
            }
        }
        PDF::MultiCell($this->colsize + 160, 20, 'GRAND TOTAL:         ' . number_format($grand_total, 2), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function default_header_PDF($branch_data, $branch_count)
    {
        $font = "";
        $fontbold = "";
        $fontsize = 10;
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
        PDF::AddPage('l', [800, 1600]);
        PDF::SetMargins(25, 25);

        PDF::SetFont($font, '', 9);

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        // PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'TLR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        $countbranch = $this->count_branch();
        foreach ($branch_data as $key => $value) {
            PDF::MultiCell(200, 20, $value->name, 'TBL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            $this->colsize += 200;
        }
        $this->colsize + 100;
        PDF::MultiCell(60, 20, '', 'TLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, 'Module', 'BLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        foreach ($branch_data as $key => $value) {
            PDF::MultiCell(50, 20, '# of Transactions', 'RB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', 8);
            PDF::MultiCell(50, 20, 'Unposted', 'RB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, 'Posted', 'RB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 20, 'Total Amount', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        }
        PDF::MultiCell(60, 20, 'Total', 'TBLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    } // end header

}//end class