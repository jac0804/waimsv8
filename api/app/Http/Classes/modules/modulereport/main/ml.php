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

class ml
{

    private $modulename = "Mechanic Ledger";
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
        $fields = ['radioprint',  'prepared', 'checked', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
        return $this->coreFunctions->opentable(
            "select 
            'PDFM' as print,
            '$username' as prepared,
            '' as checked,
            '' as received
            "
        );
    }
    public function report_default_query($config)
    {
        $mechid=$config['params']['dataid'];
        $query="select mech.clientname as mechname,ifnull(mech.addr, '') as addr,ifnull(mech.tin, '') as tin,
                  ifnull(mech.fax, '') as fax,ifnull(mech.mobile, '') as mobile,
                  ifnull(mech.email, '') as email,ifnull(mech.contact, '') as contact,
                  date(mech.start) as start, mech.area, mech.province, mech.region,mech.rate,mech.tel,mech.status,
                  head.trno, head.docno,date(head.dateid) as dateid,
                  cvh.cmake as vehicle, cvh.mileage,
                  jt.docno as jobcode, jt.jobtitle as description,am.cost as labor,
                  am.rem, jobs.jobcode as code,jobs.description as task, '' as bgcolor
  
        from lahead as head
        left join client on client.client = head.client
        left join cvehicle as cvh on cvh.clientid = client.clientid and cvh.line = head.carid
        left join amjobs as pt on pt.trno = head.trno
        left join jobthead as jt on jt.line = pt.jobid
        left join amtask as am on am.trno = head.trno and am.jobline = pt.line
        left join jobtask as jobs on jobs.line = am.laborline
        left join client as mech on mech.clientid=$mechid
        where head.doc = 'AM' and am.mecline=$mechid

        union all

        select mech.clientname as mechname,ifnull(mech.addr, '') as addr,ifnull(mech.tin, '') as tin,
                  ifnull(mech.fax, '') as fax,ifnull(mech.mobile, '') as mobile,
                  ifnull(mech.email, '') as email,ifnull(mech.contact, '') as contact,
                  date(mech.start) as start, mech.area, mech.province, mech.region,mech.rate,mech.tel,mech.status,
                  head.trno, head.docno,date(head.dateid) as dateid,
                  cvh.cmake as vehicle, cvh.mileage,
                  jt.docno as jobcode, jt.jobtitle as description,am.cost as labor,
                  am.rem, jobs.jobcode as code,jobs.description as task, '' as bgcolor
        from glhead as head
        left join client on client.clientid = head.clientid
        left join cvehicle as cvh on cvh.clientid = client.clientid and cvh.line = head.carid
        left join hamjobs as pt on pt.trno = head.trno
        left join jobthead as jt on jt.line = pt.jobid
        left join hamtask as am on am.trno = head.trno and am.jobline = pt.line
        left join jobtask as jobs on jobs.line = am.laborline
        left join client as mech on mech.clientid=$mechid
        where head.doc = 'AM' and am.mecline=$mechid";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn  

    public function reportplotting($params, $data)
    {
         return $this->default_mech_PDF($params, $data);
            
    }


    public function default_mech_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
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
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n", '', 'C');

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 0, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, "Document # : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(120, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Mechanic Name : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(630, 20, (isset($data[0]['mechname']) ? $data[0]['mechname'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Address", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(10, 20, " : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(630, 20, (isset($data[0]['addr']) ? $data[0]['addr'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      
      
       

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Tin", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(10, 20, " : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Fax", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['fax']) ? $data[0]['fax'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Cel # : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['mobile']) ? $data[0]['mobile'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Telephone", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(10, 20, " : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Email : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['email']) ? $data[0]['email'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Contact Person : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Start Date", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(10, 20, " : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['start']) ? $data[0]['start'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Status : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['status']) ? $data[0]['status'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(90, 20, "Rate : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 20, (isset($data[0]['rate']) ? $data[0]['rate'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);




        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Area", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(10, 20, " : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(630, 20, (isset($data[0]['area']) ? $data[0]['area'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Province", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(10, 20, " : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(630, 20, (isset($data[0]['province']) ? $data[0]['province'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Region", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(10, 20, " : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(630, 20, (isset($data[0]['region']) ? $data[0]['region'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

       


        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 11);
        PDF::MultiCell(100, 0, "Doc#", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "Job Code", '', 'L', false, 0);
        PDF::MultiCell(95, 0, "Job Desc", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "Task Code", '', 'L', false, 0);
        PDF::MultiCell(95, 0, "Task Desc", '', 'L', false,0);
        PDF::MultiCell(80, 0, "Notes", '', 'L', false,0);  
        PDF::MultiCell(70, 0, "Vehicle", '', 'L', false,0);
        PDF::MultiCell(70, 0, "Mileage", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "Labor", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }

    public function default_mech_PDF($params, $data)
    {
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
        $fontsize = "10";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_mech_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $docno = $data[$i]['docno'];
                $jobcode = $data[$i]['jobcode'];
                $description = $data[$i]['description'];
                $code = $data[$i]['code'];
                $task = $data[$i]['task'];
                $rem = $data[$i]['rem'];
                $vehicle = $data[$i]['vehicle'];
                $mileage = $data[$i]['mileage'];
                $labor = number_format($data[$i]['labor'], 2);
    

                $arr_docno = $this->reporter->fixcolumn([$docno], '15', 0);
                $arr_jobcode = $this->reporter->fixcolumn([$jobcode], '20', 0);
                $arr_description = $this->reporter->fixcolumn([$description], '20', 0);
                $arr_code = $this->reporter->fixcolumn([$code], '20', 0);
                $arr_task = $this->reporter->fixcolumn([$task], '20', 0);
                $arr_rem = $this->reporter->fixcolumn([$rem], '20', 0);
                $arr_vehicle = $this->reporter->fixcolumn([$vehicle], '20', 0);
                $arr_mileage = $this->reporter->fixcolumn([$mileage], '20', 0);
                $arr_labor = $this->reporter->fixcolumn([$labor], '13', 0);
     

                $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_jobcode, $arr_description, $arr_code, $arr_task, $arr_rem, $arr_vehicle, $arr_mileage, $arr_labor]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_jobcode[$r]) ? $arr_jobcode[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(95, 15, ' ' . (isset($arr_description[$r]) ? $arr_description[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 15, ' ' . (isset($arr_code[$r]) ? $arr_code[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(85, 15, ' ' . (isset($arr_task[$r]) ? $arr_task[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 15, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_vehicle[$r]) ? $arr_vehicle[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_mileage[$r]) ? $arr_mileage[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(70, 15, ' ' . (isset($arr_labor[$r]) ? $arr_labor[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                }

                // $totalext += $data[$i]['ext'];

                if (PDF::getY() > 900) {
                    $this->default_mech_header_PDF($params, $data);
                }
            }
        }

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(700, 0, '', 'B');

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(700, 0, '', '');

        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        // PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

        // PDF::MultiCell(0, 0, "\n");

        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        // PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Checked By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

  
}
