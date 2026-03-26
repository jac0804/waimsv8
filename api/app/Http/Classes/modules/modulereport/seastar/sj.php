<?php

namespace App\Http\Classes\modules\modulereport\seastar;

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
    private $modulename = "Waybill";
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
        $fields = ['radioprint', 'radioreporttype', 'print'];
        $col1 = $this->fieldClass->create($fields);

        data_set(
            $col1,
            'radioreporttype.options',
            [
                // ['label' => 'DEFAULT', 'value' => '0', 'color' => 'blue'],
                ['label' => 'PREPRINTED', 'value' => '1', 'color' => 'blue']
            ]
        );

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
            '1' as reporttype
            "
        );
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];

        $query = "select head.trno,head.docno,client.client,head.terms,
                    head.yourref,head.ourref,
                    left(head.dateid,10) as dateid,head.clientname,head.address,
                    head.rem,warehouse.client as wh,warehouse.clientname as whname,
                    ifnull(project.name,'') as projectname,
                    ifnull(project.code,'') as projectcode,
                    head.conaddr,cs.clientname as consignee,hinfo.trnxtype,
                    sh.clientname as shipper,head.whto,whto.clientname as whtoname,
                    stock.trno,stock.line,info.itemdesc,info.unit as uom,info.weight as sweight,
                        stock.isamt,ROUND(stock.isqty)  as isqty,
                        hinfo.weight,hinfo.valamt,hinfo.cumsmt,hinfo.delivery,sh.addr as shipaddr
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnum as num on num.trno = head.trno
                left join client on head.client = client.client
                        left join client as warehouse on warehouse.client = head.wh
                        left join coa on coa.acno=head.contra
                        left join projectmasterfile as project on project.line=head.projectid
                        left join cntnuminfo as hinfo on hinfo.trno = head.trno
                        left join client as cs on cs.clientid=head.consigneeid
                        left join client as sh on sh.clientid=head.shipperid
                        left join client as whto on whto.client=head.whto
                        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
                where head.doc='SJ' and head.trno=$trno
                union all
                select head.trno,head.docno,client.client,head.terms,
                    head.yourref,head.ourref,
                    left(head.dateid,10) as dateid,head.clientname,head.address,
                    head.rem,warehouse.client as wh,warehouse.clientname as whname,
                    ifnull(project.name,'') as projectname,
                    ifnull(project.code,'') as projectcode,
                    head.conaddr,cs.clientname as consignee,hinfo.trnxtype,
                    sh.clientname as shipper,head.whto,whto.clientname as whtoname,
                    stock.trno,stock.line,info.itemdesc,info.unit as uom,info.weight as sweight,
                        stock.isamt,ROUND(stock.isqty)  as isqty,
                        hinfo.weight,hinfo.valamt,hinfo.cumsmt,hinfo.delivery,sh.addr as shipaddr
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join cntnum as num on num.trno = head.trno
                left join client on head.clientid = client.clientid
                left join client as warehouse on warehouse.clientid = head.whid
                left join coa on coa.acno=head.contra
                left join projectmasterfile as project on project.line=head.projectid
                left join hcntnuminfo as hinfo on hinfo.trno = head.trno
                left join client as cs on cs.clientid=head.consigneeid
                left join client as sh on sh.clientid=head.shipperid
                left join client as whto on whto.client=head.whto
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                where head.doc='SJ' and head.trno=$trno";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        // switch ($params['params']['dataparams']['reporttype']) {
        //     case '0':
        //         $str = $this->default_sj_PDF($params, $data);
        //         break;
        //     default:
        //         $str = $this->waybill_sj_PDF($params, $data);
        //         break;
        // }

        $str = $this->waybill_sj_PDF($params, $data);
        return $str;
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

        PDF::SetFont($font, '', 9);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
        $this->reportheader->getheader($params);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Customer : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Project : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(480, 20, (isset($data[0]['projectname']) ? $data[0]['projectname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Yourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Trnx Type : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(480, 20, (isset($data[0]['trnxtype']) ? $data[0]['trnxtype'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Ourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Consignee : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(480, 20, (isset($data[0]['consignee']) ? $data[0]['consignee'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "From : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 20, "Shipper : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(480, 20, (isset($data[0]['shipper']) ? $data[0]['shipper'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "To : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['whtoname']) ? $data[0]['whtoname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(250, 0, "ITEMNAME", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "UOM", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "QUANTITY", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "WEIGHT", '', 'R', false, 0);
        PDF::MultiCell(150, 0, "DECLARED VALUE", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
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

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_sj_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $itemname = $data[$i]['itemdesc'];
                $uom = $data[$i]['uom'];
                $qty = $data[$i]['isqty'];
                $weight = number_format($data[$i]['sweight'], 2);
                $amt = number_format($data[$i]['isamt'], 2);

                $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_weight = $this->reporter->fixcolumn([$weight], '13', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname,  $arr_uom, $arr_qty, $arr_weight, $arr_amt]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_weight[$r]) ? $arr_weight[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(150, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }

                // $totalext += $data[$i]['ext'];

                if (PDF::getY() > 900) {
                    $this->default_sj_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        // PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(250, 18, 'CHARGES', 'TBLR', 'C', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 18, ' Weight', 'TBL', 'L', false, 0);
        PDF::MultiCell(150, 18, $data[0]['weight'] . ' ', 'TBLR', 'R', false);

        PDF::MultiCell(100, 18, ' Value', 'TBL', 'L', false, 0);
        PDF::MultiCell(150, 18, $data[0]['valamt'] . ' ', 'TBLR', 'R', false);

        PDF::MultiCell(100, 18, ' Cu. MSMT', 'TBL', 'L', false, 0);
        PDF::MultiCell(150, 18, $data[0]['cumsmt'] . ' ', 'TBLR', 'R', false);

        PDF::MultiCell(100, 18, ' Delivery', 'TBL', 'L', false, 0);
        PDF::MultiCell(150, 18, $data[0]['delivery'] . ' ', 'TBLR', 'R', false);

        $total = $data[0]['weight'] + $data[0]['valamt'] + $data[0]['cumsmt'] + $data[0]['delivery'];
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 18, ' Total: (display)', 'TBL', 'L', false, 0);
        PDF::MultiCell(150, 18, number_format($total, 2) . ' ', 'TBLR', 'R', false);

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function waybill_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $ext = 0;
        $font = "";
        $fontbold = "";
        $fontsize = 16;
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

        $date = $data[0]['dateid'];
        $date = date_create($date);
        $date = date_format($date, "M. j, Y");

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, 400, 130);

        PDF::SetFont($fontbold, '', $fontsize);

        $consignee = $data[0]['consignee'];
        PDF::MultiCell(500, 0,  $consignee, '', 'L', false, 0, 130, 81);
        PDF::MultiCell(400, 0,  $data[0]['conaddr'], '', 'L', false, 0, 130, 105);

        // $docno = $data[0]['docno'] * 1;
        PDF::MultiCell(500, 0,  $data[0]['docno'], '', 'L', false, 0, 590, 86);


        PDF::MultiCell(100, 0,  $data[0]['weight'] == 0 ? '' : $data[0]['weight'], '', 'R', false, 0, 590, 135); //135
        PDF::MultiCell(100, 0,  $data[0]['valamt'] == 0 ? '' : $data[0]['valamt'], '', 'R', false, 0, 590, 155); //155
        PDF::MultiCell(100, 0,  $data[0]['cumsmt'] == 0 ? '' : $data[0]['cumsmt'], '', 'R', false, 0, 590, 175); //175
        PDF::MultiCell(100, 0,  $data[0]['delivery'] == 0 ? '' : $data[0]['delivery'], '', 'R', false, 0, 590, 195); //195

        $ext = $data[0]['weight'] + $data[0]['valamt'] + $data[0]['cumsmt'] + $data[0]['delivery'];
        PDF::MultiCell(100, 0, $ext == 0 ? '' : number_format($ext, 2), '', 'R', false, 0, 590, 215); //220


        PDF::MultiCell(200, 0,  $date, '', 'L', false, 0, 570, 240); //240
        PDF::MultiCell(100, 0,  $data[0]['whname'], '', 'L', false, 0, 570, 260); //260
        PDF::MultiCell(100, 0,  $data[0]['ourref'], '', 'L', false, 0, 570, 280); //280
    }

    public function waybill_sj_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $count = $page = 13;
        $totalqty = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = 16;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->waybill_header_PDF($config, $data);
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(700, 0, '', '', '', false, 1, '', 158);

        $countarr = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $itemname = $data[$i]['itemdesc'];
                $uom = $data[$i]['uom'];
                $qty = $data[$i]['isqty'];
                $weight = number_format($data[$i]['sweight'], 2);
                $amt = number_format($data[$i]['isamt'], 2);

                $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_weight = $this->reporter->fixcolumn([$weight], '13', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname,  $arr_uom, $arr_qty, $arr_weight, $arr_amt]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(100, 20, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '-55', '', false, 1);
                    PDF::MultiCell(100, 20, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '26', '', false, 1);
                    PDF::MultiCell(250, 20, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '110', '', false, 1);
                    PDF::MultiCell(120, 20, (isset($arr_weight[$r]) ? $arr_weight[$r] : ''), '', 'R', false, 0, '313', '', false, 1);
                    PDF::MultiCell(150, 20, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 1, '367', '', false);
                }

                $totalqty += $data[$i]['isqty'];
            }

            PDF::SetFont($fontbold, '', 10);
            PDF::MultiCell(700, 0, '', '', '', false);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(75, 0, number_format($totalqty, 0), '', 'R', false, 0, -35);
            PDF::MultiCell(75, 0, 'TOTAL', '', 'L', false, 0, 55);
            PDF::MultiCell(120, 0, $data[0]['trnxtype'], '', 'L', false, 1, 190);

            PDF::SetFont($fontbold, '', 10);
            PDF::MultiCell(700, 0, '', '', '', false);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(600, 0, $data[0]['rem'], '', 'L', false, 1, 160);
        }

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(600, 0, $data[0]['rem'], '', 'L', false, 1, 160, 295);

        // PDF::MultiCell(75, 0, number_format($totalqty, 0), '', 'R', false, 0, -35, 325);
        // PDF::MultiCell(75, 0, 'TOTAL', '', 'L', false, 1, 55, 325);
        // PDF::MultiCell(120, 0, $data[0]['trnxtype'], '', 'L', false, 1, 190, 325);


        PDF::MultiCell(400, 0, $data[0]['shipper'], '', 'L', false, 1, 85, 390);
        PDF::MultiCell(400, 0, $data[0]['shipaddr'], '', 'L', false, 1, 85, 410);


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
