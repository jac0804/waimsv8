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

class sk
{

  private $modulename = "Sales Invoice";
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
    $fields = ['radioprint', 'checked', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
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
      '" . $received . "' as received"
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select head.trno, head.docno, head.doc, client.client, client.clientname, client.tin, client.tel, head.yourref, head.rem, client.contact, head.ourref, uom.factor,
    head.terms, ag.clientname as agname,  item.barcode, item.itemname, stock.isamt as amt, stock.uom, stock.amt as netamt, sum(case dr.doc when 'DR' then stock.ext else stock.ext*-1 end) as ext, 
    client.bstyle, client.addr, sum(case dr.doc when 'DR' then stock.isqty else stock.rrqty*-1 end) as qty
    from glhead as head 
    left join client on client.clientid=head.clientid
    left join client as ag on ag.clientid=head.agentid    
    left join cntnum on cntnum.trno=head.trno
    left join cntnum as dr on dr.svnum = cntnum.trno
    left join glstock as stock on stock.trno=dr.trno    
    left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
    left join item on item.itemid=stock.itemid    
    where cntnum.trno=" . $trno . "
    group by head.trno, head.docno, head.doc, client.client, client.clientname, client.tin, client.tel, head.yourref, head.rem, client.contact, head.ourref, uom.factor,
    head.terms, ag.clientname,  item.barcode, item.itemname, stock.isamt , stock.uom, stock.amt, client.bstyle, client.addr
    union all
    select head.trno, head.docno, head.doc, client.client, client.clientname, client.tin, client.tel, head.yourref, head.rem, client.contact, head.ourref, uom.factor,
    head.terms, ag.clientname as agname,  item.barcode, item.itemname, stock.isamt as amt, stock.uom, stock.amt as netamt, sum(case dr.doc when 'DR' then stock.ext else stock.ext*-1 end) as ext, 
    client.bstyle, client.addr, sum(case dr.doc when 'DR' then stock.isqty else stock.rrqty*-1 end) as qty
    from lahead as head 
    left join client on client.client=head.client
    left join client as ag on ag.client=head.agent    
    left join cntnum on cntnum.trno=head.trno
    left join cntnum as dr on dr.svnum = cntnum.trno
    left join glstock as stock on stock.trno=dr.trno    
    left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
    left join item on item.itemid=stock.itemid    
    where cntnum.trno=" . $trno . "
    group by head.trno, head.docno, head.doc, client.client, client.clientname, client.tin, client.tel, head.yourref, head.rem, client.contact, head.ourref, uom.factor,
    head.terms, ag.clientname,  item.barcode, item.itemname, stock.isamt , stock.uom, stock.amt, client.bstyle, client.addr
    union all
    select head.trno, head.docno, head.doc, client.client, client.clientname, client.tin, client.tel, head.yourref, head.rem, client.contact, head.ourref, uom.factor,
    head.terms, ag.clientname as agname, item.barcode, item.itemname, stock.isamt as amt, stock.uom, stock.amt as netamt, stock.ext, client.bstyle, client.addr, stock.isqty as qty
    from lahead as head    
    left join client on client.client=head.client
    left join client as ag on ag.client=head.agent
    left join cntnum on cntnum.trno = head.trno
    left join transnum on transnum.sitagging=cntnum.trno    
    left join hsostock as stock on stock.trno=transnum.trno
    left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
    left join item on item.itemid=stock.itemid
    where cntnum.trno=" . $trno . "
    union all
    select head.trno, head.docno, head.doc, client.client, client.clientname, client.tin, client.tel, head.yourref, head.rem, client.contact, head.ourref, uom.factor,
    head.terms, ag.clientname as agname, item.barcode, item.itemname, stock.isamt as amt, stock.uom, stock.amt as netamt, stock.ext, client.bstyle, client.addr, stock.isqty as qty
    from glhead as head    
    left join client on client.clientid=head.clientid
    left join client as ag on ag.clientid=head.agentid
    left join cntnum on cntnum.trno = head.trno
    left join transnum on transnum.sitagging=cntnum.trno    
    left join hsostock as stock on stock.trno=transnum.trno
    left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
    left join item on item.itemid=stock.itemid
    where cntnum.trno=" . $trno;
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_so_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_sk_pdf($params, $data);
    }
  }

  public function default_sk_pdf($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

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

    $this->default_sk_header_pdf($params, $data);

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        if ($data[$i]['barcode'] != '') {
          $maxrow = 1;
          $barcode = $data[$i]['barcode'];
          $itemname = $data[$i]['itemname'];
          $qty = number_format($data[$i]['qty'], $decimalqty);
          $uom = $data[$i]['uom'];
          $netamt = number_format($data[$i]['factor'] * $data[$i]['netamt'], $decimalcurr);
          $ext = number_format($data[$i]['ext'], $decimalcurr);

          $arr_barcode = $this->reporter->fixcolumn([$barcode], '10', 0);
          $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
          $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
          $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);
          $arr_netamt = $this->reporter->fixcolumn([$netamt], '15', 0);
          $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

          $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_netamt, $arr_ext]);

          for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, true, 0, 'M', false);
            PDF::MultiCell(250, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, true, 0, 'M', false);
            PDF::MultiCell(100, 0, ' ' . (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', false, 0, '', '', true, true, 0, 'M', false);
            PDF::MultiCell(110, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
        }
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
    PDF::MultiCell(600, 0, '', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

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

  public function default_sk_header_pdf($params, $data)
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

    PDF::MultiCell(25, 0, '', '', '', false, 0);
    PDF::MultiCell(300, 0, (isset($data[0]['client']) ? $data[0]['client'] : '') . ' - ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(375, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false);

    PDF::MultiCell(25, 0, '', '', '', false, 0);
    PDF::MultiCell(300, 0, (isset($data[0]['addr']) ? $data[0]['addr'] : ''), '', 'L', false);

    PDF::MultiCell(25, 0, '', '', '', false, 0);
    PDF::MultiCell(300, 0, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false);

    PDF::MultiCell(120, 0, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 0);
    PDF::MultiCell(150, 0, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0);
    PDF::MultiCell(110, 0, (isset($data[0]['agname']) ? $data[0]['agname'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
  }
}
