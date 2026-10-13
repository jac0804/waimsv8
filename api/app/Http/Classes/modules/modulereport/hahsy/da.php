<?php

namespace App\Http\Classes\modules\modulereport\hahsy;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class da
{

    private $modulename = "DAILY PIECE RATE REPORT PER TRANSACTION";
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
        $fields = ['radioprint', 'prepared', 'approved', 'received'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function reportparamsdata($config)
    {
        $paramstr = "select
                  'PDFM' as print,
                  '' as prepared,
                  '' as approved,
                  '' as received";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($config)
    {
        $center = $config['params']['center'];
        $trno = $config['params']['dataid'];

        // raw lines (draft at posted)
        $select = "select num.center, head.trno, head.docno as fulldocno, right(head.docno,4) as docno, head.type, head.rem, date(head.dateid) as dateid,
                    ifnull(warehouse.client,'') as hwh,
                    stock.line, stock.empid, ifnull(cl.client,'') as empcode, ifnull(cl.clientname,'') as empname,
                    stock.itemid, ifnull(it.barcode,'') as barcode, ifnull(it.itemname,'') as itemname, ifnull(it.uom,'') as uom,
                    ifnull(it.payqty,0) as payqty, ifnull(icat.name,'') as category,
                    ifnull(stock.amount,0) as amount, ifnull(stock.pcswork,0) as pcswork,
                    ifnull(stock.restday,0) as restday, ifnull(stock.night,0) as night, ifnull(stock.holiday,0) as holiday,
                    ifnull(stock.adjustmnt,0) as adjustmnt, ifnull(stock.thirteenmos,0) as thirteenmos, ifnull(stock.admin,0) as admin,
                    ifnull(stock.canteen,0) as canteen, ifnull(stock.cacanteen,0) as cacanteen,
                    ifnull(stock.cahahsy,0) as cahahsy, ifnull(stock.charge,0) as charge ";

        $join = " left join hrisnum as num on num.trno = head.trno
                  left join client as warehouse on warehouse.clientid = head.whid
                  left join client as cl on cl.clientid = stock.empid
                  left join item as it on it.itemid = stock.itemid
                  left join itemcategory as icat on icat.line = it.category ";

        $lines = $select . " from dahead as head left join piecetrans as stock on stock.trno = head.trno " . $join . "
                  where head.trno = '$trno' and num.center = '$center'
                  union all
                  " . $select . " from hdahead as head left join hpiecetrans as stock on stock.trno = head.trno " . $join . "
                  where head.trno = '$trno' and num.center = '$center'";

        // pagsamahin: same empid + same itemid = isang row (kahit magkaiba ang wh)
        $query = "select x.center, x.trno, x.fulldocno, x.docno, x.type, x.rem, x.dateid, x.hwh,
                    min(x.line) as line, x.empid, x.empcode, x.empname,
                    x.itemid, x.barcode, x.itemname, x.uom, x.category,
                    round(sum(x.amount),2) as amt,
                    round(sum(x.pcswork),2) as baseamt,
                    round(if(x.payqty > 0 and sum(x.pcswork) > 0, sum(x.pcswork) / x.payqty, 0), 2) as totaltp,
                    round(sum(x.amount),2) as gprofit,
                    round(sum(x.restday),2) as days,
                    round(sum(x.night),2) as dayn,
                    round(sum(x.holiday),2) as adays,
                    round(sum(x.adjustmnt),2) as adjust,
                    round(sum(x.thirteenmos),2) as payment,
                    round(sum(x.admin),2) as fname,
                    round(sum(x.canteen),2) as foramt,
                    round(sum(x.cacanteen),2) as expensename,
                    round(sum(x.cahahsy),2) as budget,
                    round(sum(x.charge),2) as actual,
                    round(sum((x.restday + x.night + x.holiday + x.adjustmnt + x.thirteenmos + x.admin)
                            - (x.canteen + x.cacanteen + x.cahahsy + x.charge)),2) as paygroup
                  from (" . $lines . ") as x
                  group by x.center, x.trno, x.fulldocno, x.docno, x.type, x.rem, x.dateid, x.hwh,
                           x.empid, x.empcode, x.empname,
                           x.itemid, x.barcode, x.itemname, x.uom, x.category, x.payqty
                  order by x.empname, line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {
        return $this->default_DA_PDF($params, $data);
    }

    // 0 = '-'
    private function fmt($val, $dec)
    {
        return floatval($val) == 0 ? '-' : number_format($val, $dec);
    }

    public function default_DA_header_PDF($params, $data)
    {
        $center = $params['params']['center'];

        $qry = "select name,address,tel,email from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        // AJ na galing sa DA na ito (refx = DA trno)
        $trno = $params['params']['dataid'];
        $ajdocno = $this->coreFunctions->datareader(
            "select h.docno as value from glhead as h left join glstock as s on s.trno = h.trno where h.doc = 'AJ' and s.refx = ?
             union all
             select h.docno as value from lahead as h left join lastock as s on s.trno = h.trno where h.doc = 'AJ' and s.refx = ?
             limit 1",
            [$trno, $trno]
        );

        // APV na galing sa DA na ito (yourref = DA docno)
        $dadocno = isset($data[0]['fulldocno']) ? $data[0]['fulldocno'] : '';
        $gjdocno = $this->coreFunctions->datareader(
            "select docno as value from glhead where doc = 'PV' and yourref = ?
             union all
             select docno as value from lahead where doc = 'PV' and yourref = ?
             limit 1",
            [$dadocno, $dadocno]
        );

        $font = "";
        $fontbold = "";
        $fontsize = 10;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('l', [1200, 900]);
        PDF::SetMargins(20, 20);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(960, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0);
        // page number  PageNo() = page ngayon, getAliasNbPages() = total pages
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(200, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(1160, 0, $this->modulename, '', 'L', false);
        PDF::MultiCell(0, 0, "\n");

        // Trans No / Trans Date / Type / Warehouse / AJ / GJ  (total width = 1160)
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 0, "Trans. No :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(75, 0, "Trans. Date :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 0, (isset($data[0]['dateid']) ? date('m-d-Y', strtotime($data[0]['dateid'])) : ''), 'B', 'L', false, 0);
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(40, 0, "Type :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, (isset($data[0]['type']) ? $data[0]['type'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(75, 0, "Warehouse :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(125, 0, (isset($data[0]['hwh']) ? $data[0]['hwh'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(85, 0, "AJ Document :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 0, $ajdocno, 'B', 'L', false, 0);
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(85, 0, "GJ Document :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 0, $gjdocno, 'B', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        // column header
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(75, 28, "Category", 'TB', 'L', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(70, 28, "ItemCode", 'TB', 'L', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(160, 28, "Item Name", 'TB', 'L', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(55, 28, "Amount", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(55, 28, "Qty Pcs", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(35, 28, "Uom", 'TB', 'C', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(45, 28, "Qty Box", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(65, 28, "Gross Rate", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        // Add Restday hanggang Less Charge
        PDF::MultiCell(52, 28, "Add\nRestday", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Add\nNight", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Add\nHoliday", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Add\nAdjustment", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Add\n13th Mos", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Add\nAdmin", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Less\nCanteen", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Less\nCA Canteen", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Less\nCA Hahsy", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(52, 28, "Less\nCharge", 'TB', 'R', false, 0, '', '', true, 0, false, true, 28, 'M');
        PDF::MultiCell(80, 28, "Net Payroll", 'TB', 'R', false, 1, '', '', true, 0, false, true, 28, 'M');
    }

    public function default_DA_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $fontsize = 9;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->default_DA_header_PDF($params, $data);

        $totalpay = 0;
        $lastemp = '';

        for ($i = 0; $i < count($data); $i++) {

            if (empty($data[$i]['line'])) {
                continue;
            }

            // I-print ang employee code + name kapag NAGPALIT ng employee.
            // $lastemp = empid ng huling employee na na-print.
            // Kung iba ang empid ng row ngayon sa $lastemp, bagong employee ito -> print ang pangalan.
            // Kung pareho, same employee pa rin -> item lang ang ipi-print (hindi na uulitin ang pangalan).
            if ($lastemp != $data[$i]['empid']) {
                PDF::MultiCell(0, 5, '', '', 'L', false, 1);
                PDF::SetFont($fontbold, 'U', $fontsize);
                $empnum = substr($data[$i]['empcode'], 2); // tanggalin ang "EM"  -> 0000001000116
                $empnum = intval($empnum);                 // tanggalin ang zero sa unahan -> 1000116
                $empcode = 'E' . $empnum;                  // lagyan ng "E" -> E1000116
                PDF::MultiCell(75, 0, $empcode, '', 'L', false, 0);
                PDF::MultiCell(1085, 0, $data[$i]['empname'], '', 'L', false, 1);

                // Tandaan ang employee na kaka-print lang,
                // para sa susunod na row malalaman kung same employee pa o bago na.
                $lastemp = $data[$i]['empid'];
            }


            $arr_category = $this->reporter->fixcolumn([$data[$i]['category']], '14', 0);
            $arr_itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '32', 0);
            $maxrow = $this->othersClass->getmaxcolumn([$arr_category, $arr_itemname]);

            PDF::SetFont($font, '', $fontsize);
            for ($r = 0; $r < $maxrow; $r++) {

                $first = ($r == 0);
                PDF::MultiCell(75, 14, (isset($arr_category[$r]) ? $arr_category[$r] : ''), '', 'L', false, 0);
                PDF::MultiCell(70, 14, ($first ? $data[$i]['barcode'] : ''), '', 'L', false, 0);
                PDF::MultiCell(160, 14, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
                PDF::MultiCell(55, 14, ($first ? $this->fmt($data[$i]['amt'], 2) : ''), '', 'R', false, 0);
                PDF::MultiCell(55, 14, ($first ? $this->fmt($data[$i]['baseamt'], 0) : ''), '', 'R', false, 0);
                PDF::MultiCell(35, 14, ($first ? $data[$i]['uom'] : ''), '', 'C', false, 0);
                PDF::MultiCell(45, 14, ($first ? $this->fmt($data[$i]['totaltp'], 0) : ''), '', 'R', false, 0);
                PDF::MultiCell(65, 14, ($first ? $this->fmt($data[$i]['gprofit'], 4) : ''), '', 'R', false, 0);
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['days'], 2) : ''), '', 'R', false, 0);        // Add Restday
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['dayn'], 2) : ''), '', 'R', false, 0);        // Add Night
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['adays'], 2) : ''), '', 'R', false, 0);       // Add Holiday
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['adjust'], 2) : ''), '', 'R', false, 0);      // Add Adjustment
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['payment'], 2) : ''), '', 'R', false, 0);     // Add 13th Mos
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['fname'], 2) : ''), '', 'R', false, 0);       // Add Admin
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['foramt'], 2) : ''), '', 'R', false, 0);      // Less Canteen
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['expensename'], 2) : ''), '', 'R', false, 0); // Less CA Canteen
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['budget'], 2) : ''), '', 'R', false, 0);      // Less CA Hahsy
                PDF::MultiCell(52, 14, ($first ? $this->fmt($data[$i]['actual'], 2) : ''), '', 'R', false, 0);      // Less Charge
                PDF::MultiCell(80, 14, ($first ? $this->fmt($data[$i]['paygroup'], 4) : ''), '', 'R', false, 1);    // Net Payroll
            }

            $totalpay += $data[$i]['paygroup'];

            if (PDF::getY() >= 820) {
                $this->default_DA_header_PDF($params, $data);
                $lastemp = ''; // ulitin ang employee name sa bagong page
            }
        }

        // total
        if (PDF::getY() >= 830) {
            $this->default_DA_header_PDF($params, $data);
        }
        PDF::MultiCell(0, 5, '', '', 'L', false, 1);
        PDF::SetLineStyle(['width' => 1.5]);
        PDF::MultiCell(1160, 0, '', 'T', 'L', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(880, 0, '', '', 'L', false, 0);
        PDF::MultiCell(195, 0, 'TOTAL PAYROLL :', 'B', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalpay, 2), 'B', 'R', false, 1);
        PDF::SetLineStyle(['width' => 0.5]);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
