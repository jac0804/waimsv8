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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class pa
{

    private $modulename = "PRICE SCHEME";
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

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "
        
     select head.docno,date(head.dateid) as start,date(head.due) as end,head.ourref,head.yourref,head.rem,
	 item.barcode, item.itemname,stock.uom,stock.isamt,stock.disc
	 from pahead as head
	 left join pastock as stock on stock.trno = head.trno
	 left join item on item.itemid=stock.itemid 
     left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
	 where head.trno = '$trno' 

	 union all
	 
	 select head.docno,date(head.dateid) as start,date(head.due) as end,head.ourref,head.yourref,head.rem,
	 item.barcode, item.itemname,stock.uom,stock.isamt,stock.disc
	 from hpahead as head
	 left join hpastock as stock on stock.trno = head.trno
	 left join item on item.itemid=stock.itemid 
     left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
     where head.trno = '$trno' ";


        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn  

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "excel") {
            return $this->default_so_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_so_PDF($params, $data);
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
        $str .= '<br><br>';


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT #:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('YOURREF: ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('START DATE: ' . (isset($data[0]['start']) ? $data[0]['start'] : ''), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('END DATE: ' . (isset($data[0]['end']) ? $data[0]['end'] : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OURREF: ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';

        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('BARCODE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('DESCRIPTION', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('DISCOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');

        return $str;
    }

    public function default_so_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";
        $count = 35;
        $page = 35;
        $str .= $this->reporter->beginreport();

        $str .= $this->default_header($params, $data);

        $totalext = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['isamt'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['disc'], '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $totalext += $data[$i]['isamt'];
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($params, $data);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }
        $str .= '<br><br>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '265', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '270', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '265', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '265', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["approved"], '270', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["received"], '265', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }

    public function default_so_header_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name, address, tel, code from center where code = '" . $center . "'";
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

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
        PDF::MultiCell(0, 0, "\n");

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(120, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Document #:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(290, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "Yourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Start Date: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 0, (isset($data[0]['start']) ? $data[0]['start'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "End Date: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 0, (isset($data[0]['end']) ? $data[0]['end'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(220, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "Ourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(150, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(320, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "AMOUNT", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "DISCOUNT", '', 'R', false, 1);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
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

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_so_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            $totalamt = 0;
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $uom = $data[$i]['uom'];
                $amt = number_format($data[$i]['isamt'], 2);
                $disc = $data[$i]['disc'];

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);


                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_uom, $arr_amt, $arr_disc]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(150, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(320, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

                    if (PDF::getY() > 900) {
                        $this->default_so_header_PDF($params, $data);
                    }
                }
                $totalamt += $data[$i]['isamt'];
            }


            PDF::SetFont($font, '', 5);
            PDF::MultiCell(720, 0, '', 'B');

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(720, 0, '', '');

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
            PDF::MultiCell(670, 0, $data[0]['rem'], '', 'L');

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
}
