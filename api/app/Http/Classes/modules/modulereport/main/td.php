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

class TD
{
    private $modulename = "Delivery Trucking";
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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'prepared', 'checked', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {

        return $this->coreFunctions->opentable(
            "select
        'PDFM' as print,
        '0' as reporttype,
        '' as checked,
        '' as prepared,
        '' as approved,
        '' as received
        "
        );
    }

    public function report_default_query($config)
    {

        $trno = $config['params']['dataid'];
        $query = "select head.trno,head.dateid,head.clientname as driver,ifnull(hp.clientname, '') as helpername,ifnull(hpp.clientname, '') as helpername2,
            truck.clientname as truck,info.plateno,head.address as area,head.tel as contact,head.checkedby as checked,head.odoin,head.odoout,
            head.amt as petty_cash,head.rem,concat(right(ifnull(la.docno, gl.docno),6)) as dr,
            ifnull(la.clientname, gl.clientname) as customer,
            ifnull((select sum(ext) from lastock where trno = stock.refx),
            ifnull((select sum(ext) from glstock where trno = stock.refx), 0)) as amount,
            ifnull(la.terms, gl.terms) as terms,ifnull(agent.clientname, glagent.clientname) as agent,
            stock.fstatus as payment, stock.rem as remarks
            from rohead as head
            left join rostock as stock on stock.trno = head.trno
            left join client on head.client = client.client
            left join headinfotrans as info on info.trno=head.trno
            left join client as truck on truck.clientid=info.truckid
            left join client as hp on hp.clientid=info.helperid
            left join client as hpp on hpp.clientid=info.helperid2
            left join lahead as la on la.trno=stock.refx
            left join glhead as gl on gl.trno=stock.refx
            left join client as agent on agent.client=la.agent
            left join client as glagent on glagent.clientid=gl.agentid
            where head.doc='TD' and head.trno='$trno'
            UNION ALL
            select head.trno,head.dateid,head.clientname as driver,ifnull(hp.clientname, '') as helpername,ifnull(hpp.clientname, '') as helpername2,
            truck.clientname as truck,info.plateno,head.address as area,head.tel as contact,head.checkedby as checked,head.odoin,head.odoout,
            head.amt as petty_cash,head.rem,concat(right(ifnull(la.docno, gl.docno),6)) as dr,
            ifnull(la.clientname, gl.clientname) as customer,
            ifnull((select sum(ext) from lastock where trno = stock.refx),
            ifnull((select sum(ext) from glstock where trno = stock.refx), 0)) as amount,
            ifnull(la.terms, gl.terms) as terms,ifnull(agent.clientname, glagent.clientname) as agent,
            stock.fstatus as payment, stock.rem as remarks
            from hrohead as head
            left join (select trno, line, refx, fstatus, rem from rostock
            union all
            select trno, line, refx, fstatus, rem from hrostock) as stock on stock.trno = head.trno
            left join client on head.client = client.client
            left join hheadinfotrans as info on info.trno=head.trno
            left join client as truck on truck.clientid=info.truckid
            left join client as hp on hp.clientid=info.helperid
            left join client as hpp on hpp.clientid=info.helperid2
            left join lahead as la on la.trno=stock.refx
            left join glhead as gl on gl.trno=stock.refx
            left join client as agent on agent.client=la.agent
            left join client as glagent on glagent.clientid=gl.agentid
            where head.doc='TD' and head.trno='$trno'
            order by dr";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function report_default_expense($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select part.rem as breakdown_expense,part.amount as p_amount
            from rohead as head
            left join particulars as part on part.tdtrno = head.trno
            where head.trno = '$trno' and part.rem is not null
            union all
            select part.rem as breakdown_expense,part.amount as p_amount
            from hrohead as head
            left join particulars as part on part.tdtrno = head.trno
            where head.trno = '$trno' and part.rem is not null";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        return $this->default_td_layout($params, $data);
    }

    public function default_td_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "TAHOMA";
        $fontbold = "";
        $fontsize = 12;
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
        // PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetX(20);
        PDF::SetFont($fontbold, '', 17);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 16);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');


        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(0, 0, "\n");

        PDF::SetX(20);
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(430, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "DELIVERY TRUCK : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['plateno']) ? $data[0]['plateno'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");

        // Date / Area
        PDF::SetX(20);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 20, "DATE : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['dateid']) ? date('F j, Y', strtotime($data[0]['dateid'])) : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "AREA : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['area']) ? $data[0]['area'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // Driver / Contact
        PDF::SetX(20);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 20, "DRIVER : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['driver']) ? $data[0]['driver'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "CONTACT NO. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // Helper1 / Checked By
        PDF::SetX(20);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 20, "HELPER 1 : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['helpername']) ? $data[0]['helpername'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "CHECKED BY : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['checked']) ? $data[0]['checked'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // Helper2 / Odo In
        PDF::SetX(20);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 20, "HELPER 2 : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['helpername2']) ? $data[0]['helpername2'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(150, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "ODO IN : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['odoin']) ? $data[0]['odoin'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        // Odo Out
        PDF::SetX(20);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(430, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "ODO OUT : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, (isset($data[0]['odoout']) ? $data[0]['odoout'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");

        // PDF::SetX(20);
        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(750, 0, '', 'T');

        PDF::SetX(20);
        PDF::SetCellPaddings(0, 3, 0, 0); // left, top, right, bottom
        PDF::SetFillColor(147, 197, 114); // pistachio background (RGB)
        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(70, 20, "DR#", 'LRTB', 'C', true, 0);
        PDF::MultiCell(180, 20, "CUSTOMER", 'LRTB', 'C', true, 0);
        PDF::MultiCell(100, 20, "AMOUNT", 'LRTB', 'C', true, 0);
        PDF::MultiCell(70, 20, "TERMS", 'LRTB', 'C', true, 0);
        PDF::MultiCell(120, 20, "AGENT", 'LRTB', 'C', true, 0);
        PDF::MultiCell(80, 20, "PAYMENT", 'LRTB', 'C', true, 0);
        PDF::MultiCell(130, 20, "REMARKS", 'LRTB', 'C', true);
        PDF::SetCellPaddings(0, 0, 0, 0); // reset padding
        PDF::SetFillColor(255, 255, 255); // reset background (RGB)


        // PDF::SetX(20);
        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(750, 0, '', 'B');
    }

    public function default_td_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_td_header_PDF($params, $data);

        // PDF::SetX(20);
        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(750, 0, '', '');

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $dr = $data[$i]['dr'];
                $customer = $data[$i]['customer'];
                $amount = (float) $data[$i]['amount'];
                $terms = $data[$i]['terms'];
                $agent = $data[$i]['agent'];
                $payment = $data[$i]['payment'];
                $remarks = $data[$i]['remarks'];

                $arr_dr = $this->reporter->fixcolumn([$dr], '12', 0);
                $arr_customer = $this->reporter->fixcolumn([$customer], '30', 0);
                $arr_amount = $this->reporter->fixcolumn([number_format($amount, $decimalcurr)], '16', 0);
                $arr_terms = $this->reporter->fixcolumn([$terms], '13', 0);
                $arr_agent = $this->reporter->fixcolumn([$agent], '20', 0);
                $arr_payment = $this->reporter->fixcolumn([$payment], '10', 0);
                $arr_remarks = $this->reporter->fixcolumn([$remarks], '20', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_dr, $arr_customer, $arr_amount, $arr_terms, $arr_agent, $arr_payment, $arr_remarks]);

                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetX(20);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::SetCellPaddings(0, 3, 0, 0); // left, top, right, bottom
                    PDF::MultiCell(70, 20, (isset($arr_dr[$r]) ? $arr_dr[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(180, 20, (isset($arr_customer[$r]) ? $arr_customer[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 20, (isset($arr_amount[$r]) ? $arr_amount[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 20, (isset($arr_terms[$r]) ? $arr_terms[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(120, 20, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 20, (isset($arr_payment[$r]) ? $arr_payment[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(130, 20, (isset($arr_remarks[$r]) ? $arr_remarks[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::SetCellPaddings(0, 0, 0, 0); // reset padding

                }

                $totalext += $amount;

                if (PDF::getY() > 600) {
                    $this->default_td_header_PDF($params, $data);
                }
            }
        }

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetXY(20, 620);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(260, 20, "TOTAL : ", 'RT', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetFillColor(147, 197, 114); // light green background (RGB)
        PDF::MultiCell(100, 20, number_format($totalext, $decimalcurr), 'LRT', 'R', true, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFillColor(255, 255, 255); // reset background (RGB)
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(390, 20, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

       // vertically allign(reset it after)
        PDF::SetCellPaddings(0, 3, 0, 0); // left, top, right, bottom
        PDF::SetXY(20, 640);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetFillColor(200, 200, 200); // grey background (RGB)
        PDF::MultiCell(750, 20, 'REMINDER!!!', 'BT', 'C', true);
        PDF::SetCellPaddings(0, 0, 0, 0); // reset padding
        PDF::SetFillColor(255, 255, 255); // reset color

        // PDF::SetX(20);
        // PDF::MultiCell(0, 0, "\n");


        PDF::SetX(20);
        PDF::SetFont($font, '', $fontsize);
        PDF::SetCellPaddings(0, 3, 0, 0); // left, top, right, bottom
        PDF::SetTextColor(255, 0, 0); // red text
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(700, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L');
        PDF::SetTextColor(0, 0, 0); // reset to black
        PDF::SetCellPaddings(0, 0, 0, 0); // reset padding


        // PDF::MultiCell(0, 0, "\n\n");

        // Breakdown of Expenses / Petty Cash 
        $expenses = $this->report_default_expense($params);
        $totalexpenses = 0;
        $pettycash = isset($data[0]['petty_cash']) ? (float) $data[0]['petty_cash'] : 0;

        $col1 = 350; // BREAKDOWN OF EXPENSES
        $col2 = 170; // AMOUNT
        $col3 = 230; // PETTY CASH

        PDF::SetLineWidth(0.01); // keep the grid lines thin, not bold
        PDF::SetDrawColor(0, 0, 0);

        PDF::SetXY(20, 720);
        PDF::SetCellPaddings(0, 3, 0, 0); // left, top, right, bottom
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetFillColor(147, 197, 114); // light green background (RGB)
        PDF::MultiCell($col1, 20, 'BREAKDOWN OF EXPENSES', 1, 'C', true, 0, '', '', true, 0, false, true, 0, 'M');
        PDF::MultiCell($col2, 20, 'AMOUNT', 1, 'C', true, 0, '', '', true, 0, false, true, 0, 'M');
        PDF::MultiCell($col3, 20, 'PETTY CASH:', 1, 'C', true, 1, '', '', true, 0, false, true, 0, 'M');
        PDF::SetCellPaddings(0, 3, 0, 0); // reset padding
        PDF::SetFillColor(255, 255, 255); // reset background (RGB)

        $minrows = 7;
        $expenserows = max($minrows, count($expenses));
        $dataTopY = PDF::GetY();

        for ($e = 0; $e < $expenserows; $e++) {
            $desc = isset($expenses[$e]['breakdown_expense']) ? $expenses[$e]['breakdown_expense'] : '';
            $amt = isset($expenses[$e]['p_amount']) ? (float) $expenses[$e]['p_amount'] : 0;
            if (isset($expenses[$e])) {
                $totalexpenses += $amt;
            }

            PDF::SetX(20);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell($col1, 15, ' ' . $desc, 1, 'L', false, 0);
            PDF::MultiCell($col2, 15, $desc !== '' ? number_format($amt, $decimalcurr) : '', 1, 'R', false, 1);
        }
        $dataBottomY = PDF::GetY();

        // draw the petty cash amount as one merged cell spanning the data rows
        PDF::SetXY(20 + $col1 + $col2, $dataTopY);
        PDF::SetFont($fontbold, '', 16);
        PDF::MultiCell($col3, $dataBottomY - $dataTopY, number_format($pettycash, $decimalcurr), 1, 'C', false, 0, '', '', true, 0, false, true, 0, 'M');

        PDF::SetXY(20, $dataBottomY);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetFillColor(147, 197, 114); // pistachio background (RGB)
        PDF::MultiCell($col1, 20, 'TOTAL EXPENSES: ', 1, 'R', true, 0, '', '', true, 0, false, true, 0, 'M');
        PDF::MultiCell($col2, 20, number_format($totalexpenses, $decimalcurr), 1, 'R', true, 0, '', '', true, 0, false, true, 0, 'M');
        PDF::MultiCell($col3, 20,'  ' . 'TOTAL REMITTED CASH:' . ' ' . number_format($pettycash - $totalexpenses, $decimalcurr), 1, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');
        PDF::SetFillColor(255, 255, 255); // reset background (RGB)

        PDF::SetX(20);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(750, 20, '', 1, 'L', false, 1, '', '', true, 0, false, true, 0, 'M');

        PDF::SetX(20);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(375, 20, '  ' . 'ACCOUNTING:', 1, 'L', false, 0, '', '', true, 0, false, true, 0, 'M');
        PDF::MultiCell(375, 20, '  ' . 'LOGISTIC:', 1, 'L', false, 1, '', '', true, 0, false, true, 0, 'M');

        PDF::MultiCell(0, 0, "\n\n");

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        // PDF::MultiCell(253, 0, 'Checked By: ', '', 'L', false, 0);
        // PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        // PDF::MultiCell(0, 0, "\n");

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        // PDF::MultiCell(253, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
        // PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
} // end class