<?php

namespace App\Http\Classes\modules\reportlist\accounting_books;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;


class accounts_payable_voucher_journal
{
    public $modulename = 'Accounts Payable Voucher Journal';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    private $pdffont = null;
    private $pdffontbold = null;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

    public function __construct($params = null)
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass($params);
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        $fields = ['start', 'end', 'dcentername'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'start.label', 'StartDate');
        data_set($col2, 'start.readonly', false);
        data_set($col2, 'end.label', 'EndDate');
        data_set($col2, 'end.readonly', false);

        $fields = ['radioposttype', 'radioreporttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set(
            $col3,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );

        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 'PDFM' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as center,
        '' as centername,
        '' as dcentername,
        '0' as reporttype,'0' as posttype,'' as contra,'' as acnoname,'' as dacnoname, '' as dclientname, '' as client, '' as clientname");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function default_query($filters)
    {
        $filter = "";
        $center = $filters['params']['dataparams']['center'];
        $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
        $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
        $posttype = $filters['params']['dataparams']['posttype'];
        $reporttype = $filters['params']['dataparams']['reporttype'];
        $client = $filters['params']['dataparams']['client'];
        $acno = $filters['params']['dataparams']['contra'];

        if ($acno == "") {
            $acno = "ALL";
        }

        if ($acno != "ALL") {
            $filter .= " and coa.acno='\\" . $acno . "' ";
        }

        if ($center != "") {
            $filter .= " and cntnum.center = '" . $center . "' ";
        }

        if ($client != "") {
            $filter .= " and client.client = '" . $client . "' ";
        }

        $condition = " cntnum.doc = 'PV' ";

        switch ($posttype) {

            case 0: //posted
                if ($reporttype == 1) { //detailed
                    $query = "select 'p' as tr, 'cr' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, detail.ref, detail.db as debit, detail.cr as credit, date(cntnum.postdate) as postdate
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    order by docno,credit";
                } else { //summarized
                    $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                    select 'p' as tr, 'cr' as bk,coa.acno, coa.acnoname as description, detail.db as debit, detail.cr as credit
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . ") as x 
                    where description is not null
                    group by acno, description
                    order by credit";
                }
                break;

            case 1: //unposted
                if ($reporttype == 1) { //detailed
                    $query = "select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, cntnum.center, sum(detail.db) as debit,
                    detail.ref, sum(detail.cr) as credit, date(cntnum.postdate) as postdate
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    group by head.docno, head.rem, head.clientname,head.dateid,
                    coa.acno, coa.acnoname, cntnum.center,detail.ref, cntnum.postdate
                    order by docno,credit";
                } else { //summarized
                    $query = "select acno, description, sum(debit) as debit, sum(credit) as credit from (
                    select 'u' as tr, 'cd' as bk,coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . "
                    group by coa.acno, coa.acnoname, cntnum.center
                    ) as x where description is not null group by acno, description order by credit";
                }
                break;

            case 2: //all
                if ($reporttype == 1) { //detailed posted and unposted
                    $query = " select   tr,  bk, docno, rem, clientname,  dateid,acno, description, ref,  debit, credit, postdate
                    from (
                    select 'p' as tr, 'cr' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, cntnum.center,  sum(detail.db) as debit,
                    detail.ref, sum(detail.cr) as credit, date(cntnum.postdate) as postdate
                    from ((glhead as head
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where  $condition
                    and date(head.dateid)  between '" . $startdate . "' and '" . $enddate . "'  $filter
                    group by head.docno, head.rem, head.clientname,head.dateid,
                    coa.acno, coa.acnoname, cntnum.center,detail.ref, cntnum.postdate

                    union all
                    select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.clientname, date(head.dateid) as dateid,
                    coa.acno, coa.acnoname as description, cntnum.center, sum(detail.db) as debit,
                    detail.ref, sum(detail.cr) as credit, date(cntnum.postdate) as postdate

                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "'  $filter
                    group by head.docno, head.rem, head.clientname,head.dateid,
                    coa.acno, coa.acnoname, cntnum.center,detail.ref, cntnum.postdate) as x
                    order by docno,credit ";
                } else { //summarized unposted and posted
                    $query = "select acno, description, sum(debit) as debit, sum(credit) as credit

                from (
                    select 'p' as tr, 'cr' as bk,coa.acno, coa.acnoname as description, detail.db as debit, detail.cr as credit
                    from ((glhead as head
                    left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    where $condition
                    and date(head.dateid)  between '" . $startdate . "' and '" . $enddate . "'  $filter

                    union all

                    select 'u' as tr, 'cd' as bk,coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
                    from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                    left join cntnum on cntnum.trno=head.trno)left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.client=head.client
                    where $condition
                    and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "'  $filter
                    group by coa.acno, coa.acnoname, cntnum.center
                    ) as x where description is not null group by acno, description order by credit";
                }
                break;
        }

        $data = $this->coreFunctions->opentable($query);
        return $data;
    }

    public function reportplotting($config)
    {
        $result = $this->default_query($config);
        $print = isset($config['params']['dataparams']['print']) ? $config['params']['dataparams']['print'] : '';

        if ($print == 'PDFM') {
            if ($config['params']['dataparams']['reporttype'] == 1) {
                return $this->DEFAULT_JOURNAL_VOUCHER_DETAILED_PDF($result, $config);
            }
            return $this->DEFAULT_JOURNAL_VOUCHER_SUMMARIZED_PDF($result, $config);
        }

        if ($config['params']['dataparams']['reporttype'] == 1) {
            return $this->DEFAULT_JOURNAL_VOUCHER_DETAILED($result, $config);
        }
        return $this->DEFAULT_JOURNAL_VOUCHER_SUMMARIZED($result, $config);
    }


    private function getPdfFonts()
    {
        if ($this->pdffont === null) {
            $font = 'helvetica';
            $fontbold = 'helvetica';
            if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            }
            $this->pdffont = $font;
            $this->pdffontbold = $fontbold;
        }
        return array($this->pdffont, $this->pdffontbold);
    }

    private function getPostTypeLabel($posttype)
    {
        switch ($posttype) {
            case 0:
                return 'Posted';
            case 1:
                return 'Unposted';
            default:
                return 'All';
        }
    }

    private function pdfAmount($value, $decimal)
    {
        if (round((float)$value, $decimal) == 0) {
            return '-';
        }
        return number_format($value, $decimal);
    }

    private function pdfWrap($text, $chars)
    {
        $lines = $this->reporter->fixcolumn(array((string)$text), $chars, 0);
        if (empty($lines)) {
            return array('');
        }
        return $lines;
    }

    private function pdfDashedLine($x1, $x2)
    {
        $y = PDF::GetY() + 3;
        PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => '2,2', 'color' => array(0, 0, 0)));
        PDF::Line($x1, $y, $x2, $y);
        PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        PDF::SetY($y + 5);
    }

    //detailed
    private function DETAILED_HEADER_PDF($params)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        list($font, $fontbold) = $this->getPdfFonts();

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $startdate = $params['params']['dataparams']['start'];
        $enddate = $params['params']['dataparams']['end'];
        $acno = $params['params']['dataparams']['contra'];
        $acnoname = $params['params']['dataparams']['acnoname'];
        $posttype = $params['params']['dataparams']['posttype'];

        if ($acnoname == "") {
            $acnoname = "ALL";
        } else {
            $acnoname = $acno . ' - ' . $acnoname;
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(40, 0);
        PDF::SetAutoPageBreak(false, 0);
        PDF::AddPage('p', array(800, 1000));

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        if (!empty($headerdata)) {
            PDF::SetFont($fontbold, '', 14);
            PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
            PDF::SetFont($font, '', 11);
            PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n", '', 'C');
        }

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(720, 30, '', '', 'L');

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(720, 0, 'DETAILED ACCOUNTS PAYABLE VOUCHER', '', 'L');

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(720, 0, date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), '', 'L');
        PDF::MultiCell(720, 0, 'Account: ' . strtoupper($acnoname), '', 'L');
        PDF::MultiCell(240, 0, 'Transaction: ' . strtoupper($this->getPostTypeLabel($posttype)), '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Center: ' . $center, '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Page ' . PDF::PageNo(), '', 'R');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(112, 30, 'DOCUMENT #', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(146, 30, 'PAYEE NAME PARTICULARS', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 30, 'DATE', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(48, 30, 'CODE', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(110, 30, 'ACCOUNT DESCRIPTION', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(64, 30, 'REF #', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 30, 'DEBIT', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 30, 'CREDIT', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    private function DETAILED_SUBTOTAL_PDF($docdb, $doccr, $decimal)
    {
        list($font, $fontbold) = $this->getPdfFonts();

        $y = PDF::GetY();
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(550, 18, 'SUB TOTAL:', 'T', 'R', false, 0, 40, $y, true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 18, $this->pdfAmount($docdb, $decimal), 'T', 'R', false, 0, 590, $y, true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 18, $this->pdfAmount($doccr, $decimal), 'T', 'R', false, 1, 675, $y, true, 0, false, true, 0, 'M', false);
        $this->pdfDashedLine(40, 760);
    }

    private function DEFAULT_JOURNAL_VOUCHER_DETAILED_PDF($data, $params)
    {
        list($font, $fontbold) = $this->getPdfFonts();
        $fontsize = 11;
        $rowh = 15;
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $this->DETAILED_HEADER_PDF($params);

        if (empty($data)) {
            PDF::SetFont($fontbold, '', 14);
            PDF::MultiCell(0, 0, "\nNO DATA FOUND", '', 'C');
            return PDF::Output($this->modulename . '.pdf', 'S');
        }

        $pagelimit = PDF::getPageHeight() - 40;

        $totaldb = 0;
        $totalcr = 0;
        $docdb = 0;
        $doccr = 0;
        $prevdoc = null;
        $showhead = true;

        foreach ($data as $row) {

            if ($prevdoc !== null && $prevdoc != $row->docno) {
                if (PDF::GetY() + $rowh + 15 > $pagelimit) {
                    $this->DETAILED_HEADER_PDF($params);
                }
                $this->DETAILED_SUBTOTAL_PDF($docdb, $doccr, $decimal);
                $docdb = 0;
                $doccr = 0;
                $showhead = true;
            }

            for ($attempt = 0; $attempt < 2; $attempt++) {
                if ($showhead) {
                    $arr_doc = $this->pdfWrap($row->docno, 15);
                    $arr_payee = array_merge($this->pdfWrap($row->clientname, 22), $this->pdfWrap($row->rem, 22));
                    $arr_date = array((string)$row->dateid);
                } else {
                    $arr_doc = array();
                    $arr_payee = array();
                    $arr_date = array();
                }
                $arr_acno = $this->pdfWrap($row->acno, 7);
                $arr_desc = $this->pdfWrap($row->description, 17);
                $arr_ref = $this->pdfWrap($row->ref, 9);

                $maxrow = max(1, count($arr_doc), count($arr_payee), count($arr_date), count($arr_acno), count($arr_desc), count($arr_ref));

                if ($attempt == 0 && PDF::GetY() + ($maxrow * $rowh) > $pagelimit) {
                    $this->DETAILED_HEADER_PDF($params);
                    $showhead = true;
                } else {
                    break;
                }
            }

            $debit = $this->pdfAmount($row->debit, $decimal);
            $credit = $this->pdfAmount($row->credit, $decimal);

            PDF::SetFont($font, '', $fontsize);
            for ($r = 0; $r < $maxrow; $r++) {
                $y = PDF::GetY();
                PDF::MultiCell(112, $rowh, isset($arr_doc[$r]) ? $arr_doc[$r] : '', '', 'L', false, 0, 40, $y, true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(146, $rowh, isset($arr_payee[$r]) ? $arr_payee[$r] : '', '', 'L', false, 0, 152, $y, true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(70, $rowh, isset($arr_date[$r]) ? $arr_date[$r] : '', '', 'L', false, 0, 298, $y, true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(48, $rowh, isset($arr_acno[$r]) ? $arr_acno[$r] : '', '', 'L', false, 0, 368, $y, true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(110, $rowh, isset($arr_desc[$r]) ? $arr_desc[$r] : '', '', 'L', false, 0, 416, $y, true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(64, $rowh, isset($arr_ref[$r]) ? $arr_ref[$r] : '', '', 'L', false, 0, 526, $y, true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(85, $rowh, $r == 0 ? $debit : '', '', 'R', false, 0, 590, $y, true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(85, $rowh, $r == 0 ? $credit : '', '', 'R', false, 1, 675, $y, true, 0, false, true, 0, 'M', false);
            }

            $totaldb += $row->debit;
            $totalcr += $row->credit;
            $docdb += $row->debit;
            $doccr += $row->credit;

            $prevdoc = $row->docno;
            $showhead = false;
        }

        // last document sub total
        if (PDF::GetY() + $rowh + 15 > $pagelimit) {
            $this->DETAILED_HEADER_PDF($params);
        }
        $this->DETAILED_SUBTOTAL_PDF($docdb, $doccr, $decimal);

        // grand total
        if (PDF::GetY() + 25 > $pagelimit) {
            $this->DETAILED_HEADER_PDF($params);
        }
        $y = PDF::GetY();
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(550, 20, 'GRAND TOTAL:', 'TB', 'R', false, 0, 40, $y, true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 20, number_format($totaldb, $decimal), 'TB', 'R', false, 0, 590, $y, true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 20, number_format($totalcr, $decimal), 'TB', 'R', false, 1, 675, $y, true, 0, false, true, 0, 'M', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    // SUMMARIZED

    private function SUMMARIZED_HEADER_PDF($params)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        list($font, $fontbold) = $this->getPdfFonts();

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $startdate = $params['params']['dataparams']['start'];
        $enddate = $params['params']['dataparams']['end'];
        $posttype = $params['params']['dataparams']['posttype'];

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(40, 0);
        PDF::SetAutoPageBreak(false, 0);
        PDF::AddPage('p', array(800, 1000));

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        if (!empty($headerdata)) {
            PDF::SetFont($fontbold, '', 14);
            PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
            PDF::SetFont($font, '', 11);
            PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n", '', 'C');
        }

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(720, 30, '', '', 'L');

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(720, 0, 'SUMMARIZED ACCOUNTS PAYABLE VOUCHER', '', 'L');

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(720, 0, date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), '', 'L');
        PDF::MultiCell(250, 0, 'Transaction: ' . strtoupper($this->getPostTypeLabel($posttype)), '', 'L', false, 0);
        PDF::MultiCell(250, 0, 'Center: ' . $center, '', 'L', false, 0);
        PDF::MultiCell(220, 0, 'Page ' . PDF::PageNo(), '', 'R');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(110, 0, 'ACCOUNT CODE', '', 'L', false, 0);
        PDF::MultiCell(350, 0, 'ACCOUNT DESCRIPTION', '', 'C', false, 0);
        PDF::MultiCell(130, 0, 'DEBIT', '', 'R', false, 0);
        PDF::MultiCell(130, 0, 'CREDIT', '', 'R');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    private function DEFAULT_JOURNAL_VOUCHER_SUMMARIZED_PDF($data, $params)
    {
        list($font, $fontbold) = $this->getPdfFonts();
        $fontsize = 11;
        $rowh = 16;
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $this->SUMMARIZED_HEADER_PDF($params);

        if (empty($data)) {
            PDF::SetFont($fontbold, '', 14);
            PDF::MultiCell(0, 0, "\nNO DATA FOUND", '', 'C');
            return PDF::Output($this->modulename . '.pdf', 'S');
        }

        $pagelimit = PDF::getPageHeight() - 30;

        $totaldb = 0;
        $totalcr = 0;

        foreach ($data as $row) {
            $arr_acno = $this->pdfWrap($row->acno, 15);
            $arr_desc = $this->pdfWrap($row->description, 55);
            $maxrow = max(1, count($arr_acno), count($arr_desc));

            if (PDF::GetY() + ($maxrow * $rowh) > $pagelimit) {
                $this->SUMMARIZED_HEADER_PDF($params);
            }

            $debit = $this->pdfAmount($row->debit, $decimal);
            $credit = $this->pdfAmount($row->credit, $decimal);

            PDF::SetFont($font, '', $fontsize);
            for ($r = 0; $r < $maxrow; $r++) {
                PDF::MultiCell(110, $rowh, isset($arr_acno[$r]) ? $arr_acno[$r] : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(350, $rowh, isset($arr_desc[$r]) ? $arr_desc[$r] : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(130, $rowh, $r == 0 ? $debit : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(130, $rowh, $r == 0 ? $credit : '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
            }

            $totaldb += $row->debit;
            $totalcr += $row->credit;
        }

        if (PDF::GetY() + 30 > $pagelimit) {
            $this->SUMMARIZED_HEADER_PDF($params);
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(460, 18, 'GRAND TOTAL:', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(130, 18, number_format($totaldb, $decimal), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(130, 18, number_format($totalcr, $decimal), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }


// html layout 
    private function default_table_cols($layoutsize, $border, $font, $fontsize, $params)
    {
        $str = '';
        $reporttype = $params['params']['dataparams']['reporttype'];
        if ($reporttype == 1) {
            $str .= $this->reporter->begintable(1050);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DOCUMENT #', 120, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('PAYEE NAME PARTICULARS', 220, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('DATE', 80, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('ACCNT CODE', 70, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 180, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('REFFERENCE #', 120, null, '', '1px solid ', 'B', 'l', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('DEBIT', 130, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('CREDIT', 130, null, '', '1px solid ', 'B', 'r', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('ACCOUNT CODE', 100, null, '', '1px solid ', 'B', 'l', $font, $fontsize + 1, 'b', '', '');
            $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 400, null, '', '1px solid ', 'B', 'C', $font, $fontsize + 1, 'b', '', '');
            $str .= $this->reporter->col('DEBIT', 150, null, '', '1px solid ', 'B', 'r', $font, $fontsize + 1, 'b', '', '');
            $str .= $this->reporter->col('CREDIT', 150, null, '', '1px solid ', 'B', 'r', $font, $fontsize + 1, 'b', '', '');
            $str .= $this->reporter->endrow();
        }
        return $str;
    }

    private function DEFAULT_JOURNAL_VOUCHER_HEADER($params)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $font = $this->companysetup->getrptfont($params['params']);
        $fontsize10 = '10';

        $str = '';

        $startdate = $params['params']['dataparams']['start'];
        $enddate = $params['params']['dataparams']['end'];
        $reporttype = $params['params']['dataparams']['reporttype'];
        $acno = $params['params']['dataparams']['contra'];
        $acnoname = $params['params']['dataparams']['acnoname'];

        if ($acnoname == "") {
            $acnoname = "ALL";
        } else {
            $acnoname = $acno . ' - ' . $acnoname;
        }

        $posttype = $params['params']['dataparams']['posttype'];

        switch ($posttype) {
            case 0:
                $post = 'Posted';
                break;
            case 1:
                $post = 'Unposted';
                break;
            default:
                $post = 'All';
                break;
        }

        if ($reporttype == 1) {
            $str .= $this->reporter->begintable('1200');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username, $params);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= '<br/><br/>';

            $str .= $this->reporter->begintable('1200', null, '', '1px solid ', '', '', $font, '', '', '', '');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DETAILED ACCOUNTS PAYABLE VOUCHER', null, null, '', '1px solid ', '', 'l', $font, '18', 'b', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col('Account: ' . strtoupper($acnoname), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

            $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->pagenumber('Page');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username, $params);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= '<br><br>';

            $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('SUMMARIZED ACCOUNTS PAYABLE VOUCHER', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->pagenumber('Page');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->printline();
        }

        return $str;
    }

    private function DEFAULT_JOURNAL_VOUCHER_DETAILED($data, $params)
    {
        $border = '1px solid';

        $font = $this->companysetup->getrptfont($params['params']);
        $fontsize10 = '10';
        $fontsize12 = 12;
        $str = '';
        $count = 41;
        $page = 40;

        $this->reporter->linecounter = 0;

        $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

        if (empty($data)) {
            return $this->othersClass->emptydata($params);
        }

        $str .= $this->reporter->beginreport();
        $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize10, $params);
        $totaldb = 0;
        $totalcr = 0;
        $docno = "";
        $date = "";
        $cname = "";
        $rem = "";
        $docdb = 0;
        $doccr = 0;

        foreach ($data as $key => $data) {
            if ($docno == $data->docno) {
                $docno = "";
                $date = "";
                $cname = "";
                $rem = "";
            } else {
                if ($docno != "") {
                    //Sub total
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col('', 120, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
                    $str .= $this->reporter->col('', 220, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
                    $str .= $this->reporter->col('', 80, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
                    $str .= $this->reporter->col('', 70, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
                    $str .= $this->reporter->col('', 180, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
                    $str .= $this->reporter->col('SUB TOTAL:', 100, null, '', $border, 'T', 'r', $font, $fontsize10, 'B', '', '');
                    $str .= $this->reporter->col(number_format($docdb, $decimal_currency), 130, null, '', $border, 'T', 'r', $font, $fontsize10, 'B', '', '');
                    $str .= $this->reporter->col(number_format($doccr, $decimal_currency), 130, null, '', $border, 'T', 'r', $font, $fontsize10, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $docdb = 0;
                    $doccr = 0;

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    for ($c = 0; $c < 9; $c++) {
                        $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
                    }
                    $str .= $this->reporter->endrow();
                }
                $docno = $data->docno;
                $date = $data->dateid;
                $cname = $data->clientname;
                $rem = $data->rem;
            }

            $debit = number_format($data->debit, $decimal_currency);
            if ($debit == 0) {
                $debit = '-';
            }
            $credit = number_format($data->credit, $decimal_currency);
            if ($credit == 0) {
                $credit = '-';
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($docno, 120, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($cname . "<br>" . $rem, 220, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($date, 80, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($data->acno, 70, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($data->description, 180, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($data->ref, 120, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($debit, 130, null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($credit, 130, null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');

            $totaldb = $totaldb + $data->debit;
            $totalcr = $totalcr + $data->credit;

            $docdb = $docdb + $data->debit;
            $doccr = $doccr + $data->credit;

            $docno = $data->docno;
            $date = $data->dateid;
            $cname = $data->clientname;
            $rem = $data->rem;

            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
                if (!$allowfirstpage) {
                    $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
                }
                $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

                $str .= $this->reporter->endrow();
                $page = $page + $count;
            }
        } // end foreach

        //Final Sub total
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('', 120, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('', 220, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('', 80, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('', 70, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('', 180, null, '', '1px solid ', 'T', '', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('SUB TOTAL:', 100, null, '', $border, 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($docdb, $decimal_currency), 130, null, '', $border, 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($doccr, $decimal_currency), 130, null, '', $border, 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        for ($c = 0; $c < 9; $c++) {
            $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 120, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col('', 250, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col('', 110, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col('', 100, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col('GRAND TOTAL: ', 200, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col('', 120, null, '', '1px solid ', 'T', 'c', '', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    private function DEFAULT_JOURNAL_VOUCHER_SUMMARIZED($data, $params)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($params['params']);
        $fontsize10 = '10';
        $fontsize12 = 12;

        $str = '';
        $count = 60;
        $page = 59;

        $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

        if (empty($data)) {
            return $this->othersClass->emptydata($params);
        }

        $str .= $this->reporter->beginreport();
        $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $totaldb = 0;
        $totalcr = 0;

        foreach ($data as $key => $data) {
            $debit = number_format($data->debit, $decimal_currency);
            if ($debit == 0) {
                $debit = '-';
            }
            $credit = number_format($data->credit, $decimal_currency);
            if ($credit == 0) {
                $credit = '-';
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->acno, 100, null, '', '1px solid ', '', 'l', $font, $fontsize12, '', '', '');
            $str .= $this->reporter->col($data->description, 400, null, '', '1px solid ', '', 'l', $font, $fontsize12, '', '', '');
            $str .= $this->reporter->col($debit, 150, null, '', '1px solid ', '', 'r', $font, $fontsize12, '', '', '');
            $str .= $this->reporter->col($credit, 150, null, '', '1px solid ', '', 'r', $font, $fontsize12, '', '', '');

            $totaldb = $totaldb + $data->debit;
            $totalcr = $totalcr + $data->credit;
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
                if (!$allowfirstpage) {
                    $str .= $this->DEFAULT_JOURNAL_VOUCHER_HEADER($params);
                }
                $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
                $str .= $this->reporter->begintable('800');
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, '', '12', '', '');
        $str .= $this->reporter->col('', 100, null, '', '1px solid ', 'T', 'c', '', $font, '', '', '');
        $str .= $this->reporter->col('GRAND TOTAL: ', 400, null, '', '1px solid ', 'T', 'C', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'b', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class