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

class cv
{

    private $modulename = "Cash/Check Voucher";
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
        $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'checked', 'payor', 'tin', 'position', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'EXCEL VOUCHER', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'radioreporttype.label', 'Print Cash/Check Voucher');
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'VOUCHER', 'value' => '0', 'color' => 'blue'],
                ['label' => 'CHECK', 'value' => '1', 'color' => 'blue'],
                ['label' => 'METROBANK CHECK', 'value' => '3', 'color' => 'blue'],
                ['label' => 'BPI CHECK', 'value' => '4', 'color' => 'blue'],
                ['label' => 'Eastwest CHECK', 'value' => '5', 'color' => 'blue'],
                ['label' => 'BIR Form 2307', 'value' => '2', 'color' => 'blue']
            ]
        );
        return array('col1' => $col1);
    }

    public function reportplotting($config, $data)
    {
        if ($config['params']['dataparams']['print'] == "excel") {
            switch ($config['params']['dataparams']['reporttype']) {
                case '0': // VOUCHER
                    $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
                    break;
                default:
                    $str = $this->No_Excel_format($data, $config);
                    break;
            }
        } else if ($config['params']['dataparams']['print'] == "PDFM") {

            switch ($config['params']['dataparams']['reporttype']) {
                case '0': // VOUCHER

                    $str = $this->PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
                    break;
                case '1':
                    $str = $this->PDF_DEFAULT_CCVOUCHER_LAYOUT2($data, $config);
                    break;
                case '2':
                    $str = $this->PDF_CV_WTAXREPORT($data, $config);
                    break;
                case '3':
                    $str = $this->PDF_METROBANK_CHECK_LAYOUT($data, $config);
                    break;

                case '4':
                    $str = $this->PDF_BPI_CHECK_LAYOUT($data, $config);
                    break;

                case '5':
                    $str = $this->PDF_EASTWEST_CHECK_LAYOUT($data, $config);
                    break;
            }
        }

        return $str;
    }

    public function reportparamsdata($config)
    {
        $trno = $config['params']['trno'];
        // var_dump($trno);
        $createname = $this->coreFunctions->datareader("select createby as value from
                   (select createby from lahead  where trno = $trno
                   union all
                   select createby from glhead  where trno = $trno) as cr");
        $create = $this->coreFunctions->datareader("select name as value from useraccess where username = '$createname'");
        $username = (!empty($create) && isset($create)) ? $create : '';
        // $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
        return $this->coreFunctions->opentable(
            "select 
        'PDFM' as print,
        '$username' as prepared,
        '' as approved,
        '' as received,
        '' as checked,
        '' as payor,
        '' as position,
        '' as tin,
        '0' as reporttype
        "
        );
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];

        switch ($filters['params']['dataparams']['reporttype']) {
            case 2:
                $query = "select * from(
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        detail.db, detail.cr, detail.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
        
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        left join coa on coa.acnoid=detail.acnoid
        where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
        union all
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref, detail.postdate,
        detail.db, detail.cr, dclient.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join client as dclient on dclient.clientid=detail.clientid
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
        as tbl order by tbl.ewtdesc";
                $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

                $arrs = [];
                $arrss = [];
                $ewt = '';
                foreach ($result1 as $key => $value) {
                    $ewtrateval = floatval($value['ewtrate']) / 100;
                    if ($value['db'] == 0) {
                        //FOR CR
                        if ($value['cr'] < 0) {
                            $db = $value['cr'];
                        } else {
                            $db = floatval($value['cr']) * -1;
                        } //end if

                        if ($value['isvewt'] == 1) {
                            $db = $db / 1.12;
                        }

                        $ewtamt = $db * $ewtrateval;
                    } else {
                        //FOR DB
                        if ($value['db'] < 0) {
                            $db = floatval($value['db']) * -1;
                        } else {
                            $db = $value['db'];
                        } //end if

                        if ($value['isvewt'] == 1) {
                            $db = $db / 1.12;
                        }
                        $ewtamt = $db * $ewtrateval;
                    } //end if

                    if ($ewt != $value['ewtcode']) {
                        $arrs[$value['ewtcode']]['oamt'] = $db;
                        $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
                        $arrs[$value['ewtcode']]['month'] = $value['month'];
                    } else {
                        array_push($arrss, $arrs);
                        $arrs[$value['ewtcode']]['oamt'] = $db;
                        $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
                        $arrs[$value['ewtcode']]['month'] = $value['month'];
                    }

                    $ewt = $value['ewtcode'];
                } //end for each

                array_push($arrss, $arrs);
                $keyers = '';
                $finalarrs = [];
                foreach ($arrss as $key => $value) {
                    foreach ($value as $key => $y) {
                        if ($keyers == '') {
                            $keyers = $key;
                            $finalarrs[$key]['oamt'] = $y['oamt'];
                            $finalarrs[$key]['xamt'] = $y['xamt'];
                        } else {
                            if ($keyers == $key) {
                                $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($y['oamt']);
                                $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($y['xamt']);
                            } else {
                                $finalarrs[$key]['oamt'] = $y['oamt'];
                                $finalarrs[$key]['xamt'] = $y['xamt'];
                            } //end if
                        } //end if
                        $finalarrs[$key]['month'] = $y['month'];
                    }
                } //end for each
                if (empty($result1)) {
                    $returnarr[0]['payee'] = '';
                    $returnarr[0]['tin'] = '';
                    $returnarr[0]['payortin'] = '';
                    $returnarr[0]['address'] = '';
                    $returnarr[0]['month'] = '';
                    $returnarr[0]['yr'] = '';
                    $returnarr[0]['payorcompname'] = '';
                    $returnarr[0]['payoraddress'] = '';
                    $returnarr[0]['payorzipcode'] = '';
                } else {
                    $returnarr[0]['payee'] = $result1[0]['clientname'];
                    $returnarr[0]['tin'] = $result1[0]['tin'];
                    $returnarr[0]['payortin'] = $result1[0]['payortin'];
                    $returnarr[0]['address'] = $result1[0]['address'];
                    $returnarr[0]['month'] = $result1[0]['month'];
                    $returnarr[0]['yr'] = $result1[0]['yr'];
                    $returnarr[0]['payorcompname'] = $result1[0]['payorcompname'];
                    $returnarr[0]['payoraddress'] = $result1[0]['payoraddress'];
                    $returnarr[0]['payorzipcode'] = $result1[0]['payorzipcode'];
                }

                $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
                break;

            default:
                $query = "select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%Y-%m-%d')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
        DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
        date(head.dateid) as dateid, 
        date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
        client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
        coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
        detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,
        IF(LEFT(coa.alias, 2) = 'CB',  CONCAT(coa.acnoname, CHAR(10),' Check #: ', detail.checkno),
          IF(LEFT(coa.alias, 2) != 'CA',  CONCAT(coa.acnoname, CHAR(10), 'Reference #: ', detail.ref), coa.acnoname ) ) AS acnonamedesc
        from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)left join coa on coa.acnoid=detail.acnoid
        left join glhead as head2 on head2.trno = detail.refx
        left join cntnum on cntnum.trno=head.trno
        LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
        where head.doc='cv' and head.trno ='$trno'
        union all
        select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%Y-%m-%d')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
        DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
        date(head.dateid) as dateid, 
        date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
        client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
        coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
        detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,
        IF(LEFT(coa.alias, 2) = 'CB',  CONCAT(coa.acnoname, CHAR(10),' Check #: ', detail.checkno),
         IF(LEFT(coa.alias, 2) != 'CA',  CONCAT(coa.acnoname, CHAR(10), 'Reference #: ', detail.ref), coa.acnoname)) AS acnonamedesc
        from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
        left join client on client.clientid=head.clientid)left join coa on coa.acnoid=detail.acnoid
        left join glhead as head2 on head2.trno = detail.refx
        left join cntnum on cntnum.trno=head.trno
        LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
        where head.doc='cv' and head.trno ='$trno'";
                // var_dump($query);
                $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
                break;
        } // end switch


        return $result;
    }

    public function PDF_default_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";


        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);
        PDF::SetCellPaddings(4, 4, 4, 4);

        PDF::SetFont($font, '');

        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($params);

        $user = $this->coreFunctions->datareader("select name as value from useraccess where username = '$username'");
        PDF::MultiCell(0, 0, "");
        PDF::SetFont($font, '', 10);
        $printdate = date_format(date_create($current_timestamp), 'm/d/Y');
        $printhrs = date_format(date_create($current_timestamp), 'h:i:s A');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(65, 0, 'Printdate : ', '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(115, 0,  $printdate, '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(65, 0, 'Printdate : ', '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(115, 0,  $printhrs, '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(49, 0, 'User : ', '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(131, 0,  $user, '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(180, 0, 'Page  ' . PDF::PageNo() . '  of  ' . PDF::getAliasNbPages(), '', 'R', false, 1);

        // PDF::MultiCell(350, 0, ' ' . 'Page    ' . PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), '', 'R');
        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(320, 5, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(220, 5, "DOCNO #: ", '', 'R', false, 0, '',  '');
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(180, 5, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'R', false);
        // PDF::SetFont($fontbold, '', 1);
        // PDF::MultiCell(720, 0, '', '', 'L', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 5, 'PAYEE : ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(425, 5, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 5, 'DATE:', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
        $dates = date_format(date_create($datehere), 'M-d-Y');
        // PDF::MultiCell(5, 5, '', 'B', 'L', false, 0);
        PDF::MultiCell(125, 5, $dates, 'B', 'L', false);
        // PDF::SetFont($fontbold, '', 1);
        // PDF::MultiCell(720, 0, '', '', 'L', false);
        // PDF::SetFont($fontbold, '', 1);
        // PDF::MultiCell(720, 0, '', '', 'L', false);

        $add = isset($data[0]['address']) ? $data[0]['address'] : '';
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
            PDF::MultiCell(75, 5, 'ADDRESS : ', '', 'L', false, 0);
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(420, 5, $firstLine, 'B', 'L', false, 0, '',  '');

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 5, 'REFERENCE# :', '', 'R', false, 0);
            PDF::SetFont($fontbold, '', $fontsize);
            // PDF::MultiCell(5, 5, '', '', 'R', false, 0);
            PDF::MultiCell(125, 5, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);


            // Loop through remaining lines and print them
            foreach ($remainingLines as $line) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(75, 5, '', '', 'L', false, 0);
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(420, 5, $line, 'B', 'L', false, 0, '',  '');

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(100, 5, '', '', 'R', false, 0);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(125, 5, '', '', 'L', false);
            }
        } else {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(75, 5, 'ADDRESS : ', '', 'L', false, 0);
            PDF::SetFont($fontbold, '', 12);
            PDF::MultiCell(420, 5, $addsz, 'B', 'L', false, 0, '',  '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 5, 'REFERENCE# :', '', 'R', false, 0);
            PDF::SetFont($fontbold, '', $fontsize);
            // PDF::MultiCell(5, 5, '', '', 'R', false, 0);
            PDF::MultiCell(125, 5, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);
        }


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 5, 'NOTES : ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(650, 5, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false);
        PDF::SetFont($fontbold, '', 2);
        PDF::MultiCell(720, 0, '', '', 'L', false);


        PDF::SetFont($font, '', 0);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(100, 0, 'ACCT #', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'ACCOUNT NAME', '', 'C', false, 0);
        PDF::MultiCell(75, 0, 'DATE', '', 'C', false, 0);
        PDF::MultiCell(85, 0, 'DEBIT', '', 'R', false, 0);
        PDF::MultiCell(85, 0, 'CREDIT', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'C', false, 0);
        PDF::MultiCell(65, 0, 'CLIENT', '', 'C', false);

        PDF::SetFont($font, '', 0);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $params)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }
        $this->PDF_default_header($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');
        PDF::SetCellPaddings(1, 1, 1, 1);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        $countarr = 1;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;

                $acno =  $data[$i]['acno'];
                $acnonamedescs = $data[$i]['acnonamedesc'];
                $checkno = $data[$i]['checkno'];
                $pdates = $data[$i]['pdate'];

                $pdate = date_format(date_create($pdates), 'm-d-y');

                $debit = number_format($data[$i]['db'], $decimalcurr);
                $debit = $debit < 0 ? '-' : $debit;
                // $ref = $data[$i]['ref'];
                $client = $data[$i]['client'];

                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $credit = $credit < 0 ? '-' : $credit;
                // $drem = $data[$i]['drem'];

                $arr_acno = $this->reporter->fixcolumn([$acno], '15', 0);
                $arr_checkno = $this->reporter->fixcolumn([$checkno], '15', 0);
                $arr_acnonamedescs = $this->reporter->fixcolumn([$acnonamedescs], '35', 0);
                $arr_pdate = $this->reporter->fixcolumn([$pdate], '13', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);
                // $arr_drem = $this->reporter->fixcolumn([$drem], '13', 0);
                // $arr_ref = $this->reporter->fixcolumn([$ref], '13', 0);
                $arr_client = $this->reporter->fixcolumn([$client], '25', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnonamedescs, $arr_checkno, $arr_pdate, $arr_debit, $arr_credit, $arr_client]);
                // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '',  '', true, 1, false, true, 0, 'M', false);
                    PDF::MultiCell(300,  15, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), '', 'L',  false,  0, '', '', true,   1, false, true,   0, 'M', false);
                    // PDF::MultiCell(100, 15, ' ' . (isset($arr_checkno[$r]) ? $arr_checkno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ' . (isset($arr_pdate[$r]) ? $arr_pdate[$r] : ''), '', 'C', false, 0, '',  '', true, 1, false, true, 0, 'M', false);
                    PDF::MultiCell(85, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '',  '', true, 1, false, true, 0, 'M', false);
                    PDF::MultiCell(85, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '',  '', true, 1, false, true, 0, 'M', false);
                    PDF::MultiCell(10, 15, ' ', '', 'L', false, 0, '',  '', true, 1, false, true, 0, 'M', false);
                    PDF::MultiCell(65, 15, ' ' . (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '',  '', true, 1, false, true, 0, 'M', false);
                    // if ($r == 0) {
                    //     $countarr++;
                    // }
                }
                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];


                if (PDF::getY() > 900) {
                    $this->PDF_default_header($params, $data);
                }
            }
        }


        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(705, 0, '', '');

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(475, 5, 'GRAND TOTAL : ', '', 'R', false, 0);
        PDF::MultiCell(85, 5, number_format($totaldb, $decimal), '', 'R', false, 0);
        PDF::MultiCell(85, 5, number_format($totalcr, $decimal), '', 'R', false, 0);
        PDF::MultiCell(10, 5, '', '', 'R', false, 0);
        PDF::MultiCell(65, 5, '', '', 'R', false);

        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($font, '', 12);
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::MultiCell(200, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, 'Checked By: ', '', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, 'Approved By: ', '', 'L');
        $checked = $params['params']['dataparams']['checked'];
        $approved = $params['params']['dataparams']['approved'];
        $received = $params['params']['dataparams']['received'];

        if ($checked == '') {
            $chek = 'JPL / JMN';
        } else {
            $chek = $checked;
        }
        if ($approved == '') {
            $appr = 'MYJ / JTG';
        } else {
            $appr = $approved;
        }


        $trno = $params['params']['dataid'];
        $createname = $this->coreFunctions->datareader("select createby as value from
                   (select createby from lahead  where trno = $trno
                   union all
                   select createby from glhead  where trno = $trno) as cr");
        $create = $this->coreFunctions->datareader("select name as value from useraccess where username = '$createname'");
        $prep = $params['params']['dataparams']['prepared'];

        if ($prep == '') {
            $preparedby = $create;
        } else {
            $preparedby = $prep;
        }
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(200, 0, $preparedby, 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, $chek, 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, $appr, 'B', 'C');
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 12);
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::MultiCell(200, 0, '', '', 'L', false, 0);
        PDF::MultiCell(60, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, 'Received By: ', '', 'L', false, 0);
        PDF::MultiCell(60, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(200, 0, '', '', 'C', false, 0);
        PDF::MultiCell(60, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '', '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_DEFAULT_CCVOUCHER_LAYOUT2($data, $params)
    {
        $companyid = $params['params']['companyid'];

        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);
        $cc = '';
        $cdate = '';

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');

        PDF::SetMargins(40, 40);

        //where trno = ".$data[0]['trno']." and left(coa.alias,2) = 'CB' 

        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($data2); $i++) {
            PDF::AddPage('p', [800, 1000]);
            $cc = $data2[$i]['cr'];
            $cdate = $data2[$i]['postdate'];

            PDF::SetFont($font, '', 10);

            PDF::MultiCell(50, 5, '', '', 'C', false, 0);
            PDF::MultiCell(170, 5, '', '', 'C', false, 0);
            PDF::MultiCell(420, 5, ('' . isset($cdate) ? $cdate : ''), '', 'C', false, 0);

            // PDF::MultiCell(420, 5, 'DATEEEEEEEE', '', 'R',false,0);
            PDF::MultiCell(120, 5, '', '', 'C', false);

            PDF::MultiCell(120, 5, '', '', 'C', false, 0);
            PDF::MultiCell(150, 5, $data[0]['clientname'], '', 'L', false, 0);
            PDF::MultiCell(420, 5, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'C', false, 0);

            // PDF::MultiCell(220, 5, 'CLIEEEEEEEEENT', '', 'L',false,0);
            // PDF::MultiCell(420, 5, 'CCCCCCCCCCCC', '', 'R',false,0);
            PDF::MultiCell(100, 5, '', '', 'C', false);

            $dd = number_format((float)$cc, 2, '.', '');

            PDF::MultiCell(120, 5, '', '', 'C', false, 0);
            PDF::setFontSpacing(2);
            PDF::MultiCell(320, 5, $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false);

            // PDF::MultiCell(720, 5, 'CASHSSSSSS ONLY', '', 'L',false);
            $this->reporter->linecounter = 30;
        }

        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_CV_WTAXREPORT($data, $params)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        $border = '2px solid';
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(10, 10);



        //Row 1 Logo
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(780, 10, '', '', 'L', false);
        PDF::MultiCell(50, 10, 'For BIR' . "\n" . 'Use Only', '', 'L', false, 0);
        PDF::MultiCell(50, 10, 'BCS/' . "\n" . 'Item:', '', 'L', false, 0);
        PDF::MultiCell(270, 10, '', '', 'L', false, 0);
        PDF::MultiCell(140, 10, 'Republic of the Philippines' . "\n" . 'Department of Finance' . "\n" . 'Bureau of Internal Revenue', '', 'C', false, 0);
        PDF::MultiCell(270, 10, '', '', 'L', false);


        //Row 2
        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::MultiCell(120, 55, '', 'TBLR', 'L', false, 0, 10);
        PDF::SetFont($fontbold, '', 16);
        PDF::MultiCell(460, 55, 'Certificate of Credible Tax' . "\n" . 'Withheld at Source', 'TBLR', 'C', false, 0, 130);

        PDF::MultiCell(200, 55, '', 'TBLR', 'L', false, 1, 590);
        $this->reportheader->getheader($params);

        //Row 3
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(780, 10, 'Fill in all applicable spaces. Mark all appropriate boxes with an "X"', 'TBLR', 'L', false, 1, 10, 129);

        //Row 4
        $d1 = '';
        $m1 = '';
        $y1 = '';

        $d2 = '';
        $m2 = '';
        $y2 = '';

        $month = "";
        $year = "";

        $trno = $params['params']['dataid'];
        if ($data['head'][0]['month'] == "" || $data['head'][0]['yr'] == "") {
            $mmyy = $this->coreFunctions->opentable("select month, right(yr,2) as yr from (select month(dateid) as month, year(dateid) as yr from lahead
        where doc= 'CV' and trno = $trno
        union all
        select month(dateid) as month, year(dateid) as year from glhead
        where doc = 'CV' and trno = $trno) as a");
            $month = $mmyy[0]->month;
            $year = $mmyy[0]->yr;
        } else {
            $month = $data['head'][0]['month'];
            $year = $data['head'][0]['yr'];
        }

        switch ($month) {
            case '1':
            case '2':
            case '3':
                $d1 = '01';
                $m1 = '01';
                $y1 = $year;

                $d2 = '03';
                $m2 = '31';
                $y2 = $year;
                break;

            case '4':
            case '5':
            case '6':
                $d1 = '04';
                $m1 = '01';
                $y1 = $year;

                $d2 = '06';
                $m2 = '30';
                $y2 = $year;
                break;

            case '7':
            case '8':
            case '9':
                $d1 = '07';
                $m1 = '01';
                $y1 = $year;

                $d2 = '09';
                $m2 = '30';
                $y2 = $year;
                break;

            default:
                $d1 = '10';
                $m1 = '01';
                $y1 = $year;

                $d2 = '12';
                $m2 = '31';
                $y2 = $year;
                break;
        }

        PDF::SetFont($font, '', 16);
        PDF::MultiCell(780, 10, '', 'LR', '', false, 0, 10, 142);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 10, '1', 'L', 'C', false, 0, 10, 145);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 10, 'For the Period', '', 'L', false, 0);
        PDF::MultiCell(90, 10, '', '', '', false, 0);
        PDF::MultiCell(35, 10, 'From', '', '', false, 0);
        PDF::MultiCell(20, 10, '', '', '', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(25, 15, $d1, 'LTB', 'C', false, 0);
        PDF::MultiCell(25, 15, $m1, 'LTB', 'C', false, 0);
        PDF::MultiCell(25, 15, $y1, 'LTBR', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(90, 10, '', '', '', false, 0);
        PDF::MultiCell(25, 15, $d2, 'LTB', 'C', false, 0);
        PDF::MultiCell(25, 15, $m2, 'LTB', 'C', false, 0);
        PDF::MultiCell(25, 15, $y2, 'LTBR', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
        PDF::MultiCell(95, 10, '', 'R', '', false);

        //Row 5
        PDF::MultiCell(780, 18, '', 'LTBR', '', false, 0, 10, 163);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(780, 18, 'Part I - Payee Information', 'LTBR', 'C', false, 1, 10, 164);

        //Row 6
        PDF::MultiCell(780, 25, '', 'LTBR', '', false, 0);
        PDF::MultiCell(50, 25, '2', '', 'C', false, 0, 10, 185);
        PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::MultiCell(5, 18, '', 'LTB', 'L', false, 0);
        PDF::MultiCell(515, 18, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'TBR', 'L', false, 0);
        PDF::MultiCell(10, 25, '', '', 'C', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        //Row 7
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 15, '3', 'LT', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(730, 15, "Payee's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'TR', 'L', false);

        //Row 8
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::MultiCell(5, 18, '', 'LTB', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::MultiCell(715, 18, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'TRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        //Row 9
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 15, '4', 'L', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, '4A', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
        PDF::MultiCell(10, 15, '', 'R', 'L', false);

        //Row 10
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::MultiCell(5, 18, '', 'LTB', 'L', false, 0);
        PDF::MultiCell(625, 18, (isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), 'TRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", '', 'L', false, 0);
        PDF::MultiCell(5, 18, '', 'LTB', 'L', false, 0);
        PDF::MultiCell(75, 18, (isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), 'TRB', 'C', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        //Row 11
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 15, '5', 'L', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(730, 15, "Foreign Address If Applicable", 'R', 'L', false);

        //Row 12
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::MultiCell(720, 18, "", 'LTRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);

        //Row 13
        PDF::MultiCell(780, 18, '', 'LRB', '', false, 1, 10, 295);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(780, 18, 'Part II - Payor Information', 'LTRB', 'C', false);

        //Row 14
        PDF::MultiCell(780, 25, '', 'LTR', '', false, 0);
        PDF::MultiCell(50, 25, '6', '', 'C', false, 0, 10, 335);
        PDF::SetFont($font, '', 11);
        $payortin = '213-362-385-00000';
        PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
        PDF::MultiCell(5, 18, '', 'LTB', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::MultiCell(515, 18, $payortin, 'TBR', 'L', false, 0);
        PDF::MultiCell(10, 25, '', '', 'C', false);
        PDF::SetCellPaddings(0, 0, 0, 0);

        //Row 15
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(780, 25, '', 'LR', '', false, 1, 10, 340);
        PDF::MultiCell(50, 15, '7', 'L', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(730, 15, "Payor's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'R', 'L', false);

        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::SetFont($fontbold, '', 11);
        $payorname = 'HOMEWORKS THE HOMECENTER INCORPORATED';
        //Row 16
        PDF::SetCellPaddings(2, 2, 2, 2);
        PDF::MultiCell(5, 18, '', 'LTB', 'L', false, 0);
        PDF::MultiCell(715, 18, $payorname, 'TRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);


        //Row 17
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 15, '8', 'L', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, '8A', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
        PDF::MultiCell(10, 15, '', 'R', 'L', false);

        $payoraddr = 'BASEMENT EVER GOTESCO COMMONWEALTH AVENUE CORNER DN MARIA MATANDANG BALARA, 1119 QUEZON ';
        $payoradd2 = 'CITY NCR, 2ND DISTRICT PHILS.';

        //Row 18
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);

        PDF::SetCellPaddings(2, 2, 2, 2);

        $html = '<span style="color:#ffffff">q</span>' . $payoraddr . '<span style="color:#ffffff">pi iii</span>' . $payoradd2;
        PDF::writeHTMLCell(630,  18,  '',  '',  $html,  1, 0,  0,   true,  'L',  true);
        // PDF::MultiCell(630, 18, (isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), 'LTRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", '', 'L', false, 0);
        PDF::MultiCell(5, 18, '', 'LTB', 'L', false, 0);
        PDF::MultiCell(75, 18, (isset($data['head'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), 'TRB', 'C', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::MultiCell(780, 1, '', 'LR', '', false, 1, 10, 415);



        //Row 13
        PDF::MultiCell(780, 23, '', 'LRB', '', false, 1, 10, 425); //  1  1, 10, 425 
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(780, 18, 'Part III - Details of Monthly Income Payments and Taxes Withheld', 'LTR', 'C', false);

        //Row 14
        // PDF::MultiCell(200, 20, '', 'LTR', 'C', false, 0, 10, 457);
        // PDF::MultiCell(80, 20, '', 'LTR', 'C', false, 0, 210, 457);
        // PDF::MultiCell(380, 20, 'AMOUNT OF INCOME PAYMENTS', 'LTR', 'C', false, 0, 290, 457);
        // PDF::MultiCell(120, 20, '', 'LTR', 'C', false, 1, 670, 457);
        PDF::MultiCell(200, 20, '', 'LTR', 'C', false, 0, 10, 464); //457 470
        PDF::MultiCell(80, 20, '', 'LTR', 'C', false, 0, 210, 464); //457
        PDF::MultiCell(380, 20, 'AMOUNT OF INCOME PAYMENTS', 'LTR', 'C', false, 0, 290, 464);
        PDF::MultiCell(120, 20, '', 'LTR', 'C', false, 1, 670, 464);

        //Row 15
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(200, 20, 'Income Payments Subject to' . "\n" . ' Expanded Withholding Tax', 'LR', 'C', false, 0);
        PDF::MultiCell(80, 20, 'ATC', 'LTR', 'C', false, 0);
        PDF::MultiCell(95, 20, '1st Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
        PDF::MultiCell(95, 20, '2nd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
        PDF::MultiCell(95, 20, '3rd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
        PDF::MultiCell(95, 20, 'Total', 'LTR', 'C', false, 0);
        PDF::MultiCell(120, 20, 'Tax Withheld for the' . "\n" . 'Quarter', 'LTR', 'C', false, 1);

        //Row 16
        PDF::MultiCell(780, 20, '', 'T', '', false);

        //Row 17
        // PDF::MultiCell(780, 10, '', 'T', '', false, 1, 10, 500);

        PDF::MultiCell(200, 10, '', 'LR', '', false, 0, 10, 500);
        PDF::MultiCell(80, 10, '', 'LR', '', false, 0, 210);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 290);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 385);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 480);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 575);

        PDF::MultiCell(120, 10, '', 'LR', 'R', false, 1, 670);

        //Row 18 ---atc1

        $total = 0;
        $a = -1;
        $totalwtx1 = 0;
        $totalwtx2 = 0;
        $totalwtx3 = 0;
        $totalwtx = 0;

        foreach ($data['detail'] as $key => $value) {
            $a++;

            $ewt_height = PDF::GetStringHeight(200, $data['res'][$a]['ewtdesc']);
            $key_height = PDF::GetStringHeight(80, $key);
            $max_height = max($ewt_height, $key_height);

            if ($max_height > 25) {
                $max_height = $max_height + 15;
            }
            PDF::MultiCell(5, $max_height, '', 'LB', '', false, 0);
            PDF::MultiCell(195, $max_height, $data['res'][$a]['ewtdesc'], 'RB', '', false, 0);
            PDF::MultiCell(80, $max_height, $key, 'LRB', 'C', false, 0);

            switch ($data['head'][0]['month']) {
                // case '1': case '2': case '3':
                case '1':
                case '4':
                case '7':
                case '10':
                    PDF::MultiCell(95, $max_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    $totalwtx1 +=  $data['detail'][$key]['oamt'];
                    break;
                // case '4': case '5': case '6':
                case '2':
                case '5':
                case '8':
                case '11':
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    PDF::MultiCell(95, $max_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    $totalwtx2 +=  $data['detail'][$key]['oamt'];
                    break;
                default:
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    PDF::MultiCell(95, $max_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
                    $totalwtx3 +=  $data['detail'][$key]['oamt'];
                    break;
            }
            $total = number_format($data['detail'][$key]['oamt'], 2);
            PDF::MultiCell(95, $max_height, $total, 'LRB', 'R', false, 0);
            PDF::MultiCell(120, $max_height, number_format($data['detail'][$key]['xamt'], 2), 'LRB', 'R', false);

            $totalwtx += $data['detail'][$key]['oamt'];
        }

        //Row 19 ----total
        $totaltax = 0;
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(200, 20, '   Total', 'LR', '', false, 0);
        PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 20, ($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), 'LR', 'R', false, 0);
        PDF::MultiCell(95, 20, ($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), 'LR', 'R', false, 0);
        PDF::MultiCell(95, 20, ($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), 'LR', 'R', false, 0);
        PDF::MultiCell(95, 20, ($totalwtx != 0 ? number_format($totalwtx, 2) : ''), 'LR', 'R', false, 0);
        foreach ($data['detail'] as $key => $value) {
            $totaltax = $totaltax + $data['detail'][$key]['xamt'];
        }
        PDF::MultiCell(120, 10, number_format($totaltax, 2), 'LR', 'R', false);
        PDF::SetFont($font, '', 9);

        //Row 20 ---space for total 
        PDF::MultiCell(200, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(120, 10, '', 'LR', 'R', false);

        //Row 21
        PDF::MultiCell(200, 10, 'Money Payments Subjects to', 'TLR', '', false, 0);
        PDF::MultiCell(80, 10, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
        PDF::MultiCell(120, 10, '', 'TLR', 'R', false);

        PDF::MultiCell(200, 10, 'Withholding of Business Tax', 'LR', '', false, 0);
        PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(120, 10, '', 'LR', 'R', false);

        PDF::MultiCell(200, 10, '(Government & Private)', 'LR', '', false, 0);
        PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
        PDF::MultiCell(120, 10, '', 'LR', 'R', false);

        //Row 22
        PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(120, 20, '', 'TLR', 'R', false);


        //Row 23
        PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

        //Row 24
        PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

        //Row 25
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(200, 20, '   Total', 'TLR', '', false, 0);
        PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
        PDF::MultiCell(120, 20, number_format($totaltax, 2), 'TLR', 'R', false);

        //Row 26
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(780, 20, 'We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.', 'TLR', 'C', false);

        //Row 27
        PDF::MultiCell(10, 30, '', 'LT', '', false, 0);
        PDF::MultiCell(395, 30, '', 'T', '', false, 0);
        PDF::MultiCell(10, 30, '', 'T', '', false, 0);
        PDF::MultiCell(175, 30, '', 'T', '', false, 0);
        PDF::MultiCell(10, 30, '', 'T', '', false, 0);
        PDF::MultiCell(170, 30, '', 'T', '', false, 0);
        PDF::MultiCell(10, 30, '', 'TR', '', false);

        //Row 28

        if ($params['params']['dataparams']['payor'] == '') {
            $payor = 'MARIETTA Y. JOSE';
        } else {
            $payor = $params['params']['dataparams']['payor'];
        }

        if ($params['params']['dataparams']['tin'] == '') {
            $tin = '908-572-911-000';
        } else {
            $tin = $params['params']['dataparams']['tin'];
        }

        if ($params['params']['dataparams']['position'] == '') {
            $position = 'AUDIT MANAGER';
        } else {
            $position =  $params['params']['dataparams']['position'];
        }

        $x = PDF::GetX();
        $y = PDF::GetY();

        PDF::Image(public_path() . '/images/homeworks/checked.png', $x + 50, $y - 35, 150, 100);  //x,y,widht,height


        PDF::SetFont($fontbold, '', 12);

        PDF::MultiCell(260, 30, $payor, 'LTB', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(260, 30, $tin, 'TB', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(260, 30, $position, 'TRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 9);


        // PDF::MultiCell(780, 30, ucwords($payor) . $tin . ucwords($position), 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(780, 30, 'Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

        //Row 29
        PDF::MultiCell(5, 25, '', 'LT', 'L', false, 0);
        PDF::MultiCell(775, 25, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'TRB', 'L', false, 0);
        PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
        PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
        PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
        PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
        PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
        PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);

        //Row 30
        PDF::MultiCell(780, 15, 'CONFORME:', 'LTRB', 'C', false);

        //Row 31
        PDF::MultiCell(780, 30, '', 'LTRB', '', false);

        //Row 32
        PDF::MultiCell(780, 30, 'Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

        //Row 29
        PDF::MultiCell(5, 30, '', 'LTB', 'L', false, 0);
        PDF::MultiCell(775, 30, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'TRB', 'L', false, 0);
        PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
        PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
        PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
        PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
        PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
        PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
        PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }




    public function PDF_METROBANK_CHECK_LAYOUT($data, $filters)
    {

        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';

        $count = 1;
        $page = 30;
        $cc = '';
        $cdate = '';
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }



        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
        // var_dump($qry);
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($data2); $i++) {
            // if($this->reporter->linecounter==$page){                  
            //   $str .= $this->reporter->page_break();
            // }
            $cc = $data2[$i]['cr'];
            // $cdate = date('m d Y', strtotime($data2[$i]['postdate']));
            $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";
            // PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

            PDF::setFontSpacing(5);
            PDF::SetFont($font, '', 11);
            PDF::MultiCell(500, 0, '', '', 'R', false, 0);
            PDF::MultiCell(50, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0, '500px');
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0, '570px');
            PDF::setFontSpacing(15);
            PDF::MultiCell(90, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0, '619px');

            PDF::MultiCell(30, 0, '', '', 'R', false, 1);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            PDF::setFontSpacing(5);
            PDF::MultiCell(500, 0, strtoupper($data[0]['clientname']), '', 'L', false, 0);
            // PDF::MultiCell(150, 0, 'test', '', 'L', false, 0);
            PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(720, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false);
        }


        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_BPI_CHECK_LAYOUT($data, $filters)
    {

        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';

        $count = 1;
        $page = 30;
        $cc = '';
        $cdate = '';
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }


        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($data2); $i++) {

            $cc = $data2[$i]['cr'];
            $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:10px; margin-right:-8px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

            PDF::setFontSpacing(5);
            PDF::SetFont($font, '', 11);
            // PDF::MultiCell(570, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0);
            // PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            // PDF::MultiCell(50, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            // PDF::MultiCell(30, 0, '', '', 'C');
            PDF::MultiCell(500, 0, '', '', 'R', false, 0);
            PDF::MultiCell(50, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0, '500px');
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0, '570px');
            PDF::setFontSpacing(15);
            PDF::MultiCell(90, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0, '619px');

            PDF::MultiCell(30, 0, '', '', 'R', false, 1);


            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::setFontSpacing(5);
            // PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(500, 0,  strtoupper($data[0]['clientname']), '', 'L', false, 0);
            // PDF::MultiCell(150, 0, '', '', 'L', false, 0);
            PDF::MultiCell(220, 0, (isset($cc) ?  number_format($cc, $decimal)  : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            // PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(720, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false);
        }
        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_EASTWEST_CHECK_LAYOUT($data, $filters)
    {

        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';

        $count = 1;
        $page = 30;
        $cc = '';
        $cdate = '';
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($data2); $i++) {

            $cc = $data2[$i]['cr'];
            $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

            PDF::setFontSpacing(5);
            PDF::SetFont($font, '', 11);
            // PDF::MultiCell(570, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0);
            // PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            // PDF::MultiCell(50, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            // PDF::MultiCell(30, 0, '', '', 'C');
            PDF::MultiCell(500, 0, '', '', 'R', false, 0);
            PDF::MultiCell(50, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0, '500px');
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0, '570px');
            PDF::setFontSpacing(15);
            PDF::MultiCell(90, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0, '619px');

            PDF::MultiCell(30, 0, '', '', 'R', false, 1);


            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::setFontSpacing(5);
            // PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(500, 0,  strtoupper($data[0]['clientname']), '', 'L', false, 0);
            // PDF::MultiCell(150, 0, '', '', 'L', false, 0);
            PDF::MultiCell(220, 0, (isset($cc) ?  number_format($cc, $decimal)  : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            // PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(720, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false);
        }
        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function rpt_default_header($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CASH/CHECK VOUCHER', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAYEE : ', '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '200', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('DATE : ', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS : ', '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col('REFERENCE # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTES : ', '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '725', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ACCOUNT NAME', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CHECK DETAILS', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REMARKS', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        return $str;
    }

    public function rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $str = '';
        $count = 30;
        $page = 30;

        $str .= $this->reporter->beginreport();
        $str .= $this->rpt_default_header($data, $filters);
        $totaldb = 0;
        $totalcr = 0;
        for ($i = 0; $i < count($data); $i++) {

            $debit = number_format($data[$i]['db'], $decimal);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = number_format($data[$i]['cr'], $decimal);
            $credit = $credit < 0 ? '-' : $credit;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['acnoname'], '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['checkno'], '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['pdate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($debit, '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($credit, '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['drem'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');

            $totaldb = $totaldb + $data[$i]['db'];
            $totalcr = $totalcr + $data[$i]['cr'];

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->rpt_default_header($data, $filters);
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
        $str .= $this->reporter->col(number_format($totaldb, $decimal), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col(number_format($totalcr, $decimal), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '265', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '270', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '265', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '265', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['received'], '270', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '265', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    } //end fn
    public function No_Excel_format($config)
    {
        return "
      <div style='position:relative;'>
        <div class='text-center' style='position:absolute; top:150px; left:400px;'>
          <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
          <br>
          <div style='font-size:32px; color:#1E1E1E'>INVALID OPTION</div>
        </div>
      </div>
    ";
    }
}
