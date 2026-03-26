<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use TCPDF;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


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

class statement_of_account_water_bill
{
  public $modulename = 'Statement of Account Water Bill';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'dateid', 'dclientname', 'dprojectname'];
    $col1 = $this->fieldClass->create($fields);
    switch ($companyid) {
      case 35: //aquamax
        data_set(
          $col1,
          'radioprint.options',
          [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
          ]
        );
        break;
    }

    data_set($col1, 'docno.action', 'lookupconsumtionno');
    data_set($col1, 'dateid.label', 'Balance as of');
    data_set($col1, 'dateid.readonly', false);

    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['attention', 'certifby', 'print'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'attention.readonly', false);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
    'PDFM' as print,
    left(now(),10) as dateid,
    '' as docno,
    '' as client,
    '' as clientname,
    0 as clientid,
    '' as dclientname,
    '' as project,
    '' as projectname,
    0 as trno,
    '' as attention,
    '' as certifby";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $printtype = $config['params']['dataparams']['print'];

    switch ($printtype) {
      case 'default':
        return $this->report_aquamax_layout($config);
        break;
      case 'PDFM':
        return $this->report_aquamaxPDF_layout($config);
        break;
      default:
        return $this->reportDefaultLayout($config);
        break;
    }
  }
  public function aquamax_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";


    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  public function report_aquamax_layout($config)
  {
    //oks
    $data = json_decode(json_encode($this->reportDefault($config)), true);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $project = $config['params']['dataparams']['project'];
    $projectname  = $config['params']['dataparams']['projectname'];

    $query = "select name, accountno,email from center";
    $companyinfo = $this->coreFunctions->opentable($query);
    $certifby  = $config['params']['dataparams']['certifby'];
    $attention  = $config['params']['dataparams']['attention'];
    $client    = $config['params']['dataparams']['client'];
    $clientname    = $config['params']['dataparams']['clientname'];
    $project = $config['params']['dataparams']['project'];
    $projectname  = $config['params']['dataparams']['projectname'];
    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = $this->reportParams['layoutSize'];
    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $filterproject = "";

    $str .= $this->reporter->beginreport($layoutsize);
    $customer = '';
    $customersub = '';
    $totalbal = 0;

    $str .= $this->aquamax_header($config);
    for ($i = 0; $i < count($data); $i++) {
      if ($data[$i]['name'] != '') {
        $projectname  = $data[$i]['name'];
      } else {
        $projectname = '';
      }
      $paymentamt = $data[$i]['db'] - $data[$i]['bal'];
      if ($customer == '') {

        header:
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ' . $data[$i]['clientname'], '75px', null, false, $border, 'TL', 'L', $font, '10', 'B');
        $str .= $this->reporter->col('PROJECT: ' . (!empty($projectname) ? $projectname : $filterproject), null, null, false, $border, 'TR', 'C', $font, '10', 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS    : ' . $data[$i]['addr'], null, null, false, $border, 'L', 'L', $font, '10', 'B');
        $str .= $this->reporter->col('', null, null, false, $border, 'R', 'R', $font, '10', 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LB', 'L', $font, '10', 'B');
        $str .= $this->reporter->col('', null, null, false, $border, 'RB', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT DATE', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DOCUMENT NO.', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('METER NO.', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('PRESENT READING', '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('PREVIOUS READING', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('CONSUMPTION', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('RATE', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('BALANCE DUE', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        startdata:
        $meterbal = 0;
        if ($data[$i]['alias'] == 'ARSC' || $data[$i]['doc'] == 'AR' || $data[$i]['doc'] == 'CR') {

          $meterno = '';
          if ($data[$i]['alias'] == 'ARSC') {
            $meterno = 'Surcharge';
          }
          $dateid = $data[$i]['dateid'];
          $docno = $data[$i]['docno'];
          $meter = $meterno;
          $rem = trim($data[$i]['rem']);
          $presread = '';
          $pastread = '';
          $consump = '';
          $rate = '';
          $meterbal = $data[$i]['bal'];
          $bal = number_format($data[$i]['bal'], 2);


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('' . (isset($dateid) ? $dateid : ''), '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
          $str .= $this->reporter->col('' . (isset($docno) ? $docno : ''), '150', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
          $str .= $this->reporter->col('' . (isset($meter) ? $meter : ''), '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
          $str .= $this->reporter->col('' . (isset($rem) ? $rem : ''), '150', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
          $str .= $this->reporter->col($bal, '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $totalbal += $meterbal;
        } else {
          $stock = $this->coreFunctions->opentable("select i.barcode as meter, s.isqty3 as presread, s.isqty2 as pastread, s.isqty as consumption, s.ext from glstock as s left join item as i on i.itemid=s.itemid where s.suppid=" . $data[$i]['clientid'] . " and s.trno=" . $data[$i]['trno'] . " order by s.line");
          for ($j = 0; $j < count($stock); $j++) {

            if ($paymentamt >= $stock[$j]->ext) {
              $paymentamt -= $stock[$j]->ext;
              continue;
            }
            $meterno = $stock[$j]->meter;
            if ($data[$i]['alias'] == 'ARSC') {
              $meterno = 'Surcharge';
            }
            $dateid = $data[$i]['dateid'];
            $docno = $data[$i]['docno'];
            $rem = trim($data[$i]['rem']);
            $meter = $meterno;
            $presread = number_format($stock[$j]->presread, 2);
            $pastread = number_format($stock[$j]->pastread, 2);
            $consump = number_format($stock[$j]->consumption, 2);
            $rate = number_format($data[$i]['rate'], 2);

            if ($paymentamt == 0) {
              $meterbal = $stock[$j]->ext;
            } else {
              $meterbal = $data[$i]['bal'];
            }

            $bal = number_format($meterbal, 2);
            $str .= $this->reporter->begintable('1000');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('' . (isset($dateid) ? $dateid : ''), '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
            $str .= $this->reporter->col('' . (isset($docno) ? $docno : ''), '150', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
            $str .= $this->reporter->col('' . (isset($meter) ? $meter : ''), '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
            $str .= $this->reporter->col('' . (isset($rem) ? $rem : ''), '150', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
            if ($data[$i]['alias'] == 'ARSC' || $data[$i]['doc'] == 'AR') {
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
            } else {
              $str .= $this->reporter->col('' . (isset($presread) ? $presread : ''), '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
              $str .= $this->reporter->col('' . (isset($pastread) ? $pastread : ''), '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
              $str .= $this->reporter->col('' . (isset($consump) ? $consump : ''), '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
              $str .= $this->reporter->col('' . (isset($rate) ? $rate : ''), '100', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
            }
            $str .= $this->reporter->col($bal, '100', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalbal += $meterbal;
          }
        }
      } else {
        if ($customer == $data[$i]['clientname']) {
          goto startdata;
        } else {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '150', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '150', null, false, ' ', 'T', 'L', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TOTAL DUE:', '100', null, false, ' ', 'T', 'C', $font, $fontsize + 2, 'B');
          $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, ' ', 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $customersub = $data[$i]['clientname'];
          $totalbal = 0;
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT IS ALREADY PAID', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', null, null, false, $border, 'LR', '', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '250px', null, false, $border, 'L', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('For payment through bank deposits and online transfers:', '400px', null, false, $border, '', 'L', $font, '10', 'BI', '', '');
          $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('' . $companyinfo[0]->name, '400px', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('' . $companyinfo[0]->accountno, '400px', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('and email your transaction slep to ' . $companyinfo[0]->email, '400px', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', '400px', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px solid ', 'BLR', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CERTIFIED CORRECT:  ' . $certifby, null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->page_break();
          $str .= $this->aquamax_header($config);
          goto header;
        }
      }
      $customer = $data[$i]['clientname'];
    } //foreach end
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '150', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '150', null, false, ' ', 'T', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, ' ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('TOTAL DUE:', '100', null, false, ' ', 'T', 'C', $font, $fontsize + 2, 'B');
    $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, ' ', 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT IS ALREADY PAID', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, $border, 'LR', '', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '250px', null, false, $border, 'L', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('For payment through bank deposits and online transfers:', '400px', null, false, $border, '', 'L', $font, '12', 'BI', '', '');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('' . $companyinfo[0]->name, '400px', null, false, $border, '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('' . $companyinfo[0]->accountno, '400px', null, false, $border, '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('and email your transaction slep to ' . $companyinfo[0]->email, '400px', null, false, $border, '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250px', null, false, $border, 'L', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', '400px', null, false, $border, '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'R', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px solid ', 'BLR', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:  ' . $certifby, null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];

    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $clientname    = $config['params']['dataparams']['clientname'];
    $project = $config['params']['dataparams']['project'];
    $projectname  = $config['params']['dataparams']['projectname'];
    $filter = "";
    $pjtname = "";

    if ($clientname != '') {
      $filter .= " and client.client='$client'";
    }
    if ($projectname != '') {
      $pjtname .= " and pm.name = '$projectname'";
    }

    //2023.11.13 revised - FRED
    $query = "	select coa.alias, ar.trno, date(ar.dateid) as dateid, h.doc, ar.docno, client.addr,TRIM(LEADING '\n' FROM h.rem) as rem, client.clientid, client.clientname, ar.db, ar.cr, ifnull(if(ar.cr<>0,ar.bal*-1,ar.bal),0) as bal, ar.acnoid, pm.name, pm.rate
    from arledger as ar left join client on client.clientid=ar.clientid 
    left join coa on coa.acnoid=ar.acnoid left join glhead as h on h.trno=ar.trno
    left join projectmasterfile as pm on pm.line=h.projectid
    where ar.bal<>0 $filter $pjtname
    order by clientname, dateid";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config, $clientname, $addr)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $attention  = $config['params']['dataparams']['attention'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ' . $clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS    : ' . $addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT DATE', '75', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('DOCUMENT NO.', '75', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('METER NO.', '105', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('PRESENT READING', '60', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('PREVIOUS READING', '60', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('CONSUMPTION', '75', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('RATE', '75', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('BALANCE DUE', '75', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    return $str;
  }



  public function reportDefaultLayout($config)
  {

    $data = json_decode(json_encode($this->reportDefault($config)), true);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $certifby  = $config['params']['dataparams']['certifby'];

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = $this->reportParams['layoutSize'];

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $clientname = $data[0]['clientname'];
    $addr = $data[0]['addr'];

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
    $str .= $this->default_table_cols($layoutsize, $border, $font, $fontsize, $config, $clientname, $addr);
    $customer = '';
    $customersub = '';
    $balance = 0;

    for ($i = 0; $i < count($data); $i++) {
      $meterno = $data[$i]['meter'];
      if ($data[$i]['alias'] == 'ARSC') {
        $meterno = 'Surcharge';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['dateid'], '75', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['docno'], '75', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
      $str .= $this->reporter->col($meterno, '105', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['rem'], '150', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');

      if ($data[$i]['alias'] == 'ARSC' || $data[$i]['doc'] == 'AR') {
        $str .= $this->reporter->col('', '60', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
      } else {
        $str .= $this->reporter->col(number_format($data[$i]['presread'], 2), '60', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($data[$i]['pastread'], 2), '60', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($data[$i]['consumption'], 2), '75', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '75', null, false, '1px solid ', 'TBLR', 'C', $font, $fontsize, '');
      }


      $str .= $this->reporter->col(number_format($data[$i]['bal'], 2), '75', null, false, '1px solid ', 'TBLR', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $balance += $data[$i]['bal'];
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '106', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '150', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '60', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '60', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('TOTAL DUE', '75', null, false, '1px dotted ', 'T', 'C', $font, $fontsize , 'B');
    $str .= $this->reporter->col(number_format($balance, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }
  public function report_aquamaxPDF_header($config)
  {
    //oks
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $dataid = $config['params']['dataid'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
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
    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(720, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(720, 0, 'STATEMENT OF ACCOUNTS', '', 'C', false, 1, '', '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, 'For the Period Ending ' . date('M-d-Y', strtotime($asof)), '', 'C', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n");
  }
  public function emptydataAquamax($config)
  {
    $attention  = $config['params']['dataparams']['attention'];
    $projectname  = $config['params']['dataparams']['projectname'];
    $clientname    = $config['params']['dataparams']['clientname'];
    $font = '';
    $fontbold = '';
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
    PDF::SetFont($fontbold, '', 20);
    PDF::MultiCell(720, 20, 'NO TRANSACTION.', '', 'C', false, 1, '', '250');
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function report_aquamaxPDF_layout($config)
  {
    //oks
    $data = json_decode(json_encode($this->reportDefault($config)), true);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $project = $config['params']['dataparams']['project'];

    $query = "select name, accountno,email from center";
    $companyinfo = $this->coreFunctions->opentable($query);
    $certifby  = $config['params']['dataparams']['certifby'];
    $attention  = $config['params']['dataparams']['attention'];
    $client    = $config['params']['dataparams']['client'];
    $clientname    = $config['params']['dataparams']['clientname'];
    $project = $config['params']['dataparams']['project'];
    $projectname  = $config['params']['dataparams']['projectname'];


    $font = '';
    $fontbold = '';
    $fontsize = 11;
    $totalbal = 0;
    $customer = '';
    $customersub = '';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    if (empty($data)) {
      return $this->emptydataAquamax($config);
    }
    $this->report_aquamaxPDF_header($config);

    for ($i = 0; $i < count($data); $i++) {

      if ($data[$i]['name'] != '') {
        $projectname  = $data[$i]['name'];
      } else {
        $projectname = '';
      }

      $paymentamt = $data[$i]['db'] - $data[$i]['bal'];

      if ($customer == '') {

        header:
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(220, 15, 'CUSTOMER : ' . $data[$i]['clientname'], 'TL', 'L', false, 0, '', '');
        PDF::MultiCell(350, 15, 'PROJECT: ', 'T', 'R', false, 0, '', '');
        PDF::MultiCell(150, 15, '' . $projectname, 'TR', 'L', false, 1, '', '');
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(200, 15, 'ADDRESS : ' . $data[$i]['addr'], 'L', 'L', false, 0, '', '');
        PDF::MultiCell(350, 15, '', '', 'R', false, 0, '', '');
        PDF::MultiCell(170, 15, '', 'R', 'L', false, 1, '', '');
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(200, 15, 'ATTENTION : ' . $attention, 'BL', 'L', false, 0, '', '');
        PDF::MultiCell(350, 15, '', 'B', 'R', false, 0, '', '');
        PDF::MultiCell(170, 15, '', 'BR', 'L', false, 1, '', '');
        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(70, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(100, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(70, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(100, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(60, 0, '', 'LT', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'LTR', 'C', false, 1, '', '');

        PDF::SetFont($fontbold, '', 8);
        PDF::MultiCell(70, 20, 'DOCUMENT DATE', 'LR', 'C', false, 0, '', '');
        PDF::MultiCell(100, 20, 'DOCUMENT NO.', 'L', 'C', false, 0, '', '');
        PDF::MultiCell(70, 20, 'METER NO.', 'L', 'C', false, 0, '', '');
        PDF::MultiCell(100, 20, 'NOTES', 'L', 'C', false, 0, '', '');
        PDF::MultiCell(80, 20, 'PRESENT READING', 'L', 'C', false, 0, '', '');
        PDF::MultiCell(80, 20, 'PREVIOUS READING', 'L', 'C', false, 0, '', '');
        PDF::MultiCell(80, 20, 'CONSUMPTION', 'L', 'C', false, 0, '', '');
        PDF::MultiCell(60, 20, 'RATE', 'L', 'C', false, 0, '', '');
        PDF::MultiCell(80, 20, 'BALANCE DUE', 'RL', 'C', false, 1, '', '');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(70, 0, '', 'LRB', 'C', false, 0, '', '');
        PDF::MultiCell(100, 0, '', 'RB', 'C', false, 0, '', '');
        PDF::MultiCell(70, 0, '', 'RB', 'C', false, 0, '', '');
        PDF::MultiCell(100, 0, '', 'RB', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'RB', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'RB', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'RB', 'C', false, 0, '', '');
        PDF::MultiCell(60, 0, '', 'RB', 'C', false, 0, '', '');
        PDF::MultiCell(80, 0, '', 'RB', 'C', false, 1, '', '');

        PrintDetailHere:
        $meterbal = 0;

        if ($data[$i]['alias'] == 'ARSC' || $data[$i]['doc'] == 'AR' || $data[$i]['doc'] == 'CR') {
          $maxrow = 1;
          $meterno = '';
          if ($data[$i]['alias'] == 'ARSC') {
            $meterno = 'Surcharge';
          }
          $dateid = $data[$i]['dateid'];
          $docno = $data[$i]['docno'];
          $meter = $meterno;
          $rem = trim($data[$i]['rem']);
          $presread = '';
          $pastread = '';
          $consump = '';
          $rate = '';
          $meterbal = $data[$i]['bal'];
          $bal = number_format($data[$i]['bal'], 2);

          $arr_dateid = $this->reporter->fixcolumn([$dateid], '10', 0);
          $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
          $arr_meter = $this->reporter->fixcolumn([$meter], '13', 0);
          $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
          $arr_presread = $this->reporter->fixcolumn([$presread], '13', 0);
          $arr_pastread = $this->reporter->fixcolumn([$pastread], '13', 0);
          $arr_consump = $this->reporter->fixcolumn([$consump], '13', 0);
          $arr_rate = $this->reporter->fixcolumn([$rate], '13', 0);
          $arr_bal = $this->reporter->fixcolumn([$bal], '13', 0);

          $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_docno, $arr_meter, $arr_rem, $arr_presread, $arr_pastread, $arr_consump, $arr_rate, $arr_bal]);
          for ($r = 0; $r < $maxrow; $r++) {

            if (isset($arr_rem[$r])) {

              if ($arr_rem[$r] == '' && $r > 0) {
                continue;
              }
            }
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(70, 15, '' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(100, 15, '' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(70, 15, (isset($arr_meter[$r]) ? $arr_meter[$r] : ''), 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(100, 15, '' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(80, 15, '', 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(80, 15, '', 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(80, 15, '', 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(60, 15, '', 'LB', 'C', false, 0, '', '');
            PDF::MultiCell(80, 15, '' . (isset($arr_bal[$r]) ? $arr_bal[$r] : ''), 'LRB', 'R', false, 1, '', '');

            $totalbal += $meterbal;
          }
        } else {
          $stock = $this->coreFunctions->opentable("select i.barcode as meter, s.isqty3 as presread, s.isqty2 as pastread, s.isqty as consumption, s.ext from glstock as s left join item as i on i.itemid=s.itemid where s.suppid=" . $data[$i]['clientid'] . " and s.trno=" . $data[$i]['trno'] . " order by s.line");
          for ($j = 0; $j < count($stock); $j++) {

            if ($paymentamt >= $stock[$j]->ext) {
              $paymentamt -= $stock[$j]->ext;
              continue;
            }

            $maxrow = 1;

            $meterno = $stock[$j]->meter;
            if ($data[$i]['alias'] == 'ARSC') {
              $meterno = 'Surcharge';
            }
            $dateid = $data[$i]['dateid'];
            $docno = $data[$i]['docno'];
            $meter = $meterno;
            $rem = trim($data[$i]['rem']);
            $presread = number_format($stock[$j]->presread, 2);
            $pastread = number_format($stock[$j]->pastread, 2);
            $consump = number_format($stock[$j]->consumption, 2);
            $rate = number_format($data[$i]['rate'], 2);

            if ($paymentamt == 0) {
              $meterbal = $stock[$j]->ext;
            } else {
              $meterbal = $data[$i]['bal'];
            }
            $bal = number_format($meterbal, 2);

            $arr_dateid = $this->reporter->fixcolumn([$dateid], '10', 0);
            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_meter = $this->reporter->fixcolumn([$meter], '13', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_presread = $this->reporter->fixcolumn([$presread], '13', 0);
            $arr_pastread = $this->reporter->fixcolumn([$pastread], '13', 0);
            $arr_consump = $this->reporter->fixcolumn([$consump], '13', 0);
            $arr_rate = $this->reporter->fixcolumn([$rate], '13', 0);
            $arr_bal = $this->reporter->fixcolumn([$bal], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_docno, $arr_meter, $arr_rem, $arr_presread, $arr_pastread, $arr_consump, $arr_rate, $arr_bal]);
            for ($r = 0; $r < $maxrow; $r++) {

              if (isset($arr_rem[$r])) {

                if ($arr_rem[$r] == '' && $r > 0) {
                  continue;
                }
              }
              if ($maxrow > 1 && $r == 0) {
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(70, 15, '' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), 'L', 'C', false, 0, '', '');
                PDF::MultiCell(100, 15, '' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), 'L', 'C', false, 0, '', '');
                PDF::MultiCell(70, 15, (isset($arr_meter[$r]) ? $arr_meter[$r] : ''), 'L', 'C', false, 0, '', '');
                PDF::MultiCell(100, 15, '' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'L', 'C', false, 0, '', '');

                if ($data[$i]['alias'] == 'ARSC' || $data[$i]['doc'] == 'AR') {
                  PDF::MultiCell(80, 15, '', 'L', 'C', false, 0, '', '');
                  PDF::MultiCell(80, 15, '', 'L', 'C', false, 0, '', '');
                  PDF::MultiCell(80, 15, '', 'L', 'C', false, 0, '', '');
                  PDF::MultiCell(60, 15, '', 'L', 'C', false, 0, '', '');
                } else {
                  PDF::MultiCell(80, 15, '' . (isset($arr_presread[$r]) ? $arr_presread[$r] : ''), 'L', 'R', false, 0, '', '');
                  PDF::MultiCell(80, 15, '' . (isset($arr_pastread[$r]) ? $arr_pastread[$r] : ''), 'L', 'R', false, 0, '', '');
                  PDF::MultiCell(80, 15, '' . (isset($arr_consump[$r]) ? $arr_consump[$r] : ''), 'L', 'R', false, 0, '', '');
                  PDF::MultiCell(60, 15, '' . (isset($arr_rate[$r]) ? $arr_rate[$r] : ''), 'L', 'C', false, 0, '', '');
                }
                PDF::MultiCell(80, 0, '' . (isset($arr_bal[$r]) ? $arr_bal[$r] : ''), 'RL', 'R', false, 1, '', '');
              } else {
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(70, 15, '' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), 'LB', 'C', false, 0, '', '');
                PDF::MultiCell(100, 15, '' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), 'LB', 'C', false, 0, '', '');
                PDF::MultiCell(70, 15, (isset($arr_meter[$r]) ? $arr_meter[$r] : ''), 'LB', 'C', false, 0, '', '');
                PDF::MultiCell(100, 15, '' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'LB', 'C', false, 0, '', '');

                if ($data[$i]['alias'] == 'ARSC' || $data[$i]['doc'] == 'AR') {
                  PDF::MultiCell(80, 15, '', 'LB', 'C', false, 0, '', '');
                  PDF::MultiCell(80, 15, '', 'LB', 'C', false, 0, '', '');
                  PDF::MultiCell(80, 15, '', 'LB', 'C', false, 0, '', '');
                  PDF::MultiCell(60, 15, '', 'LB', 'C', false, 0, '', '');
                } else {
                  PDF::MultiCell(80, 15, '' . (isset($arr_presread[$r]) ? $arr_presread[$r] : ''), 'LB', 'R', false, 0, '', '');
                  PDF::MultiCell(80, 15, '' . (isset($arr_pastread[$r]) ? $arr_pastread[$r] : ''), 'LB', 'R', false, 0, '', '');
                  PDF::MultiCell(80, 15, '' . (isset($arr_consump[$r]) ? $arr_consump[$r] : ''), 'LB', 'R', false, 0, '', '');
                  PDF::MultiCell(60, 15, '' . (isset($arr_rate[$r]) ? $arr_rate[$r] : ''), 'LB', 'C', false, 0, '', '');
                }
                PDF::MultiCell(80, 15, '' . (isset($arr_bal[$r]) ? $arr_bal[$r] : ''), 'LRB', 'R', false, 1, '', '');
              }
            }

            $totalbal += $meterbal;

            if (PDF::getY() > 900) {
              $this->othersClass->logConsole('new page');
              $this->report_aquamaxPDF_header($config);
              goto header;
            }
          } // end stock loop
        }
      } else {
        if ($customer == $data[$i]['clientname']) {
          goto PrintDetailHere;
        } else {

          PDF::SetFont($fontbold, '', 9);
          PDF::MultiCell(570, 0, 'TOTAL DUE: ', '', 'R', false, 0, '', '');
          PDF::MultiCell(150, 0, '' . number_format($totalbal, 2), '', 'R', false, 1, '', '');
          PDF::MultiCell(0, 0, "\n\n\n");
          PDF::SetFont($fontbold, '', 11);
          PDF::MultiCell(720, 0, 'PLEASE DISREGARD STATEMENT IF ALREADY PAID', 'LTR', 'C', false, 1, '', '');
          PDF::MultiCell(720, 0, '', 'LRB', 'C', false, 1, '', '');
          PDF::SetFont($font, '', 3);
          PDF::MultiCell(720, 0, '', 'LR', 'C', false, 1, '', '');
          PDF::SetFont($font, '', 9);
          $customersub = $data[$i]['clientname'];
          $totalbal = 0;
          PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
          PDF::MultiCell(420, 0, 'For payment through bank deposits and online transfers:', '', 'L', false, 0, '', '');
          PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');
          PDF::SetFont($fontbold, '', 9);
          PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
          PDF::MultiCell(420, 0, $companyinfo[0]->name, '', 'L', false, 0, '', '');
          PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');
          PDF::SetFont($font, '', 9);
          PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
          PDF::MultiCell(420, 0, $companyinfo[0]->accountno, '', 'L', false, 0, '', '');
          PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');

          PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
          PDF::MultiCell(420, 0, 'and email your transaction slep to ' . $companyinfo[0]->email, '', 'L', false, 0, '', '');
          PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');

          PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
          PDF::MultiCell(460, 0, 'Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', '', 'L', false, 0, '', '');
          PDF::MultiCell(130, 0, '', 'R', '', false, 1, '', '');

          PDF::SetFont($fontbold, '', 11);
          PDF::MultiCell(720, 0, '', 'LBR', 'C', false, 1, '', '');
          PDF::MultiCell(0, 0, "\n\n");
          PDF::SetFont($font, '', 9);
          PDF::MultiCell(95, 0, 'CERTIFIED CORRECT: ', '', '', false, 0, '', '');
          PDF::MultiCell(110, 0, '' . $certifby, '', '', false, 0, '', '');
          PDF::MultiCell(260, 0, '', '', '', false, 0, '', '');
          PDF::MultiCell(80, 0, 'RECIEVED BY: ', '', '', false, 0, '', '');
          PDF::MultiCell(175, 0, '', '', '', false, 1, '', '');

          $this->report_aquamaxPDF_header($config);
          goto header;
        }
      }

      $customer = $data[$i]['clientname'];
    }

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(570, 0, 'TOTAL DUE: ', '', 'R', false, 0, '', '');
    PDF::MultiCell(150, 0, '' . number_format($totalbal, 2), '', 'R', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(720, 0, 'PLEASE DISREGARD STATEMENT IF ALREADY PAID', 'LTR', 'C', false, 1, '', '');
    PDF::MultiCell(720, 0, '', 'LRB', 'C', false, 1, '', '');
    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', 'LR', 'C', false, 1, '', '');
    PDF::SetFont($font, '', 9);

    PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
    PDF::MultiCell(420, 0, 'For payment through bank deposits and online transfers:', '', 'L', false, 0, '', '');
    PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
    PDF::MultiCell(420, 0, $companyinfo[0]->name, '', 'L', false, 0, '', '');
    PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
    PDF::MultiCell(420, 0, $companyinfo[0]->accountno, '', 'L', false, 0, '', '');
    PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');

    PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
    PDF::MultiCell(420, 0, 'and email your transaction slep to ' . $companyinfo[0]->email, '', 'L', false, 0, '', '');
    PDF::MultiCell(170, 0, '', 'R', '', false, 1, '', '');

    PDF::MultiCell(130, 0, '', 'L', '', false, 0, '', '');
    PDF::MultiCell(460, 0, 'Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', '', 'L', false, 0, '', '');
    PDF::MultiCell(130, 0, '', 'R', '', false, 1, '', '');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(720, 0, '', 'LBR', 'C', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(95, 0, 'CERTIFIED CORRECT: ', '', '', false, 0, '', '');
    PDF::MultiCell(110, 0, '' . $certifby, '', '', false, 0, '', '');
    PDF::MultiCell(260, 0, '', '', '', false, 0, '', '');
    PDF::MultiCell(80, 0, 'RECIEVED BY: ', '', '', false, 0, '', '');
    PDF::MultiCell(175, 0, '', '', '', false, 1, '', '');
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}//end class