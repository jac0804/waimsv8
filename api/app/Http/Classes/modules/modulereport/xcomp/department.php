<?php

namespace App\Http\Classes\modules\modulereport\xcomp;

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

class department
{
  private $modulename = "Department Ledger";
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
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

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

  public function generateResult($config)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = md5($config['params']['dataid']);

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];


    $query = "select client.client,client.clientname,client.addr,client.tel,
        client.tel2,client.tin,client.mobile,client.rem,
        client.email,client.contact,client.fax,client.start,client.status,client.quota,
        client.area,client.province,client.region,client.groupid,client.issupplier,client.iscustomer,
        client.isagent,client.isemployee
        from client where md5(client.clientid)='$clientid'";

    return $this->coreFunctions->opentable($query);
  }


  public function reportplotting($config, $data)
  {
    $data = $this->generateResult($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_department_layout($config, $data);
    } else {
      $str = $this->rpt_department_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_department_layout($config, $data)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT LEDGER - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
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

  public function rpt_department_PDF($config, $data)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

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
    PDF::MultiCell(760, 30, "DEPARTMENT LEDGER - PROFILE", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(80, 20, "Department : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(680, 20, '(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '   ' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(80, 20, "Address : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(680, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false);

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
