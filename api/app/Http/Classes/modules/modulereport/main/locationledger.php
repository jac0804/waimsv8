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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class locationledger
{

  private $modulename = "LOCATION LEDGER";
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

  public function createreportfilter(){
    $fields = ['radioprint', 'prepared','approved', 'received','print'];
    $col1 = $this->fieldClass->create($fields);        

    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable("select 
      'default' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($filters){
    $trno = $filters['params']['dataid'];
    $query="
    select loc.line, loc.code, loc.name, loc.emeter, loc.wmeter, loc.semeter, loc.area as sqm,
    cl.client, cl.clientname
    from loc as loc
    left join client as cl on cl.locid = loc.line
    where loc.line = '$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data) {
    return $this->default_locationledger_PDF($params, $data);
  }

  public function default_locationledger_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
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
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Name: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(500, 0, (isset($data[0]['name']) ? $data[0]['name'] : ''), 'B', 'L', false, 0, '',  '');
    
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Code #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['code']) ? $data[0]['code'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Electric Meter #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['emeter']) ? $data[0]['emeter'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Water Meter #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['wmeter']) ? $data[0]['wmeter'] : ''), 'B', 'L', false, 0, '',  '');
    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "S. Electric Meter #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['semeter']) ? $data[0]['semeter'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "SQM: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['sqm']) ? $data[0]['sqm'] : ''), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(350, 0, "Tenant Code", '', 'L', false, 0);
    PDF::MultiCell(350, 0, "Tenant Name", '', 'L', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function modifyLengthField()
  {
    $tables = $this->coreFunctions->opentable("show tables"); // get all table names
    foreach ($tables as $key => $table) {
      foreach ($table as $k => $tablename) {
        $structure =  $this->coreFunctions->opentable("show full columns FROM " . $tablename . "
          where field = 'viewby'
          or Field = 'editby'
          or Field = 'createby'
          or Field = 'createdby'
          or Field = 'openby'
          or Field = 'lockuser'
          or Field = 'postedby'
          or Field = 'encodedby'
          or Field = 'users'
          or Field = 'user'
          or Field = 'userid'
          or Field = 'approvedby'"); // get all table structure
        foreach ($structure as $skey => $sval) {
          // switch ($sval->Field) {
            // case 'viewby':
            // case 'editby':
            // case 'createby':
            // case 'createdby':
            // case 'openby':
            // case 'lockuser':
            // case 'postedby':
            // case 'encodedby':
            // case 'users':
            // case 'user':
            // case 'userid':
            // case 'approvedby':
            //   if (substr($sval->Type, 0, 7) == "varchar") {
                // $this->coreFunctions->execqry("UPDATE $tablename SET $sval->Field = '' WHERE $sval->Field IS NULL || $sval->Field = ''");
                // $this->coreFunctions->sbcaddcolumn($tablename, $sval->Field, "varchar(200) NOT NULL DEFAULT ''", 1);
              // }
          //     break;
          // }
        }
      }
    }
  }

  public function default_locationledger_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_locationledger_header_PDF($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $arr_client = $this->reporter->fixcolumn([$data[$i]['client']],'35',0);
      $arr_clientname = $this->reporter->fixcolumn([$data[$i]['clientname']],'35',0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_client, $arr_clientname]);

      for($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(350, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(350, 0, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '');
      }
      if (intVal($i) + 1 == $page) {
        $this->default_locationledger_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(700, 0, "", "");
    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    //PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    //PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    //PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    // PDF::MultiCell(253, 0, 'Prepared By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Approved By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Received By: ', '', 'C');


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
