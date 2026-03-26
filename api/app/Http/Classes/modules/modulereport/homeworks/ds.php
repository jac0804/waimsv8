<?php

namespace App\Http\Classes\modules\modulereport\homeworks;

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

class ds
{

    private $modulename = "Deposit Slip";
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
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'EXCEL', 'value' => 'excel', 'color' => 'red']
        ]);
        return array('col1' => $col1);
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

    public function report_default_query($filters)
    {
        $trno = md5($filters['params']['dataid']);
        $query = "
    select head.trno, date(head.dateid) as dateid, head.docno, 
    coa.acnoname as clientname, coa.acno,
    coa.acnoname, client.client, detail.ref,
    date(detail.postdate) as postdate, detail.db, 
    detail.cr, detail.line,coa.alias,hc.acnoname as contraname,head.rem
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    left join coa as hc on hc.acno = head.contra
    
    where head.doc='ds' and md5(head.trno)='$trno'
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, 
    hcoa.acnoname as clientname, coa.acno,
    coa.acnoname, client.client, detail.ref,
     date(detail.postdate) as postdate, detail.db, 
     detail.cr, detail.line,coa.alias,hc.acnoname as contraname,head.rem
    from (((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa as hcoa on hcoa.acno=head.contra)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    left join coa as hc on hc.acno = head.contra
    
    where head.doc='ds' and md5(head.trno)='$trno' order by line";

        $result = $this->coreFunctions->opentable($query);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "excel") {
            return $this->default_ds_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_DS_PDF($params, $data);
        }
    }

    public function rpt_ds_header_default($config, $result)
    {
        $str = '';
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $border = '1px solid';
        $font = 'Century Gothic';
        $fontsize = '12';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'L', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '75', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BANK NAME: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]->contraname) ? $result[0]->contraname : ''), '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('DATE: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '75', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REFERENCE #', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        return $str;
    }

    public function default_ds_layout($config, $result)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $prepared = $config['params']['dataparams']['prepared'];
        $received = $config['params']['dataparams']['received'];
        $approved = $config['params']['dataparams']['approved'];

        $str = '';
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();

        $str .= $this->rpt_ds_header_default($config, $result);

        $totaldb = 0;
        $totalcr = 0;
        for ($i = 0; $i < count($result); $i++) {
            $debit = number_format($result[$i]->db, 2);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = number_format($result[$i]->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($result[$i]->acno, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]->acnoname, '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]->ref, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]->postdate, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]->client, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $totaldb = $totaldb + $result[$i]->db;
            $totalcr = $totalcr + $result[$i]->cr;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->rpt_ds_header_default($config, $result);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '350', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('GRAND TOTAL :', '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prepared, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($approved, '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function default_DS_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name, code, address, tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 10;
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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
        PDF::MultiCell(0, 0, $reporttimestamp . ' Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'L');
        $this->reportheader->getheader($params);

        PDF::SetFont($fontbold, '', 13);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(720, 0, $this->modulename, '', 'L', false, 1, '',  '');


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(520, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "Docno #:", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(140, 0, (isset($data[0]->docno) ? $data[0]->docno : ''), 'B', 'L', false, 1, '',  '');


        // PDF::MultiCell(720, 0, '', '', 'L', false, 1, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Bank Name: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(350, 0, $data[0]->contraname, 'B', 'L', false, 0, '', '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(140, 0, (isset($data[0]->dateid) ? $data[0]->dateid : ''), 'B', 'L', false, 1, '',  '');

        // PDF::MultiCell(720, 2, "", '', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Remarks: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(350, 0, (isset($data[0]->rem) ? $data[0]->rem : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(140, 0, '', '', 'L', false, 1, '',  '');




        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, " ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '',  '');


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 10);
        PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
        PDF::MultiCell(150, 0, "ACCOUNT NAME", '', 'C', false, 0);
        PDF::MultiCell(145, 0, "REFERENCE #", '', 'L', false, 0);
        PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
        PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", '', 'R', false, 0);
        PDF::MultiCell(80, 0, "CLIENT", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function default_DS_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_DS_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {


                $maxrow = 1;
                $acno = $data[$i]->acno;
                $acnoname = $data[$i]->acnoname;
                $ref = $data[$i]->ref;
                $postdate = $data[$i]->postdate;
                $debit = number_format($data[$i]->db, $decimalcurr);
                $credit = number_format($data[$i]->cr, $decimalcurr);
                $client = $data[$i]->client;
                $debit = $debit <= 0 ? '-' : $debit;
                $credit = $credit <= 0 ? '-' : $credit;


                $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
                $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '35', 0);
                $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
                $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '16', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '16', 0);
                $arr_client = $this->reporter->fixcolumn([$client], '16', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(90, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(150, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(145, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(80, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''),  '', 'L', false, 1, '', '', false, 1);
                }


                $totaldb += $data[$i]->db;
                $totalcr += $data[$i]->cr;

                if (intVal($i) + 1 == $page) {
                    $this->default_DS_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(460, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(90, 0, '', '', 'R', false, 1);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');
        PDF::MultiCell(0, 0, "\n\n");


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");
        $trno = $params['params']['dataid'];
        $createby = $this->coreFunctions->datareader("
        select createby as value from lahead where trno = $trno
        union all
        select createby as value from glhead where trno = $trno");
        $createname = $this->coreFunctions->datareader("select name as value from useraccess where username = '$createby'");

        PDF::MultiCell(253, 0, $createname, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
