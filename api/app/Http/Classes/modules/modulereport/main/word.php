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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class word
{

    private $modulename = "Rest Day Form";
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


        $fields = ['radioprint', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $companyid = $config['params']['companyid'];

        // $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
        // $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
        // $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);


        //         $paramstr = "select
        //   'PDFM' as print,
        //   '$username' as prepared,
        //   '$approved' as approved,
        //   '$received' as received";
        $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as noted";
        return $this->coreFunctions->opentable($paramstr);
    }

    public function report_default_query($config)
    {

        $trno = $config['params']['dataid'];
        $query = "select cl.client,
        cl.clientname, cl.clientid as empid,csapp.line,
        date(csapp.dateid) as dateid,csapp.schedin,csapp.schedout,
        csapp.rem,date(csapp.createdate) as createdate,csapp.submitdate,emp.divid,csapp.daytype,
        csapp.reason,dept.clientname as deptname,csapp.approvedby
        from changeshiftapp as csapp
        left join employee as emp on emp.empid = csapp.empid
        left join client as cl on cl.clientid = emp.empid
        left join client as dept on dept.clientid = emp.deptid
		where csapp.line = '$trno' ";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn  

    public function reportplotting($params, $data)
    {
        return $this->default_restday_PDF($params, $data);
    }

    public function default_restday_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name, address, tel, code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

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

        PDF::MultiCell(0, 0, "\n\n");


        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

        PDF::SetFont($fontbold, '', 20);
        PDF::SetTextColor(245, 16, 0);
        PDF::MultiCell(0, 30, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetTextColor(0, 0, 0);

        // change logo
        // PDF::Image($this->companysetup->getlogopath($params['params']) . 'warningsign.jpg', '80', '25', 100, 100);

        PDF::SetTextColor(2, 47, 115);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(160, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $headerdata[0]->address, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0);

        // PDF::MultiCell(0, 0, "\n\n\n");
        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(720, 0, 'W.O.R.D REQUEST FORM', '', 'C', false, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(720, 0, '(WORK ON REST DAY)', '', 'C', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(40, 0, "NAME: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(200, 0, "" . $data[0]['clientname'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(260, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 0, "DATE FILED: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, '' . $data[0]['createdate'], 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "DEPARTMENT: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(160, 0, "" . $data[0]['deptname'], 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(260, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(180, 0, "DATE OF REQUEST DUTY", 'TBL', 'C', false, 0);
        PDF::MultiCell(180, 0, "REASON", 'TBL', 'C', false, 0);
        PDF::MultiCell(180, 0, "NO. OF DAYS", 'TBL', 'C', false, 0);
        PDF::MultiCell(180, 0, "REMARKS", 'TBLR', 'C', false, 1);
    }

    public function default_restday_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_restday_header($params, $data);

        $countarr = 0;
        $printline = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $clientname = $data[$i]['clientname'];
                $dateid = $data[$i]['dateid'];
                $rem = $data[$i]['rem'];
                $reason = $data[$i]['reason'];

                $arr_reason = $this->reporter->fixcolumn([$reason], '45', 0);
                $arr_rem = $this->reporter->fixcolumn([$rem], '45', 0);
                $arr_dateid = $this->reporter->fixcolumn([$dateid], '45', 0);
                $maxrow = $this->othersClass->getmaxcolumn([$arr_reason, $arr_rem, $arr_dateid]);
                for ($r = 0; $r < $maxrow; $r++) {

                    $printline++;
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(180, 15, '' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(180, 15, '' . (isset($arr_reason[$r]) ? $arr_reason[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(180, 15, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                    PDF::MultiCell(180, 15, '' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'LR', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', false);
                }
                if ($printline >= count($data)) {
                    $this->borderline($printline);
                }
            }
        }

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(240, 0, 'REQUESTED BY: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, '', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'APPROVED BY: ', '', 'L', false, 1);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(28, 0, '', '', 'L', false, 0);
        // approvedby
        PDF::MultiCell(185, 0, $data[0]['approvedby'], 'B', 'L', false, 0);
        PDF::MultiCell(27, 0, '', '', 'L', false, 0);


        PDF::MultiCell(240, 0, '', '', 'C', false, 0);

        PDF::MultiCell(195, 0, '', 'B', 'C', false, 0); // add value
        PDF::MultiCell(45, 0, '', '', 'C', false, 1);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(240, 0, 'DEPARTMENT/ROM', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '', '', 'C', false, 0);
        PDF::MultiCell(280, 0, 'CEO / GM', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2));
        PDF::MultiCell(285, 0, '', 'B', 'C', false, 0);
        PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
        PDF::MultiCell(150, 0, 'TO BE FILLED BY HR', '', 'C', false, 0);
        PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2));
        PDF::MultiCell(285, 0, '', 'B', 'C', false, 1);
        PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, "REMARKS: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(190, 0, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(250, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(100, 0, "DATE RECEIVED: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(120, 0, '', 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "RECORDED BY: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(160, 0, "", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(480, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        // PDF::MultiCell(0, 0, "\n\n");
        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(720, 0, 'NOTED BY: ', '', 'L', false, 1);


        // PDF::MultiCell(0, 0, "\n");

        // PDF::MultiCell(28, 0, '', '', 'L', false, 0);
        // // noted by
        // PDF::MultiCell(185, 0, '', 'B', 'L', false, 0);
        // PDF::MultiCell(27, 0, '', '', 'L', false, 0);


        // PDF::MultiCell(240, 0, '', '', 'C', false, 0);
        // PDF::MultiCell(240, 0, '', '', 'C', false, 1);

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(720, 0, '', '');

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(28, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(185, 0, 'General Manager', '', 'L', false, 0);
        // PDF::MultiCell(27, 0, '', '', 'L', false, 0);

        // PDF::MultiCell(240, 0, '', '', 'C', false, 0);
        // PDF::MultiCell(240, 0, '', '', 'L', false, 1);


        return PDF::Output($this->modulename . '.pdf', 'S');
        // }
    }

    public function borderline($line)
    {
        if ($line > 4) {
            $line = $line + 2;
        } else {
            $line = 5 - $line;
        }
        for ($i = 0; $i < $line; $i++) {
            if ($i == ($line) - 1) {
                PDF::MultiCell(180, 15, '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'LRB', 'L', false, 1, '',  '', true, 1, false, true, 15, 'M', false);
            } else {
                PDF::MultiCell(180, 15, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'L', 'C', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', false);
                PDF::MultiCell(180, 15, '', 'LR', 'L', false, 1, '',  '', true, 1, false, true, 15, 'M', false);
            }
        }
    }
}
