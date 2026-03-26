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

class notice_of_disconnection
{
  public $modulename = 'Notice Of Disconnection';
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

    $fields = ['radioprint', 'docno', 'dclientname']; //radioemail
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.action', 'lookupconsumtionno');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'docno.required', true);
    data_set($col1, 'dclientname.required', true);
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
    $query = $this->reportDefault($config, $config['params']['dataparams']['trno'],  $config['params']['dataparams']['clientid']);
    $data = $this->coreFunctions->opentable($query);



    $result_layout = $this->default_layout($config, $data);
    return $result_layout['pdf'];
  }

  public function reportDefault($config, $trno, $clientid, $blnSendMail = false)
  {
    $filter = " and stock.suppid = '$clientid'";
    if ($clientid == 0) {
      $filter = "";
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
    where stock.isqty<>0 and head.trno = $trno " . $filter . "
    union all
    select pm.name as projname, head.docno, date(head.dateid) as dateid, date_format(head.dateid,'%M %d, %Y') as tdateid, head.rem, date_format(date(head.due),'%M %d, %Y') as due, date_format(date(head.sdate1),'%M %d, %Y') as sdate1, date_format(date(head.sdate2),'%M %d, %Y') as sdate2,
    item.barcode, item.shortname as address, client.addr, client.client, client.clientname as customer, 
    stock.isqty as presconsumption,stock.prevqty as prevconsumption,stock.isqty2 as prevread, stock.isqty3 as presread, stock.isamt as rate, pm.surcharge,pm.reconfee, client.clientid
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join item on item.itemid = stock.itemid
    left join client on client.clientid = stock.suppid
    left join projectmasterfile as pm on pm.line = head.projectid
    where stock.isqty<>0 and head.trno = $trno " . $filter . "
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
      $arbalSCqry = "select ar.bal as value from arledger as ar left join coa on coa.acnoid=ar.acnoid where ar.trno=" . $cutomerdata[$key]->trno . " and ar.clientid=" . $cutomerdata[$key]->clientid . " and coa.alias='ARSC'";
      $surcharge = $this->coreFunctions->datareader($arbalSCqry, [], '', true);

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
      $pdf->MultiCell(250, 0,  trim(ucwords($word)) . " Only", "", 'L', false, 1);

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
      } while ($pdf->getY() < 620);

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


    $limit = "";
    if ($plimit != 0) {
      $limit = " limit " . $plimit;
    }

    $sql = "select * from (
            select h.trno, h.docno, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, sum(s.ext) as amt,
            date_format(date(h.sdate1),'%M %d, %Y') as sdate1, date_format(date(h.sdate2),'%M %d, %Y') as sdate2, ifnull(project.name,'') as projectname, h.address, month(h.dateid) as mon, 0 as posted
            from lastock as s left join lahead as h on h.trno=s.trno left join client on client.clientid=s.suppid
            left join projectmasterfile as project on project.line = h.projectid
            where h.doc='WM' and s.isqty<>0 and s.trno=" . $trno . " " . $filter . "
            group by h.trno, h.docno, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, h.sdate1, h.sdate2, project.name, h.address
            union all
            select h.trno, h.docno, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, sum(s.ext) as amt,
            date_format(date(h.sdate1),'%M %d, %Y') as sdate1, date_format(date(h.sdate2),'%M %d, %Y') as sdate2, ifnull(project.name,'') as projectname, h.address, month(h.dateid) as mon, 1 as posted
            from glstock as s left join glhead as h on h.trno=s.trno left join client on client.clientid=s.suppid
            left join projectmasterfile as project on project.line = h.projectid
            where h.doc='WM' and s.isqty<>0 and s.trno=" . $trno . " " . $filter . "
            group by h.trno, h.docno, s.suppid, h.dateid, h.due, client.client, client.clientid, client.email, client.clientname, h.sdate1, h.sdate2, project.name, h.address
            ) as client " . $limit;

    return $this->coreFunctions->opentable($sql);
  }

  public function sendemail($params)
  {
    $dataparams =  json_decode(json_encode(json_decode($params['params']['dataparams'])), true);
    $trno = $dataparams['trno'];

    $arrclient = $this->getCustomer($trno, $dataparams['clientid'], true, 10);

    $counter = 0;

    $companyqry = "select name,email,accountno,billingclerk,tel from center where code='" . $params['params']['center'] . "'";
    $company = json_decode(json_encode($this->coreFunctions->opentable($companyqry)), true);

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
            $info['subject'] = "\xF0\x9F\x9A\xA9 NOTICE OF DISCONNECTION: " . $val->projectname . ': ' . $val->clientname . ' ' . $val->address . ' Water Bill ' . $val->sdate1 . ' - ' . $val->sdate2;
            $info['title'] = 'Statement of Account';
            $info['view'] = 'emails.firstnotice';
            $info['msg'] = "<p>Dear " . (isset($val->clientname) ? " " . $val->clientname : '') . ",<br><br>
                      Please see attached file for your water bill period " . $val->sdate1 . " - " . $val->sdate2 . "<br><br>
                      For payments through bank deposits and online transfers, you may refer to these bank details:<br>
                      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>" . strtoupper($company[0]['name']) . "</b><br>
                      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>" . $company[0]['accountno'] . "</b><br><br>
                      Kindly send us your transaction slip once payment has been made and please email us at <b>" . $company[0]['email'] . "</b> or contact <b>" . $company[0]['tel'] . "</b>. Thank you.<br><br><br>
                      Best Regards,<br>
                      " . $company[0]['billingclerk'] . "<br><br>
                      <b>IMPORTANT REMINDER:</b><br>
                      &nbsp;&nbsp;&nbsp;<i>An extension of five (5) days from the due date shall be given to settle the amount in full with a surcharge of 2% per month.<br>
                      &nbsp;&nbsp;&nbsp;<FONT COLOR=red>A Notice of Disconnection will be given after the five-day extension period and disconnection for unsettled accounts will automatically occur 48-hours after receipt there.</FONT></i>
                      </p>
                      <br>";

            $info['email'] = $email;
            $info['filename'] = $this->modulename;
            $info['name'] = (isset($val->clientname) ? $val->clientname : '');
            $info['newformat'] = true;
            $info['pdf'] = $pdf;

            $resutmail = $this->othersClass->sbcsendemail($params, $info);
            if (!$resutmail['status']) {
              return ['status' => false, 'msg' => 'Sending email failed, ' . $val->clientname];
            } else {
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


    return ['status' => true, 'msg' => 'Email sent'];
  }
}//end class


// setup 2fa
// add pass key
