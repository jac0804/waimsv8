<?php

namespace App\Http\Classes\modules\modulereport\sbc;

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
use App\Http\Classes\sbcscript\sbcscript;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class so
{

    private $modulename = "Sales Order";
    private $reportheader;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    private $sbcscript;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->reportheader = new reportheader;
        $this->sbcscript = new sbcscript;
    }

    public function createreportfilter($config)
    {
        $companyid = $config['params']['companyid'];

   
        $fields = ['radioprint', 'radioreporttype', 'clientname','prepared', 'approved', 'received', 'checked', 'requested', 'itime','htime', 'print'];
        $col1 = $this->fieldClass->create($fields);
    
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'itime.label', 'Time In');
        data_set($col1, 'htime.label', 'Time Out');
        data_set($col1, 'htime.readonly', false);
        data_set($col1, 'itime.readonly', false);
        data_set($col1, 'htime.type', 'input');
        data_set($col1, 'itime.type', 'input');
        data_set($col1, 'clientname.label', 'Representative');
        data_set($col1, 'clientname.readonly', false); //'readonly' => true,

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Sales Order', 'value' => '0', 'color' => 'red'],
            ['label' => 'Job Order', 'value' => '1', 'color' => 'red'],
            ['label' => 'Job Order Shooting', 'value' => '2', 'color' => 'red']
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $paramstr = "select
          'PDFM' as print,
          '' as amountformat,
          '0' as reporttype,
          '' as prepared,
          '' as approved,
          '' as received,
          '' as requested,
          '' as checked,
          '' as itime,
          '' as htime,
          '' as clientname";
    
        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($trno)
    {
        $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,if(head.ourref <> '',head.ourref,head.docno) as jono,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname,head.tax
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,if(head.ourref <> '',head.ourref,head.docno) as jono,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname,head.tax
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.doc='so' and head.trno='$trno' order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn  

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "default") {
            return $this->default_so_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {

            switch ($params['params']['dataparams']['reporttype']) {
                case '1':
                    return $this->Job_Order($params, $data);
                    break;
                case '2':
                    return $this->Job_Order_Shooting($params, $data);
                    break;

                default:
                    return $this->default_so_PDF($params, $data);
                    break;
            }
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
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('SALES ORDER', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('TERMS : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->printline();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R I P T I O N', '500px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');

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
            $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['gross'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
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
        $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
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
        $str .= '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }

    public function default_so_header_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $amtformat = $params['params']['dataparams']['amountformat'];
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
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        // PDF::SetFont($font, '', $fontsize);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

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

    public function default_so_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $amtformat = $params['params']['dataparams']['amountformat'];
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
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $qty = number_format($data[$i]['qty'], 2);
                $uom = $data[$i]['uom'];
                $amt = number_format($data[$i]['gross'], 2);
                $disc = $data[$i]['disc'];
                $ext = number_format($data[$i]['ext'], 2);

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '28', 0);
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

                    $totalext += $data[$i]['ext'];

                    if (PDF::getY() > 900) {
                        $this->default_so_header_PDF($params, $data);
                    }
                }
            }

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(700, 0, '', 'B');

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(700, 0, '', '');

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
    public function job_order_header($config,$data){
        $center = $config['params']['center'];
        $username =  $config['params']['user'];
        

        $qry = "select name, address, tel, code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 15;
        if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
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

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
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
        PDF::MultiCell(520, 0, "", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 15, "Company Name: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(320, 15, "". (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 15, "Date: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 15, "". (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 15, "JO NO.", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, (isset($data[0]['jono']) ? $data[0]['jono'] : ''), 'B', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 15, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(80, 15, "Representative: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(330, 15, "". $config['params']['dataparams']['clientname'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 15, "Time In: ", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(90, 15, "". $config['params']['dataparams']['itime'], 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 15, "Time Out: ", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 15, "". $config['params']['dataparams']['htime'], 'B', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(720, 0, "JOB DESCRIPTION", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    }
    public function Job_Order($config,$data) {

        $username = $config['params']['user'];
        $count = $page = 35;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
        }
        $this->job_order_header($config, $data);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 15, "", 'B', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(720, 15, "", 'LR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        $rem = $data[0]['rem'];
        $tax = $data[0]['tax'];
        $maxrow = 1;
        $arr_rem = $this->reporter->fixcolumn([$rem], '114', 0);


        $array = array_filter($arr_rem, function ($value) {
            return trim($value) !== '';
        });
        $maxrow = $this->othersClass->getmaxcolumn([$array]);
        $line = $maxrow;
        PDF::SetFont($font, '', $fontsize);
        for ($r = 0; $r < $maxrow; $r++) {
            
            PDF::MultiCell(20, 20, "", 'L', '', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(680, 20, '' . (isset($array[$r]) ? $array[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(20, 20, "", 'R', '', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        }
        $this->addsideline($line);

        PDF::MultiCell(20, 20, "", 'BL', '', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(680, 20, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(20, 20, "", 'BR', '', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        $totalext = 0;

        foreach ($data as $key => $value) {
            $totalext += $data[$key]['ext'];
        }
        $service = $totalext;
        $vat = 0;
        if($tax != 0){
           $service = ($service / 1.12);
           $vat = ($service * .12);
        }
        $vat = number_format($vat,2);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(10, 10, "", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(480, 10, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 10, "", 'RL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(210, 10, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 10, "", 'TR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        
        
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(50, 15, "Note: ", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(420, 15, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'RL', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(100, 15, "Service Charge: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(100, 15, "" . number_format($service, 2), 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'R', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(470, 15, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'RL', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(60, 15, "Item Cost: ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(140, 15, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'R', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);


        PDF::MultiCell(10, 15, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(470, 15, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(40, 15, "Others: ", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(160, 15, "" . $vat != 0 ? 'VAT: ' . $vat : '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'R', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(10, 15, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(100, 15, "Requested by:", '', 'L', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(165, 15, $config['params']['dataparams']['requested'] != "" ? $config['params']['dataparams']['requested'] : '', 'B', '', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(70, 15, "Check by: ", '', 'L', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(135, 15, $config['params']['dataparams']['checked'] != "" ? $config['params']['dataparams']['checked'] : '', 'B', '', false, 0, '',  '', true, 0, false, true, 25, 'M', true);

        
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(10, 15, "", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(200, 15, "TOTAL AMOUNT: ", '', 'L', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(10, 15, "", 'R', 'C', false, 1, '',  '', true, 0, false, true, 25, 'M', true);

        PDF::MultiCell(10, 15, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(100, 15, "Approved by:", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(370, 15, $config['params']['dataparams']['approved'] != "" ? $config['params']['dataparams']['approved'] : '', 'B', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'LR', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(200, 15, "". number_format($totalext, 2), 'B', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 15, "", 'R', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);

        PDF::SetFont($font, '', 8);
        PDF::MultiCell(10, 0, "", 'BL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(470, 0, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", 'RL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 0, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(140, 0, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", 'BR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        return PDF::Output($this->modulename . '.pdf', 'S');

    }
    public function addsideline($line) {
        $maxline = 10;
        $addline = $maxline - $line;
        $fontsize = "12";
        $font = '';
        if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
        }
        PDF::SetFont($font, '', $fontsize);
        if($maxline > $line){
            $addline = $maxline - $line;
            for ($i = 1; $i <= $addline; $i++) {
                $line++;

                PDF::MultiCell(20, 20, "", 'L', '', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(680, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(20, 20, "", 'R', '', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
            }
        }
        
    }

    public function job_order_header_shooting($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
        }

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(20, 40);

        PDF::SetXY(110, 92);
        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)


        PDF::SetFont($font, 'B', 18);
        PDF::MultiCell(520, 0, "", '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, " ". '', '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true); //Company Name: 
        PDF::MultiCell(290, 0, " ". (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(45, 0, " ", '', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true); //Date: 
        PDF::MultiCell(100, 0, "" . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);

        PDF::SetXY(555, 113);
     
        PDF::MultiCell(65, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true); // JO NO.
        PDF::SetFont($font, '', 15);
        PDF::MultiCell(100, 0, "" . (isset($data[0]['jono']) ? $data[0]['jono'] : ''), '', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);

        PDF::SetFont($font, 'B', 13);
        PDF::MultiCell(695, 0, "", '', '', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true); // Representative:
        PDF::MultiCell(300, 0, " " . $config['params']['dataparams']['clientname'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(55, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true); //Time In: 
        PDF::MultiCell(80, 0, " " . $config['params']['dataparams']['itime'], '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(65, 0, " ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true); //Time Out:
        PDF::MultiCell(100, 0, "" . $config['params']['dataparams']['htime'], '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


        PDF::SetFont($font, 'B', 16);
        PDF::MultiCell(695, 0, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, 'B', 18);
        PDF::MultiCell(695, 0, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    }
    public function Job_Order_Shooting($config,$data){
        $username = $config['params']['user'];
        $count = $page = 35;
        $totalext = 0;

        $font = "helvetica";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "12";
        // if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
        //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
        //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
        // }
        $this->job_order_header_shooting($config, $data);
        PDF::SetXY(555, 210);
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(700, 10, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(700, 10, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        $rem = $data[0]['rem'];
        $tax = $data[0]['tax'];
        $maxrow = 1;
        $arr_rem = $this->reporter->fixcolumn([$rem], '110', 0);


        $array = array_filter($arr_rem, function ($value) {
            return trim($value) !== '';
        });

        $maxrow = $this->othersClass->getmaxcolumn([$array]);
        $line = $maxrow;
        PDF::SetFont($font, '', $fontsize);
        for ($r = 0; $r < $maxrow; $r++) {

            PDF::MultiCell(20, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(660, 20, '' . (isset($array[$r]) ? $array[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(20, 20, "", '', '', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        }

        
        $this->addsideline_shooting($line);

        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(20, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(660, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(20, 20, "", '', '', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        $totalext = 0;

        foreach ($data as $key => $value) {
            $totalext += $data[$key]['ext'];
        }
        $service = $totalext;
        $vat = 0;
        if ($tax != 0) {
            $service = ($service / 1.12);
            $vat = ($service * .12);
        }
        $vat = number_format($vat, 2);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(700, 10, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
       

        PDF::SetFont($font, '', 2);
        PDF::MultiCell(10, 10, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(450, 10, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 10, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(40, 15, " ", '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(400, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(440, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', true);


        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(440, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(440, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(90, 15, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(160, 15, $config['params']['dataparams']['requested'] != "" ? $config['params']['dataparams']['requested'] : '', '', '', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(75, 15, " ", '', '', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(115, 15, $config['params']['dataparams']['checked'] != "" ? $config['params']['dataparams']['checked'] : '', '', '', false, 0, '',  '', true, 0, false, true, 25, 'M', true);


        PDF::MultiCell(10, 15, "", '', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
        PDF::MultiCell(10, 15, "", '', 'C', false, 1, '',  '', true, 0, false, true, 25, 'M', true);

        PDF::MultiCell(10, 10, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(90, 10, " ", '', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(350, 10, $config['params']['dataparams']['approved'] != "" ? $config['params']['dataparams']['approved'] : '', '', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);

        PDF::MultiCell(10, 10, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 10, "", '', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);

        PDF::SetFont($font, '', 8);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(440, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetXY(480, 474);


        PDF::SetFont($font, '', 5);
       
        PDF::MultiCell(10, 10, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(220, 10, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 10, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetXY(480, 486);

        #Service charage
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(100, 20, " ", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($font, 'B', 13);
        PDF::MultiCell(110, 20, "" . number_format($service, 2), '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);


        PDF::SetXY(480, 502);
        #Item Cost
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(70, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($font, 'B', 13);
        PDF::MultiCell(140, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);


        PDF::SetXY(480, 520);
        #Others
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(50, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($font, 'B', 13);
        PDF::MultiCell(160, 20, "" . $vat != 0 ? 'VAT: ' . $vat : '', '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);


        PDF::SetXY(480, 530);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(50, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($font, 'B', 13);
        PDF::MultiCell(160, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);


        PDF::SetXY(480, 550);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(110, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::SetFont($font, 'B', 13);
        PDF::MultiCell(100, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
        PDF::MultiCell(10, 20, "", '', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);

        PDF::SetXY(480, 568);
        #Amount
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 18, "", '', 'C', false, 0, '',  '', true, 0, false, true, 18, 'M', true);
        PDF::MultiCell(10, 18, "", '', '', false, 0, '',  '', true, 0, false, true, 18, 'M', true);
        PDF::SetFont($font, 'B', 15);
        PDF::MultiCell(210, 18, "" . number_format($totalext, 2), '', 'C', false, 0, '',  '', true, 0, false, true, 18, 'M', true);
        PDF::MultiCell(10, 18, "", '', 'C', false, 1, '',  '', true, 0, false, true, 18, 'M', true);

        PDF::SetXY(480, 571);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 18, "", '', 'C', false, 0, '',  '', true, 0, false, true, 18, 'M', true);
        PDF::MultiCell(10, 18, "", '', '', false, 0, '',  '', true, 0, false, true, 18, 'M', true);
        PDF::SetFont($font, 'B', 13);
        PDF::MultiCell(210, 18, "", '', 'C', false, 0, '',  '', true, 0, false, true, 18, 'M', true);
        PDF::MultiCell(10, 18, "", '', 'C', false, 1, '',  '', true, 0, false, true, 18, 'M', true);



        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function addsideline_shooting($line)
    {
  
        $fontsize = "12";
        $font = '';
        if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
        }

        $maxline = 10;

        if($maxline > $line){
            $addline = $maxline - $line;
            PDF::SetFont($font, '', $fontsize);
            for ($i = 1; $i <= $addline; $i++) {
                $line++;
                PDF::MultiCell(20, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(660, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(20, 20, "", '', '', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                
            }
        }

       
    }
  
}
