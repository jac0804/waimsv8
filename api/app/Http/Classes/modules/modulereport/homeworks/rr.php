<?php

namespace App\Http\Classes\modules\modulereport\homeworks;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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
use DateTime;

class rr
{

    private $modulename = "Receiving Items";
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
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $ispurchases = $this->companysetup->getispurchases($config['params']);

        $fields = ['radioprint',  'approved', 'checked', 'print'];

        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'EXCEL', 'value' => 'excel', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }



    public function reportparamsdata($config)
    {
        $trno = $config['params']['trno'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $ispurchases = $this->companysetup->getispurchases($config['params']);

        $approv = $this->coreFunctions->datareader("select postedby as value from cntnum where trno = $trno");
        $approveds = $this->coreFunctions->datareader("select name as value from useraccess where username = '$approv'");
        $approved = (!empty($approveds) && isset($approveds)) ? $approveds : '';

        $paramstr = "select
        'PDFM' as print, 
        '$approved' as approved,
        '' as checked ";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($config)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $trno = $config['params']['dataid'];
        $query = "select head.docno,head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model,
        cl.mobile,cl.tel,cl.tel2,cl.email, wh.addr as whadd,cl.client,cl.contact
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join client as cl  on cl.client=head.client
        where head.trno='$trno'
        union all
        select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model,
         cl.mobile,cl.tel,cl.tel2,cl.email, wh.addr as whadd,cl.client,cl.contact
        from (glhead as head
        left join glstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        left join client as cl on cl.clientid=head.clientid
        where head.trno='$trno'
        order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "excel") {
            return $this->default_RR_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_RR_PDF($params, $data);
        }
    }

    public function default_header($params, $data)
    {
        $this->modulename = app('App\Http\Classes\modules\purchase\rr')->modulename;

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
        $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '100px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '350px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT:', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : '') . QrCode::size(100)->generate($data[0]['docno'] . '-' . $data[0]['trno']), '150px', null, false, $border, '', 'L', $font, '13', 'B', '', '') . '<br />';

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER NAME', '100px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '350px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');

        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('DATE:', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150px', null, false, $border, '', 'L', $font, '13', 'B', '', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('SUPPLIER CODE :', '100px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['client']) ? $data[0]['client'] : ''), '350px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '150px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->col('ADDRESS :', '100px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '350px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('TERMS:', '50px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150px', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('BARCODE', '100px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DESCRIPTION', '450px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px'); //300
        $str .= $this->reporter->col('UNIT COST', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DISC', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        return $str;
    }

    public function default_RR_layout($params, $data)
    {

        $decimal = $this->companysetup->getdecimal('currency', $params['params']);
        $str = '';
        $count = 35;
        $page = 35;
        $font =  "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();
        $str .= $this->default_header($params, $data);


        $totalext = 0;
        for ($i = 0; $i < count($data); $i++) {
            $ext = number_format($data[$i]['ext'], $decimal);
            $ext = $ext < 0 ? '-' : $ext;
            $netamt = number_format($data[$i]['gross'], $decimal);
            $netamt = $netamt < 0 ? '-' : $netamt;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            // $str .= $this->reporter->col($data[$i]['dateid'], '50px', null, false, $border, '', 'TC', $font, $fontsize, '', '', '2px');
            // $str .= $this->reporter->col($data[$i]['docno'], '100px', null, false, $border, '', 'TC', $font, $fontsize, '', '', '2px');
            // $str .= $this->reporter->col($data[$i]['client'], '100px', null, false, $border, '', 'TL', $font, $fontsize, '', '', '2px');
            // // $str .= $this->reporter->col($data[$i]['clientname'], '100px', null, false, $border, '', 'TL', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col("'" . $data[$i]['barcode'], '100px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['qty'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '450px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px'); //300
            // $str .= $this->reporter->col($data[$i]['expiry'], '100px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['gross'], $this->companysetup->getdecimal('price', $params['params'])), '50px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($ext, '50px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $totalext = $totalext + $data[$i]['ext'];
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($params, $data);

                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '50px', null, false, '1px dotted', 'T', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted', 'T', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '300px', null, false, '1px dotted', 'T', 'L', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '100px', null, false, '1px dotted', 'T', 'L', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col(' ', '125px', null, false, '1px dotted', 'T', 'R', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted', 'T', 'L', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted', 'T', 'R', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REMARKS : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $trno = $params['params']['dataid'];
        $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from lahead where trno = $trno
                                                                                        union all
                                                                                    select createby as user from glhead where trno = $trno) as s ");
        $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '265', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '270', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('Checked By :', '265', null, false, $border, '', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prep, '265', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["approved"], '270', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']["checked"], '265', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    } //end fn




    public function default_RR_header_PDF($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];

        $font = "";
        $fontbold = "";
        $fontsize = 9;
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

        $newpageadd = 1;

        $qry = "select name,address,tel,zipcode from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(290, 0, $combined . "\n" . 'Phone: ' . $tel, '', 'L', false, 1, '350',  '20', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(720, 110, "", 'TLR', '', false, 1, '40',  '10', true, 0, false, true, 0, '', true);


        // PDF::Image($this->companysetup->getlogopath($params['params']) . 'hmlogo.jpg', '40', '20', 300, 65);
        $imagePath = $this->companysetup->getlogopath($params['params']) . 'hmlogo.jpg';
        // $logohere=isset($imagePath) ? PDF::Image($imagePath, 40, 20, 300, 65) :  'No image found';

        $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 40, 20, 300, 65) : 'No image found';

        // $add = (isset($headerdata[0]->address) ? strtoupper($headerdata[0]->address) : '');
        // $tel = (isset($headerdata[0]->tel) ? strtoupper($headerdata[0]->tel) : '');
        // $zip = (isset($headerdata[0]->zipcode) ? strtoupper($headerdata[0]->zipcode) : '');

        $add = '7F Main Building, Metropolitan Medical Ctr., 1357 Masangkay St., Sta. Cruz';
        $zip = 'Manila 1008';
        $tel = '(632)8735-7866/8735-7844';


        $combined = $add . ' ' . $zip;

        PDF::SetFont($font, '', 12);
        PDF::MultiCell(290, 0, $combined . "\n" . 'Phone: ' . $tel, '', 'L', false, 1, '350',  '20', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', 16);
        PDF::MultiCell(720, 0, 'RECEIVING REPORT', '', 'R', false, 1, '',  '55', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(720, 0, 'RR # : ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false, 1, '',  '80', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(100, 0, 'REPRINT', '', 'C', false, 0, '600',  '95', true, 0, false, true, 0, 'B', true);

        PDF::Ln(25);

        PDF::SetFillColor(125, 125, 125);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 12, "", '', 'L', true, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(410, 15, "Supplying Company", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 15, "Issued Date: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(210, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 15, "Vendor Name: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(310, 15, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 15, "Expiration Date: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(210, 15, (isset($data[0]['expiry']) ? $data[0]['expiry'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        $add = isset($data[0]['address']) ? $data[0]['address'] : '';
        // $add = '7F Main Building, Metropolitan Medical Ctr., 1357 Masangkay St., Sta. Cruz Manila 1008. Pinamalayan Oriental Mindoro Philippines, Santa Isabel Sitio Puting Tubig Quezon City Philippines MeTRO MANILA 255B GREGORIO ARANETA';
        $maxChars = 50;
        $adds = strlen($add);
        $firstLine = '';
        $remaininglines = [];
        $addsz = '';

        if ($adds > $maxChars) {
            $firstLine = substr($add, 0, $maxChars);
            $remaining = substr($add, $maxChars);
            // Split remaining address into multiple lines without cutting words
            while (strlen($remaining) > $maxChars) {
                // Find the last space within the maxChars limit
                $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

                // If there's no space, just cut at maxChars
                if ($spacePos === false) {
                    $nextLine = substr($remaining, 0, $maxChars);
                    $remaining = substr($remaining, $maxChars);
                } else {
                    $nextLine = substr($remaining, 0, $spacePos);
                    $remaining = substr($remaining, $spacePos + 1);
                }

                $remainingLines[] = $nextLine;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remaining) > 0) {
                $remainingLines[] = $remaining;
            }
        } else {
            $addsz = $add;
        }


        if ($adds > $maxChars) {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 0, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 0, "Vendor Address: ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(310, 0, $firstLine, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 0, "", 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 0, "Released By:", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(210, 0, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            // Loop through remaining lines and print them
            foreach ($remainingLines as $line) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 0, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(90, 0, " ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(310, 0, $line, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 0, "", 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(90, 0, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(210, 0, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }
        } else {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(85, 15, "Vendor Address: ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(315, 15, $addsz, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 15, "Released By: ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(210, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        }

        $mobile = (isset($data[0]['mobile']) ? $data[0]['mobile'] : '');
        $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
        $tel2 = (isset($data[0]['tel2']) ? $data[0]['tel2'] : '');

        $contact = '';
        if ($mobile != '') {
            $contact = $mobile;
        } elseif ($tel != '') {
            $contact = $tel;
        } elseif ($tel2 != '') {
            $contact = $tel2;
        }


        $deliverto = isset($data[0]['whname']) ? $data[0]['whname'] : '';
        $maxCharsq = 43;
        $del = strlen($deliverto);
        $firstLinez = '';
        $remaininglinez = [];
        $addline = '';

        if ($del > $maxCharsq) {
            $firstLinez = substr($deliverto, 0, $maxCharsq);
            $remaining = substr($deliverto, $maxCharsq);
            // Split remaining delivertoress into multiple lines without cutting words
            while (strlen($remaining) > $maxCharsq) {
                // Find the last space within the maxCharsq limit
                $spacePos = strrpos(substr($remaining, 0, $maxCharsq), ' ');

                // If there's no space, just cut at maxCharsq
                if ($spacePos === false) {
                    $nextLine = substr($remaining, 0, $maxCharsq);
                    $remaining = substr($remaining, $maxCharsq);
                } else {
                    $nextLine = substr($remaining, 0, $spacePos);
                    $remaining = substr($remaining, $spacePos + 1);
                }

                $remaininglinez[] = $nextLine;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remaining) > 0) {
                $remaininglinez[] = $remaining;
            }
        } else {
            $addline = $deliverto;
        }


        if ($del > $maxCharsq) {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 15, "Contact Details: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(310, 15,  $contact, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 15, "Please Deliver to: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(210, 15, $firstLinez, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            // Loop through remaining lines and print them
            foreach ($remaininglinez as $linez) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 0, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(90, 0, " ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(310, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(10, 0, "", 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(300, 0, $linez, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
                // PDF::SetFont($font, '', $fontsize);
                // PDF::MultiCell(210, 0, $linez, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }
        } else {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 15, "Contact Details: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(310, 15, $contact, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 15, "Please Deliver to: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(210, 15, $addline, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        }


        $whadd = (isset($data[0]['whadd']) ? $data[0]['whadd'] : '');

        $maxCharss = 45;
        $whadds = strlen($whadd);
        $firstLines = '';
        $remainingLiness = [];
        $whaddsz = '';

        if ($whadds > $maxCharss) {
            $firstLines = substr($whadd, 0, $maxCharss);
            $remainings = substr($whadd, $maxCharss);
            // Split remaining address into multiple lines without cutting words
            while (strlen($remainings) > $maxCharss) {
                // Find the last space within the maxChars limit
                $spacePoss = strrpos(substr($remainings, 0, $maxCharss), ' ');

                // If there's no space, just cut at maxChars
                if ($spacePoss === false) {
                    $nextLines = substr($remainings, 0, $maxCharss);
                    $remainings = substr($remainings, $maxCharss);
                } else {
                    $nextLines = substr($remainings, 0, $spacePoss);
                    $remainings = substr($remainings, $spacePoss + 1);
                }

                $remainingLiness[] = $nextLines;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remainings) > 0) {
                $remainingLiness[] = $remainings;
            }
        } else {
            $whaddsz = $whadd;
        }

        $lineCount = count($remainingLiness); //sample 4 yung linecount

        //65 char para sa warehouse
        if ($whadds > $maxCharss) {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(100, 15, "Email Address: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 15, (isset($data[0]['email']) ? $data[0]['email'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 15, $firstLines, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(5, 15, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

            for ($i = 0; $i < $lineCount; $i++) {
                if ($i == 0) {
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(30, 15, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::MultiCell(100, 15, "Payment Terms: ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(280, 15, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                }
                if ($i == 1) {
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(30, 15, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::MultiCell(100, 15, "Currency: ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(280, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                }
                if ($i == 2) {
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(10, 15, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::MultiCell(215, 15, "Your Sales Representative: ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(185, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                }

                if ($i == 3) {
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(10, 15, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::MultiCell(215, 15, "Sales Rep Contact Number: ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(185, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                }

                if ($i == 4) {
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(10, 15, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::MultiCell(215, 15, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(185, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                }

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(300, 15, $remainingLiness[$i], '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(5, 15, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);




                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(30, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(100, 15, "Currency:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(280, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(90, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(210, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(150, 15, "Your Sales Representative: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(250, 15, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(300, 15, '', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);


                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(150, 15, "Sales Rep Contact Number:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(250, 15, $contact, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(300, 15, '', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }
        } else {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(215, 15, "Email Address: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(185, 15, (isset($data[0]['email']) ? $data[0]['email'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 15, $whaddsz, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(5, 15, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(30, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(100, 15, "Payment Terms: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(280, 15, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(210, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(30, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(100, 15, "Currency:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(280, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(210, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(150, 15, "Your Sales Representative: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(250, 15, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 15, '', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(150, 15, " Sales Rep Contact Number:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(250, 15, $contact, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        }
        PDF::MultiCell(410, 0, "", '', 'C', false, 0);
        PDF::MultiCell(310, 0, "", 'L', 'C', false, 1);


        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(55, 18, "Line Item", 'TB', 'C', false, 0);
        PDF::MultiCell(100, 18, "EAN", 'TB', 'C', false, 0);
        PDF::MultiCell(295, 18, "Item Description", 'TB', 'C', false, 0);
        PDF::MultiCell(50, 18, "RR Qty", 'TB', 'C', false, 0);
        PDF::MultiCell(50, 18, "UOM", 'TB', 'C', false, 0);
        PDF::MultiCell(70, 18, "Cost", 'TB', 'C', false, 0);
        PDF::MultiCell(100, 18, "Total Cost", 'TB', 'C', false, 1);
    }

    public function default_RR_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $totalqty = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_RR_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');


        $countarr = 1;
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
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '44', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(55, 15, ($r == 0 ? $countarr  : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                // PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                // PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(295, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                PDF::MultiCell(70, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                // PDF::MultiCell(70, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

                if ($r == 0) {
                    $countarr++;
                }
            }


            $totalext += $data[$i]['ext'];
            $totalqty += $data[$i]['qty'];

            // if (PDF::getY() > 720) {
            //     $this->default_RR_header_PDF($params, $data);
            // }

            if ($i < count($data) - 1 && PDF::getY() >= 905) {
                $this->other_footer($params, $data);
                $this->default_RR_header_PDF($params, $data);
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, 'GRAND TOTAL: ', '', 'L', false, 0);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(295, 0, '', '', 'R', false, 0);
        PDF::MultiCell(50, 0, number_format($totalqty, 2), '', 'R', false, 0);
        PDF::MultiCell(50, 0, '', '', 'R', false, 0);
        PDF::MultiCell(70, 0, '', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(55, 0, 'REMARKS: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(665, 0, $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(720, 12, "", '', 'L', false, 1, '40', '870', true, 0, false, true, 0, 'B', true);



        $trno = $params['params']['dataid'];
        $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from lahead where trno = $trno
                                                                                        union all
                                                                                     select createby as user from glhead where trno = $trno) as s ");
        $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");

        PDF::MultiCell(206, 0, 'Prepared By: ', 'TL', 'C', false, 0);
        PDF::MultiCell(50, 0, ' ', 'T', 'L', false, 0);
        PDF::MultiCell(208, 0, 'Checked By: ', 'TL', 'C', false, 0);
        PDF::MultiCell(50, 0, '', 'T', 'L', false, 0);
        PDF::MultiCell(206, 0, 'Approved By: ', 'TLR', 'C');

        PDF::MultiCell(206, 0, '', 'L', 'L', false, 0);
        PDF::MultiCell(50, 0, ' ', '', 'L', false, 0);
        PDF::MultiCell(208, 0, '', 'L', 'L', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(206, 0, '', 'LR', 'L');

        // PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(206, 0, $prep, 'L', 'C', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(208, 0, $params['params']['dataparams']['checked'], 'L', 'C', false, 0);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(206, 0, $params['params']['dataparams']['approved'], 'LR', 'C');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, 'AUTHORIZED SIGNATURE OVER PRINTED NAME NAME/DATE', 'TLBR', 'C', false, 1);

        PDF::MultiCell(720, 0, '', '', 'L', false, 1);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $username = $params['params']['user'];

        // $username = $params['params']['user'];

        // PDF::MultiCell(720, 12, "", '', 'L', false, 1, '40', '945', true, 0, false, true, 0, 'B', true);

        $dt = new DateTime($current_timestamp);

        $date = $dt->format('n/j/Y');
        $time = $dt->format('g:i:sa');
        $time = strtoupper($time); //  AM/PM (malaking letter)

        // $curpage = $this->reporter->pagenumber('Page');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 0, "", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        // $str .= $this->reporter->pagenumber('Page');
        //    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);
        PDF::MultiCell(106, 0,  'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(116, 0, "Date Printed", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(116, 0, $date, 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(16, 0, 'at', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(116, 0, $time, 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 0, 'User: ', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 0, $username, 'TB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function other_footer($params, $data)
    {

        $companyid = $params['params']['companyid'];
        // $reporttype = $params['params']['dataparams']['reporttype'];
        $username = $params['params']['user'];
        $count = $page = 3;
        $header_count = 1;
        $total_header_count = 1;
        $trno = $params['params']['dataid'];

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "9";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }


        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

        // PDF::SetFont($font, '', 1);
        // PDF::MultiCell(720, 0, '', 'B');

        PDF::MultiCell(720, 12, "", '', 'L', false, 1, '40', '943', true, 0, false, true, 0, 'B', true);

        // $trno = $params['params']['dataid'];
        // $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
        //                                                                                 union all
        //                                                                              select createby as user from hpohead where trno = $trno) as s ");
        // $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");


        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(206, 0, 'Prepared By: ', '', 'L', false, 0);
        // PDF::MultiCell(50, 0, ' ', '', 'L', false, 0);
        // PDF::MultiCell(208, 0, 'Approved By: ', '', 'L', false, 0);
        // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(206, 0, 'Checked By: ', '', 'L');

        // PDF::MultiCell(0, 0, "\n");

        // PDF::MultiCell(206, 0, $prep, '', 'L', false, 0);
        // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(208, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(206, 0, $params['params']['dataparams']['checked'], '', 'L');


        // PDF::MultiCell(720, 0, '', '', 'L', false, 1);

        // PDF::MultiCell(206, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(258, 0, 'AUTHORIZED SIGNATURE OVER PRINTED NAME', 'T', 'L', false, 0);
        // PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(206, 0, '', '', 'L');

        // PDF::MultiCell(206, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(208, 0, 'NAME/DATE', '', 'C', false, 0);
        // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(206, 0, '', '', 'L');

        // PDF::MultiCell(720, 0, '', '', 'L', false, 1);


        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $username = $params['params']['user'];


        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $username = $params['params']['user'];


        $dt = new DateTime($current_timestamp);

        $date = $dt->format('n/j/Y');
        $time = $dt->format('g:i:sa');
        $time = strtoupper($time); //  AM/PM (malaking letter)

        // $curpage = $this->reporter->pagenumber('Page');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 0, "", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        // $str .= $this->reporter->pagenumber('Page');
        //    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);
        PDF::MultiCell(106, 0,  'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(116, 0, "Date Printed", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(116, 0, $date, 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(16, 0, 'at', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(116, 0, $time, 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 0, 'User: ', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 0, $username, 'TB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    }
}
