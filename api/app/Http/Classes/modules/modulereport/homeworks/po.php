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
use Illuminate\Support\Facades\URL;
use App\Http\Classes\reportheader;
use DateTime;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class po
{

    private $modulename = "Purchase Order";
    private $reportheader;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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

        $fields = ['radioprint', 'radioreporttype',  'checked',  'approved'];
        $col1 = $this->fieldClass->create($fields);

        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Show Cost and SRP', 'value' => '0', 'color' => 'blue'],
                ['label' => 'Show SRP Only', 'value' => '1', 'color' => 'blue'],
                ['label' => 'Not Show Cost and SRP', 'value' => '2', 'color' => 'blue'],
                ['label' => 'Default', 'value' => '3', 'color' => 'blue']
            ]
        );

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'defaults', 'value' => 'default', 'color' => 'red']
            ['label' => 'EXCEL', 'value' => 'excel', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function reportparamsdata($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        // var_dump($user);
        // $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
        //                                                                                 union all
        //                                                                              select createby as user from hpohead where trno = $trno) as s ");
        // // $prepared = (!empty($prepared) && isset($prepared)) ? $prepared : '';

        // $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");
        // $prep = (!empty($prep) && isset($prep)) ? $prep : '';
        // $approv = $this->coreFunctions->datareader("select postedby as value from transnum where trno = $trno");
        // $approveds = $this->coreFunctions->datareader("select name as value from useraccess where username = '$approv'");
        // $approved = (!empty($approveds) && isset($approveds)) ? $approveds : '';

        //  '$prep' as prepared,

        $paramstr = "select
          'PDFM' as print,
           '0' as reporttype,
          '' as checked,
          '' as approved";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($trno)
    {
        $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,date(head.expiration) as exp,
        head.terms,head.rem, item.partno, item.barcode,client.mobile,client.tel,client.tel2,client.email,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext, m.model_name as model,item.sizeid,stock.cost,
        wh.clientname as whname, wh.addr as whadd,item.itemid,'UNPOSTED' as statuss,client.contact,sit.amt1
       
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join client as wh on wh.client=head.wh
        left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line

    
    
        where head.doc='po' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address,date(head.expiration) as exp, head.terms,head.rem, item.partno, item.barcode,client.mobile,client.tel,client.tel2,client.email,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid,stock.cost,
        wh.clientname as whname, wh.addr as whadd,item.itemid,'POSTED' as statuss,client.contact,sit.amt1
     
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join client as wh on wh.client=head.wh
        left join hstockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line

        where head.doc='po' and head.trno='$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {

        $reporttype = $params['params']['dataparams']['reporttype'];
        $print = $params['params']['dataparams']['print'];
        switch ($print) {
            case 'excel':
                switch ($reporttype) {
                    case '0': //show cost and srp
                        return $this->other_option_layoutrep($params, $data);
                        break;
                    case '1': //show srp 
                        return $this->other_option_layoutrep($params, $data);
                        break;
                    case '2': //not show cost and srp
                        return $this->other_option_layoutrep($params, $data);
                        break;
                    case '3': //def
                        return $this->default_po_layout($params, $data);
                        break;
                }
                break; //end ng excel
            case 'PDFM':
                switch ($reporttype) {
                    case '0': //show cost and srp
                        return $this->other_option_layout_pdf($params, $data);
                        break;
                    case '1': //show srp
                        return $this->other_option_layout_pdf($params, $data);
                        break;
                    case '2': //not show cost and srp
                        return $this->other_option_layout_pdf($params, $data);
                        break;
                    case '3':
                        return $this->default_PO_PDF($params, $data);
                        break;
                }
                break; //end ng pdf
        }
    }

    public function default_header($params, $data)
    {
        $companyid = $params['params']['companyid'];

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
        $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'L', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, '', 'L', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('SUPPLIER :', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('DATE :', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->col('ADDRESS :', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('TERMS :', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->endtable();

        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R I P T I O N', '375', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('(+/-) %', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        return $str;
    }


    public function default_po_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];


        $str = '';
        $count = 35;
        $page = 35;
        $font =  "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();
        $str .= $this->default_header($params, $data);
        $print = $params['params']['dataparams']['print'];




        $totalext = 0;
        $net = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '375', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price', $params['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['disc'], '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');


            $totalext = $totalext + $data[$i]['ext'];
            $net = $net + $data[$i]['netamt'];
            $str .= $this->reporter->endrow();

            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->default_header($params, $data);
            //     $str .= $this->reporter->endrow();
            //     $str .= $this->reporter->printline();
            //     $page = $page + $count;
            // }
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM(S)', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL:', '375', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($net, '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '200', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('Checked By :', '200', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('Noted By :', '200', null, false, $border, '', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '200', null, false, $border, '', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $trno = $params['params']['trno'];


        $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
                                                                                        union all
                                                                                    select createby as user from hpohead where trno = $trno) as s ");
        $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");

        $approve = $params['params']['dataparams']['approved'];
        if ($approve == '') {
            $approved = 'Jonathan Go';
        } else {
            $approved = $approve;
        }

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prep, '200', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']['checked'], '200', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Marietta Y. Jose', '200', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($approved, '200', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function default_PO_header_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $center = $params['params']['center'];
        $username = $params['params']['user'];


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
        $this->reportheader->getheader($params);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(0, 0, "\n");

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

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Supplier : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(480, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(480, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, 'B', $fontsize);

        PDF::MultiCell(125, 25, "CODE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 25, "QTY", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 25, "UNIT", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(250, 25, "DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 25, "UNIT PRICE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 25, "(+/-) %", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 25, "TOTAL", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }

    public function default_PO_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 40;
        $totalext = 0;
        $totalnet = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_PO_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);

        $countarr = 0;
        $printedRowCount = 1;
        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $partno = $data[$i]['partno'];
            $barcode = $data[$i]['barcode'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 2);
            $uom = $data[$i]['uom'];
            $amt = number_format($data[$i]['netamt'], 2);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], 2);

            $arr_partno = $this->reporter->fixcolumn([$partno], '50', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '37', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_partno, $arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);

                PDF::MultiCell(125, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(75, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                $printedRowCount++;
            }


            $totalext += $data[$i]['ext'];
            $totalnet += $data[$i]['netamt'];

            if (PDF::getY() > 900) {
                $this->default_PO_header_PDF($params, $data);
            }
            // if ($printedRowCount >= $page && ($i + 1) < count($data)) {
            //     // $this->other_footer($params, $data);
            //     $this->default_PO_header_PDF($params, $data);
            //     $printedRowCount = 0;
            // }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(475, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(75, 0, number_format($totalnet, 2), '', 'R', false, 0);
        PDF::MultiCell(50, 0, '', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(175, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(175, 0, 'Noted By: ', '', 'L', false, 0);
        PDF::MultiCell(175, 0, 'Checked By: ', '', 'L', false, 0);
        PDF::MultiCell(175, 0, 'Approved By: ', '', 'L');

        $trno = $params['params']['trno'];


        $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
                                                                                        union all
                                                                                    select createby as user from hpohead where trno = $trno) as s ");
        $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");

        $approve= $params['params']['dataparams']['approved'];
        if ($approve == '') {
            $approved = 'Jonathan Go';
        } else {
            $approved = $approve;
        }



        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(175, 0, $prep, '', 'L', false, 0);
        PDF::MultiCell(175, 0, 'Marietta Y. Jose', '', 'L', false, 0);
        PDF::MultiCell(175, 0,  $params['params']['dataparams']['checked'], '', 'L', false, 0);
        PDF::MultiCell(175, 0, $approved, '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function other_option_hlayout_pdf($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['trno'];

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


        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(720, 0, strtoupper($this->modulename), '', 'R', false, 1, '',  '55', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(720, 0, 'P.O # : ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false, 1, '',  '80', true, 0, false, true, 0, 'B', true);
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
        PDF::MultiCell(210, 15, (isset($data[0]['exp']) ? $data[0]['exp'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        $add = isset($data[0]['address']) ? $data[0]['address'] : '';
       
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



        // $deliverto = '7F Main Building, Metropolitan Medical Ctr., 1357 Masangkay St., Sta. Cruz Manila 1008';
        $deliverto = isset($data[0]['whname']) ? $data[0]['whname'] : '';
        $maxCharsq = 45;
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

        $maxCharss = 58;
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
                PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(300, 15, $remainingLiness[$i], '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(5, 15, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
            }


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
        } else {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(100, 15, "Email Address: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 15, (isset($data[0]['email']) ? $data[0]['email'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            // PDF::SetFont($fontbold, '', $fontsize);
            // PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(5, 15, "", 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
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



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(410, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(350, 15, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);



        PDF::SetFillColor(125, 125, 125);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 10, "", '', 'L', true, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(410, 5, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(310, 5, '', 'L', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        // $q1 = "select refx, linex from postock as stock where trno = $trno
        //        union all 
        //        select refx, linex from hpostock as stock where trno =$trno";
        // $res = $this->coreFunctions->opentable($q1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(25, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 15, "Reference Purchase Requisition: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);

        // $hasDocno = false;

        // if (!empty($res)) {
        //     foreach ($res as $data2) {
        //         $refx = $data2->refx;
        //         $linex = $data2->linex;

        //         $q2 = "select h.docno from hprhead as h
        //        left join hprstock as stock on stock.trno=h.trno 
        //        where stock.line = $linex and stock.trno = $refx";

        //         $res2 = $this->coreFunctions->opentable($q2);
        //         if (!empty($res2)) {
        //             foreach ($res2 as $data3) {
        //                 $docno = $data3->docno;
        //                 PDF::MultiCell(185, 15, $docno, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        //                 $hasDocno = true;
        //             }
        //         }
        //     }
        // }

        // if (!$hasDocno) {
        PDF::MultiCell(185, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        // }
        $reporttype = $params['params']['dataparams']['reporttype'];
        // $test = $this->report_default_query($trno);
        $datenow =  $this->othersClass->getCurrentDate();
        $dext = 0;
        $qty = 0;

        for ($i = 0; $i < count($data); $i++) {
            $itemid = $data[$i]['itemid'];
            $raw_srp = $data[$i]['netamt'];
            $qty += $data[$i]['qty'];

            // if ($raw_srp == 0) {
            //     $raw_srp = $this->coreFunctions->datareader("select amount as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
            //     if ($raw_srp == null || $raw_srp == '') {
            //         $raw_srp = 0;
            //     }
            //     $raw_srp = $raw_srp;
            //     // $ext = $raw_srp * $data[$i]['qty'];
            // } else {
            //     // $ext = $data[$i]['ext'];
            // }


            if ($raw_srp == 0) {
                $raw_srp = $this->coreFunctions->datareader("select amount as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                if ($raw_srp == null || $raw_srp == '') {
                    $raw_srp = 0;
                }
                $raw_srp = $raw_srp;
                // $ext = $raw_srp * $data[$i]['qty'];
                $srp = $raw_srp;

                $rawcost = $this->coreFunctions->datareader("select cost as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                if ($rawcost == null || $rawcost == '') {
                    $rawcost = 0;
                }
                $rawcost = $rawcost;
                $cost = $rawcost;
                $ext = $data[$i]['qty'] * $cost;
            } else {
                $srp = $raw_srp;
                $rawcost = $data[$i]['netamt'];
                $cost =  $rawcost;
                $ext = $data[$i]['ext'];
            }
            //   $ext = $data[$i]['ext'];

            // switch ($reporttype) {
            //     case '0':

            // $ext = $data[$i]['ext'];
            //         break;
            //     case '1':
            //         $ext = $data[$i]['qty'] * $srp;
            //         break;
            //     case '2':
            //         $ext = $data[$i]['qty'] * $cost;
            //         break;
            // }

            $dext += $ext;
        }

        // PDF::MultiCell(185, 15, $docno, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 15, "Total Quantity:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(180, 15, number_format($qty, 2), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        $tlwithoutvat = $dext / 1.12;

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(410, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 15, "Total PO Value:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(180, 15, number_format($tlwithoutvat, 2), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(410, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 15, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 15, "Total PO Value (w/ VAT):", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 15,  '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(170, 15,  number_format($dext, 2), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(410, 5, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(310, 5, '', 'L', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, 'B', $fontsize);
        $reporttype = $params['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': //show all
                PDF::MultiCell(22, 25, "Line Item", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(100, 25, "EAN", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(248, 25, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "PO Qty", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(50, 25, "UOM", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(75, 25, "SRP", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(75, 25, "Cost", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(100, 25, "Total Price", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                break;
            case '1': //show srp
                PDF::MultiCell(22, 25, "Line Item", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(112, 25, "EAN", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(263, 25, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(62, 25, "PO Qty", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(62, 25, "UOM", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(87, 25, "SRP", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                // PDF::MultiCell(75, 25, "Cost", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(112, 25, "Total SRP", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

                break;
            case '2': // not show cost and srp
                PDF::MultiCell(22, 25, "Line Item", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(130, 25, "EAN", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(408, 25, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 25, "PO Qty", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(80, 25, "UOM", 'TB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                // PDF::MultiCell(75, 25, "SRP", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                // PDF::MultiCell(75, 25, "Cost", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                // PDF::MultiCell(130, 25, "Total Price", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
                break;
        }
    }


    public function other_option_layout_pdf($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $reporttype = $params['params']['dataparams']['reporttype'];
        $username = $params['params']['user'];

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "9";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
        }
        $this->other_option_hlayout_pdf($params, $data);
        PDF::SetFont($font, '', 5);

        $datenow =  $this->othersClass->getCurrentDate();
        $countarr = 1;
        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $barcode = $data[$i]['barcode'];
            $itemname = $data[$i]['itemname'];
            $qty = $data[$i]['qty'];
            $uom = $data[$i]['uom'];
            $itemid = $data[$i]['itemid'];

            $raw_cost = $data[$i]['netamt']; //rrcost
            $raw_srp = $data[$i]['amt1']; //srp

            if ($raw_cost == 0) {


                // $raw_srp = $this->coreFunctions->datareader("select amount as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                // if ($raw_srp == null || $raw_srp == '') {
                //     $raw_srp = 0;
                // }
                // $raw_srp = $raw_srp;
                // // $ext = $raw_srp * $data[$i]['qty'];
                // $srp = $raw_srp;

                $rawcost = $this->coreFunctions->datareader("select cost as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                if ($rawcost == null || $rawcost == '') {
                    $rawcost = 0;
                }
                $rawcost = $rawcost;
                $cost = $rawcost;

                if ($reporttype == 0 || $reporttype == 2) {
                    $ext = $data[$i]['qty'] * $cost;
                }
            } else {
                // $srp = $data[$i]['amt1'];
                // $rawcost = $data[$i]['netamt'];
                $cost =  $raw_cost;
                $ext = $data[$i]['ext'];
            }

            if ($raw_srp == 0) {
                $raw_srp = $this->coreFunctions->datareader("select amount as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                if ($raw_srp == null || $raw_srp == '') {
                    $raw_srp = 0;
                }
                $raw_srp = $raw_srp;
                // $ext = $raw_srp * $data[$i]['qty'];
                $srp = $raw_srp;


                if ($reporttype == 1) {
                    $ext = $data[$i]['qty'] * $srp;
                }
            } else {
                $srp = $data[$i]['amt1'];
                // $ext = $data[$i]['ext'];

                if ($reporttype == 1) {
                    $ext = $data[$i]['qty'] * $srp;
                }
            }

            // $dext = $data[$i]['ext'];

            // switch ($reporttype) {
            //     case '0':
            //         // $dext = $data[$i]['qty'] * $cost;
            //         $dext = $data[$i]['ext'];
            //         break;
            //     case '1':
            //         $dext = $data[$i]['qty'] * $srp;
            //         break;
            //     case '2':
            //         // $dext = $data[$i]['qty'] * $cost;
            //         $dext = $data[$i]['ext'];
            //         break;
            // }

            $dext = $ext;

            $newsrp = number_format($srp, 2);
            $newcost = number_format($cost, 2);
            $newext = number_format($dext, 2);

            $newqty = number_format($qty, 2);

            $ss = '0';

            switch ($reporttype) {
                case '0':
                    $ss = '45';
                    break;
                case '1':
                    $ss = '45';
                    break;
                case '2':
                    $ss = '50';
                    break;
            }

            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname],  $ss, 0); //40
            $arr_qty = $this->reporter->fixcolumn([$newqty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_srp = $this->reporter->fixcolumn([$newsrp], '13', 0);
            $arr_cost = $this->reporter->fixcolumn([$newcost], '13', 0);
            $arr_ext = $this->reporter->fixcolumn([$newext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_srp, $arr_cost, $arr_ext]);
            for ($r = 0; $r < $maxrow; $r++) {

                PDF::SetFont($font, '', $fontsize);
                switch ($reporttype) {
                    case '0': //show all
                        PDF::MultiCell(22, 15, ($r == 0 ? $countarr  : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(248, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(75, 15, ' ' . (isset($arr_srp[$r]) ? $arr_srp[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(75, 15, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

                        break;
                    case '1': //show srp
                        PDF::MultiCell(22, 15, ($r == 0 ? $countarr  : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(112, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(263, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(62, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(62, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(87, 15, ' ' . (isset($arr_srp[$r]) ? $arr_srp[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(112, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

                        break;
                    case '2': // not show cost and srp
                        PDF::MultiCell(22, 15, ($r == 0 ? $countarr  : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(130, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(408, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(80, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                        break;
                }
                if ($r == 0) {
                    $countarr++;
                }
            }

            if ($i < count($data) - 1 && PDF::getY() >= 672) {
                //655->17rows
                //690->22rows
                //672->20rows
                $this->other_footer($params, $data);
                $this->other_option_hlayout_pdf($params, $data);
            }
        }

        PDF::SetFont($font, '', 1);
        PDF::MultiCell(720, 0, '', 'B');


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(720, 0, "******NOTHING FOLLOWS******", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($font, '', 1);
        PDF::MultiCell(720, 0, '', '', 'L', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(53, 0, 'REMARKS: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(557, 0, $data[0]['rem'], '', 'L', false, 1);



        PDF::SetFillColor(125, 125, 125);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 12, "", '', 'L', true, 1, '40', '725', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, " DELIVERY INSTRUCTION", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(720, 0, " 1. Please deliver to the specified delivery site.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 2. Delivery schedule if from 10am to 4pm from Mondays thru Thursday. (Notes: No delivery on Holidays) ", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        // PDF::SetFont($fontitalic, 'I', $fontsize);
        // PDF::MultiCell(340, 0, "(Notes: No delivery on Holidays)", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(720, 0, " 3. Suppliers shall only bring three copies of Delivery Receipt that bears the PO reference number and a clear breakdown of Item Description (including color,", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(15, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(705, 0, "dimensions, serial number and product specification), Quantity and Unit of measurement.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 4. For imported goods,supplier shall ensure that the Official Packing List and Bill of Landing,/Airway Bill have been sent via email or courier service to Merchandising ", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(15, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(705, 0, "Department prior to delivering the goods to HOMEWORKS authorized freight forwarder.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 5. Failure to submit accredited requirements would result to non processing of payment.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 6. Late delivery is subject to cancellation. This PO is VAT inclusive.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(720, 0, '', false, 1);
        PDF::SetFillColor(125, 125, 125);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 12, "", '', 'L', true, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(480, 10, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(240, 10, "", 'L', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        // $prepared    = $params['params']['dataparams']['prepared'];

        // var_dump($prepare);
        $approve = $params['params']['dataparams']['approved'];
        $checked = $params['params']['dataparams']['checked'];


        // $prepare    = $params['params']['dataparams']['prepared'];

        // // var_dump($prepare);
        // $approve = $params['params']['dataparams']['approved'];
        // $checked = $params['params']['dataparams']['checked'];
        // // $prepared = '';
        // $approved = '';

        $trno = $params['params']['trno'];
        $user = $params['params']['user'];

        $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
                                                                                        union all
                                                                                     select createby as user from hpohead where trno = $trno) as s ");
        $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");
        // // $prepared = (!empty($prepared) && isset($prepared)) ? $prepared : '';

        // $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");
        // $prep = (!empty($prep) && isset($prep)) ? $prep : '';




        // $pr = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
        //                                                                                 union all
        //                                                                              select createby as user from hpohead where trno = $trno) as s"); //dito
        // $pre = $this->coreFunctions->datareader("select name as value from useraccess where username = '$pr'");
        // $appro = $this->coreFunctions->datareader("select postedby as value from transnum where trno = $trno");

        // if ($prepare == '') {
        //     $prepared = $pre;
        // } else {
        //     $prepared = $prepare;
        // }

        if ($approve == '') {
            $approved = 'Jonathan Go';
        } else {
            $approved = $approve;
        }

        $stat = $data[0]['statuss'];

        // var_dump($stat);

        // if ($approved == '') {
        //     $approved = $appro;
        // } else {
        //     $approved = $approve;
        // }


        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(120, 0, "Prepared By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, "Noted By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, "Checked By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, "Approved By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(240, 0, "", '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        if ($stat == 'POSTED') {
            PDF::SetFont($font, '', $fontsize);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

            $x = PDF::GetX();
            $y = PDF::GetY();
            $signature =  PDF::Image(public_path() . '/images/homeworks/checked.png', $x + 100, $y - 30, 150, 100);  //x,y,widht,heigh
            PDF::SetFont($font, '', 9);

            PDF::MultiCell(120, 0, $prep, '', 'C', false, 0, $x, $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, "Marietta Y. Jose", '', 'C', false, 0, $x + 120,  $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $checked, '', 'C', false, 0, $x + 240, '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $approved, '', 'C', false, 0, $x + 360, '', true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(5, 100, '', 'L', 'CT', false, 0, $x + 470, '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 8);
            PDF::MultiCell(240, 0, "ANY STANDARD TERMS AND CONDITION OF THE VENDOR OR ANY OTHER TERMS AND CONDITIONS SPECIFIED BY THE VENDOR SHALL NOT APPLY UNLESS EXPRESSLY ACCEPTED BY HOMEWORKS IN WRITING. THIS IS A SYSTEM-GENERATED DOCUMENT.", 'L', 'C', false, 1, $x + 480, $y - 15, true, 0, false, true, 0, 'B', true);
        } else {
            $x = PDF::GetX();
            $y = PDF::GetY();
            PDF::SetFont($font, '', 9);

            PDF::MultiCell(120, 0, $prep, '', 'C', false, 0, $x, $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, "", '', 'C', false, 0, $x + 120,  $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $checked, '', 'C', false, 0, $x + 240, '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $approved, '', 'C', false, 0, $x + 360, '', true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(5, 100, '', 'L', 'CT', false, 0, $x + 470, '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 8);
            PDF::MultiCell(240, 0, "ANY STANDARD TERMS AND CONDITION OF THE VENDOR OR ANY OTHER TERMS AND CONDITIONS SPECIFIED BY THE VENDOR SHALL NOT APPLY UNLESS EXPRESSLY ACCEPTED BY HOMEWORKS IN WRITING. THIS IS A SYSTEM-GENERATED DOCUMENT.", 'L', 'C', false, 1, $x + 480, $y - 15, true, 0, false, true, 0, 'B', true);
        }

        // PDF::SetFont($font, '', $fontsize);
        // // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

        // $x = PDF::GetX();
        // $y = PDF::GetY();
        // $signature =  PDF::Image(public_path() . '/images/homeworks/checked.png', $x + 100, $y - 30, 150, 100);  //x,y,widht,height

        // // PDF::MultiCell(120, 0, '', '', 'C', false, 0, $x, $y + 20, true, 0, false, true, 0, 'B', true);
        // // PDF::MultiCell(120, 0, $signature, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        // // PDF::MultiCell(120, 0, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        // // PDF::MultiCell(120, 0, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        // // PDF::MultiCell(240, 40, '', 'L', 'C', false, 1, $x + 480, $y - 20, true, 0, false, true, 0, 'B', true);

        // PDF::SetFont($font, '', 9);

        // PDF::MultiCell(120, 0, $prepared, '', 'C', false, 0, $x, $y + 15, true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(120, 0, "Marietta Y. Jose", '', 'C', false, 0, $x + 120,  $y + 15, true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(120, 0, $checked, '', 'C', false, 0, $x + 240, '', true, 0, false, true, 0, 'B', true);
        // PDF::MultiCell(120, 0, $approved, '', 'C', false, 0, $x + 360, '', true, 0, false, true, 0, 'B', true);
        // // PDF::MultiCell(5, 100, '', 'L', 'CT', false, 0, $x + 470, '', true, 0, false, true, 0, 'B', true);
        // PDF::SetFont($font, '', 8);
        // PDF::MultiCell(240, 0, "ANY STANDARD TERMS AND CONDITION OF THE VENDOR OR ANY OTHER TERMS AND CONDITIONS SPECIFIED BY THE VENDOR SHALL NOT APPLY UNLESS EXPRESSLY ACCEPTED BY HOMEWORKS IN WRITING. THIS IS A SYSTEM-GENERATED DOCUMENT.", 'L', 'C', false, 1, $x + 480, $y - 15, true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', 3);

        PDF::MultiCell(120, 3, '', '', 'C', false, 0);
        PDF::MultiCell(120, 3, "", '', 'C', false, 0);
        PDF::MultiCell(120, 3, '', '', 'C', false, 0);
        PDF::MultiCell(120, 3, '', '', 'C', false, 0);
        PDF::MultiCell(240, 3, "", 'L', 'C', false, 1);


        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $username = $params['params']['user'];

        // $username = $params['params']['user'];



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
        $reporttype = $params['params']['dataparams']['reporttype'];
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

        PDF::SetFont($font, '', 1);
        PDF::MultiCell(720, 0, '', 'B');

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(720, 0, "", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        // PDF::MultiCell(0, 0, "\n");

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(55, 0, '', '', 'L', false, 0);
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(555, 0, '', '', 'L');

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetFillColor(125, 125, 125);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 12, "", '', 'L', true, 1, '40', '725', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, " DELIVERY INSTRUCTION", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(720, 0, " 1. Please deliver to the specified delivery site.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 2. Delivery schedule if from 10am to 4pm from Mondays thru Thursday. (Notes: No delivery on Holidays) ", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        // PDF::SetFont($fontitalic, 'I', $fontsize);
        // PDF::MultiCell(340, 0, "(Notes: No delivery on Holidays)", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(720, 0, " 3. Suppliers shall only bring three copies of Delivery Receipt that bears the PO reference number and a clear breakdown of Item Description (including color,", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(15, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(705, 0, "dimensions, serial number and product specification), Quantity and Unit of measurement.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 4. For imported goods,supplier shall ensure that the Official Packing List and Bill of Landing,/Airway Bill have been sent via email or courier service to Merchandising ", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(15, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(705, 0, "Department prior to delivering the goods to HOMEWORKS authorized freight forwarder.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 5. Failure to submit accredited requirements would result to non processing of payment.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(720, 0, " 6. Late delivery is subject to cancellation. This PO is VAT inclusive.", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(720, 0, '', false, 1);
        PDF::SetFillColor(125, 125, 125);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 12, "", '', 'L', true, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(480, 10, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(240, 10, "", 'L', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        // $prepared    = $params['params']['dataparams']['prepared'];

        // var_dump($prepare);
        $approve = $params['params']['dataparams']['approved'];
        $checked = $params['params']['dataparams']['checked'];
        $trno = $params['params']['trno'];


        $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
                                                                                        union all
                                                                                    select createby as user from hpohead where trno = $trno) as s ");
        $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");


        // $prep     = $params['params']['dataparams']['prepared'];
        // // $noted    = $params['params']['dataparams']['noted']; //"Ms. Krystelle Dominique Ramirez y Salvador de San Miguel";
        // // $checked = PDF::Image(public_path() . '/images/homeworks/checked.png', '12', '80', 103, 43);
        // $approved = $params['params']['dataparams']['approved'];
        // $checked = $params['params']['dataparams']['checked'];

        // $prepare    = $params['params']['dataparams']['prepared'];

        // // var_dump($prepare);
        // $approve = $params['params']['dataparams']['approved'];
        // $checked = $params['params']['dataparams']['checked'];
        // $prepared = '';
        // $approved = '';

        // $trno = $params['params']['trno'];
        // $user = $params['params']['user'];
        // $pr = $this->coreFunctions->datareader("select createby as value from hpohead where trno = $trno");
        // $pre = $this->coreFunctions->datareader("select name as value from useraccess where username = '$pr'");
        // $appro = $this->coreFunctions->datareader("select postedby as value from transnum where trno = $trno");

        // if ($prepare == '') {
        //     $prepared = $pre;
        // } else {
        //     $prepared = $prepare;
        // }

        // if ($approve == '') {
        //     $approved = $appro;
        // } else {
        //     $approved = $approve;
        // }
        $stat = $data[0]['statuss'];

        if ($approve == '') {
            $approved = 'Jonathan Go';
        } else {
            $approved = $approve;
        }


        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(120, 0, "Prepared By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, "Noted By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, "Checked By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, "Approved By:", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(240, 0, "", '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        if ($stat == 'POSTED') {

            PDF::SetFont($font, '', $fontsize);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

            $x = PDF::GetX();
            $y = PDF::GetY();
            $signature =  PDF::Image(public_path() . '/images/homeworks/checked.png', $x + 100, $y - 30, 150, 100);  //x,y,widht,height

            // PDF::MultiCell(120, 0, '', '', 'C', false, 0, $x, $y + 20, true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(120, 0, $signature, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(120, 0, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(120, 0, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(240, 40, '', 'L', 'C', false, 1, $x + 480, $y - 20, true, 0, false, true, 0, 'B', true);

            PDF::SetFont($font, '', 11);

            PDF::MultiCell(120, 0, $prep, '', 'C', false, 0, $x, $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, "Marietta Y. Jose", '', 'C', false, 0, $x + 120,  $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $checked, '', 'C', false, 0, $x + 240, '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $approved, '', 'C', false, 0, $x + 360, '', true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(5, 100, '', 'L', 'CT', false, 0, $x + 470, '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(240, 0, "ANY STANDARD TERMS AND CONDITION OF THE VENDOR OR ANY OTHER TERMS AND CONDITIONS SPECIFIED BY THE VENDOR SHALL NOT APPLY UNLESS EXPRESSLY ACCEPTED BY HOMEWORKS IN WRITING. THIS IS A SYSTEM-GENERATED DOCUMENT.", 'L', 'C', false, 1, $x + 480, $y - 15, true, 0, false, true, 0, 'B', true);
        } else {
            PDF::SetFont($font, '', $fontsize);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

            $x = PDF::GetX();
            $y = PDF::GetY();
            PDF::SetFont($font, '', 11);

            PDF::MultiCell(120, 0, $prep, '', 'C', false, 0, $x, $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, "", '', 'C', false, 0, $x + 120,  $y + 15, true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $checked, '', 'C', false, 0, $x + 240, '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(120, 0, $approved, '', 'C', false, 0, $x + 360, '', true, 0, false, true, 0, 'B', true);
            // PDF::MultiCell(5, 100, '', 'L', 'CT', false, 0, $x + 470, '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(240, 0, "ANY STANDARD TERMS AND CONDITION OF THE VENDOR OR ANY OTHER TERMS AND CONDITIONS SPECIFIED BY THE VENDOR SHALL NOT APPLY UNLESS EXPRESSLY ACCEPTED BY HOMEWORKS IN WRITING. THIS IS A SYSTEM-GENERATED DOCUMENT.", 'L', 'C', false, 1, $x + 480, $y - 15, true, 0, false, true, 0, 'B', true);
        }

        PDF::SetFont($font, '', 3);

        PDF::MultiCell(120, 3, '', '', 'C', false, 0);
        PDF::MultiCell(120, 3, "", '', 'C', false, 0);
        PDF::MultiCell(120, 3, '', '', 'C', false, 0);
        PDF::MultiCell(120, 3, '', '', 'C', false, 0);
        PDF::MultiCell(240, 3, "", 'L', 'C', false, 1);


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


    public function other_option_hlayoutrep($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $trno = $params['params']['trno'];


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
        $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'L', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, '', 'L', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Supplying Company', '800', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Supplier Name :', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '50', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('Issued Date :', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, '', 'L', $font, '13', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Supplier Code :', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['client']) ? $data[0]['client'] : ''), '50', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address :', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '50', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


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



        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Contact Details :', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col($contact, '50', null, false, $border, '', 'L', $font, '13', '', '', '');
        // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('Released By :', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Email Address: ', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col((isset($data[0]['email']) ? $data[0]['email'] : ''), '50', null, false, $border, '', 'L', $font, '13', '', '', '');
        // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('Email Address:', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Payment Terms: ', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '50', null, false, $border, '', 'L', $font, '13', '', '', '');
        // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('Please Deliver to: ', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col((isset($data[0]['whname']) ? $data[0]['whname'] : ''), '100', null, false, $border, '', 'L', $font, '13', '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();






        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Currency: ', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', '', '', '');
        // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();





        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Your Sales Representative: ', '150', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();



        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col(' Sales Rep Contact Number:', '150', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        // $q1 = "select refx, linex from postock as stock where trno = $trno
        //        union all 
        //        select refx, linex from hpostock as stock where trno =$trno";
        // $res = $this->coreFunctions->opentable($q1);

        // $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Reference Purchase Requisition:', '200', null, false, $border, '', 'L', $font, '13', 'B', '', '');

        // $hasDocno = false;

        // if (!empty($res)) {
        //     foreach ($res as $data2) {
        //         $refx = $data2->refx;
        //         $linex = $data2->linex;

        //         $q2 = "select h.docno from hprhead as h
        //        left join hprstock as stock on stock.trno=h.trno 
        //        where stock.line = $linex and stock.trno = $refx";

        //         $res2 = $this->coreFunctions->opentable($q2);
        //         if (!empty($res2)) {
        //             foreach ($res2 as $data3) {
        //                 $docno = $data3->docno;
        //                 $str .= $this->reporter->col($docno, '325', null, false, $border, '', 'L', $font, '13', '', '', '');
        //                 // PDF::MultiCell(185, 15, $docno, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        //                 $hasDocno = true;
        //             }
        //         }
        //     }
        // }

        // if (!$hasDocno) {
        // $str .= $this->reporter->col('', '325', null, false, $border, '', 'L', $font, '13', '', '', '');
        // // }

        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $reporttype = $params['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': //show all

                // $str .= $this->reporter->col('Line Item', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('EAN', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Item Description', '250', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Qty', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('UOM', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('SRP', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Unit Cost', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Total Price', '95', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');

                break;
            case '1': //show srp
                // $str .= $this->reporter->col('Line Item', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('EAN', '109', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Item Description', '250', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Qty', '109', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('UOM', '109', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('SRP', '109', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                // $str .= $this->reporter->col('Cost', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Total Price', '114', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                break;
            case '2': //not show cost and srp
                // $str .= $this->reporter->col('Line Item', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('EAN', '124', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Item Description', '250', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Qty', '124', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('UOM', '124', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                // $str .= $this->reporter->col('SRP', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                // $str .= $this->reporter->col('Cost', '91', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                $str .= $this->reporter->col('Total Price', '128', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
                break;
        }

        $str .= $this->reporter->endrow();
        return $str;
    }


    public function other_option_layoutrep($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);
        $reporttype = $params['params']['dataparams']['reporttype'];

        $str = '';
        $count = 35;
        $page = 35;
        $font =  "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();
        $str .= $this->other_option_hlayoutrep($params, $data);

        $totalext = 0;
        $totalqty = 0;
        $totalsrp = 0;
        $totalcost = 0;
        $countarr = 1;
        $datenow =  $this->othersClass->getCurrentDate();
        for ($i = 0; $i < count($data); $i++) {

            $itemid = $data[$i]['itemid'];
            $raw_cost = $data[$i]['netamt']; //rrcost
            $raw_srp = $data[$i]['amt1']; //srp
            $qty = $data[$i]['qty'];

            // if ($raw_srp == 0) {
            //     $raw_srp = $this->coreFunctions->datareader("select amount as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
            //     if ($raw_srp == null || $raw_srp == '') {
            //         $raw_srp = 0;
            //     }
            //     $raw_srp = $raw_srp;
            //     $ext = $raw_srp * $data[$i]['qty'];
            //     $srp = number_format($raw_srp, 2);

            //     $rawcost = $this->coreFunctions->datareader("select cost as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
            //     if ($rawcost == null || $rawcost == '') {
            //         $rawcost = 0;
            //     }
            //     $rawcost = $rawcost;
            //     $cost = number_format($rawcost, 2);
            // } else {
            //     $srp = number_format($raw_srp, 2);
            //     $rawcost = $data[$i]['cost'];
            //     $cost =  number_format($rawcost, 2);
            //     $ext = $data[$i]['ext'];
            // }

            if ($raw_cost == 0) {


                // $raw_srp = $this->coreFunctions->datareader("select amount as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                // if ($raw_srp == null || $raw_srp == '') {
                //     $raw_srp = 0;
                // }
                // $raw_srp = $raw_srp;
                // // $ext = $raw_srp * $data[$i]['qty'];
                // $srp = $raw_srp;

                $rawcost = $this->coreFunctions->datareader("select cost as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                if ($rawcost == null || $rawcost == '') {
                    $rawcost = 0;
                }
                $rawcost = $rawcost;
                $cost = $rawcost;
            } else {
                // $srp = $data[$i]['amt1'];
                // $rawcost = $data[$i]['netamt'];
                $cost =  $raw_cost;
                $ext = $data[$i]['ext'];
            }

            if ($raw_srp == 0) {
                $raw_srp = $this->coreFunctions->datareader("select amount as value from pricelist where '$datenow' BETWEEN DATE(startdate) AND DATE(enddate) AND itemid=$itemid order by startdate desc limit 1");
                if ($raw_srp == null || $raw_srp == '') {
                    $raw_srp = 0;
                }
                $raw_srp = $raw_srp;
                // $ext = $raw_srp * $data[$i]['qty'];
                $srp = $raw_srp;
            } else {
                $srp = $data[$i]['amt1'];
                $ext = $data[$i]['ext'];
            }


            // $dext = $ext;
            switch ($reporttype) {
                case '0':
                    // $dext = $data[$i]['qty'] * $cost;
                    $dext = $data[$i]['ext'];
                    break;
                case '1':
                    $dext = $data[$i]['qty'] * $srp;
                    break;
                case '2':
                    // $dext = $data[$i]['qty'] * $cost;
                    $dext = $data[$i]['ext'];
                    break;
            }

            $newsrp = number_format($srp, 2);
            $newcost = number_format($cost, 2);
            $newext = number_format($dext, 2);

            $newqty = number_format($qty, 2);



            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            switch ($reporttype) {
                case '0': //show all
                    // $str .= $this->reporter->col($countarr, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col("'" . $data[$i]['barcode'], '91', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newqty, '91', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data[$i]['uom'], '91', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newsrp, '91', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newcost, '91', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newext, '95', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    break;
                case '1': //show srp
                    // $str .= $this->reporter->col($countarr, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col("'" . $data[$i]['barcode'], '109', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newqty, '109', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data[$i]['uom'], '109', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newsrp, '109', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    // $str .= $this->reporter->col(number_format($data[$i]['cost'], $this->companysetup->getdecimal('price', $params['params'])), '91', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newext, '114', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    break;
                case '2': //not show cost and srp
                    // $str .= $this->reporter->col($countarr, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col("'" . $data[$i]['barcode'], '124', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data[$i]['qty'], '124', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data[$i]['uom'], '124', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
                    // $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price', $params['params'])), '109', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    // $str .= $this->reporter->col(number_format($data[$i]['cost'], $this->companysetup->getdecimal('price', $params['params'])), '91', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($newext, '128', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
                    break;
            }

            $countarr++;
            $totalext += $dext;
            $totalqty +=  $qty;

            $totalsrp +=  $srp;
            $totalcost +=  $cost;

            $str .= $this->reporter->endrow();

            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->other_option_hlayoutrep($params, $data);
            //     $str .= $this->reporter->endrow();
            //     $str .= $this->reporter->printline();
            //     $page = $page + $count;
            // }
        }

        switch ($reporttype) {
            case '0': //show all
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '91', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Grand Total: ', '20', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalqty, 2), '91', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '91', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalsrp, 2), '91', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalcost, 2), '91', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalext, $decimal), '95', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                break;
            case '1': //show srp only
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '109', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Grand Total: ', '250', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalqty, 2), '109', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '109', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalsrp, 2), '109', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
                // $str .= $this->reporter->col(number_format($totalcost, 2), '91', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalext, $decimal), '114', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                break;
            case '2': //not show srp and cost

                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '124', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Grand Total: ', '250', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalqty, 2), '124', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '124', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                // $str .= $this->reporter->col(number_format($totalsrp,2), '109', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                // $str .= $this->reporter->col(number_format($totalcost, 2), '91', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalext, $decimal), '128', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                break;
        }


        // $withoutvat = $totalext / 1.12;

        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Total PO value:', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col(number_format($withoutvat, 2), '275', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('Total PO Value (w/ VAT)', '150', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col(number_format($totalext, 2), '275', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        // // $str .= $this->reporter->col('', '450', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REMARKS : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '200', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('Noted By :', '200', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('Checked By :', '200', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '200', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $trno = $params['params']['trno'];


        $prepared = $this->coreFunctions->datareader("select user as value from(select createby as user from pohead where trno = $trno
                                                                                        union all
                                                                                    select createby as user from hpohead where trno = $trno) as s ");
        $prep = $this->coreFunctions->datareader("select name as value from useraccess where username = '$prepared'");

         $approve= $params['params']['dataparams']['approved'];

         if ($approve == '') {
            $approved = 'Jonathan Go';
        } else {
            $approved = $approve;
        }


        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prep, '200', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Marietta Y. Jose', '200', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']['checked'], '200', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($approved, '200', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    private function addrowrem()
    {
        PDF::MultiCell(720, 0, '', '', 'L', false);
    }



    public function notallowtoprint($config, $msg)
    {
        $font = "";
        $fontbold = "";
        $fontsize = 20;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::SetMargins(20, 20);
        PDF::AddPage('p', [800, 1000]);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, $msg, '', 'L', false, 1);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
