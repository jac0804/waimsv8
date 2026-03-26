<?php

namespace App\Http\Classes\modules\modulereport\kitchenstar;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\common\commonsbc;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class sj
{
    private $modulename = "Sales Journal";
    private $reportheader;
    private $commonsbc;
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
        $this->commonsbc = new commonsbc;
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'prepared', 'checked', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);


        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '0' as reporttype,
            '' as prepared,
            '' as checked
            "
        );
    }

    public function report_default_query($config)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $trno = $config['params']['dataid'];

        $query = "select head.trno,head.docno,right(head.docno,5) as drno,date_format(date(head.dateid),'%b. %d,%Y') as dateid,head.terms,
                        head.yourref,head.clientname,head.address,c.tel,
                        case ifnull(info.itemdesc,'') when '' then concat(item.itemname,' - ',item.sizeid,' - ',item.color) else info.itemdesc end as itemname,stock.uom,
                        stock.isamt,round(stock.isqty) as isqty,stock.ext,head.rem,a.clientname as agentname, 
                        ifnull(head.shipto,'') as shipto,ifnull(head.crref,'') as crref
                  from lahead as head
                  left join lastock as stock on stock.trno=head.trno
                  left join stockinfo as info on info.trno = stock.trno and info.line = stock.line
                  left join client as c on c.client=head.client
                  left join client as a on a.client=head.agent
                  left join item on item.itemid=stock.itemid
                  where head.trno = $trno and stock.noprint=0
                  union all
                  select head.trno,head.docno,right(head.docno,5) as drno,date_format(date(head.dateid),'%b. %d,%Y') as dateid,head.terms,
                        head.yourref,head.clientname,head.address,c.tel,
                        case ifnull(info.itemdesc,'') when '' then concat(item.itemname,' - ',item.sizeid,' - ',item.color) else info.itemdesc end as itemname,stock.uom,
                        stock.isamt,round(stock.isqty) as isqty,stock.ext,head.rem,a.clientname as agentname,
                         ifnull(head.shipto,'') as shipto,ifnull(head.crref,'') as crref
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join hstockinfo as info on info.trno = stock.trno and info.line = stock.line
                  left join client as c on c.clientid=head.clientid
                  left join client as a on a.clientid=head.agentid
                  left join item on item.itemid=stock.itemid
                  where head.trno = $trno and stock.noprint=0";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {

        return $this->default_sj_PDF($params, $data);
    }

    public function default_sj_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontbold2 = '';
        $fontsize = 9;
        $fontsize8 = 8;
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }

        if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
            $fontbold2 = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
        }

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('l', [1000, 800]);
        PDF::SetMargins(7, 15);

        PDF::SetFont($font, '', 9);

        PDF::MultiCell(0, 0, "\n");

        PDF::Image($this->companysetup->getlogopath($params['params']) . 'drlogo.JPG', '20', '35', 90, 35);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(325, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "D.R. NO : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::SetTextColor(255, 0, 0);
        PDF::MultiCell(85, 20, (isset($data[0]['drno']) ? $data[0]['drno'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0);


        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(325, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "DATE : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 2);
        PDF::MultiCell(85, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(325, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "TERMS : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 2);
        PDF::MultiCell(85, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(325, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "PO NO : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 2);
        PDF::MultiCell(85, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(470, 0, '', '');

        //customer
        //MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'TLR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'TLR', 'C', false);
        //MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, "CUSTOMER:", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(390, 20, ' ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'BLR', 'C', false);

        //address
        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'LR', 'C', false);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, "ADDRESS:", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 1);
        PDF::MultiCell(390, 20, ' ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'BLR', 'C', false);

        //contact no
        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'LR', 'C', false);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, "CONTACT NO:", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 1);
        PDF::MultiCell(390, 20, ' ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'BLR', 'C', false);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(470, 0, '', 'BLR', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(40, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(40, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(280, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(50, 0, "", 'L', 'C', false, 0);
        PDF::MultiCell(60, 0, "", 'LR', 'C', false);

        PDF::SetFont($font, 'B', $fontsize8 + 1.5);
        PDF::MultiCell(40, 10, "QTY", 'L', 'C', false, 0);
        PDF::MultiCell(40, 10, "UNIT", 'L', 'C', false, 0);
        PDF::MultiCell(280, 10, "DESCRIPTION", 'L', 'C', false, 0);
        PDF::SetFont($font, 'B', $fontsize8);
        PDF::MultiCell(50, 10, "UNIT PRICE", 'L', 'C', false, 0);
        PDF::SetFont($font, 'B', $fontsize8 + 1);
        PDF::MultiCell(60, 10, "AMOUNT", 'LR', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(40, 0, "", 'LB', 'C', false, 0);
        PDF::MultiCell(40, 0, "", 'LB', 'C', false, 0);
        PDF::MultiCell(280, 0, "", 'LB', 'C', false, 0);
        PDF::MultiCell(50, 0, "", 'LB', 'C', false, 0);
        PDF::MultiCell(60, 0, "", 'LRB', 'C', false);
    }

    public function default_sj_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $lineborder = 1;


        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "8";//9
        //if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
        //    $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
        //    $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        //}
         if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
             $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
             $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
         }
        $this->default_sj_header_PDF($params, $data);

        $countline = 15;
        $k = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $qty = number_format($data[$i]['isqty'], 0);
                $uom = $data[$i]['uom'];
                $itemname = $data[$i]['itemname'];
                $amt = number_format($data[$i]['isamt'], 2);
                $ext = number_format($data[$i]['ext'], 2);


                $arr_qty = $this->reporter->fixcolumn([$qty], '7', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '6', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '65', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname,  $arr_uom, $arr_qty, $arr_amt, $arr_ext]);

                //MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
                for ($r = 0; $r < $maxrow; $r++) {
                    $k++;
                    if ($k > $countline) {

                        goto end;
                    } else {
                        PDF::SetFont($font, '', $fontsize + 3);
                        PDF::MultiCell(40, 24, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        PDF::MultiCell(40, 24, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        PDF::MultiCell(280, 24, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LB', 'L', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        PDF::MultiCell(50, 24, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'LB', 'R', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        PDF::MultiCell(60, 24, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'LRB', 'R', false, 1, '',  '', true, 0, false, true, 24, 'M', false);
                    }
                }
                $totalext += $data[$i]['ext'];
                end:
            }

            if ($k > $countline) {
                $k = $countline;
            }
            if ($k != $countline) {
                $count = $countline - $k;
                if ($count != 0) {
                    for ($j = 0; $j < $count; $j++) {
                        $this->addrowremwline();
                    }
                }
                $this->addrowremwoline();
            } else {
                $this->addrowremwoline();
            }
            //comment

            PDF::SetFont($fontbold, '', $fontsize + 3);
            PDF::MultiCell(60, 20,  number_format($totalext, 2) . ' ', '', 'R', false, 1, 418, 588);

            $this->footer($params, $data);
        }


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    private function addrowremwoline()
    {
        PDF::MultiCell(40, 25, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', false);
        PDF::MultiCell(40, 25, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', false);
        PDF::MultiCell(280, 25, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', false);
        PDF::MultiCell(50, 25, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', false);
        PDF::MultiCell(60, 25, "", 'LRB', 'C', false, 1, '',  '', true, 0, false, true, 25, 'M', false);
    }
    private function addrowremwline()
    {
        PDF::MultiCell(40, 24, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
        PDF::MultiCell(40, 24, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
        PDF::MultiCell(280, 24, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
        PDF::MultiCell(50, 24, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
        PDF::MultiCell(60, 24, "", 'LRB', 'C', false, 1, '',  '', true, 0, false, true, 24, 'M', false);
    }


    public function footer($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $font = "";
        $fontbold = "";
        $fontbold2 = '';
        $border = "1px solid ";
        $fontsize = 8;
        $fontsize7 = 7;
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }
        if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
            $fontbold2 = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
        }

        //sr
        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'B', 'C', false);

        PDF::SetFont($fontbold2, '', $fontsize7);
        PDF::MultiCell(80, 20, " SALES REPRESENTATIVE:", 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 1);
        PDF::MultiCell(390, 20, ' ' . $data[0]['agentname'], 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'BLR', 'C', false);

        //note
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, " NOTE: ", 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 1);
        PDF::MultiCell(390, 20, ' ' . $data[0]['rem'], 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        /////
        PDF::SetFont($fontbold, '', $fontsize7 + 2);
        PDF::MultiCell(80, 15, ' ', 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(390, 15, ' PLS. ISSUE CHECK PAYABLE TO KITCHEN STAR, INC.', 'TR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(390, 0, '', 'BLR', 'C', false);


        /////bt
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, ' No. of Boxes: ' . (isset($data[0]['crref']) ? $data[0]['crref'] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(180, 20, ' Trucking: ' . (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(210, 20, '', 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(180, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(210, 0, '', 'LR', 'C', false);

        //data
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, ' ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(180, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(210, 20, '', 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(180, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(210, 0, '', 'LR', 'C', false);


        /////pc
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, ' Prepared by: ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(180, 20, ' Checked by: ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize7 + 2);
        PDF::MultiCell(210, 20, 'RECEIVED THE ABOVE MERCHANDISE', 'LR', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        //data
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, ' ' . $params['params']['dataparams']['prepared'], 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(180, 20, ' ' . $params['params']['dataparams']['checked'], 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize7 + 2);
        PDF::MultiCell(210, 20, 'IN GOOD ORDER AND CONDITION', 'LR', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(180, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(210, 0, '', 'BLR', 'C', false);
    }
}