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

class gd
{

    private $modulename = "Debit Memo";
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
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'checked', 'print'];
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
          '' as received,
          '' as checked";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($filters)
    {
        $trno = md5($filters['params']['dataid']);
        $query = "select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, 
    head.yourref, left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,head.rem
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    where head.doc='" . $filters['params']['doc'] . "' and md5(head.trno)='$trno'
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref, left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,head.rem
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    where head.doc='" . $filters['params']['doc'] . "' and md5(head.trno)='$trno' order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "excel") {
            return $this->default_gd_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_GD_PDF($params, $data);
        }
    }
    public function default_header($config, $result)
    {

        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $str = '';
        $border = '1px solid';
        $font = 'Century Gothic';
        $fontsize = '12';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
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
        $str .= $this->reporter->col((isset($result[0]['docno']) ? $result[0]['docno'] : ''), '75', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER/SUPPLIER: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]['clientname']) ? $result[0]['clientname'] : ''), '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('DATE :', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]['dateid']) ? $result[0]['dateid'] : ''), '75', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]['address']) ? $result[0]['address'] : ''), '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('REF. :', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]['yourref']) ? $result[0]['yourref'] : ''), '75', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');

        return $str;
    }

    public function default_gd_layout($config, $result)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $prepared = $config['params']['dataparams']['prepared'];
        $received = $config['params']['dataparams']['received'];
        $approved = $config['params']['dataparams']['approved'];

        $str = '';
        $count = 35;
        $page = 35;
        $layoutsize =  '800';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config, $result);
        $str .= '<br/><br/>';


        $totaldb = 0;
        $totalcr = 0;
        for ($i = 0; $i < count($result); $i++) {
            $debit = number_format($result[$i]['db'], 2);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = number_format($result[$i]['cr'], 2);
            $credit = $credit < 0 ? '-' : $credit;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($result[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]['acnoname'], '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]['ref'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]['postdate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($result[$i]['client'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $totaldb = $totaldb + $result[$i]['db'];
            $totalcr = $totalcr + $result[$i]['cr'];
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($config, $result);
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
        $str .= $this->reporter->col('Prepared By : ', '265', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '270', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '265', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prepared, '265', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($approved, '270', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($received, '265', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    public function default_GD_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $companyid = $params['params']['companyid'];

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
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
        PDF::SetCellPaddings(4, 4, 4, 4);


        // write2DBarcode(code, type, x, y, width, height, style, align)

        PDF::SetFont($font, '', 9);


        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        $this->reportheader->getheader($params);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(465, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 0, "DOCNO #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(185, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '');

        // PDF::SetFont($fontbold, '', 4);
        // PDF::MultiCell(720, 7, '', '', 'L', false); //space muna bago mag next line

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(130, 0, "CUSTOMER/SUPPLIER: ", '', 'L', false, 0, '',  '');
        // PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(420, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : '').'  PAG MAHABA ANG LINE DAPAT DI ITO MAG OVERLAP THANK YOU VERY MUCHHIEEEEEE', 'B', 'L', false, 0, '',  '');
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 0, "DATE: ", '', 'L', false, 0, '',  '');
        // PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        // PDF::MultiCell(0, 0, "\n");




        $add = isset($data[0]['clientname']) ? $data[0]['clientname'] : '';
        $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
        $dates = date_format(date_create($datehere), 'M-d-Y');
        $maxChars = 50;
        $adds = strlen($add);
        $firstLine = '';
        $remaininglines = [];
        $addsz = '';

        if ($adds > $maxChars) {
            $firstLine = substr($add, 0, $maxChars);
            $remaining = substr($add, $maxChars);
            // Split remaining address into multiple lines without cutting words
            while (strlen($remaining) > $maxChars) {
                // Find the last space within the maxChars limit
                $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');
                // If there's no space, just cut at maxChars
                if ($spacePos === false) {
                    $nextLine = substr($remaining, 0, $maxChars);
                    $remaining = substr($remaining, $maxChars);
                } else {
                    $nextLine = substr($remaining, 0, $spacePos);
                    $remaining = substr($remaining, $spacePos + 1);
                }
                $remainingLines[] = $nextLine;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remaining) > 0) {
                $remainingLines[] = $remaining;
            }
        } else {
            $addsz = $add;
        }


        if ($adds > $maxChars) {

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(135, 0, "CUSTOMER/SUPPLIER: ", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(415, 0, $firstLine, 'B', 'L', false, 0, '',  '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, "DATE: ", '', 'R', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(120, 0, $dates, 'B', 'L', false, 1, '',  '');
            // PDF::SetFont($fontbold, '', 4);
            // PDF::MultiCell(720, 5, '', '', 'L', false); //space muna bago mag next line


            // Loop through remaining lines and print them
            foreach ($remainingLines as $line) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(135, 0, "", '', 'L', false, 0, '',  '');
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(415, 0, $line, 'B', 'L', false, 0, '',  '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(120, 0, '', '', 'L', false, 1, '',  '');
                // PDF::SetFont($fontbold, '', 4);
                // PDF::MultiCell(720, 5, '', '', 'L', false); //space muna bago mag next line
            }
        } else {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(135, 0, "CUSTOMER/SUPPLIER: ", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(415, 0, $addsz, 'B', 'L', false, 0, '',  '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, "DATE: ", '', 'R', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(120, 0, $dates, 'B', 'L', false, 1, '',  '');
            // PDF::SetFont($fontbold, '', 4);
            // PDF::MultiCell(720, 5, '', '', 'L', false); //space muna bago mag next line
        }



        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(60, 0, "ADDRESS: ", '', 'L', false, 0, '',  '');
        // PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(490, 0, (isset($data[0]['address']) ? $data[0]['address'] : '').' HI DAPAT HINDI DIN DITO MAG OVERLAP KAPAG MAHABA ANG KANYANG LAMN KASING DAMI NG 1000 CAHRACTER', 'B', 'L', false, 0, '',  '');
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 0, "REF: ", '', 'L', false, 0, '',  '');
        // PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');

        $addr = isset($data[0]['address']) ? $data[0]['address'] : '';
        $maxCharsz = 50;
        $addrs = strlen($addr);
        $fline = '';
        $remainingliness = [];
        $address = '';

        if ($addrs > $maxCharsz) {
            $fline = substr($addr, 0, $maxCharsz);
            $remaining = substr($addr, $maxCharsz);
            // Split remaining address into multiple lines without cutting words
            while (strlen($remaining) > $maxCharsz) {
                // Find the last space within the maxCharsz limit
                $spacePos = strrpos(substr($remaining, 0, $maxCharsz), ' ');
                // If there's no space, just cut at maxCharsz
                if ($spacePos === false) {
                    $nextLine = substr($remaining, 0, $maxCharsz);
                    $remaining = substr($remaining, $maxCharsz);
                } else {
                    $nextLine = substr($remaining, 0, $spacePos);
                    $remaining = substr($remaining, $spacePos + 1);
                }
                $remainingliness[] = $nextLine;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remaining) > 0) {
                $remainingliness[] = $remaining;
            }
        } else {
            $address = $addr;
        }


        if ($addrs > $maxChars) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(70, 0, "ADDRESS: ", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(480, 0, $fline, 'B', 'L', false, 0, '',  '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, "REF: ", '', 'R', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(120, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');
            // PDF::SetFont($fontbold, '', 4);
            // PDF::MultiCell(720, 5, '', '', 'L', false); //space muna bago mag next line

            // Loop through remaining lines and print them
            foreach ($remainingliness as $line) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(70, 0, "", '', 'L', false, 0, '',  '');
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(480, 0, $line, 'B', 'L', false, 0, '',  '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(120, 0, '', '', 'L', false, 1, '',  '');
                // PDF::SetFont($fontbold, '', 4);
                // PDF::MultiCell(720, 5, '', '', 'L', false); //space muna bago mag next line
            }
        } else {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(70, 0, "ADDRESS: ", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(480, 0, $address, 'B', 'L', false, 0, '',  '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, "REF: ", '', 'R', false, 0, '',  '');
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(120, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');
            // PDF::SetFont($fontbold, '', 4);
            // PDF::MultiCell(720, 5, '', '', 'L', false); //space muna bago mag next line
        }


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 0, "NOTES: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(650, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 1, '',  '');


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 0);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 10);
        PDF::MultiCell(110, 0, "ACCOUNT NO.", '', 'L', false, 0);
        PDF::MultiCell(185, 0, "ACCOUNT NAME", '', 'C', false, 0);
        PDF::MultiCell(105, 0, "REFERENCE #", '', 'L', false, 0);
        PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
        PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", '', 'R', false, 0);
        PDF::MultiCell(65, 0, "CLIENT", '', 'C', false);

        PDF::SetFont($font, '', 0);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function default_GD_PDF($params, $data)
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
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }
        $this->default_GD_header_PDF($params, $data);

        PDF::SetFont($font, '', 2);
        PDF::MultiCell(720, 0, '', '');
        PDF::SetCellPaddings(0, 0, 0, 0);

        $countarr = 0;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $acno = $data[$i]['acno'];
                $acnoname = $data[$i]['acnoname'];
                $ref = $data[$i]['ref'];
                $postdates = $data[$i]['postdate'];
                $postdate = date_format(date_create($postdates), 'm-d-y');
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $client = $data[$i]['client'];
                $debit = $debit < 0 ? '-' : $debit;
                $credit = $credit < 0 ? '-' : $credit;

                $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
                $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '33', 0);
                $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
                $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
                $arr_client = $this->reporter->fixcolumn([$client], '16', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(110, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(185, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(105, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(65, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
                }
                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];

                if (intVal($i) + 1 == $page) {
                    $this->default_GD_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(475, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(75, 0, '', '', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(670, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        // PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        // PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        // PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        // PDF::MultiCell(30, 0, ' ', '', 'L', false, 0);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(200, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, 'Checked By: ', '', 'L', false);
        // PDF::MultiCell(30, 0, ' ', '', 'L', false);


        $approved = $params['params']['dataparams']['approved'];
        $checked = $params['params']['dataparams']['checked'];
        $received = $params['params']['dataparams']['received'];
        $prep = $params['params']['dataparams']['prepared'];

        if ($approved == '') {
            $approvedby = 'MYJ / JTG';
        } else {
            $approvedby = $approved;
        }

        if ($checked == '') {
            $checkedby = 'JPL / JMN ';
        } else {
            $checkedby = $checked;
        }

        $trno = $params['params']['dataid'];
        $createname = $this->coreFunctions->datareader("select createby as value from
                   (select createby from lahead  where trno = $trno
                   union all
                   select createby from glhead  where trno = $trno) as cr");
        $create = $this->coreFunctions->datareader("select name as value from useraccess where username = '$createname'");
        if ($prep == '') {
            $preparedby = $create;
        } else {
            $preparedby = $prep;
        }
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, "\n");
        // PDF::MultiCell(30, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, $preparedby, 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, $checkedby, 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, $approvedby, 'B', 'C', false);
        // PDF::MultiCell(30, 0, ' ', '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 12);
        PDF::SetCellPaddings(0, 0, 0, 0);
        // PDF::MultiCell(30, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, ' ', '', 'C', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, 'Received By: ', '', 'L', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, '', '', 'C', false);
        // PDF::MultiCell(30, 0, ' ', '', 'L', false);

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::MultiCell(200, 0, '', '', 'C', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, $received, 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, '', '', 'C', false);
        // PDF::MultiCell(30, 0, ' ', '', 'L', false);

        // PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        // PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        // PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
