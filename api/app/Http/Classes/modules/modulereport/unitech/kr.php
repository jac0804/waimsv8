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

class kr
{
    private $modulename = "Counter Receipt";
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
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);

        return $this->coreFunctions->opentable(
            "select 
            'PDFM' as print,
            '$username' as prepared,
            '' as approved,
            '' as received "
        );
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];
        $query = "select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
                head2.yourref as krourref,head.disc,
                coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, ar.docno as ref,client.tel,coa.alias,head2.doc
                from (krhead as head 
                left join arledger as ar on ar.kr=head.trno)
                left join coa on coa.acnoid=ar.acnoid
                left join glhead as head2 on head2.trno = ar.trno 
                left join client on client.client = head.client
                where head.trno='$trno'
                union all
                select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
                head2.yourref as krourref,head.disc,
                coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, ar.docno as ref,client.tel,coa.alias,head2.doc
                from (hkrhead as head 
                left join arledger as ar on ar.kr=head.trno)
                left join coa on coa.acnoid=ar.acnoid
                left join glhead as head2 on head2.trno = ar.trno 
                left join client on client.client = head.client
                where head.trno='$trno' order by postdate,dateid, docno";
        //  var_dump($query);
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        return $this->default_KR_PDF($params, $data);
    }


    public function default_KR_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

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
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        $this->reportheader->getheader($params);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

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
        PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
        PDF::MultiCell(150, 0, "ACCOUNT NAME", '', 'L', false, 0);
        PDF::MultiCell(110, 0, "REFERENCE #", '', 'L', false, 0);
        PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
        PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_KR_PDF_ORIG($params, $data)// B4-1/16/2026
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
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_KR_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;
        $grandtotal = 0;
        $totalar =0;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $acno = $data[$i]['acno'];
                $acnoname = $data[$i]['acnoname'];
                $ref = $data[$i]['ref'];
                $postdate = $data[$i]['postdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $client = $data[$i]['client'];
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
                    PDF::MultiCell(150, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(110, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
                }


                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];

                if($data[$i]['alias'] == 'AR1'){
                    $totalar += ($data[$i]['db']-$data[$i]['cr']);
                }

                if (intVal($i) + 1 == $page) {
                    $this->default_KR_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }
        $grandtotal = $totaldb - $totalcr;
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(425, 0, 'TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false);

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(425, 0, '', '', 'R', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'R', false, 0);
        PDF::MultiCell(170, 0, 'LESS DISCOUNT: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, $data[0]['disc'], '', 'R', false);
        
        $discamt= 0; 
        if($data[0]['disc'] !=0){
            $discamt= $totalar-( $this->othersClass->Discount($totalar,$data[0]['disc']));
        }else{
            $discamt= 0;  
        }

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(425, 0, '', '', 'R', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'R', false, 0);
        PDF::MultiCell(170, 0, 'DISCOUNT AMOUNT: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($discamt,2), '', 'R', false);

        $grandtotal = $grandtotal - $discamt;// $this->othersClass->Discount($grandtotal,$data[0]['disc']);

        PDF::MultiCell(425, 0, '', '', 'R', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'R', false, 0);
        PDF::MultiCell(170, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($grandtotal, $decimalprice), '', 'R', false);

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


    public function default_KR_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35; //35

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_KR_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;
        $grandtotal = 0;
        $totalar =0;
        $tldb=0;
        $tlcr=0;

        $rowCount=0;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {
               
                //SKIP A/R Freight
               if($data[$i]['doc'] == 'GC' || $data[$i]['doc'] == 'GD'){
                continue;
               }

                $maxrow = 1;
                $acno = $data[$i]['acno'];
                $acnoname = $data[$i]['acnoname'];
                $ref = $data[$i]['ref'];
                $postdate = $data[$i]['postdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $client = $data[$i]['client'];
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
                    PDF::MultiCell(150, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(110, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
                    $rowCount++;
                }



                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];

                if($data[$i]['alias'] == 'AR1'){
                    $totalar += ($data[$i]['db']-$data[$i]['cr']);
                }

                // if (intVal($i) + 1 == $page) {
                //     $this->default_KR_header_PDF($params, $data);
                //     $page += $count;
                // }

                  if ($rowCount >= $page && $i < count($data) - 1) {
                          $this->default_KR_header_PDF($params, $data);
                        $rowCount = 0; // reset counter
                 }
            }
        }

        // var_dump($rowCount);
        // $grandtotal = $totaldb - $totalcr;
        PDF::SetFont($font, '', 5); //1
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize); //2
        PDF::MultiCell(425, 0, 'TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false);

        // PDF::MultiCell(0, 0, "\n\n\n"); //5 

         for ($i = 0; $i < 3; $i++) { //5
             $this->addrow('');
             $rowCount++;
            }

         
        PDF::SetFont($fontbold, '', $fontsize); //6
        PDF::MultiCell(425, 0, '', '', 'R', false, 0);
        PDF::MultiCell(170, 0, 'LESS DISCOUNT: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, $data[0]['disc'], '', 'R', false);
        
        $discamt= 0; 
        if($data[0]['disc'] !=0){
            $discamt= $totalar-( $this->othersClass->Discount($totalar,$data[0]['disc']));
        }else{
            $discamt= 0;  
        }

        PDF::SetFont($fontbold, '', $fontsize); //7
        PDF::MultiCell(425, 0, '', '', 'R', false, 0);
        PDF::MultiCell(170, 0, 'DISCOUNT AMOUNT: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($discamt,2), '', 'R', false);


        PDF::SetFont($font, '', 11); //8
        PDF::MultiCell(700, 0, '', '');
         
        PDF::SetFont($font, '', 5);  
        PDF::MultiCell(700, 0, '', 'B');
        
        PDF::SetFont($font, '', 5);  //9
        PDF::MultiCell(700, 0, '', '');
    

        PDF::SetFont($font, 'B', 12); //10
        PDF::MultiCell(700, 0, "CREDIT/DEBIT MEMO", '', 'L', false);
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B'); //11

        PDF::SetFont($font, '', 5);  //9
        PDF::MultiCell(700, 0, '', '');



        if (!empty($data)) {
            $totaldb1 = 0;
            $totalcr1 = 0;
            for ($i = 0; $i < count($data); $i++) {
               
               if($data[$i]['doc'] != 'GC' && $data[$i]['doc'] != 'GD'){
                continue;
               }
               
                $maxrow = 1;
                $acno = $data[$i]['acno'];
                $acnoname = $data[$i]['acnoname'];
                $ref = $data[$i]['ref'];
                $postdate = $data[$i]['postdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $client = $data[$i]['client'];
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
                    PDF::MultiCell(150, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(110, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
                }
                $totaldb1 += $data[$i]['db'];
                $totalcr1 += $data[$i]['cr'];
                $rowCount=$rowCount+8;

            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(425, 0, 'TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totaldb1, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalcr1, $decimalprice), '', 'R', false);
       
        PDF::SetFont($font, '', 15);
        PDF::MultiCell(700, 0, '', '');


        $tldb=$totaldb + $totaldb1;
        $tlcr=$totalcr + $totalcr1;
        $grandtotal = $tldb - $tlcr;


        PDF::SetFont($fontbold, '', $fontsize);
        $grandtotal = $grandtotal - $discamt;// $this->othersClass->Discount($grandtotal,$data[0]['disc']);

        PDF::MultiCell(425, 0, '', '', 'R', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'R', false, 0);
        PDF::MultiCell(170, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($grandtotal, $decimalprice), '', 'R', false);

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

     private function addrow($border)
     {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(90, 0, '', '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(150, 0,'', '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(110, 0, '', '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, '', '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, '', '', 'L', false, 1, '', '', false, 1);
    }
}
