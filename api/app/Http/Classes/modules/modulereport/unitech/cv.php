<?php

namespace App\Http\Classes\modules\modulereport\unitech;

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
        data_set($col1, 'checked.label', 'Corrected by');
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);
        data_set($col1, 'radioreporttype.label', 'Print Cash/Check Voucher');
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'VOUCHER', 'value' => '0', 'color' => 'blue'],
                ['label' => 'VOUCHER 2 (Shooting)', 'value' => '9', 'color' => 'blue'],
                ['label' => 'CHECK', 'value' => '1', 'color' => 'blue'],
                ['label' => 'METROBANK CHECK', 'value' => '3', 'color' => 'blue'],
                ['label' => 'BPI CHECK', 'value' => '4', 'color' => 'blue'],
                ['label' => 'Eastwest CHECK', 'value' => '5', 'color' => 'blue'],
                ['label' => 'AUB CHECK', 'value' => '6', 'color' => 'blue'],
                ['label' => 'BDO CHECK', 'value' => '7', 'color' => 'blue'],
                ['label' => 'PBB CHECK', 'value' => '8', 'color' => 'blue'],
                ['label' => 'BIR Form 2307', 'value' => '2', 'color' => 'blue']
            ]
        );

        return array('col1' => $col1);
    }

    public function reportplotting($config, $data)
    {
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
            case '6':
                $str = $this->PDF_AUB_CHECK_LAYOUT($data, $config);
                break;
            case '7':
                $str = $this->PDF_BDO_CHECK_LAYOUT($data, $config);
                break;
            case '8':
                $str = $this->PDF_PBB_CHECK_LAYOUT($data, $config);
                break;
            case 9:
                $str = $this->PDF_CV_VOUCHER2($data, $config);
                break;
        }

        return $str;
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
        'PDFM' as print,
        '' as prepared,
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
                            detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno
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
                            detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno
                            from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                            left join client on client.clientid=head.clientid)left join coa on coa.acnoid=detail.acnoid
                            left join glhead as head2 on head2.trno = detail.refx
                            left join cntnum on cntnum.trno=head.trno
                            LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
                            where head.doc='cv' and head.trno ='$trno'";
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
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '');
        switch ($params['params']['companyid']) {
            case 10:
            case 12:
                break;
            default:
                $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
                break;
        }
        PDF::MultiCell(0, 0, "\n\n");
        $this->reportheader->getheader($params);

        PDF::MultiCell(0, 0, "");

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(300, 5, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(300, 5, "Docno #: ", '', 'R', false, 0, '',  '');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 5, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(300, 5, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(300, 5, "", '', 'R', false, 0, '',  '');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 5, '', 'T', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 5, 'PAYEE : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(425, 5, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(85, 5, 'DATE:', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 5, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 5, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(425, 5, '', 'T', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(85, 5, '', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 5, '', 'T', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 5, 'ADDRESS : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(425, 5, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(85, 5, 'REFERENCE # :', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 5, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 5, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(425, 5, '', 'T', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(85, 5, '', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 5, '', 'T', 'R', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 5, 'NOTES : ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(630, 5, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false);

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(70, 5, '', '', 'L', false, 0);
        PDF::MultiCell(630, 5, '', 'T', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(70, 0, 'ACCT #', '', 'L', false, 0);
        PDF::MultiCell(180, 0, 'ACCOUNT NAME', '', 'C', false, 0);
        PDF::MultiCell(100, 0, 'CHECK DETAILS', '', 'C', false, 0);
        PDF::MultiCell(75, 0, 'DATE', '', 'C', false, 0);
        PDF::MultiCell(85, 0, 'DEBIT', '', 'R', false, 0);
        PDF::MultiCell(85, 0, 'CREDIT', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'C', false, 0);
        PDF::MultiCell(110, 0, 'REMARKS', '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
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
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->PDF_default_header($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(705, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;

                $acno =  $data[$i]['acno'];
                $acnonamedescs = $data[$i]['acnoname'];
                $checkno = $data[$i]['checkno'];
                $pdate = $data[$i]['pdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $debit = $debit < 0 ? '-' : $debit;

                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $credit = $credit < 0 ? '-' : $credit;
                $drem = $data[$i]['drem'];

                $arr_acno = $this->reporter->fixcolumn([$acno], '15', 0);
                $arr_checkno = $this->reporter->fixcolumn([$checkno], '15', 0);
                $arr_acnonamedescs = $this->reporter->fixcolumn([$acnonamedescs], '25', 0);
                $arr_pdate = $this->reporter->fixcolumn([$pdate], '13', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);
                $arr_drem = $this->reporter->fixcolumn([$drem], '13', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnonamedescs, $arr_checkno, $arr_pdate, $arr_debit, $arr_credit, $arr_drem]);




                // $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnonamedescs, $arr_checkno, $arr_pdate, $arr_debit, $arr_credit, $arr_drem]);

                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(180, 15, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_checkno[$r]) ? $arr_checkno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(75, 15, ' ' . (isset($arr_pdate[$r]) ? $arr_pdate[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(85, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(85, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_drem[$r]) ? $arr_drem[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];


                if (PDF::getY() > 900) {
                    $this->PDF_default_header($params, $data);
                }
            }
        }


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(705, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(705, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(425, 5, 'GRAND TOTAL : ', '', 'R', false, 0);
        PDF::MultiCell(85, 5, number_format($totaldb, $decimal), '', 'R', false, 0);
        PDF::MultiCell(85, 5, number_format($totalcr, $decimal), '', 'R', false);

        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($font, '', 12);


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');


        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


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

            PDF::MultiCell(120, 5, '', '', 'C', false);

            PDF::MultiCell(120, 5, '', '', 'C', false, 0);

            if ($params['params']['dataparams']['payor'] == '') {
                PDF::MultiCell(150, 5, $data[0]['clientname'], '', 'L', false, 0);
            } else {
                PDF::MultiCell(150, 5, $params['params']['dataparams']['payor'], '', 'L', false, 0);
            }
            PDF::MultiCell(420, 5, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'C', false, 0);


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
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
        PDF::MultiCell(520, 18, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'LTBR', 'L', false, 0);
        PDF::MultiCell(10, 25, '', '', 'C', false);

        //Row 7
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(50, 15, '3', 'LT', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(730, 15, "Payee's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'TR', 'L', false);

        //Row 8
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::MultiCell(720, 18, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'LTRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);

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
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::MultiCell(630, 18, (isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), 'LTRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", '', 'L', false, 0);
        PDF::MultiCell(80, 18, (isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), 'LTRB', 'C', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);

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
        PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
        PDF::MultiCell(520, 18, (isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), 'LTBR', 'L', false, 0);
        PDF::MultiCell(10, 25, '', '', 'C', false);

        //Row 15
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(780, 25, '', 'LR', '', false, 1, 10, 340);
        PDF::MultiCell(50, 15, '7', 'L', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(730, 15, "Payor's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'R', 'L', false);

        //Row 16
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::MultiCell(720, 18, (isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : ''), 'LTRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);


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

        //Row 18
        PDF::MultiCell(50, 18, '', 'L', '', false, 0);
        PDF::MultiCell(630, 18, (isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), 'LTRB', 'L', false, 0);
        PDF::MultiCell(10, 18, "", '', 'L', false, 0);
        PDF::MultiCell(80, 18, (isset($data['head'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), 'LTRB', 'C', false, 0);
        PDF::MultiCell(10, 18, "", 'R', 'L', false);


        //Row 13
        PDF::MultiCell(780, 1, '', 'LRB', '', false, 1, 10, 425);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(780, 18, 'Part III - Details of Monthly Income Payments and Taxes Withheld', 'LTRB', 'C', false);

        //Row 14
        PDF::MultiCell(200, 20, '', 'LTR', 'C', false, 0, 10, 457);
        PDF::MultiCell(80, 20, '', 'LTR', 'C', false, 0, 210, 457);
        PDF::MultiCell(380, 20, 'AMOUNT OF INCOME PAYMENTS', 'LTR', 'C', false, 0, 290, 457);
        PDF::MultiCell(120, 20, '', 'LTR', 'C', false, 1, 670, 457);

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
        PDF::MultiCell(780, 10, '', 'T', '', false, 1, 10, 500);

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
            PDF::MultiCell(200, $max_height, $data['res'][$a]['ewtdesc'], 'LRB', '', false, 0);
            PDF::MultiCell(80, $max_height, $key, 'LRB', '', false, 0);

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
            $payor = $data['head'][0]['payorcompname'] . ' / ';
        } else {
            $payor = $params['params']['dataparams']['payor'] . ' / ';
        }

        if ($params['params']['dataparams']['tin'] == '') {
            $tin = $data['head'][0]['payortin'];
        } else {
            $tin = $params['params']['dataparams']['tin'];
        }

        if ($params['params']['dataparams']['position'] == '') {
            $position = '';
        } else {
            $position = ' / ' . $params['params']['dataparams']['position'];
        }


        PDF::MultiCell(780, 30, ucwords($payor) . $tin . ucwords($position), 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(780, 30, 'Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

        //Row 29
        PDF::MultiCell(780, 25, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
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
        PDF::MultiCell(780, 30, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
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
                where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and detail.cr<>0
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate
                UNION ALL
                select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from gldetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . "
                and left(coa.alias,2) = 'CB'  and detail.cr<>0
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
            PDF::MultiCell(600, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0);
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(50, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C');

            PDF::MultiCell(720, 5, "\n", '', 'L', false);


            PDF::MultiCell(350, 0, strtoupper($data[0]['clientname']), '', 'L', false, 0);
            PDF::MultiCell(150, 0, '', '', 'L', false, 0);
            PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(720, 0, $this->ftNumberToWordsConverter($dd), '', 'L', false);
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
            PDF::MultiCell(570, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0);
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(50, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(30, 0, '', '', 'C');

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(320, 0, '**' . strtoupper($data[0]['clientname']) . '**', '', 'L', false, 0);
            PDF::MultiCell(150, 0, '', '', 'L', false, 0);
            PDF::MultiCell(220, 0, (isset($cc) ? '**' . number_format($cc, $decimal) . '**' : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(690, 0, '**' . $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY**', '', 'L', false);
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
            PDF::MultiCell(570, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0);
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(50, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(30, 0, '', '', 'C');

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(320, 0, '**' . strtoupper($data[0]['clientname']) . '**', '', 'L', false, 0);
            PDF::MultiCell(150, 0, '', '', 'L', false, 0);
            PDF::MultiCell(220, 0, (isset($cc) ? '**' . number_format($cc, $decimal) . '**' : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            PDF::MultiCell(690, 0, '**' . $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY**', '', 'L', false);
        }
        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_AUB_CHECK_LAYOUT($data, $filters)
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
        PDF::SetMargins(80, 60);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }



        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from ladetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and detail.cr<>0
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate
                UNION ALL
                select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from gldetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . "
                and left(coa.alias,2) = 'CB'  and detail.cr<>0
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate

                ";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
        PDF::MultiCell(0, 20, "");
        for ($i = 0; $i < count($data2); $i++) {
            $cc = $data2[$i]['cr'];
            $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";
            PDF::setFontSpacing(15);
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(590, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0, -20);
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'R', false, 0);
            PDF::MultiCell(90, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'R');

            PDF::MultiCell(720, 5, "\n\n", '', 'L', false);
            PDF::setFontSpacing(2);
            if ($filters['params']['dataparams']['payor'] == '') {
                PDF::MultiCell(460, 0, strtoupper($data[0]['clientname']), '', 'L', false, 0, 100);
            } else {
                PDF::MultiCell(460, 0, $filters['params']['dataparams']['payor'], '', 'L', false, 100);
            }

            // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
            PDF::MultiCell(270, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(720, 0, $this->ftNumberToWordsConverter($dd), '', 'L', false);
        }


        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_BDO_CHECK_LAYOUT($data, $filters)
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
        PDF::SetMargins(80, 60);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }



        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from ladetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and detail.cr<>0
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate
                UNION ALL
                select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from gldetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . "
                and left(coa.alias,2) = 'CB'  and detail.cr<>0
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate

                ";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
        PDF::MultiCell(0, 20, "");
        for ($i = 0; $i < count($data2); $i++) {
            $cc = $data2[$i]['cr'];
            $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";
            PDF::setFontSpacing(13);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(580, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0, 5);
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(95, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C');

            PDF::MultiCell(720, 5, "\n\n", '', 'L', false);
            PDF::setFontSpacing(2);
            if ($filters['params']['dataparams']['payor'] == '') {
                PDF::MultiCell(460, 0, strtoupper($data[0]['clientname']), '', 'L', false, 0, 100);
            } else {
                PDF::MultiCell(460, 0, $filters['params']['dataparams']['payor'], '', 'L', false, 0, 100);
            }

            PDF::MultiCell(270, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(720, 0, $this->ftNumberToWordsConverter($dd), '', 'L', false, 1, 80, 95);
        }


        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_PBB_CHECK_LAYOUT($data, $filters)
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
        PDF::SetMargins(80, 60);

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }



        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from ladetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and detail.cr<>0
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate
                UNION ALL
                select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from gldetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . "
                and left(coa.alias,2) = 'CB'  and detail.cr<>0
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate

                ";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
        PDF::MultiCell(0, 20, "");
        for ($i = 0; $i < count($data2); $i++) {
            $cc = $data2[$i]['cr'];
            $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";
            PDF::setFontSpacing(13);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(580, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0, 5);
            PDF::MultiCell(50, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(95, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C');

            PDF::MultiCell(720, 5, "\n\n", '', 'L', false);
            PDF::setFontSpacing(2);
            if ($filters['params']['dataparams']['payor'] == '') {
                PDF::MultiCell(460, 0, strtoupper($data[0]['clientname']), '', 'L', false, 0, 100);
            } else {
                PDF::MultiCell(460, 0, $filters['params']['dataparams']['payor'], '', 'L', false, 0, 100);
            }

            PDF::MultiCell(270, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(720, 0, $this->ftNumberToWordsConverter($dd), '', 'L', false, 1, 80, 95);
        }


        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn


    ///CV printout shooting

    public function PDF_header_VOUCHER2($params, $data)
    {

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        $fontsize13 = 13;

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
        PDF::SetMargins(40, 40);

        // PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, 610, 40);
        PDF::MultiCell(150, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, 610, 70);

        PDF::MultiCell(330, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] . ' Kimberly Rose Dalisay Soriano' : ''), '', 'L', false, 1, 170, 28);
    }

    public function PDF_CV_VOUCHER2($data, $params)
    {
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
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->PDF_header_VOUCHER2($params, $data);

        PDF::MultiCell(0, 95, "");

        $totaldb = 0;
        $totalcr = 0;
        $remarks = "";

        for ($i = 0; $i < count($data); $i++) {

            $totaldb = $totaldb + $data[$i]['db'];
            $totalcr = $totalcr + $data[$i]['cr'];

            if ($this->reporter->linecounter == $page) {

                PDF::MultiCell(720, 0, "", "T");
                $page = $page + $count;
            }
        }

        if ($remarks == $data[0]['rem']) {
            $remarks = "";
        } else {
            $remarks = $data[0]['rem'];
        }

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 1);
        PDF::MultiCell(510, 0, $remarks, '', 'L', false, 0, 65, '', false, 1);
        PDF::MultiCell(155, 75, number_format($totaldb, 2), '', 'R', false);

        PDF::MultiCell(0, 145, "");

        $totaldb = 0;
        $totalcr = 0;
        $acname = "";

        for ($i = 0; $i < count($data); $i++) {
            $debit = number_format($data[$i]['db'], 2);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = number_format($data[$i]['cr'], 2);
            $credit = $credit < 0 ? '-' : $credit;
            if ($acname == $data[$i]['acnoname']) {
                $acname = "";
            } else {
                $acname = $data[$i]['acnoname'];
            }
            PDF::SetFont($font, '', 11);
            PDF::MultiCell(250, 0, $acname, '', 'L', false, 0, 50, '');

            PDF::MultiCell(80, 0, $debit, '', 'R', false, 0, 245);
            PDF::MultiCell(80, 0, $credit, '', 'R', false, 1, 330);
        }

        $qry = "select sum(detail.cr) as cr,group_concat(coa.acnoname,' ') as bank,group_concat(detail.checkno,' ') as checkno
                from ladetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and detail.cr<>0
                group by detail.checkno,coa.acno
                UNION ALL
                select sum(detail.cr) as cr,group_concat(coa.acnoname,' ') as bank,group_concat(detail.checkno,' ') as checkno
                from gldetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr<>0
                group by detail.checkno,coa.acno";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
        $cc = $data2[0]['cr'];

        $dd = number_format((float)$cc, 2, '.', '');
        PDF::MultiCell(220, 0, $this->ftNumberToWordsConverter($dd), '', 'L', false, 1, 510, 342);

        PDF::MultiCell(100, 0, $data2[0]['bank'], '', 'L', false, 1, 495, 435);
        PDF::MultiCell(100, 0, $data2[0]['checkno'], '', 'L', false, 1, 658, 435);

        PDF::MultiCell(220, 0,  $params['params']['dataparams']['received'], '', 'C', false, 1, 500, 495);

        PDF::MultiCell(250, 0,  $params['params']['dataparams']['prepared'], '', 'C', false, 0, 25, 549);
        PDF::MultiCell(240, 0,  $params['params']['dataparams']['checked'], '', 'C', false, 0);
        PDF::MultiCell(260, 0,  $params['params']['dataparams']['approved'], '', 'C', false, 1);

        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function ftNumberToWordsConverter($number)
    {
        $numberwords = $this->ftNumberToWordsBuilder($number);

        if (strpos($numberwords, "/") == false) {
            $numberwords .= " Pesos Only";
        } else {
            $numberwords = str_replace(" AND ", " Pesos And ", $numberwords);
        } //end if

        return $numberwords;
    } //end function convert to words

    public function ftNumberToWordsBuilder($number)
    {
        if ($number == 0) {
            return 'Zero';
        } else {
            $hyphen      = ' ';
            $conjunction = ' ';
            $separator   = ' ';
            $negative    = 'negative ';
            $decimal     = ' and ';
            $dictionary  = array(
                0                   => '',
                1                   => 'One',
                2                   => 'Two',
                3                   => 'Three',
                4                   => 'Four',
                5                   => 'Five',
                6                   => 'Six',
                7                   => 'Seven',
                8                   => 'Eight',
                9                   => 'Nine',
                10                  => 'Ten',
                11                  => 'Eleven',
                12                  => 'Twelve',
                13                  => 'Thirteen',
                14                  => 'Fourteen',
                15                  => 'Fifteen',
                16                  => 'Sixteen',
                17                  => 'Seventeen',
                18                  => 'Eighteen',
                19                  => 'Nineteen',
                20                  => 'Twenty',
                30                  => 'Thirty',
                40                  => 'Forty',
                50                  => 'Fifty',
                60                  => 'Sixty',
                70                  => 'Seventy',
                80                  => 'Eighty',
                90                  => 'Ninety',
                100                 => 'Hundred',
                1000                => 'Thousand',
                1000000             => 'Million',
                1000000000          => 'Billion',
                1000000000000       => 'Trillion',
                1000000000000000    => 'Quadrillion',
                1000000000000000000 => 'Quintillion'
            );

            if (!is_numeric($number)) {
                return false;
            } //end if

            if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
                // overflow
                return false;
            } //end if

            if ($number < 0) {
                return $negative . $this->ftNumberToWordsBuilder(abs($number));
            } //end if

            $string = $fraction = null;

            if (strpos($number, '.') !== false) {
                $fractionvalues = explode('.', $number);
                if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
                    list($number, $fraction) = explode('.', $number);
                } //end if
            } //end if

            switch (true) {
                case $number < 21:
                    $string = $dictionary[$number];
                    break;

                case $number < 100:
                    $tens   = ((int) ($number / 10)) * 10;
                    $units  = $number % 10;
                    $string = $dictionary[$tens];
                    if ($units) {
                        $string .= $hyphen . $dictionary[$units];
                    } //end if
                    break;

                case $number < 1000:
                    $hundreds  = $number / 100;
                    $remainder = $number % 100;
                    $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                    if ($remainder) {
                        $string .= $conjunction . $this->ftNumberToWordsBuilder($remainder);
                    } //end if
                    break;

                default:
                    $baseUnit = pow(1000, floor(log($number, 1000)));
                    $numBaseUnits = (int) ($number / $baseUnit);
                    $remainder = $number % $baseUnit;
                    $string = $this->ftNumberToWordsBuilder($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                    if ($remainder) {
                        $string .= $remainder < 100 ? $conjunction : $separator;
                        $string .= $this->ftNumberToWordsBuilder($remainder);
                    } //end if
                    break;
            } //end switch
            if (null !== $fraction && is_numeric($fraction)) {

                $string .= $decimal . ' ' . $fraction .  '/100';
                $words = array();
                $string .= implode(' ', $words);
            } //end if


            return $string;
        } //end
    } //end fn

}
