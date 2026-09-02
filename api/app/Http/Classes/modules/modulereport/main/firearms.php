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

class firearms
{

    private $modulename;
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
        $fields = ['radioprint','print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);


        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $paramstr = "select 'PDFM' as print";
        return $this->coreFunctions->opentable($paramstr);
    }

    public function generateResult($config)
    {
        $query = $this->firearms_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function reportplotting($config, $data)
    {
        ini_set('memory_limit', '-1');
        $data = $this->generateResult($config);
        $str = $this->firearms_PDF($config, $data);
        return $str;
    }

    public function firearms_QUERY($config)
    {
        $center   = $config['params']['center'];
        $clientid =$config['params']['dataid'];
        $query=" select fr.line as clientid, fr.code as client, fr.make, fr.type, fr.expiry as expiry1, fr.serialno, fr.licenseno, fr.cal 
        from firearms as fr where fr.line = '$clientid' and fr.center= '$center'";
        return $query;
    } 

  

    //DEFAULT LAYOUT
    public function firearms_PDFs($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $count = 55;
        $page = 54;
        $layoutsize = '800';
        $font =  "Verdana";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();

     
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FIREARMS LEDGER', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Fire Arms Code:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->client) ? $data[0]->client : ''), '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Make:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->make) ? $data[0]->make : ''), '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Type:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->type) ? $data[0]->type : ''), '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Serial No:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->serialno) ? $data[0]->serialno : ''), '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();




        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('License No:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->licenseno) ? $data[0]->licenseno : ''), '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Expiry:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->expiry1) ? $data[0]->expiry1 : ''), '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CAL:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->cal) ? $data[0]->cal : ''), '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }


    public function firearms_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
    
        $count = 55;
        $page = 54;
        $fontsize = "10";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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
        PDF::SetMargins(20, 20);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');


        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");



        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "Fire Arms Ledger", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 15, "Fire Arms Code", '', 'L', false, 0);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, ":", '', 'L', false, 0);
       
        PDF::MultiCell(610, 15, (isset($data[0]->client) ? $data[0]->client : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 15, "Make", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, ":", '', 'L', false, 0);

        PDF::MultiCell(610, 15, (isset($data[0]->make) ? $data[0]->make : ''), '', 'L', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 15, "Type", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, ":", '', 'L', false, 0);
        PDF::MultiCell(610, 15, (isset($data[0]->type) ? $data[0]->type : ''), '', 'L', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 15, "Serial No", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, ":", '', 'L', false, 0);
        PDF::MultiCell(610, 15, (isset($data[0]->serialno) ? $data[0]->serialno : ''), '', 'L', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 15, "License No", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, ":", '', 'L', false, 0);
        PDF::MultiCell(610, 15, (isset($data[0]->licenseno) ? $data[0]->licenseno : ''), '', 'L', false, 1);



        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 15, "Expiry", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, ":", '', 'L', false, 0);
        PDF::MultiCell(610, 15, (isset($data[0]->expiry1) ? $data[0]->expiry1 : ''), '', 'L', false, 1);



        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 15, "CAL", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(30, 15, ":", '', 'L', false, 0);
        PDF::MultiCell(610, 15, (isset($data[0]->cal) ? $data[0]->cal : ''), '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

}
