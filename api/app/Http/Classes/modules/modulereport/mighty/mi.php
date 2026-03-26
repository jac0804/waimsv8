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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class mi
{

    private $modulename = "Material Issuance";
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
        $fields = ['radioprint',  'radioreporttype', 'prepared', 'approved', 'received', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']

        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Material Issuance', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Withdrawal Slip', 'value' => '1', 'color' => 'orange']

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

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname,emp.clientname as driver,info.odometer,ifnull(project.name,'') as projectname,coa.acnoname as expenses,
    ifnull(uom.factor,1) as factor
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
     left join cntnuminfo as info on info.trno=head.trno
    left join client as emp on head.empid = emp.clientid
    left join projectmasterfile as project on project.line=head.projectid
    left join coa on coa.acno=head.contra
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    where head.doc='MI' and head.trno='$trno'
    UNION ALL
    select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname,emp.clientname as driver,info.odometer,ifnull(project.name,'') as projectname,coa.acnoname as expenses,
    ifnull(uom.factor,1) as factor
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    left join hcntnuminfo as info on info.trno=head.trno
    left join client as emp on head.empid = emp.clientid
    left join projectmasterfile as project on project.line=head.projectid
    left join coa on coa.acno=head.contra
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    where head.doc='MI' and head.trno='$trno' order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        return $this->default_MI_PDF($params, $data);
    }

    public function default_MI_layout($params, $data)
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
        $str .= $this->report_default_header($params, $data);

        $totalext = 0;
        $netdiscs = 0;
        for ($i = 0; $i < count($data); $i++) {
            $netofdisc =  number_format($data[$i]['factor'] * $data[$i]['amt'], $decimal);
            $net = $netofdisc - number_format($data[$i]['disc']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '550px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            // $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $totalext = $totalext + $data[$i]['ext'];
            $netdiscs += $net;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // <--- Header
                $str .= $this->report_default_header($params, $data);

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            } //end if
        } //end for 

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '175px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        // $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col(number_format($netdiscs, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    private function report_default_header($params, $data)
    {
        $mdc = URL::to('public/images/reports/mdc.jpg');
        $tuv = URL::to('public/images/reports/tuv.jpg');

        $companyid = $params['params']['companyid'];
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $reporttype = $params['params']['dataparams']['reporttype'];

        $str = '';
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        if ($companyid == 8) {
            $str .= "<div style='position: relative;'>";
            $str .= $this->reporter->begintable('800');
            $qry = "select name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= "<div style='position:absolute; top: 60px;'>";
            $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
            $str .= $this->reporter->col('<img src ="' . $tuv . '" alt="TUV" width="140px" height ="70px" style="margin-left: 510px;">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
            $str .= "</div>";

            $str .= "</div>";
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->letterhead($center, $username);
            $str .= $this->reporter->endtable();
        }
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Warehouse : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['whname']) ? $data[0]['whname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
        $str .= $this->reporter->col('Yourref : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Project : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['projectname']) ? $data[0]['projectname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
        $str .= $this->reporter->col('Ourref : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Expense : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['expenses']) ? $data[0]['expenses'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
        $str .= $this->reporter->col('Driver : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['driver']) ? $data[0]['driver'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '600', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
        $str .= $this->reporter->col('ODO : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['odometer']) ? $data[0]['odometer'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R P T I O N', '550px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        // $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        return $str;
    }

    public function default_MI_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $reporttype = $params['params']['dataparams']['reporttype'];
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


        // PDF::Image('public/images/reports/mdc.jpg', '45', '35', 100, 40);
        // PDF::Image('public/images/reports/tuv.jpg', '630', '35', 100, 40);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        if ($reporttype == 1) {
            $this->modulename = 'Withdrawal Slip';
        }
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Name: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Yourref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Project: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['projectname']) ? $data[0]['projectname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Ourref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Expense: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['expenses']) ? $data[0]['expenses'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Driver: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['driver']) ? $data[0]['driver'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(550, 0, '', '', 'L', false, 0, '',  '');
        PDF::MultiCell(50, 0, "ODO: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['odometer']) ? $data[0]['odometer'] : ''), 'B', 'L', false, 0, '',  '');


        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(120, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(60, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(60, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
        // PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
        PDF::MultiCell(110, 0, "TOTAL", '', 'R', false);

        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_MI_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;
        $netdiscs = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_MI_header_PDF($params, $data);

        $arritemname = array();
        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                // $arritemname = (str_split($data[$i]['itemname'], 28));
                // $itemcodedescs = [];
                // if(!empty($arritemname)) {
                //   foreach($arritemname as $arri) {
                //     if(strstr($arri, "\n")) {
                //       $array = preg_split("/\r\n|\n|\r/", $arri);
                //       foreach($array as $arr) {
                //         array_push($itemcodedescs, $arr);
                //       }
                //     } else {
                //       array_push($itemcodedescs, $arri);
                //     }
                //   }
                // }
                // $countarr = count($itemcodedescs);
                // $maxrow = $countarr;
                // $maxh = PDF::GetStringHeight(200, $data[$i]['itemname']);

                $maxrow = 1;
                $barcode = $data[$i]['barcode'];
                $qty = number_format($data[$i]['qty'], $decimalqty);
                $uom = $data[$i]['uom'];
                $itemname = $data[$i]['itemname'];
                $amt = number_format($data[$i]['amt'], 2);
                $disc = $data[$i]['disc'];
                $ext = number_format($data[$i]['ext'], $decimalprice);
                $netofdisc =  number_format($data[$i]['factor'] * $data[$i]['amt'], $decimalcurr);
                // $net = $netofdisc - ($companyid == 43) ? $data[$i]['disc'] : number_format($data[$i]['disc'], 2);

                $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
                $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
                $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
                $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_qty, $arr_uom, $arr_itemname, $arr_amt, $arr_disc, $arr_ext]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(120, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 1);
                    PDF::MultiCell(60, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(60, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(250, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    // PDF::MultiCell(100, 0, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(110, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', false, 1);
                }

                // if ($data[$i]['itemname'] == '') {
                // } else {
                //   for($r = 0; $r < $maxrow; $r++) {
                //     if($r == 0) {
                //         $barcode =  $data[$i]['barcode'];
                //         $qty = number_format($data[$i]['qty'], $decimalqty);
                //         $uom = $data[$i]['uom'];
                //         $amt = number_format($data[$i]['amt'], $decimalprice);
                //         $disc = $data[$i]['disc'];
                //         $ext = number_format($data[$i]['ext'], $decimalprice);
                //     } else {
                //         $barcode = '';
                //         $qty = '';
                //         $uom = '';
                //         $amt = '';
                //         $disc = '';
                //         $ext = '';
                //     }
                //     PDF::SetFont($font, '', $fontsize);
                //     PDF::MultiCell(100, 0, $barcode, '', 'C', false, 0, '', '', true, 1);
                //     PDF::MultiCell(50, 0, $qty, '', 'R', false, 0, '', '', false, 1);
                //     PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
                //     PDF::MultiCell(200, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
                //     PDF::MultiCell(100, 0, $amt, '', 'R', false, 0, '', '', false, 1);
                //     PDF::MultiCell(100, 0, $disc, '', 'R', false, 0, '', '', false, 1);
                //     PDF::MultiCell(100, 0, $ext, '', 'R', false, 1, '', '', false, 1);
                //   }
                // }
                // $netdiscs += $net;
                $totalext += $data[$i]['ext'];

                if (intVal($i) + 1 == $page) {
                    $this->default_MI_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }
        // // PDF::MultiCell(700, 0, "", "T");
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(590, 0, number_format($netdiscs, $decimalprice), 'T', 'R', false, 0);
        // PDF::MultiCell(110, 0, '', 'T', 'R');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(600, 0, 'GRAND TOTAL: ', 'T', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), 'T', 'R');

        // PDF::MultiCell(760, 0, '', 'B');
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

        //PDF::AddPage();
        //$b = 62;
        //for ($i = 0; $i < 1000; $i++) {
        //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
        //  PDF::MultiCell(0, 0, "\n");
        //  if($i==$b){
        //    PDF::AddPage();
        //    $b = $b + 62;
        //  }
        //}

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
