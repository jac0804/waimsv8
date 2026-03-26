<?php

namespace App\Http\Classes\modules\modulereport\aquamax;

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

class wm
{
    private $modulename = "WATER BILL";
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
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
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
            '' as prepared,
            '' as approved,
            '' as received
        "
        );
    }

    public function reportplotting($params, $data)
    {
        return $this->default_AR_PDF($params, $data);
    }

    public function report_default_query($trno)
    {
        $qry = $this->default_query($trno);
        $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
        return $result;
    } //end fn

    public function default_query($trno)
    {
        $qry = "select pm.name as projname, head.docno, date(head.dateid) as dateid, head.rem, head.due, date(head.sdate1) as sdate1, date(head.sdate2) as sdate2,
        item.barcode, item.shortname as address, client.clientname as customer, isqty, isqty2, isqty3
        from lahead as head
        left join lastock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join client on item.clientid = client.clientid
        left join projectmasterfile as pm on pm.line = head.projectid
        where head.trno = $trno
        union all
        select pm.name as projname, head.docno, date(head.dateid) as dateid, head.rem, head.due, date(head.sdate1) as sdate1, date(head.sdate2) as sdate2,
        item.barcode, item.shortname as address, client.clientname as customer, isqty, isqty2, isqty3
        from glhead as head
        left join glstock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join client on item.clientid = client.clientid
        left join projectmasterfile as pm on pm.line = head.projectid
        where head.trno = $trno 
        order by address";
        return $qry;
    }

    public function default_AR_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];


        $qry = " select code ,name ,address ,tel from center where code = '" . $center  . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)  . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        PDF::SetCellPadding(2);
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80,  0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "Project: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(440, 0, (isset($data[0]['projname']) ? $data[0]['projname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(30, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "Notes: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(440, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(30, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 0, "Due: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['due']) ? $data[0]['due'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "Start Date", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['sdate1']) ? $data[0]['sdate1'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(20, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "End Date", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['sdate2']) ? $data[0]['sdate2'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(20, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(95, 25, "Meter #", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(170, 25, "Meter Address", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(170, 25, "Name", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(95, 25, "Present Reading", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(95, 25, "Previous Reading", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(95, 25, "Consumption", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }

    public function default_AR_PDF($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_AR_header_PDF($params, $data);

        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $barcode = $data[$i]['barcode'];
            $address = $data[$i]['address'];
            $customer = $data[$i]['customer'];
            $isqty = number_format($data[$i]['isqty'], 2);
            $isqty2 = number_format($data[$i]['isqty2'], 2);
            $isqty3 = number_format($data[$i]['isqty3'], 2);

            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_address = $this->reporter->fixcolumn([$address], '30', 0);
            $arr_customer = $this->reporter->fixcolumn([$customer], '25', 0);
            $arr_isqty = $this->reporter->fixcolumn([$isqty], '13', 0);
            $arr_isqty2 = $this->reporter->fixcolumn([$isqty2], '13', 0);
            $arr_isqty3 = $this->reporter->fixcolumn([$isqty3], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_address, $arr_customer, $arr_isqty, $arr_isqty2, $arr_isqty3]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(170, 15, ' ' . (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(170, 15, ' ' . (isset($arr_customer[$r]) ? $arr_customer[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(95, 15, ' ' . (isset($arr_isqty3[$r]) ? $arr_isqty3[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(95, 15, ' ' . (isset($arr_isqty2[$r]) ? $arr_isqty2[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(95, 15, ' ' . (isset($arr_isqty[$r]) ? $arr_isqty[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            }

            if (PDF::getY() > 900) {
                $this->default_AR_header_PDF($params, $data);
            }
        }

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
