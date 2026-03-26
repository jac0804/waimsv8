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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class dm
{

    private $modulename = "Purchase Return";
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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'EXCEL', 'value' => 'excel', 'color' => 'red']
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {

        $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";


        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($trno)
    {
        $query = "select m.model_name as model,item.sizeid,date(head.dateid) as dateid, head.docno,head.wh, client.client, client.clientname,
  head.address, head.terms,head.rem, item.barcode,
  item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext,stock.ref,left(client.client,3) as supp
  from lahead as head
  left join lastock as stock on stock.trno=head.trno
  left join client on client.client=head.client
  left join item on item.itemid = stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  where head.doc='dm' and head.trno ='$trno'
  union all
  select m.model_name as model,item.sizeid,
  date(head.dateid) as dateid, head.docno, wh.client as wh,client.client, client.clientname,
  head.address, head.terms,head.rem, item.barcode,
  item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext,stock.ref,left(client.client,3) as supp
  from glhead as head
  left join glstock as stock on stock.trno=head.trno
  left join client as wh on wh.clientid=head.whid
  left join client on client.clientid=head.clientid
  left join item on item.itemid=stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  where head.doc='dm' and head.trno ='$trno' ";
        // var_dump($query);
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "excel") {
            return $this->default_dm_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_DM_PDF($params, $data);
        }
    }

    public function default_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = "";
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        // $str .= '<br><br>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'L', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier Code:', '50px', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->col((isset($data[0]['client']) ? $data[0]['client'] : ''), '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '400px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '125px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('DOCNO #: ', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '125px', null, false, $border, '', 'L', $font, '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER: ', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '50px', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->col('', '400px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '125px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('DATE: ', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '125px', null, false, $border, '', 'L', $font, '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS: ', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '50px', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->col('', '400px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '125px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('TERMS: ', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '125px', null, false, $border, '', 'L', $font, '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');

        return $str;
    }

    public function default_dm_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $count = 35;
        $page = 35;
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();
        $str .= $this->default_header($params, $data);

        $totalext = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '400px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $totalext = $totalext + $data[$i]['ext'];



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
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '400px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '265', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($data[0]['rem'], '270', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '265', null, false, $border, '', 'L', $font, '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '265', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '270', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '265', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '265', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["approved"], '270', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["received"], '265', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function default_DM_header_PDF($params, $data)
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

        if ($params['params']['companyid'] != 10 && $params['params']['companyid'] != 12) {
            $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        }
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);
        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Supplier Code : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['client']) ? $data[0]['client'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(140, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "Supplier : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(360, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_DM_PDF($params, $data)
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
        $this->default_DM_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $qty = number_format($data[$i]['qty'], 2);
                $uom = $data[$i]['uom'];
                $amt = number_format($data[$i]['amt'], 2);
                $disc = $data[$i]['disc'];
                $ext = number_format($data[$i]['ext'], 2);

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                $totalext += $data[$i]['ext'];

                if (PDF::getY() > 900) {
                    $this->default_DM_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

        PDF::MultiCell(0, 0, "\n");

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
