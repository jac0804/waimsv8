<?php

namespace App\Http\Classes\modules\reportlist\customers;

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
use Exception;
use Illuminate\Support\Facades\URL;

class water_bill
{
  public $modulename = 'Water Bill';
  public $tablelogs = 'table_log';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $showemailbtn = true;

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

    $fields = ['radioprint', 'radioemail', 'docno', 'dclientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.action', 'lookupconsumtionno');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'docno.required', true);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],

    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1,  'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $companyid = $config['params']['companyid'];
    $username = $config['params']['user'];
    $type = 'PDFM';

    $paramstr = "select 
    '" . $type . "' as print,
    'default' as sendmail,
    '' as docno,
    0 as trno,
    '' as client,
    '' as clientname,
    0 as clientid,
    '' as dclientname";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
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
    if ($config['params']['dataparams']['client'] == '') {
      $config['params']['dataparams']['clientid'] = 0;
    }
    $query = $this->reportDefault($config, $config['params']['dataparams']['trno'],  $config['params']['dataparams']['clientid']);
    $data = $this->coreFunctions->opentable($query);

    $sendmail = $config['params']['dataparams']['sendmail'];
    if ($sendmail == 'resend') {
      $table = "lastock";
      if ($this->othersClass->isposted2($config['params']['dataparams']['trno'], "cntnum")) {
        $table = "glstock";
      }
      $arrfilter = ['trno' => $config['params']['dataparams']['trno']];
      if ($config['params']['dataparams']['clientid'] != 0) {
        $arrfilter['suppid'] = $config['params']['dataparams']['clientid'];
      }
      $this->coreFunctions->sbcupdate($table, ['isemail' => 0], $arrfilter);
    }

    $result_layout = $this->default_layout($config, $data);

    return $result_layout['pdf'];
  }

  public function reportDefault($config, $trno, $clientid, $blnSendMail = false)
  {
    $filter = " and stock.suppid = '$clientid'";
    if ($clientid == 0) {
      $filter = "";
    }
    if ($blnSendMail) {
      $filter .= " and stock.isemail=0";
    }

    $query = "select * from (
    select pm.name as projname, head.docno, date(head.dateid) as dateid, date_format(head.dateid,'%M %d, %Y') as tdateid, head.rem, date_format(date(head.due),'%M %d, %Y') as due, date_format(date(head.sdate1),'%M %d, %Y') as sdate1, date_format(date(head.sdate2),'%M %d, %Y') as sdate2,
    item.barcode, item.shortname as address, client.addr, client.client, client.clientname as customer, 
    stock.isqty as presconsumption,stock.prevqty as prevconsumption,stock.isqty2 as prevread, stock.isqty3 as presread, stock.isamt as rate, pm.surcharge,pm.reconfee, client.clientid
    from lahead as head
    left join lastock as stock on head.trno = stock.trno
    left join item on item.itemid = stock.itemid
    left join client on client.clientid = stock.suppid
    left join projectmasterfile as pm on pm.line = head.projectid
    where head.trno = $trno " . $filter . "
    union all
    select pm.name as projname, head.docno, date(head.dateid) as dateid, date_format(head.dateid,'%M %d, %Y') as tdateid, head.rem, date_format(date(head.due),'%M %d, %Y') as due, date_format(date(head.sdate1),'%M %d, %Y') as sdate1, date_format(date(head.sdate2),'%M %d, %Y') as sdate2,
    item.barcode, item.shortname as address, client.addr, client.client, client.clientname as customer, 
    stock.isqty as presconsumption,stock.prevqty as prevconsumption,stock.isqty2 as prevread, stock.isqty3 as presread, stock.isamt as rate, pm.surcharge,pm.reconfee, client.clientid
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join item on item.itemid = stock.itemid
    left join client on client.clientid = stock.suppid
    left join projectmasterfile as pm on pm.line = head.projectid
    where head.trno = $trno " . $filter . "
    ) as x group by projname, docno, dateid, rem, due, sdate1, sdate2, barcode, address, addr, client, customer, presconsumption,prevconsumption, prevread, presread, rate, surcharge,reconfee, clientid, tdateid
    order by customer, barcode 
    ";

    return $query;
  }

  public function default_layout($params, $data, $blnSendMail = false)
  {


    $this->othersClass->setDefaultTimeZone();
    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $decimalcurr = 2;
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    $count = $page = 900;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;
    $arbal = 0;
    $font = '';
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }
    $timesi = "";
    $fonth = 'dejavusans';
    $peso = "<span>&#8369;</span>";

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTitle($this->modulename);
    $pdf->SetAuthor('Solutionbase Corp.');
    $pdf->SetCreator('Solutionbase Corp.');
    $pdf->SetSubject($this->modulename . ' Module Report');
    $pdf->setPageUnit('px');
    $pdf->SetMargins(20, 20);
    $pdf->AddPage('P', 'LETTER'); // 570 width

    $fontsize9 = "9";
    $fontsize10 = "10";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";
    $surcharge = 0;

    if ($blnSendMail) {
      $cutomerdata = $this->getCustomer($params['params']['trno'], $params['params']['clientid']);
    } else {
      $cutomerdata = $this->getCustomer($params['params']['dataparams']['trno'], $params['params']['dataparams']['clientid']);
    }



    if (empty($cutomerdata)) {
      $pdf->SetFont($fontbold, '', 20);
      $pdf->MultiCell(320, 0, 'Report empty.', '', 'L', false, 1);
    }

    $counter = count($cutomerdata);
    foreach ($cutomerdata as $key => $value) {

      $customx = $this->coreFunctions->opentable($this->reportDefault($params, $value->trno, $value->suppid));

      $pdf->SetFont($font, '', 9);
      $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
      $pdf->MultiCell(0, 0, $reporttimestamp, 0, 'L', false, 1, '',  '', true, 0, true);
      $pdf->SetFont($fontbold, '', 11);
      $pdf->MultiCell(0, 0, strtoupper($headerdata[0]->name), 0, 'C');
      $pdf->SetFont($fontbold, '', 11);
      $pdf->MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), 0, 'C');


      $pdf->SetFont($fontbold, '', 14);
      $pdf->MultiCell(320, 0, $this->modulename, '', 'L', false, 1);


      $pdf->MultiCell(380, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($fontbold, '', $fontsize10);
      $pdf->MultiCell(100, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($font, '', 10);
      $pdf->MultiCell(90, 0, (isset($customx[0]->docno) ? $customx[0]->docno : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


      $pdf->SetFont($fontbold, '', $fontsize10);
      $pdf->MultiCell(80, 20, "Customer : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($font, '', $fontsize10);
      $pdf->MultiCell(320, 20, (isset($customx[0]->customer) ? $customx[0]->customer : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($fontbold, '', $fontsize10);
      $pdf->MultiCell(60, 20, "Date : ", '', 'r', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($font, '', $fontsize10);
      $pdf->MultiCell(90, 20, (isset($customx[0]->tdateid) ? $customx[0]->tdateid : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



      $pdf->SetFont($fontbold, '', $fontsize10);
      $pdf->MultiCell(80, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($font, '', $fontsize10);
      $pdf->MultiCell(320, 20, (isset($customx[0]->addr) ? $customx[0]->addr : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($fontbold, '', $fontsize10);
      $pdf->MultiCell(60, 20, "Project : ", '', 'r', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($font, '', $fontsize10);
      $pdf->MultiCell(90, 20, (isset($customx[0]->projname) ? $customx[0]->projname : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      $pdf->SetFont($fontbold, '', $fontsize10);
      $pdf->MultiCell(80, 20, "Billing Period : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($font, '', $fontsize10);
      $pdf->MultiCell(320, 20, (isset($customx[0]->sdate1) ? $customx[0]->sdate1 : '') . ' - ' . (isset($customx[0]->sdate2) ? $customx[0]->sdate2 : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->MultiCell(20, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

      $pdf->SetFont($fontbold, '', $fontsize10);
      $pdf->MultiCell(60, 20, "Due Date : ", '', 'r', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      $pdf->SetFont($font, '', $fontsize10);
      $pdf->MultiCell(90, 20, (isset($customx[0]->due) ? $customx[0]->due : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      $pdf->MultiCell(0, 0, "", "B");

      $totals = 0;


      $surchargeperc = 0;
      $reconfee = 0;
      if (!empty($customx)) {
        foreach ($customx as $ki => $vals) {

          $pdf->SetCellPaddings(0, 4, 0, 0);


          $pdf->MultiCell(30, 0, "", "", 'L', false, 0);
          $pdf->SetFont($font, 'B', $fontsize11);
          $pdf->MultiCell(90, 0, "Current Charges:", "", 'L', false, 0);
          $pdf->SetFont($font, '', $fontsize11);
          $pdf->MultiCell(180, 0, $vals->address, "", 'L', false, 0, '', '', true, 0, true);
          $pdf->MultiCell(270, 0, " <b>Meter No.</b> : " . $vals->barcode, "", 'L', false, 1, '', '', true, 0, true);
          $pdf->SetCellPaddings(0, 2, 0, 0);

          $pdf->MultiCell(30, 0, "", "", 'L', false, 0);
          $pdf->SetFont($font, 'B', $fontsize11);
          $pdf->MultiCell(90, 0, "Present Reading:", "", 'L', false, 0);
          $pdf->MultiCell(80, 0, number_format($vals->presread, 2) . " cu.m.", "", 'R', false, 1);

          $pdf->MultiCell(30, 0, "", "", 'L', false, 0);
          $pdf->SetFont($font, 'B', $fontsize11);
          $pdf->MultiCell(90, 0, "Previous Reading:", "", 'L', false, 0);
          $pdf->MultiCell(80, 0, number_format($vals->prevread, 2) . " cu.m.", "B", 'R', false, 1);

          $pdf->MultiCell(30, 0, "", "", 'L', false, 0);
          $pdf->SetFont($font, 'B', $fontsize11);
          $pdf->MultiCell(90, 0, "Consumption:", "", 'L', false, 0);
          $pdf->MultiCell(80, 0, number_format($vals->presconsumption, 2) . " cu.m.", "", 'R', false, 0);
          $pdf->MultiCell(100, 0, " x Php " . number_format($vals->rate, 2) . "/cu.m.", "", 'L', false, 0);
          $pdf->SetFont($fonth, '', $fontsize10);
          $pdf->MultiCell(10, 0, $peso, "", 'L', false, 0, '', '', true, 0, true);
          $pdf->SetFont($font, 'B', $fontsize11);
          $pdf->MultiCell(50, 0, number_format($vals->presconsumption * $vals->rate, 2), "", 'R', false, 1);
          $totals += $vals->presconsumption * $vals->rate;

          $surchargeperc = $vals->surcharge;
          $reconfee = $vals->reconfee;

          if (count($customx) - 1 > $ki) {
            $pdf->MultiCell(0, 0, "");
          }
        }
      } //foreach

      $arbal = $this->coreFunctions->datareader("select ifnull(sum(if(ar.cr<>0,ar.bal*-1,ar.bal)),0) as value from arledger as ar left join glhead as h on h.trno=ar.trno where date(h.dateid)<'" . $cutomerdata[$key]->dateid . "' and ar.clientid = " . $cutomerdata[$key]->clientid, [], '', true);



      $pdf->MultiCell(30, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, 'B', $fontsize11);
      $pdf->MultiCell(90, 0, "Surcharge:", "", 'L', false, 0);

      if ($cutomerdata[$key]->posted) {
        $arbalSCqry = "select ar.db as value from arledger as ar left join coa on coa.acnoid=ar.acnoid where ar.trno=" . $cutomerdata[$key]->trno . " and ar.clientid=" . $cutomerdata[$key]->clientid . " and coa.alias='ARSC'";
        $surcharge = $this->coreFunctions->datareader($arbalSCqry, [], '', true);
      } else {
        $arbalSCqry = "select ifnull(sum(bal),0) as value from (
          select detail.db-detail.cr AS bal 
          from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
          left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
          where date(head.dateid)<'" . $cutomerdata[$key]->enddate . "' and dclient.clientid=" . $cutomerdata[$key]->clientid . " and left(coa.alias,2)='AR') as bal";
        $arbalSC = $this->coreFunctions->datareader($arbalSCqry, [], '', true);

        if ($arbalSC > 0) {
          $surcharge = $arbalSC * ($surchargeperc / 100);
        } else {
          $surcharge = 0;
        }
      }

      $pdf->MultiCell(240, 0, number_format($surcharge, 2), "", 'R', false, 1);

      $pdf->MultiCell(30, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, 'B', $fontsize11);
      $pdf->MultiCell(120, 0, "Arrears/Overpayment:", "", 'L', false, 0);
      $pdf->MultiCell(210, 0, number_format($arbal, 2), "", 'R', false, 1);
      $pdf->SetCellPaddings(0, 4, 0, 0);
      $pdf->MultiCell(30, 0, "", "", 'L', false, 0);

      $pdf->SetFont($font, 'B', $fontsize11);
      $pdf->MultiCell(270, 0, "TOTAL AMOUNT DUE ON UTILITIES ", "", 'L', false, 0);
      $fntotals = $totals + $surcharge + $arbal;
      $pdf->SetFont($fonth, '', $fontsize10);
      $pdf->MultiCell(10, 0, $peso, "", 'L', false, 0, '', '', true, 0, true);
      $pdf->SetFont($font, 'B', $fontsize11);
      $pdf->MultiCell(50, 0, number_format($fntotals, 2), "T", 'R', false, 1);
      $pdf->MultiCell(30, 0, "", "", 'L', false, 0);

      $pdf->SetFont($font, 'B', $fontsize11);
      $pdf->MultiCell(90, 0, "Amount in words: ", "", 'L', false, 0);
      $fntotals = number_format((float) $fntotals, 2, '.', '');
      $pdf->SetFont($font, 'i', $fontsize11);
      $word = strtolower($this->reporter->ftNumberToWordsConverter($fntotals));
      $pdf->MultiCell(600, 0,  trim(ucwords($word)) . " Only", "", 'L', false, 1);

      $lastpayment = $this->coreFunctions->opentable("select date(h.dateid) as dateid, d.checkno, d.db from lahead as h left join ladetail as d on d.trno=h.trno left join coa on coa.acnoid=d.acnoid
                                                      where h.doc='CR' and h.client='" . $cutomerdata[$key]->client . "' and h.dateid<'" . $cutomerdata[$key]->dateid . "' and left(coa.alias,2) in ('CA','CB','CR')
                                                      union all
                                                      select date(h.dateid) as dateid, d.checkno, d.db from glhead as h left join gldetail as d on d.trno=h.trno left join coa on coa.acnoid=d.acnoid
                                                      where h.doc='CR' and h.clientid=" . $cutomerdata[$key]->clientid . " and h.dateid<'" . $cutomerdata[$key]->dateid . "' and left(coa.alias,2) in ('CA','CB','CR') 
                                                      order by dateid desc limit 1");

      if (!empty($lastpayment)) {

        foreach ($lastpayment as $key => $valpay) {
          $pdf->SetFont($font, 'B', $fontsize11);
          $pdf->SetCellPaddings(0, 4, 0, 0);
          $pdf->MultiCell(30, 0, "", "", 'L', false, 0);
          $pdf->MultiCell(90, 0, "Last Payment: ", "", 'L', false, 0);
          $pdf->SetFont($font, '', $fontsize11);
          $pdf->MultiCell(600, 0,  $valpay->dateid . "  " . $valpay->checkno . "  " . number_format($valpay->db, 2), "", 'L', false, 1);
        }
      }

      $center = $params['params']['center'];
      $companyqry = "select name,email,accountno,billingclerk from center where code='$center'";
      $company = json_decode(json_encode($this->coreFunctions->opentable($companyqry)), true);

      $pdf->SetCellPaddings(0, 2, 0, 0);
      $pdf->MultiCell(0, 0, "");
      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(200, 0, " Prepared by:", "", 'L', false, 0);
      $pdf->SetFont($font, 'i', $fontsize11);
      $pdf->MultiCell(350, 0,  "For payments through bank deposits and online transfers:", "", 'L', false, 1);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(200, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, 'b', $fontsize11);
      $pdf->MultiCell(350, 0,  strtoupper($company[0]['name']), "", 'L', false, 1);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(200, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, 'b', $fontsize11);
      $pdf->MultiCell(350, 0,  $company[0]['accountno'], "", 'L', false, 1);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(200, 0, $company[0]['billingclerk'], "", 'L', false, 0);
      $pdf->SetFont($font, 'b', $fontsize11);
      $pdf->MultiCell(350, 0,  "and email your transaction slip to " . $company[0]['email'], "", 'L', false, 1);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(200, 0, "Accounting Assistant", "", 'L', false, 0);
      $pdf->SetFont($font, 'i', $fontsize11);
      $pdf->MultiCell(350, 0,  "", "", 'L', false, 1);

      $pdf->MultiCell(0, 0, "\n\n\n");

      do {
        $pdf->MultiCell(50, 0, "", "", 'L', false, 1);
      } while ($pdf->getY() < 600);

      $pdf->SetTextColor(240, 20, 20);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(0, 0, "IMPORTANT REMINDERS", "", "C");
      $pdf->SetTextColor(0, 0, 0);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(500, 0, "• Full payment is requested on or before the due date as stated on the statement of account.", "", 'L', false, 1);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(500, 0, "• Partial payments are not accepted after the due date.", "", 'L', false, 1);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(500, 0, "• An extension of five days from the due date shall be given to settle the amount in full with a surcharge of " . $surchargeperc . "% per month.", "", 'L', false, 1);

      $pdf->SetTextColor(240, 20, 20);
      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(500, 0, "• A Notice of Disconnection will be given after the five-day extension period and disconnection for unsettled accounts will automatically occur 48-hours after receipt", "", 'L', false, 1);
      $pdf->SetTextColor(0, 0, 0);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(500, 0, "• A reconnection fee of Php " . number_format($reconfee, 2) . " for water will automatically be charged on next month's bill or may be paid together with the amount due. Amount is subject to change without prior notice.", "", 'L', false, 1);

      $pdf->MultiCell(50, 0, "", "", 'L', false, 0);
      $pdf->SetFont($font, '', $fontsize11);
      $pdf->MultiCell(500, 0, "• WE DO NOT ACCEPT PARTIAL PAYMENTS", "", 'L', false, 1);

      $this->othersClass->logConsole("IsAddPage:" . ($counter - 1) . "=" . $key);
      if ($counter - 1 == $key) {
      } else {
        $this->othersClass->logConsole("IsAddPage:true");
        $pdf->AddPage('P', 'LETTER');
      }
    }


    $this->othersClass->logConsole("Output:" . $this->modulename . '.pdf');
    $pdf = $pdf->Output($this->modulename . '.pdf', 'S');

    return ['pdf' => $pdf, 'ar' => $arbal, 'surcharge' => $surcharge];
  }

  public function getCustomer($trno, $clientid = 0, $blnSendMail = false, $plimit = 0)
  {
    $filter = "";
    if ($clientid != 0) {
      $filter .= " and s.suppid=" . $clientid;
    }
    if ($blnSendMail) {
      $filter .= " and s.isemail=0";
    }

    $limit = "";
    if ($plimit != 0) {
      $limit = " limit " . $plimit;
    }

    $sql = "select * from (
      select h.trno, h.docno,h.doc, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, sum(s.ext) as amt,
      date_format(date(h.sdate1),'%M %d, %Y') as sdate1, date_format(date(h.sdate2),'%M %d, %Y') as sdate2, month(h.dateid) as mon, year(h.dateid) as yr, 0 as posted, DATE_FORMAT(h.dateid ,'%Y-%m-01') as enddate
      from lastock as s
      left join lahead as h on h.trno=s.trno
      left join client on client.clientid=s.suppid
      where h.doc='WM' and s.trno=" . $trno . " " . $filter . "
      group by h.trno, h.docno,h.doc, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, h.sdate1, h.sdate2
      union all
      select h.trno, h.docno,h.doc, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, sum(s.ext) as amt,
      date_format(date(h.sdate1),'%M %d, %Y') as sdate1, date_format(date(h.sdate2),'%M %d, %Y') as sdate2, month(h.dateid) as mon, year(h.dateid) as yr, 1 as posted, DATE_FORMAT(h.dateid ,'%Y-%m-01') as enddate
      from glstock as s
      left join glhead as h on h.trno=s.trno
      left join client on client.clientid=s.suppid
      where h.doc='WM' and s.trno=" . $trno . " " . $filter . "
      group by h.trno, h.docno,h.doc, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, h.sdate1, h.sdate2 ";

    $sql .= ") as client" . $limit;

    return $this->coreFunctions->opentable($sql);
  }



  public function sendemail($params)
  {
    $dataparams =  json_decode(json_encode(json_decode($params['params']['dataparams'])), true);
    $trno = $dataparams['trno'];

    $arrclient = $this->getCustomer($trno, $dataparams['clientid'], true, 10);

    $counter = 0;

    foreach ($arrclient  as $key => $val) {

      if (isset($val->email)) {
        if ($val->email != '') {

          $arrEmail = explode(";", $val->email);
          foreach ($arrEmail as $key) {
            $email = trim($key);

            $params['params']['client'] = $val->client;
            $params['params']['clientid'] = $val->suppid;
            $params['params']['trno'] = $trno;

            $result_layout = $this->default_layout($params, [], true);
            $pdf = $result_layout['pdf'];

            $info = [];
            $info['subject'] = 'Water Bill for the period of ' . $val->sdate1 . ' - ' . $val->sdate2;
            $info['title'] = 'Statement of Account';
            $info['view'] = 'emails.firstnotice';
            $info['msg'] = "<p>Dear " . (isset($val->clientname) ? " " . $val->clientname : '') . ",<br><br>
                      Your latest billing statement Reference Number " . $val->docno . " is now available.<br><br>
                      Statement Date: <b>" . $val->dateid . "</b><br>
                      Due Date: <b>" . $val->due . "</b><br>
                      Total Bill : <b>P" . number_format($val->amt + $result_layout['ar'] + $result_layout['surcharge'], 2) . "</b><br><br>
                      </p>
                      <br>";

            $info['email'] = $email;
            $info['filename'] = $this->modulename;
            $info['name'] = (isset($val->clientname) ? $val->clientname : '');
            $info['newformat'] = true;
            $info['pdf'] = $pdf;

            try {
              $resutmail = $this->othersClass->sbcsendemail($params, $info);
              if (!$resutmail['status']) {
                return ['status' => false, 'msg' => 'Sending email failed, ' . $val->clientname];
              } else {
                if ($email != "noemail") {
                  $this->coreFunctions->sbcupdate("lastock", ['isemail' => 1], ['trno' => $val->trno, 'suppid' => $val->suppid]);
                  $this->coreFunctions->sbcupdate("glstock", ['isemail' => 1], ['trno' => $val->trno, 'suppid' => $val->suppid]);
                }
              }
            } catch (Exception $ex) {
              $params['params']['dataid'] = $trno;
              $this->logger->sbcviewreportlog($params, "Failed to send to " . $email);
              $this->othersClass->logConsole('File:' . $ex->getFile() . ' Line:' . $ex->getLine() . " -> " . $ex->getMessage());
            }
          }
        }
      }

      continuehere:
      $counter += 1;
      if ($counter >= 10) {
        break;
      }
    }

    if (empty($arrclient)) {
      return ['status' => true, 'msg' => 'Nothing to send'];
    } else {
      $addonmsg = "";
      if ((count($arrclient) - $counter) > 0) {
        $addonmsg = ". Pending: " . $addonmsg;
      }
      return ['status' => true, 'msg' => 'Email send Success (' . $counter . ' customers)' . $addonmsg];
    }
  }
}//end class


// MAIL_DRIVER=smtp
// MAIL_HOST=smtp.gmail.com
// MAIL_PORT=587
// MAIL_USERNAME=jersonmandac@gmail.com
// MAIL_PASSWORD=*****
// MAIL_ENCRYPTION=TLS

// setup 2fa
// add pass key
