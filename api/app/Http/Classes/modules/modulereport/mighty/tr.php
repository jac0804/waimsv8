<?php

namespace App\Http\Classes\modules\modulereport\mighty;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class tr
{

    private $modulename = "Stock Request";
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


    public function createreportfilter()
    {
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            " select      
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received"
        );
    }


    public function report_default_query($trno)
    {
        $query = "select head.docno, head.client, head.clientname as requestor, head.terms,
                    head.address, date(head.dateid) as dateid, head.wh as destination, head.rem,
                    item.barcode, item.itemname, stock.uom,
                    stock.rrqty, stock.qty, stock.qa, stock.reqqty, (stock.reqqty - stock.rrqty) as pending,
                    stock.rem as remarks,ifnull(wh2.clientname,'') as source, head.yourref,head.ourref as prref
                    from htrhead as head
                    left join htrstock as stock on head.trno = stock.trno
                    left join item on item.itemid=stock.itemid
                    left join client as wh on wh.client=head.wh
                    left join hheadinfotrans as info on info.trno=head.trno
                    left join client as wh2 on wh2.clientid = info.wh2
                    where head.trno =  '$trno'
                    union all
                    select head.docno, head.client, head.clientname as requestor, head.terms,
                    head.address, date(head.dateid) as dateid, head.wh as destination, head.rem,
                    item.barcode, item.itemname, stock.uom,
                    stock.rrqty, stock.qty, stock.qa, stock.reqqty, (stock.reqqty - stock.rrqty) as pending,
                    stock.rem as remarks,ifnull(wh2.clientname,'') as source, head.yourref,head.ourref as prref
                    from trhead as head
                    left join trstock as stock on head.trno = stock.trno
                    left join item on item.itemid=stock.itemid
                    left join client as wh on wh.client=head.wh
                    left join headinfotrans as info on info.trno=head.trno
                    left join client as wh2 on wh2.clientid = info.wh2
                    where head.trno = '$trno'";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "default") {
            return $this->default_tr_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_TR_PDF($params, $data);
        }
    }

    public function default_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = "";
        $font =  "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br><br>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('STOCK REQUEST', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Requestor : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['requestor']) ? $data[0]['requestor'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Source : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['source']) ? $data[0]['source'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('Yourref : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Destination : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['destination']) ? $data[0]['destination'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('PR Ref : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['prref']) ? $data[0]['prref'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('BARCODE', '100px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('REQUEST QTY', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('APPROVED QTY', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('WAREHOUSE', '100px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');

        return $str;
    }

    public function default_tr_layout($params, $data)
    {

        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $font =  "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $count = 35;
        $page = 35;
        $str .= $this->reporter->beginreport();
        $str .= $this->default_header($params, $data);

        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['barcode'], '100px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '400px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['reqqty'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['rrqty'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['destination'], '100px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($params, $data);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col(' ', '50px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
        // $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    } //end fn

    public function default_TR_header_PDF($params, $data)
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

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

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
        PDF::MultiCell(80, 0, "Requestor: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['requestor']) ? $data[0]['requestor'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Source: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['source']) ? $data[0]['source'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Yourref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Destination: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['destination']) ? $data[0]['destination'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "PR Ref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['prref']) ? $data[0]['prref'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "REQUEST QTY", '', 'R', false, 0);
        PDF::MultiCell(120, 0, "APPROVED QTY", '', 'R', false, 0);
        PDF::MultiCell(120, 0, "WAREHOUSE", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_TR_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
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
        $this->default_TR_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;
            $barcode = $data[$i]['barcode'];
            $itemname = $data[$i]['itemname'];
            $uom = $data[$i]['uom'];
            $reqqty = number_format($data[$i]['reqqty'], $decimalqty);
            $rrqty = number_format($data[$i]['rrqty'], $decimalqty);
            $stockwh = $data[$i]['destination'];

            $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '16', 0);
            $arr_reqqty = $this->reporter->fixcolumn([$reqqty], '13', 0);
            $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '13', 0);
            $arr_stockwh = $this->reporter->fixcolumn([$stockwh], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_uom, $arr_reqqty, $arr_rrqty, $arr_stockwh]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(100, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0);
                PDF::MultiCell(200, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
                PDF::MultiCell(50, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0);
                PDF::MultiCell(100, 0, (isset($arr_reqqty[$r]) ? $arr_reqqty[$r] : ''), '', 'R', false, 0);
                PDF::MultiCell(120, 0, (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : ''), '', 'R', false, 0);
                PDF::MultiCell(120, 0, (isset($arr_stockwh[$r]) ? $arr_stockwh[$r] : ''), '', 'C', false);
            }

            if (intVal($i) + 1 == $page) {
                $this->default_TR_header_PDF($params, $data);
                $page += $count;
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

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
}
