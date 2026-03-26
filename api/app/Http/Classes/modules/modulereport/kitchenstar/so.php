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

class so
{
    private $modulename = "Sales Order";
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
        $fields = ['radioprint', 'prepared',  'approved', 'received', 'print'];
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
            '' as approved,
            '' as received
            "
        );
    }

    public function report_default_query($trno)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $query = "select head.rtype,head.rdate,cust.tel,cust.email,right(head.docno,5) as sono,head.trno, head.clientname, head.address, 
        date_format(date(head.dateid),'%b. %d, %Y') as dateid,head.terms, head.rem,head.agent,head.wh,
        item.barcode, case when ifnull(stockinfo.itemdesc,'')  = '' then  concat(item.itemname,' - ',item.sizeid,' - ',item.color) else stockinfo.itemdesc end as itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
        item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname,
        '' as shipto,'' as crref,wh.clientname as whname
        from sohead as head left join sostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        left join client as agent on agent.client=head.agent
        left join model_masterfile as m on m.model_id = item.model
        left join client on client.client=head.wh
        left join client as cust on cust.client = head.client
        left join client as wh on wh.clientid=stock.whid
        left join stockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
        where head.trno='$trno' and stock.noprint <> 1
        union all
        select head.rtype,head.rdate,cust.tel,cust.email,right(head.docno,5) as sono,head.trno, head.clientname, head.address, 
        date_format(date(head.dateid),'%b. %d, %Y') as dateid, head.terms, head.rem,head.agent,head.wh,
        item.barcode, case when ifnull(stockinfo.itemdesc,'')  = '' then  concat(item.itemname,' - ',item.sizeid,' - ',item.color) else stockinfo.itemdesc end  as itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
        item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname,
        '' as shipto,'' as crref,wh.clientname as whname             
        from hsohead as head 
        left join hsostock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid 
        left join client as agent on agent.client=head.agent
        left join model_masterfile as m on m.model_id = item.model
        left join client on client.client=head.wh
        left join client as cust on cust.client = head.client
        left join client as wh on wh.clientid=stock.whid
        left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
        where head.doc='so' and head.trno='$trno' and stock.noprint <> 1 order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {

        return $this->default_so_PDF($params, $data);
    }

    public function default_so_header_PDF($params, $data)
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
        PDF::SetMargins(10, 15);

        PDF::SetFont($font, '', 9);

        PDF::MultiCell(0, 0, "\n");

        PDF::Image($this->companysetup->getlogopath($params['params']) . 'drlogo.JPG', '20', '35', 90, 35);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(250, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(75, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "SO NO : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::SetTextColor(255, 0, 0);
        PDF::MultiCell(85, 20, (isset($data[0]['sono']) ? $data[0]['sono'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(250, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(75, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "DATE : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 2);
        PDF::MultiCell(85, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(250, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(75, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "TERMS : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 2);
        PDF::MultiCell(85, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(250, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(75, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold2, '', $fontsize + 2);
        PDF::MultiCell(50, 20, "PO NO : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 2);
        PDF::MultiCell(85, 20, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(460, 0, '', '');

        //customer
        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'TLR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'TLR', 'C', false);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, "CUSTOMER:", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(380, 20, ' ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'BLR', 'C', false);

        //address
        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'LR', 'C', false);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, "ADDRESS:", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 1);
        PDF::MultiCell(380, 20, ' ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'BLR', 'C', false);

        //contact no
        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'LR', 'C', false);

        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, "CONTACT NO:", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(380, 20, ' ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'BLR', 'C', false);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(460, 0, '', 'BLR', 'C', false);

        PDF::SetFont($font, 'B', $fontsize8 + 1.5);
        PDF::MultiCell(40, 26, "QTY", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 26, 'M', false);
        PDF::MultiCell(40, 26, "UNIT", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 26, 'M', false);
        PDF::MultiCell(270, 26, "DESCRIPTION", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 26, 'M', false);
        PDF::SetFont($font, 'B', 7);
        PDF::MultiCell(45, 26, "UNIT PRICE", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 26, 'M', false);
        PDF::SetFont($font, 'B', $fontsize8 + 1);
        PDF::MultiCell(65, 26, "WAREHOUSE", 'BLR', 'C', false, 1, '',  '', true, 0, false, true, 26, 'M', false);
    }

    public function default_so_PDF($params, $data)
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
        $fontsize = "9";
        $fontsize8 = "8";
        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }
        // if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
        //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
        //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        // }
        $this->default_so_header_PDF($params, $data);

        $countarr = 0;
        $countline = 15;
        $k = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $qty = number_format($data[$i]['qty'], 0);
                $uom = $data[$i]['uom'];
                $itemname = $data[$i]['itemname'];
                $amt = number_format($data[$i]['gross'], 2);
                // $ext = number_format($data[$i]['ext'], 2);
                $whname = $data[$i]['whname'];

                $arr_qty = $this->reporter->fixcolumn([$qty], '7', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '65', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                // $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);
                $arr_whname = $this->reporter->fixcolumn([$whname], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_amt, $arr_uom, $arr_qty, $arr_whname]);
                //MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
                for ($r = 0; $r < $maxrow; $r++) {
                    $k++;

                    if ($k > $countline) {
                        goto end;
                    } else {
                        PDF::SetFont($font, '', $fontsize + 3);
                        PDF::MultiCell(40, 24, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        PDF::MultiCell(40, 24, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        PDF::MultiCell(270, 24, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LB', 'L', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        PDF::MultiCell(45, 24, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'LB', 'R', false, 0, '',  '', true, 0, false, true, 24, 'M', false);
                        // PDF::MultiCell(45, 24, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'LB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(65, 24, (isset($arr_whname[$r]) ? $arr_whname[$r] : ''), 'LRB', 'C', false, 1, '',  '', true, 0, false, true, 24, 'M', false);
                    }
                }
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
            } else {
                $this->addrowremwoline();
            }
            $this->footer($params, $data);
        }


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    private function addrowremwoline()
    {
        PDF::MultiCell(40, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(40, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(270, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(45, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(65, 23, "", 'LRB', 'C', false, 1, '',  '', true, 0, false, true, 23, 'M', false);
    }

    private function addrowremwline()
    {
        PDF::MultiCell(40, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(40, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(270, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(45, 23, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 23, 'M', false);
        PDF::MultiCell(65, 23, "", 'LRB', 'C', false, 1, '',  '', true, 0, false, true, 23, 'M', false);
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
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(80, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'B', 'C', false);

        PDF::SetFont($fontbold2, '', $fontsize7);
        PDF::MultiCell(80, 20, " SALES REPRESENTATIVE:", 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 1);
        PDF::MultiCell(380, 20, ' ' . $data[0]['agentname'], 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'BLR', 'C', false);

        //note
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, " NOTE: ", 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize + 1);
        PDF::MultiCell(380, 20, ' ' . $data[0]['rem'], 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'BLR', 'C', false);

        /////
        PDF::SetFont($fontbold, '', $fontsize7 + 2);
        PDF::MultiCell(80, 20, ' ', 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(380, 20, ' PLS. ISSUE CHECK PAYABLE TO KITCHEN STAR, INC.', 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(380, 0, '', 'BLR', 'C', false);


        /////bt
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, ' No. of Boxes: ' . (isset($data[0]['crref']) ? $data[0]['crref'] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(180, 20, ' Trucking: ' . (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 20, '', 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(180, 0, '', 'LR', 'C', false, 0);
        PDF::MultiCell(200, 0, '', 'LR', 'C', false);

        //data
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, ' ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(180, 20, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 20, '', 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(180, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(200, 0, '', 'LR', 'C', false);


        /////pc
        PDF::SetFont($fontbold, '', $fontsize + 2);
        PDF::MultiCell(80, 20, ' Prepared by: ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 20, ' Approved by: ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 20, ' Received by: ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize7 + 2);
        PDF::MultiCell(200, 20, 'RECEIVED THE ABOVE MERCHANDISE', 'LR', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        //data
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 20, ' ' . $params['params']['dataparams']['prepared'], 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 20, ' ' . $params['params']['dataparams']['approved'], 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 20, ' ' . $params['params']['dataparams']['received'], 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize7 + 2);
        PDF::MultiCell(200, 20, 'IN GOOD ORDER AND CONDITION', 'LR', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(90, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(90, 0, '', 'BLR', 'C', false, 0);
        PDF::MultiCell(200, 0, '', 'BLR', 'C', false);
    }
}
