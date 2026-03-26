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

class vr
{

  private $modulename = "Vechicle Scheduling";
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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'prepared', 'approved', 'received'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $paramstr = "select
                  'PDFM' as print,
                  '' as prepared,
                  '' as approved,
                  '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];

    $query = "select date(head.dateid) as dateid, head.docno, emp.clientname as employee, head.rem, 
        driver.clientname as driver, dept.clientname as department, vehicle.clientname as vehicle,
        left(head.schedin,10) as schedin, left(head.schedout,10) as schedout, customer.client as customer, customer.clientname as customername
        from vrhead as head left join vrstock as stock on stock.trno=head.trno
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as dept on dept.clientid = head.deptid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as customer on customer.clientid = stock.clientid
        where head.doc='vr' and head.trno='$trno'
        ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    return $this->default_PDF($params, $data);
  }

  public function default_header_PDF($params, $data)
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
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(520, 0, strtoupper($this->modulename), '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "DOCUMENT # : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 10, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "EMPLOYEE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['employee']) ? $data[0]['employee'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "DATE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "DRIVER : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['driver']) ? $data[0]['driver'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "VEHICLE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['vehicle']) ? $data[0]['vehicle'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "DEPARTMENT : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, (isset($data[0]['department']) ? $data[0]['department'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "START TIME : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['schedin']) ? $data[0]['schedin'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "END TIME : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['schedout']) ? $data[0]['schedout'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "REMARKS : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(620, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(300, 0, "CUSTOMER", '', 'L', false, 0);
    PDF::MultiCell(400, 0, "CUSTOMER NAME", '', 'C', false);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_PDF($params, $data)
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
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        // $arracnoname = (str_split($data[$i]['customer'], 40));
        // $acnonamedescs = [];
        // if (!empty($arracnoname)) {
        //   foreach ($arracnoname as $arri) {
        //     if (strstr($arri, "\n")) {
        //       $array = preg_split("/\r\n|\n|\r/", $arri);
        //       foreach ($array as $arr) {
        //         array_push($acnonamedescs, $arr);
        //       }
        //     } else {
        //       array_push($acnonamedescs, $arri);
        //     }
        //   }
        // }
        // $countarr = count($acnonamedescs);
        // $maxrow = $countarr;
        $maxrow = 1;
        $arr_customer = $this->reporter->fixcolumn([$data[$i]['customer']],'35',0);
        $arr_customername = $this->reporter->fixcolumn([$data[$i]['customername']],'35',0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_customer, $arr_customername]);

        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(300, 0, (isset($arr_customer[$r]) ? $arr_customer[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(400, 18, (isset($arr_customername[$r]) ? $arr_customername[$r] : ''), '', 'C', 0, 1, '', '', true, 0, false, false, 'M');
        }

        // if ($data[$i]['customer'] == '') {
        // } else {
        //   for ($r = 0; $r < $maxrow; $r++) {
        //     if ($r == 0) {
        //       $customer =  $data[$i]['customer'];
        //       $customername = $data[$i]['customername'];
        //     } else {
        //       $customer = '';
        //       $customername = '';
        //     }
        //   }
        // }

        if (intVal($i) + 1 == $page) {
          $this->default_header_PDF($params, $data);
          $page += $count;
        }
      }
    }


    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
