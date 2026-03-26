<?php

namespace App\Http\Classes\modules\modulereport\cbbsi;

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

class agent
{

  private $modulename = "AGENT LEDGER - PROFILE";
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
    $fields = ['prepared', 'received', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select 'PDFM' as print,
    '' as prepared,
    '' as received,
    '' as approved
    ");
  }


  public function generateResult($config)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = md5($config['params']['dataid']);

    $query = "select client.client,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,client.rem,
    client.email,client.contact,client.fax,client.start,client.status,client.quota,
    client.area,client.province,client.region,client.groupid,client.issupplier,client.iscustomer,
    client.isagent,client.isemployee
    from client where md5(client.clientid)='$clientid'";

    return $this->coreFunctions->opentable($query);
  }

  public function reportplotting($config)
  {
    $data = $this->generateResult($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_agent_layout($config, $data);
    } else {
      $str = $this->rpt_agent_PDF($config, $data);
    }
    return $str;
  }


  public function rpt_agent_PDF($config, $data)
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

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(760, 30, $this->modulename, '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Agent : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->client) ? $data[0]->client : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Telephone No/s: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Fax No/s: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Mobile No/s: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Remarks : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->rem) ? $data[0]->rem : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Email Address: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, '', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Contact Person : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "", "T");

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Started : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->start) ? $data[0]->start : ''), '', 'L', false, 0);
    if ($data[0]->issupplier == 1) {
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(300, 20, "|| SUPPLIER", '', 'L', false);
    } else {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(300, 20, "Supplier", '', 'L', false);
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Status : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->status) ? $data[0]->status : ''), '', 'L', false, 0);
    if ($data[0]->iscustomer == 1) {
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(300, 20, "|| CUSTOMER", '', 'L', false);
    } else {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(300, 20, "Customer", '', 'L', false);
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Quota : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->quota) ? $data[0]->quota : ''), '', 'L', false, 0);
    if ($data[0]->isagent == 1) {
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(300, 20, "|| AGENT", '', 'L', false);
    } else {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(300, 20, "Agent", '', 'L', false);
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Area : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->area) ? $data[0]->area : ''), '', 'L', false, 0);
    if ($data[0]->isemployee == 1) {
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(300, 20, "|| EMPLOYEE", '', 'L', false);
    } else {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(300, 20, "Employee", '', 'L', false);
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Province : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->province) ? $data[0]->province : ''), '', 'L', false, 0);
    PDF::MultiCell(300, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Region : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->region) ? $data[0]->region : ''), '', 'L', false, 0);
    PDF::MultiCell(300, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Group : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->groupid) ? $data[0]->groupid : ''), '', 'L', false, 0);
    PDF::MultiCell(300, 20, "", '', 'L', false);

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

  public function rpt_agent_layout($config, $data)
  {

    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT LEDGER - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Telephone No/s:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Remarks:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->rem) ? $data[0]->rem : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Started :', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col((isset($data[0]->start) ? $data[0]->start : ''), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');

    if ($data[0]->issupplier == 1) {
      $str .= $this->reporter->col('|| SUPPLIER', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    } else {
      $str .= $this->reporter->col('Supplier', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    }
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Status :', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col((isset($data[0]->status) ? $data[0]->status : ''), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');

    if ($data[0]->iscustomer == 1) {
      $str .= $this->reporter->col('|| CUSTOMER', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    } else {
      $str .= $this->reporter->col('Customer', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    }

    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Quota :', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format((isset($data[0]->quota) ? $data[0]->quota : ''), 2), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');

    if ($data[0]->isagent == 1) {
      $str .= $this->reporter->col('|| AGENT', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    } else {
      $str .= $this->reporter->col('Agent', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    }
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Area :', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col((isset($data[0]->area) ? $data[0]->area : ''), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');

    if ($data[0]->isemployee == 1) {
      $str .= $this->reporter->col('|| EMPLOYEE', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    } else {
      $str .= $this->reporter->col('Employee', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    }
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Province :', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col((isset($data[0]->province) ? $data[0]->province : ''), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Region :', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col((isset($data[0]->region) ? $data[0]->region : ''), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Group :', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col((isset($data[0]->groupid) ? $data[0]->groupid : ''), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }
}
