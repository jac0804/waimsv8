<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
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

class statement_of_account_email
{
  public $modulename = 'Statement of Accounts Email';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $showemailbtn = true;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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

    $fields = ['radiocustreporttypeafti', 'dclientname', 'attention', 'cc', 'interestrate', 'lblmessage', 'message', 'start', 'end', 'days'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'dclientname.required', true);
    data_set($col1, 'start.label', 'As of Date');
    data_set($col1, 'end.label', 'Due Date');
    data_set($col1, 'days.label', 'Days');
    data_set($col1, 'dclientname.addedparams', ['reporttype']);
    data_set($col1, 'attention.readonly', false);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    return $this->coreFunctions->opentable("select 
    'PDFM' as print,
    '' as client,
    '' as clientname,
    '' as dclientname,
    left(now(),10) as start,
    left(now(),10) as end,
    '' as days,
    'n1' as reporttype,
    '' as cc,
    '' as message,
    '' as attention,
    '' as interestrate
    ");
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
    $result = $this->reportDefault($config);
    return $this->report_soa_email($config, $result);
  }

  public function reportDefault($config)
  {

    $client     = $config['params']['dataparams']['client'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $asof     = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $due      = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $days       = $config['params']['dataparams']['days'];
    $filter     = "";

    $filter .= "and client.client='$client'";

    // Statement of Account to Customer : The uncollected CWT AR must be reflected in the SOA
    $query = "select coa.alias, head.trno, 'p' as tr, 1 as trsort, client.client, client.clientname,
    date(ar.dateid) as ardate,  ar.ref as applied, ar.db as debit,
    ar.cr as credit,case ar.cr when 0 then (ar.bal) else (ar.bal)*-1 end as balance, ag.client as agent, head.due,
    client.terms, client.tel, client.start as startdate, cp.email,
    concat(cp.fname,' ',cp.mname,' ',cp.lname) as attention, 
    case
    when head.doc = 'AR' then detail.poref
    else head.yourref
    end as yourref,
    concat(billadd.addrline1,' ',billadd.addrline2,' ',billadd.city,' ',billadd.province,' ',billadd.country,' ',billadd.zipcode) as addr,
    detail.rem as remarks, cp.contactno, head.dateid,

    case
    when head.doc = 'AR' then datediff(now(), detail.podate)
    when head.doc = 'CR' then datediff(now(), head.dateid)
    else datediff(now(), head.due)
    end as elapse,

    case 
    when left(ar.docno,2) = 'DR' then concat('SI',right(ar.docno,10))
    when left(ar.docno,2) = 'AR' then detail.rem
    else ar.docno
    end as invoiceno

    from glhead as head 
    left join arledger as ar on ar.trno=head.trno
    left join gldetail as detail on head.trno = detail.trno and ar.line = detail.line
    left join client on client.clientid=head.clientid
    left join billingaddr as billadd on billadd.line = client.billid
    left join contactperson cp on cp.line = client.billcontactid
    left join coa on coa.acnoid=ar.acnoid
    left join client as ag on ag.clientid=ar.agentid
    left join cntnum as num on num.trno = head.trno
    where left(coa.alias,2)='ar' and ar.bal<>0 
    and ifnull(client.client,'')<>'' and date(head.dateid)<='" . $asof . "'  " . $filter . "
    order by client, clientname, ardate ";

    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }

  public function report_soa_email($params, $data)
  {
    $this->othersClass->setDefaultTimeZone();
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $asof     = date('Y-m-d', strtotime($params['params']['dataparams']['start']));
    $attention     = $params['params']['dataparams']['attention'];
    $qry = "select name,concat(address,' ',zipcode,'<br>','Tel nos: ',tel,'<br>','E-mail: ',email,'<br>','<b>VAT REG TIN: ',tin,'</b>') as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $companyid = $params['params']['companyid'];
    $decimalcurr = 2; //$this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $count = $page = 900;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;

    $font = 'helvetica';
    $peso = "<span>&#8369;</span>";
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    $fontsize9 = "9";
    $fontsize10 = "10";
    $fontsize11 = "10";
    $fontsize12 = "10";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

    PDF::MultiCell(0, 0, "\n", 0, 'L');
    PDF::MultiCell(0, 0, date('m/d/y h:i A'), 0, 'L');
    PDF::MultiCell(0, 0, "\n", 0, 'L');

    PDF::Image('public/images/afti/qslogo.png', '', '', 330, 80);
    PDF::MultiCell(500, 0, '', 0, 'L', 0, 0, '', '', false, 0, false, false, 0);
    PDF::SetFont($font, 'B', 17);
    PDF::MultiCell(260, 0, '', 0, 'C', 0, 1, '', '', false, 0, false, false, 0);

    PDF::MultiCell(0, 30, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['name']) ? $headerdata[0]['name'] : ''), 0, 'L', false, 1, '', '', true, 0, true);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['address']) ? $headerdata[0]['address'] : ''), 0, 'L', false, 1, '', '', true, 0, true);

    // statement of account email
    PDF::SetFont($font, 'B', $fontsize14);
    PDF::MultiCell(0, 30, "\n");
    PDF::MultiCell(760, 0, ' STATEMENT OF ACCOUNT ', 0, 'C', false);

    // border buttom
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150)));
    PDF::MultiCell(410, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, "", 0, 'L', false, 0);
    PDF::MultiCell(300, 0, "", 'B', 'L', false, 1);

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(211, 211, 211)));
    PDF::SetFillColor(211, 211, 211);
    PDF::SetFont($font, 'B', $fontsize12);
    // $w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false
    PDF::MultiCell(160, 30, '  ' . 'CUSTOMER NAME: ', 'LR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(250, 30, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'LR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 30, ' ', '', 'LR', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(150, 30, ' DATE: ', 'LR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(150, 30, '  ' . $asof, 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

    // second col
    PDF::SetFillColor(150, 150, 150);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(160, 100, ' ADDRESS: ', 'TLRB', 'L', false, 0, '', '', true, 1);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(250, 100, '  ' . (isset($data[0]['addr']) ? $data[0]['addr'] : ''), 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(50, 100, ' ', '', 'LR', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(150, 20, ' TERMS: ', 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(150, 20, '  ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    // 3rd col
    PDF::MultiCell(0, 100, "");
    PDF::SetFillColor(211, 211, 211);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(160, 20, ' CONTACT NUMBER: ', 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(250, 20, '  ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'TLRB', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFillColor(211, 211, 211);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(160, 20, ' ATTENTION: ', 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(250, 20, '  ' . (isset($attention) ? $attention : ''), 'TLRB', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
    // end header
    // start data
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100)));
    PDF::MultiCell(760, 0, "", 'B', 'L', false, 1);
    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(211, 211, 211)));
    PDF::MultiCell(135, 20, ' Invoice Date ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 20, ' Invoice No ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 20, ' PO No. ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 20, ' Amount ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 20, ' Payment ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 20, ' Balance ', 'LRB', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    // $peso = TCPDF_FONTS::unichr(8369); //php
    $total = 0;
    // $peso = 'PHP ';
    $interestrate = $params['params']['dataparams']['interestrate'] != "" ? $params['params']['dataparams']['interestrate'] / 100 : 0;
    $interest = 0;
    $totalamt_due = 0;

    $total = 0;
    $totala = 0;
    $totalb = 0;
    $totalc = 0;
    $totald = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        PDF::SetFont($font, 'R', $fontsize11);

        $balance = $data[$i]['balance'];
        $debit =  $data[$i]['debit'];
        $credit = $data[$i]['credit'];

        if ($data[$i]['alias'] == 'AR5') {
          $amount = 0;
          $payment = $credit - $debit;
          $bal = $balance;
        } else {
          $amount = $debit - $credit;
          $payment = $amount - $balance;
          $bal = $balance;
        }

        PDF::MultiCell(135, 25, ' ' . $data[$i]['ardate'], 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(125, 25, ' ' . $data[$i]['invoiceno'], 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(125, 25, ' ' . $data[$i]['yourref'], 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont('dejavusans', 'R', $fontsize11);
        PDF::MultiCell(15, 25, ' ' . $peso, 'B', 'R', false, 0, '', '', false, 1, true);

        PDF::SetFont($font, 'R', $fontsize11);
        PDF::MultiCell(110, 25, ' ' . number_format($amount, $decimalcurr), 'RB', 'R', false, 0, '', '', true, 1, false, true, 0, 'M', true);

        PDF::SetFont('dejavusans', 'R', $fontsize11);
        PDF::MultiCell(15, 25, ' ' . $peso, 'B', 'R', false, 0, '', '', false, 1, true);

        PDF::SetFont($font, 'R', $fontsize11);
        PDF::MultiCell(110, 25, ' ' . number_format($payment, $decimalcurr), 'RB', 'R', false, 0, '', '', true, 1, false, true, 0, 'M', true);

        PDF::SetFont('dejavusans', 'R', $fontsize11);
        PDF::MultiCell(15, 25, ' ' . $peso, 'B', 'R', false, 0, '', '', false, 1, true);

        PDF::SetFont($font, 'R', $fontsize11);
        PDF::MultiCell(110, 25, ' ' . number_format($bal, $decimalcurr), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
        // $total += $data[$i]['balance'];
        $payment = ($data[$i]['debit'] - $data[$i]['credit']) - $data[$i]['balance'];
        $total += ($data[$i]['debit'] - $data[$i]['credit']) - $payment;

        if ($data[$i]['elapse'] <= 30) {
          $totala += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
          $totalb += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
          $totalc += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 91) {
          $totald += $data[$i]['balance'];
        }

        $date1 = date('Y-m-d');
        $date2 = date('Y-m-d', strtotime($data[$i]['ardate']));
        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year1 - $year2) * 12) + ($month1 - $month2);

        // Interest Computation = Invoice amount x 1% x no. of months overdue
        $interest += $data[$i]['balance'] * $interestrate * $diff;
        $totalamt_due += $data[$i]['balance'];
      }
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(260, 25, ' Current', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' 30 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' 60 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' 90 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' Amount Due', 'LRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(245, 25, number_format($totala, $decimalcurr), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($totalb, $decimalcurr), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($totalc, $decimalcurr), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($totald, $decimalcurr), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($total, $decimalcurr), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    $total = 0;
    $totala = 0;
    $totalb = 0;
    $totalc = 0;
    $totald = 0;

    if ($params['params']['dataparams']['interestrate'] != "") {
      PDF::SetFont($font, 'B', $fontsize12);
      PDF::MultiCell(260, 20, 'Interest', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, '', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, 'PHP ' . number_format($interest, $decimalcurr), 'LRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

      PDF::MultiCell(260, 20, 'Total Amount Due', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, '', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 20, 'PHP ' . number_format($totalamt_due + $interest, $decimalcurr), 'LRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
    }


    do {
      PDF::MultiCell(0, 0, "\n");
    } while (PDF::getY() < 680);


    PDF::MultiCell(0, 30, "\n");
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(500, 15, 'For Inquiries :', 0, 'L', false);

    $userinfo = $this->getuserinfo($params);

    PDF::MultiCell(0, 5, "\n");
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(120, 15, 'Collection Officer : ', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(500, 15, ' ' . (isset($userinfo[0]->clientname) ? $userinfo[0]->clientname : ''), 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(120, 15, 'Contact Number : ', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(500, 15, '' . (isset($userinfo[0]->tel2) ? $userinfo[0]->tel2 : ''), 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(120, 15, 'Email Address : ', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(500, 15, '' . (isset($userinfo[0]->email) ? $userinfo[0]->email : ''), 0, 'L', false, 1);

    PDF::MultiCell(0, 5, "\n");
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(500, 15, 'Collection Department', 0, 'L', false);
    PDF::SetFont($font, '', $fontsize11);

    PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['name']) ? $headerdata[0]['name'] : ''), 0, 'L', false, 1, '', '', true, 0, true);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['address']) ? $headerdata[0]['address'] : ''), 0, 'L', false, 1, '', '', true, 0, true);

    PDF::MultiCell(0, 0, "");
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(0, 15, "");
    PDF::MultiCell(760, 15, 'This is computer generated, No signature required', 0, 'C', false);

    // &#8369  -- Peso Sign not working
    $pdf = PDF::Output($this->modulename . '.pdf', 'S');
    return $pdf;
  }

  public function sendemail($params)
  {
    $dataparams =  json_decode(json_encode(json_decode($params['params']['dataparams'])), true);
    $userinfo = $this->getuserinfo($params);

    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    $client     = $dataparams['client'];
    $reporttype = $dataparams['reporttype'];
    $asof     = date('Y-m-d', strtotime($dataparams['start']));
    $due      = date('Y-m-d', strtotime($dataparams['end']));
    $days       = $dataparams['days'];
    $filter     = "";

    $filter .= "and client.client='$client'";
    $query = "select coa.alias, head.trno, 'p' as tr, 1 as trsort, client.client, client.clientname,
    date(ar.dateid) as ardate,  ar.ref as applied, ar.db as debit,
    ar.cr as credit,case ar.cr when 0 then (ar.bal) else (ar.bal)*-1 end as balance, ag.client as agent, head.due,
    client.terms, client.tel, client.start as startdate, cp.email,
    concat(cp.fname,' ',cp.mname,' ',cp.lname) as attention, 
    case
    when head.doc = 'AR' then detail.poref
    else head.yourref
    end as yourref,
    concat(billadd.addrline1,' ',billadd.addrline2,' ',billadd.city,' ',billadd.province,' ',billadd.country,' ',billadd.zipcode) as addr,
    detail.rem as remarks, cp.contactno, head.dateid,

    case
    when head.doc = 'AR' then datediff(now(), detail.podate)
    when head.doc = 'CR' then datediff(now(), head.dateid)
    else datediff(now(), head.due)
    end as elapse,

    case 
    when left(ar.docno,2) = 'DR' then concat('SI',right(ar.docno,10))
    when left(ar.docno,2) = 'AR' then detail.rem
    else ar.docno
    end as invoiceno

    from glhead as head 
    left join arledger as ar on ar.trno=head.trno
    left join gldetail as detail on head.trno = detail.trno and ar.line = detail.line
    left join client on client.clientid=head.clientid
    left join billingaddr as billadd on billadd.line = client.billid
    left join contactperson cp on cp.line = client.billcontactid
    left join coa on coa.acnoid=ar.acnoid
    left join client as ag on ag.clientid=ar.agentid
    left join cntnum as num on num.trno = head.trno
    where left(coa.alias,2)='ar' and ar.bal<>0 
    and ifnull(client.client,'')<>'' and date(head.dateid)<='" . $asof . "'  " . $filter . "
    order by client, clientname, ardate 

    ";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;
    $total = 0;
    $peso = 'PHP ';
    $interestrate = $dataparams['interestrate'] != "" ? $dataparams['interestrate'] / 100 : 0;
    $interest = 0;
    $totalamt_due = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $payment = $data[$i]['debit'] - $data[$i]['balance'];
        $total += $data[$i]['balance'];
        $date1 = date('Y-m-d');
        $date2 = date('Y-m-d', strtotime($data[$i]['ardate']));

        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year1 - $year2) * 12) + ($month1 - $month2);

        // Interest Computation = Invoice amount x 1% x no. of months overdue
        $interest += $data[$i]['balance'] * $interestrate * $diff;
        $totalamt_due += $data[$i]['balance'];
      }
    }

    $info = [];

    $info['companyid'] = $params['params']['companyid'];

    $amount = number_format($total, $decimalprice);
    $reporttype = $dataparams['reporttype'];
    $asof       = date('F d, Y', strtotime($dataparams['start']));
    $due        = date('F d, Y', strtotime($dataparams['end']));
    $days       = $dataparams['days'];
    $cc         = $dataparams['cc'];
    $message    = $dataparams['message'];
    $interestrate = $dataparams['interestrate'] != "" ? $dataparams['interestrate'] / 100 : 0;


    $str = "";
    // <- interest rate 
    // $amt =  $interestrate / $data[$i]['debit'] * 100;
    $interest = 0;
    $totalamt_due = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $balance = $data[$i]['balance'];
        $debit =  $data[$i]['debit'];
        $credit = $data[$i]['credit'];

        if ($data[$i]['alias'] == 'AR5') {
          $amount = 0;
          $payment = $credit - $debit;
          $bal = $balance;
        } else {
          $amount = $debit - $credit;
          $payment = $amount - $balance;
          $bal = $balance;
        }

        $str .= "
            <tr>
                <td>" . $data[$i]['clientname'] . "</td>
                <td>" . $data[$i]['ardate'] . "</td>
                <td>" . $data[$i]['remarks'] . "</td>
                <td>" . $data[$i]['yourref'] . "</td>
                <td align='right'>" . $peso . number_format($bal, $decimalcurr) . "</td>
                <td>" . date('Y-m-d', strtotime($data[$i]['ardate'])) . "</td>
            </tr>";

        $date1 = date('Y-m-d');
        $date2 = date('Y-m-d', strtotime($data[$i]['ardate']));
        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year1 - $year2) * 12) + ($month1 - $month2);

        // Interest Computation = Invoice amount x 1% x no. of months overdue
        $interest += $data[$i]['balance'] * $interestrate * $diff;
        $totalamt_due += $data[$i]['balance'];
      }
      if ($dataparams['interestrate'] != "") {
        $str .= "
            <tr>
              <td>Interest</td>
              <td></td>
              <td></td>
              <td></td>
              <td align='right'>" . $peso . number_format($interest, $decimalcurr) . "</td>
              <td></td>
            </tr>";
      }
    }

    // add message concatation on existing

    switch ($reporttype) {
      case 'n1': // 1st notice
        $info['subject'] = 'Invoice ';
        $info['title'] = 'Statement of Account';
        $info['view'] = 'emails.firstnotice';
        $info['msg'] = '
                
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    table {
                    font-family: Arial, Helvetica, sans-serif;
                    border-collapse: collapse;
                    width: 100%;
                    }

                    table td, table th {
                    border: 1px solid #ddd;
                    padding: 8px;
                    }

                    table tr:nth-child(even){background-color: #f2f2f2;}

                    table tr:hover {background-color: #ddd;}

                    table th {
                    padding-top: 12px;
                    padding-bottom: 12px;
                    text-align: left;
                    background-color: white;
                    color: black;
                    }
                </style>
            </head>
            <body>
                <p>Good Day!</p>
                <p>This is friendly reminder that your account balance of <b>PHP ' . $amount . '</b> will due on <b>' . $due . '</b> 
                <br>Here\'s the details of your account/s: </p>
                <br>

                <table style="width:100%">
                    <tr>
                        <th>Company Name</th>
                        <th>Date</th>
                        <th>Invoice No.</th>
                        <th>Po No.</th>
                        <th>Invoice Amount</th>
                        <th>Due Date</th>
                    </tr>
                    ' . $str . '
                </table>
                <br>
                  <p>
                    ' . $message . '
                  </p>
                
                <p>We Would much appreciate if you could provide status of this payment<br>
                If you have any queries regarding this account, Please help to notify us at:</p>
                <br>
                <p>
                    Collection Department. <br>
                    Contact Person:       ' . (isset($userinfo[0]->clientname) ? $userinfo[0]->clientname : '') . '<br>
                    Contact Number:       ' . (isset($userinfo[0]->tel2) ? $userinfo[0]->tel2 : '') . '<br>
                    Email Address:        ' . (isset($userinfo[0]->email) ? $userinfo[0]->email : '') . ' 
                </p>
            </body>
            </html>    
        ';
        break;
      case 'n2': // 2nd notice
        $info['subject'] = 'Invoice ';
        $info['title'] = 'Statement of Account';
        $info['view'] = 'emails.firstnotice';
        $info['msg'] = '

        <!DOCTYPE html>
            <html>
            <head>
                <style>
                    table {
                    font-family: Arial, Helvetica, sans-serif;
                    border-collapse: collapse;
                    width: 100%;
                    }

                    table td, table th {
                    border: 1px solid #ddd;
                    padding: 8px;
                    }

                    table tr:nth-child(even){background-color: #f2f2f2;}

                    table tr:hover {background-color: #ddd;}

                    table th {
                    padding-top: 12px;
                    padding-bottom: 12px;
                    text-align: left;
                    background-color: white;
                    color: black;
                    }
                </style>
            </head>
            <body>

        <p>Good Day!</p>
        <p>Kindly advice us the payment schedule on the overdue invoice below</p>
        <br>
        <table style="width:100%">
            <tr>
                <th>Company Name</th>
                <th>Date</th>
                <th>Invoice No.</th>
                <th>Po No.</th>
                <th>Invoice Amount</th>
                <th>Due Date</th>
            </tr>
            ' . $str . '
        </table>
        <br>
          <p>
            ' . $message . '
          </p>
        
        <p>Your Prompt response is highly appreciated.<br>
        If you have any queries regarding this account, please help to notify us at.</p>
        <br>
        <p>
          Collection Department. <br>
          Contact Person:       ' . (isset($userinfo[0]->clientname) ? $userinfo[0]->clientname : '') . '<br>
          Contact Number:       ' . (isset($userinfo[0]->tel2) ? $userinfo[0]->tel2 : '') . '<br>
          Email Address:        ' . (isset($userinfo[0]->email) ? $userinfo[0]->email : '') . '
        </p>

        </body>
        </html>
        ';

        break;
      case 'n3': // 3rd notice
        $info['subject'] = 'PAST DUE NOTICE  ';
        $info['title'] = 'Statement of Account';
        $info['view'] = 'emails.firstnotice';
        $info['msg'] = '<p>This notice is to remind you that your account is still past due with<br>
        an amount of <b>PHP ' . $amount . '</b><br>
        On <b>' . $asof . '</b>, we sent you a statement of account thru email regarding your due payment on which we did<br>
        not receive any response from you. We ask you to submit your payment as early as possible.
        </p>
        <p>
          Failure to pay the due amount is a violation of our credit agreement<br>
          terms. We shall be compelled to suspend your account if we do not <br>
          receive the payment in the next <b>' . $days . '</b> days.
        </p>
        <br>
          <p>
            ' . $message . '
          </p>
        <br>';


        break;
    }


    if (isset($data[0]['email'])) {
      if ($data[0]['email'] != '') {
        $email = $data[0]['email'];
      } else {
        $email = 'noemail';
      }
    } else {
      $email = 'noemail';
    }

    $info['email'] = $email;
    $info['cc'] = $cc;
    $info['filename'] = $this->modulename;
    $info['name'] = (isset($userinfo[0]->clientname) ? $userinfo[0]->clientname : '');
    $info['pdf'] = $params['params']['pdf'];

    return $this->othersClass->sbcsendemail($params, $info);
  }



  private function getuserinfo($config)
  {
    $adminid = $config['params']['adminid'];
    $qry = "select clientname, tel2, email from client where clientid = ? ";
    return $this->coreFunctions->opentable($qry, [$adminid]);
  }
}//end class