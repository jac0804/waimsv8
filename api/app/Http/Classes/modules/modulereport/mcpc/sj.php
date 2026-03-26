<?php

namespace App\Http\Classes\modules\modulereport\mcpc;

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

class sj
{

  private $modulename = "Sales Journal";
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



    $fields = ['radioprint', 'radiosjafti', 'radioinvoice', 'prepared', 'checked', 'delivered', 'approved', 'received', 'print'];


    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radiosjafti.label', 'Delivery Receipt');


    data_set($col1, 'radiosjafti.options', [
      ['label' => 'Default', 'value' => 'df', 'color' => 'orange'],
      ['label' => 'DR', 'value' => 'dr', 'color' => 'orange'],
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);

    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        'dr' as radiosjafti,
         '$username' as prepared,
        '' as approved,
        '' as received,
        '' as checked,
         '0' as invoice,
           '' as delivered
        "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, concat(right(head.docno,6)) as docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,item.itemname as itemname,
    stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,client.bstyle,
    item.sizeid, ag.clientname as agname, item.brand, concat(item.itemname,' ',ifnull(itemclass.cl_name,'')) as itemnames,
    wh.client as whcode, wh.clientname as whname from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join item_class as itemclass on itemclass.cl_id = item.class
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    where head.doc='sj' and head.trno='$trno'
    UNION ALL
    select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
     right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, concat(right(head.docno,6)) as docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,item.itemname as itemname,
     stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,client.bstyle,
    item.sizeid, ag.clientname as agname, item.brand,concat(item.itemname,' ',ifnull(itemclass.cl_name,'')) as itemnames,
    wh.client as whcode, wh.clientname as whname from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join item_class as itemclass on itemclass.cl_id = item.class
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    where head.doc='sj' and head.trno='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['radiosjafti'] == 'dr') {
      return $this->DReceipt_sj_PDF($params, $data);
    } else {
      return   $this->default_sj_PDF($params, $data);
    }
  }
  public function DReceipt_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
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
    PDF::SetFont($fontbold, '', 20);
    PDF::MultiCell(720, 0,  strtoupper($headerdata[0]->name), '', 'C', false, 0);


    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(36, 59, 117);
    PDF::MultiCell(570, 0, strtoupper($headerdata[0]->address), '', 'C', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(150, 0, '', '', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n\n");


    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(540, 0, 'DELIVERY RECEIPT', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'NO: ' . $data[0]['docno'], '', 'R', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 15);
    PDF::MultiCell(130, 15, "Customer Name: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);
    PDF::MultiCell(390, 15, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(80, 15, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);

    PDF::MultiCell(120, 15, (isset($data[0]['dateid']) ? date("m-d-Y", strtotime($data[0]['dateid'])) : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(160, 15, "Bussines Style / Name: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);
    PDF::MultiCell(360, 15, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(80, 15, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);
    PDF::MultiCell(120, 15, '', 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B');

    PDF::SetFont($font, '', 15);
    PDF::MultiCell(80, 15, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);
    PDF::MultiCell(440, 15, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', 15);
    PDF::MultiCell(80, 15, "Terms:", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);
    PDF::MultiCell(120, 15, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B');

    PDF::SetFont($font, '', 15);
    PDF::MultiCell(90, 15, "Ship to : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);
    PDF::MultiCell(430, 15, (isset($data[0]['ship']) ? $data[0]['shipto'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(80, 15, "TIN:", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize + 1);
    PDF::MultiCell(120, 15, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, "QUANTITY", 'TB', 'R', false, 0);
    PDF::MultiCell(60, 0, "U/M", 'TB', 'C', false, 0);
    PDF::MultiCell(420, 0, "DESCRIPTION", 'TB', 'C', false, 0);
    PDF::MultiCell(80, 0, "UNIT PRICE", 'TB', 'R', false, 0);
    PDF::MultiCell(90, 0, "SUBTOTAL", 'TB', 'R', false, 1);
  }

  public function DReceipt_sj_PDF($params, $data)
  {
    $prepared = $params['params']['dataparams']['prepared'];
    $invoce = $params['params']['dataparams']['invoice'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->DReceipt_sj_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $po = $data[$i]['yourref'];
        $so = $data[$i]['ourref'];

        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);

        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_so = $this->reporter->fixcolumn([$so], '15', 0);
        $arr_po = $this->reporter->fixcolumn([$po], '15', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_po, $arr_so, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          $printqty = '';
          if (isset($arr_qty[$r])) {
            $printqty = $arr_qty[$r] == 0 ? '' : $arr_qty[$r];
          }

          $printamt = '';
          if (isset($arr_amt[$r])) {
            $printamt = $arr_amt[$r] == 0 ? '' : $arr_amt[$r];
          }

          $printext = '';
          if (isset($arr_ext[$r])) {
            $printext = $arr_ext[$r] == 0 ? '' : $arr_ext[$r];
          }

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(70, 15, ' ' . $printqty, '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(420, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : '-'), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . $printamt, '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 15, ' ' .  $printext, '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 775) {
          $this->DReceipt_sj_header_PDF($params, $data);
        }
      }

      PDF::MultiCell(720, 0, "******NOTHING FOLLOWS******", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(0, 0, "\n");
    }


    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(470, 0, '', '', '', false, 0);
    PDF::MultiCell(250, 0, '', 'T', '', false, 1);



    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, '', '', '', false, 0);
    PDF::MultiCell(10, 0, '', 'B', '', false, 0);
    PDF::MultiCell(150, 0, 'TOTAL AMOUNT: ', 'B', 'L', false, 0);
    PDF::MultiCell(90, 0, number_format($totalext, 2), 'B', 'R', false, 1);

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(470, 0, '', '', '', false, 0);
    PDF::MultiCell(10, 0, '', 'B', '', false, 0);
    PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(90, 0, '', 'B', 'R', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, "\n");
    if ($data[0]['rem'] != '') {

      PDF::SetFont($font, '', 14);
      PDF::MultiCell(470, 0, '', '', '', false, 0);
      PDF::MultiCell(65, 0, 'Remarks:', '', 'L', false, 0);
      PDF::MultiCell(185, 0, '' .  $data[0]['rem'], '', 'L', false, 1);
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B', '', false, 1, '', '750');
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, "Prepared By:", '', 'L', false, 0, '',  '', true, 0, false, true, 0, '', true);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Checked By:', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', '', false, 0);
    PDF::MultiCell(100, 0, 'Deliverd By:', '', 'C', false, 0);
    PDF::MultiCell(270, 0, "", '', '', false, 1, '',  '', true, 0, false, true, 0, '', true);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 0, strtoupper($params['params']['dataparams']['prepared']), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, '', true);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, $params['params']['dataparams']['checked'], 'B', 'L', false, 0);
    PDF::MultiCell(20, 0, '', '', '', false, 0);
    PDF::MultiCell(120, 0, $params['params']['dataparams']['delivered'], 'B', 'C', false, 0);
    PDF::MultiCell(270, 0, "", '', '', false, 1, '',  '', true, 0, false, true, 0, '', true);

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(200, 0, (!$invoce ? 'INVOICE TO FOLLOW' : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(520, 0, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(720, 0, '', '', '', false, 1, '', '740');
    PDF::SetFont($font, '', 11);

    PDF::MultiCell(720, 0, 'Received the above articles in good order and condition.', '', 'R', false, 1);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(470, 0, 'By: ', '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(600, 0, 'Print Name', '', 'R', false, 1);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(470, 0, '', '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(625, 0, 'Authorized Signature', '', 'R', false, 1);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(720, 0, '"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX"', '', 'C', false, 1);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function default_sj_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_sj_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '32', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(260, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->default_sj_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(620, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(670, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(240, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function default_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 0, $this->modulename, '', 'L', false, 1);


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(480, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(480, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 20, "Terms:", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(260, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(60, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }
  public function notallowtoprint($config, $msg)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 20;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(20, 20);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(0, 0, $msg, '', 'L', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
