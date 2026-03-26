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

class dr
{

  private $modulename = "Delivery Receipt";
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
    $isposted = $this->othersClass->isposted($config);
    $fields = ['radioprint', 'radioreporttype', 'checked', 'approved', 'received', 'wh', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    data_set($col1, 'radioreporttype.label', 'Print as');
    $roptions = [
      ['label' => 'Delivery Receipt', 'value' => '0', 'color' => 'orange'],
      ['label' => 'HMALL', 'value' => '1', 'color' => 'orange']
    ];
    if (!$isposted) array_push($roptions, ['label' => 'Picklist', 'value' => '2', 'color' => 'orange']);
    data_set($col1, 'radioreporttype.options', $roptions);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $signatories = $this->othersClass->getSignatories($config);
    $checked = '';
    $approved = '';
    $received =  '';
    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'checked':
          $checked = $value->fieldvalue;
          break;
        case 'approved':
          $approved = $value->fieldvalue;
          break;
        case 'received':
          $received = $value->fieldvalue;
          break;
      }
    }
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '" . $checked . "' as checked,
      '" . $approved . "' as approved,
      '" . $received . "' as received,
      '' as wh,
      '0' as reporttype"
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
      right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,client.tel,client.bstyle,
      head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
      item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent, stock.amt as netamt,
      item.sizeid, ag.clientname as agname, item.brand,item.barcode,
      wh.client as whcode, wh.clientname as whname, ifnull(head.due,'') as due,stock.whid,
      whs.client as stockwh,head.clientname as transclient,head.VatType,head.tax, head.trnxtype, ifnull(uom.factor,1) as factor,
      client.bstyle
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client on client.client=head.client
      left join item on item.itemid=stock.itemid
      left join client as ag on ag.client=head.agent
      left join client as wh on wh.client=head.wh
      left join client as whs on whs.clientid=stock.whid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      where head.doc='dr' and head.trno='$trno' 
      UNION ALL
      select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
      right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,client.tel,client.bstyle,
      head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
      item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent, stock.amt as netamt,
      item.sizeid, ag.clientname as agname, item.brand,item.barcode,
      wh.client as whcode, wh.clientname as whname, ifnull(head.due,'') as due,stock.whid,
      whs.client as stockwh,head.clientname as transclient,head.VatType,head.tax, head.trnxtype, ifnull(uom.factor,1) as factor,
      client.bstyle
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client on client.clientid=head.clientid
      left join item on item.itemid=stock.itemid
      left join client as ag on ag.clientid=head.agentid
      left join client as wh on wh.clientid=head.whid
      left join client as whs on whs.clientid=stock.whid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      where head.doc='dr' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_so_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      $reporttype = $params['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case 0: // delivery receipt
          return $this->default_dr_pdf($params, $data);
          break;
        case 1: // hmall
          return $this->default_hmall_pdf($params, $data);
          break;
        case 2: // picklist
          return $this->default_picklist_pdf($params, $data);
          break;
      }
    }
  }

  public function default_dr_pdf($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $wh = $params['params']['dataparams']['wh'];

    $count = $page = 35;
    $totalext = 0;

    $font = '';
    $fontbold = '';
    $border = '1px solid';
    $fontsize = '11';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_dr_header_pdf($params, $data);

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $amt = number_format($data[$i]['amt'], $decimalprice);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_qty = $this->reporter->fixcolumn([$qty], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '15', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '15', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_itemname, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(270, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        }
        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 900) {
          $this->default_dr_header_pdf($params, $data);
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
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(160, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(160, 0, 'Credit Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(160, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Received the above article/s in', '', 'L', false);

    PDF::MultiCell(480, 0, '', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'good order and condition:', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    $user = $params['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $username = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


    if ((isset($username[0]['name']) ? $username[0]['name'] : '') != '') {
      $userr = $username[0]['name'];
    }

    PDF::MultiCell(155, 0, $userr, 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $params['params']['dataparams']['checked'], 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(185, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'B', 'L', false);

    PDF::MultiCell(480, 0, '', '', 'L', false, 0);
    PDF::MultiCell(185, 0, 'Signature above printed name', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Date', '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_dr_header_pdf($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code, name, address, tel from center where code='" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $font = '';
    $fontbold = '';
    $fontsize = '11';
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
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(540, 0, '', '', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Doc #: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(540, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Date: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]['dateid']) ? date('m/d/Y', strtotime($data[0]['dateid'])) : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 1, '', '120');

    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(720, 0, '', '', 'L', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Customer: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Terms: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Address: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Due: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['due']) ? $data[0]['due'] : ''), 'B', 'L', false, 1, '', '');
    PDF::SetFont($font, '', $fontsize);

    if (strlen($data[0]['address']) > 68) {
      PDF::MultiCell(0, 0, '', '', 'L', false, 1, '', '');
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Agent: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['agname']) ? $data[0]['agname'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Yourref: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Trnx Type: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['trnxtype']) ? $data[0]['trnxtype'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Ourref: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '', '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(270, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }


  public function default_hmall_pdf($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $wh = $params['params']['dataparams']['wh'];

    $count = $page = 35;
    $totalext = 0;

    $font = '';
    $fontbold = '';
    $border = '1px solid';
    $fontsize = '11';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->default_hmall_header_pdf($params, $data);

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $amt = number_format($data[$i]['amt'], $decimalprice);
        $netofdisc =  number_format($data[$i]['factor'] * $data[$i]['netamt'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '10', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '34', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '15', 0);
        $arr_netofdisc = $this->reporter->fixcolumn([$netofdisc], '15', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_amt, $arr_netofdisc, $arr_ext, $arr_qty, $arr_uom]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, true, 0, 'M', false);
          PDF::MultiCell(210, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'L', false, 0, '', '', true, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_netofdisc[$r]) ? $arr_netofdisc[$r] : ''), '', 'L', false, 0, '', '', true, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'L', false, 0, '', '', true, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'L', false, 0, '', '', true, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 1, '', '', true, true, 0, 'M', false);
        }
        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 900) {
          $this->default_hmall_header_pdf($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(620, 0, '', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(240, 0, '', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    $user = $params['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $username = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


    if ((isset($username[0]['name']) ? $username[0]['name'] : '') != '') {
      $userr = $username[0]['name'];
    }

    PDF::MultiCell(155, 0, $userr, '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(185, 0, '', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_hmall_header_pdf($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code, name, address, tel from center where code='" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $font = '';
    $fontbold = '';
    $border = '1px solid';
    $fontsize = '11';
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

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(450, 0, '', '', '', false, 0);
    PDF::MultiCell(200, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0);
    PDF::MultiCell(70, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(320, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(400, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false);

    PDF::MultiCell(320, 0, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false);

    PDF::MultiCell(120, 0, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 0);
    PDF::MultiCell(150, 0, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0);
    PDF::MultiCell(110, 0, (isset($data[0]['agname']) ? $data[0]['agname'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
  }

  public function default_picklist_pdf($params, $data)
  {

    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $wh = $params['params']['dataparams']['wh'];

    $count = $page = 35;
    $totalext = 0;

    $font = '';
    $fontbold = '';
    $border = '1px solid';
    $fontsize = '11';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_picklist_header_pdf($params, $data);

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $netofdisc =  number_format($data[$i]['factor'] * $data[$i]['netamt'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_qty = $this->reporter->fixcolumn([$qty], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '15', 0);
        $arr_barcode = $this->reporter->fixcolumn([$barcode], '20', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        $arr_netofdisc = $this->reporter->fixcolumn([$netofdisc], '15', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_barcode, $arr_itemname, $arr_netofdisc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          if ($r == 0) {
            PDF::MultiCell(70, 0, '', 'B', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          } else {
            PDF::MultiCell(70, 0, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          }

          PDF::MultiCell(70, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_netofdisc[$r]) ? $arr_netofdisc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        }
        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 900) {
          $this->default_dr_header_pdf($params, $data);
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
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(160, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(160, 0, 'Credit Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(160, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Received the above article/s in', '', 'L', false);

    PDF::MultiCell(480, 0, '', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'good order and condition:', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    $user = $params['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $username = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


    if ((isset($username[0]['name']) ? $username[0]['name'] : '') != '') {
      $userr = $username[0]['name'];
    }

    PDF::MultiCell(155, 0, $userr, 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $params['params']['dataparams']['checked'], 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(185, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'B', 'L', false);

    PDF::MultiCell(480, 0, '', '', 'L', false, 0);
    PDF::MultiCell(185, 0, 'Signature above printed name', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Date', '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_picklist_header_pdf($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code, name, address, tel from center where code='" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $font = '';
    $fontbold = '';
    $fontsize = '11';
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
    PDF::MultiCell(0, 0, strtoupper($username) . '- ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . ' ' . $headerdata[0]->code . "RSSC\n\n\n", '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(540, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Doc #: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, 'PICK LIST', '', 'L', false, 1, '', '120');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Trnx Type: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['trnxtype']) ? $data[0]['trnxtype'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Date: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Customer: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Terms: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Address: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Due: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['due']) ? $data[0]['due'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Agent: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['agent']) ? $data[0]['agent'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Yourref: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Warehouse: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Ourref: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '', '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "ISSUED", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "NET OF DISC", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }
}
