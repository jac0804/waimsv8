<?php

namespace App\Http\Classes\modules\modulereport\eapp;

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

class cr
{

    private $modulename = "Received Payment";
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
        $fields = ['radioprint', 'radiobilling', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);

        data_set($col1, 'radiobilling.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'pink'],
            ['label' => 'Official Receipt', 'value' => 'or', 'color' => 'pink'],
            ['label' => 'Ledger', 'value' => 'ledger', 'color' => 'pink']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
            'PDFM' as print,
            'default' as radiobilling,
            '' as prepared,
            '' as approved,
            '' as received
            "
        );
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];
        $query = "
            select head.trno, date_format(head.dateid,'%m/%d/%Y') as dateid, concat(left(head.docno,2),'',right(head.docno,8)) as docno2, head.docno, head.clientname, client.addr as address, head.yourref,head.ourref, left(coa.alias, 2) as alias, 
            coa.acno, coa.acnoname, coa.alias as ali,client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line, client.bstyle, client.tin, ifnull(plan.amount,0) as camount,
            info.clientname as planholder, plan.name as plantype, ea.terms,ifnull(head.amount,0) as amount,cnt.bref,cnt.seq,trn.bref as cbref,trn.seq as cseq,agent.clientname as agentname,info.issenior,detail.rem,ci.cptrno,
            concat(info.addressno,' ',info.street,' ',info.subdistown,' ',info.city) as certaddr
            from ((lahead as head
            left join ladetail as detail on detail.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid)
            left join client on client.client=detail.client
            left join cntnum as cnt on cnt.trno = head.trno
            left join cntnuminfo as ci on ci.trno = head.trno
            left join glhead as cp on cp.trno=ci.cptrno
            left join cntnum as trn on trn.trno = cp.trno
            left join heahead as ea on ea.trno=cp.aftrno
            left join heainfo as info on info.trno=ea.trno
            left join plantype as plan on plan.line=ea.planid
            left join client as agent on agent.client=head.agent
            where head.doc='cr' and head.trno=" . $trno . "
            union all
            select head.trno, date_format(head.dateid,'%m/%d/%Y') as dateid, concat(left(head.docno,2),'',right(head.docno,8)) as docno2, head.docno, head.clientname, client.addr as address, head.yourref,head.ourref, left(coa.alias, 2) as alias, 
            coa.acno, coa.acnoname, coa.alias as ali,client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line, client.bstyle, client.tin, ifnull(plan.amount,0) as camount,
            info.clientname as planholder, plan.name as plantype, ea.terms,ifnull(head.amount,0) as amount,cnt.bref,cnt.seq,trn.bref as cbref,trn.seq as cseq,agent.clientname as agentname,info.issenior,detail.rem,ci.cptrno,
            concat(info.addressno,' ',info.street,' ',info.subdistown,' ',info.city) as certaddr
            from ((glhead as head
            left join gldetail as detail on detail.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid)
            left join client on client.clientid=detail.clientid
            left join cntnum as cnt on cnt.trno = head.trno
            left join hcntnuminfo as ci on ci.trno = head.trno
            left join glhead as cp on cp.trno=ci.cptrno
            left join cntnum as trn on trn.trno = cp.trno
            left join heahead as ea on ea.trno=cp.aftrno
            left join heainfo as info on info.trno=ea.trno
            left join plantype as plan on plan.line=ea.planid
            left join client as agent on agent.clientid=head.agentid
            where head.doc='cr' and head.trno=" . $trno . " order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['radiobilling'] == "default") {
            return $this->default_CR_PDF($params, $data);
        } else if ($params['params']['dataparams']['radiobilling'] == 'ledger') {
            return $this->ledger_pdf($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->or_pdf($params, $data);
        }
    }

    public function default_CR_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
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


        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($fontbold, '', 14);

        PDF::Image($this->companysetup->getlogopath($params['params']) . 'elsi.png', '18', '25', 342, 78);

        $strdocno = $data[0]['bref'] . $data[0]['seq'];
        $strdocno = $this->othersClass->PadJ($strdocno, 10);

        $strladocno = $data[0]['cbref'] . $data[0]['cseq'];
        $strladocno = $this->othersClass->PadJ($strladocno, 10);

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename . '', '', 'L', false, 0, '',  '110');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Doc No.: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, $strdocno, 'B', 'L', false, 0, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, $strladocno, 'B', 'L', false, 0, '',  '');


        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
        PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
        PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
        PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "DETAILS", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_CR_PDF($params, $data)
    {
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
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_CR_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {

                $strladocno = $data[0]['cbref'] . $data[0]['cseq'];
                $strladocno = $this->othersClass->PadJ($strladocno, 10);

                $maxrow = 1;
                $acno = $data[$i]['acno'];
                $acnoname = $data[$i]['acnoname'];
                $ref = $strladocno; //$data[$i]['ref'];
                $postdate = $data[$i]['postdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $client = $data[$i]['rem'];
                $debit = $debit < 0 ? '-' : $debit;
                $credit = $credit < 0 ? '-' : $credit;

                $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
                $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '35', 0);
                $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
                $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
                $arr_client = $this->reporter->fixcolumn([$client], '16', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(90, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(160, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
                }


                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];

                if (intVal($i) + 1 == $page) {
                    $this->default_CR_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(425, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function or_header_pdf($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $id = $params['params']['adminid'];


        $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 10;
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }


        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);



        // 1st col
        $dataform = ['', '', '', '', '', '', 'Total Due',  'Less: Withholding Tax'];
        $dataform2 = ['CHECK', '  BANK', '  CHECK NO.', '  CHECK DATE'];

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(220, 20, 'IN SETTLEMENT OF THE FOLLOWING: ', 'TLRB', 'C', false, 1, '20',  '20', true, 0, false, true, 0, 'M', true);

        PDF::SetTextColor(0, 0, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(110, 20, 'PARTICULARS', 'TLRB', 'C', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, 'AMOUNT', 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        foreach ($dataform as $key => $value) {
            PDF::MultiCell(110, 17, $value, 'TLRB', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(80, 17, '', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(30, 17, '', 'TLRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        }
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 17, 'Payment Due', 'TLRB', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 17, '', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 17, '', 'TLRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(110, 17, '', 'TLRB', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 17, '', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 17, '', 'TLRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(110, 17, 'FORM OF', 'TLB', 'R', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 17, ' PAYMENT', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 17, '', 'BRT', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, '', 10);
        PDF::MultiCell(110, 17, 'CASH', 'TLRB', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 17, 'P', 'TLRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        foreach ($dataform2 as $key => $value) {
            PDF::MultiCell(110, 17, $value, 'TLRB', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(110, 17, '', 'TLRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        }
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 17, 'TOTAL AMOUNT', 'TLRB', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 17, 'P', 'TLRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        // 2nd col


        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C', false, 1, '300', '20');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tin) . "\n\n\n", '', 'C', false, 1, '300', '40');


        PDF::SetFont($font, '', 12);
        PDF::MultiCell(710, 0, '', '', '', false, 0);

        $strdocno = $data[0]['bref'] . $data[0]['seq'];
        $strdocno = $this->othersClass->PadJ($strdocno, 10);


        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(520, 0, 'OFFICIAL RECEIPT', '', 'L', false, 0, '250',  '110');
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(30, 0, "No.", '', 'L', false, 0, '650',  '');
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(120, 0, $strdocno, '', 'L', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(35, 0, "Date: ", '', 'R', false, 0, '600',  '130');
        PDF::MultiCell(115, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(85, 0, "Received from: ", '', 'L', false, 0, '250',  '150');
        PDF::MultiCell(415, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 1, '',  '');



        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(85, 0, "and Address at:", '', 'L', false, 0, '250',  '170');
        PDF::SetFont($font, '', 8);
        PDF::MultiCell(415, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 1, '',  '');


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(155, 0, "engaged in the Business style of", '', 'L', false, 0, '250',  '190');
        PDF::MultiCell(200, 0, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), 'B', 'L', false, 0, '',  '');


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, "with TIN", '', 'R', false, 0, '',  '');
        PDF::MultiCell(15, 0, "", '', '', false, 0, '',  '');
        PDF::MultiCell(80, 0, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 1, '',  '');

        // set word
        $wordnum = $this->reporter->ftNumberToWordsConverter((isset($data[0]['amount']) ? $data[0]['amount'] : 0), false, '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'the sum of', '', 'L', false, 0, '250',  '210');
        PDF::MultiCell(450, 0, $wordnum, 'B', 'C', false, 1, '',  '');


        $strladocno = $data[0]['cbref'] . $data[0]['cseq'];
        $strladocno = $this->othersClass->PadJ($strladocno, 10);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(380, 0, "", 'B', 'L', false, 0, '250',  '230');
        PDF::MultiCell(50, 0, " pesos(P", '', 'R', false, 0, '',  '');
        PDF::MultiCell(70, 0, "" . (isset($data[0]['amount']) ? number_format($data[0]['amount'], 2) : '0') . ")", 'B', 'C', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 0, 'in partial/full payment for', '', 'L', false, 0, '250',  '250');
        PDF::MultiCell(370, 0, (isset($data[0]['planholder']) ? $data[0]['planholder'] : '') . ' ' . $strladocno, 'B', 'C', false, 1, '',  '');




        // 580



        PDF::MultiCell(150, 0, "By:", '', 'L', false, 0, '565',  '295');

        PDF::SetFont($font, '', 8);
        if ($id != 0) {
            PDF::MultiCell(150, 0, $data[0]['agentname'], '', 'C', false, 1, '580',  '295');
            PDF::MultiCell(150, 0, "Cashier/Authorize Representative", 'T', 'C', false, 1, '580',  '310');
        } else {
            PDF::MultiCell(150, 0, $username, '', 'C', false, 1, '580',  '295');
            PDF::MultiCell(150, 0, "Cashier/Authorize Representative", 'T', 'C', false, 1, '580',  '310');
        }
    }

    public function or_pdf($params, $data)
    {
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
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->or_header_pdf($params, $data);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }



    public function ledger_header_pdf($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
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


        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::Image($this->companysetup->getlogopath($params['params']) . 'elsi.png', '18', '25', 342, 78);
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, 'Customer Ledger', '', 'L', false, 1, '',  '120');
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(0, 0, "\n");


        $strladocno = $data[0]['cbref'] . $data[0]['cseq'];
        $strladocno = $this->othersClass->PadJ($strladocno, 10);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 10, "", '', 'L');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "Contract #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 0, $strladocno, '', 'L', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "Plan Holder: ", '', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 0, (isset($data[0]['planholder']) ? $data[0]['planholder'] : ''), '', 'L', false, 1, '', '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "Payor: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "Plan Type: ", '', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 0, (isset($data[0]['plantype']) ? $data[0]['plantype'] : ''), '', 'L', false, 1, '', '');

        $amt =  $data[0]['camount'];
        if ($data[0]['issenior'] == 1) {
            $amt =  $data[0]['camount'] / 1.12;
        }
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "Contract Amount: ", '', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 0, number_format($amt, 2), '', 'L', false, 1, '', '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "Payment Terms: ", '', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '', '');

        $due = $this->coreFunctions->datareader("select date_format(dateid,'%m/%d/%Y') as value from arledger where bal <>0 and trno=? order by dateid limit 1", [$data[0]['cptrno']]);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, "Due Date: ", '', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 0, (isset($due) ? $due : ''), '', 'L', false, 1, '', '');

        PDF::MultiCell(0, 0, "\n\n");



        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(95, 0, "TRANSACTION #", 1, 'C', false, 0);
        PDF::MultiCell(65, 0, "DATE", 1, 'C', false, 0);
        PDF::MultiCell(125, 0, "PAYOR", 1, 'C', false, 0);
        PDF::MultiCell(115, 0, "MODE OF PAYMENT", 1, 'C', false, 0);
        PDF::MultiCell(190, 0, "REMARKS", 1, 'C', false, 0);
        PDF::MultiCell(65, 0, "AMOUNT", 1, 'C', false, 0);
        PDF::MultiCell(65, 0, "BALANCE", 1, 'C', false);
    }

    public function ledger_pdf($params, $data)
    {
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
        $this->ledger_header_pdf($params, $data);

        $result = $this->ledger_query($data);
        $amt = '';
        $rbal = '';

        if (!empty($result)) {
            $bal = 0;
            foreach ($result as $r) {
                $strdocno = $r->bref . $r->seq;
                $strdocno = $this->othersClass->PadJ($strdocno, 10);
                $h = 0;
                $bal += $r->amount;

                if ($r->amount < 0) {
                    $amt = "(" . number_format(abs($r->amount), 2) . ")";
                } else {
                    $amt = number_format($r->amount, 2);
                }

                if ($bal < 0) {
                    $rbal = "(" . number_format(abs($bal), 2) . ")";
                } else {
                    $rbal = number_format($bal, 2);
                }

                $maxrow = 1;
                $arr_docno = $this->reporter->fixcolumn([$strdocno], '15', 0);
                $arr_dateid = $this->reporter->fixcolumn([$r->dateid], '10', 0);
                $arr_clientname = $this->reporter->fixcolumn([$r->clientname], '20', 0);
                $arr_ourref = $this->reporter->fixcolumn([$r->ourref], '20', 0);
                $arr_rem = $this->reporter->fixcolumn([$r->rem], '35', 0);
                $arr_amount = $this->reporter->fixcolumn([$amt], '12', 0);
                $arr_bal = $this->reporter->fixcolumn([$rbal], '10', 0);





                $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_clientname, $arr_ourref, $arr_rem, $arr_amount, $arr_bal]);


                for ($a = 0; $a < $maxrow; $a++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(95, $h, (isset($arr_docno[$a]) ? $arr_docno[$a] : ''), 'LR', 'L', false, 0);
                    PDF::MultiCell(65, $h, (isset($arr_dateid[$a]) ? $arr_dateid[$a] : ''), 'LR', 'L', false, 0);
                    PDF::MultiCell(125, $h, (isset($arr_clientname[$a]) ? $arr_clientname[$a] : ''), 'LR', 'L', false, 0);
                    PDF::MultiCell(115, $h, (isset($arr_ourref[$a]) ? $arr_ourref[$a] : ''), 'LR', 'L', false, 0);

                    PDF::MultiCell(190, $h, (isset($arr_rem[$a]) ? $arr_rem[$a] : ''), 'LR', 'L', false, 0);
                    PDF::MultiCell(65, $h, (isset($arr_amount[$a]) ? $arr_amount[$a] : ''), 'LR', 'R', false, 0);
                    PDF::MultiCell(65, $h, (isset($arr_bal[$a]) ? $arr_bal[$a] : ''), 'LR', 'R', false);
                }
                PDF::SetFont($font, '', $fontsize - 7);
                PDF::MultiCell(95, $h, '', 'LRB', 'L', false, 0);
                PDF::MultiCell(65, $h, '', 'LRB', 'L', false, 0);
                PDF::MultiCell(125, $h, '', 'LRB', 'L', false, 0);

                PDF::MultiCell(115, $h, '', 'LRB', 'L', false, 0);
                PDF::MultiCell(190, $h, '', 'LRB', 'L', false, 0);
                PDF::MultiCell(65, $h, '', 'LRB', 'R', false, 0);
                PDF::MultiCell(65, $h, '', 'LRB', 'R', false);
            }
        }

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function ledger_query($data)
    {
        $result = [];
        if (!empty($data)) {
            $cptrno = $this->coreFunctions->opentable("select trno from glhead where docno='" . $data[0]['yourref'] . "' union all select trno from lahead where docno='" . $data[0]['yourref'] . "'");
            $qry = "select head.trno, head.docno, head.dateid, head.clientname, head.rem, ifnull(sum(detail.db-detail.cr),0) as amount, head.ourref, head.rem,cntnum.bref,cntnum.seq
                  from glhead as head left join cntnum on cntnum.trno = head.trno
                  left join gldetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where detail.refx=" . $cptrno[0]->trno . " and head.doc='CR' and left(coa.alias,2)='AR'
                  group by trno, docno, dateid, clientname, rem, ourref,bref,seq
                union all
                select head.trno, head.docno, head.dateid, head.clientname, head.rem, ifnull(sum(detail.db-detail.cr),0) as amount, head.ourref, head.rem,cntnum.bref,cntnum.seq
                  from lahead as head  left join cntnum on cntnum.trno = head.trno
                  left join ladetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where detail.refx=" . $cptrno[0]->trno . " and head.doc='CR' and left(coa.alias,2)='AR'
                  group by trno, docno, dateid, clientname, rem, ourref,bref,seq
            union all
            select head.trno, head.docno, head.dateid, head.clientname, head.rem, ifnull(sum(detail.db-detail.cr),0) as amount, head.ourref, head.rem,cntnum.bref,cntnum.seq
                  from glhead as head left join cntnum on cntnum.trno = head.trno
                  left join gldetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where head.trno=" . $cptrno[0]->trno . " and head.doc='CP' and left(coa.alias,2)='AR'
                  group by trno, docno, dateid, clientname, rem, ourref,bref,seq
                union all
                select head.trno, head.docno, head.dateid, head.clientname, head.rem, ifnull(sum(detail.db-detail.cr),0) as amount, head.ourref, head.rem,cntnum.bref,cntnum.seq
                  from lahead as head left join cntnum on cntnum.trno = head.trno
                  left join ladetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where head.trno=" . $cptrno[0]->trno . " and head.doc='CP' and left(coa.alias,2)='AR'
                  group by trno, docno, dateid, clientname, rem, ourref,bref,seq order by trno";
            $result = $this->coreFunctions->opentable($qry);
        }
        return $result;
    }
}
