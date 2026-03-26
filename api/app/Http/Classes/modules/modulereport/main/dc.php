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

class dc
{
  private $modulename = "Daily Collection Report";

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public $tablenum = 'transnum';
  public $head = 'dchead';
  public $hhead = 'hdchead';
  public $detail = 'dcdetail';
  public $hdetail = 'hdcdetail';

  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public function __construct() {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter($config) {
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config) {
    $paramstr = "select 'PDFM' as print";
    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config) {
    $trno = $config['params']['dataid'];

    $qry = "select head.docno, date(head.dateid) as dateid, head.collector, d.amount, d.client, client.clientname, head.isinclude
      from ".$this->head." as head left join ".$this->detail." as d on d.trno=head.trno left join client on client.client=d.client
        where head.trno=".$trno."
      union all
      select head.docno, date(head.dateid) as dateid, head.collector, d.amount, d.client, client.clientname, head.isinclude
      from ".$this->hhead." as head left join ".$this->hdetail." as d on d.trno=head.trno left join client on client.client=d.client
        where head.trno=".$trno;
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data) {
    return $this->default_DC_PDF($params, $data);
  }

  public function default_DC_header_PDF($params, $data) {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
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
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 0, $this->modulename, '', 'C', false, 1, '',  '100');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'Collector:', '', 'L', false, 0, '', '');
    PDF::MultiCell(390, 0, (isset($data[0]['collector']) ? $data[0]['collector'] : ''), 'B', 'L', false, 0, '', '');
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(150, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '', 'L', false, 1);

    $isinclude = (isset($data[0]['isinclude']) ? $data[0]['isinclude'] : false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(25, 0, '', '', 'L', false, 0);
    PDF::MultiCell(25, 0, '', ($isinclude ? '' : 1), '', ($isinclude ? true : false), 0);
    PDF::MultiCell(50, 0, ' Included', '', 'L', false, 0);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(25, 0, '', ($isinclude ? 1 : ''), '', ($isinclude ? false : true), 0);
    PDF::MultiCell(50, 0, ' Excluded', '', 'L', false, 0);
    PDF::MultiCell(265, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Doc Date:', '', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1);

    PDF::MultiCell(720, 0, '', 'B', '', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, 'Customer Code', ['B'=>['dash'=>1]], 'C', false, 0);
    PDF::MultiCell(270, 0, 'Customer Name', ['B'=>['dash'=>1]], 'C', false, 0);
    PDF::MultiCell(100, 0, 'Balance', ['B'=>['dash'=>1]], 'C', false, 0);
    PDF::MultiCell(100, 0, 'Amount Paid', ['B'=>['dash'=>1]], 'C', false, 0);
    PDF::MultiCell(100, 0, 'Remarks', ['B'=>['dash'=>1]], 'C', false, 1);
  } // end fn

  public function default_DC_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_DC_header_PDF($params, $data);
    $totalamt = 0;
    if(!empty($data)) {
      foreach($data as $d) {
        $maxrow = 1;
        $arr_client = $this->reporter->fixcolumn([$d['client']], '15', 0);
        $arr_clientname = $this->reporter->fixcolumn([$d['clientname']], '25', 0);
        $arr_amount = $this->reporter->fixcolumn([number_format($d['amount'], $decimalcurr)], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_client, $arr_clientname, $arr_amount]);
        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(150, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(270, 0, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(100, 0, (isset($arr_amount[$r]) ? $arr_amount[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(98, 0, '', ['B'=>['dash'=>0]], 'L', false, 0);
          PDF::MultiCell(4, 0, '', '', '', false, 0);
          PDF::MultiCell(98, 0, '', ['B'=>['dash'=>0]], 'R', false);
        }
        $totalamt += $d['amount'];
      }
    }
    PDF::MultiCell(720, 0, '', ['B'=>['dash'=>1]], 'L', false);
    PDF::MultiCell(720, 0, '', '', '', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, 'Grand Total', ['B'=>['dash'=>0]], 'L', false, 0);
    PDF::MultiCell(270, 0, '', ['B'=>['dash'=>0]], 'L', false, 0);
    PDF::MultiCell(100, 0, number_format($totalamt,$decimalcurr), ['B'=>['dash'=>0]], 'R', false, 0);
    PDF::MultiCell(200, 0, '', ['B'=>['dash'=>0]], 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, '', '', '', false);

    PDF::MultiCell(75, 0, 'Total Cash:', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, 'Total Check:', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L', false);

    PDF::MultiCell(720, 0, '', '', '', false);

    PDF::MultiCell(200, 0, 'Field Collection Remitted by:', '', 'L', false, 0);
    PDF::MultiCell(175, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Received Above Amount', '', 'L', false);

    PDF::MultiCell(720, 0, '', '', '', false);

    PDF::MultiCell(275, 0, isset($data[0]['collector']) ? $data[0]['collector'] : '', 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(275, 0, '', 'B', 'L', false);

    PDF::MultiCell(275, 0, "Collector's Name", '', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(275, 0, 'Cashier', '', 'C', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}

// use dejavusans to work
// &#x2713; check
// &#x2611; checkbox w/ check
// &#9744;  checkbox w/o
// &#8369;  peso sign
