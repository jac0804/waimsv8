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

class mc_unit_statement_of_account
{
  public $modulename = 'MC Unit Statement of Accounts';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  private $balance;
  private $acurrent;
  private $a30days;
  private $a60days;
  private $a90days;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->balance = 0;
    $this->acurrent = 0;
    $this->a30days = 0;
    $this->a60days = 0;
    $this->a90days = 0;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'docno', 'dateid', 'dclientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.action', 'lookupreportlist_docno');
    data_set($col1, 'docno.lookupclass', 'MJ');
    data_set($col1, 'docno.readonly', true);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);


    data_set($col1, 'dateid.label', 'Balance as of');
    data_set($col1, 'dateid.readonly', false);

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['radioreportcustomerfilter', 'attention', 'certifby', 'collector', 'prepared'];
    $col2 = $this->fieldClass->create($fields);



    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

    $paramstr = '';
    $username = $config['params']['user'];
    $type = 'PDFM';
    $username = '';
    $paramstr = "select 
    '" . $type . "' as print,
    left(now(),10) as dateid,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as attention,
    '' as docno,
    0 as trno,
    '" . $username . "' as certifby,
    '' as received,
    '' as collector,
    '' as prepared,
    '0' as customerfilter,
    '0' as reporttype";
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

    return $this->PDF_layout($config);
  }


  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $attention = $config['params']['dataparams']['attention'];
    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];
    $trnofilter = $config['params']['dataparams']['trno'];


    $filter = "";
    $filter1 = "";
    switch ($customerfilter) {
      case '0':
      case '2':
        if ($client != "") {
          $filter = "and client.client='$client'";
        }
        break;
      case '1':
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }
    if ($trnofilter != 0) {
      $filter .= " and head.trno = '$trnofilter' ";
    }

    $query = "select ar.trno,'p' as tr, 1 as trsort,
    client.clientid, client.client, client.clientname, client.addr,
    head.terms,date(ar.dateid) as docdate, ar.docno as refno, sum(ar.db) as debit, client.tel,
    sum(ar.cr) as credit, sum(ar.bal) as balance, ag.client as agent, ag.clientname as agentname,
    date(ar.dateid) as due,head.yourref, head.rem,proj.name as project , format(ifnull(hinfo.downpayment, 0), 2) as downpayment,
    format(ifnull(hinfo.penalty, 0), 2) as penalty, format(ifnull(hinfo.fma1, 0), 2) as amortization,
    format(ifnull(hinfo.rebate, 0), 2) as rebate,format(ifnull(hinfo.fma1+hinfo.rebate, 0), 2) as ma,
    sum(ar.bal)+ifnull(hinfo.rebate, 0) as balancewithreb,
    concat(ifnull(item.itemname, ''), ifnull(mm.model_name,'') , ifnull(brand.brand_desc,''), ifnull(sot.color, ''),
    ifnull(sot.serial, ''), ifnull(sot.chassis, ''))
    as model

    from glhead as head
    left join arledger as ar on ar.trno=head.trno
    left join glstock as stock on stock.trno = head.trno
    left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
    left join item on item.itemid = stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join client on client.clientid=head.clientid
    left join coa on coa.acnoid=ar.acnoid
    left join client as ag on ag.clientid=ar.agentid
    left join cntnum as num on num.trno = head.trno
    left join hcntnuminfo as hinfo on hinfo.trno = head.trno
    left join projectmasterfile as proj on proj.line=head.projectid
    where head.doc = 'MJ' and
    coa.alias in ('AR1','AR2')
    and num.center = '$center'
    and date(ar.dateid)<='$asof' and ar.bal<>0
    and ifnull(client.client,'')<>'' $filter
    
    group by 
    ar.trno,
    client.clientid,client.client, client.clientname, client.addr,
    head.terms,ar.dateid, ar.docno , client.tel,
    ag.client, ag.clientname,
    ar.dateid,head.yourref, head.rem,proj.name,hinfo.downpayment,hinfo.penalty,hinfo.fma1,hinfo.rebate,item.itemname,mm.model_name,brand.brand_desc,sot.color,sot.serial,sot.chassis
    ";



    return $this->coreFunctions->opentable($query);
  }

  public function PDF_layout($config)
  {
    $result = $this->reportDefault($config);
    $this->othersClass->setDefaultTimeZone();
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $attention  = $config['params']['dataparams']['attention'];
    $asof     = date('m/d/Y', strtotime($config['params']['dataparams']['dateid']));
    $asof2 = date_create($config['params']['dataparams']['dateid']);

    $qry = "select name,concat(address,' ',zipcode,'<br>','Tel nos: ',tel,'<br>','E-mail: ',email,'<br>','<b>VAT REG TIN: ',tin,'</b>') as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = 2;
    $count = $page = 900;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;

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

    PDF::MultiCell(0, 0, "\n");

    $fontsize9 = "9";
    $fontsize10 = "10";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";


    $customer = '';
    $daysdue = '';
    $totalcurrentdue = 0;
    $totalpenalty_months = [];
    $totalpenalty_amount = [];
    $totalpenalty_ma_with_rebate = [];
    $totalpenalty = [];
    $isfirstloop = 1;
    $rebate = 0;
    $prevday = '';
    $prevdue = '';
    $prevbalance = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        //print on change of client
        if ($customer != $data->clientname && $customer != '') {

          if ($totalcurrentdue != 0) {
            $isfirstloop = 1;

            $asof     = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
            $paymentdate =  '';
            $paymentd =  '';
            $paymentd = $this->get_paymentdate($data->trno, $data->docdate);
            $paymentd = isset($paymentd) ? $paymentd : '';
            if ($daysdue == '') //on first loop, initialize daysdue//skip on 2nd
            {
              $daysdue       = $data->due;
            }

            $paymentdate = $asof;
            if (isset($daysdue)) {
              if ($daysdue != '') {
                //previous daysdue used
                if (!isset($no_of_days->days)) {
                  $no_of_days = date_diff(date_create($daysdue), date_create($paymentdate));
                  //daysdue of current line loaded for next
                  $daysdue       = $data->due;

                  $comare_due = strtotime($daysdue);
                  $compare_pay = strtotime($paymentdate);

                  if ($comare_due > $compare_pay) {
                    $penalty = 0;
                  } else {
                    if ($prevbalance > 0) {
                      $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
                    } else {
                      $penalty = 0;
                    }
                  }
                }
              }
            }

            PDF::MultiCell(120, 0, '', 'TBLR', 'L', false, 0);
            PDF::MultiCell(100, 0, '', 'TBLR', 'R', false, 0);

            PDF::MultiCell(120, 0, $paymentdate, 'TBLR', 'R', false, 0);
            if ($penalty == 0) {
              PDF::MultiCell(100, 0, '-', 'TBLR', 'R', false, 0);
              PDF::MultiCell(140, 0, '-', 'TBLR', 'R', false, 0);
            } else {
              PDF::MultiCell(100, 0, $no_of_days->days, 'TBLR', 'R', false, 0);
              PDF::MultiCell(140, 0, number_format(isset($penalty) ? round($penalty, 1) : 0, 2), 'TBLR', 'R', false, 0);
            }

            $totalcurrentdue = $totalcurrentdue + (isset($penalty) ? $penalty : 0) + $rebate;


            PDF::MultiCell(140, 0, number_format(round($totalcurrentdue), 2), 'TBLR', 'R', false, 1);

            array_push($totalpenalty_amount, isset($penalty) ? $penalty : 0);


            $col = array('name' => $data->clientname, 'date' => $data->docdate, 'amount' => isset($penalty) ? $penalty : 0);
            array_push($totalpenalty, $col);

            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(120, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(120, 0, '.', '', 'L', false, 0);
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(140, 0, '', '', 'L', false, 0);
            PDF::MultiCell(140, 0, number_format(round($totalcurrentdue), 2), 'B', 'R', false, 1);

            PDF::MultiCell(0, 0, "\n\n\n");

            PDF::MultiCell(170, 0, 'Total Penalty', '', 'L', false, 0);
            PDF::MultiCell(150, 0, number_format(array_sum($totalpenalty_amount), 2), '', 'R', false, 0);
            PDF::MultiCell(400, 0, '', '', 'L', false, 1);


            $style = ['dash' => 2];
            PDF::SetLineStyle($style);
            foreach ($totalpenalty as $key => $value) {
              $penalty_date = date('j-M-y', strtotime($totalpenalty_months[$key]));
              $ma_with_rebate = $totalpenalty_ma_with_rebate[$key];

              if ($value['amount'] != 0) {
                PDF::MultiCell(170, 0, $penalty_date, '', 'L', false, 0);
                PDF::MultiCell(150, 0, number_format($ma_with_rebate, 2), 'B', 'R', false, 0);
                PDF::MultiCell(400, 0, '', '', 'L', false, 1);
              }
            }


            PDF::MultiCell(170, 0, 'Total Amount Due', '', 'L', false, 0);
            PDF::MultiCell(150, 0, number_format(round($totalcurrentdue), 2), 'B', 'R', false, 0);
            PDF::MultiCell(400, 0, '', '', 'L', false, 1);
          }

          //ending
          PDF::MultiCell(0, 0, "\n\n");

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(120, 0, 'Collection Officer: ', '', 'L', false, 0);
          PDF::MultiCell(150, 0, $config['params']['dataparams']['collector'], 'B', 'C', false, 0);
          PDF::MultiCell(450, 0, '', '', 'L', false, 1);

          PDF::MultiCell(120, 0, 'Date', '', 'L', false, 0);
          PDF::MultiCell(150, 0, date_format($asof2, 'd-M-y'), '', 'C', false, 0);
          PDF::MultiCell(450, 0, '', '', 'L', false, 1);

          $style = ['dash' => 0];
          PDF::SetLineStyle($style);

          PDF::MultiCell(0, 0, "\n");

          PDF::MultiCell(720, 0, 'If there is any discrepancies against our record please feel free to call, or text our Head Office Bookkeeping Department,', '', 'J', false, 1);
          PDF::MultiCell(720, 0, 'contact no.# 0917-794-6016 for your reconciliation of your account.', '', 'J', false, 1);

          PDF::MultiCell(0, 0, "\n");

          PDF::MultiCell(720, 0, 'Please disregard this statement if payment has been made. Thank You for doing your usual business transaction with us.', '', 'J', false, 1);


          PDF::MultiCell(0, 0, "\n\n");

          PDF::MultiCell(100, 0, 'Prepared by: ', '', 'L', false, 0);
          PDF::MultiCell(300, 0, '', '', 'L', false, 0);
          PDF::MultiCell(320, 0, 'Received by: ', '', 'L', false, 1);

          PDF::MultiCell(100, 0, '', '', 'L', false, 0);
          PDF::MultiCell(300, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
          PDF::MultiCell(320, 0, 'Signature Over Printed Name', '', 'L', false, 1);

          PDF::MultiCell(0, 0, "\n\n");

          PDF::MultiCell(100, 0, '', '', 'L', false, 0);
          PDF::MultiCell(300, 0, '', '', 'L', false, 0);
          PDF::MultiCell(320, 0, 'Date Received:', '', 'L', false, 1);
          //ending

          //new customer
          PDF::setPageUnit('px');
          PDF::AddPage('p', [800, 1000]);
          PDF::SetMargins(40, 40);

          PDF::MultiCell(0, 0, "\n");

          $rebate = 0;
          $totalcurrentdue = 0;
          $totalpenalty_months = [];
          $totalpenalty_amount = [];
          $totalpenalty_ma_with_rebate = [];
          $totalpenalty = [];

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'DATE : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, date_format($asof2, 'd-M-y'), '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'NAME : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, $data->clientname, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'ADDRESS    : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->addr, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'MODEL    : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->model, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'TERM    : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, $data->terms, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'D/P : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->downpayment, '', 'L', false, 1);

          $outbal = $this->get_outbal($center, $data->clientid);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'Outstanding Balance : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, number_format($outbal, 2), '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'M. A. : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->ma, '', 'L', false, 1);

          $due = $this->othersClass->getOrdinal(date("j", strtotime($data->due)));

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'Due Date: : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, $due, '', 'L', false, 1);

          $style = ['dash' => 2];
          PDF::SetLineStyle($style);
          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(220, 0, '', 'B', 'L', false, 0);
          PDF::MultiCell(500, 0, 'Interest & Over Due', 'TBLR', 'C', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(120, 0, '', 'RL', 'C', false, 0);
          PDF::MultiCell(100, 0, 'MONTHLY', 'R', 'C', false, 0);
          PDF::MultiCell(120, 0, 'PAYMENT', 'R', 'C', false, 0);
          PDF::MultiCell(100, 0, 'No. of', 'R', 'C', false, 0);
          PDF::MultiCell(140, 0, 'Penalty', 'R', 'C', false, 0);
          PDF::MultiCell(140, 0, '', 'R', 'C', false, 1);

          PDF::MultiCell(120, 0, 'Due Date', 'LBR', 'C', false, 0);
          PDF::MultiCell(100, 0, 'AMORTIZATION', 'BR', 'C', false, 0);
          PDF::MultiCell(120, 0, 'DATE.', 'BR', 'C', false, 0);
          PDF::MultiCell(100, 0, 'Days int.', 'BR', 'C', false, 0);
          PDF::MultiCell(140, 0, 'Due', 'BR', 'C', false, 0);
          PDF::MultiCell(140, 0, 'Current Due', 'BR', 'C', false, 1);
          $style = ['dash' => 0];
          PDF::SetLineStyle($style);

          $totalcurrentdue = $data->balance + $data->rebate;

          $prevday = '';
          $prevdue = '';
          $prevbalance =  0;

          $customer = $data->clientname;
        }
        //print on first loop
        if ($customer == '') {
          $rebate = 0;
          $totalcurrentdue = 0;
          $totalpenalty_months = [];
          $totalpenalty_amount = [];
          $totalpenalty_ma_with_rebate = [];
          $totalpenalty = [];

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'DATE : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, date_format($asof2, 'd-M-y'), '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'NAME : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, $data->clientname, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'ADDRESS    : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->addr, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'MODEL    : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->model, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'TERM    : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, $data->terms, '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'D/P : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->downpayment, '', 'L', false, 1);

          $outbal = $this->get_outbal($center, $data->clientid);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'Outstanding Balance : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, number_format($outbal, 2), '', 'L', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'M. A. : ', '', 'L', false, 0);
          PDF::MultiCell(520, 0, $data->ma, '', 'L', false, 1);

          $due = $this->othersClass->getOrdinal(date("j", strtotime($data->due)));

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(200, 0, 'Due Date: : ', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', $fontsize12);
          PDF::MultiCell(520, 0, $due, '', 'L', false, 1);

          $style = ['dash' => 2];
          PDF::SetLineStyle($style);
          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(220, 0, '', 'B', 'L', false, 0);
          PDF::MultiCell(500, 0, 'Interest & Over Due', 'TBLR', 'C', false, 1);

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(120, 0, '', 'RL', 'C', false, 0);
          PDF::MultiCell(100, 0, 'MONTHLY', 'R', 'C', false, 0);
          PDF::MultiCell(120, 0, 'PAYMENT', 'R', 'C', false, 0);
          PDF::MultiCell(100, 0, 'No. of', 'R', 'C', false, 0);
          PDF::MultiCell(140, 0, 'Penalty', 'R', 'C', false, 0);
          PDF::MultiCell(140, 0, '', 'R', 'C', false, 1);

          PDF::MultiCell(120, 0, 'Due Date', 'LBR', 'C', false, 0);
          PDF::MultiCell(100, 0, 'AMORTIZATION', 'BR', 'C', false, 0);
          PDF::MultiCell(120, 0, 'DATE.', 'BR', 'C', false, 0);
          PDF::MultiCell(100, 0, 'Days int.', 'BR', 'C', false, 0);
          PDF::MultiCell(140, 0, 'Due', 'BR', 'C', false, 0);
          PDF::MultiCell(140, 0, 'Current Due', 'BR', 'C', false, 1);
          $style = ['dash' => 0];
          PDF::SetLineStyle($style);

          $totalcurrentdue = $data->balance + $data->rebate;

          $prevday = '';
          $prevdue = '';
          $prevbalance =  0;
          $customer = $data->clientname;
        }

        PDF::SetFont($font, '', $fontsize12);

        $asof     = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
        $paymentdate =  '';
        $paymentd =  '';
        $paymentd = $this->get_paymentdate($data->trno, $data->docdate);
        $paymentd = isset($paymentd) ? $paymentd : '';
        if ($daysdue == '') //on first loop, initialize daysdue//skip on 2nd
        {
          $daysdue       = $data->due;
        }

        if ($paymentd != '') { //if last pay date is not empty
          $paymentdate =  $paymentd;
          if (!isset($no_of_days->days)) {
            $no_of_days = date_diff(date_create($daysdue), date_create($paymentdate));
            $daysdue       = $data->due;
            $rebate = $data->rebate;

            $comare_due = strtotime($daysdue);
            $compare_pay = strtotime($paymentdate);

            if ($comare_due > $compare_pay) {
              $penalty = 0;
            } else {
              if ($prevbalance > 0) {
                $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
              } else {
                $penalty = 0;
              }
            }
          }
        } else { //if last pay date is empty

          if (isset($daysdue)) {
            if ($daysdue != '') {
              //previous daysdue used
              if (!isset($no_of_days->days)) {
                $no_of_days = date_diff(date_create($daysdue), date_create($data->due));
                //daysdue of current line loaded for next
                $daysdue       = $data->due;
                $rebate = $data->rebate;

                $comare_due = strtotime($daysdue);
                $compare_pay = strtotime($data->due);

                if ($comare_due > $compare_pay) {
                  $penalty = 0;
                } else {
                  if ($prevbalance > 0) {
                    $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
                  } else {
                    $penalty = 0;
                  }
                }
              }
            }
          }
        }


        if ($isfirstloop == 1) {

          PDF::SetFont($font, '', $fontsize12);
          PDF::MultiCell(120, 0, $data->due, 'TBLR', 'L', false, 0);
          PDF::MultiCell(100, 0, '', 'TBLR', 'L', false, 0);
          if ($paymentdate == '') {

            PDF::MultiCell(120, 0, '', 'TBLR', 'L', false, 0);
          } else {
            PDF::MultiCell(120, 0, $paymentdate, 'TBLR', 'R', false, 0);

            $prevday =  $paymentdate;
            $prevdue =  $data->due;
            $prevbalance =  $data->balance;
          }

          PDF::MultiCell(100, 0, '-', 'TBLR', 'R', false, 0);
          PDF::MultiCell(140, 0, '-', 'TBLR', 'R', false, 0);
          PDF::MultiCell(140, 0, number_format(round($totalcurrentdue), 2), 'TBLR', 'R', false, 1);
          $isfirstloop = 0;


          array_push($totalpenalty_months, $data->due);
          array_push($totalpenalty_ma_with_rebate, $totalcurrentdue);
        } else {
          PDF::MultiCell(120, 0, isset($data->due) ? $data->due : $data->postdate, 'TBLR', 'L', false, 0);
          PDF::MultiCell(100, 0, number_format($data->balancewithreb, 2), 'TBLR', 'R', false, 0);

          if ($prevday != '') {
            $no_of_days = date_diff(date_create($prevdue), date_create($prevday));
            //daysdue of current line loaded for next
            $daysdue       = $data->due;
            $rebate = $data->rebate;


            $comare_due = strtotime($prevdue);
            $compare_pay = strtotime($prevday);

            if ($comare_due > $compare_pay) {
              $penalty = 0;
              if ($prevbalance > 0) {
                $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
              } else {
                $penalty = 0;
              }
            } else {

              if ($prevbalance > 0) {
                $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
              } else {
                $penalty = 0;
              }
            }
          } else {
            $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
          }

          PDF::MultiCell(120, 0, $paymentdate, 'TBLR', 'R', false, 0);
          PDF::MultiCell(100, 0, $no_of_days->days, 'TBLR', 'R', false, 0);
          PDF::MultiCell(140, 0, number_format(isset($penalty) ? round($penalty, 1) : 0, 2), 'TBLR', 'R', false, 0);

          $totalcurrentdue = $data->balance + $totalcurrentdue + (isset($penalty) ? $penalty : 0) + $data->rebate;


          $prevday =  $paymentd;
          $prevdue =  $data->due;
          $prevbalance =  $data->balance;

          PDF::MultiCell(140, 0, number_format(round($totalcurrentdue), 2), 'TBLR', 'R', false, 1);


          array_push($totalpenalty_months, $data->due);
          array_push($totalpenalty_amount, isset($penalty) ? $penalty : 0);
          array_push($totalpenalty_ma_with_rebate, $data->balancewithreb);


          $col = array('name' => $data->clientname, 'date' => $data->docdate, 'amount' => isset($penalty) ? $penalty : 0);
          array_push($totalpenalty, $col);
        }
      }
      // zxc
      if ($totalcurrentdue != 0) {

        $asof     = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
        $paymentdate =  '';
        $paymentd =  '';
        $paymentd = $this->get_paymentdate($data->trno, $data->docdate);
        $paymentd = isset($paymentd) ? $paymentd : '';
        if ($daysdue == '') //on first loop, initialize daysdue//skip on 2nd
        {
          $daysdue       = $data->due;
        }

        $paymentdate = $asof;
        if (isset($daysdue)) {
          if ($daysdue != '') {
            //previous daysdue used
            if (!isset($no_of_days->days)) {
              $no_of_days = date_diff(date_create($daysdue), date_create($paymentdate));
              //daysdue of current line loaded for next
              $daysdue       = $data->due;

              $comare_due = strtotime($daysdue);
              $compare_pay = strtotime($paymentdate);

              if ($comare_due > $compare_pay) {
                $penalty = 0;
              } else {
                if ($prevbalance > 0) {
                  $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
                } else {
                  $penalty = 0;
                }
              }
            }
          }
        }

        PDF::MultiCell(120, 0, '', 'TBLR', 'L', false, 0);
        PDF::MultiCell(100, 0, '', 'TBLR', 'R', false, 0);

        PDF::MultiCell(120, 0, $paymentdate, 'TBLR', 'R', false, 0);
        if ($penalty == 0) {
          PDF::MultiCell(100, 0, '-', 'TBLR', 'R', false, 0);
          PDF::MultiCell(140, 0, '-', 'TBLR', 'R', false, 0);
        } else {
          PDF::MultiCell(100, 0, $no_of_days->days, 'TBLR', 'R', false, 0);
          PDF::MultiCell(140, 0, number_format(isset($penalty) ? round($penalty, 1) : 0, 2), 'TBLR', 'R', false, 0);
        }

        $totalcurrentdue = $totalcurrentdue + (isset($penalty) ? $penalty : 0) + $rebate;

        PDF::MultiCell(140, 0, number_format(round($totalcurrentdue), 2), 'TBLR', 'R', false, 1);
        array_push($totalpenalty_amount, isset($penalty) ? $penalty : 0);
        $col = array('name' => $data->clientname, 'date' => $data->docdate, 'amount' => isset($penalty) ? $penalty : 0);
        array_push($totalpenalty, $col);

        $isfirstloop = 1;
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(120, 0, '', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(120, 0, '.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(140, 0, '', '', 'L', false, 0);
        PDF::MultiCell(140, 0, number_format(round($totalcurrentdue), 2), 'B', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::MultiCell(170, 0, 'Total Penalty', '', 'L', false, 0);
        PDF::MultiCell(150, 0, number_format(array_sum($totalpenalty_amount), 2), '', 'R', false, 0);
        PDF::MultiCell(400, 0, '', '', 'L', false, 1);


        $style = ['dash' => 2];
        PDF::SetLineStyle($style);
        foreach ($totalpenalty as $key => $value) {
          $penalty_date = date('j-M-y', strtotime($totalpenalty_months[$key]));
          $ma_with_rebate = $totalpenalty_ma_with_rebate[$key];

          if ($value['amount'] != 0) {
            PDF::MultiCell(170, 0, $penalty_date, '', 'L', false, 0);
            PDF::MultiCell(150, 0, number_format($ma_with_rebate, 2), 'B', 'R', false, 0);
            PDF::MultiCell(400, 0, '', '', 'L', false, 1);
          }
        }

        PDF::MultiCell(170, 0, 'Total Amount Due', '', 'L', false, 0);
        PDF::MultiCell(150, 0, number_format(round($totalcurrentdue), 2), 'B', 'R', false, 0);
        PDF::MultiCell(400, 0, '', '', 'L', false, 1);
      }

      //ending
      PDF::MultiCell(0, 0, "\n\n");

      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(120, 0, 'Collection Officer: ', '', 'L', false, 0);
      PDF::MultiCell(150, 0, $config['params']['dataparams']['collector'], 'B', 'C', false, 0);
      PDF::MultiCell(450, 0, '', '', 'L', false, 1);

      PDF::MultiCell(120, 0, 'Date', '', 'L', false, 0);
      PDF::MultiCell(150, 0, date_format($asof2, 'd-M-y'), '', 'C', false, 0);
      PDF::MultiCell(450, 0, '', '', 'L', false, 1);

      $style = ['dash' => 0];
      PDF::SetLineStyle($style);

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(720, 0, 'If there is any discrepancies against our record please feel free to call, or text our Head Office Bookkeeping Department,', '', 'J', false, 1);
      PDF::MultiCell(720, 0, 'contact no.# 0917-794-6016 for your reconciliation of your account.', '', 'J', false, 1);

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(720, 0, 'Please disregard this statement if payment has been made. Thank You for doing your usual business transaction with us.', '', 'J', false, 1);

      PDF::MultiCell(0, 0, "\n\n");

      PDF::MultiCell(100, 0, 'Prepared by: ', '', 'L', false, 0);
      PDF::MultiCell(300, 0, '', '', 'L', false, 0);
      PDF::MultiCell(320, 0, 'Received by: ', '', 'L', false, 1);

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
      PDF::MultiCell(320, 0, 'Signature Over Printed Name', '', 'L', false, 1);

      PDF::MultiCell(0, 0, "\n\n");

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, '', '', 'L', false, 0);
      PDF::MultiCell(320, 0, 'Date Received:', '', 'L', false, 1);
      //ending
    } else {

      PDF::SetFont($font, '', 20);
      PDF::MultiCell(720, 0, 'NO TRANSACTION', '', 'C', false);
    }

    $pdf = PDF::Output($this->modulename . '.pdf', 'S');
    return $pdf;
  }


  public function reportDefaultLayout($config)
  {
    $result     = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $attention  = $config['params']['dataparams']['attention'];
    $certifby   = $config['params']['dataparams']['certifby'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $currentdate = $this->othersClass->getCurrentDate();
    $getcurrentmonth = date('m', strtotime($currentdate));
    $count = 11;
    $page = 1;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');

    $customer = '';
    $balance = 0;
    $first_customer = true;


    $test_interest_due = 5;

    $no_days_int_test = 7;

    $no_days_int = 0;
    $totalcurrentdue = 0;
    $totalpenalty_months = [];
    $totalpenalty_amount = [];
    $totalpenalty = [];
    $isfirstloop = 1;

    $str .= $this->reporter->begintable('1000');
    foreach ($result as $key => $data) {
      //print on change of client
      if ($customer != $data->clientname && $customer != '') {

        if ($totalcurrentdue != 0) {
          $isfirstloop = 1;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '120', null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
          $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '220', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalcurrentdue, 2), '220', null, false, '1.5px dotted ', 'B', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= '<br><br><br>';
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total Penalty', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '90', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col(number_format(array_sum($totalpenalty_amount), 2), '100',  null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          foreach ($totalpenalty as $key => $value) {

            $penalty_date = date('j-M-y', strtotime($value['date']));
            $str .= $this->reporter->col($penalty_date, '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
            $str .= $this->reporter->col('', '90', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
            $str .= $this->reporter->col(number_format($value['amount'], 2), '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
            $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->endrow();
          }

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->col('Total Amount Due', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '90', null, false,  '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col(number_format($totalcurrentdue, 2), '100', null, false,  '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        //total 
        $str .= '<br><br>';
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->col('CI/Collector: ', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '190', null, false,  '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->col('Date', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($asof, '190', null, false,  $border, '', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('If there is any discrepancies against our record please feel free to call 
          or text our Head Office Bookkeeping Department, <br>
          contact no.# 0917-794-6016 for your reconciliation of your account.', null, null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Please disregard this statement if payment has been made.', null, null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Thank You for doing your usual business transaction with us.', null, null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->endtable();

        $str .= '<br><br>';

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, '12', 'B');
        $str .= $this->reporter->col('', '200', null, false, '1px solid', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '400', null, false,  $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Received by:', '100', null, false, '1px dotted ', '', 'C', $font, '12', 'B');
        $str .= $this->reporter->col('', '200', null, false, '1px solid', 'B', 'C', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'L', $font, '12', 'B');
        $str .= $this->reporter->col('BOOKKEEPING HEAD', '200', null, false, '1px solid', '', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col('', '500', null, false,  $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Signature Over Printed Name', '200', null, false, '1px solid', '', 'C', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'L', $font, '12', 'B');
        $str .= $this->reporter->col('', '200', null, false, '1px solid', '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '400', null, false,  $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Date Received:', '100', null, false, '1px solid', '', 'C', $font, '12', 'B');
        $str .= $this->reporter->col('', '200', null, false, '1px solid', 'B', 'C', $font, '12', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // Check for page break
        if ($this->reporter->linecounter == $page) {

          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->printline();
          $page += $count;
        }


        //new customer
        $totalpenalty_months = [];
        $totalpenalty_amount = [];
        $totalpenalty = [];
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE :', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($asof, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NAME :', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->clientname, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS    : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->addr, '160', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MODEL    : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->model, '500', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '380', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TERM    : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->terms, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('D/P :      ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->downpayment, '160', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();


        $outbal = $this->get_outbal($center, $data->clientid);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Outstanding Balance : ', '200', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($outbal, 2), '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '640', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('M. A. : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->amortization, '160', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $due = $this->othersClass->getOrdinal(date("j", strtotime($data->due)));

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Due Date: ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($due, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '420', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Interest & Over Due', '580', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('MONTHLY', '160', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('PAYMENT', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('No. of', '160', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Penalty ', '220', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '220', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Due Date', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('AMORTIZATION', '160', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DATE.', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Days int.', '160', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Due', '220', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Current Due', '220', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $test_interest_due = 5;

        $totalcurrentdue = $data->balance;
        $no_days_int_test = 7;

        $paymentd = $this->get_paymentdate($data->trno, $data->docdate);
        $paymentd = isset($paymentd) ? $paymentd : '';
        if ($paymentd != '') {
          $paymentdate =  $paymentd;
        } else {
          $paymentdate = '';
        }

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->due, '120', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($paymentdate, '120', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('-', '160', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('-', '220', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalcurrentdue, 2), '220', null, false, '1px dotted', 'LRB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $customer = $data->clientname;
      }
      //print on first loop
      if ($customer == '') {
        $totalcurrentdue = 0;
        $totalpenalty_months = [];
        $totalpenalty_amount = [];
        $totalpenalty = [];
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE :', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($asof, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NAME :', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->clientname, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS    : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->addr, '160', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MODEL    : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->model, '500', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '380', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TERM    : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->terms, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('D/P :      ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->downpayment, '160', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $outbal = $this->get_outbal($center, $data->clientid);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Outstanding Balance : ', '200', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($outbal, 2), '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '640', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('M. A. : ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->amortization, '160', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $due = $this->othersClass->getOrdinal(date("j", strtotime($data->due)));

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Due Date: ', '120', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($due, '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '720', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '420', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Interest & Over Due', '580', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('MONTHLY', '160', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('PAYMENT', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('No. of', '160', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Penalty ', '220', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '220', null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Due Date', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('AMORTIZATION', '160', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DATE.', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Days int.', '160', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Due', '220', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Current Due', '220', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $test_interest_due = 5;

        $totalcurrentdue = $data->balance;
        $no_days_int_test = 7;


        $paymentd = $this->get_paymentdate($data->trno, $data->docdate);
        $paymentd = isset($paymentd) ? $paymentd : '';
        if ($paymentd != '') {
          $paymentdate =  $paymentd;
        } else {
          $paymentdate = '';
        }

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->due, '120', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($paymentdate, '120', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('-', '160', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('-', '220', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalcurrentdue, 2), '220', null, false, '1px dotted', 'LRB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $customer = $data->clientname;
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(isset($data->due) ? $data->due : $data->postdate, '120', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '160', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');

      $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
      $paymentd = $this->get_paymentdate($data->trno, $data->docdate);
      $paymentd = isset($paymentd) ? $paymentd : '';
      if ($paymentd != '') {

        $paymentdate =  $paymentd;
        $due       = $data->due;
        $no_of_days = date_diff(date_create($due), date_create($paymentdate));
      } else {

        $paymentdate = $asof;
        $due       = $data->due;
        $no_of_days = date_diff(date_create($due), date_create($paymentdate));
      }

      $penalty = (($no_of_days->days / 30) * 0.04) * $totalcurrentdue;
      $str .= $this->reporter->col($paymentdate, '120', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($no_of_days->days, '160', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($penalty, 2), '220', null, false, '1px dotted', 'LB', 'C', $font, $fontsize, '', '', '');
      if ($isfirstloop == 1) {
        $totalcurrentdue = $totalcurrentdue + $penalty;
        $isfirstloop = 0;
      } else {
        $totalcurrentdue = $totalcurrentdue + $penalty + $data->balance;
      }

      $str .= $this->reporter->col(number_format($totalcurrentdue, 2), '220', null, false, '1px dotted', 'LRB', 'R', $font, $fontsize, '', '', '');

      $balance += $data->balance;

      array_push($totalpenalty_months, $data->docdate);
      array_push($totalpenalty_amount, $penalty);


      $col = array('name' => $data->clientname, 'date' => $data->docdate, 'amount' => $penalty);
      array_push($totalpenalty, $col);
    }

    if ($totalcurrentdue != 0) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '120', null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '120', null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
      $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '220', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalcurrentdue, 2), '220', null, false, '1.5px dotted ', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= '<br><br><br>';

      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Total Penalty', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '90', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(number_format(array_sum($totalpenalty_amount), 2), '100',  null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();



      $str .= $this->reporter->begintable('1000');
      foreach ($totalpenalty as $key => $value) {

        $penalty_date = date('j-M-y', strtotime($value['date']));
        $str .= $this->reporter->col($penalty_date, '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '90', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($value['amount'], 2), '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->col('Total Amount Due', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '90', null, false,  '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(number_format($totalcurrentdue, 2), '100', null, false,  '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->col('CI/Collector: ', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, '');
    $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '190', null, false,  '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->col('Date', '120', null, false, '1px dotted ', '', 'C', $font, $fontsize, '');
    $str .= $this->reporter->col('', '160', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col($asof, '190', null, false,  $border, '', 'C', $font, $fontsize, '');
    $str .= $this->reporter->col('', '530', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('If there is any discrepancies against our record please feel free to call 
    or text our Head Office Bookkeeping Department, <br>
    contact no.# 0917-794-6016 for your reconciliation of your account.', null, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Please disregard this statement if payment has been made.', null, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Thank You for doing your usual business transaction with us.', null, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, '12', 'B');
    $str .= $this->reporter->col('', '200', null, false, '1px solid', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '400', null, false,  $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Received by:', '100', null, false, '1px dotted ', '', 'C', $font, '12', 'B');
    $str .= $this->reporter->col('', '200', null, false, '1px solid', 'B', 'C', $font, '12', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'L', $font, '12', 'B');
    $str .= $this->reporter->col('BOOKKEEPING HEAD', '200', null, false, '1px solid', '', 'C', $font, $fontsize, '');
    $str .= $this->reporter->col('', '500', null, false,  $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Signature Over Printed Name', '200', null, false, '1px solid', '', 'C', $font, '12', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'L', $font, '12', 'B');
    $str .= $this->reporter->col('', '200', null, false, '1px solid', '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '400', null, false,  $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Date Received:', '100', null, false, '1px solid', '', 'C', $font, '12', 'B');
    $str .= $this->reporter->col('', '200', null, false, '1px solid', 'B', 'C', $font, '12', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function get_outbal($center, $clientid)
  {
    return $this->coreFunctions->datareader("
    select sum(aroutbal.bal) as value
    from arledger as aroutbal
    left join coa as c on c.acnoid=aroutbal.acnoid
    left join cntnum as cnum on cnum.trno=aroutbal.trno
    where cnum.center='$center' and aroutbal.clientid=$clientid and c.alias in ('AR1','AR2') 
    
    and aroutbal.bal>0    
    ");
  }

  private function get_paymentdate($trno, $date)
  {
    return $this->coreFunctions->datareader("select ifnull(dateid,'') as value from (
    select h.dateid 
    from lahead as h 
    left join ladetail as d on d.trno = h.trno 
    where d.refx = '$trno' 
    and date(d.postdate) = '" . date('Y-m-d', strtotime($date)) . "' 
    
    union all 

    select h.dateid 
    from glhead as h 
    left join gldetail as d on d.trno = h.trno 
    where d.refx = '$trno' 
    and date(d.postdate) = '" . date('Y-m-d', strtotime($date)) . "' 
    ) as a 
    order by dateid desc limit 1
    ");
  }
}//end class