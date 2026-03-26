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

class ka
{

    private $modulename = "AR Audit";
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
        $fields = ['radioprint', 'prepared', 'approved', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $signatories = $this->othersClass->getSignatories($config);
        $prepared = '';
        $approved = '';
        foreach ($signatories as $key => $value) {
            switch ($value->fieldname) {
                case 'prepared':
                    $prepared = $value->fieldvalue;
                    break;
                case 'approved':
                    $approved = $value->fieldvalue;
                    break;
            }
        }
        return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '" . $prepared . "' as approved,
      '" . $approved . "' as prepared
    ");
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];
        $query = "select head.trno,head.docno,date(head.dateid) as dateid
from kahead as head where head.doc = 'KA' and head.trno = '" . $trno . "'
group by head.docno,head.dateid,head.trno,head.doc
union all
select head.trno,head.docno,date(head.dateid) as dateid
from hkahead as head where head.doc = 'KA' and head.trno = '" . $trno . "'
group by head.docno,head.dateid,head.trno,head.doc";


        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {

        return $this->default_AR_audit_PDF($params, $data);
    }
    public function default_AR_header_PDF($params, $data)
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
        PDF::MultiCell(540, 0, strtoupper($this->modulename), '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '');
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(540, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Date : ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '',  '');

        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($fontbold, '', 11);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(110, 0, "DOC NO", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(90, 0, "OURREF", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "CUST CODE", '', 'L', false, 0);
        PDF::MultiCell(110, 0, "CUST NAME", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "NOTES", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "AMOUNT", '', 'R', false, 1);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }
    public function default_AR_audit_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];

        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_AR_header_PDF($params, $data);

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(705, 0, '', '');
        $totalamount = 0;
        for ($i = 0; $i < count($data); $i++) {


            $query = "select head.docno,sum(ledger.db - ledger.cr) as amount,date(head.dateid) as dateid,
                        cl.client,head.rem,head.ourref,coa.acno as cstcode,coa.acnoname as cstname
                        from arledger as ledger
                        left join coa on coa.acnoid = ledger.acnoid
                        left join glhead as head on head.trno = ledger.trno
                        left join gldetail as detail on detail.trno = ledger.trno and detail.line = ledger.line
                        left join client as cl on cl.clientid = ledger.clientid
                        where head.doc in ('SJ','CM','SR') and ledger.ka = '" . $data[$i]['trno'] . "'
                        group by head.docno,ledger.db,ledger.cr ,cl.client,head.rem,head.ourref,coa.acno,coa.acnoname,head.dateid";
            $data2 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

            for ($j = 0; $j < count($data2); $j++) {
                $maxrow = 1;
                $docno =  $data2[$j]['docno'];
                $dateid =  $data2[$j]['dateid'];
                $ourref = $data2[$j]['ourref'];
                $amount = $data2[$j]['amount'];
                $cstname = $data2[$j]['cstname'];
                $cstcode = $data2[$j]['cstcode'];
                $rem = $data2[$j]['rem'];

                $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
                $arr_ourref = $this->reporter->fixcolumn([$ourref], '28', 0);
                $arr_amount = $this->reporter->fixcolumn([number_format($amount, 2)], '15', 0);
                $arr_docno = $this->reporter->fixcolumn([$docno], '15', 0);
                $arr_code = $this->reporter->fixcolumn([$cstcode], '15', 0);
                $arr_name = $this->reporter->fixcolumn([$cstname], '15', 0);
                $arr_rem = $this->reporter->fixcolumn([$rem], '18', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_amount, $arr_ourref, $arr_docno, $arr_code, $arr_name, $arr_rem]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(110, 15, ' ' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(90, 15, ' ' . (isset($arr_ourref[$r]) ? $arr_ourref[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_code[$r]) ? $arr_code[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(110, 15, ' ' . (isset($arr_name[$r]) ? $arr_name[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(120, 15, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(120, 15, ' ' . (isset($arr_amount[$r]) ? $arr_amount[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
                $totalamount += $data2[$j]['amount'];
            }


            if (PDF::getY() > 900) {
                $this->default_AR_header_PDF($params, $data);
            }
        }


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(705, 0, '', '');


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(640, 0, 'TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(80, 0, number_format($totalamount, 2), '', 'R', false, 0);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(240, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, '', '', 'L');
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
