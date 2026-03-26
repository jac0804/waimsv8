<?php

namespace App\Http\Classes\modules\modulereport\main;

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

class undertime
{

    private $modulename;
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
        // $fields = ['radioprint','prepared','approved','received','print'];
        $fields = ['prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
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

    public function report_default_query($config)
    {

        $trno = $config['params']['dataid'];

        $query = "
        select under.line as trno, under.line as clientid, cl.client, 
        cl.clientname, cl.clientid as empid,
        under.type, date(under.dateid) as dateid, time(dateid) as itime, 
        under.rem,
        case 
          when under.status = 'E' then 'ENTRY'
          when under.status = 'A' then 'APPROVED'
        END as status
        from undertime as under
        left join employee as emp on emp.empid = under.empid
        left join client as cl on cl.clientid = emp.empid
        where under.line=" . $trno;;

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($config, $data)
    {

        return $this->rpt_undertime_PDF($config, $data);
    }

    public function rpt_default_header_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $fontsize = "11";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(720, 30, "UNDERTIME APPLICATION", '', 'L', false);

        PDF::SetFont($font, '', 5);
        // PDF::MultiCell(600, 0, "", '', 'L', false, 0);
        PDF::MultiCell(720, 0, "", '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(120, 15, "Employee Name : ", '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(600, 15, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

        PDF::SetFont($font, '', 5);
        // PDF::MultiCell(600, 0, "", '', 'L', false, 0);
        PDF::MultiCell(720, 0, "", '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(720, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

        PDF::SetFont($font, '', 5);
        // PDF::MultiCell(100, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(720, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(254, 20, "Date", '', 'L', false, 0);
        PDF::MultiCell(253, 20, "Time", '', 'L', false, 0);
        PDF::MultiCell(253, 20, "Status", '', 'L', false);
    }

    public function rpt_undertime_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $fontsize = "11";
        $count = 35;
        $page = 35;
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->rpt_default_header_PDF($config, $data);

        PDF::SetFont($font, '', 5);
        // PDF::MultiCell(100, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(720, 0, "", 'T', 'L', false);

        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $arr_dateid = $this->reporter->fixcolumn([$data[$i]['dateid']], '16', 0);
            $arr_itime = $this->reporter->fixcolumn([$data[$i]['itime']], '16', 0);
            // $arr_type = $this->reporter->fixcolumn([$data[$i]['type']], '16', 0);
            $arr_jstatus = $this->reporter->fixcolumn([$data[$i]['status']], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_itime, $arr_jstatus]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(254, 10, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(253, 10, (isset($arr_itime[$r]) ? $arr_itime[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(253, 10, (isset($arr_jstatus[$r]) ? $arr_jstatus[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }

            if (intVal($i) + 1 == $page) {
                $this->rpt_default_header_PDF($config, $data);
                $page += $count;
            }
        }

        PDF::SetFont($font, '', 5);
        // PDF::MultiCell(100, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(720, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 0, "Remarks : ", '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(620, 0, $data[0]['rem'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(240, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Approved By : ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Received By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(240, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $config['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
