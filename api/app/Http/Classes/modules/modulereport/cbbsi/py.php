<?php

namespace App\Http\Classes\modules\modulereport\cbbsi;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class py
{

    private $modulename = "Supplier Payment Listing";
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
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $signatories = $this->othersClass->getSignatories($config);
        $prepared = '';
        $approved = '';
        $received =  '';
        foreach ($signatories as $key => $value) {
            switch ($value->fieldname) {
                case 'prepared':
                    $prepared = $value->fieldvalue;
                    break;
                case 'approved':
                    $approved = $value->fieldvalue;
                    break;
                case 'received':
                    $received = $value->fieldvalue;
                    break;
            }
        }
        return $this->coreFunctions->opentable(
            "select 
      'PDFM' as print,
      '" . $prepared . "' as prepared,
      '" . $approved . "' as approved,
      '" . $received . "' as received
      "
        );
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];
        $query = "
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno,head.docno as doc, head.clientname, head.address, head.yourref, head.ourref,ap.docno as ref,
    coa.acno, coa.acnoname, ap.db, ap.cr, head2.due,detail.rem as notes,date(ap.dateid) as apdate
    from pyhead as head 
    left join apledger as ap on ap.py=head.trno
    left join coa on coa.acnoid=ap.acnoid
    left join glhead as head2 on head2.trno = ap.trno 
    left join gldetail as detail on detail.trno = ap.trno and detail.line = ap.line
    left join client on client.client = head.client
    where head.trno='$trno'
    union all
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno,head.docno as doc, head.clientname, head.address, head.yourref, head.ourref,ap.docno as ref,
    coa.acno, coa.acnoname, ap.db, ap.cr, head2.due,detail.rem as notes,date(ap.dateid) as apdate
    from hpyhead as head 
    left join apledger as ap on ap.py=head.trno
    left join coa on coa.acnoid=ap.acnoid
    left join glhead as head2 on head2.trno = ap.trno  
    left join gldetail as detail on detail.trno = ap.trno and detail.line = ap.line
    left join client on client.client = head.client
    where head.trno='$trno' order by due,dateid, docno";


        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        return $this->default_PY_PDF($params, $data);
    }

    public function default_PY_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];


        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '',  '');

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', $fontsize);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 10);

        PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(90, 0, "DOC NO", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "REFERENCE #", '', 'C', false, 0);
        PDF::MultiCell(65, 0, "DUE DATE", '', 'C', false, 0);

        PDF::MultiCell(90, 0, "NOTES", '', 'C', false, 0);
        PDF::MultiCell(70, 0, "DEBIT", '', 'R', false, 0);
        PDF::MultiCell(70, 0, "CREDIT", '', 'R', false, 0);

        PDF::MultiCell(75, 0, "APPROVED BY", '', 'C', false, 0);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(75, 0, "CHECK DETAILS", '', 'R', false, 1);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function default_PY_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $trno = $params['params']['dataid'];
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_PY_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        $countarr = 0;
        $totaldb = 0;
        $totalcr = 0;
        $totalamount = 0;

        $current = 0;
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;

        $totcur = 0;
        $tota = 0;
        $totb = 0;
        $totc = 0;
        $totd = 0;
        $tote = 0;
        $gt = 0;

        if (!empty($data)) {

            for ($i = 0; $i < count($data); $i++) {


                $maxrow = 1;
                $yourref = $data[$i]['ref'];
                $due = $data[$i]['due'];
                $dateid = $data[$i]['apdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $debit = $debit < 0 ? '-' : $debit;
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $credit = $credit < 0 ? '-' : $credit;
                $doc = $data[$i]['doc'];
                $notes = $data[$i]['notes'];

                $arr_yourref = $this->reporter->fixcolumn([$yourref], '28', 0);
                $arr_due = $this->reporter->fixcolumn([$due], '13', 0);
                $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);
                $arr_doc = $this->reporter->fixcolumn([$doc], '15', 0);
                $arr_notes = $this->reporter->fixcolumn([$notes], '14', 0);
                $maxrow = $this->othersClass->getmaxcolumn([$arr_yourref, $arr_due, $arr_dateid, $arr_debit, $arr_credit, $arr_doc, $arr_notes]);

                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);

                    PDF::MultiCell(75, 15, ' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(90, 15, ' ' . (isset($arr_doc[$r]) ? $arr_doc[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_yourref[$r]) ? $arr_yourref[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(65, 15, ' ' . (isset($arr_due[$r]) ? $arr_due[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(90, 15, ' ' . (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(10, 15, ' ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ', 'B', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];
                $totalamount = $totaldb + $totalcr;


                if (PDF::getY() > 900) {
                    $this->default_PY_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(400, 0, 'TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(20, 0, '', '', 'R', false, 0);
        PDF::MultiCell(70, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(70, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(70, 0, number_format($totalamount, $decimalprice), '', 'R', false, 0);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 0, "CURRENT", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "30 DAYS", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "60 DAYS", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "90 DAYS", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "120 DAYS", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "OVER 120 DAYS", '', 'L', false, 1);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        $qry = "select datediff(now(), ap.dateid) as elapse,(case when ap.db>0 then (ap.bal*-1) else ap.bal end) as balance
                from pyhead as head
                left join apledger as ap on ap.py=head.trno
                where head.trno=?
                 union all
                select datediff(now(), ap.dateid) as elapse,(case when ap.db>0 then (ap.bal*-1) else ap.bal end) as balance
                from hpyhead as head
                left join apledger as ap on ap.py=head.trno
                where head.trno=?";

        $data2 =  json_decode(json_encode($this->coreFunctions->opentable($qry, [$trno, $trno])), true);
        for ($j = 0; $j < count($data2); $j++) {
            if ($data2[$j]['elapse'] >= 0 && $data2[$j]['elapse'] < 30) {
                $current = 0;
                $a = 0;
                $b = 0;
                $c = 0;
                $d = 0;
                $e = 0;
                $current = $data2[$j]['balance']; //current
            }
            if ($data2[$j]['elapse'] >= 30 && $data2[$j]['elapse'] < 30) { // 30days
                $current = 0;
                $a = 0;
                $b = 0;
                $c = 0;
                $d = 0;
                $e = 0;
                $a = $data2[$j]['balance'];
            }
            if ($data2[$j]['elapse'] >= 31 && $data2[$j]['elapse'] <= 60) {  // 60days
                $current = 0;
                $a = 0;
                $b = 0;
                $c = 0;
                $d = 0;
                $e = 0;
                $b = $data2[$j]['balance'];
            }
            if ($data2[$j]['elapse'] >= 61 && $data2[$j]['elapse'] <= 90) {  // 90days
                $current = 0;
                $a = 0;
                $b = 0;
                $c = 0;
                $d = 0;
                $e = 0;
                $c = $data2[$j]['balance'];
            }
            if ($data2[$j]['elapse'] >= 91 && $data2[$j]['elapse'] <= 120) {  // 120days
                $current = 0;
                $a = 0;
                $b = 0;
                $c = 0;
                $d = 0;
                $e = 0;
                $d = $data2[$j]['balance'];
            }
            if ($data2[$j]['elapse'] > 120) {  // over 120da
                $current = 0;
                $a = 0;
                $b = 0;
                $c = 0;
                $d = 0;
                $e = 0;
                $e = $data2[$j]['balance'];
            }
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(720, 0, '', '');

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(120, 15, ' ' . ($current > 0 ? number_format($current, 2) : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(120, 15, ' ' . ($a > 0 ? number_format($a, 2) : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(120, 15, ' ' . ($b > 0 ? number_format($b, 2) : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(120, 15, ' ' . ($c > 0 ? number_format($c, 2) : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(120, 15, ' ' . ($d > 0 ? number_format($d, 2) : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(120, 15, ' ' . ($e > 0 ? number_format($e, 2) : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            $totcur += $current;
            $tota += $a;
            $totb += $b;
            $totc += $c;
            $totd += $d;
            $tote += $e;
            // $gt += $data2[$j]['balance'];

        }
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(120, 15, ' ' . number_format($totcur, 2), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 15, ' ' . number_format($tota, 2), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 15, ' ' . number_format($totb, 2), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 15, ' ' . number_format($totc, 2), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 15, ' ' . number_format($totd, 2), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 15, ' ' . number_format($tote, 2), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(0, 0, "\n\n");

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
