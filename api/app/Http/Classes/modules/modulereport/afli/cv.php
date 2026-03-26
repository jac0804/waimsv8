<?php

namespace App\Http\Classes\modules\modulereport\afli;

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
    private $btnClass;
    private $fieldClass;
    private $tabClass;
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
        $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'approved2', 'received', 'checked', 'payor', 'tin', 'position', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);
        data_set($col1, 'radioreporttype.label', 'Voucher');
        data_set($col1, 'checked.label', 'Posted by');
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'VOUCHER', 'value' => '0', 'color' => 'blue'],
                //['label' => 'CHECK', 'value' => '1', 'color' => 'blue'],
                ['label' => 'BIR Form 2307', 'value' => '2', 'color' => 'blue'],
                //['label' => 'METROBANK CHECK', 'value' => '3', 'color' => 'blue'],
                ['label' => 'CHECK', 'value' => '4', 'color' => 'blue'],
                //['label' => 'Eastwest CHECK', 'value' => '5', 'color' => 'blue'],
                ['label' => 'Liquidation Format', 'value' => '6', 'color' => 'blue']

            ]
        );

        return array('col1' => $col1);
    }

    public function reportplotting($config, $data)
    {
        // if ($config['params']['dataparams']['print'] == "default") {
        //     switch ($config['params']['dataparams']['reporttype']) {
        //         case '0': // VOUCHER
        //             $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
        //             break;
        //     }
        // } else if ($config['params']['dataparams']['print'] == "PDFM") {
        //     switch ($config['params']['dataparams']['reporttype']) {
        //         case '0': // VOUCHER
        //             $str = $this->PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
        //             break;
        //     }
        // }
        if ($config['params']['dataparams']['print'] == "default") {
            switch ($config['params']['dataparams']['reporttype']) {
                case '0': // VOUCHER
                    $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
                    break;
                case '1':
                    $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT2($data, $config);
                    break;
                case '2':
                    $str = $this->rpt_CV_WTAXREPORT($data, $config);
                    break;
                case '3':
                    $str = $this->METROBANK_CHECK_LAYOUT($data, $config);
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
                case '6':
                    $str = $this->PDF_LIQUIDATIONFORMAT_LAYOUT($data, $config);
                    break;
            }
        }

        return $str;
    }

    public function reportparamsdata($config)
    {
        $username = $config['params']['user'];
        $signatories = $this->othersClass->getSignatories($config);
        $prepared = '';
        $approved = '';
        $approved2 = '';
        $received =  '';
        $checked =  '';
        $payor =  '';
        $position =  '';

        $tin =  '';

        foreach ($signatories as $key => $value) {
            switch ($value->fieldname) {
                case 'prepared':
                    $prepared = $value->fieldvalue;
                    break;
                case 'approved':
                    $approved = $value->fieldvalue;
                    break;
                case 'approved2':
                    $approved2 = $value->fieldvalue;
                    break;
                case 'received':
                    $received = $value->fieldvalue;
                    break;
                case 'checked':
                    $checked = $value->fieldvalue;
                    break;
                case 'payor':
                    $payor = $value->fieldvalue;
                    break;
                case 'position':
                    $position = $value->fieldvalue;
                    break;
                case 'tin':
                    $tin = $value->fieldvalue;
                    break;
            }
        }

        if ($prepared == '') {
            $prepared = $this->coreFunctions->datareader("select name as value from useraccess where username = '" . $username . "'");
        }

        return $this->coreFunctions->opentable(
            "select 
        'PDFM' as print,
        '$prepared' as prepared,
        '$approved' as approved,
        '$approved2' as approved2,
        '$received' as received,
        '$checked' as checked,
        '$payor' as payor,
        '$position' as position,
        '$tin' as tin,
        '0' as reporttype "
        );
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];
        // $query = "select vcdateid,trno, docno,dateid,client,clientname,address, acno,acnoname,sum(db) as db,sum(cr)as cr,rem from (
        //             select DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as vcdateid,head.trno,
        //             date(head.dateid) as dateid,client.client, head.clientname, head.address,coa.acno,head.rem,
        //             coa.acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,concat(cntnum.bref,cntnum.seq) as docno
        //             from ((lahead as head
        //             left join ladetail as detail on detail.trno=head.trno)
        //             left join client on client.client=head.client)
        //             left join coa on coa.acnoid=detail.acnoid
        //             left join glhead as head2 on head2.trno = detail.refx
        //             left join cntnum on cntnum.trno=head.trno
        //             where head.doc='cv' and head.trno ='$trno'
        //             union all
        //             select
        //            DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as vcdateid ,head.trno, 
        //             date(head.dateid) as dateid,client.client, head.clientname, head.address, coa.acno,head.rem,
        //             coa.acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,concat(cntnum.bref,cntnum.seq) as docno
        //             from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
        //             left join client on client.clientid=head.clientid)left join coa on coa.acnoid=detail.acnoid
        //             left join glhead as head2 on head2.trno = detail.refx
        //             left join cntnum on cntnum.trno=head.trno
        //             LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
        //             where head.doc='cv' and head.trno ='$trno'
        //             ) as w group by acno,acnoname,vcdateid,docno,trno,dateid,client,clientname,address,acno,acnoname,rem";
        // $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        switch ($filters['params']['dataparams']['reporttype']) {
            case 2:
                $query = "select * from(
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        head.address,head.rem,detail.rem as drem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        detail.db, detail.cr, detail.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname,head.acctname
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        left join coa on coa.acnoid=detail.acnoid
        where head.doc='cv' and head.trno ='$trno' 
        union all
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        head.address,head.rem,detail.rem as drem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref, detail.postdate,
        detail.db, detail.cr, dclient.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname,head.acctname
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join client as dclient on dclient.clientid=detail.clientid
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        where head.doc='cv' and head.trno ='$trno' )
        as tbl ";

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
            case '0':
            case '6':
                $query = "select vcdateid,trno, docno,dateid,client,clientname,address, acno,acnoname,sum(db) as db,sum(cr)as cr,rem,alias,cbamt,dptrno,lname,fname,left(mname,1) as mname,acctname from (
                    select DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as vcdateid,head.trno,
                    date(head.dateid) as dateid,client.client, head.clientname, head.address,coa.acno,head.rem,head.amount as cbamt,
                    coa.acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,concat(cntnum.bref,cntnum.seq) as docno,coa.alias,cntnum.dptrno,cl.lname,cl.fname,cl.mname,head.acctname
                    from ((lahead as head
                    left join ladetail as detail on detail.trno=head.trno)
                    left join client on client.client=head.client)
                     left join clientinfo as cl on cl.clientid = client.clientid
                    left join coa on coa.acnoid=detail.acnoid
                    left join glhead as head2 on head2.trno = detail.refx
                    left join cntnum on cntnum.trno=head.trno
                    where head.doc='cv' and head.trno ='$trno'
                    union all
                    select DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as vcdateid ,head.trno, 
                    date(head.dateid) as dateid,client.client, head.clientname, head.address, coa.acno,head.rem,head.amount as cbamt,
                    coa.acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,concat(cntnum.bref,cntnum.seq) as docno,coa.alias,cntnum.dptrno,cl.lname,cl.fname,cl.mname,head.acctname
                    from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                    left join client on client.clientid=head.clientid)left join coa on coa.acnoid=detail.acnoid
                    left join glhead as head2 on head2.trno = detail.refx
                     left join clientinfo as cl on cl.clientid = client.clientid
                    left join cntnum on cntnum.trno=head.trno
                    LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
                    where head.doc='cv' and head.trno ='$trno'
                    ) as w group by acno,acnoname,vcdateid,docno,trno,dateid,client,clientname,address,acno,acnoname,rem,alias,cbamt,dptrno,lname,fname,mname,acctname
                    order by case when left(alias,2) ='CB' then 1 else 0 end, sum(db) desc, sum(cr) desc";
                $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
                break;

            default:

                $query = "select concat(cntnum.bref,cntnum.seq) as cvno,ifnull(DATE_FORMAT(head.dateid,'%Y-%m-%d'),'') as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
                            DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as vcdateid ,detail.ref,head.trno, head.docno, 
                            date(head.dateid) as dateid, abs(detail.db - detail.cr) as amount,head.amount as cbamt,
                            date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
                            client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
                            coa.acnoname, round(detail.db,2) as db,round(detail.cr,2) as cr, 
                            detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,cl.lname,cl.fname,left(cl.mname,1) as mname,head.acctname
                            from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                            left join client on client.client=head.client)left join coa on coa.acnoid=detail.acnoid
                            left join clientinfo as cl on cl.clientid = client.clientid
                            left join glhead as head2 on head2.trno = detail.refx
                            left join cntnum on cntnum.trno=head.trno
                            where head.doc='cv' and head.trno ='$trno'
                            union all
                            select concat(cntnum.bref,cntnum.seq) as cvno, ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%Y-%m-%d')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
                            DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as vcdateid ,detail.ref,head.trno, head.docno, 
                            date(head.dateid) as dateid,  abs(detail.db - detail.cr) as amount,head.amount as cbamt,
                            date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
                            client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
                            coa.acnoname, round(detail.db,2) as db,round(detail.cr,2) as cr, 
                            detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,cl.lname,cl.fname,left(cl.mname,1) as mname,head.acctname
                            from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                            left join client on client.clientid=head.clientid)left join coa on coa.acnoid=detail.acnoid
                            left join glhead as head2 on head2.trno = detail.refx
                            left join clientinfo as cl on cl.clientid = client.clientid
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
        $qry = "select name,address,tel from center where code = '" . $center . "'";

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

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 13);

        PDF::MultiCell(720, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(650, 5, '', '', 'C', false, 0);
        PDF::MultiCell(70, 5, 'CV#. ' . $data[0]['docno'], '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 16);
        PDF::MultiCell(720, 5, strtoupper('Check Voucher'), '', 'C', false, 1);


        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(300, 5, 'ACCOUNT TITLE ', 'TL', 'C', false, 0);
        PDF::MultiCell(210, 5, 'DEBIT', 'TL', 'C', false, 0);
        PDF::MultiCell(210, 5, 'CREDIT', 'TLR', 'C', false, 1);



        PDF::SetFont($font, '', 5);

        PDF::MultiCell(300, 5, ' ', 'TL', 'C', false, 0);
        PDF::MultiCell(210, 5, '', 'TL', 'C', false, 0);
        PDF::MultiCell(210, 5, '', 'TLR', 'C', false, 1);
    }

    public function PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $params)
    {
        $trno = $params['params']['dataid'];
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $decimal = $this->companysetup->getdecimal('currency',  $params['params']);

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->PDF_default_header($params, $data);
        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(620, 0, '', '');
        $countarr = 0;
        $totaldb = 0;
        $totalcr = 0;

        if (!empty($data)) {


            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;

                $acnonamedescs = trim($data[$i]['acnoname']);

                $debit = number_format($data[$i]['db'], $decimalcurr);
                $debit = $debit <= 0 ? '-' : $debit;
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $credit = $credit <= 0 ? '-' : $credit;


                $arr_acnonamedescs = $this->reporter->fixcolumn([$acnonamedescs], '50', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acnonamedescs, $arr_debit, $arr_credit]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(300, 15, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(210, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), 'L', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(210, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), 'LR', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                $totaldb = $totaldb + $data[$i]['db'];
                $totalcr = $totalcr + $data[$i]['cr'];

                if (PDF::getY() > 900) {
                    $this->PDF_default_header($params, $data);
                }
            }
        }



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(30, 5, '  ', 'TL', 'L', false, 0);
        PDF::MultiCell(270, 5, 'TOTAL  ', 'T', 'L', false, 0);
        PDF::MultiCell(210, 5, number_format($totaldb, 2), 'TL', 'R', false, 0);
        PDF::MultiCell(210, 5, number_format($totalcr, 2), 'TLR', 'R', false, 1);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 5, 'EXPLANATION : ', 'TL', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(630, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'T', 'B', false, 1);


        PDF::SetFont($font, '', $fontsize);

        //430
        PDF::MultiCell(180, 0, 'Prepared By: ', 'TL', 'L', false, 0);
        PDF::MultiCell(180, 0, 'Approved By: ', 'TL', 'L', false, 0);
        PDF::MultiCell(180, 0, 'Approved By: ', 'TL', 'L', false, 0);
        PDF::MultiCell(180, 0, 'Posted By: ', 'TLR', 'L', false, 1);

        PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'BL', 'L', false, 0);
        PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'BL', 'L', false, 0); //
        PDF::MultiCell(180, 0, $params['params']['dataparams']['approved2'], 'BL', 'L', false, 0);
        PDF::MultiCell(180, 0, $params['params']['dataparams']['checked'], 'LRB', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(50, 0, 'Paid To: ', '', 'L', false, 0);
        
        $clname =$data[0]['clientname'];
        $acctname =$data[0]['acctname'];

        if($acctname !=""){
            $clname = $acctname;
        }else{
            if( $data[0]['fname'].$data[0]['mname'].$data[0]['lname'] != ''){
                $clname = $data[0]['fname'].' '.$data[0]['lname'];
                // if ($data[0]['mname'] != ''){
                //     $clname = $data[0]['fname'].' '.$data[0]['mname'].'. '.$data[0]['lname'];
                // }else{
                //     $clname = $data[0]['fname'].' '.$data[0]['lname'];
                // }
            }
        }
        


        PDF::MultiCell(310, 0, $clname, '', 'L', false, 0);
        PDF::MultiCell(60, 0, 'CV Date: ', '', 'L', false, 0);
        PDF::MultiCell(300, 0,  date_format(date_create($data[0]['dateid']), 'm/d/Y'), '', 'L', false, 1);


        $qry = "
        select acnoname,amount,checkno,pdate from ( 
        select  coa.acnoname,abs(detail.db - detail.cr) as amount, left(coa.acno,2) as alias,detail.checkno,
        date(detail.postdate) as pdate from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        where head.doc = 'cv' and left(coa.alias,2) = 'cb' and head.trno = " . $trno . " 
        union all
        select  coa.acnoname,abs(detail.db - detail.cr) as amount, left(coa.acno,2) as alias,detail.checkno,
        date(detail.postdate) as pdate from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        where head.doc = 'cv' and left(coa.alias,2) = 'cb' and head.trno = " . $trno . " ) as v group by checkno,amount,acnoname,pdate";
        $cbdata = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        PDF::MultiCell(0, 0, "\n");
        $amounts = number_format((float)$cbdata[0]['amount'], 2, '.', '');

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(105, 0, 'Amount in Words: ', '', 'L', false, 0);

        PDF::SetFont('dejavusans', '', 11);
        if (strpos($cbdata[0]['amount'], '.') !== false) {
            $fractionvalues = explode('.', $cbdata[0]['amount']);
            if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
                PDF::MultiCell(615, 0, $this->ftNumberToWordsConverter($cbdata[0]['amount'],false,'',true) .' ' . '( ₱ ' . number_format($amounts, 2) . ' )', '', 'L', false, 1); 
            }else{
                PDF::MultiCell(615, 0, $this->ftNumberToWordsConverter($cbdata[0]['amount'],false,'',true) . ' ' . 'ONLY' . ' ' . '( ₱ ' . number_format($amounts, 2) . ' )', '', 'L', false, 1);  
            } //end if
        }else{
            PDF::MultiCell(615, 0, $this->ftNumberToWordsConverter($cbdata[0]['amount'],false,'',true) . ' ' . 'ONLY' . ' ' . '( ₱ ' . number_format($amounts, 2) . ' )', '', 'L', false, 1);
        }//end if
        
        //PDF::MultiCell(615, 0, $this->reporter->ftNumberToWordsConverter($amounts,false,'',false). ' ' . 'ONLY' . ' ' . '( ₱ ' . number_format($amounts, 2) . ' )', '', 'L', false, 1);


        if (count($cbdata) == 0) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(0, 0, "\n");

            PDF::MultiCell(80, 0, 'BANK :', '', 'L', false, 0);
            PDF::MultiCell(640, 0, '', '', 'L', false, 1);


            PDF::MultiCell(80, 0, 'CHECK :', '', 'L', false, 0);
            PDF::MultiCell(640, 0, '', '', 'L', false, 1);

            PDF::MultiCell(0, 0, "\n");
            PDF::MultiCell(80, 0, 'DATE :', '', 'L', false, 0);
            PDF::MultiCell(640, 0, '', '', 'L', false, 1);
        } else {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(0, 0, "\n");
            PDF::MultiCell(80, 0, 'BANK :', '', 'L', false, 0);
            PDF::MultiCell(640, 0, '' . (isset($cbdata[0]['acnoname']) ? $cbdata[0]['acnoname'] : ""), '', 'L', false, 1);

            PDF::MultiCell(0, 0, "\n");

            PDF::MultiCell(80, 0, 'CHECK :', '', 'L', false, 0);
            PDF::MultiCell(640, 0, '' . (isset($cbdata[0]['checkno']) ? $cbdata[0]['checkno'] : ""), '', 'L', false, 1);

            PDF::MultiCell(0, 0, "\n");
            PDF::MultiCell(80, 0, 'DATE :', '', 'L', false, 0);
            PDF::MultiCell(640, 0, '' . (isset($cbdata[0]['pdate']) ? date_format(date_create($cbdata[0]['pdate']), 'm/d/Y') : ""), '', 'L', false, 1);
        }
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(85, 0, 'Received from ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(230, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(65, 0, ' the sum of', '', 'L', false, 0);
        PDF::MultiCell(340, 0, 'PHP ' . number_format($amounts, 2) . ' in settelement of the above account.', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(80, 0, 'Received By: ', '', 'L', false, 0);
        if ($data[0]['dptrno'] != 0) {
            PDF::MultiCell(200, 0, $clname, 'B', 'L', false, 0);
        } else {
            PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
        }

        PDF::MultiCell(80, 0, '', '', 'L', false, 0);

        PDF::MultiCell(40, 0, 'DATE', '', 'L', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(170, 0, '', '', 'L', false, 1);



        PDF::MultiCell(80, 0, '', '', 'L', false, 0);
        PDF::MultiCell(200, 0, 'Signature over printed name', '', 'C', false, 0);
        PDF::MultiCell(440, 0, '', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(720, 0, 'PLEASE ATTACH SUPPORTING DOCUMENTS', '', 'C', false, 1);

        $rem = "select head.purpose from heahead as head 
               left join glhead as head2 on head2.yourref=head.docno where head2.trno=$trno";
        $remhere = json_decode(json_encode($this->coreFunctions->opentable($rem)), true);

        PDF::MultiCell(60, 0, 'REMARKS: ', '', 'L', false, 0);

        PDF::MultiCell(660, 0,  '' . (isset($remhere[0]['purpose']) ? $remhere[0]['purpose'] : ""), '', 'L', false, 1);

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
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('CASH/CHECK VOUCHER', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->col('CHECK NO. :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAYEE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '450', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->col('REFERENCE # :', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTES : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '720', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
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

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

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
        $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
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
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
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
                detail.cr, detail.postdate";
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
            PDF::MultiCell(200, 5, $data[0]['clientname'], '', 'L', false, 0);
            PDF::MultiCell(370, 5, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'C', false, 0);

            PDF::MultiCell(100, 5, '', '', 'C', false);

            $dd = number_format((float)$cc, 2, '.', '');

            PDF::MultiCell(120, 5, '', '', 'C', false, 0);
            PDF::setFontSpacing(2);
            PDF::MultiCell(600, 5, $this->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false);

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
                case '1':
                case '4':
                case '7':
                case '10':
                    PDF::MultiCell(95, $max_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
                    $totalwtx1 +=  $data['detail'][$key]['oamt'];
                    break;
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
            $tin = ' / ' . $params['params']['dataparams']['tin'];
        }

        if ($params['params']['dataparams']['position'] == '') {
            $position = '';
        } else {
            $position =  $params['params']['dataparams']['position'];
        }


        PDF::MultiCell(780, 30, ucwords($payor) . ucwords($position) . $tin, 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
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
                detail.cr, detail.postdate";

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
            PDF::MultiCell(220, 0, (isset($cc) ? '***' . number_format($cc, $decimal) . '***' : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(720, 0, '***' . $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY***', '', 'L', false);
        }

        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function PDF_BPI_CHECK_LAYOUT($data, $filters)
    {

        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);
        $trno = $filters['params']['dataid'];
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
        $fontsize = "12";

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        if (Storage::disk('sbcpath')->exists('/fonts/timebd.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
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
                detail.cr, detail.postdate";
        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($data2); $i++) {

            $cc = $data2[$i]['cr'];
            $month = "<span style='letter-spacing:15px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:15px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:15px; margin-right:-8px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

            PDF::setFontSpacing(8);
            PDF::SetFont($font, '', 11);
            PDF::MultiCell(580, 0, date('m', strtotime($data2[$i]['postdate'])), '', 'R', false, 0);
            PDF::MultiCell(60, 0, date('d', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(60, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'C', false, 0);
            PDF::MultiCell(20, 0, '', '', 'C');

            PDF::setFontSpacing(0);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);

            $clname = $data[0]['clientname'];
            if($data[0]['fname'].$data[0]['mname'].$data[0]['lname'] != ''){
                $clname = $data[0]['fname'].' '.$data[0]['lname'];
                // if ($data[0]['mname'] != ''){
                //     $clname = $data[0]['fname'].' '.$data[0]['mname'].'. '.$data[0]['lname'];
                // }else{
                //     $clname = $data[0]['fname'].' '.$data[0]['lname'];
                // }
            }

            PDF::MultiCell(50, 0, '', '', 'C', false, 0);
            PDF::MultiCell(471, 0,  '  '.strtoupper($clname) , '', 'L', false, 0);
            //PDF::MultiCell(170, 0, '', '', 'L', false, 0);
            PDF::MultiCell(200, 0, (isset($cc) ?  number_format($cc, $decimal)  : ''), '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            $dd = number_format((float)$cc, 2, '.', '');
            PDF::MultiCell(30, 0, '', '', 'C', false, 0);
            if (strpos($data2[$i]['cr'], '.') !== false) {
                $fractionvalues = explode('.', $data2[$i]['cr']);
                if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
                    PDF::MultiCell(690, 0, $this->ftNumberToWordsConverter($data2[$i]['cr'],false,'',true), '', 'L', false, 1); 
                }else{
                    PDF::MultiCell(690, 0, $this->ftNumberToWordsConverter($data2[$i]['cr'],false,'',true) . ' ' . 'ONLY', '', 'L', false, 1);  
                } //end if
            }else{
                PDF::MultiCell(690, 0, $this->ftNumberToWordsConverter($data2[$i]['cr'],false,'',true) . ' ' . 'ONLY', '', 'L', false, 1);
            }//end if
        
            
            //PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false);

            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 5, "\n", '', 'L', false);
            PDF::MultiCell(720, 0, $data[0]['cvno'].' #'.$data2[$i]['checkno'], '', 'L', false, 0);
        }
        $this->coreFunctions->execqry("update cntnum set printcheck = now() where trno = " .$trno,"update");
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
                detail.cr, detail.postdate";

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




    public function rpt_DEFAULT_CCVOUCHER_LAYOUT2($data, $filters)
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


        $qry = "select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from ladetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' 
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate
                UNION ALL
                select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
                from gldetail as detail
                left join coa on coa.acnoid = detail.acnoid
                where trno = " . $data[0]['trno'] . "
                and left(coa.alias,2) = 'CB' 
                group by 
                detail.checkno,coa.acno,
                detail.cr, detail.postdate";

        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($data2); $i++) {
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
            }
            $cc = $data2[$i]['cr'];
            $cdate = $data2[$i]['postdate'];

            $str .= '<div style="margin-top:-2px;letter-spacing: 3px;">';
            $str .= $this->reporter->beginreport('900');


            $str .= $this->reporter->begintable('920');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
            $str .= $this->reporter->col('', '670', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->col(('' . isset($cdate) ? $cdate : ''), '180', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable('920');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
            $str .= $this->reporter->col($data[0]['clientname'], '720', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->col((isset($cc) ? number_format($cc, $decimal) : ''), '150', null, false, '1px solid ', '', 'C', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable('920');
            $dd = number_format((float)$cc, 2, '.', '');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
            $str .= $this->reporter->col($this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '900', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->endreport();
            $str .= '</div>';
            $this->reporter->linecounter = 30;
        }
        return $str;
    } //end fn

    public function rpt_CV_WTAXREPORT($data, $filters)
    {
        $str = '';
        $count = 60;
        $page = 58;

        $birlogo = URL::to('/images/reports/birlogo.png');
        $birblogo = URL::to('/images/reports/birbarcode.png');
        $bir = URL::to('/images/reports/bir2307.png');

        $str .= $this->reporter->beginreport();
        $str .= $this->reporter->endtable();
        $str .= '';

        //1st row
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('For BIR&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspBCS/<br/>Use Only&nbsp&nbsp&nbspItem:', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="60px" height ="60px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
        $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue', '60', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<img src ="' . $bir . '">', '135', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col('Certificate of Creditable Tax <br> Withheld At Source', '450', null, false, '2px solid ', 'RTB', 'C', 'Century Gothic', '16', 'B', '', '');

        $str .= $this->reporter->col('<img src ="' . $birblogo . '" alt="BIR" width="200px" height ="50px">', '130', null, false, '2px solid ', 'TB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '5', null, false, '2px solid ', 'RTB', 'L', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"', '100', null, false, '2px solid ', 'LRTB', 'L', 'Century Gothic', '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        //2nd row blank
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '11', '', '', '1px');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //3rd row -> 1 for the period
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('1', '40', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '1px');
        $str .= $this->reporter->col('For the Period', '120', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('From', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

        switch ($data['head'][0]['month']) {
            case '1':
            case '2':
            case '3':
                $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

                $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');

                $str .= $this->reporter->col('03', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                break;

            case '4':
            case '5':
            case '6':
                $str .= $this->reporter->col('04', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

                $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');

                $str .= $this->reporter->col('06', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                break;

            case '7':
            case '8':
            case '9':
                $str .= $this->reporter->col('07', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

                $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');

                $str .= $this->reporter->col('09', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                break;

            default:
                $str .= $this->reporter->col('10', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

                $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');

                $str .= $this->reporter->col('12', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
                break;
        }

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '1px');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //5th row -> part 1
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part I-Payee Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //6th row -> blank 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //7th row -> 2 tax payer
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('2', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //blank row
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', '', '', '1px');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //9th row -> 3 payees name

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('3', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col("Payee`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //payees name box
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //registered address
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('4', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('4A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //address name box
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col((isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // 5 foreign address

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('5', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col("Foreign Address, <i>if applicable <i/>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //f address box
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('', '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '10px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //14th row -> blank 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '10', '', '', '1px');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //part II
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part II-Payor Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //16th row -> blank 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //TIN payor
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('6', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col((isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //payor
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('7', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col("Payor`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //Payor name box
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

        $company_name = (isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : '');

        $str .= $this->reporter->col($company_name, '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //registered address
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('8', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('8A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //address name box
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col((isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col((isset($data['head'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //22th row -> blank 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '11', '', '', '1px');
        $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //part III
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part III-Details of Monthly Income Payments and Taxes Withheld', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //24th row -> income payments 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '3px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRT', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '380', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '11', 'B', '', '');

        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //25th row -> month header
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '2px');
        $str .= $this->reporter->col('ATC', '80', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('1st Month of the Quarter', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('2nd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('3rd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('Total', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('Tax Withheld For the Quarter', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //26th row -> blank 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '1px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');

        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //27th row -> line
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LTRB', 'C', 'Century Gothic', '12', 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //28th row -> atc1
        $str .= $this->reporter->begintable('800');

        $total = 0;
        $totalwtx1 = 0;
        $totalwtx2 = 0;
        $totalwtx3 = 0;
        $totalwtx = 0;
        $a = -1;
        foreach ($data['detail'] as $key => $value) {
            $a++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data['res'][$a]['ewtdesc'], '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '2px');
            $str .= $this->reporter->col($key, '80', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

            switch ($data['detail'][$key]['month']) {
                case '1':
                case '4':
                case '7':
                case '10':
                    $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $totalwtx1 +=  $data['detail'][$key]['oamt'];
                    break;
                case '2':
                case '5':
                case '8':
                case '11':
                    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $totalwtx2 +=  $data['detail'][$key]['oamt'];
                    break;
                default:
                    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
                    $totalwtx3 +=  $data['detail'][$key]['oamt'];
                    break;
            }
            $total = number_format($data['detail'][$key]['oamt'], 2);
            $str .= $this->reporter->col($total, '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
            $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'], 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');

            $totalwtx += $data['detail'][$key]['oamt'];
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //29th row -> total
        $str .= $this->reporter->begintable('800');
        $totaltax = 0;

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col(($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col(($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col(($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col(($totalwtx != 0 ? number_format($totalwtx, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');

        foreach ($data['detail'] as $key2 => $value2) {

            $totaltax = $totaltax + $data['detail'][$key2]['xamt'];
        }

        $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //30th row -> space for total 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //31th row -> money payments row
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '1px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //32th row -> money payments row
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //32th row -> money payments row
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //32th row -> money payments row
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //32th row -> money payments row
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
        $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

        $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //33th row -> declaration
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent  to the processing of our information as contemplated under  the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful  purposes.', '800', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '11', 'B', '', '1px');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //signatory from parameter
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '11', 'B', '', '3px');
        $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['payor']), '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['tin'], '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['position']), '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', 'B', '', '13px');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //line after signatory
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //signatory
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent
      <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
        //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //TAX Agent
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //39th row -> signature line 1
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
        Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //42th row -> blank space after authorized signature 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //43th row -> space after declaration
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CONFORME:', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '1px');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //signatory from parameter
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', 'B', '', '13px');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //line after signatory
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //signatory
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent
      <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //TAX Agent
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //39th row -> signature line 1
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
          Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //52th row -> blank space after authorized signature 
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
        $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function METROBANK_CHECK_LAYOUT($data, $filters)
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
                detail.cr, detail.postdate";

        $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        for ($i = 0; $i < count($data2); $i++) {
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
            }
            $cc = $data2[$i]['cr'];
            $cdate = date('m d Y', strtotime($data2[$i]['postdate']));
            $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
            $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
            $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

            $str .= '<div style="margin-top:60px;letter-spacing: 3px;">';
            $str .= $this->reporter->beginreport('900');

            $str .= $this->reporter->begintable('920');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
            $str .= $this->reporter->col('', '670', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->col("
        <div style='margin-right: 50px;'>
        " . ('' . isset($cdate) ? $month . ' ' . $day . ' ' . $year : '') . "
        </div>", '300', null, false, '1px solid ', '', 'R', 'Verdana', '13', '', '30px', '4px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable('920');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
            $str .= $this->reporter->col('', '670', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'R', 'Verdana', '12', '', '30px', '4px');
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable('920');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
            $str .= $this->reporter->col('', '670', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
            $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'R', 'Verdana', '12', '', '30px', '4px');
            $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= "<div style='margin-top: -3px;'>";
            $str .= $this->reporter->begintable('920');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');

            $str .= $this->reporter->col(strtoupper($data[0]['clientname']), '720', null, false, '1px solid ', '', 'L', 'Verdana', '13', '', '30px', '4px');
            $str .= $this->reporter->col((isset($cc) ? '***' . number_format($cc, $decimal) . '***' : ''), '150', null, false, '1px solid ', '', 'C', 'Verdana', '13', '', '30px', '4px');
            $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= "</div>";


            $str .= "<div style='margin-top: 3px;'>";
            $str .= $this->reporter->begintable('920');
            $dd = number_format((float)$cc, 2, '.', '');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
            
            $str .= $this->reporter->col('***' . $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY***', '900', null, false, '1px solid ', '', 'L', 'Verdana', '13', '', '30px', '4px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= "</div>";
            $str .= $this->reporter->endreport();
            $str .= '</div>';
            $this->reporter->linecounter = 30;
        }
        return $str;
    } //end fn


    //start liquidation format
    public function PDF_liquidationformat_header($params, $data)
    {
        $center = $params['params']['center'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";

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

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 13);

        $qry = "select name, address, tel, code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), 'TLR', 'C');
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(0, 0, $headerdata[0]->address, 'LR', 'C');
        PDF::MultiCell(0, 0, $headerdata[0]->tel, 'LR', 'C');
        $this->addrowspace();

        PDF::SetFont($font, '', 12);
        PDF::MultiCell(500, 5, '', 'L', 'C', false, 0);
        PDF::MultiCell(220, 5, 'CV#. ' . $data[0]['docno'] . ' ', 'R', 'R', false, 1);

        $this->addrowspace();

        PDF::SetFont($fontbold, '', 16);
        PDF::SetTextColor(255, 0, 0);

        PDF::MultiCell(720, 5, strtoupper('Check Voucher'), 'LR', 'C');

        PDF::SetFont('', '', 3);
        PDF::MultiCell(0, 0, "", 'LR');

        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(500, 5, 'ACCOUNT TITLE ', 'TL', 'C', false, 0);
        PDF::MultiCell(110, 5, 'DEBIT', 'TL', 'C', false, 0);
        PDF::MultiCell(110, 5, 'CREDIT', 'TLR', 'C', false, 1);

        PDF::SetFont($font, '', 5);

        PDF::MultiCell(500, 5, ' ', 'TL', 'C', false, 0);
        PDF::MultiCell(110, 5, '', 'TL', 'C', false, 0);
        PDF::MultiCell(110, 5, '', 'TLR', 'C', false, 1);
    }

    private function addrowspace()
    {
        PDF::MultiCell(720, 0, '', 'LR', 'L', false);
    }

    public function PDF_LIQUIDATIONFORMAT_LAYOUT($data, $params)
    {
        $trno = $params['params']['dataid'];
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $decimal = $this->companysetup->getdecimal('currency',  $params['params']);

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        $fontsize13 = 13;
        $fontsize12 = 12;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->PDF_liquidationformat_header($params, $data);
        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(620, 0, '', '');
        $countarr = 0;
        $totaldb = 0;
        $totalcr = 0;

        if (!empty($data)) {


            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;

                $acnonamedescs = trim($data[$i]['acnoname']);

                $debit = number_format($data[$i]['db'], $decimalcurr);
                $debit = $debit <= 0 ? '-' : $debit;
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $credit = $credit <= 0 ? '-' : $credit;


                $arr_acnonamedescs = $this->reporter->fixcolumn([$acnonamedescs], '50', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acnonamedescs, $arr_debit, $arr_credit]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(500, 15, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(110, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), 'L', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(110, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), 'LR', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                $totaldb = $totaldb + $data[$i]['db'];
                $totalcr = $totalcr + $data[$i]['cr'];

                if (PDF::getY() > 900) {
                    $this->PDF_liquidationformat_header($params, $data);
                }
            }
        }



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(30, 15, '  ', 'TL', 'L', false, 0);
        PDF::MultiCell(470, 15, 'TOTAL  ', 'T', 'L', false, 0);
        PDF::MultiCell(110, 15, number_format($totaldb, 2) . ' ', 'TL', 'R', false, 0);
        PDF::MultiCell(110, 15, number_format($totalcr, 2) . ' ', 'TLR', 'R', false, 1);


        PDF::SetFont($fontbold, '', $fontsize13);
        PDF::MultiCell(110, 0, ' EXPLANATION : ', 'TL', 'L', false, 0);
        PDF::MultiCell(610, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'TR', 'C', false);


        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(180, 0, ' Prepared By: ', 'TL', 'L', false, 0);
        PDF::MultiCell(180, 0, ' Approved By: ', 'TL', 'L', false, 0);
        PDF::MultiCell(180, 0, ' Approved By: ', 'TL', 'L', false, 0);
        PDF::MultiCell(180, 0, ' Posted By: ', 'TLR', 'L', false, 1);


        PDF::MultiCell(180, 15, '', 'L', 'L', false, 0);
        PDF::MultiCell(180, 0, '', 'L', 'L', false, 0);
        PDF::MultiCell(180, 0, '', 'L', 'L', false, 0);
        PDF::MultiCell(180, 0, '', 'LR', 'L', false, 1);

        PDF::MultiCell(180, 0, ' ' . $params['params']['dataparams']['prepared'], 'BL', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ' . $params['params']['dataparams']['approved'], 'BL', 'L', false, 0); //
        PDF::MultiCell(180, 0, ' ' . $params['params']['dataparams']['approved2'], 'BL', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ' . $params['params']['dataparams']['checked'], 'LRB', 'L', false, 1);


        $this->addrowspace();

        $clname = $data[0]['clientname'];
        $acctname =$data[0]['acctname'];

        if($acctname !=""){
            $clname = $acctname;
        }else{
            if( $data[0]['fname'].$data[0]['mname'].$data[0]['lname'] != ''){
                $clname = $data[0]['fname'].' '.$data[0]['lname'];
                // if ($data[0]['mname'] != ''){
                //     $clname = $data[0]['fname'].' '.$data[0]['mname'].'. '.$data[0]['lname'];
                // }else{
                //     $clname = $data[0]['fname'].' '.$data[0]['lname'];
                // }
            }
        }


        PDF::MultiCell(120, 0, ' Paid To: ', 'L', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize13);
        PDF::MultiCell(440, 0, $clname, '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(60, 0, 'CV Date: ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize13);

        PDF::MultiCell(100, 0, date_format(date_create($data[0]['dateid']), 'm/d/Y'), 'R', 'L', false, 1);


        $qry = "
        select acnoname,amount,checkno,pdate from ( 
        select  coa.acnoname,abs(detail.db - detail.cr) as amount, left(coa.acno,2) as alias,detail.checkno,
        date(detail.postdate) as pdate from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        where head.doc = 'cv' and left(coa.alias,2) = 'cb' and head.trno = " . $trno . " 
        union all
        select  coa.acnoname,abs(detail.db - detail.cr) as amount, left(coa.acno,2) as alias,detail.checkno,
        date(detail.postdate) as pdate from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        where head.doc = 'cv' and left(coa.alias,2) = 'cb' and head.trno = " . $trno . " ) as v group by checkno,amount,acnoname,pdate";
        $cbdata = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        $this->addrowspace();
        $amounts = number_format((float)$cbdata[0]['amount'], 2, '.', '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, ' Amount in Words: ', 'L', 'L', false, 0);

        PDF::SetFont('dejavusans', '', 11);
        if (strpos($cbdata[0]['amount'], '.') !== false) {
            $fractionvalues = explode('.', $cbdata[0]['amount']);
            if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
                PDF::MultiCell(600, 0, $this->ftNumberToWordsConverter($cbdata[0]['amount'], false, '',true) . ' ( ₱ ' . number_format($amounts, 2) . ' )', 'R', 'L', false, 1);
            }else{
                PDF::MultiCell(600, 0, $this->ftNumberToWordsConverter($cbdata[0]['amount'], false, '',true) . ' ' . 'ONLY' . ' ' . '( ₱ ' . number_format($amounts, 2) . ' )', 'R', 'L', false, 1);
            } //end if
        }else{
            PDF::MultiCell(600, 0, $this->ftNumberToWordsConverter($cbdata[0]['amount'], false, '',true) . ' ' . 'ONLY' . ' ' . '( ₱ ' . number_format($amounts, 2) . ' )', 'R', 'L', false, 1);
        }//end if

        


        if (count($cbdata) == 0) {
            PDF::SetFont($font, '', $fontsize);
            $this->addrowspace();

            PDF::MultiCell(120, 0, ' Bank :', 'L', 'L', false, 0);
            PDF::MultiCell(600, 0, '', 'R', 'L', false, 1);


            PDF::MultiCell(120, 0, ' Check no. :', 'L', 'L', false, 0);
            PDF::MultiCell(600, 0, '', 'R', 'L', false, 1);

            $this->addrowspace();
            PDF::MultiCell(120, 0, ' Check Date :', 'L', 'L', false, 0);
            PDF::MultiCell(600, 0, '', 'R', 'L', false, 1);
        } else {
            PDF::SetFont($font, '', $fontsize);
            $this->addrowspace();
            PDF::MultiCell(120, 0, ' Bank :', 'L', 'L', false, 0);
            PDF::MultiCell(600, 0, '' . (isset($cbdata[0]['acnoname']) ? $cbdata[0]['acnoname'] : ""), 'R', 'L', false, 1);

            $this->addrowspace();

            PDF::MultiCell(120, 0, ' Check no. :', 'L', 'L', false, 0);
            PDF::MultiCell(600, 0, '' . (isset($cbdata[0]['checkno']) ? $cbdata[0]['checkno'] : ""), 'R', 'L', false, 1);

            $this->addrowspace();
            PDF::MultiCell(120, 0, ' Check Date :', 'L', 'L', false, 0);


            PDF::MultiCell(600, 0, '' . date_format(date_create($cbdata[0]['pdate']), 'm/d/Y'), 'R', 'L', false, 1);
        }
        $this->addrowspace();

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(85, 0, ' Received from ', 'L', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(230, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(65, 0, ' the sum of', '', 'L', false, 0);
        PDF::MultiCell(340, 0, 'PHP ' . number_format($amounts, 2) . ' in settelement of the above account.', 'R', 'L', false, 1);


        $this->addrowspace();

        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(80, 0, ' Received By: ', 'L', 'L', false, 0);
        if ($data[0]['dptrno'] != 0) {
            PDF::MultiCell(200, 0, $clname, 'B', 'L', false, 0);
        } else {
            PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
        }

        PDF::MultiCell(80, 0, '', '', 'L', false, 0);

        PDF::MultiCell(40, 0, 'Date:', '', 'L', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(170, 0, '', 'R', 'L', false, 1);

        PDF::MultiCell(80, 0, '', 'L', 'L', false, 0);
        PDF::MultiCell(200, 0, 'Signature over printed name', '', 'C', false, 0);
        PDF::MultiCell(440, 0, '', 'R', 'L', false, 1);

        $this->addrowspace();

        PDF::SetTextColor(255, 0, 0);
        PDF::SetFont($fontbold, '', $fontsize13);
        PDF::MultiCell(720, 10, 'PLEASE ATTACH SUPPORTING DOCUMENTS', 'TLRB', 'C');

        PDF::SetTextColor(0, 0, 0);

        //second
        $this->addrowspace();

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(120, 0, ' COMPANY: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(450, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize13);
        PDF::SetTextColor(255, 0, 0);
        PDF::MultiCell(150, 0, 'TREASURY COPY', 'R', 'C', false, 1);

        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(720, 10, 'CHECK VOUCHER LIQUIDATION REFERENCE FORM', 'LR', 'C');

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' RELEASED DATE: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(120, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(60, 0, 'PAYEE: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(300, 0, $clname, 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '', 'R', 'L', false, 1);

        PDF::SetFont('', '', 3);
        PDF::MultiCell(0, 0, "", 'LR');

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' CHECK VOUCHER NO.: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(110, 0, $data[0]['docno'], 'B', 'C', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(150, 0, 'CHECK VOUCHER DATE: ', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 0, date_format(date_create($data[0]['dateid']), 'm/d/Y'), 'B', 'R', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(100, 0, 'AMOUNT PHP:', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(90, 0, number_format($amounts, 2), 'B', 'R', false, 0);
        PDF::MultiCell(30, 0, '', 'R', 'L', false, 1);


         ///new add
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' CHECK NO.: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(110, 0,'' . (isset($cbdata[0]['checkno']) ? $cbdata[0]['checkno'] : ""), 'B', 'C', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(150, 0, 'CHECK DATE: ', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 0, date_format(date_create($cbdata[0]['pdate']), 'm/d/Y'), 'B', 'R', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(90, 0, '', '', 'R', false, 0);
        PDF::MultiCell(30, 0, '', 'R', 'L', false, 1);




        $this->addrowspace();
        $this->addrowspace();

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' RELEASED BY: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(220, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(80, 0, 'RECEIVED BY: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(160, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'R', 'L', false, 1);

        $this->addrowspace();
        $this->addrowspace();

        PDF::SetTextColor(255, 0, 0);
        PDF::SetFont($fontbold, '', $fontsize13);
        PDF::MultiCell(720, 10, ' NOTE: LIQUIDATION TIME IS 3:00 - 5:00 PM', 'LRB', 'L');

        PDF::SetTextColor(0, 0, 0);

        PDF::MultiCell(720, 10, '', 'TLRB', 'L');


        //3rd
        $this->addrowspace();

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(120, 0, ' COMPANY: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(450, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize13);
        PDF::SetTextColor(255, 0, 0);
        PDF::MultiCell(150, 0, 'EMPLOYEE COPY', 'R', 'C', false, 1);

        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(720, 10, 'CHECK VOUCHER LIQUIDATION REFERENCE FORM', 'LR', 'C');

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' RELEASED DATE: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(120, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(60, 0, 'PAYEE: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(300, 0, $clname, 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '', 'R', 'L', false, 1);

        PDF::SetFont('', '', 3);
        PDF::MultiCell(0, 0, "", 'LR');

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' CHECK VOUCHER NO.: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(110, 0, $data[0]['docno'], 'B', 'C', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(150, 0, 'CHECK VOUCHER DATE: ', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 0, date_format(date_create($data[0]['dateid']), 'm/d/Y'), 'B', 'R', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(100, 0, 'AMOUNT PHP:', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(90, 0, number_format($amounts, 2), 'B', 'R', false, 0);
        PDF::MultiCell(30, 0, '', 'R', 'L', false, 1);

          ///new add
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' CHECK NO.: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(110, 0,'' . (isset($cbdata[0]['checkno']) ? $cbdata[0]['checkno'] : ""), 'B', 'C', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(150, 0, 'CHECK DATE: ', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 0, date_format(date_create($cbdata[0]['pdate']), 'm/d/Y'), 'B', 'R', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(90, 0, '', '', 'R', false, 0);
        PDF::MultiCell(30, 0, '', 'R', 'L', false, 1);


        $this->addrowspace();
        $this->addrowspace();

        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(140, 0, ' RELEASED BY: ', 'L', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(220, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize12);
        PDF::MultiCell(80, 0, 'RECEIVED BY: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(160, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'R', 'L', false, 1);

        $this->addrowspace();
        $this->addrowspace();

        PDF::SetTextColor(255, 0, 0);
        PDF::SetFont($fontbold, '', $fontsize13);
        PDF::MultiCell(720, 10, ' NOTE: LIQUIDATION TIME IS 3:00 - 5:00 PM', 'LRB', 'L');

        PDF::SetTextColor(0, 0, 0);
        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn


    //end liquidation




    public function ftNumberToWordsConverter($number, $ischeck = true, $cur = 'PHP', $isnumbercent = false)

    {
        $numberwords = $this->ftNumberToWordsBuilder(number_format($number, 2, '.', ''), $ischeck, $isnumbercent);

        switch ($cur) { // add more currency
            case 'USD':
                $cur = 'DOLLAR';
                break;

            case 'PHP':
                $cur = 'PESOS';
                break;

            default:
                $cur = "PESOS";
                break;
        }

        if (strpos($numberwords, "/") == true || strpos($numberwords, "CENTS") == true || strpos($numberwords, "CENTAVOS") == true) {
            $numberwords = str_replace(" AND ", " " . $cur . " AND ", $numberwords);
        } else {
            $this->coreFunctions->LogConsole('that' . $numberwords . 'aaa' . $cur);
            $numberwords .= " " . $cur . " ";
        } //end if

        return $numberwords;
    } //end function convert to words

    public function ftNumberToWordsBuilder($number, $ischeck = true, $isnumbercent = false)
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
                1000000000000000000 => 'Quintillion',
                '01' => 'One',
                '02' => 'Two',
                '03' => 'Three',
                '04' => 'Four',
                '05' => 'Five',
                '06' => 'Six',
                '07' => 'Seven',
                '08' => 'Eight',
                '09' => 'Nine',
                '60' => 'Sixty'
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
            $this->coreFunctions->LogConsole($number . 'number');
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
                if ($ischeck) { // for check
                    $string .= $decimal . ' ' . $fraction .  '/100';
                    $words = array();
                    $string .= implode(' ', $words);
                } else { // for currency                    
                    if ($isnumbercent) {
                        $cent = round($fraction, 2);
                    } else {
                        $cent = $this->ftNumberToWordsBuilder(round($fraction, 2));
                    }
                    $string .= $decimal . ' ' . $cent . ' CENTAVOS';
                }
            } //end if

            return strtoupper($string);
        } //end
    } //end fn




}
