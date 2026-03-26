<?php

namespace App\Http\Classes\modules\modulereport\main;

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
use DateTime;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class rd
{

    private $modulename = "Deposit Slip";
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
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 'PDFM' as print,
            '' as prepared,
            '' as approved,
            '' as received"
        );
    }

    public function report_default_query($filters)
    {
        $trno = $filters['params']['dataid'];

        

        $query = "select rd.trno,rd.docno,date(rd.dateid) as dateid,rd.rem,rd.yourref,rd.ourref,coa.acnoname, 
                         rch.docno as rcdocno, concat(rcd.bank,'-',rcd.branch) as bank,
                         rcd.checkno,rcd.amount,rcd.checkdate,coa.acno
                  from rdhead as rd
                  left join coa on coa.acnoid = rd.acnoid
                  left join hrcdetail as rcd on rcd.rdtrno=rd.trno
                  left join hrchead as rch on rch.trno=rcd.trno
                  where rd.trno= '$trno'
                  union all
                  select rd.trno,rd.docno,date(rd.dateid) as dateid,rd.rem,rd.yourref,rd.ourref,coa.acnoname, 
                        rch.docno as rcdocno, concat(rcd.bank,'-',rcd.branch) as bank,
                        rcd.checkno,rcd.amount,rcd.checkdate,coa.acno

                  from hrdhead as rd
                  left join coa on coa.acnoid = rd.acnoid
                  left join hrcdetail as rcd on rcd.rdtrno=rd.trno
                  left join hrchead as rch on rch.trno=rcd.trno
                  where rd.trno= '$trno'
                  union all
                  select rd.trno,rd.docno,date(rd.dateid) as dateid,rd.rem,rd.yourref,rd.ourref,coa.acnoname, 
                         rch.docno as rcdocno, concat(rcd.bank,'-',rcd.branch) as bank,
                         '' as checkno,rcd.amount,'' as checkdate,coa.acno
                  from rdhead as rd
                  left join coa on coa.acnoid = rd.acnoid
                  left join hrhdetail as rcd on rcd.rdtrno=rd.trno
                  left join hrhhead as rch on rch.trno=rcd.trno
                  where rd.trno= '$trno'
                  union all
                  select rd.trno,rd.docno,date(rd.dateid) as dateid,rd.rem,rd.yourref,rd.ourref,coa.acnoname, 
                         rch.docno as rcdocno, concat(rcd.bank,'-',rcd.branch) as bank,
                         '' as checkno,rcd.amount,'' as checkdate,coa.acno
                  from hrdhead as rd
                  left join coa on coa.acnoid = rd.acnoid
                  left join hrhdetail as rcd on rcd.rdtrno=rd.trno
                  left join hrhhead as rch on rch.trno=rcd.trno
                  where rd.trno= '$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        return $this->default_RD_PDF($params, $data);
    }

   public function default_RD_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $companyid = $params['params']['user'];
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
        PDF::SetMargins(40, 40);
        PDF::AddPage('p', [800, 1000]);

        // PDF::SetCellPaddings(4, 4, 4, 4);
      

            PDF::SetFont($fontbold, '', 18);
            PDF::MultiCell(400, 0, $this->modulename, '', 'L', false, 1);

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, "Docno #: ", '', 'L', false, 0, '',  '');
            PDF::MultiCell(15, 0, ":", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(285, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '');


            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, "BANK", '', 'L', false, 0, '',  '');
            PDF::MultiCell(15, 0, ":", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(285, 0, (isset($data[0]['acnoname']) ? $data[0]['acnoname'] : ''), '', 'L', false, 1, '', '');

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, "BRANCH", '', 'L', false, 0, '',  '');
            PDF::MultiCell(15, 0, ":", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(285, 0, '', '', 'L', false, 1, '', '');

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, "TYPE OF CHECK", '', 'L', false, 0, '',  '');
            PDF::MultiCell(15, 0, ":", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(285, 0, '', '', 'L', false, 1, '', '');

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, "ACCT NAME", '', 'L', false, 0, '',  '');
            PDF::MultiCell(15, 0, ":", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(285, 0,  (isset($data[0]['acnoname']) ? $data[0]['acnoname'] : ''), '', 'L', false, 1, '', '');

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, "ACCT NO", '', 'L', false, 0, '',  '');
            PDF::MultiCell(15, 0, ":", '', 'L', false, 0, '',  '');
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(285, 0,  (isset($data[0]['acno']) ? $data[0]['acno'] : ''), '', 'L', false, 1, '', '');


            PDF::SetFont($font, '', 5);
            PDF::MultiCell(400, 0, "", 'B', 'C', false, 1);
            
            PDF::SetFont($font, '', 2);
            PDF::MultiCell(400, 0, "", '', 'C', false, 1);


            PDF::SetFont($font, '', 5);
            PDF::MultiCell(200, 0, "", 'TR', 'C', false, 0);
            PDF::MultiCell(100, 0, "", 'TR', 'C', false, 0);
            PDF::MultiCell(100, 0, "", 'T', 'C', false, 1);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(200, 0, "BANK / BRANCH", 'R', 'C', false, 0);
            PDF::MultiCell(100, 0, "CHECK NUMBER", 'R', 'C', false, 0);
            PDF::MultiCell(100, 0, "CHECK AMOUNT", '', 'C', false, 1);

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(200, 0, "", 'BR', 'C', false, 0);
            PDF::MultiCell(100, 0, "", 'BR', 'C', false, 0);
            PDF::MultiCell(100, 0, "", 'B', 'C', false, 1);

            PDF::SetFont($font, '', 2);
            PDF::MultiCell(400, 0, "", '', 'C', false, 1);
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(400, 0, "", 'T', 'C', false, 1);




    }

    public function default_RD_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
         $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_RD_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);

        $totalamt = 0;
        $cno=0;
        $checknocount=0;
        PDF::SetCellPaddings(1, 1, 1, 1);
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;
                $bank = $data[$i]['bank'];;
                $checkno = $data[$i]['checkno'];
                $amount = number_format($data[$i]['amount'], $decimalcurr);
                $arrbank = $this->reporter->fixcolumn([$bank], '25', 0);
                $arrcheckno = $this->reporter->fixcolumn([$checkno], '12', 0);
                $arramount = $this->reporter->fixcolumn([$amount], '14', 0);
                 
                if (!empty(trim($checkno))) {
                        $cno++;
                    }

                $maxrow = $this->othersClass->getmaxcolumn([$arrbank, $arrcheckno, $arramount]);
                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                   if (!empty(trim($checkno))) {
                    PDF::MultiCell(20, 0, ($r == 0 ? $cno : '') .' .', '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(180, 0, (isset($arrbank[$r]) ? $arrbank[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arrcheckno[$r]) ? $arrcheckno[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arramount[$r]) ? $arramount[$r] : ''), '', 'R', false, 1, '', '', false, 1);
                    }else{
                    PDF::MultiCell(20, 0, '', '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(180, 0, '', '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, '', '', 'R', false, 1, '', '', false, 1);
                    }
                    
                }
                 $totalamt += $data[$i]['amount'];
            }
        }

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(400, 0, '', 'B');

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(400, 0, '', '');

            $datehere = $data[0]['dateid']; // date('m-d-Y');
            $depositdate = (new DateTime($datehere))->format('Y/m/d'); //'2025-05-21'
            
            PDF::MultiCell(0, 0, "\n\n\n");
            
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(200, 0, 'DEPOSIT DATE', '', 'C', false, 0);
            PDF::MultiCell(100, 0, 'TOTAL CHECKS', '', 'C', false, 0);
            PDF::MultiCell(100, 0, 'TOTAL AMOUNT', '', 'C', false, 1);

            //paano ko namn ngayon mabibilang kung ilan ang checkno kapag hidni ito empty?
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(200, 0, $depositdate, '', 'C', false, 0);
            PDF::MultiCell(100, 0,  number_format($cno, 0), '', 'C', false, 0); //number_format($totalcheck,0)
            PDF::MultiCell(100, 0, number_format($totalamt, $decimalcurr), '', 'R', false, 1);


            PDF::SetFont($font, '', 12);
            PDF::SetXY(40, 950);
            PDF::MultiCell(400, 0, date_format(date_create($current_timestamp), 'Y/m/d H:i:s') , '', 'L', false, 1);



        return PDF::Output($this->modulename . '.pdf', 'S');
    }




}
