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

class leaseprovision
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
    // $fields = ['radioprint','prepared','approved','received', 'print'];
    $fields = ['prepared', 'approved', 'received', 'print'];

    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
  ");
  }

  public function report_default_query($config)
  {

    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = $config['params']['dataid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $query = "select 
      tenant.clientid, tenant.client, tenant.clientname,
      tenant.addr, tenant.bstyle, loc.name as locname
     from client as tenant
     left join loc as loc on loc.line = tenant.locid
     where istmptenant = 1 and tenant.clientid = '$clientid'";

    return $this->coreFunctions->opentable($query);
  } //end fn

  public function reportplotting($config, $data)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_employee_PDF($config, $data);
    return $str;
  }


  public function rpt_employee_PDF($config, $data)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $count = 55;
    $page = 54;
    $fontsize = "11";
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    if ($companyid == 3) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, strtoupper($username) . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->code . ' RSSC'), '', 'L');
    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    }

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(760, 30, "LEASE PROVISION", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 20, "Tenant : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(500, 20, '(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '   ' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 20, "Business Address : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(500, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 20, "Business Style : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(500, 20, (isset($data[0]->bstyle) ? $data[0]->bstyle : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 20, "Location : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(500, 20, (isset($data[0]->locname) ? $data[0]->locname : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $approved, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
